<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Calendar;

use WPCalibrate\EventsManager\Calendar\Views\DayView;
use WPCalibrate\EventsManager\Calendar\Views\ListView;
use WPCalibrate\EventsManager\Calendar\Views\MonthView;
use WPCalibrate\EventsManager\Calendar\Views\WeekView;
use WPCalibrate\EventsManager\Occurrences\OccurrenceRepository;
use WPCalibrate\EventsManager\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages shortcode registrations and rendering for calendars and upcoming events.
 */
class Shortcodes {

	/**
	 * Register shortcodes.
	 */
	public function register_hooks(): void {
		add_shortcode( 'wpcalibrate_calendar', [ $this, 'render_calendar_shortcode' ] );
		add_shortcode( 'wpcalibrate_upcoming_events', [ $this, 'render_upcoming_shortcode' ] );
	}

	/**
	 * Main calendar shortcode [wpcalibrate_calendar].
	 *
	 * @param array<string, mixed>|string $atts
	 * @return string
	 */
	public function render_calendar_shortcode( array|string $atts = [] ): string {
		wp_enqueue_style( 'wpcal-calendar-css' );
		wp_enqueue_script( 'wpcal-calendar-js' );

		$defaults = [
			'view'     => SettingsManager::get( 'default_view', 'month' ),
			'category' => null,
			'venue'    => null,
			'year'     => (int) current_time( 'Y' ),
			'month'    => (int) current_time( 'n' ),
			'date'     => current_time( 'Y-m-d' ),
		];
		$params = shortcode_atts( $defaults, is_array( $atts ) ? $atts : [], 'wpcalibrate_calendar' );

		$filters = [
			'status'      => 'publish',
			'category_id' => ! empty( $params['category'] ) ? (int) $params['category'] : null,
			'venue_id'    => ! empty( $params['venue'] ) ? (int) $params['venue'] : null,
		];

		return self::render_calendar_markup( (string) $params['view'], (int) $params['year'], (int) $params['month'], (string) $params['date'], $filters );
	}

	/**
	 * Render calendar markup with view switcher toolbar.
	 *
	 * @param string               $view
	 * @param int                  $year
	 * @param int                  $month
	 * @param string               $date
	 * @param array<string, mixed> $filters
	 * @return string
	 */
	public static function render_calendar_markup( string $view, int $year, int $month, string $date, array $filters = [] ): string {
		$enabled_views = (array) SettingsManager::get( 'enabled_views', [ 'month', 'week', 'day', 'list' ] );

		ob_start();
		?>
		<div class="wpcal-main-calendar-app" data-current-view="<?php echo esc_attr( $view ); ?>">
			<!-- View Switcher -->
			<?php if ( count( $enabled_views ) > 1 ) : ?>
				<div class="wpcal-view-switcher" role="radiogroup" aria-label="<?php esc_attr_e( 'Choose Calendar View', 'wpcalibrate-events-manager' ); ?>">
					<?php
					$view_names = [
						'month' => __( 'Month', 'wpcalibrate-events-manager' ),
						'week'  => __( 'Week', 'wpcalibrate-events-manager' ),
						'day'   => __( 'Day', 'wpcalibrate-events-manager' ),
						'list'  => __( 'List', 'wpcalibrate-events-manager' ),
					];
					foreach ( $enabled_views as $v_key ) {
						if ( ! isset( $view_names[ $v_key ] ) ) {
							continue;
						}
						$is_active = $v_key === $view;
						?>
						<button type="button" class="wpcal-view-btn <?php echo $is_active ? 'active' : ''; ?>" data-view="<?php echo esc_attr( $v_key ); ?>" role="radio" aria-checked="<?php echo $is_active ? 'true' : 'false'; ?>">
							<?php echo esc_html( $view_names[ $v_key ] ); ?>
						</button>
						<?php
					}
					?>
				</div>
			<?php endif; ?>

			<!-- Calendar View Content Container -->
			<div class="wpcal-calendar-view-stage">
				<?php
				echo match ( $view ) {
					'week'  => WeekView::render( $date, $filters ),
					'day'   => DayView::render( $date, $filters ),
					'list'  => ListView::render( $date, 1, $filters ),
					default => MonthView::render( $year, $month, $filters ),
				};
				?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Upcoming events list shortcode [wpcalibrate_upcoming_events].
	 *
	 * @param array<string, mixed>|string $atts
	 * @return string
	 */
	public function render_upcoming_shortcode( array|string $atts = [] ): string {
		wp_enqueue_style( 'wpcal-calendar-css' );

		$defaults = [
			'limit'    => 5,
			'category' => null,
		];
		$params = shortcode_atts( $defaults, is_array( $atts ) ? $atts : [], 'wpcalibrate_upcoming_events' );

		$limit   = max( 1, min( 20, (int) $params['limit'] ) );
		$cat_id  = ! empty( $params['category'] ) ? (int) $params['category'] : null;

		$repo   = new OccurrenceRepository();
		$events = $repo->get_upcoming( $limit, $cat_id );

		ob_start();
		?>
		<div class="wpcal-upcoming-widget">
			<?php if ( empty( $events ) ) : ?>
				<p class="wpcal-empty-msg"><?php esc_html_e( 'No upcoming events at this time.', 'wpcalibrate-events-manager' ); ?></p>
			<?php else : ?>
				<ul class="wpcal-upcoming-list">
					<?php foreach ( $events as $occ ) : ?>
						<?php
						$start_time = strtotime( $occ->start_datetime_local );
						$date_fmt   = date_i18n( get_option( 'date_format', 'F j' ), $start_time );
						$time_fmt   = $occ->all_day ? __( 'All day', 'wpcalibrate-events-manager' ) : date_i18n( get_option( 'time_format', 'g:i a' ), $start_time );
						?>
						<li class="wpcal-upcoming-item">
							<a href="<?php echo esc_url( get_permalink( (int) $occ->event_id ) ); ?>" class="wpcal-upcoming-link">
								<span class="wpcal-upcoming-date"><?php echo esc_html( $date_fmt ); ?></span>
								<strong class="wpcal-upcoming-title"><?php echo esc_html( $occ->event_title ); ?></strong>
								<span class="wpcal-upcoming-time"><?php echo esc_html( $time_fmt ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
