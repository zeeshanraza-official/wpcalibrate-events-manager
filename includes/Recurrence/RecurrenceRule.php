<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Recurrence;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Value object representing an event's recurrence rule configuration.
 */
class RecurrenceRule {

	public const FREQ_NONE    = 'none';
	public const FREQ_DAILY   = 'daily';
	public const FREQ_WEEKLY  = 'weekly';
	public const FREQ_MONTHLY = 'monthly';
	public const FREQ_YEARLY  = 'yearly';

	public const END_NEVER = 'never';
	public const END_UNTIL = 'until';
	public const END_COUNT = 'count';

	public const MONTHLY_BY_DAY_OF_MONTH = 'day_of_month';
	public const MONTHLY_BY_DAY_OF_WEEK  = 'day_of_week';

	/**
	 * @param string        $frequency
	 * @param int           $interval
	 * @param array<int>    $weekdays          0 = Sun, 1 = Mon, ..., 6 = Sat
	 * @param string        $monthly_type      'day_of_month' or 'day_of_week'
	 * @param string        $monthly_week      'first', 'second', 'third', 'fourth', 'last'
	 * @param int           $monthly_weekday   0 = Sun, ..., 6 = Sat
	 * @param string        $end_type          'never', 'until', 'count'
	 * @param string|null   $until             Date string Y-m-d
	 * @param int|null      $count             Positive integer
	 * @param array<string> $excluded_dates    Array of Y-m-d strings
	 * @param array<string, array<string, mixed>> $exceptions Keyed by Y-m-d
	 */
	public function __construct(
		public readonly string $frequency = self::FREQ_NONE,
		public readonly int $interval = 1,
		public readonly array $weekdays = [],
		public readonly string $monthly_type = self::MONTHLY_BY_DAY_OF_MONTH,
		public readonly string $monthly_week = 'first',
		public readonly int $monthly_weekday = 1,
		public readonly string $end_type = self::END_NEVER,
		public readonly ?string $until = null,
		public readonly ?int $count = null,
		public readonly array $excluded_dates = [],
		public readonly array $exceptions = []
	) {}

	/**
	 * Check if rule has recurrence enabled.
	 *
	 * @return bool
	 */
	public function is_recurring(): bool {
		return $this->frequency !== self::FREQ_NONE;
	}

	/**
	 * Create rule from post meta values.
	 *
	 * @param int $post_id
	 * @return self
	 */
	public static function from_post_id( int $post_id ): self {
		$enabled = (bool) get_post_meta( $post_id, '_wpcal_recurrence_enabled', true );
		if ( ! $enabled ) {
			return new self( self::FREQ_NONE );
		}

		$frequency    = (string) get_post_meta( $post_id, '_wpcal_recurrence_frequency', true ) ?: self::FREQ_NONE;
		$interval     = max( 1, (int) get_post_meta( $post_id, '_wpcal_recurrence_interval', true ) );
		$weekdays_raw = get_post_meta( $post_id, '_wpcal_recurrence_weekdays', true );
		$weekdays     = is_array( $weekdays_raw ) ? array_map( 'intval', $weekdays_raw ) : [];

		$monthly_type    = (string) get_post_meta( $post_id, '_wpcal_recurrence_monthly_type', true ) ?: self::MONTHLY_BY_DAY_OF_MONTH;
		$monthly_week    = (string) get_post_meta( $post_id, '_wpcal_recurrence_monthly_week', true ) ?: 'first';
		$monthly_weekday = (int) get_post_meta( $post_id, '_wpcal_recurrence_monthly_weekday', true );

		$end_type = (string) get_post_meta( $post_id, '_wpcal_recurrence_end_type', true ) ?: self::END_NEVER;
		$until    = get_post_meta( $post_id, '_wpcal_recurrence_until', true );
		$until    = ! empty( $until ) ? (string) $until : null;
		$count    = get_post_meta( $post_id, '_wpcal_recurrence_count', true );
		$count    = ! empty( $count ) ? (int) $count : null;

		$excluded_raw = get_post_meta( $post_id, '_wpcal_recurrence_excluded_dates', true );
		$excluded     = is_array( $excluded_raw ) ? array_values( array_filter( array_map( 'strval', $excluded_raw ) ) ) : [];

		$exceptions_raw = get_post_meta( $post_id, '_wpcal_recurrence_exceptions', true );
		$exceptions     = is_array( $exceptions_raw ) ? $exceptions_raw : [];

		return new self(
			frequency: $frequency,
			interval: $interval,
			weekdays: $weekdays,
			monthly_type: $monthly_type,
			monthly_week: $monthly_week,
			monthly_weekday: $monthly_weekday,
			end_type: $end_type,
			until: $until,
			count: $count,
			excluded_dates: $excluded,
			exceptions: $exceptions
		);
	}

