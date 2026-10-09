<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Settings;

use WPCalibrate\EventsManager\Domain\Capabilities;
use WPCalibrate\EventsManager\Domain\DateTimeHandler;
use WPCalibrate\EventsManager\Licensing\LicenseManager;
use WPCalibrate\EventsManager\Support\SupportSection;
use WPCalibrate\EventsManager\Admin\OverviewSection;
use WPCalibrate\EventsManager\Infrastructure\Diagnostics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages plugin settings pages, tab navigation, options persistence, and validation.
 */
class SettingsManager {

	public const OPTION_KEY   = 'wpcalibrate_events_settings';
	public const NONCE_ACTION = 'wpcal_settings_nonce_action';
	public const NONCE_NAME   = 'wpcal_settings_nonce';

	/**
	 * Default settings array.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_defaults(): array {
		return [
			// General
			'default_timezone'       => '', // Empty means site default
			'default_event_status'   => 'publish',
			// Calendar
			'default_view'           => 'month',
			'enabled_views'          => [ 'month', 'week', 'day', 'list' ],
			'week_start'             => (int) get_option( 'start_of_week', 1 ),
			'events_per_page'        => 10,
			// Events
			'show_single_venue'      => 1,
			'show_single_organizer'  => 1,
			'show_single_map'        => 1,
			'show_single_ical'       => 1,
			'show_single_gcal'       => 1,
			// Display
			'accent_color'           => '#2271b1',
			// Maps
			'enable_osm'             => 1,
			'default_map_zoom'       => 14,
			// Advanced
			'enable_debug_log'       => 0,
			'delete_data_on_uninstall' => 0,
		];
	}

	/**
	 * Initialize default settings if not exists.
	 */
	public static function initialize_defaults(): void {
		if ( get_option( self::OPTION_KEY ) === false ) {
			add_option( self::OPTION_KEY, self::get_defaults() );
		}
	}

	/**
	 * Retrieve all plugin settings with defaults merged.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_settings(): array {
		$saved = get_option( self::OPTION_KEY, [] );
		return is_array( $saved ) ? wp_parse_args( $saved, self::get_defaults() ) : self::get_defaults();
	}

	/**
	 * Get a specific setting value.
	 *
	 * @param string $key
	 * @param mixed  $default
	 * @return mixed
	 */
	public static function get( string $key, mixed $default = null ): mixed {
		$settings = self::get_settings();
		return $settings[ $key ] ?? $default;
	}

	/**
	 * Save updated settings.
	 *
	 * @param array<string, mixed> $updated
	 */
	public static function update_settings( array $updated ): void {
		$current = self::get_settings();
		$merged  = array_merge( $current, $updated );
		update_option( self::OPTION_KEY, $merged );
	}

