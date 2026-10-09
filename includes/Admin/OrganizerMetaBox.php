<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Admin;

use WPCalibrate\EventsManager\Domain\Capabilities;
use WPCalibrate\EventsManager\Organizers\OrganizerPostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles admin meta boxes for Organizer post type.
 */
class OrganizerMetaBox {

	public const NONCE_ACTION = 'wpcal_save_organizer_meta_action';
	public const NONCE_NAME   = 'wpcal_organizer_nonce';

	/**
	 * Register meta box hooks.
	 */
	public function register_hooks(): void {
		add_action( 'add_meta_boxes_' . OrganizerPostType::POST_TYPE, [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post_' . OrganizerPostType::POST_TYPE, [ $this, 'save_meta_box_data' ], 10, 2 );
	}

	/**
	 * Add meta box to Organizer edit screen.
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'wpcal_organizer_details',
			__( 'Organizer Contact & Details', 'wpcalibrate-events-manager' ),
			[ $this, 'render_meta_box' ],
			OrganizerPostType::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render Organizer meta box.
	 *
	 * @param \WP_Post $post
	 */
	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$post_id = $post->ID;
		$email   = get_post_meta( $post_id, '_wpcal_organizer_email', true ) ?: '';
		$phone   = get_post_meta( $post_id, '_wpcal_organizer_phone', true ) ?: '';
		$website = get_post_meta( $post_id, '_wpcal_organizer_website', true ) ?: '';
		?>
		<table class="form-table">
			<tr>
				<th><label for="wpcal_organizer_email"><?php esc_html_e( 'Contact Email', 'wpcalibrate-events-manager' ); ?></label></th>
				<td><input type="email" name="wpcal_organizer_email" id="wpcal_organizer_email" value="<?php echo esc_attr( $email ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpcal_organizer_phone"><?php esc_html_e( 'Phone Number', 'wpcalibrate-events-manager' ); ?></label></th>
				<td><input type="tel" name="wpcal_organizer_phone" id="wpcal_organizer_phone" value="<?php echo esc_attr( $phone ); ?>" class="regular-text"></td>
			</tr>
			<tr>
				<th><label for="wpcal_organizer_website"><?php esc_html_e( 'Website / Profile URL', 'wpcalibrate-events-manager' ); ?></label></th>
				<td><input type="url" name="wpcal_organizer_website" id="wpcal_organizer_website" value="<?php echo esc_url( $website ); ?>" class="regular-text"></td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save Organizer meta box data.
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

		if ( isset( $_POST['wpcal_organizer_email'] ) ) {
			update_post_meta( $post_id, '_wpcal_organizer_email', sanitize_email( wp_unslash( $_POST['wpcal_organizer_email'] ) ) );
		}

		if ( isset( $_POST['wpcal_organizer_phone'] ) ) {
			update_post_meta( $post_id, '_wpcal_organizer_phone', sanitize_text_field( wp_unslash( $_POST['wpcal_organizer_phone'] ) ) );
		}

		if ( isset( $_POST['wpcal_organizer_website'] ) ) {
			update_post_meta( $post_id, '_wpcal_organizer_website', esc_url_raw( wp_unslash( $_POST['wpcal_organizer_website'] ) ) );
		}
	}
}
