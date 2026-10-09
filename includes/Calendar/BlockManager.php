<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Calendar;

use WPCalibrate\EventsManager\Calendar\Shortcodes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages Gutenberg block registrations and server-side render callbacks.
 */
class BlockManager {

	/**
	 * Register Gutenberg blocks on init.
	 */
	public function register_hooks(): void {
		add_action( 'init', [ $this, 'register_blocks' ] );
	}

	/**
	 * Register blocks with WordPress.
	 */
	public function register_blocks(): void {
		// Register editor script handle
		wp_register_script(
			'wpcal-blocks-js',
			WPCAL_PLUGIN_URL . 'assets/js/blocks.js',
			[ 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor' ],
			WPCAL_VERSION,
			true
		);

		// Calendar Block
		register_block_type(
			WPCAL_PLUGIN_DIR . 'blocks/calendar/block.json',
			[
				'render_callback' => [ $this, 'render_calendar_block' ],
			]
		);

		// Upcoming Events Block
		register_block_type(
			WPCAL_PLUGIN_DIR . 'blocks/upcoming-events/block.json',
			[
				'render_callback' => [ $this, 'render_upcoming_block' ],
			]
		);
	}

	/**
	 * Server-side render callback for Calendar Block.
	 *
	 * @param array<string, mixed> $attributes
	 * @return string
	 */
	public function render_calendar_block( array $attributes ): string {
		$shortcodes = new Shortcodes();
		return $shortcodes->render_calendar_shortcode( $attributes );
	}

	/**
	 * Server-side render callback for Upcoming Events Block.
	 *
	 * @param array<string, mixed> $attributes
	 * @return string
	 */
	public function render_upcoming_block( array $attributes ): string {
		$shortcodes = new Shortcodes();
		return $shortcodes->render_upcoming_shortcode( $attributes );
	}
}
