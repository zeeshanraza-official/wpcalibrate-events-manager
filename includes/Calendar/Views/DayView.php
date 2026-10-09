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
 * Renders Day calendar view.
 */
class DayView {

	/**
	 * Render HTML for Day view.
	 *
	 * @param string               $date Y-m-d
	 * @param array<string, mixed> $filters
	 * @return string HTML output
	 */
	public static function render( string $date, array $filters = [] ): string {
		$query = new CalendarQuery();
		$events = $query->get_day_data( $date, $filters );

		$tz      = DateTimeHandler::get_site_timezone();
		$day_dt  = DateTimeHandler::create( $date, '00:00:00', $tz );
		$day_title = DateTimeHandler::format_date( $day_dt, 'l, F j, Y' );

		$prev_day = $day_dt->modify( '-1 day' )->format( 'Y-m-d' );
		$next_day = $day_dt->modify( '+1 day' )->format( 'Y-m-d' );
		$today    = current_time( 'Y-m-d' );

		ob_start();
		?>
		<div class="wpcal-calendar-container wpcal-day-view" data-view="day" data-date="<?php echo esc_attr( $date ); ?>">
			<!-- Header Navigation -->
			<header class="wpcal-nav-bar" role="toolbar" aria-label="<?php esc_attr_e( 'Day Navigation', 'wpcalibrate-events-manager' ); ?>">
				<div class="wpcal-nav-actions">
					<button type="button" class="wpcal-btn wpcal-btn-prev" data-date="<?php echo esc_attr( $prev_day ); ?>" aria-label="<?php esc_attr_e( 'Previous Day', 'wpcalibrate-events-manager' ); ?>">
						<span aria-hidden="true">&lsaquo;</span> <?php esc_html_e( 'Prev', 'wpcalibrate-events-manager' ); ?>
					</button>
					<button type="button" class="wpcal-btn wpcal-btn-today" data-date="<?php echo esc_attr( $today ); ?>" aria-label="<?php esc_attr_e( 'Today', 'wpcalibrate-events-manager' ); ?>">
						<?php esc_html_e( 'Today', 'wpcalibrate-events-manager' ); ?>
					</button>
					<button type="button" class="wpcal-btn wpcal-btn-next" data-date="<?php echo esc_attr( $next_day ); ?>" aria-label="<?php esc_attr_e( 'Next Day', 'wpcalibrate-events-manager' ); ?>">
						<?php esc_html_e( 'Next', 'wpcalibrate-events-manager' ); ?> <span aria-hidden="true">&rsaquo;</span>
					</button>
				</div>
				<h2 class="wpcal-day-title" aria-live="polite"><?php echo esc_html( $day_title ); ?></h2>
			</header>

			<!-- Day Schedule List -->
			<div class="wpcal-day-schedule" role="list">
				<?php if ( empty( $events ) ) : ?>
					<div class="wpcal-empty-state">
						<p><?php esc_html_e( 'No events scheduled for this day.', 'wpcalibrate-events-manager' ); ?></p>
					</div>
				<?php else : ?>
					<?php foreach ( $events as $occ_item ) : ?>
						<?php
						$event_url  = get_permalink( (int) $occ_item->event_id );
						$start_loc  = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $occ_item->start_datetime_local, $tz ) ?: $day_dt;
						$end_loc    = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $occ_item->end_datetime_local, $tz ) ?: $day_dt;
						$time_str   = $occ_item->all_day
							? __( 'All Day', 'wpcalibrate-events-manager' )
							: sprintf( '%s – %s', DateTimeHandler::format_time( $start_loc ), DateTimeHandler::format_time( $end_loc ) );
						$status_cls = $occ_item->status !== 'publish' ? 'wpcal-status-' . sanitize_html_class( $occ_item->status ) : '';
						?>
						<article class="wpcal-day-item <?php echo esc_attr( $status_cls ); ?>" role="listitem">
							<div class="wpcal-day-time-badge">
								<span class="wpcal-time-text"><?php echo esc_html( $time_str ); ?></span>
							</div>
							<div class="wpcal-day-content">
								<h3 class="wpcal-event-heading">
									<a href="<?php echo esc_url( $event_url ); ?>"><?php echo esc_html( $occ_item->event_title ); ?></a>
								</h3>
								<?php if ( ! empty( $occ_item->event_excerpt ) ) : ?>
									<div class="wpcal-event-summary">
										<?php echo wp_kses_post( wpautop( $occ_item->event_excerpt ) ); ?>
									</div>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
