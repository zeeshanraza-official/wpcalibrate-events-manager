<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Maps;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * OpenStreetMap provider implementation using open web tile standards.
 */
class OpenStreetMapProvider implements MapProviderInterface {

	/**
	 * Render OpenStreetMap embed HTML.
	 *
	 * @param float|null  $latitude
	 * @param float|null  $longitude
	 * @param string|null $address
	 * @param int         $zoom
	 * @return string HTML output
	 */
	public function render_map( ?float $latitude, ?float $longitude, ?string $address = null, int $zoom = 14 ): string {
		if ( $latitude === null || $longitude === null ) {
			if ( empty( $address ) ) {
				return '';
			}
			// Search URL fallback
			$search_url = 'https://www.openstreetmap.org/search?query=' . rawurlencode( $address );
			return sprintf(
				'<div class="wpcal-map-fallback"><a href="%s" target="_blank" rel="noopener noreferrer" class="button button-secondary">%s</a></div>',
				esc_url( $search_url ),
				esc_html__( 'View on OpenStreetMap &#8599;', 'wpcalibrate-events-manager' )
			);
		}

		// Calculate bounding box based on zoom level
		$offset = 0.008 * ( 15 / max( 1, $zoom ) );
		$min_lon = $longitude - $offset;
		$max_lon = $longitude + $offset;
		$min_lat = $latitude - $offset;
		$max_lat = $latitude + $offset;

		$embed_url = sprintf(
			'https://www.openstreetmap.org/export/embed.html?bbox=%f%%2C%f%%2C%f%%2C%f&layer=mapnik&marker=%f%%2C%f',
			$min_lon,
			$min_lat,
			$max_lon,
			$max_lat,
			$latitude,
			$longitude
		);

		$view_url = sprintf(
			'https://www.openstreetmap.org/?mlat=%f&mlon=%f#map=%d/%f/%f',
			$latitude,
			$longitude,
			$zoom,
			$latitude,
			$longitude
		);

		ob_start();
		?>
		<div class="wpcal-map-container" style="margin-top: 15px; border-radius: 8px; overflow: hidden; border: 1px solid #e2e4e7;">
			<iframe
				width="100%"
				height="280"
				frameborder="0"
				scrolling="no"
				marginheight="0"
				marginwidth="0"
				src="<?php echo esc_url( $embed_url ); ?>"
				style="border: 0;"
				title="<?php esc_attr_e( 'Event Venue Location Map', 'wpcalibrate-events-manager' ); ?>"
				loading="lazy">
			</iframe>
			<div class="wpcal-map-footer" style="padding: 6px 12px; background: #f6f7f7; font-size: 11px; text-align: right;">
				<a href="<?php echo esc_url( $view_url ); ?>" target="_blank" rel="noopener noreferrer" style="color: #646970; text-decoration: none;">
					<?php esc_html_e( 'View Larger Map on OpenStreetMap &#8599;', 'wpcalibrate-events-manager' ); ?>
				</a>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
