<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Recurrence;

use DateTimeImmutable;
use DateTimeZone;
use WPCalibrate\EventsManager\Domain\DateTimeHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Calculates deterministic event occurrence dates based on recurrence rules.
 */
class RecurrenceCalculator {

	public const DEFAULT_MAX_OCCURRENCES = 730; // 2 years limit for safe bounding

	/**
	 * Generate all occurrence instances for an event.
	 *
	 * @param DateTimeImmutable $start_local Base start local datetime
	 * @param DateTimeImmutable $end_local   Base end local datetime
	 * @param bool              $all_day     Whether event is all-day
	 * @param RecurrenceRule    $rule        Recurrence rule
	 * @param int               $max_limit   Safety ceiling
	 * @return array<int, array<string, mixed>>
	 */
	public static function calculate(
		DateTimeImmutable $start_local,
		DateTimeImmutable $end_local,
		bool $all_day,
		RecurrenceRule $rule,
		int $max_limit = self::DEFAULT_MAX_OCCURRENCES
	): array {
		$occurrences = [];
		$duration    = $end_local->getTimestamp() - $start_local->getTimestamp();
		$timezone    = $start_local->getTimezone();

		// Case 1: Non-recurring event
		if ( ! $rule->is_recurring() ) {
			$occurrences[] = self::build_occurrence_entry(
				1,
				$start_local,
				$end_local,
				$timezone,
				$all_day,
				$rule
			);
			return $occurrences;
		}

		// Calculate boundary condition
		$target_count = $rule->end_type === RecurrenceRule::END_COUNT && $rule->count !== null
			? min( $rule->count, $max_limit )
			: $max_limit;

		$until_dt = null;
		if ( $rule->end_type === RecurrenceRule::END_UNTIL && ! empty( $rule->until ) ) {
			$until_dt = DateTimeHandler::create( $rule->until, '23:59:59', $timezone );
		}

		switch ( $rule->frequency ) {
			case RecurrenceRule::FREQ_DAILY:
				$occurrences = self::calculate_daily( $start_local, $duration, $timezone, $all_day, $rule, $target_count, $until_dt );
				break;

			case RecurrenceRule::FREQ_WEEKLY:
				$occurrences = self::calculate_weekly( $start_local, $duration, $timezone, $all_day, $rule, $target_count, $until_dt );
				break;

			case RecurrenceRule::FREQ_MONTHLY:
				$occurrences = self::calculate_monthly( $start_local, $duration, $timezone, $all_day, $rule, $target_count, $until_dt );
				break;

			case RecurrenceRule::FREQ_YEARLY:
				$occurrences = self::calculate_yearly( $start_local, $duration, $timezone, $all_day, $rule, $target_count, $until_dt );
				break;

			default:
				$occurrences[] = self::build_occurrence_entry( 1, $start_local, $end_local, $timezone, $all_day, $rule );
				break;
		}

		return $occurrences;
	}

	/**
	 * Daily recurrence calculation.
	 */
	private static function calculate_daily(
		DateTimeImmutable $start,
		int $duration,
		DateTimeZone $tz,
		bool $all_day,
		RecurrenceRule $rule,
		int $target_count,
		?DateTimeImmutable $until
	): array {
		$occurrences = [];
		$current     = $start;
		$sequence    = 1;
		$interval    = max( 1, $rule->interval );

		while ( count( $occurrences ) < $target_count ) {
			if ( $until !== null && $current > $until ) {
				break;
			}

			$curr_end = $current->modify( "+{$duration} seconds" );
			$occurrences[] = self::build_occurrence_entry( $sequence, $current, $curr_end, $tz, $all_day, $rule );

			$sequence++;
			$current = $current->modify( "+{$interval} days" );
		}

		return $occurrences;
	}

