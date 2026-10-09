<?php
/**
 * Plugin Name:       WPCalibrate Events Manager
 * Plugin URI:        https://wpcalibrate.com
 * Description:       A responsive, robust, and extensible event calendar and event management system for WordPress.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.2
 * Author:            WPCalibrate
 * Author URI:        https://wpcalibrate.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpcalibrate-events-manager
 * Domain Path:       /languages
 * Update URI:        https://github.com/zeeshanraza-official/wpcalibrate-events-manager/
 * GitHub Plugin URI: zeeshanraza-official/wpcalibrate-events-manager
 * Primary Branch:    main
 * Release Asset:     true
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants
define( 'WPCAL_VERSION', '1.0.0' );
define( 'WPCAL_DB_VERSION', '1.0.0' );
define( 'WPCAL_MIN_PHP_VERSION', '8.2.0' );
define( 'WPCAL_MIN_WP_VERSION', '6.4' );
define( 'WPCAL_PLUGIN_FILE', __FILE__ );
define( 'WPCAL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPCAL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPCAL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader for WPCalibrate\EventsManager namespace.
 */
spl_autoload_register( static function ( string $class ): void {
	$prefix = 'WPCalibrate\\EventsManager\\';
	if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, strlen( $prefix ) );
	$file           = WPCAL_PLUGIN_DIR . 'includes/' . str_replace( '\\', '/', $relative_class ) . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
} );

/**
 * Environment requirement checks.
 *
 * @return bool True if requirements are met, false otherwise.
 */
function wpcal_check_requirements(): bool {
	if ( version_compare( PHP_VERSION, WPCAL_MIN_PHP_VERSION, '<' ) ) {
		add_action( 'admin_notices', static function (): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: Current PHP version, 2: Required PHP version */
						__( 'WPCalibrate Events Manager requires PHP %2$s or higher. Your server is running PHP %1$s. Please upgrade your PHP version.', 'wpcalibrate-events-manager' ),
						PHP_VERSION,
						WPCAL_MIN_PHP_VERSION
					)
				)
			);
		} );
		return false;
	}

	global $wp_version;
	if ( isset( $wp_version ) && version_compare( $wp_version, WPCAL_MIN_WP_VERSION, '<' ) ) {
		add_action( 'admin_notices', static function () use ( $wp_version ): void {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: Current WordPress version, 2: Required WordPress version */
						__( 'WPCalibrate Events Manager requires WordPress %2$s or higher. Your site is running WordPress %1$s. Please update WordPress.', 'wpcalibrate-events-manager' ),
						$wp_version,
						WPCAL_MIN_WP_VERSION
					)
				)
			);
		} );
		return false;
	}

	return true;
}

// Register activation and deactivation hooks
register_activation_hook( WPCAL_PLUGIN_FILE, static function (): void {
	if ( wpcal_check_requirements() ) {
		\WPCalibrate\EventsManager\Activation::activate();
	}
} );

register_deactivation_hook( WPCAL_PLUGIN_FILE, static function (): void {
	\WPCalibrate\EventsManager\Deactivation::deactivate();
} );

/**
 * Bootstrap plugin on plugins_loaded.
 */
add_action( 'plugins_loaded', static function (): void {
	if ( ! wpcal_check_requirements() ) {
		return;
	}

	// Load text domain
	load_plugin_textdomain(
		'wpcalibrate-events-manager',
		false,
		dirname( WPCAL_PLUGIN_BASENAME ) . '/languages'
	);

	// Initialize the main plugin instance
	\WPCalibrate\EventsManager\Plugin::instance()->boot();
} );
