<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Admin;

use WPCalibrate\EventsManager\Events\EventPostType;
use WPCalibrate\EventsManager\Events\EventTaxonomies;
use WPCalibrate\EventsManager\Recurrence\RecurrenceRule;
use WPCalibrate\EventsManager\Venues\VenuePostType;
use WPCalibrate\EventsManager\Organizers\OrganizerPostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Customizes admin list table columns and filters for Events.
 */
class EventListTable {

	/**
	 * Register hooks.
	 */
	public function register_hooks(): void {
		add_filter( 'manage_' . EventPostType::POST_TYPE . '_posts_columns', [ $this, 'set_columns' ] );
		add_action( 'manage_' . EventPostType::POST_TYPE . '_posts_custom_column', [ $this, 'render_column_content' ], 10, 2 );
		add_action( 'restrict_manage_posts', [ $this, 'add_filters' ] );
		add_filter( 'parse_query', [ $this, 'filter_query' ] );
	}

	/**
	 * Set custom columns for Event list.
	 *
	 * @param array<string, string> $columns
	 * @return array<string, string>
	 */
	public function set_columns( array $columns ): array {
		$new_columns = [];
		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;
			if ( $key === 'title' ) {
				$new_columns['wpcal_datetime']   = __( 'Event Date & Time', 'wpcalibrate-events-manager' );
				$new_columns['wpcal_recurrence'] = __( 'Recurrence', 'wpcalibrate-events-manager' );
				$new_columns['wpcal_venue']      = __( 'Venue / Type', 'wpcalibrate-events-manager' );
				$new_columns['wpcal_categories'] = __( 'Categories', 'wpcalibrate-events-manager' );
				$new_columns['wpcal_status']     = __( 'Status', 'wpcalibrate-events-manager' );
			}
		}
		return $new_columns;
	}

	/**
	 * Render custom column content.
	 *
	 * @param string $column
	 * @param int    $post_id
	 */
	public function render_column_content( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'wpcal_datetime':
				$start_date = get_post_meta( $post_id, '_wpcal_start_date', true );
				$start_time = get_post_meta( $post_id, '_wpcal_start_time', true );
				$all_day    = (bool) get_post_meta( $post_id, '_wpcal_all_day', true );

				if ( ! $start_date ) {
					echo '—';
					break;
				}

				if ( $all_day ) {
					printf( '<strong>%s</strong> <span class="badge" style="background:#e0e0e0; padding:2px 6px; border-radius:3px; font-size:11px;">%s</span>', esc_html( $start_date ), esc_html__( 'All Day', 'wpcalibrate-events-manager' ) );
				} else {
					printf( '<strong>%s</strong> @ %s', esc_html( $start_date ), esc_html( $start_time ) );
				}
				break;

			case 'wpcal_recurrence':
				$rule = RecurrenceRule::from_post_id( $post_id );
				echo esc_html( $rule->get_human_summary() );
				break;

			case 'wpcal_venue':
				$event_type = get_post_meta( $post_id, '_wpcal_event_type', true );
				$venue_id   = (int) get_post_meta( $post_id, '_wpcal_venue_id', true );

				if ( $venue_id ) {
					$venue_title = get_the_title( $venue_id );
					echo esc_html( $venue_title );
				} elseif ( $event_type === 'online' ) {
					echo '<em>' . esc_html__( 'Online Event', 'wpcalibrate-events-manager' ) . '</em>';
				} else {
					echo '—';
				}
				break;

			case 'wpcal_categories':
				$terms = get_the_term_list( $post_id, EventTaxonomies::TAXONOMY_CATEGORY, '', ', ' );
				echo ! empty( $terms ) && ! is_wp_error( $terms ) ? wp_kses_post( $terms ) : '—';
				break;

			case 'wpcal_status':
				$status = get_post_meta( $post_id, '_wpcal_status', true ) ?: 'publish';
				if ( $status === 'cancelled' ) {
					echo '<span style="color:#d63638; font-weight:600;">' . esc_html__( 'Cancelled', 'wpcalibrate-events-manager' ) . '</span>';
				} elseif ( $status === 'postponed' ) {
					echo '<span style="color:#dba617; font-weight:600;">' . esc_html__( 'Postponed', 'wpcalibrate-events-manager' ) . '</span>';
				} else {
					echo '<span style="color:#00a32a;">' . esc_html__( 'Active', 'wpcalibrate-events-manager' ) . '</span>';
				}
				break;
		}
	}

	/**
	 * Add dropdown filter controls above post list.
	 *
	 * @param string $post_type
	 */
	public function add_filters( string $post_type ): void {
		if ( $post_type !== EventPostType::POST_TYPE ) {
			return;
		}

		// Time range filter (Upcoming vs Past)
		$selected_time = isset( $_GET['wpcal_time_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['wpcal_time_filter'] ) ) : '';
		?>
		<select name="wpcal_time_filter">
			<option value=""><?php esc_html_e( 'All Dates', 'wpcalibrate-events-manager' ); ?></option>
			<option value="upcoming" <?php selected( $selected_time, 'upcoming' ); ?>><?php esc_html_e( 'Upcoming Events', 'wpcalibrate-events-manager' ); ?></option>
			<option value="past" <?php selected( $selected_time, 'past' ); ?>><?php esc_html_e( 'Past Events', 'wpcalibrate-events-manager' ); ?></option>
		</select>
		<?php

		// Event Status filter
		$selected_status = isset( $_GET['wpcal_status_filter'] ) ? sanitize_text_field( wp_unslash( $_GET['wpcal_status_filter'] ) ) : '';
		?>
		<select name="wpcal_status_filter">
			<option value=""><?php esc_html_e( 'All Statuses', 'wpcalibrate-events-manager' ); ?></option>
			<option value="publish" <?php selected( $selected_status, 'publish' ); ?>><?php esc_html_e( 'Active', 'wpcalibrate-events-manager' ); ?></option>
			<option value="cancelled" <?php selected( $selected_status, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'wpcalibrate-events-manager' ); ?></option>
			<option value="postponed" <?php selected( $selected_status, 'postponed' ); ?>><?php esc_html_e( 'Postponed', 'wpcalibrate-events-manager' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Filter WP_Query based on custom filters.
	 *
	 * @param \WP_Query $query
	 */
	public function filter_query( \WP_Query $query ): void {
		global $pagenow;

		if ( ! is_admin() || $pagenow !== 'edit.php' || ! $query->is_main_query() ) {
			return;
		}

		if ( $query->get( 'post_type' ) !== EventPostType::POST_TYPE ) {
			return;
		}

		$meta_query = $query->get( 'meta_query' ) ?: [];

		if ( ! empty( $_GET['wpcal_time_filter'] ) ) {
			$today = current_time( 'Y-m-d' );
			if ( $_GET['wpcal_time_filter'] === 'upcoming' ) {
				$meta_query[] = [
					'key'     => '_wpcal_start_date',
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				];
			} elseif ( $_GET['wpcal_time_filter'] === 'past' ) {
				$meta_query[] = [
					'key'     => '_wpcal_start_date',
					'value'   => $today,
					'compare' => '<',
					'type'    => 'DATE',
				];
			}
		}

		if ( ! empty( $_GET['wpcal_status_filter'] ) ) {
			$meta_query[] = [
				'key'     => '_wpcal_status',
				'value'   => sanitize_text_field( wp_unslash( $_GET['wpcal_status_filter'] ) ),
				'compare' => '=',
			];
		}

		if ( ! empty( $meta_query ) ) {
			$query->set( 'meta_query', $meta_query );
		}
	}
}
