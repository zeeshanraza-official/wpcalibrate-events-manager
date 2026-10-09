<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages the custom occurrence table schema creation and verification.
 */
class OccurrenceSchema {

	public const TABLE_NAME = 'wpcalibrate_event_occurrences';

	/**
	 * Get full table name with WordPress table prefix.
	 *
	 * @return string
	 */
	public static function get_table_name(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Get table creation SQL definition for dbDelta.
	 *
	 * @return string
	 */
	public static function get_schema_sql(): string {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table_name} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_id bigint(20) unsigned NOT NULL,
			start_datetime_utc datetime NOT NULL,
			end_datetime_utc datetime NOT NULL,
			start_datetime_local datetime NOT NULL,
			end_datetime_local datetime NOT NULL,
			timezone varchar(64) NOT NULL DEFAULT 'UTC',
			all_day tinyint(1) NOT NULL DEFAULT 0,
			status varchar(20) NOT NULL DEFAULT 'publish',
			is_exception tinyint(1) NOT NULL DEFAULT 0,
			exception_type varchar(20) DEFAULT NULL,
			sequence int(10) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY event_id (event_id),
			KEY start_utc (start_datetime_utc),
			KEY end_utc (end_datetime_utc),
			KEY range_utc (start_datetime_utc, end_datetime_utc),
			KEY status_range (status, start_datetime_utc, end_datetime_utc),
			KEY event_sequence (event_id, sequence)
		) {$charset_collate};";
	}

	/**
	 * Create or update the table schema using dbDelta.
	 */
	public static function install(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( self::get_schema_sql() );
	}

	/**
	 * Drop the table completely (used only on explicit destructive uninstall).
	 */
	public static function drop(): void {
		global $wpdb;
		$table_name = self::get_table_name();
		// Table name is derived from internal constant and $wpdb->prefix; safe to execute.
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
