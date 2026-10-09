<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Occurrences;

use WPCalibrate\EventsManager\Recurrence\RecurrenceManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles individual occurrence exceptions (cancellation, time modifications) without breaking the series.
 */
class OccurrenceException {

	/**
	 * Cancel a single occurrence of an event.
	 *
	 * @param int    $event_id
	 * @param string $date Y-m-d
	 * @return bool
	 */
	public static function cancel_occurrence( int $event_id, string $date ): bool {
		$exceptions = get_post_meta( $event_id, '_wpcal_recurrence_exceptions', true );
		if ( ! is_array( $exceptions ) ) {
			$exceptions = [];
		}

		$exceptions[ $date ] = [
			'status' => 'cancelled',
		];

		update_post_meta( $event_id, '_wpcal_recurrence_exceptions', $exceptions );

		// Rebuild occurrences
		$manager = new RecurrenceManager();
		$manager->rebuild_event_occurrences( $event_id );

		return true;
	}

	/**
	 * Modify a single occurrence time.
	 *
	 * @param int    $event_id
	 * @param string $date       Y-m-d
	 * @param string $start_time H:i
	 * @param string $end_time   H:i
	 * @return bool
	 */
	public static function modify_occurrence_time( int $event_id, string $date, string $start_time, string $end_time ): bool {
		$exceptions = get_post_meta( $event_id, '_wpcal_recurrence_exceptions', true );
		if ( ! is_array( $exceptions ) ) {
			$exceptions = [];
		}

		$exceptions[ $date ] = [
			'status'     => 'publish',
			'start_time' => sanitize_text_field( $start_time ),
			'end_time'   => sanitize_text_field( $end_time ),
		];

		update_post_meta( $event_id, '_wpcal_recurrence_exceptions', $exceptions );

		$manager = new RecurrenceManager();
		$manager->rebuild_event_occurrences( $event_id );

		return true;
	}

	/**
	 * Remove an exception for a specific occurrence date.
	 *
	 * @param int    $event_id
	 * @param string $date Y-m-d
	 * @return bool
	 */
	public static function remove_exception( int $event_id, string $date ): bool {
		$exceptions = get_post_meta( $event_id, '_wpcal_recurrence_exceptions', true );
		if ( ! is_array( $exceptions ) || ! isset( $exceptions[ $date ] ) ) {
			return false;
		}

		unset( $exceptions[ $date ] );
		update_post_meta( $event_id, '_wpcal_recurrence_exceptions', $exceptions );

		$manager = new RecurrenceManager();
		$manager->rebuild_event_occurrences( $event_id );

		return true;
	}
}
