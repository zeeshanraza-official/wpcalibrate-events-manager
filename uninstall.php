<?php
/**
 * WPCalibrate Events Manager - Uninstall Handler
 *
 * Runs automatically when user clicks "Delete" on the plugin in WordPress admin.
 * Preserves user events and settings by default.
 * Completely purges plugin data ONLY IF 'delete_data_on_uninstall' was explicitly enabled in settings.
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// 1. Check data retention setting
$settings = get_option( 'wpcalibrate_events_settings', [] );
$should_delete = ! empty( $settings['delete_data_on_uninstall'] );

if ( ! $should_delete ) {
	// Preserve all data as per default configuration.
	return;
}

global $wpdb;

// 2. Drop the custom occurrence table
$table_name = $wpdb->prefix . 'wpcalibrate_event_occurrences';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

// 3. Delete all plugin posts (Events, Venues, Organizers)
$post_types = [ 'wpcal_event', 'wpcal_venue', 'wpcal_organizer' ];
foreach ( $post_types as $pt ) {
	$posts = get_posts( [
		'post_type'   => $pt,
		'post_status' => 'any',
		'numberposts' => -1,
		'fields'      => 'ids',
	] );

	foreach ( $posts as $post_id ) {
		wp_delete_post( (int) $post_id, true );
	}
}

// 4. Delete terms from custom taxonomies
$taxonomies = [ 'wpcal_event_category', 'wpcal_event_tag' ];
foreach ( $taxonomies as $taxonomy ) {
	$terms = get_terms( [
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
		'fields'     => 'ids',
	] );

	if ( ! is_wp_error( $terms ) && is_array( $terms ) ) {
		foreach ( $terms as $term_id ) {
			wp_delete_term( (int) $term_id, $taxonomy );
		}
	}
}

// 5. Delete plugin options
delete_option( 'wpcalibrate_events_settings' );
delete_option( 'wpcalibrate_events_db_version' );
delete_option( 'wpcalibrate_events_version' );
delete_option( 'wpcalibrate_events_license' );

// 6. Delete plugin transients
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpcal_%' OR option_name LIKE '_transient_timeout_wpcal_%'" );

// 7. Remove capabilities
$roles = [ 'administrator', 'editor' ];
$caps  = [
	'manage_wpcalibrate_events',
	'edit_wpcalibrate_events',
	'publish_wpcalibrate_events',
	'delete_wpcalibrate_events',
	'read_wpcalibrate_events',
];

foreach ( $roles as $r_name ) {
	$role = get_role( $r_name );
	if ( $role ) {
		foreach ( $caps as $c ) {
			$role->remove_cap( $c );
		}
	}
}
