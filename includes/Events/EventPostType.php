<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Events;

use WPCalibrate\EventsManager\Domain\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the Event custom post type (wpcal_event).
 */
class EventPostType {

	public const POST_TYPE = 'wpcal_event';

	/**
	 * Register the post type with WordPress.
	 */
	public static function register(): void {
		$labels = [
			'name'                  => _x( 'Events', 'Post type general name', 'wpcalibrate-events-manager' ),
			'singular_name'         => _x( 'Event', 'Post type singular name', 'wpcalibrate-events-manager' ),
			'menu_name'             => _x( 'Events', 'Admin Menu text', 'wpcalibrate-events-manager' ),
			'name_admin_bar'        => _x( 'Event', 'Add New on Toolbar', 'wpcalibrate-events-manager' ),
			'add_new'               => __( 'Add New Event', 'wpcalibrate-events-manager' ),
			'add_new_item'          => __( 'Add New Event', 'wpcalibrate-events-manager' ),
			'new_item'              => __( 'New Event', 'wpcalibrate-events-manager' ),
			'edit_item'             => __( 'Edit Event', 'wpcalibrate-events-manager' ),
			'view_item'             => __( 'View Event', 'wpcalibrate-events-manager' ),
			'all_items'             => __( 'All Events', 'wpcalibrate-events-manager' ),
			'search_items'          => __( 'Search Events', 'wpcalibrate-events-manager' ),
			'not_found'             => __( 'No events found.', 'wpcalibrate-events-manager' ),
			'not_found_in_trash'    => __( 'No events found in Trash.', 'wpcalibrate-events-manager' ),
			'featured_image'        => __( 'Event Image', 'wpcalibrate-events-manager' ),
			'set_featured_image'    => __( 'Set event image', 'wpcalibrate-events-manager' ),
			'remove_featured_image' => __( 'Remove event image', 'wpcalibrate-events-manager' ),
			'use_featured_image'    => __( 'Use as event image', 'wpcalibrate-events-manager' ),
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
			'show_in_menu'       => false, // Registered under WPCalibrate admin menu
			'show_in_nav_menus'  => true,
			'show_in_rest'       => true,
			'rest_base'          => 'events',
			'has_archive'        => 'events',
			'rewrite'            => [
				'slug'       => 'event',
				'with_front' => false,
			],
			'capability_type'    => 'post',
			'capabilities'       => $capabilities,
			'map_meta_cap'       => true,
			'supports'           => [ 'title', 'editor', 'excerpt', 'thumbnail', 'custom-fields' ],
		];

		register_post_type( self::POST_TYPE, apply_filters( 'wpcalibrate_events_post_type_args', $args ) );
	}
}
