<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Calendar\Views;

use DateTimeImmutable;
use WPCalibrate\EventsManager\Calendar\CalendarQuery;
use WPCalibrate\EventsManager\Domain\DateTimeHandler;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders Week calendar view.
 */
class WeekView {

	/**
	 * Render HTML for Week view.
	 *
	 * @param string               $date Y-m-d within target week
	 * @param array<string, mixed> $filters
	 * @return string HTML output
	 */
	public static function render( string $date, array $filters = [] ): string {
		$query = new CalendarQuery();
		$data  = $query->get_week_data( $date, $filters );

		$tz       = DateTimeHandler::get_site_timezone();
		$start_dt = new DateTimeImmutable( $data['week_start'] . ' 00:00:00', $tz );
		$end_dt   = new DateTimeImmutable( $data['week_end'] . ' 23:59:59', $tz );

		$week_title = sprintf(
			/* translators: 1: Week start date, 2: Week end date */
			__( 'Week of %1$s – %2$s', 'wpcalibrate-events-manager' ),
			DateTimeHandler::format_date( $start_dt, 'M j' ),
			DateTimeHandler::format_date( $end_dt, 'M j, Y' )
		);

		// Group occurrences by date
		$events_by_date = [];
		foreach ( $data['occurrences'] as $occ ) {
			$date_key = substr( (string) $occ->start_datetime_local, 0, 10 );
			if ( ! isset( $events_by_date[ $date_key ] ) ) {
				$events_by_date[ $date_key ] = [];
			}
			$events_by_date[ $date_key ][] = $occ;
		}

		$prev_week = $start_dt->modify( '-7 days' )->format( 'Y-m-d' );
		$next_week = $start_dt->modify( '+7 days' )->format( 'Y-m-d' );
		$today     = current_time( 'Y-m-d' );

		ob_start();
		?>
		<div class="wpcal-calendar-container wpcal-week-view" data-view="week" data-date="<?php echo esc_attr( $date ); ?>">
			<!-- Header Navigation -->
			<header class="wpcal-nav-bar" role="toolbar" aria-label="<?php esc_attr_e( 'Week Navigation', 'wpcalibrate-events-manager' ); ?>">
				<div class="wpcal-nav-actions">
					<button type="button" class="wpcal-btn wpcal-btn-prev" data-date="<?php echo esc_attr( $prev_week ); ?>" aria-label="<?php esc_attr_e( 'Previous Week', 'wpcalibrate-events-manager' ); ?>">
						<span aria-hidden="true">&lsaquo;</span> <?php esc_html_e( 'Prev', 'wpcalibrate-events-manager' ); ?>
					</button>
					<button type="button" class="wpcal-btn wpcal-btn-today" data-date="<?php echo esc_attr( $today ); ?>" aria-label="<?php esc_attr_e( 'Current Week', 'wpcalibrate-events-manager' ); ?>">
						<?php esc_html_e( 'Today', 'wpcalibrate-events-manager' ); ?>
					</button>
					<button type="button" class="wpcal-btn wpcal-btn-next" data-date="<?php echo esc_attr( $next_week ); ?>" aria-label="<?php esc_attr_e( 'Next Week', 'wpcalibrate-events-manager' ); ?>">
						<?php esc_html_e( 'Next', 'wpcalibrate-events-manager' ); ?> <span aria-hidden="true">&rsaquo;</span>
					</button>
				</div>
				<h2 class="wpcal-week-title" aria-live="polite"><?php echo esc_html( $week_title ); ?></h2>
			</header>

			<!-- 7 Day Columns -->
			<div class="wpcal-week-columns" role="region" aria-label="<?php esc_attr_e( 'Days of the week', 'wpcalibrate-events-manager' ); ?>">
				<?php
				$curr = $start_dt;
				for ( $i = 0; $i < 7; $i++ ) {
					$curr_str   = $curr->format( 'Y-m-d' );
					$is_today   = $curr_str === $today;
					$day_events = $events_by_date[ $curr_str ] ?? [];
					?>
					<div class="wpcal-week-col <?php echo $is_today ? 'wpcal-today' : ''; ?>">
						<div class="wpcal-week-col-header">
							<span class="wpcal-week-wday"><?php echo esc_html( DateTimeHandler::format_date( $curr, 'D' ) ); ?></span>
							<span class="wpcal-week-daynum"><?php echo esc_html( $curr->format( 'j' ) ); ?></span>
						</div>
						<div class="wpcal-week-col-events" role="list">
							<?php if ( empty( $day_events ) ) : ?>
								<div class="wpcal-empty-day" aria-hidden="true">·</div>
							<?php else : ?>
								<?php foreach ( $day_events as $occ_item ) : ?>
									<?php
									$event_url  = get_permalink( (int) $occ_item->event_id );
									$status_cls = $occ_item->status !== 'publish' ? 'wpcal-status-' . sanitize_html_class( $occ_item->status ) : '';
									$time_label = $occ_item->all_day
										? __( 'All day', 'wpcalibrate-events-manager' )
										: DateTimeHandler::format_time( DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $occ_item->start_datetime_local, $tz ) ?: $curr );
									?>
									<div class="wpcal-card-mini <?php echo esc_attr( $status_cls ); ?>" role="listitem">
										<a href="<?php echo esc_url( $event_url ); ?>">
											<span class="wpcal-card-time"><?php echo esc_html( $time_label ); ?></span>
											<strong class="wpcal-card-title"><?php echo esc_html( $occ_item->event_title ); ?></strong>
										</a>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</div>
					<?php
					$curr = $curr->modify( '+1 day' );
				}
				?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
