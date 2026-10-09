<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders verified WPCalibrate support and contact section.
 */
class SupportSection {

	public const EMAIL       = 'support@wpcalibrate.com';
	public const WEBSITE     = 'https://wpcalibrate.com';
	public const MARKETPLACE = 'https://marketplace.wpcalibrate.com/';
	public const PHONE       = '+447474795976';

	/**
	 * Render HTML output for the support card/section.
	 */
	public static function render(): void {
		$icon_url = WPCAL_PLUGIN_URL . 'branding/icon-dark.png';
		?>
		<div class="wpcal-support-box" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 24px; max-width: 650px; margin-top: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
			<div style="display: flex; align-items: center; margin-bottom: 18px;">
				<img src="<?php echo esc_url( $icon_url ); ?>" alt="WPCalibrate" style="width: 48px; height: 48px; border-radius: 6px; margin-right: 16px;">
				<div>
					<h3 style="margin: 0; font-size: 18px; color: #1d2327; font-weight: 600;">WPCalibrate Events Manager</h3>
					<p style="margin: 3px 0 0; color: #50575e; font-size: 13px;">Dedicated WordPress Event Calendar & Management Engine</p>
				</div>
			</div>

			<p style="color: #3c434a; font-size: 14px; line-height: 1.6;">
				Need assistance with event calendars, custom recurrence setups, or extending WPCalibrate? Our engineering and support team is available to assist you.
			</p>

			<div style="background: #f6f7f7; border-radius: 6px; padding: 14px 18px; margin: 18px 0;">
				<ul style="list-style: none; margin: 0; padding: 0; font-size: 14px;">
					<li style="margin-bottom: 10px;">
						<strong><?php esc_html_e( 'Official Website:', 'wpcalibrate-events-manager' ); ?></strong>
						<a href="<?php echo esc_url( self::WEBSITE ); ?>" target="_blank" rel="noopener noreferrer" style="margin-left: 8px; text-decoration: none; color: #2271b1; font-weight: 500;">
							<?php echo esc_html( self::WEBSITE ); ?> &#8599;
						</a>
					</li>
					<li style="margin-bottom: 10px;">
						<strong><?php esc_html_e( 'Plugin Marketplace:', 'wpcalibrate-events-manager' ); ?></strong>
						<a href="<?php echo esc_url( self::MARKETPLACE ); ?>" target="_blank" rel="noopener noreferrer" style="margin-left: 8px; text-decoration: none; color: #2271b1; font-weight: 500;">
							<?php echo esc_html( self::MARKETPLACE ); ?> &#8599;
						</a>
					</li>
					<li style="margin-bottom: 10px;">
						<strong><?php esc_html_e( 'Technical Support Email:', 'wpcalibrate-events-manager' ); ?></strong>
						<a href="mailto:<?php echo esc_attr( self::EMAIL ); ?>" style="margin-left: 8px; text-decoration: none; color: #2271b1; font-weight: 500;">
							<?php echo esc_html( self::EMAIL ); ?>
						</a>
					</li>
					<li>
						<strong><?php esc_html_e( 'Direct Phone / WhatsApp:', 'wpcalibrate-events-manager' ); ?></strong>
						<a href="https://wa.me/<?php echo esc_attr( str_replace( '+', '', self::PHONE ) ); ?>" target="_blank" rel="noopener noreferrer" style="margin-left: 8px; text-decoration: none; color: #2271b1; font-weight: 500;">
							<?php echo esc_html( self::PHONE ); ?> &#8599;
						</a>
					</li>
				</ul>
			</div>

			<div style="font-size: 12px; color: #646970; border-top: 1px solid #f0f0f1; padding-top: 12px;">
				<?php esc_html_e( 'Version 1.0.0 | Licensed under GPL-2.0-or-later | Crafted by WPCalibrate', 'wpcalibrate-events-manager' ); ?>
			</div>
		</div>
		<?php
	}
}
