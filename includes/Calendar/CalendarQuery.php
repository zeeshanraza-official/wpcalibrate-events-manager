<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Calendar;

use DateTimeImmutable;
use DateTimeZone;
use WPCalibrate\EventsManager\Domain\DateTimeHandler;
use WPCalibrate\EventsManager\Occurrences\OccurrenceRepository;
use WPCalibrate\EventsManager\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service calculating bounded date ranges and fetching occurrences for calendar views.
 */
class CalendarQuery {

	private OccurrenceRepository $repository;

	public function __construct( ?OccurrenceRepository $repository = null ) {
		$this->repository = $repository ?: new OccurrenceRepository();
	}

	/**
	 * Fetch occurrences for Month view including grid boundary padding.
	 *
	 * @param int                  $year
	 * @param int                  $month
	 * @param array<string, mixed> $filters
	 * @return array{grid_start: string, grid_end: string, occurrences: array<object>}
	 */
	public function get_month_data( int $year, int $month, array $filters = [] ): array {
		$tz         = DateTimeHandler::get_site_timezone();
		$week_start = (int) SettingsManager::get( 'week_start', 1 ); // 0 = Sun, 1 = Mon

		// First day of target month
		$first_day = new DateTimeImmutable( sprintf( '%04d-%02d-01 00:00:00', $year, $month ), $tz );
		// Last day of target month
		$last_day  = $first_day->modify( 'last day of this month 23:59:59' );

		// Grid start: align backwards to week_start
		$first_wday = (int) $first_day->format( 'w' ); // 0 (Sun) to 6 (Sat)
		$diff_start = ( $first_wday - $week_start + 7 ) % 7;
		$grid_start = $first_day->modify( "-{$diff_start} days 00:00:00" );

		// Grid end: align forwards to complete a 7-day week
		$last_wday  = (int) $last_day->format( 'w' );
		$week_end   = ( $week_start + 6 ) % 7;
		$diff_end   = ( $week_end - $last_wday + 7 ) % 7;
		$grid_end   = $last_day->modify( "+{$diff_end} days 23:59:59" );

		$utc_start = DateTimeHandler::to_utc( $grid_start )->format( DateTimeHandler::MYSQL_FORMAT );
		$utc_end   = DateTimeHandler::to_utc( $grid_end )->format( DateTimeHandler::MYSQL_FORMAT );

		$occurrences = $this->repository->get_occurrences_for_range( $utc_start, $utc_end, $filters );

		return [
			'grid_start'  => $grid_start->format( 'Y-m-d' ),
			'grid_end'    => $grid_end->format( 'Y-m-d' ),
			'occurrences' => $occurrences,
		];
	}

	/**
	 * Fetch occurrences for Week view.
	 *
	 * @param string               $date Y-m-d date within the target week
	 * @param array<string, mixed> $filters
	 * @return array{week_start: string, week_end: string, occurrences: array<object>}
	 */
	public function get_week_data( string $date, array $filters = [] ): array {
		$tz         = DateTimeHandler::get_site_timezone();
		$week_start = (int) SettingsManager::get( 'week_start', 1 );

		$dt   = DateTimeHandler::create( $date, '00:00:00', $tz );
		$wday = (int) $dt->format( 'w' );
		$diff = ( $wday - $week_start + 7 ) % 7;

		$start_local = $dt->modify( "-{$diff} days 00:00:00" );
		$end_local   = $start_local->modify( '+6 days 23:59:59' );

		$utc_start = DateTimeHandler::to_utc( $start_local )->format( DateTimeHandler::MYSQL_FORMAT );
		$utc_end   = DateTimeHandler::to_utc( $end_local )->format( DateTimeHandler::MYSQL_FORMAT );

		$occurrences = $this->repository->get_occurrences_for_range( $utc_start, $utc_end, $filters );

		return [
			'week_start'  => $start_local->format( 'Y-m-d' ),
			'week_end'    => $end_local->format( 'Y-m-d' ),
			'occurrences' => $occurrences,
		];
	}

	/**
	 * Fetch occurrences for Day view.
	 *
	 * @param string               $date Y-m-d
	 * @param array<string, mixed> $filters
	 * @return array<object>
	 */
	public function get_day_data( string $date, array $filters = [] ): array {
		$tz          = DateTimeHandler::get_site_timezone();
		$start_local = DateTimeHandler::create( $date, '00:00:00', $tz );
		$end_local   = DateTimeHandler::create( $date, '23:59:59', $tz );

		$utc_start = DateTimeHandler::to_utc( $start_local )->format( DateTimeHandler::MYSQL_FORMAT );
		$utc_end   = DateTimeHandler::to_utc( $end_local )->format( DateTimeHandler::MYSQL_FORMAT );

		return $this->repository->get_occurrences_for_range( $utc_start, $utc_end, $filters );
	}

	/**
	 * Fetch occurrences for List/Agenda view.
	 *
	 * @param string               $start_date Y-m-d
	 * @param int                  $limit
	 * @param int                  $offset
	 * @param array<string, mixed> $filters
	 * @return array{total: int, occurrences: array<object>}
	 */
	public function get_list_data( string $start_date, int $limit = 10, int $offset = 0, array $filters = [] ): array {
		$tz          = DateTimeHandler::get_site_timezone();
		$start_local = DateTimeHandler::create( $start_date, '00:00:00', $tz );
		$end_local   = $start_local->modify( '+1 year 23:59:59' );

		$utc_start = DateTimeHandler::to_utc( $start_local )->format( DateTimeHandler::MYSQL_FORMAT );
		$utc_end   = DateTimeHandler::to_utc( $end_local )->format( DateTimeHandler::MYSQL_FORMAT );

		$merged_filters           = $filters;
		$merged_filters['limit']  = $limit;
		$merged_filters['offset'] = $offset;

		$occurrences = $this->repository->get_occurrences_for_range( $utc_start, $utc_end, $merged_filters );
		$total       = $this->repository->count_occurrences_for_range( $utc_start, $utc_end, $filters );

		return [
			'total'       => $total,
			'occurrences' => $occurrences,
		];
	}
}
