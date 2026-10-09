<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\ImportExport;

use DateTimeImmutable;
use DateTimeZone;
use WPCalibrate\EventsManager\Domain\DateTimeHandler;
use WPCalibrate\EventsManager\Events\EventPostType;
use WPCalibrate\EventsManager\Occurrences\OccurrenceRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles .ics (iCalendar) generation and Google Calendar links.
 */
class IcsExporter {

	/**
	 * Build Google Calendar URL for an event.
	 *
	 * @param int $event_id
	 * @return string
	 */
	public static function get_google_calendar_url( int $event_id ): string {
		$title      = get_the_title( $event_id );
		$content    = wp_strip_all_tags( get_post_field( 'post_content', $event_id ) );
		$start_date = (string) get_post_meta( $event_id, '_wpcal_start_date', true );
		$start_time = (string) get_post_meta( $event_id, '_wpcal_start_time', true ) ?: '09:00';
		$end_date   = (string) get_post_meta( $event_id, '_wpcal_end_date', true ) ?: $start_date;
		$end_time   = (string) get_post_meta( $event_id, '_wpcal_end_time', true ) ?: '10:00';
		$all_day    = (bool) get_post_meta( $event_id, '_wpcal_all_day', true );
		$tz_string  = (string) get_post_meta( $event_id, '_wpcal_timezone', true );

		$tz = DateTimeHandler::resolve_timezone( $tz_string );

		if ( $all_day ) {
			$norm = DateTimeHandler::normalize_all_day_range( $start_date, $end_date, $tz );
			// All day in Google Calendar is YYYYMMDD/YYYYMMDD (exclusive end, so +1 day)
			$dates_param = $norm['start_local']->format( 'Ymd' ) . '/' . $norm['end_local']->modify( '+1 day' )->format( 'Ymd' );
		} else {
			$norm        = DateTimeHandler::normalize_timed_range( $start_date, $start_time, $end_date, $end_time, $tz );
			$dates_param = $norm['start_utc']->format( 'Ymd\THis\Z' ) . '/' . $norm['end_utc']->format( 'Ymd\THis\Z' );
		}

		$venue_id = (int) get_post_meta( $event_id, '_wpcal_venue_id', true );
		$location = '';
		if ( $venue_id ) {
			$location = get_the_title( $venue_id );
			$address  = get_post_meta( $venue_id, '_wpcal_address', true );
			if ( $address ) {
				$location .= ', ' . $address;
			}
		}

		$params = [
			'action'   => 'TEMPLATE',
			'text'     => $title,
			'dates'    => $dates_param,
			'details'  => substr( $content, 0, 1000 ),
			'location' => $location,
		];

		return 'https://calendar.google.com/calendar/render?' . http_build_query( $params );
	}

	/**
	 * Generate single event .ics content string.
	 *
	 * @param int $event_id
	 * @return string
	 */
	public static function generate_single_ics( int $event_id ): string {
		$post = get_post( $event_id );
		if ( ! $post || $post->post_type !== EventPostType::POST_TYPE ) {
			return '';
		}

		$title      = $post->post_title;
		$desc       = wp_strip_all_tags( $post->post_content );
		$start_date = (string) get_post_meta( $event_id, '_wpcal_start_date', true );
		$start_time = (string) get_post_meta( $event_id, '_wpcal_start_time', true ) ?: '09:00';
		$end_date   = (string) get_post_meta( $event_id, '_wpcal_end_date', true ) ?: $start_date;
		$end_time   = (string) get_post_meta( $event_id, '_wpcal_end_time', true ) ?: '10:00';
		$all_day    = (bool) get_post_meta( $event_id, '_wpcal_all_day', true );
		$tz_string  = (string) get_post_meta( $event_id, '_wpcal_timezone', true );
		$uid        = get_post_meta( $event_id, '_wpcal_uid', true ) ?: 'wpcal-' . $event_id . '@' . wp_parse_url( home_url(), PHP_URL_HOST );

		$tz = DateTimeHandler::resolve_timezone( $tz_string );

		if ( $all_day ) {
			$norm       = DateTimeHandler::normalize_all_day_range( $start_date, $end_date, $tz );
			$dt_start   = 'DTSTART;VALUE=DATE:' . $norm['start_local']->format( 'Ymd' );
			$dt_end     = 'DTEND;VALUE=DATE:' . $norm['end_local']->modify( '+1 day' )->format( 'Ymd' );
		} else {
			$norm       = DateTimeHandler::normalize_timed_range( $start_date, $start_time, $end_date, $end_time, $tz );
			$dt_start   = 'DTSTART:' . $norm['start_utc']->format( 'Ymd\THis\Z' );
			$dt_end     = 'DTEND:' . $norm['end_utc']->format( 'Ymd\THis\Z' );
		}

		$now_utc = gmdate( 'Ymd\THis\Z' );

		$ics  = "BEGIN:VCALENDAR\r\n";
		$ics .= "VERSION:2.0\r\n";
		$ics .= "PRODID:-//WPCalibrate//WPCalibrate Events Manager 1.0//EN\r\n";
		$ics .= "CALSCALE:GREGORIAN\r\n";
		$ics .= "METHOD:PUBLISH\r\n";
		$ics .= "BEGIN:VEVENT\r\n";
		$ics .= "UID:" . self::escape_ics( $uid ) . "\r\n";
		$ics .= "DTSTAMP:" . $now_utc . "\r\n";
		$ics .= $dt_start . "\r\n";
		$ics .= $dt_end . "\r\n";
		$ics .= "SUMMARY:" . self::escape_ics( $title ) . "\r\n";
		$ics .= "DESCRIPTION:" . self::escape_ics( $desc ) . "\r\n";
		$ics .= "URL:" . esc_url_raw( get_permalink( $event_id ) ) . "\r\n";
		$ics .= "END:VEVENT\r\n";
		$ics .= "END:VCALENDAR\r\n";

		return $ics;
	}

