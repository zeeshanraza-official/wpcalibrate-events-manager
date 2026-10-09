<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Admin;

use WPCalibrate\EventsManager\Domain\Capabilities;
use WPCalibrate\EventsManager\Events\EventPostType;
use WPCalibrate\EventsManager\Events\EventTaxonomies;
use WPCalibrate\EventsManager\Venues\VenuePostType;
use WPCalibrate\EventsManager\Organizers\OrganizerPostType;
use WPCalibrate\EventsManager\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles WPCalibrate top-level shared menu registration and Events Manager submenus.
 */
class Menu {

	public const PARENT_SLUG = 'wpcalibrate';
	public const PAGE_SLUG   = 'wpcalibrate-events-manager';

	/**
	 * Register hooks.
	 */
	public function register_hooks(): void {
		// Priority 9 so top-level is ready before other submenus if this loads first
		add_action( 'admin_menu', [ $this, 'register_menus' ], 9 );
		add_action( 'admin_head', [ $this, 'admin_menu_styles' ] );
	}

	/**
	 * Register or reuse parent menu and attach Events Manager items.
	 */
	public function register_menus(): void {
		global $admin_page_hooks;

		// 1. Check if WPCalibrate parent menu exists
		if ( ! isset( $admin_page_hooks[ self::PARENT_SLUG ] ) ) {
			// Select white branding icon for dark WP admin menu background
			$icon_url = WPCAL_PLUGIN_URL . 'branding/icon-white.png';

			add_menu_page(
				'WPCalibrate',
				'WPCalibrate',
				Capabilities::MANAGE_EVENTS,
				self::PARENT_SLUG,
				[ $this, 'render_parent_dashboard' ],
				$icon_url,
				58.5
			);
		}

		// 2. Add Events Manager submenu items
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'All Events', 'wpcalibrate-events-manager' ),
			__( 'Events', 'wpcalibrate-events-manager' ),
			Capabilities::EDIT_EVENTS,
			'edit.php?post_type=' . EventPostType::POST_TYPE
		);

		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Add New Event', 'wpcalibrate-events-manager' ),
			__( 'Add Event', 'wpcalibrate-events-manager' ),
			Capabilities::EDIT_EVENTS,
			'post-new.php?post_type=' . EventPostType::POST_TYPE
		);

		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Venues', 'wpcalibrate-events-manager' ),
			__( 'Venues', 'wpcalibrate-events-manager' ),
			Capabilities::EDIT_EVENTS,
			'edit.php?post_type=' . VenuePostType::POST_TYPE
		);

		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Organizers', 'wpcalibrate-events-manager' ),
			__( 'Organizers', 'wpcalibrate-events-manager' ),
			Capabilities::EDIT_EVENTS,
			'edit.php?post_type=' . OrganizerPostType::POST_TYPE
		);

		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Event Categories', 'wpcalibrate-events-manager' ),
			__( 'Categories', 'wpcalibrate-events-manager' ),
			Capabilities::MANAGE_EVENTS,
			'edit-tags.php?taxonomy=' . EventTaxonomies::TAXONOMY_CATEGORY . '&post_type=' . EventPostType::POST_TYPE
		);

		// Events Manager Settings Page
		add_submenu_page(
			self::PARENT_SLUG,
			__( 'Events Manager Settings', 'wpcalibrate-events-manager' ),
			__( 'Events Settings', 'wpcalibrate-events-manager' ),
			Capabilities::MANAGE_EVENTS,
			self::PAGE_SLUG,
			[ SettingsManager::class, 'render_page' ]
		);
	}

	/**
	 * Top-level menu fallback handler if clicked directly.
	 */
	public function render_parent_dashboard(): void {
		// Redirect or render Events Manager settings
		SettingsManager::render_page();
	}

	/**
	 * Inline style to size branding icon correctly and match WordPress admin icon contrast.
	 */
	public function admin_menu_styles(): void {
		?>
		<style>
			#adminmenu .toplevel_page_wpcalibrate .wp-menu-image img {
				max-width: 20px;
				max-height: 20px;
				padding-top: 7px;
				opacity: 1 !important;
				filter: none !important;
				-webkit-filter: none !important;
				transition: opacity 0.15s ease-in-out, filter 0.15s ease-in-out;
			}
			#adminmenu .toplevel_page_wpcalibrate:hover .wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate.wp-has-current-submenu .wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate.current .wp-menu-image img {
				opacity: 1 !important;
				filter: brightness(1) !important;
			}
		</style>
		<?php
	}
}
