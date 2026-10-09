<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Licensing;

use WPCalibrate\EventsManager\Domain\Capabilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles licensing abstraction and provider contract.
 * Note: Remote licensing provider is unconfigured in Version 1.
 * Never reports valid without a verified provider response.
 */
class LicenseManager {

	public const OPTION_KEY = 'wpcalibrate_events_license';

	/**
	 * Get stored license configuration.
	 *
	 * @return array{key: string, status: string, expires: string}
	 */
	public static function get_license_data(): array {
		$defaults = [
			'key'     => '',
			'status'  => 'unconfigured',
			'expires' => '',
		];

		$saved = get_option( self::OPTION_KEY, [] );
		return is_array( $saved ) ? wp_parse_args( $saved, $defaults ) : $defaults;
	}

	/**
	 * Save license key securely.
	 *
	 * @param string $raw_key
	 */
	public static function save_license_key( string $raw_key ): void {
		if ( ! current_user_can( Capabilities::MANAGE_EVENTS ) ) {
			return;
		}

		$clean_key = trim( sanitize_text_field( $raw_key ) );
		$current   = self::get_license_data();

		$current['key']    = $clean_key;
		$current['status'] = empty( $clean_key ) ? 'unconfigured' : 'inactive_provider_unconfigured';

		update_option( self::OPTION_KEY, $current );
	}

	/**
	 * Return masked license key for secure UI presentation.
	 *
	 * @param string $key
	 * @return string
	 */
	public static function mask_key( string $key ): string {
		if ( empty( $key ) ) {
			return '';
		}

		$len = strlen( $key );
		if ( $len <= 8 ) {
			return str_repeat( '•', $len );
		}

		return substr( $key, 0, 4 ) . str_repeat( '•', $len - 8 ) . substr( $key, -4 );
	}

	/**
	 * Get human readable status description.
	 *
	 * @return string
	 */
	public static function get_status_label(): string {
		$data = self::get_license_data();
		if ( empty( $data['key'] ) ) {
			return __( 'No license key entered', 'wpcalibrate-events-manager' );
		}

		return __( 'Remote licensing provider not configured. Plugin operates in standard mode.', 'wpcalibrate-events-manager' );
	}
}
