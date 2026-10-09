<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Migrations;

use WPCalibrate\EventsManager\Database\OccurrenceSchema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles database schema versions and incremental upgrade migrations.
 */
class Migrator {

	public const OPTION_KEY = 'wpcalibrate_events_db_version';

	/**
	 * Run required migrations based on stored database version.
	 */
	public static function run(): void {
		$installed_version = get_option( self::OPTION_KEY, '0.0.0' );

		if ( version_compare( (string) $installed_version, WPCAL_DB_VERSION, '<' ) ) {
			self::migrate( (string) $installed_version );
			update_option( self::OPTION_KEY, WPCAL_DB_VERSION );
		}
	}

	/**
	 * Execute specific migration steps sequentially.
	 *
	 * @param string $from_version
	 */
	private static function migrate( string $from_version ): void {
		// Base migration: 1.0.0 schema
		if ( version_compare( $from_version, '1.0.0', '<' ) ) {
			OccurrenceSchema::install();
		}

		do_action( 'wpcalibrate_events_migrated', $from_version, WPCAL_DB_VERSION );
	}
}
