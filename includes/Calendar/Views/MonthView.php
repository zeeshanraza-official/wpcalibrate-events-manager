<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Calendar\Views;

use DateTimeImmutable;
use WPCalibrate\EventsManager\Calendar\CalendarQuery;
use WPCalibrate\EventsManager\Domain\DateTimeHandler;
use WPCalibrate\EventsManager\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders Month calendar view with accessible table markup.
 */
class MonthView {

	/**
	 * Render HTML for Month view.
	 *
	 * @param int                  $year
	 * @param int                  $month
	 * @param array<string, mixed> $filters
	 * @return string HTML output
	 */
	public static function render( int $year, int $month, array $filters = [] ): string {
		$query = new CalendarQuery();
		$data  = $query->get_month_data( $year, $month, $filters );

		$tz         = DateTimeHandler::get_site_timezone();
		$week_start = (int) SettingsManager::get( 'week_start', 1 );

		$first_day_of_month = new DateTimeImmutable( sprintf( '%04d-%02d-01', $year, $month ), $tz );
		$month_title        = DateTimeHandler::format_date( $first_day_of_month, 'F Y' );

		// Index occurrences by local date (Y-m-d)
		$events_by_date = [];
		foreach ( $data['occurrences'] as $occ ) {
			$date_key = substr( (string) $occ->start_datetime_local, 0, 10 );
			if ( ! isset( $events_by_date[ $date_key ] ) ) {
				$events_by_date[ $date_key ] = [];
			}
			$events_by_date[ $date_key ][] = $occ;
		}

		// Calculate weekday header names starting from week_start
		$day_labels_full  = [
			0 => __( 'Sunday', 'wpcalibrate-events-manager' ),
			1 => __( 'Monday', 'wpcalibrate-events-manager' ),
			2 => __( 'Tuesday', 'wpcalibrate-events-manager' ),
			3 => __( 'Wednesday', 'wpcalibrate-events-manager' ),
			4 => __( 'Thursday', 'wpcalibrate-events-manager' ),
			5 => __( 'Friday', 'wpcalibrate-events-manager' ),
			6 => __( 'Saturday', 'wpcalibrate-events-manager' ),
		];
		$day_labels_short = [
			0 => __( 'Sun', 'wpcalibrate-events-manager' ),
			1 => __( 'Mon', 'wpcalibrate-events-manager' ),
			2 => __( 'Tue', 'wpcalibrate-events-manager' ),
			3 => __( 'Wed', 'wpcalibrate-events-manager' ),
			4 => __( 'Thu', 'wpcalibrate-events-manager' ),
			5 => __( 'Fri', 'wpcalibrate-events-manager' ),
			6 => __( 'Sat', 'wpcalibrate-events-manager' ),
		];

		$ordered_days = [];
		for ( $i = 0; $i < 7; $i++ ) {
			$day_idx = ( $week_start + $i ) % 7;
			$ordered_days[] = [
				'index' => $day_idx,
				'short' => $day_labels_short[ $day_idx ],
				'full'  => $day_labels_full[ $day_idx ],
			];
		}

		// Prev / Next month calculations
		$prev_dt = $first_day_of_month->modify( '-1 month' );
		$next_dt = $first_day_of_month->modify( '+1 month' );

		$prev_year  = (int) $prev_dt->format( 'Y' );
		$prev_month = (int) $prev_dt->format( 'n' );
		$next_year  = (int) $next_dt->format( 'Y' );
		$next_month = (int) $next_dt->format( 'n' );

		$today = current_time( 'Y-m-d' );

		ob_start();
		?>
		<div class="wpcal-calendar-container wpcal-month-view" data-view="month" data-year="<?php echo esc_attr( (string) $year ); ?>" data-month="<?php echo esc_attr( (string) $month ); ?>">
			<!-- Header Navigation -->
			<header class="wpcal-nav-bar" role="toolbar" aria-label="<?php esc_attr_e( 'Calendar Navigation', 'wpcalibrate-events-manager' ); ?>">
				<div class="wpcal-nav-actions">
					<button type="button" class="wpcal-btn wpcal-btn-prev" data-year="<?php echo esc_attr( (string) $prev_year ); ?>" data-month="<?php echo esc_attr( (string) $prev_month ); ?>" aria-label="<?php esc_attr_e( 'Previous Month', 'wpcalibrate-events-manager' ); ?>">
						<span aria-hidden="true">&lsaquo;</span> <?php esc_html_e( 'Prev', 'wpcalibrate-events-manager' ); ?>
					</button>
					<button type="button" class="wpcal-btn wpcal-btn-today" data-year="<?php echo esc_attr( gmdate( 'Y' ) ); ?>" data-month="<?php echo esc_attr( gmdate( 'n' ) ); ?>" aria-label="<?php esc_attr_e( 'Current Month', 'wpcalibrate-events-manager' ); ?>">
						<?php esc_html_e( 'Today', 'wpcalibrate-events-manager' ); ?>
					</button>
					<button type="button" class="wpcal-btn wpcal-btn-next" data-year="<?php echo esc_attr( (string) $next_year ); ?>" data-month="<?php echo esc_attr( (string) $next_month ); ?>" aria-label="<?php esc_attr_e( 'Next Month', 'wpcalibrate-events-manager' ); ?>">
						<?php esc_html_e( 'Next', 'wpcalibrate-events-manager' ); ?> <span aria-hidden="true">&rsaquo;</span>
					</button>
				</div>
				<h2 class="wpcal-month-title" aria-live="polite"><?php echo esc_html( $month_title ); ?></h2>
			</header>

			<!-- Month Grid -->
			<table class="wpcal-month-table" role="grid" aria-label="<?php echo esc_attr( $month_title ); ?>">
				<thead>
					<tr role="row">
						<?php foreach ( $ordered_days as $d ) : ?>
							<th role="columnheader" scope="col" aria-label="<?php echo esc_attr( $d['full'] ); ?>">
								<span class="wpcal-th-short" aria-hidden="true"><?php echo esc_html( $d['short'] ); ?></span>
								<span class="screen-reader-text"><?php echo esc_html( $d['full'] ); ?></span>
							</th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php
					$curr_cursor = new DateTimeImmutable( $data['grid_start'] . ' 00:00:00', $tz );
					$grid_end_dt = new DateTimeImmutable( $data['grid_end'] . ' 23:59:59', $tz );

					while ( $curr_cursor <= $grid_end_dt ) {
						echo '<tr role="row">';
						for ( $col = 0; $col < 7; $col++ ) {
							$curr_str     = $curr_cursor->format( 'Y-m-d' );
							$is_curr_m    = (int) $curr_cursor->format( 'n' ) === $month;
							$is_today     = $curr_str === $today;
							$day_num      = $curr_cursor->format( 'j' );
							$day_events   = $events_by_date[ $curr_str ] ?? [];
							$cell_classes = [ 'wpcal-day-cell' ];

							if ( ! $is_curr_m ) {
								$cell_classes[] = 'wpcal-other-month';
							}
							if ( $is_today ) {
								$cell_classes[] = 'wpcal-today';
							}
							if ( ! empty( $day_events ) ) {
								$cell_classes[] = 'wpcal-has-events';
							}

							$cell_label = sprintf(
								/* translators: 1: Formatted date, 2: Number of events */
								__( '%1$s, %2$d events', 'wpcalibrate-events-manager' ),
								DateTimeHandler::format_date( $curr_cursor ),
								count( $day_events )
							);
							?>
							<td role="gridcell" class="<?php echo esc_attr( implode( ' ', $cell_classes ) ); ?>" aria-label="<?php echo esc_attr( $cell_label ); ?>">
								<div class="wpcal-day-header">
									<span class="wpcal-day-number"><?php echo esc_html( $day_num ); ?></span>
								</div>
								<div class="wpcal-day-events" role="list">
									<?php foreach ( $day_events as $occ_item ) : ?>
										<?php
										$event_url   = get_permalink( (int) $occ_item->event_id );
										$status_cls  = $occ_item->status !== 'publish' ? 'wpcal-status-' . sanitize_html_class( $occ_item->status ) : '';
										$time_label  = $occ_item->all_day
											? __( 'All day', 'wpcalibrate-events-manager' )
											: DateTimeHandler::format_time( DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $occ_item->start_datetime_local, $tz ) ?: $curr_cursor );
										?>
										<div class="wpcal-event-chip <?php echo esc_attr( $status_cls ); ?>" role="listitem">
											<a href="<?php echo esc_url( $event_url ); ?>" title="<?php echo esc_attr( $occ_item->event_title ); ?>">
												<span class="wpcal-event-time"><?php echo esc_html( $time_label ); ?></span>
												<span class="wpcal-event-title"><?php echo esc_html( $occ_item->event_title ); ?></span>
												<?php if ( $occ_item->status === 'cancelled' ) : ?>
													<span class="wpcal-badge-cancelled"><?php esc_html_e( '(Cancelled)', 'wpcalibrate-events-manager' ); ?></span>
												<?php endif; ?>
											</a>
										</div>
									<?php endforeach; ?>
								</div>
							</td>
							<?php
							$curr_cursor = $curr_cursor->modify( '+1 day' );
						}
						echo '</tr>';
					}
					?>
				</tbody>
			</table>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
