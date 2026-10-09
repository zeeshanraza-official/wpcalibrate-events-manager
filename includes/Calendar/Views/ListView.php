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
 * Renders List/Agenda calendar view with pagination and filters.
 */
class ListView {

	/**
	 * Render HTML for List view.
	 *
	 * @param string               $start_date
	 * @param int                  $page
	 * @param array<string, mixed> $filters
	 * @return string HTML output
	 */
	public static function render( string $start_date, int $page = 1, array $filters = [] ): string {
		$per_page = (int) SettingsManager::get( 'events_per_page', 10 );
		$page     = max( 1, $page );
		$offset   = ( $page - 1 ) * $per_page;

		$query = new CalendarQuery();
		$data  = $query->get_list_data( $start_date, $per_page, $offset, $filters );

		$tz          = DateTimeHandler::get_site_timezone();
		$total       = $data['total'];
		$total_pages = (int) ceil( $total / $per_page );
		$events      = $data['occurrences'];

		ob_start();
		?>
		<div class="wpcal-calendar-container wpcal-list-view" data-view="list" data-page="<?php echo esc_attr( (string) $page ); ?>">
			<!-- Filter & Search Bar -->
			<div class="wpcal-filter-bar">
				<form method="get" class="wpcal-filter-form" role="search">
					<div class="wpcal-filter-group">
						<label for="wpcal-search-input" class="screen-reader-text"><?php esc_html_e( 'Search Events', 'wpcalibrate-events-manager' ); ?></label>
						<input type="text" id="wpcal-search-input" name="wpcal_search" value="<?php echo esc_attr( $filters['search'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Search events...', 'wpcalibrate-events-manager' ); ?>">
					</div>
					<div class="wpcal-filter-group">
						<button type="submit" class="wpcal-btn wpcal-btn-primary"><?php esc_html_e( 'Search', 'wpcalibrate-events-manager' ); ?></button>
					</div>
				</form>
			</div>

			<!-- Events List -->
			<div class="wpcal-list-items" role="feed" aria-label="<?php esc_attr_e( 'Upcoming Events List', 'wpcalibrate-events-manager' ); ?>">
				<?php if ( empty( $events ) ) : ?>
					<div class="wpcal-empty-state">
						<p><?php esc_html_e( 'No upcoming events found.', 'wpcalibrate-events-manager' ); ?></p>
					</div>
				<?php else : ?>
					<?php foreach ( $events as $occ_item ) : ?>
						<?php
						$event_url = get_permalink( (int) $occ_item->event_id );
						$start_loc = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $occ_item->start_datetime_local, $tz );
						$date_str  = $start_loc ? DateTimeHandler::format_date( $start_loc ) : '';
						$time_str  = $occ_item->all_day
							? __( 'All Day', 'wpcalibrate-events-manager' )
							: ( $start_loc ? DateTimeHandler::format_time( $start_loc ) : '' );
						$status_cls = $occ_item->status !== 'publish' ? 'wpcal-status-' . sanitize_html_class( $occ_item->status ) : '';
						?>
						<article class="wpcal-list-item <?php echo esc_attr( $status_cls ); ?>" role="article">
							<div class="wpcal-item-date-col">
								<span class="wpcal-item-month"><?php echo esc_html( $start_loc ? $start_loc->format( 'M' ) : '' ); ?></span>
								<span class="wpcal-item-day"><?php echo esc_html( $start_loc ? $start_loc->format( 'j' ) : '' ); ?></span>
							</div>
							<div class="wpcal-item-details-col">
								<div class="wpcal-item-meta">
									<span class="wpcal-item-time"><?php echo esc_html( $date_str . ( $time_str ? ' @ ' . $time_str : '' ) ); ?></span>
									<?php if ( $occ_item->status === 'cancelled' ) : ?>
										<span class="wpcal-badge-cancelled"><?php esc_html_e( 'Cancelled', 'wpcalibrate-events-manager' ); ?></span>
									<?php elseif ( $occ_item->status === 'postponed' ) : ?>
										<span class="wpcal-badge-postponed"><?php esc_html_e( 'Postponed', 'wpcalibrate-events-manager' ); ?></span>
									<?php endif; ?>
								</div>
								<h3 class="wpcal-item-title">
									<a href="<?php echo esc_url( $event_url ); ?>"><?php echo esc_html( $occ_item->event_title ); ?></a>
								</h3>
								<?php if ( ! empty( $occ_item->event_excerpt ) ) : ?>
									<div class="wpcal-item-summary">
										<?php echo wp_kses_post( wpautop( $occ_item->event_excerpt ) ); ?>
									</div>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<!-- Pagination -->
			<?php if ( $total_pages > 1 ) : ?>
				<nav class="wpcal-pagination" role="navigation" aria-label="<?php esc_attr_e( 'Events Pagination', 'wpcalibrate-events-manager' ); ?>">
					<?php if ( $page > 1 ) : ?>
						<button type="button" class="wpcal-btn wpcal-btn-prev" data-page="<?php echo esc_attr( (string) ( $page - 1 ) ); ?>">
							&lsaquo; <?php esc_html_e( 'Previous', 'wpcalibrate-events-manager' ); ?>
						</button>
					<?php endif; ?>

					<span class="wpcal-page-info">
						<?php printf( esc_html__( 'Page %1$d of %2$d', 'wpcalibrate-events-manager' ), (int) $page, (int) $total_pages ); ?>
					</span>

					<?php if ( $page < $total_pages ) : ?>
						<button type="button" class="wpcal-btn wpcal-btn-next" data-page="<?php echo esc_attr( (string) ( $page + 1 ) ); ?>">
							<?php esc_html_e( 'Next', 'wpcalibrate-events-manager' ); ?> &rsaquo;
						</button>
					<?php endif; ?>
				</nav>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