	/**
	 * Save rule to post meta.
	 *
	 * @param int $post_id
	 */
	public function save_to_post( int $post_id ): void {
		update_post_meta( $post_id, '_wpcal_recurrence_enabled', $this->is_recurring() ? 1 : 0 );
		update_post_meta( $post_id, '_wpcal_recurrence_frequency', sanitize_text_field( $this->frequency ) );
		update_post_meta( $post_id, '_wpcal_recurrence_interval', max( 1, $this->interval ) );
		update_post_meta( $post_id, '_wpcal_recurrence_weekdays', array_map( 'intval', $this->weekdays ) );
		update_post_meta( $post_id, '_wpcal_recurrence_monthly_type', sanitize_text_field( $this->monthly_type ) );
		update_post_meta( $post_id, '_wpcal_recurrence_monthly_week', sanitize_text_field( $this->monthly_week ) );
		update_post_meta( $post_id, '_wpcal_recurrence_monthly_weekday', (int) $this->monthly_weekday );
		update_post_meta( $post_id, '_wpcal_recurrence_end_type', sanitize_text_field( $this->end_type ) );
		update_post_meta( $post_id, '_wpcal_recurrence_until', $this->until ? sanitize_text_field( $this->until ) : '' );
		update_post_meta( $post_id, '_wpcal_recurrence_count', $this->count ? (int) $this->count : '' );
		update_post_meta( $post_id, '_wpcal_recurrence_excluded_dates', array_map( 'sanitize_text_field', $this->excluded_dates ) );
		update_post_meta( $post_id, '_wpcal_recurrence_exceptions', $this->exceptions );
	}

	/**
	 * Human readable summary of the rule.
	 *
	 * @return string
	 */
	public function get_human_summary(): string {
		if ( ! $this->is_recurring() ) {
			return __( 'One-time event (does not repeat)', 'wpcalibrate-events-manager' );
		}

		$parts = [];
		switch ( $this->frequency ) {
			case self::FREQ_DAILY:
				$parts[] = $this->interval === 1
					? __( 'Daily', 'wpcalibrate-events-manager' )
					: sprintf( __( 'Every %d days', 'wpcalibrate-events-manager' ), $this->interval );
				break;

			case self::FREQ_WEEKLY:
				$day_names = [
					0 => __( 'Sun', 'wpcalibrate-events-manager' ),
					1 => __( 'Mon', 'wpcalibrate-events-manager' ),
					2 => __( 'Tue', 'wpcalibrate-events-manager' ),
					3 => __( 'Wed', 'wpcalibrate-events-manager' ),
					4 => __( 'Thu', 'wpcalibrate-events-manager' ),
					5 => __( 'Fri', 'wpcalibrate-events-manager' ),
					6 => __( 'Sat', 'wpcalibrate-events-manager' ),
				];
				$selected_days = array_intersect_key( $day_names, array_flip( $this->weekdays ) );
				$days_str = ! empty( $selected_days ) ? implode( ', ', $selected_days ) : __( 'selected days', 'wpcalibrate-events-manager' );

				$parts[] = $this->interval === 1
					? sprintf( __( 'Weekly on %s', 'wpcalibrate-events-manager' ), $days_str )
					: sprintf( __( 'Every %1$d weeks on %2$s', 'wpcalibrate-events-manager' ), $this->interval, $days_str );
				break;

			case self::FREQ_MONTHLY:
				$parts[] = $this->interval === 1
					? __( 'Monthly', 'wpcalibrate-events-manager' )
					: sprintf( __( 'Every %d months', 'wpcalibrate-events-manager' ), $this->interval );
				break;

			case self::FREQ_YEARLY:
				$parts[] = $this->interval === 1
					? __( 'Yearly', 'wpcalibrate-events-manager' )
					: sprintf( __( 'Every %d years', 'wpcalibrate-events-manager' ), $this->interval );
				break;
		}

		if ( $this->end_type === self::END_UNTIL && $this->until ) {
			$parts[] = sprintf( __( 'until %s', 'wpcalibrate-events-manager' ), $this->until );
		} elseif ( $this->end_type === self::END_COUNT && $this->count ) {
			$parts[] = sprintf( __( 'for %d occurrences', 'wpcalibrate-events-manager' ), $this->count );
		}

		return implode( ', ', $parts );
	}
}
