<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Events;

use DateTimeImmutable;
use WPCalibrate\EventsManager\Domain\DateTimeHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates Schema.org JSON-LD Event structured data.
 */
class EventSchema {

	/**
	 * Build Schema.org Event data array.
	 *
	 * @param int $event_id
	 * @return array<string, mixed>|null
	 */
	public static function generate( int $event_id ): ?array {
		$post = get_post( $event_id );
		if ( ! $post || $post->post_type !== EventPostType::POST_TYPE || $post->post_status !== 'publish' ) {
			return null;
		}

		$start_date = (string) get_post_meta( $event_id, '_wpcal_start_date', true );
		$start_time = (string) get_post_meta( $event_id, '_wpcal_start_time', true ) ?: '09:00';
		$end_date   = (string) get_post_meta( $event_id, '_wpcal_end_date', true ) ?: $start_date;
		$end_time   = (string) get_post_meta( $event_id, '_wpcal_end_time', true ) ?: '10:00';
		$all_day    = (bool) get_post_meta( $event_id, '_wpcal_all_day', true );
		$tz_string  = (string) get_post_meta( $event_id, '_wpcal_timezone', true );
		$event_type = (string) get_post_meta( $event_id, '_wpcal_event_type', true ) ?: 'physical';
		$status_val = (string) get_post_meta( $event_id, '_wpcal_status', true ) ?: 'publish';

		if ( empty( $start_date ) ) {
			return null;
		}

		$tz = DateTimeHandler::resolve_timezone( $tz_string );

		if ( $all_day ) {
			$norm       = DateTimeHandler::normalize_all_day_range( $start_date, $end_date, $tz );
			$start_iso  = $norm['start_local']->format( 'Y-m-d' );
			$end_iso    = $norm['end_local']->format( 'Y-m-d' );
		} else {
			$norm       = DateTimeHandler::normalize_timed_range( $start_date, $start_time, $end_date, $end_time, $tz );
			$start_iso  = $norm['start_local']->format( 'c' );
			$end_iso    = $norm['end_local']->format( 'c' );
		}

		// Status mapping
		$event_status = 'https://schema.org/EventScheduled';
		if ( $status_val === 'cancelled' ) {
			$event_status = 'https://schema.org/EventCancelled';
		} elseif ( $status_val === 'postponed' ) {
			$event_status = 'https://schema.org/EventPostponed';
		}

		// Attendance mode mapping
		$attendance_mode = match ( $event_type ) {
			'online' => 'https://schema.org/OnlineEventAttendanceMode',
			'hybrid' => 'https://schema.org/MixedEventAttendanceMode',
			default  => 'https://schema.org/OfflineEventAttendanceMode',
		};

		$schema = [
			'@context'              => 'https://schema.org',
			'@type'                 => 'Event',
			'name'                  => wp_strip_all_tags( $post->post_title ),
			'description'           => wp_strip_all_tags( $post->post_excerpt ?: $post->post_content ),
			'startDate'             => $start_iso,
			'endDate'               => $end_iso,
			'eventStatus'           => $event_status,
			'eventAttendanceMode'   => $attendance_mode,
			'url'                   => get_permalink( $event_id ),
		];

		// Image
		if ( has_post_thumbnail( $event_id ) ) {
			$thumb_url = get_the_post_thumbnail_url( $event_id, 'full' );
			if ( $thumb_url ) {
				$schema['image'] = [ $thumb_url ];
			}
		}

		// Location
		if ( $event_type === 'online' || $event_type === 'hybrid' ) {
			$online_url = get_post_meta( $event_id, '_wpcal_online_url', true );
			if ( $online_url ) {
				$schema['location'] = [
					'@type' => 'VirtualLocation',
					'url'   => esc_url_raw( (string) $online_url ),
				];
			}
		}

		$venue_id = (int) get_post_meta( $event_id, '_wpcal_venue_id', true );
		if ( $venue_id && ( $event_type === 'physical' || $event_type === 'hybrid' ) ) {
			$v_title   = get_the_title( $venue_id );
			$v_address = get_post_meta( $venue_id, '_wpcal_address', true );
			$v_city    = get_post_meta( $venue_id, '_wpcal_city', true );
			$v_state   = get_post_meta( $venue_id, '_wpcal_state', true );
			$v_zip     = get_post_meta( $venue_id, '_wpcal_postal_code', true );
			$v_country = get_post_meta( $venue_id, '_wpcal_country', true );

			$location_data = [
				'@type'   => 'Place',
				'name'    => $v_title,
				'address' => [
					'@type'           => 'PostalAddress',
					'streetAddress'   => $v_address,
					'addressLocality' => $v_city,
					'addressRegion'   => $v_state,
					'postalCode'      => $v_zip,
					'addressCountry'  => $v_country,
				],
			];

			if ( $event_type === 'hybrid' && isset( $schema['location'] ) ) {
				$schema['location'] = [ $schema['location'], $location_data ];
			} else {
				$schema['location'] = $location_data;
			}
		}

		// Organizer
		$organizer_ids = (array) get_post_meta( $event_id, '_wpcal_organizer_ids', true );
		if ( ! empty( $organizer_ids ) ) {
			$org_id = (int) reset( $organizer_ids );
			if ( $org_id ) {
				$schema['organizer'] = [
					'@type' => 'Organization',
					'name'  => get_the_title( $org_id ),
					'url'   => get_post_meta( $org_id, '_wpcal_organizer_website', true ) ?: get_permalink( $org_id ),
				];
			}
		}

		return apply_filters( 'wpcalibrate_event_schema_json', $schema, $event_id );
	}

	/**
	 * Output JSON-LD script tag in wp_head for single event page.
	 */
	public static function output_schema_head(): void {
		if ( is_singular( EventPostType::POST_TYPE ) ) {
			$event_id = get_the_ID();
			if ( $event_id ) {
				$data = self::generate( $event_id );
				if ( $data ) {
					echo "\n<!-- WPCalibrate Events Manager Schema -->\n";
					echo '<script type="application/ld+json">' . wp_json_encode( $data ) . "</script>\n";
				}
			}
		}
	}
}
