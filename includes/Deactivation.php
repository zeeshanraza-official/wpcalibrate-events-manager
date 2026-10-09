<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager;

use WPCalibrate\EventsManager\Infrastructure\BackgroundProcessor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin deactivation routines.
 */
class Deactivation {

	/**
	 * Perform deactivation actions.
	 */
	public static function deactivate(): void {
		// Clear scheduled cron events
		BackgroundProcessor::clear_scheduled_hooks();

		// Delete locks
		delete_transient( 'wpcal_rebuild_lock' );

		// Flush rewrites
		flush_rewrite_rules();

		do_action( 'wpcalibrate_events_deactivated' );
	}
}
