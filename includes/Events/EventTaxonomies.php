<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Events;

use WPCalibrate\EventsManager\Domain\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers event categories and event tags taxonomies.
 */
class EventTaxonomies {

	public const TAXONOMY_CATEGORY = 'wpcal_event_category';
	public const TAXONOMY_TAG      = 'wpcal_event_tag';

	/**
	 * Register taxonomies with WordPress.
	 */
	public static function register(): void {
		self::register_category();
		self::register_tag();
	}

	/**
	 * Register Event Category taxonomy.
	 */
	private static function register_category(): void {
		$labels = [
			'name'              => _x( 'Event Categories', 'taxonomy general name', 'wpcalibrate-events-manager' ),
			'singular_name'     => _x( 'Event Category', 'taxonomy singular name', 'wpcalibrate-events-manager' ),
			'search_items'      => __( 'Search Event Categories', 'wpcalibrate-events-manager' ),
			'all_items'         => __( 'All Event Categories', 'wpcalibrate-events-manager' ),
			'parent_item'       => __( 'Parent Category', 'wpcalibrate-events-manager' ),
			'parent_item_colon' => __( 'Parent Category:', 'wpcalibrate-events-manager' ),
			'edit_item'         => __( 'Edit Category', 'wpcalibrate-events-manager' ),
			'update_item'       => __( 'Update Category', 'wpcalibrate-events-manager' ),
			'add_new_item'      => __( 'Add New Category', 'wpcalibrate-events-manager' ),
			'new_item_name'     => __( 'New Category Name', 'wpcalibrate-events-manager' ),
			'menu_name'         => __( 'Categories', 'wpcalibrate-events-manager' ),
		];

		$capabilities = [
			'manage_terms' => Capabilities::MANAGE_EVENTS,
			'edit_terms'   => Capabilities::MANAGE_EVENTS,
			'delete_terms' => Capabilities::MANAGE_EVENTS,
			'assign_terms' => Capabilities::EDIT_EVENTS,
		];

		$args = [
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'show_in_rest'      => true,
			'rest_base'         => 'event-categories',
			'rewrite'           => [
				'slug'       => 'event-category',
				'with_front' => false,
			],
			'capabilities'      => $capabilities,
		];

		register_taxonomy( self::TAXONOMY_CATEGORY, [ EventPostType::POST_TYPE ], apply_filters( 'wpcalibrate_category_taxonomy_args', $args ) );
	}

	/**
	 * Register Event Tag taxonomy.
	 */
	private static function register_tag(): void {
		$labels = [
			'name'                       => _x( 'Event Tags', 'taxonomy general name', 'wpcalibrate-events-manager' ),
			'singular_name'              => _x( 'Event Tag', 'taxonomy singular name', 'wpcalibrate-events-manager' ),
			'search_items'               => __( 'Search Event Tags', 'wpcalibrate-events-manager' ),
			'popular_items'              => __( 'Popular Event Tags', 'wpcalibrate-events-manager' ),
			'all_items'                  => __( 'All Event Tags', 'wpcalibrate-events-manager' ),
			'edit_item'                  => __( 'Edit Tag', 'wpcalibrate-events-manager' ),
			'update_item'                => __( 'Update Tag', 'wpcalibrate-events-manager' ),
			'add_new_item'               => __( 'Add New Tag', 'wpcalibrate-events-manager' ),
			'new_item_name'              => __( 'New Tag Name', 'wpcalibrate-events-manager' ),
			'separate_items_with_commas' => __( 'Separate tags with commas', 'wpcalibrate-events-manager' ),
			'add_or_remove_items'        => __( 'Add or remove tags', 'wpcalibrate-events-manager' ),
			'choose_from_most_used'      => __( 'Choose from the most used tags', 'wpcalibrate-events-manager' ),
			'not_found'                  => __( 'No tags found.', 'wpcalibrate-events-manager' ),
			'menu_name'                  => __( 'Tags', 'wpcalibrate-events-manager' ),
		];

		$capabilities = [
			'manage_terms' => Capabilities::MANAGE_EVENTS,
			'edit_terms'   => Capabilities::MANAGE_EVENTS,
			'delete_terms' => Capabilities::MANAGE_EVENTS,
			'assign_terms' => Capabilities::EDIT_EVENTS,
		];

		$args = [
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => false,
			'query_var'         => true,
			'show_in_rest'      => true,
			'rest_base'         => 'event-tags',
			'rewrite'           => [
				'slug'       => 'event-tag',
				'with_front' => false,
			],
			'capabilities'      => $capabilities,
		];

		register_taxonomy( self::TAXONOMY_TAG, [ EventPostType::POST_TYPE ], apply_filters( 'wpcalibrate_tag_taxonomy_args', $args ) );
	}
}
