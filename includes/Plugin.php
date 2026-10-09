<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager;

use WPCalibrate\EventsManager\Admin\EventListTable;
use WPCalibrate\EventsManager\Admin\EventMetaBox;
use WPCalibrate\EventsManager\Admin\Menu;
use WPCalibrate\EventsManager\Admin\OrganizerMetaBox;
use WPCalibrate\EventsManager\Admin\VenueMetaBox;
use WPCalibrate\EventsManager\Api\CalendarRestController;
use WPCalibrate\EventsManager\Calendar\BlockManager;
use WPCalibrate\EventsManager\Calendar\Shortcodes;
use WPCalibrate\EventsManager\Events\EventPostType;
use WPCalibrate\EventsManager\Events\EventTaxonomies;
use WPCalibrate\EventsManager\Events\SingleEventRenderer;
use WPCalibrate\EventsManager\Infrastructure\BackgroundProcessor;
use WPCalibrate\EventsManager\Infrastructure\GitHubUpdater;
use WPCalibrate\EventsManager\Organizers\OrganizerPostType;
use WPCalibrate\EventsManager\Recurrence\RecurrenceManager;
use WPCalibrate\EventsManager\Settings\SettingsManager;
use WPCalibrate\EventsManager\Venues\VenuePostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin singleton coordinator.
 */
final class Plugin {

	private static ?Plugin $instance = null;

	public static function instance(): Plugin {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {}

	/**
	 * Boot all plugin services and hooks.
	 */
	public function boot(): void {
		// 1. Post Types & Taxonomies
		add_action( 'init', [ EventPostType::class, 'register' ] );
		add_action( 'init', [ VenuePostType::class, 'register' ] );
		add_action( 'init', [ OrganizerPostType::class, 'register' ] );
		add_action( 'init', [ EventTaxonomies::class, 'register' ] );

		// 2. Admin Interface & Menu
		if ( is_admin() ) {
			( new Menu() )->register_hooks();
			( new EventMetaBox() )->register_hooks();
			( new VenueMetaBox() )->register_hooks();
			( new OrganizerMetaBox() )->register_hooks();
			( new EventListTable() )->register_hooks();
			( new GitHubUpdater( WPCAL_PLUGIN_FILE, WPCAL_VERSION ) )->register();
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		}

		// 3. Recurrence Engine
		( new RecurrenceManager() )->register_hooks();

		// 4. Single Event Presentation
		( new SingleEventRenderer() )->register_hooks();

		// 5. Shortcodes
		( new Shortcodes() )->register_hooks();

		// 6. Gutenberg Blocks
		( new BlockManager() )->register_hooks();

		// 7. REST API
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );

		// 8. Background Processing
		( new BackgroundProcessor() )->register_hooks();

		// 9. Frontend Asset Registration
		add_action( 'wp_enqueue_scripts', [ $this, 'register_frontend_assets' ] );
	}

	/**
	 * Register frontend CSS and JavaScript assets.
	 */
	public function register_frontend_assets(): void {
		wp_register_style(
			'wpcal-calendar-css',
			WPCAL_PLUGIN_URL . 'assets/css/calendar.css',
			[],
			WPCAL_VERSION
		);

		// Dynamic Accent Color
		$accent_color = (string) SettingsManager::get( 'accent_color', '#2271b1' );
		if ( $accent_color && $accent_color !== '#2271b1' ) {
			$custom_css = sprintf(
				':root { --wpcal-primary: %1$s; --wpcal-primary-hover: %1$s; }',
				sanitize_hex_color( $accent_color )
			);
			wp_add_inline_style( 'wpcal-calendar-css', $custom_css );
		}

		wp_register_script(
			'wpcal-calendar-js',
			WPCAL_PLUGIN_URL . 'assets/js/calendar.js',
			[],
			WPCAL_VERSION,
			true
		);

		wp_localize_script(
			'wpcal-calendar-js',
			'wpcalData',
			[
				'restUrl' => esc_url_raw( rest_url( 'wpcalibrate-events/v1/' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			]
		);

		// If on single event page, enqueue automatically
		if ( is_singular( EventPostType::POST_TYPE ) ) {
			wp_enqueue_style( 'wpcal-calendar-css' );
		}
	}

	/**
	 * Enqueue admin CSS and JavaScript.
	 *
	 * @param string $hook_suffix
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		wp_enqueue_style(
			'wpcal-admin-css',
			WPCAL_PLUGIN_URL . 'assets/css/admin.css',
			[],
			WPCAL_VERSION
		);

		wp_enqueue_script(
			'wpcal-admin-js',
			WPCAL_PLUGIN_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			WPCAL_VERSION,
			true
		);
	}

	/**
	 * Register REST routes.
	 */
	public function register_rest_routes(): void {
		$controller = new CalendarRestController();
		$controller->register_routes();
	}
}
