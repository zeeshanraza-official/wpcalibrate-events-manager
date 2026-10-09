<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Venues;

use WPCalibrate\EventsManager\Domain\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Venue custom post type (wpcal_venue).
 */
class VenuePostType {

	public const POST_TYPE = 'wpcal_venue';

	/**
	 * Register the post type with WordPress.
	 */
	public static function register(): void {
		$labels = [
			'name'                  => _x( 'Venues', 'Post type general name', 'wpcalibrate-events-manager' ),
			'singular_name'         => _x( 'Venue', 'Post type singular name', 'wpcalibrate-events-manager' ),
			'menu_name'             => _x( 'Venues', 'Admin Menu text', 'wpcalibrate-events-manager' ),
			'name_admin_bar'        => _x( 'Venue', 'Add New on Toolbar', 'wpcalibrate-events-manager' ),
			'add_new'               => __( 'Add New Venue', 'wpcalibrate-events-manager' ),
			'add_new_item'          => __( 'Add New Venue', 'wpcalibrate-events-manager' ),
			'new_item'              => __( 'New Venue', 'wpcalibrate-events-manager' ),
			'edit_item'             => __( 'Edit Venue', 'wpcalibrate-events-manager' ),
			'view_item'             => __( 'View Venue', 'wpcalibrate-events-manager' ),
			'all_items'             => __( 'Venues', 'wpcalibrate-events-manager' ),
			'search_items'          => __( 'Search Venues', 'wpcalibrate-events-manager' ),
			'not_found'             => __( 'No venues found.', 'wpcalibrate-events-manager' ),
			'not_found_in_trash'    => __( 'No venues found in Trash.', 'wpcalibrate-events-manager' ),
			'featured_image'        => __( 'Venue Photo', 'wpcalibrate-events-manager' ),
			'set_featured_image'    => __( 'Set venue photo', 'wpcalibrate-events-manager' ),
			'remove_featured_image' => __( 'Remove venue photo', 'wpcalibrate-events-manager' ),
			'use_featured_image'    => __( 'Use as venue photo', 'wpcalibrate-events-manager' ),
		];

		$capabilities = [
			'edit_post'              => Capabilities::EDIT_EVENTS,
			'read_post'              => Capabilities::READ_EVENTS,
			'delete_post'            => Capabilities::DELETE_EVENTS,
			'edit_posts'             => Capabilities::EDIT_EVENTS,
			'edit_others_posts'      => Capabilities::MANAGE_EVENTS,
			'publish_posts'          => Capabilities::PUBLISH_EVENTS,
			'read_private_posts'     => Capabilities::MANAGE_EVENTS,
			'delete_posts'           => Capabilities::DELETE_EVENTS,
			'delete_private_posts'   => Capabilities::MANAGE_EVENTS,
			'delete_published_posts' => Capabilities::DELETE_EVENTS,
			'delete_others_posts'    => Capabilities::MANAGE_EVENTS,
			'edit_private_posts'     => Capabilities::MANAGE_EVENTS,
			'edit_published_posts'   => Capabilities::EDIT_EVENTS,
		];

		$args = [
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'show_in_nav_menus'  => false,
			'show_in_rest'       => true,
			'rest_base'          => 'venues',
			'has_archive'        => false,
			'rewrite'            => [
				'slug'       => 'venue',
				'with_front' => false,
			],
			'capability_type'    => 'post',
			'capabilities'       => $capabilities,
			'map_meta_cap'       => true,
			'supports'           => [ 'title', 'editor', 'thumbnail' ],
		];

		register_post_type( self::POST_TYPE, apply_filters( 'wpcalibrate_venues_post_type_args', $args ) );
	}
}