	/**
	 * Generate entire public calendar feed .ics.
	 *
	 * @return string
	 */
	public static function generate_feed_ics(): string {
		$repo       = new OccurrenceRepository();
		$start_utc  = gmdate( 'Y-m-d H:i:s', strtotime( '-1 month' ) );
		$end_utc    = gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
		$occurrences = $repo->get_occurrences_for_range( $start_utc, $end_utc, [ 'limit' => 200 ] );

		$now_utc = gmdate( 'Ymd\THis\Z' );

		$ics  = "BEGIN:VCALENDAR\r\n";
		$ics .= "VERSION:2.0\r\n";
		$ics .= "PRODID:-//WPCalibrate//WPCalibrate Events Manager 1.0//EN\r\n";
		$ics .= "CALSCALE:GREGORIAN\r\n";
		$ics .= "X-WR-CALNAME:" . self::escape_ics( get_bloginfo( 'name' ) . ' Events' ) . "\r\n";

		foreach ( $occurrences as $occ ) {
			$event_id  = (int) $occ->event_id;
			$uid       = 'wpcal-' . $event_id . '-' . $occ->id . '@' . wp_parse_url( home_url(), PHP_URL_HOST );
			$start_raw = strtotime( $occ->start_datetime_utc );
			$end_raw   = strtotime( $occ->end_datetime_utc );

			$ics .= "BEGIN:VEVENT\r\n";
			$ics .= "UID:" . self::escape_ics( $uid ) . "\r\n";
			$ics .= "DTSTAMP:" . $now_utc . "\r\n";
			if ( $occ->all_day ) {
				$ics .= "DTSTART;VALUE=DATE:" . gmdate( 'Ymd', $start_raw ) . "\r\n";
				$ics .= "DTEND;VALUE=DATE:" . gmdate( 'Ymd', strtotime( '+1 day', $end_raw ) ) . "\r\n";
			} else {
				$ics .= "DTSTART:" . gmdate( 'Ymd\THis\Z', $start_raw ) . "\r\n";
				$ics .= "DTEND:" . gmdate( 'Ymd\THis\Z', $end_raw ) . "\r\n";
			}
			$ics .= "SUMMARY:" . self::escape_ics( (string) $occ->event_title ) . "\r\n";
			$ics .= "URL:" . esc_url_raw( get_permalink( $event_id ) ) . "\r\n";
			$ics .= "END:VEVENT\r\n";
		}

		$ics .= "END:VCALENDAR\r\n";
		return $ics;
	}

	/**
	 * Escape string for iCalendar RFC 5545 specifications.
	 *
	 * @param string $text
	 * @return string
	 */
	public static function escape_ics( string $text ): string {
		$text = str_replace( [ '\\', ';', ',', "\n", "\r" ], [ '\\\\', '\;', '\,', '\n', '' ], $text );
		return trim( $text );
	}
}
