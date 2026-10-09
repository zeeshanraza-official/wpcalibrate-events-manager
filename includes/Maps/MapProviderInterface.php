<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Maps;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Interface contract for map providers.
 */
interface MapProviderInterface {

	/**
	 * Render map embed HTML for given coordinates or address.
	 *
	 * @param float|null  $latitude
	 * @param float|null  $longitude
	 * @param string|null $address
	 * @param int         $zoom
	 * @return string HTML output
	 */
	public function render_map( ?float $latitude, ?float $longitude, ?string $address = null, int $zoom = 14 ): string;
}
