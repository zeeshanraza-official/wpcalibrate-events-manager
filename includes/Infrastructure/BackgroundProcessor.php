<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Infrastructure;

use WPCalibrate\EventsManager\Events\EventPostType;
use WPCalibrate\EventsManager\Recurrence\RecurrenceManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles scheduled cron jobs and batch recurrence maintenance safely.
 */
class BackgroundProcessor {

	public const CRON_HOOK_DAILY_MAINTENANCE = 'wpcalibrate_events_daily_maintenance';

	/**
	 * Register cron hooks.
	 */
	public function register_hooks(): void {
		add_action( self::CRON_HOOK_DAILY_MAINTENANCE, [ $this, 'run_daily_maintenance' ] );

		// Ensure scheduled event exists
		if ( ! wp_next_scheduled( self::CRON_HOOK_DAILY_MAINTENANCE ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK_DAILY_MAINTENANCE );
		}
	}

	/**
	 * Run daily maintenance: check recurring events that have 'never' end type and extend upcoming occurrences if needed.
	 */
	public function run_daily_maintenance(): void {
		$lock = get_transient( 'wpcal_maintenance_lock' );
		if ( $lock ) {
			return; // Avoid concurrent execution
		}
		set_transient( 'wpcal_maintenance_lock', 1, 300 ); // 5 minutes lock

		try {
			$recurring_events = get_posts( [
				'post_type'      => EventPostType::POST_TYPE,
				'post_status'    => 'publish',
				'meta_key'       => '_wpcal_recurrence_enabled',
				'meta_value'     => 1,
				'posts_per_page' => 50,
			] );

			$manager = new RecurrenceManager();
			foreach ( $recurring_events as $event ) {
				$manager->rebuild_event_occurrences( (int) $event->ID );
			}

			Logger::log( 'Daily recurrence maintenance completed', [ 'events_processed' => count( $recurring_events ) ] );
		} finally {
			delete_transient( 'wpcal_maintenance_lock' );
		}
	}

	/**
	 * Clear scheduled cron jobs on plugin deactivation.
	 */
	public static function clear_scheduled_hooks(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK_DAILY_MAINTENANCE );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK_DAILY_MAINTENANCE );
		}
	}
}
