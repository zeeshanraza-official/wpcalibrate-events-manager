<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles custom capability registration and role assignments.
 */
class Capabilities {

	public const MANAGE_EVENTS  = 'manage_wpcalibrate_events';
	public const EDIT_EVENTS    = 'edit_wpcalibrate_events';
	public const PUBLISH_EVENTS = 'publish_wpcalibrate_events';
	public const DELETE_EVENTS  = 'delete_wpcalibrate_events';
	public const READ_EVENTS    = 'read_wpcalibrate_events';

	/**
	 * Get list of core capabilities.
	 *
	 * @return array<string>
	 */
	public static function all(): array {
		return [
			self::MANAGE_EVENTS,
			self::EDIT_EVENTS,
			self::PUBLISH_EVENTS,
			self::DELETE_EVENTS,
			self::READ_EVENTS,
		];
	}

	/**
	 * Map capabilities to roles on activation.
	 */
	public static function register(): void {
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			foreach ( self::all() as $cap ) {
				$admin->add_cap( $cap );
			}
		}

		$editor = get_role( 'editor' );
		if ( $editor ) {
			$editor->add_cap( self::EDIT_EVENTS );
			$editor->add_cap( self::PUBLISH_EVENTS );
			$editor->add_cap( self::DELETE_EVENTS );
			$editor->add_cap( self::READ_EVENTS );
		}
	}

	/**
	 * Remove custom capabilities (used during uninstall).
	 */
	public static function unregister(): void {
		$roles = [ 'administrator', 'editor' ];
		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				foreach ( self::all() as $cap ) {
					$role->remove_cap( $cap );
				}
			}
		}
	}
}
