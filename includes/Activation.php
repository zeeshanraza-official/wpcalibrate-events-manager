<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager;

use WPCalibrate\EventsManager\Domain\Capabilities;
use WPCalibrate\EventsManager\Migrations\Migrator;
use WPCalibrate\EventsManager\Events\EventPostType;
use WPCalibrate\EventsManager\Events\EventTaxonomies;
use WPCalibrate\EventsManager\Venues\VenuePostType;
use WPCalibrate\EventsManager\Organizers\OrganizerPostType;
use WPCalibrate\EventsManager\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin activation routines.
 */
class Activation {

	/**
	 * Perform activation actions.
	 */
	public static function activate(): void {
		// 1. Assign capabilities
		Capabilities::register();

		// 2. Install database schema and migrations
		Migrator::run();

		// 3. Register entities so rewrites have endpoints
		EventPostType::register();
		VenuePostType::register();
		OrganizerPostType::register();
		EventTaxonomies::register();

		// 4. Initialize default settings if not exists
		SettingsManager::initialize_defaults();

		// 5. Store current plugin version
		update_option( 'wpcalibrate_events_version', WPCAL_VERSION );

		// 6. Flush rewrite rules
		flush_rewrite_rules();

		do_action( 'wpcalibrate_events_activated' );
	}
}
