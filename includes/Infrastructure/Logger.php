<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Infrastructure;

use WPCalibrate\EventsManager\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Diagnostic logger with strict redaction of sensitive credentials.
 */
class Logger {

	/**
	 * Log a message if debug logging is enabled.
	 *
	 * @param string               $message
	 * @param array<string, mixed> $context
	 */
	public static function log( string $message, array $context = [] ): void {
		$settings = SettingsManager::get_settings();
		if ( empty( $settings['enable_debug_log'] ) ) {
			return;
		}

		$sanitized_context = self::redact_secrets( $context );
		$entry = sprintf(
			'[%s] [WPCalibrate Events Manager] %s %s',
			gmdate( 'Y-m-d H:i:s' ),
			$message,
			! empty( $sanitized_context ) ? wp_json_encode( $sanitized_context ) : ''
		);

		// Output via error_log only when WP_DEBUG is true or dedicated debug log option
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( $entry ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
	}

	/**
	 * Redact sensitive fields.
	 *
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	public static function redact_secrets( array $data ): array {
		$sensitive_keys = [ 'key', 'license', 'token', 'password', 'secret', 'auth', 'email' ];
		$cleaned = [];

		foreach ( $data as $k => $v ) {
			$k_lower = strtolower( (string) $k );
			$is_sensitive = false;
			foreach ( $sensitive_keys as $s_key ) {
				if ( str_contains( $k_lower, $s_key ) ) {
					$is_sensitive = true;
					break;
				}
			}

			if ( $is_sensitive ) {
				$cleaned[ $k ] = '***REDACTED***';
			} elseif ( is_array( $v ) ) {
				$cleaned[ $k ] = self::redact_secrets( $v );
			} else {
				$cleaned[ $k ] = $v;
			}
		}

		return $cleaned;
	}
}
