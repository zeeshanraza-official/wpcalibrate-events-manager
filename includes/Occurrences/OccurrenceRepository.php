<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Occurrences;

use WPCalibrate\EventsManager\Database\OccurrenceSchema;
use WPCalibrate\EventsManager\Events\EventPostType;
use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Occurrence data access layer for high-performance indexed queries.
 */
class OccurrenceRepository {

	/**
	 * Insert or replace batch occurrences for an event.
	 *
	 * @param int                          $event_id
	 * @param array<array<string, mixed>>  $occurrences
	 * @return int Number of inserted rows
	 */
	public function save_occurrences( int $event_id, array $occurrences ): int {
		global $wpdb;

		$table = OccurrenceSchema::get_table_name();

		// Clean old occurrences for this event
		$this->delete_for_event( $event_id );

		if ( empty( $occurrences ) ) {
			return 0;
		}

		$inserted = 0;
		// Batch in chunks of 50 to avoid query size limits
		$chunks = array_chunk( $occurrences, 50 );

		foreach ( $chunks as $chunk ) {
			$placeholders = [];
			$values       = [];

			foreach ( $chunk as $occ ) {
				$placeholders[] = '(%d, %s, %s, %s, %s, %s, %d, %s, %d, %s, %d)';
				$values[]       = $event_id;
				$values[]       = $occ['start_datetime_utc'];
				$values[]       = $occ['end_datetime_utc'];
				$values[]       = $occ['start_datetime_local'];
				$values[]       = $occ['end_datetime_local'];
				$values[]       = $occ['timezone'];
				$values[]       = $occ['all_day'];
				$values[]       = $occ['status'];
				$values[]       = $occ['is_exception'];
				$values[]       = $occ['exception_type'] ?? null;
				$values[]       = $occ['sequence'];
			}

			$query = "INSERT INTO {$table} (
				event_id,
				start_datetime_utc,
				end_datetime_utc,
				start_datetime_local,
				end_datetime_local,
				timezone,
				all_day,
				status,
				is_exception,
				exception_type,
				sequence
			) VALUES " . implode( ', ', $placeholders );

			$prepared = $wpdb->prepare( $query, $values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$res      = $wpdb->query( $prepared ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $res ) {
				$inserted += (int) $res;
			}
		}

		return $inserted;
	}

	/**
	 * Delete all occurrences for a specific event.
	 *
	 * @param int $event_id
	 * @return int
	 */
	public function delete_for_event( int $event_id ): int {
		global $wpdb;
		$table = OccurrenceSchema::get_table_name();
		return (int) $wpdb->delete( $table, [ 'event_id' => $event_id ], [ '%d' ] );
	}

	/**
	 * Query occurrences bounded by date range with optional filtering.
	 *
	 * @param string              $start_utc Range start in Y-m-d H:i:s (UTC)
	 * @param string              $end_utc   Range end in Y-m-d H:i:s (UTC)
	 * @param array<string, mixed> $args      Query filters
	 * @return array<object>
	 */
	public function get_occurrences_for_range( string $start_utc, string $end_utc, array $args = [] ): array {
		global $wpdb;

		$table = OccurrenceSchema::get_table_name();

		$defaults = [
			'status'        => 'publish', // Or 'all', or array
			'event_id'      => null,
			'category_id'   => null,
			'venue_id'      => null,
			'organizer_id'  => null,
			'search'        => '',
			'limit'         => 200,
			'offset'        => 0,
			'orderby'       => 'start_utc',
			'order'         => 'ASC',
		];
		$params = wp_parse_args( $args, $defaults );

		// Overlap condition: start <= range_end AND end >= range_start
		$where_clauses = [
			'o.start_datetime_utc <= %s',
			'o.end_datetime_utc >= %s',
		];
		$query_values  = [ $end_utc, $start_utc ];

		// Status filter
		if ( $params['status'] !== 'all' ) {
			if ( is_array( $params['status'] ) ) {
				$escaped_statuses = array_map( 'esc_sql', $params['status'] );
				$where_clauses[]  = "o.status IN ('" . implode( "','", $escaped_statuses ) . "')";
			} else {
				$where_clauses[] = 'o.status = %s';
				$query_values[]  = sanitize_text_field( $params['status'] );
			}
		}

		// Single Event filter
		if ( ! empty( $params['event_id'] ) ) {
			$where_clauses[] = 'o.event_id = %d';
			$query_values[]  = (int) $params['event_id'];
		}

		// Venue filter
		if ( ! empty( $params['venue_id'] ) ) {
			$where_clauses[] = "EXISTS (
				SELECT 1 FROM {$wpdb->postmeta} pm_v
				WHERE pm_v.post_id = o.event_id
				AND pm_v.meta_key = '_wpcal_venue_id'
				AND pm_v.meta_value = %s
			)";
			$query_values[]  = (string) ( (int) $params['venue_id'] );
		}

