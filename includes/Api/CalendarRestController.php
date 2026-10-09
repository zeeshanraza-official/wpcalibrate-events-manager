<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Api;

use WPCalibrate\EventsManager\Calendar\CalendarQuery;
use WPCalibrate\EventsManager\Calendar\Views\DayView;
use WPCalibrate\EventsManager\Calendar\Views\ListView;
use WPCalibrate\EventsManager\Calendar\Views\MonthView;
use WPCalibrate\EventsManager\Calendar\Views\WeekView;
use WPCalibrate\EventsManager\Events\EventPostType;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API Controller for public calendar views and event querying.
 */
class CalendarRestController extends WP_REST_Controller {

	public const REST_NAMESPACE = 'wpcalibrate-events/v1';

	public function __construct() {
		$this->namespace = self::REST_NAMESPACE;
		$this->rest_base = 'calendar';
	}

	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/view',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_calendar_view' ],
					'permission_callback' => [ $this, 'check_public_permission' ],
					'args'                => [
						'view'     => [
							'type'              => 'string',
							'default'           => 'month',
							'sanitize_callback' => 'sanitize_key',
							'validate_callback' => static fn( $v ) => in_array( $v, [ 'month', 'week', 'day', 'list' ], true ),
						],
						'year'     => [
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						],
						'month'    => [
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						],
						'date'     => [
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'page'     => [
							'type'              => 'integer',
							'default'           => 1,
							'sanitize_callback' => 'absint',
						],
						'category' => [
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						],
						'venue'    => [
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						],
						'search'   => [
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
			]
		);

		register_rest_route(
			$this->namespace,
			'/events',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_events_list' ],
					'permission_callback' => [ $this, 'check_public_permission' ],
					'args'                => [
						'start'    => [
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'end'      => [
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'category' => [
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						],
						'limit'    => [
							'type'              => 'integer',
							'default'           => 20,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);
	}

	/**
	 * Explicit permission callback for public calendar events (returns true).
	 *
	 * @param WP_REST_Request $request
	 * @return bool
	 */
	public function check_public_permission( WP_REST_Request $request ): bool {
		return true;
	}

	/**
	 * Render HTML and data for dynamic view navigation via AJAX/REST.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_calendar_view( WP_REST_Request $request ): WP_REST_Response {
		$view     = (string) $request->get_param( 'view' ) ?: 'month';
		$year     = (int) $request->get_param( 'year' ) ?: (int) gmdate( 'Y' );
		$month    = (int) $request->get_param( 'month' ) ?: (int) gmdate( 'n' );
		$date     = (string) $request->get_param( 'date' ) ?: current_time( 'Y-m-d' );
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$category = (int) $request->get_param( 'category' ) ?: null;
		$venue    = (int) $request->get_param( 'venue' ) ?: null;
		$search   = (string) $request->get_param( 'search' ) ?: '';

		$filters = [
			'status'      => 'publish',
			'category_id' => $category,
			'venue_id'    => $venue,
			'search'      => $search,
		];

		$html = '';
		switch ( $view ) {
			case 'week':
				$html = WeekView::render( $date, $filters );
				break;

			case 'day':
				$html = DayView::render( $date, $filters );
				break;

			case 'list':
				$html = ListView::render( $date, $page, $filters );
				break;

			case 'month':
			default:
				$html = MonthView::render( $year, $month, $filters );
				break;
		}

		return new WP_REST_Response( [
			'success' => true,
			'view'    => $view,
			'html'    => $html,
		], 200 );
	}

	/**
	 * Return bounded list of public occurrences.
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_events_list( WP_REST_Request $request ): WP_REST_Response {
		$query = new CalendarQuery();

		$start = (string) $request->get_param( 'start' ) ?: current_time( 'Y-m-d' );
		$limit = min( 100, max( 1, (int) $request->get_param( 'limit' ) ) );
		$cat   = (int) $request->get_param( 'category' ) ?: null;

		$data = $query->get_list_data( $start, $limit, 0, [ 'category_id' => $cat, 'status' => 'publish' ] );

		return new WP_REST_Response( [
			'total'  => $data['total'],
			'events' => $data['occurrences'],
		], 200 );
	}
}