	/**
	 * Render settings page.
	 */
	public static function render_page(): void {
		if ( ! current_user_can( Capabilities::MANAGE_EVENTS ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpcalibrate-events-manager' ) );
		}

		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'overview';
		$valid_tabs  = [ 'overview', 'general', 'calendar', 'events', 'display', 'maps', 'import_export', 'advanced', 'license', 'support' ];
		if ( ! in_array( $current_tab, $valid_tabs, true ) ) {
			$current_tab = 'overview';
		}

		// Handle Save Form Submission
		$message = null;
		if ( isset( $_POST[ self::NONCE_NAME ] ) && wp_verify_nonce( sanitize_key( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION ) ) {
			$message = self::handle_save( $current_tab );
		}

		$settings = self::get_settings();
		?>
		<div class="wrap wpcal-settings-wrap">
			<h1 style="display: flex; align-items: center; gap: 10px;">
				<span><?php esc_html_e( 'WPCalibrate Events Manager Settings', 'wpcalibrate-events-manager' ); ?></span>
				<span style="font-size: 13px; font-weight: normal; background: #e0e0e0; color: #333; padding: 2px 8px; border-radius: 4px;">v<?php echo esc_html( WPCAL_VERSION ); ?></span>
			</h1>

			<?php if ( $message ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
			<?php endif; ?>

			<nav class="nav-tab-wrapper wp-clearfix" style="margin-bottom: 20px;">
				<a href="?page=wpcalibrate-events-manager&tab=overview" class="nav-tab <?php echo $current_tab === 'overview' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Overview', 'wpcalibrate-events-manager' ); ?></a>
				<a href="?page=wpcalibrate-events-manager&tab=general" class="nav-tab <?php echo $current_tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'General', 'wpcalibrate-events-manager' ); ?></a>
				<a href="?page=wpcalibrate-events-manager&tab=calendar" class="nav-tab <?php echo $current_tab === 'calendar' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Calendar', 'wpcalibrate-events-manager' ); ?></a>
				<a href="?page=wpcalibrate-events-manager&tab=events" class="nav-tab <?php echo $current_tab === 'events' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Events Page', 'wpcalibrate-events-manager' ); ?></a>
				<a href="?page=wpcalibrate-events-manager&tab=display" class="nav-tab <?php echo $current_tab === 'display' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Display', 'wpcalibrate-events-manager' ); ?></a>
				<a href="?page=wpcalibrate-events-manager&tab=maps" class="nav-tab <?php echo $current_tab === 'maps' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Maps', 'wpcalibrate-events-manager' ); ?></a>
				<a href="?page=wpcalibrate-events-manager&tab=import_export" class="nav-tab <?php echo $current_tab === 'import_export' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Import / Export', 'wpcalibrate-events-manager' ); ?></a>
				<a href="?page=wpcalibrate-events-manager&tab=advanced" class="nav-tab <?php echo $current_tab === 'advanced' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Advanced & Diagnostics', 'wpcalibrate-events-manager' ); ?></a>
				<a href="?page=wpcalibrate-events-manager&tab=license" class="nav-tab <?php echo $current_tab === 'license' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'License', 'wpcalibrate-events-manager' ); ?></a>
				<a href="?page=wpcalibrate-events-manager&tab=support" class="nav-tab <?php echo $current_tab === 'support' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Support', 'wpcalibrate-events-manager' ); ?></a>
			</nav>

			<?php if ( $current_tab === 'overview' ) : ?>
				<?php OverviewSection::render(); ?>
			<?php elseif ( $current_tab === 'support' ) : ?>
				<?php SupportSection::render(); ?>
			<?php else : ?>
				<form method="post" action="" enctype="multipart/form-data">
					<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

					<?php if ( $current_tab === 'general' ) : ?>
						<table class="form-table">
							<tr>
								<th><label for="default_timezone"><?php esc_html_e( 'Default Event Timezone', 'wpcalibrate-events-manager' ); ?></label></th>
								<td>
									<select name="default_timezone" id="default_timezone">
										<option value=""><?php printf( esc_html__( 'Site Default (%s)', 'wpcalibrate-events-manager' ), esc_html( DateTimeHandler::get_site_timezone()->getName() ) ); ?></option>
										<?php foreach ( timezone_identifiers_list() as $tz ) : ?>
											<option value="<?php echo esc_attr( $tz ); ?>" <?php selected( $settings['default_timezone'], $tz ); ?>><?php echo esc_html( $tz ); ?></option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php esc_html_e( 'Fallback timezone when an individual event does not specify a custom timezone.', 'wpcalibrate-events-manager' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label for="default_event_status"><?php esc_html_e( 'Default Event Status', 'wpcalibrate-events-manager' ); ?></label></th>
								<td>
									<select name="default_event_status" id="default_event_status">
										<option value="publish" <?php selected( $settings['default_event_status'], 'publish' ); ?>><?php esc_html_e( 'Active / Published', 'wpcalibrate-events-manager' ); ?></option>
										<option value="draft" <?php selected( $settings['default_event_status'], 'draft' ); ?>><?php esc_html_e( 'Draft', 'wpcalibrate-events-manager' ); ?></option>
									</select>
								</td>
							</tr>
						</table>

					<?php elseif ( $current_tab === 'calendar' ) : ?>
						<table class="form-table">
							<tr>
								<th><label for="default_view"><?php esc_html_e( 'Default Calendar View', 'wpcalibrate-events-manager' ); ?></label></th>
								<td>
									<select name="default_view" id="default_view">
										<option value="month" <?php selected( $settings['default_view'], 'month' ); ?>><?php esc_html_e( 'Month View', 'wpcalibrate-events-manager' ); ?></option>
										<option value="week" <?php selected( $settings['default_view'], 'week' ); ?>><?php esc_html_e( 'Week View', 'wpcalibrate-events-manager' ); ?></option>
										<option value="day" <?php selected( $settings['default_view'], 'day' ); ?>><?php esc_html_e( 'Day View', 'wpcalibrate-events-manager' ); ?></option>
										<option value="list" <?php selected( $settings['default_view'], 'list' ); ?>><?php esc_html_e( 'List / Agenda View', 'wpcalibrate-events-manager' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Enabled Views', 'wpcalibrate-events-manager' ); ?></th>
								<td>
									<?php $ev = (array) $settings['enabled_views']; ?>
									<label style="margin-right:15px;"><input type="checkbox" name="enabled_views[]" value="month" <?php checked( in_array( 'month', $ev, true ) ); ?>> <?php esc_html_e( 'Month', 'wpcalibrate-events-manager' ); ?></label>
									<label style="margin-right:15px;"><input type="checkbox" name="enabled_views[]" value="week" <?php checked( in_array( 'week', $ev, true ) ); ?>> <?php esc_html_e( 'Week', 'wpcalibrate-events-manager' ); ?></label>
									<label style="margin-right:15px;"><input type="checkbox" name="enabled_views[]" value="day" <?php checked( in_array( 'day', $ev, true ) ); ?>> <?php esc_html_e( 'Day', 'wpcalibrate-events-manager' ); ?></label>
									<label><input type="checkbox" name="enabled_views[]" value="list" <?php checked( in_array( 'list', $ev, true ) ); ?>> <?php esc_html_e( 'List', 'wpcalibrate-events-manager' ); ?></label>
								</td>
							</tr>
							<tr>
								<th><label for="week_start"><?php esc_html_e( 'Week Starts On', 'wpcalibrate-events-manager' ); ?></label></th>
								<td>
									<select name="week_start" id="week_start">
										<option value="0" <?php selected( (int) $settings['week_start'], 0 ); ?>><?php esc_html_e( 'Sunday', 'wpcalibrate-events-manager' ); ?></option>
										<option value="1" <?php selected( (int) $settings['week_start'], 1 ); ?>><?php esc_html_e( 'Monday', 'wpcalibrate-events-manager' ); ?></option>
										<option value="6" <?php selected( (int) $settings['week_start'], 6 ); ?>><?php esc_html_e( 'Saturday', 'wpcalibrate-events-manager' ); ?></option>
									</select>
								</td>
							</tr>
							<tr>
								<th><label for="events_per_page"><?php esc_html_e( 'Events Per Page (List View)', 'wpcalibrate-events-manager' ); ?></label></th>
								<td>
									<input type="number" min="1" max="100" name="events_per_page" id="events_per_page" value="<?php echo esc_attr( (string) $settings['events_per_page'] ); ?>">
								</td>
							</tr>
						</table>

					<?php elseif ( $current_tab === 'events' ) : ?>
						<table class="form-table">
							<tr>
								<th><?php esc_html_e( 'Single Event Components', 'wpcalibrate-events-manager' ); ?></th>
								<td>
									<fieldset>
										<label><input type="checkbox" name="show_single_venue" value="1" <?php checked( (bool) $settings['show_single_venue'] ); ?>> <?php esc_html_e( 'Display Venue and Address', 'wpcalibrate-events-manager' ); ?></label><br><br>
										<label><input type="checkbox" name="show_single_organizer" value="1" <?php checked( (bool) $settings['show_single_organizer'] ); ?>> <?php esc_html_e( 'Display Organizer Details', 'wpcalibrate-events-manager' ); ?></label><br><br>
										<label><input type="checkbox" name="show_single_map" value="1" <?php checked( (bool) $settings['show_single_map'] ); ?>> <?php esc_html_e( 'Display OpenStreetMap on venue events', 'wpcalibrate-events-manager' ); ?></label><br><br>
										<label><input type="checkbox" name="show_single_ical" value="1" <?php checked( (bool) $settings['show_single_ical'] ); ?>> <?php esc_html_e( 'Display "Download .ics / iCalendar" button', 'wpcalibrate-events-manager' ); ?></label><br><br>
										<label><input type="checkbox" name="show_single_gcal" value="1" <?php checked( (bool) $settings['show_single_gcal'] ); ?>> <?php esc_html_e( 'Display "Add to Google Calendar" button', 'wpcalibrate-events-manager' ); ?></label>
									</fieldset>
								</td>
							</tr>
						</table>

					<?php elseif ( $current_tab === 'display' ) : ?>
						<table class="form-table">
							<tr>
								<th><label for="accent_color"><?php esc_html_e( 'Calendar Accent Color', 'wpcalibrate-events-manager' ); ?></label></th>
								<td>
									<input type="color" name="accent_color" id="accent_color" value="<?php echo esc_attr( (string) $settings['accent_color'] ); ?>">
									<p class="description"><?php esc_html_e( 'Primary accent color for calendar highlights, buttons, and badges.', 'wpcalibrate-events-manager' ); ?></p>
								</td>
							</tr>
						</table>

					<?php elseif ( $current_tab === 'maps' ) : ?>
						<table class="form-table">
							<tr>
								<th><?php esc_html_e( 'OpenStreetMap Provider', 'wpcalibrate-events-manager' ); ?></th>
								<td>
									<label><input type="checkbox" name="enable_osm" value="1" <?php checked( (bool) $settings['enable_osm'] ); ?>> <?php esc_html_e( 'Enable OpenStreetMap embed for venues with coordinates or address', 'wpcalibrate-events-manager' ); ?></label>
									<p class="description"><?php esc_html_e( 'OpenStreetMap operates without requiring paid third-party API keys.', 'wpcalibrate-events-manager' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label for="default_map_zoom"><?php esc_html_e( 'Default Zoom Level', 'wpcalibrate-events-manager' ); ?></label></th>
								<td>
									<input type="number" min="1" max="18" name="default_map_zoom" id="default_map_zoom" value="<?php echo esc_attr( (string) $settings['default_map_zoom'] ); ?>" style="width: 80px;">
								</td>
							</tr>
						</table>

					<?php elseif ( $current_tab === 'import_export' ) : ?>
						<table class="form-table">
							<tr>
								<th><?php esc_html_e( 'Public Calendar Feed URL', 'wpcalibrate-events-manager' ); ?></th>
								<td>
									<code><?php echo esc_url( home_url( '/?wpcal_feed=ical' ) ); ?></code>
									<p class="description"><?php esc_html_e( 'Subscribe to this standards-compliant .ics feed from Apple Calendar, Google Calendar, or Microsoft Outlook.', 'wpcalibrate-events-manager' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><label for="ics_file"><?php esc_html_e( 'Import .ics File', 'wpcalibrate-events-manager' ); ?></label></th>
								<td>
									<input type="file" name="ics_file" id="ics_file" accept=".ics">
									<p class="description"><?php esc_html_e( 'Upload a standards-compatible iCalendar (.ics) file to import events into WordPress.', 'wpcalibrate-events-manager' ); ?></p>
								</td>
							</tr>
						</table>

					<?php elseif ( $current_tab === 'advanced' ) : ?>
						<table class="form-table">
							<tr>
								<th><?php esc_html_e( 'Debug Logging', 'wpcalibrate-events-manager' ); ?></th>
								<td>
									<label><input type="checkbox" name="enable_debug_log" value="1" <?php checked( (bool) $settings['enable_debug_log'] ); ?>> <?php esc_html_e( 'Enable diagnostic logging to WordPress debug log', 'wpcalibrate-events-manager' ); ?></label>
									<p class="description"><?php esc_html_e( 'Disabled by default. Sensitive secrets are always redacted.', 'wpcalibrate-events-manager' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'Uninstall Data Retention', 'wpcalibrate-events-manager' ); ?></th>
								<td>
									<label style="color: #d63638; font-weight: 600;">
										<input type="checkbox" name="delete_data_on_uninstall" value="1" <?php checked( (bool) $settings['delete_data_on_uninstall'] ); ?>>
										<?php esc_html_e( 'Delete all plugin data, occurrences table, and settings on plugin uninstall', 'wpcalibrate-events-manager' ); ?>
									</label>
									<p class="description" style="color: #646970;">
										<?php esc_html_e( 'CAUTION: Default behavior preserves all user events and settings. Check this only if you want a complete wipe upon deleting the plugin from WordPress.', 'wpcalibrate-events-manager' ); ?>
									</p>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'System Diagnostics', 'wpcalibrate-events-manager' ); ?></th>
								<td>
									<textarea readonly rows="8" style="width: 100%; max-width: 600px; font-family: monospace; font-size: 12px;"><?php echo esc_textarea( Diagnostics::get_formatted_text() ); ?></textarea>
								</td>
							</tr>
						</table>

					<?php elseif ( $current_tab === 'license' ) : ?>
						<?php
						$license_data = LicenseManager::get_license_data();
						$masked_key   = LicenseManager::mask_key( $license_data['key'] );
						?>
						<table class="form-table">
							<tr>
								<th><label for="license_key"><?php esc_html_e( 'License Key', 'wpcalibrate-events-manager' ); ?></label></th>
								<td>
									<input type="text" name="license_key" id="license_key" value="<?php echo esc_attr( $masked_key ); ?>" class="regular-text" placeholder="Enter license key...">
									<p class="description"><?php esc_html_e( 'License keys remain securely masked in the interface.', 'wpcalibrate-events-manager' ); ?></p>
								</td>
							</tr>
							<tr>
								<th><?php esc_html_e( 'License Status', 'wpcalibrate-events-manager' ); ?></th>
								<td>
									<span class="wpcal-badge-info"><?php echo esc_html( LicenseManager::get_status_label() ); ?></span>
								</td>
							</tr>
						</table>
					<?php endif; ?>

					<?php submit_button(); ?>
				</form>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handle form save for a specific tab.
	 *
	 * @param string $tab
	 * @return string Success notice
	 */
	private static function handle_save( string $tab ): string {
		$current = self::get_settings();

		switch ( $tab ) {
			case 'general':
				$current['default_timezone']     = sanitize_text_field( wp_unslash( $_POST['default_timezone'] ?? '' ) );
				$current['default_event_status'] = sanitize_text_field( wp_unslash( $_POST['default_event_status'] ?? 'publish' ) );
				break;

			case 'calendar':
				$current['default_view']    = sanitize_text_field( wp_unslash( $_POST['default_view'] ?? 'month' ) );
				$current['enabled_views']   = ! empty( $_POST['enabled_views'] ) && is_array( $_POST['enabled_views'] )
					? array_map( 'sanitize_text_field', wp_unslash( $_POST['enabled_views'] ) )
					: [ 'month' ];
				$current['week_start']      = isset( $_POST['week_start'] ) ? absint( $_POST['week_start'] ) : 1;
				$current['events_per_page'] = isset( $_POST['events_per_page'] ) ? max( 1, absint( $_POST['events_per_page'] ) ) : 10;
				break;

			case 'events':
				$current['show_single_venue']     = ! empty( $_POST['show_single_venue'] ) ? 1 : 0;
				$current['show_single_organizer'] = ! empty( $_POST['show_single_organizer'] ) ? 1 : 0;
				$current['show_single_map']       = ! empty( $_POST['show_single_map'] ) ? 1 : 0;
				$current['show_single_ical']      = ! empty( $_POST['show_single_ical'] ) ? 1 : 0;
				$current['show_single_gcal']      = ! empty( $_POST['show_single_gcal'] ) ? 1 : 0;
				break;

			case 'display':
				$current['accent_color'] = sanitize_hex_color( wp_unslash( $_POST['accent_color'] ?? '#2271b1' ) ) ?: '#2271b1';
				break;

			case 'maps':
				$current['enable_osm']        = ! empty( $_POST['enable_osm'] ) ? 1 : 0;
				$current['default_map_zoom'] = isset( $_POST['default_map_zoom'] ) ? min( 18, max( 1, absint( $_POST['default_map_zoom'] ) ) ) : 14;
				break;

			case 'import_export':
				if ( ! empty( $_FILES['ics_file']['tmp_name'] ) ) {
					// Handle .ics file upload
					$file = $_FILES['ics_file'];
					$res  = \WPCalibrate\EventsManager\ImportExport\IcsImporter::import_uploaded_file( $file );
					if ( is_wp_error( $res ) ) {
						return sprintf( __( 'Import error: %s', 'wpcalibrate-events-manager' ), $res->get_error_message() );
					}
					return sprintf( __( 'iCalendar file imported successfully: %d events processed.', 'wpcalibrate-events-manager' ), (int) $res );
				}
				break;

			case 'advanced':
				$current['enable_debug_log']         = ! empty( $_POST['enable_debug_log'] ) ? 1 : 0;
				$current['delete_data_on_uninstall'] = ! empty( $_POST['delete_data_on_uninstall'] ) ? 1 : 0;
				break;

			case 'license':
				if ( isset( $_POST['license_key'] ) ) {
					$input_key = sanitize_text_field( wp_unslash( $_POST['license_key'] ) );
					// If input is not masked dots, save new key
					if ( ! str_contains( $input_key, '••' ) ) {
						LicenseManager::save_license_key( $input_key );
					}
				}
				break;
		}

		update_option( self::OPTION_KEY, $current );
		return __( 'Settings saved successfully.', 'wpcalibrate-events-manager' );
	}
}