		// Category filter
		if ( ! empty( $params['category_id'] ) ) {
			$where_clauses[] = "EXISTS (
				SELECT 1 FROM {$wpdb->term_relationships} tr
				JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				WHERE tr.object_id = o.event_id
				AND tt.taxonomy = 'wpcal_event_category'
				AND tt.term_id = %d
			)";
			$query_values[]  = (int) $params['category_id'];
		}

		// Search filter
		if ( ! empty( $params['search'] ) ) {
			$where_clauses[] = "EXISTS (
				SELECT 1 FROM {$wpdb->posts} p_search
				WHERE p_search.ID = o.event_id
				AND p_search.post_status = 'publish'
				AND (p_search.post_title LIKE %s OR p_search.post_content LIKE %s)
			)";
			$like_term       = '%' . $wpdb->esc_like( sanitize_text_field( $params['search'] ) ) . '%';
			$query_values[]  = $like_term;
			$query_values[]  = $like_term;
		}

		// Ensure parent event post exists and is published
		$where_clauses[] = "EXISTS (
			SELECT 1 FROM {$wpdb->posts} p_main
			WHERE p_main.ID = o.event_id
			AND p_main.post_type = %s
			AND p_main.post_status = 'publish'
		)";
		$query_values[]  = EventPostType::POST_TYPE;

		// Sorting allowlist
		$sort_allowlist = [
			'start_utc'   => 'o.start_datetime_utc',
			'end_utc'     => 'o.end_datetime_utc',
			'start_local' => 'o.start_datetime_local',
			'id'          => 'o.id',
		];
		$orderby_col = $sort_allowlist[ $params['orderby'] ] ?? 'o.start_datetime_utc';
		$order_dir   = strtoupper( (string) $params['order'] ) === 'DESC' ? 'DESC' : 'ASC';

		// Pagination
		$limit  = max( 1, min( 500, (int) $params['limit'] ) );
		$offset = max( 0, (int) $params['offset'] );

		$where_sql = implode( ' AND ', $where_clauses );
		$sql = "SELECT o.*, p.post_title as event_title, p.post_excerpt as event_excerpt, p.post_content as event_content
			FROM {$table} o
			JOIN {$wpdb->posts} p ON p.ID = o.event_id
			WHERE {$where_sql}
			ORDER BY {$orderby_col} {$order_dir}
			LIMIT %d OFFSET %d";

		$query_values[] = $limit;
		$query_values[] = $offset;

		$prepared = $wpdb->prepare( $sql, $query_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results  = $wpdb->get_results( $prepared );

		return is_array( $results ) ? $results : [];
	}

	/**
	 * Count occurrences in a range for pagination.
	 *
	 * @param string              $start_utc
	 * @param string              $end_utc
	 * @param array<string, mixed> $args
	 * @return int
	 */
	public function count_occurrences_for_range( string $start_utc, string $end_utc, array $args = [] ): int {
		global $wpdb;

		$table = OccurrenceSchema::get_table_name();

		$where_clauses = [
			'o.start_datetime_utc <= %s',
			'o.end_datetime_utc >= %s',
		];
		$query_values  = [ $end_utc, $start_utc ];

		if ( isset( $args['status'] ) && $args['status'] !== 'all' ) {
			$where_clauses[] = 'o.status = %s';
			$query_values[]  = sanitize_text_field( $args['status'] );
		}

		if ( ! empty( $args['category_id'] ) ) {
			$where_clauses[] = "EXISTS (
				SELECT 1 FROM {$wpdb->term_relationships} tr
				JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				WHERE tr.object_id = o.event_id
				AND tt.taxonomy = 'wpcal_event_category'
				AND tt.term_id = %d
			)";
			$query_values[]  = (int) $args['category_id'];
		}

		$where_clauses[] = "EXISTS (
			SELECT 1 FROM {$wpdb->posts} p_main
			WHERE p_main.ID = o.event_id
			AND p_main.post_type = %s
			AND p_main.post_status = 'publish'
		)";
		$query_values[] = EventPostType::POST_TYPE;

		$where_sql = implode( ' AND ', $where_clauses );
		$sql       = "SELECT COUNT(*) FROM {$table} o WHERE {$where_sql}";

		$prepared = $wpdb->prepare( $sql, $query_values ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $prepared );
	}

	/**
	 * Fetch upcoming occurrences starting from now.
	 *
	 * @param int $limit
	 * @param int|null $category_id
	 * @return array<object>
	 */
	public function get_upcoming( int $limit = 5, ?int $category_id = null ): array {
		$now_utc   = gmdate( 'Y-m-d H:i:s' );
		$limit_utc = gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) );

		$args = [
			'limit'       => $limit,
			'status'      => 'publish',
			'orderby'     => 'start_utc',
			'order'       => 'ASC',
			'category_id' => $category_id,
		];

		return $this->get_occurrences_for_range( $now_utc, $limit_utc, $args );
	}
}