	/**
	 * Weekly recurrence calculation with multiple weekdays.
	 */
	private static function calculate_weekly(
		DateTimeImmutable $start,
		int $duration,
		DateTimeZone $tz,
		bool $all_day,
		RecurrenceRule $rule,
		int $target_count,
		?DateTimeImmutable $until
	): array {
		$occurrences = [];
		$sequence    = 1;
		$interval    = max( 1, $rule->interval );
		$weekdays    = $rule->weekdays;

		// Default to base start weekday if none selected
		if ( empty( $weekdays ) ) {
			$weekdays = [ (int) $start->format( 'w' ) ]; // 0 = Sun .. 6 = Sat
		}
		sort( $weekdays );

		// Find the Monday of the starting week
		$start_weekday = (int) $start->format( 'w' ); // 0 (Sun) to 6 (Sat)
		// Calculate diff from Sunday
		$week_start = $start->modify( "-{$start_weekday} days" );

		$current_week = $week_start;
		$time_str     = $start->format( 'H:i:s' );

		while ( count( $occurrences ) < $target_count ) {
			foreach ( $weekdays as $day_index ) {
				$candidate_date = $current_week->modify( "+{$day_index} days" );
				$candidate_str  = $candidate_date->format( 'Y-m-d' ) . ' ' . $time_str;
				$candidate      = new DateTimeImmutable( $candidate_str, $tz );

				// Only include occurrences on or after the original start
				if ( $candidate < $start ) {
					continue;
				}

				if ( $until !== null && $candidate > $until ) {
					return $occurrences;
				}

				$cand_end = $candidate->modify( "+{$duration} seconds" );
				$occurrences[] = self::build_occurrence_entry( $sequence, $candidate, $cand_end, $tz, $all_day, $rule );
				$sequence++;

				if ( count( $occurrences ) >= $target_count ) {
					return $occurrences;
				}
			}

			// Advance by interval weeks
			$current_week = $current_week->modify( "+{$interval} weeks" );
		}

		return $occurrences;
	}

	/**
	 * Monthly recurrence calculation.
	 */
	private static function calculate_monthly(
		DateTimeImmutable $start,
		int $duration,
		DateTimeZone $tz,
		bool $all_day,
		RecurrenceRule $rule,
		int $target_count,
		?DateTimeImmutable $until
	): array {
		$occurrences = [];
		$sequence    = 1;
		$interval    = max( 1, $rule->interval );
		$target_day  = (int) $start->format( 'j' );
		$time_str    = $start->format( 'H:i:s' );

		$year  = (int) $start->format( 'Y' );
		$month = (int) $start->format( 'n' );

		while ( count( $occurrences ) < $target_count ) {
			if ( $rule->monthly_type === RecurrenceRule::MONTHLY_BY_DAY_OF_WEEK ) {
				$week_words = [
					'first'  => 'first',
					'second' => 'second',
					'third'  => 'third',
					'fourth' => 'fourth',
					'last'   => 'last',
				];
				$day_words = [
					0 => 'sunday',
					1 => 'monday',
					2 => 'tuesday',
					3 => 'wednesday',
					4 => 'thursday',
					5 => 'friday',
					6 => 'saturday',
				];

				$w_word = $week_words[ $rule->monthly_week ] ?? 'first';
				$d_word = $day_words[ $rule->monthly_weekday ] ?? 'monday';

				$month_start = new DateTimeImmutable( sprintf( '%04d-%02d-01 00:00:00', $year, $month ), $tz );
				$cand_base   = $month_start->modify( "{$w_word} {$d_word} of this month" );
				$candidate   = new DateTimeImmutable( $cand_base->format( 'Y-m-d' ) . ' ' . $time_str, $tz );
			} else {
				// Day of month: clamp to maximum days in month
				$days_in_month = (int) date( 't', strtotime( sprintf( '%04d-%02d-01', $year, $month ) ) );
				$actual_day    = min( $target_day, $days_in_month );
				$candidate     = new DateTimeImmutable( sprintf( '%04d-%02d-%02d %s', $year, $month, $actual_day, $time_str ), $tz );
			}

			if ( $candidate >= $start ) {
				if ( $until !== null && $candidate > $until ) {
					break;
				}

				$cand_end = $candidate->modify( "+{$duration} seconds" );
				$occurrences[] = self::build_occurrence_entry( $sequence, $candidate, $cand_end, $tz, $all_day, $rule );
				$sequence++;
			}

			// Advance month
			$month += $interval;
			while ( $month > 12 ) {
				$month -= 12;
				$year++;
			}
		}

		return $occurrences;
	}

