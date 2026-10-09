<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Domain;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles timezone conversion, date formatting, and canonical datetime normalization.
 */
class DateTimeHandler {

	public const MYSQL_FORMAT = 'Y-m-d H:i:s';
	public const DATE_FORMAT  = 'Y-m-d';
	public const TIME_FORMAT  = 'H:i:s';

	/**
	 * Get the WordPress site's configured timezone.
	 *
	 * @return DateTimeZone
	 */
	public static function get_site_timezone(): DateTimeZone {
		if ( function_exists( 'wp_timezone' ) ) {
			return wp_timezone();
		}

		$timezone_string = get_option( 'timezone_string' );
		if ( ! empty( $timezone_string ) ) {
			try {
				return new DateTimeZone( $timezone_string );
			} catch ( Exception $e ) {
				// Fallback below
			}
		}

		$offset = (float) get_option( 'gmt_offset', 0 );
		$hours  = (int) $offset;
		$minutes = (int) ( ( $offset - $hours ) * 60 );
		$sign    = $offset < 0 ? '-' : '+';
		$formatted_offset = sprintf( '%s%02d:%02d', $sign, abs( $hours ), abs( $minutes ) );

		try {
			return new DateTimeZone( $formatted_offset );
		} catch ( Exception $e ) {
			return new DateTimeZone( 'UTC' );
		}
	}

	/**
	 * Get valid DateTimeZone for a given timezone string or fallback to site timezone.
	 *
	 * @param string|null $timezone_name
	 * @return DateTimeZone
	 */
	public static function resolve_timezone( ?string $timezone_name = null ): DateTimeZone {
		if ( ! empty( $timezone_name ) ) {
			try {
				return new DateTimeZone( $timezone_name );
			} catch ( Exception $e ) {
				// Fall through to site timezone
			}
		}

		return self::get_site_timezone();
	}

	/**
	 * Create a DateTimeImmutable from date string, time string, and timezone.
	 *
	 * @param string       $date Date in Y-m-d format.
	 * @param string       $time Time in H:i:s or H:i format.
	 * @param DateTimeZone $timezone
	 * @return DateTimeImmutable
	 */
	public static function create( string $date, string $time, DateTimeZone $timezone ): DateTimeImmutable {
		$time_clean = strlen( $time ) === 5 ? $time . ':00' : $time;
		$combined   = trim( $date ) . ' ' . trim( $time_clean );

		try {
			return new DateTimeImmutable( $combined, $timezone );
		} catch ( Exception $e ) {
			return new DateTimeImmutable( 'now', $timezone );
		}
	}

	/**
	 * Convert any DateTimeImmutable to UTC.
	 *
	 * @param DateTimeImmutable $datetime
	 * @return DateTimeImmutable
	 */
	public static function to_utc( DateTimeImmutable $datetime ): DateTimeImmutable {
		return $datetime->setTimezone( new DateTimeZone( 'UTC' ) );
	}

	/**
	 * Parse UTC datetime string from DB into target timezone.
	 *
	 * @param string       $utc_mysql_string Datetime in Y-m-d H:i:s format (UTC).
	 * @param DateTimeZone $target_timezone
	 * @return DateTimeImmutable
	 */
	public static function from_utc( string $utc_mysql_string, DateTimeZone $target_timezone ): DateTimeImmutable {
		try {
			$utc_dt = new DateTimeImmutable( $utc_mysql_string, new DateTimeZone( 'UTC' ) );
			return $utc_dt->setTimezone( $target_timezone );
		} catch ( Exception $e ) {
			return new DateTimeImmutable( 'now', $target_timezone );
		}
	}

	/**
	 * Format date for display according to WordPress settings or given format.
	 *
	 * @param DateTimeImmutable $datetime
	 * @param string|null       $format
	 * @return string
	 */
	public static function format_date( DateTimeImmutable $datetime, ?string $format = null ): string {
		$format = $format ?: (string) get_option( 'date_format', 'F j, Y' );
		if ( function_exists( 'wp_date' ) ) {
			return wp_date( $format, $datetime->getTimestamp(), $datetime->getTimezone() );
		}
		return $datetime->format( $format );
	}

	/**
	 * Format time for display according to WordPress settings or given format.
	 *
	 * @param DateTimeImmutable $datetime
	 * @param string|null       $format
	 * @return string
	 */
	public static function format_time( DateTimeImmutable $datetime, ?string $format = null ): string {
		$format = $format ?: (string) get_option( 'time_format', 'g:i a' );
		if ( function_exists( 'wp_date' ) ) {
			return wp_date( $format, $datetime->getTimestamp(), $datetime->getTimezone() );
		}
		return $datetime->format( $format );
	}

	/**
	 * Format combined date and time for display.
	 *
	 * @param DateTimeImmutable $datetime
	 * @param bool              $all_day
	 * @return string
	 */
	public static function format_full( DateTimeImmutable $datetime, bool $all_day = false ): string {
		if ( $all_day ) {
			return self::format_date( $datetime );
		}
		return sprintf( '%s %s', self::format_date( $datetime ), self::format_time( $datetime ) );
	}

	/**
	 * Normalize all-day event start and end datetimes.
	 *
	 * @param string       $start_date Y-m-d
	 * @param string       $end_date   Y-m-d
	 * @param DateTimeZone $timezone
	 * @return array{start_local: DateTimeImmutable, end_local: DateTimeImmutable, start_utc: DateTimeImmutable, end_utc: DateTimeImmutable}
	 */
	public static function normalize_all_day_range(
		string $start_date,
		string $end_date,
		DateTimeZone $timezone
	): array {
		$start_local = self::create( $start_date, '00:00:00', $timezone );
		$end_local   = self::create( $end_date, '23:59:59', $timezone );

		return [
			'start_local' => $start_local,
			'end_local'   => $end_local,
			'start_utc'   => self::to_utc( $start_local ),
			'end_utc'     => self::to_utc( $end_local ),
		];
	}

	/**
	 * Normalize timed event start and end datetimes.
	 *
	 * @param string       $start_date Y-m-d
	 * @param string       $start_time H:i
	 * @param string       $end_date   Y-m-d
	 * @param string       $end_time   H:i
	 * @param DateTimeZone $timezone
	 * @return array{start_local: DateTimeImmutable, end_local: DateTimeImmutable, start_utc: DateTimeImmutable, end_utc: DateTimeImmutable}
	 */
	public static function normalize_timed_range(
		string $start_date,
		string $start_time,
		string $end_date,
		string $end_time,
		DateTimeZone $timezone
	): array {
		$start_local = self::create( $start_date, $start_time, $timezone );
		$end_local   = self::create( $end_date, $end_time, $timezone );

		// Guard: If end is before start, clamp end to start + 1 hour
		if ( $end_local < $start_local ) {
			$end_local = $start_local->modify( '+1 hour' );
		}

		return [
			'start_local' => $start_local,
			'end_local'   => $end_local,
			'start_utc'   => self::to_utc( $start_local ),
			'end_utc'     => self::to_utc( $end_local ),
		];
	}
}
