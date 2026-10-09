<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\ImportExport;

use DateTimeImmutable;
use DateTimeZone;
use WPCalibrate\EventsManager\Domain\Capabilities;
use WPCalibrate\EventsManager\Domain\DateTimeHandler;
use WPCalibrate\EventsManager\Events\EventPostType;
use WPCalibrate\EventsManager\Recurrence\RecurrenceManager;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles administrative .ics import with strict validation, security limits, and duplicate avoidance.
 */
class IcsImporter {

	public const MAX_FILE_SIZE = 5242880; // 5 MB
	public const MAX_EVENTS    = 200;

	/**
	 * Import an uploaded .ics file.
	 *
	 * @param array<string, mixed> $file PHP $_FILES entry
	 * @return int|WP_Error Number of imported events or WP_Error
	 */
	public static function import_uploaded_file( array $file ): int|WP_Error {
		if ( ! current_user_can( Capabilities::MANAGE_EVENTS ) ) {
			return new WP_Error( 'unauthorized', __( 'You do not have permission to import events.', 'wpcalibrate-events-manager' ) );
		}

		if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( (string) $file['tmp_name'] ) ) {
			return new WP_Error( 'no_file', __( 'No valid file uploaded.', 'wpcalibrate-events-manager' ) );
		}

		if ( (int) $file['size'] > self::MAX_FILE_SIZE ) {
			return new WP_Error( 'file_too_large', __( 'File size exceeds maximum allowed limit (5MB).', 'wpcalibrate-events-manager' ) );
		}

		// Read content safely
		$content = file_get_contents( (string) $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( $content === false || empty( $content ) ) {
			return new WP_Error( 'empty_file', __( 'Could not read file content.', 'wpcalibrate-events-manager' ) );
		}

		return self::parse_and_import( $content );
	}

	/**
	 * Parse VCALENDAR / VEVENT data and import events.
	 *
	 * @param string $ics_content
	 * @return int|WP_Error
	 */
	public static function parse_and_import( string $ics_content ): int|WP_Error {
		if ( ! str_contains( $ics_content, 'BEGIN:VCALENDAR' ) || ! str_contains( $ics_content, 'BEGIN:VEVENT' ) ) {
			return new WP_Error( 'invalid_format', __( 'Invalid iCalendar format: Missing VCALENDAR or VEVENT tags.', 'wpcalibrate-events-manager' ) );
		}

		// Split by VEVENT
		preg_match_all( '/BEGIN:VEVENT(.*?)END:VEVENT/s', $ics_content, $matches );
		if ( empty( $matches[1] ) ) {
			return 0;
		}

		$events_data   = array_slice( $matches[1], 0, self::MAX_EVENTS );
		$imported_count = 0;
		$rec_manager   = new RecurrenceManager();

		foreach ( $events_data as $event_raw ) {
			$parsed = self::parse_vevent_block( $event_raw );
			if ( empty( $parsed['summary'] ) || empty( $parsed['dtstart'] ) ) {
				continue;
			}

			// Prevent duplicate import using UID
			if ( ! empty( $parsed['uid'] ) ) {
				$existing = get_posts( [
					'post_type'   => EventPostType::POST_TYPE,
					'meta_key'    => '_wpcal_uid',
					'meta_value'  => $parsed['uid'],
					'post_status' => 'any',
					'numberposts' => 1,
				] );
				if ( ! empty( $existing ) ) {
					continue; // Skip duplicate
				}
			}

			// Create event post
			$post_id = wp_insert_post( [
				'post_title'   => sanitize_text_field( $parsed['summary'] ),
				'post_content' => wp_kses_post( $parsed['description'] ?? '' ),
				'post_status'  => 'publish',
				'post_type'    => EventPostType::POST_TYPE,
			] );

			if ( is_wp_error( $post_id ) || ! $post_id ) {
				continue;
			}

			// Save metadata
			update_post_meta( $post_id, '_wpcal_start_date', $parsed['start_date'] );
			update_post_meta( $post_id, '_wpcal_start_time', $parsed['start_time'] );
			update_post_meta( $post_id, '_wpcal_end_date', $parsed['end_date'] );
			update_post_meta( $post_id, '_wpcal_end_time', $parsed['end_time'] );
			update_post_meta( $post_id, '_wpcal_all_day', $parsed['all_day'] ? 1 : 0 );
			update_post_meta( $post_id, '_wpcal_status', 'publish' );
			if ( ! empty( $parsed['uid'] ) ) {
				update_post_meta( $post_id, '_wpcal_uid', sanitize_text_field( $parsed['uid'] ) );
			}

			// Build occurrences
			$rec_manager->rebuild_event_occurrences( (int) $post_id );
			$imported_count++;
		}

		return $imported_count;
	}

