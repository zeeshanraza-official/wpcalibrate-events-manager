<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Admin;

use WPCalibrate\EventsManager\Domain\Capabilities;
use WPCalibrate\EventsManager\Venues\VenuePostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles admin meta boxes for Venue post type.
 */
class VenueMetaBox {

	public const NONCE_ACTION = 'wpcal_save_venue_meta_action';
	public const NONCE_NAME   = 'wpcal_venue_nonce';

	/**
	 * Register meta box hooks.
	 */
	public function register_hooks(): void {
		add_action( 'add_meta_boxes_' . VenuePostType::POST_TYPE, [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post_' . VenuePostType::POST_TYPE, [ $this, 'save_meta_box_data' ], 10, 2 );
	}

	/**
	 * Add meta box to Venue edit screen.
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'wpcal_venue_details',
			__( 'Venue Location & Contact Information', 'wpcalibrate-events-manager' ),
			[ $this, 'render_meta_box' ],
			VenuePostType::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render Venue meta box.
	 *
	 * @param \WP_Post $post
	 */
	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$post_id     = $post->ID;
		$address     = get_post_meta( $post_id, '_wpcal_address', true ) ?: '';
		$city        = get_post_meta( $post_id, '_wpcal_city', true ) ?: '';
		$state       = get_post_meta( $post_id, '_wpcal_state', true ) ?: '';
		$postal_code = get_post_meta( $post_id, '_wpcal_postal_code', true ) ?: '';
		$country     = get_post_meta( $post_id, '_wpcal_country', true ) ?: '';
		$lat         = get_post_meta( $post_id, '_wpcal_latitude', true ) ?: '';
		$lng         = get_post_meta( $post_id, '_wpcal_longitude', true ) ?: '';
		$website     = get_post_meta( $post_id, '_wpcal_website', true ) ?: '';
		$phone       = get_post_meta( $post_id, '_wpcal_phone', true ) ?: '';
		?>
		<table class="form-table">
			<tr>
				<th><label for="wpcal_address"><?php esc_html_e( 'Street Address', 'wpcalibrate-events-manager' ); ?></label></th>
				<td><input type="text" name="wpcal_address" id="wpcal_address" value="<?php echo esc_attr( $address ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpcal_city"><?php esc_html_e( 'City', 'wpcalibrate-events-manager' ); ?></label></th>
				<td><input type="text" name="wpcal_city" id="wpcal_city" value="<?php echo esc_attr( $city ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpcal_state"><?php esc_html_e( 'State / Region', 'wpcalibrate-events-manager' ); ?></label></th>
				<td><input type="text" name="wpcal_state" id="wpcal_state" value="<?php echo esc_attr( $state ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpcal_postal_code"><?php esc_html_e( 'Postal / Zip Code', 'wpcalibrate-events-manager' ); ?></label></th>
				<td><input type="text" name="wpcal_postal_code" id="wpcal_postal_code" value="<?php echo esc_attr( $postal_code ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpcal_country"><?php esc_html_e( 'Country', 'wpcalibrate-events-manager' ); ?></label></th>
				<td><input type="text" name="wpcal_country" id="wpcal_country" value="<?php echo esc_attr( $country ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpcal_latitude"><?php esc_html_e( 'Coordinates (Lat / Long)', 'wpcalibrate-events-manager' ); ?></label></th>
				<td>
					<input type="text" name="wpcal_latitude" id="wpcal_latitude" value="<?php echo esc_attr( $lat ); ?>" placeholder="Latitude (e.g. 51.5074)" style="width: 170px;">
					<input type="text" name="wpcal_longitude" id="wpcal_longitude" value="<?php echo esc_attr( $lng ); ?>" placeholder="Longitude (e.g. -0.1278)" style="width: 170px;">
					<p class="description"><?php esc_html_e( 'Optional. Used for OpenStreetMap display when enabled.', 'wpcalibrate-events-manager' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="wpcal_website"><?php esc_html_e( 'Venue Website', 'wpcalibrate-events-manager' ); ?></label></th>
				<td><input type="url" name="wpcal_website" id="wpcal_website" value="<?php echo esc_url( $website ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpcal_phone"><?php esc_html_e( 'Phone Number', 'wpcalibrate-events-manager' ); ?></label></th>
				<td><input type="tel" name="wpcal_phone" id="wpcal_phone" value="<?php echo esc_attr( $phone ); ?>" class="regular-text"></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save Venue meta box data.
	 *
	 * @param int      $post_id
	 * @param \WP_Post $post
	 */
	public function save_meta_box_data( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( Capabilities::EDIT_EVENTS, $post_id ) ) {
			return;
		}

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$fields = [
			'wpcal_address'     => '_wpcal_address',
			'wpcal_city'        => '_wpcal_city',
			'wpcal_state'       => '_wpcal_state',
			'wpcal_postal_code' => '_wpcal_postal_code',
			'wpcal_country'     => '_wpcal_country',
			'wpcal_latitude'    => '_wpcal_latitude',
			'wpcal_longitude'   => '_wpcal_longitude',
			'wpcal_phone'       => '_wpcal_phone',
		];

		foreach ( $fields as $input => $meta_key ) {
			if ( isset( $_POST[ $input ] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $input ] ) ) );
			}
		}

		if ( isset( $_POST['wpcal_website'] ) ) {
			update_post_meta( $post_id, '_wpcal_website', esc_url_raw( wp_unslash( $_POST['wpcal_website'] ) ) );
		}
	}
}
