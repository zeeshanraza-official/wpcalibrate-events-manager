<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Recurrence;

use WPCalibrate\EventsManager\Domain\DateTimeHandler;
use WPCalibrate\EventsManager\Events\EventPostType;
use WPCalibrate\EventsManager\Occurrences\OccurrenceRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service managing occurrence generation, synchronizing event metadata with occurrence records.
 */
class RecurrenceManager {

	private OccurrenceRepository $repository;

	public function __construct( ?OccurrenceRepository $repository = null ) {
		$this->repository = $repository ?: new OccurrenceRepository();
	}

	/**
	 * Register WordPress action hooks.
	 */
	public function register_hooks(): void {
		add_action( 'save_post_' . EventPostType::POST_TYPE, [ $this, 'on_save_event' ], 20, 2 );
		add_action( 'before_delete_post', [ $this, 'on_delete_event' ], 10, 1 );
		add_action( 'trashed_post', [ $this, 'on_trash_event' ], 10, 1 );
		add_action( 'untrashed_post', [ $this, 'on_untrash_event' ], 10, 1 );
	}

	/**
	 * Rebuild occurrences when an event is saved.
	 *
	 * @param int      $post_id
	 * @param \WP_Post $post
	 */
	public function on_save_event( int $post_id, \WP_Post $post ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		if ( $post->post_status !== 'publish' ) {
			// If not published, remove occurrences from public calendar table
			$this->repository->delete_for_event( $post_id );
			return;
		}

		$this->rebuild_event_occurrences( $post_id );
	}

	/**
	 * Remove occurrences when event is deleted.
	 *
	 * @param int $post_id
	 */
	public function on_delete_event( int $post_id ): void {
		if ( get_post_type( $post_id ) === EventPostType::POST_TYPE ) {
			$this->repository->delete_for_event( $post_id );
		}
	}

	/**
	 * Handle event post trashed.
	 *
	 * @param int $post_id
	 */
	public function on_trash_event( int $post_id ): void {
		if ( get_post_type( $post_id ) === EventPostType::POST_TYPE ) {
			$this->repository->delete_for_event( $post_id );
		}
	}

	/**
	 * Handle event post restored from trash.
	 *
	 * @param int $post_id
	 */
	public function on_untrash_event( int $post_id ): void {
		if ( get_post_type( $post_id ) === EventPostType::POST_TYPE ) {
			$this->rebuild_event_occurrences( $post_id );
		}
	}

	/**
	 * Synchronize and rebuild occurrences for an event.
	 *
	 * @param int $post_id
	 * @return int Number of occurrences generated
	 */
	public function rebuild_event_occurrences( int $post_id ): int {
		// Acquire simple lock per event to prevent race conditions
		$lock_key = 'wpcal_lock_event_' . $post_id;
		if ( get_transient( $lock_key ) ) {
			return 0;
		}
		set_transient( $lock_key, 1, 30 ); // 30 seconds lock

		try {
			$start_date = (string) get_post_meta( $post_id, '_wpcal_start_date', true );
			$start_time = (string) get_post_meta( $post_id, '_wpcal_start_time', true ) ?: '09:00';
			$end_date   = (string) get_post_meta( $post_id, '_wpcal_end_date', true ) ?: $start_date;
			$end_time   = (string) get_post_meta( $post_id, '_wpcal_end_time', true ) ?: '10:00';
			$all_day    = (bool) get_post_meta( $post_id, '_wpcal_all_day', true );
			$tz_string  = (string) get_post_meta( $post_id, '_wpcal_timezone', true );

			if ( empty( $start_date ) ) {
				$this->repository->delete_for_event( $post_id );
				return 0;
			}

			$timezone = DateTimeHandler::resolve_timezone( $tz_string );

			if ( $all_day ) {
				$normalized = DateTimeHandler::normalize_all_day_range( $start_date, $end_date, $timezone );
			} else {
				$normalized = DateTimeHandler::normalize_timed_range( $start_date, $start_time, $end_date, $end_time, $timezone );
			}

			$rule = RecurrenceRule::from_post_id( $post_id );

			$occurrences = RecurrenceCalculator::calculate(
				$normalized['start_local'],
				$normalized['end_local'],
				$all_day,
				$rule
			);

			$saved_count = $this->repository->save_occurrences( $post_id, $occurrences );

			// Invalidate calendar caches
			wp_cache_delete( 'wpcalibrate_calendar_cache', 'calendar' );
			delete_transient( 'wpcalibrate_calendar_feed' );

			do_action( 'wpcalibrate_occurrences_rebuilt', $post_id, $saved_count );

			return $saved_count;
		} finally {
			delete_transient( $lock_key );
		}
	}
}