	/**
	 * Parse individual VEVENT block string.
	 *
	 * @param string $block
	 * @return array<string, mixed>
	 */
	private static function parse_vevent_block( string $block ): array {
		$data = [
			'summary'     => '',
			'description' => '',
			'uid'         => '',
			'dtstart'     => '',
			'dtend'       => '',
			'start_date'  => current_time( 'Y-m-d' ),
			'start_time'  => '09:00',
			'end_date'    => current_time( 'Y-m-d' ),
			'end_time'    => '10:00',
			'all_day'     => false,
		];

		// Unfold folded lines (RFC 5545 CRLF followed by space/tab)
		$unfolded = preg_replace( "/\r\n[ \t]/", '', $block );
		$lines    = explode( "\n", (string) $unfolded );

		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( empty( $line ) ) {
				continue;
			}

			if ( str_starts_with( $line, 'SUMMARY' ) ) {
				$parts           = explode( ':', $line, 2 );
				$data['summary'] = self::unescape_ics( $parts[1] ?? '' );
			} elseif ( str_starts_with( $line, 'DESCRIPTION' ) ) {
				$parts               = explode( ':', $line, 2 );
				$data['description'] = self::unescape_ics( $parts[1] ?? '' );
			} elseif ( str_starts_with( $line, 'UID' ) ) {
				$parts       = explode( ':', $line, 2 );
				$data['uid'] = trim( $parts[1] ?? '' );
			} elseif ( str_starts_with( $line, 'DTSTART' ) ) {
				$parts           = explode( ':', $line, 2 );
				$data['dtstart'] = trim( $parts[1] ?? '' );
				$is_value_date   = str_contains( $parts[0], 'VALUE=DATE' );
				$parsed_date     = self::parse_ics_datetime( $data['dtstart'], $is_value_date );
				$data['start_date'] = $parsed_date['date'];
				$data['start_time'] = $parsed_date['time'];
				$data['all_day']    = $parsed_date['all_day'];
			} elseif ( str_starts_with( $line, 'DTEND' ) ) {
				$parts         = explode( ':', $line, 2 );
				$data['dtend'] = trim( $parts[1] ?? '' );
				$is_value_date = str_contains( $parts[0], 'VALUE=DATE' );
				$parsed_date   = self::parse_ics_datetime( $data['dtend'], $is_value_date );
				$data['end_date'] = $parsed_date['date'];
				$data['end_time'] = $parsed_date['time'];
			}
		}

		if ( empty( $data['end_date'] ) ) {
			$data['end_date'] = $data['start_date'];
			$data['end_time'] = $data['start_time'];
		}

		return $data;
	}

	/**
	 * Parse iCalendar datetime string format (YYYYMMDD or YYYYMMDDTHHMMSSZ).
	 *
	 * @param string $dt
	 * @param bool   $force_date_only
	 * @return array{date: string, time: string, all_day: bool}
	 */
	private static function parse_ics_datetime( string $dt, bool $force_date_only ): array {
		$clean = preg_replace( '/[^0-9T]/', '', $dt );

		if ( $force_date_only || strlen( (string) $clean ) === 8 ) {
			$date = sprintf( '%s-%s-%s', substr( $clean, 0, 4 ), substr( $clean, 4, 2 ), substr( $clean, 6, 2 ) );
			return [
				'date'    => $date,
				'time'    => '00:00',
				'all_day' => true,
			];
		}

		if ( strlen( (string) $clean ) >= 15 ) {
			$year  = substr( $clean, 0, 4 );
			$month = substr( $clean, 4, 2 );
			$day   = substr( $clean, 6, 2 );
			$hour  = substr( $clean, 9, 2 );
			$min   = substr( $clean, 11, 2 );

			return [
				'date'    => sprintf( '%s-%s-%s', $year, $month, $day ),
				'time'    => sprintf( '%s:%s', $hour, $min ),
				'all_day' => false,
			];
		}

		return [
			'date'    => current_time( 'Y-m-d' ),
			'time'    => '09:00',
			'all_day' => false,
		];
	}

	/**
	 * Unescape RFC 5545 text sequences.
	 *
	 * @param string $text
	 * @return string
	 */
	private static function unescape_ics( string $text ): string {
		return str_replace( [ '\,', '\;', '\n', '\\\\' ], [ ',', ';', "\n", '\\' ], trim( $text ) );
	}
}