	/**
	 * Yearly recurrence calculation.
	 */
	private static function calculate_yearly(
		DateTimeImmutable $start,
		int $duration,
		DateTimeZone $tz,
		bool $all_day,
		RecurrenceRule $rule,
		int $target_count,
		?DateTimeImmutable $until
	): array {
		$occurrences = [];
		$sequence    = 1;
		$interval    = max( 1, $rule->interval );
		$target_month = (int) $start->format( 'n' );
		$target_day   = (int) $start->format( 'j' );
		$time_str     = $start->format( 'H:i:s' );
		$year         = (int) $start->format( 'Y' );

		while ( count( $occurrences ) < $target_count ) {
			// Handle leap year clamp (Feb 29)
			$days_in_month = (int) date( 't', strtotime( sprintf( '%04d-%02d-01', $year, $target_month ) ) );
			$actual_day    = min( $target_day, $days_in_month );

			$candidate = new DateTimeImmutable( sprintf( '%04d-%02d-%02d %s', $year, $target_month, $actual_day, $time_str ), $tz );

			if ( $until !== null && $candidate > $until ) {
				break;
			}

			$cand_end = $candidate->modify( "+{$duration} seconds" );
			$occurrences[] = self::build_occurrence_entry( $sequence, $candidate, $cand_end, $tz, $all_day, $rule );
			$sequence++;

			$year += $interval;
		}

		return $occurrences;
	}

	/**
	 * Build normalized occurrence entry structure including exception and exclusion checks.
	 */
	private static function build_occurrence_entry(
		int $sequence,
		DateTimeImmutable $start_local,
		DateTimeImmutable $end_local,
		DateTimeZone $tz,
		bool $all_day,
		RecurrenceRule $rule
	): array {
		$date_key       = $start_local->format( 'Y-m-d' );
		$is_excluded    = in_array( $date_key, $rule->excluded_dates, true );
		$exception_data = $rule->exceptions[ $date_key ] ?? null;

		$status         = 'publish';
		$is_exception   = false;
		$exception_type = null;

		if ( $is_excluded ) {
			$status         = 'cancelled';
			$is_exception   = true;
			$exception_type = 'excluded';
		} elseif ( is_array( $exception_data ) ) {
			$is_exception = true;
			if ( isset( $exception_data['status'] ) ) {
				$status         = sanitize_text_field( (string) $exception_data['status'] );
				$exception_type = $status;
			}
			if ( ! empty( $exception_data['start_time'] ) && ! empty( $exception_data['end_time'] ) ) {
				$exception_type = 'modified_time';
				$start_local    = DateTimeHandler::create( $date_key, (string) $exception_data['start_time'], $tz );
				$end_local      = DateTimeHandler::create( $date_key, (string) $exception_data['end_time'], $tz );
			}
		}

		$start_utc = DateTimeHandler::to_utc( $start_local );
		$end_utc   = DateTimeHandler::to_utc( $end_local );

		return [
			'sequence'             => $sequence,
			'start_datetime_local' => $start_local->format( DateTimeHandler::MYSQL_FORMAT ),
			'end_datetime_local'   => $end_local->format( DateTimeHandler::MYSQL_FORMAT ),
			'start_datetime_utc'   => $start_utc->format( DateTimeHandler::MYSQL_FORMAT ),
			'end_utc'              => $end_utc->format( DateTimeHandler::MYSQL_FORMAT ),
			'end_datetime_utc'     => $end_utc->format( DateTimeHandler::MYSQL_FORMAT ),
			'timezone'             => $tz->getName(),
			'all_day'              => $all_day ? 1 : 0,
			'status'               => $status,
			'is_exception'         => $is_exception ? 1 : 0,
			'exception_type'       => $exception_type,
		];
	}
}
