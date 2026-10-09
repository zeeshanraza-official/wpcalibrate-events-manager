<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Infrastructure;

use WPCalibrate\EventsManager\Database\OccurrenceSchema;
use WPCalibrate\EventsManager\Domain\DateTimeHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generates environment and system diagnostics for troubleshooting.
 */
class Diagnostics {

	/**
	 * Get an array of diagnostic information without exposing secrets.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_report(): array {
		global $wpdb, $wp_version;

		$table_name    = OccurrenceSchema::get_table_name();
		$table_exists  = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;
		$occ_count     = $table_exists ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" ) : 0; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return [
			'plugin_version'        => WPCAL_VERSION,
			'database_version'      => get_option( 'wpcalibrate_events_db_version', 'none' ),
			'php_version'           => PHP_VERSION,
			'wordpress_version'     => $wp_version ?? 'unknown',
			'web_server'            => sanitize_text_field( $_SERVER['SERVER_SOFTWARE'] ?? 'unknown' ),
			'site_timezone'         => DateTimeHandler::get_site_timezone()->getName(),
			'gmt_offset'            => get_option( 'gmt_offset', 0 ),
			'wp_cron_enabled'       => ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ),
			'occurrence_table'      => $table_exists ? 'present' : 'missing',
			'total_occurrences'     => $occ_count,
			'multisite'             => ( function_exists( 'is_multisite' ) && is_multisite() ) ? 'yes' : 'no',
			'memory_limit'          => ini_get( 'memory_limit' ),
			'max_execution_time'    => ini_get( 'max_execution_time' ),
		];
	}

	/**
	 * Format diagnostics report as copyable plain text.
	 *
	 * @return string
	 */
	public static function get_formatted_text(): string {
		$report = self::get_report();
		$lines  = [ '### WPCalibrate Events Manager - System Diagnostics ###' ];

		foreach ( $report as $key => $value ) {
			$lines[] = sprintf( '%s: %s', str_replace( '_', ' ', ucwords( $key, '_' ) ), (string) $value );
		}

		return implode( "\n", $lines );
	}
}
