<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Events;

use DateTimeImmutable;
use WPCalibrate\EventsManager\Domain\DateTimeHandler;
use WPCalibrate\EventsManager\ImportExport\IcsExporter;
use WPCalibrate\EventsManager\Maps\OpenStreetMapProvider;
use WPCalibrate\EventsManager\Recurrence\RecurrenceRule;
use WPCalibrate\EventsManager\Settings\SettingsManager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders structured single event details and actions seamlessly in any WordPress theme.
 */
class SingleEventRenderer {

	/**
	 * Register template hooks.
	 */
	public function register_hooks(): void {
		add_filter( 'the_content', [ $this, 'filter_event_content' ] );
		add_action( 'wp_head', [ EventSchema::class, 'output_schema_head' ] );
		add_action( 'template_redirect', [ $this, 'handle_ics_export_download' ] );
	}

	/**
	 * Append event details panels to single event content.
	 *
	 * @param string $content
	 * @return string
	 */
	public function filter_event_content( string $content ): string {
		if ( ! is_singular( EventPostType::POST_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$event_id = get_the_ID();
		if ( ! $event_id ) {
			return $content;
		}

		$details_html = self::render_event_details( $event_id );
		return $details_html . $content;
	}

	/**
	 * Handle single event .ics export download request.
	 */
	public function handle_ics_export_download(): void {
		if ( isset( $_GET['wpcal_export_ics'] ) ) {
			$event_id = absint( $_GET['wpcal_export_ics'] );
			if ( $event_id && get_post_type( $event_id ) === EventPostType::POST_TYPE ) {
				$ics = IcsExporter::generate_single_ics( $event_id );
				$slug = sanitize_title( get_the_title( $event_id ) ) ?: 'event';

				header( 'Content-Type: text/calendar; charset=utf-8' );
				header( 'Content-Disposition: attachment; filename="' . $slug . '.ics"' );
				echo $ics; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				exit;
			}
		}

		if ( isset( $_GET['wpcal_feed'] ) && $_GET['wpcal_feed'] === 'ical' ) {
			$feed_ics = IcsExporter::generate_feed_ics();
			header( 'Content-Type: text/calendar; charset=utf-8' );
			header( 'Content-Disposition: inline; filename="wpcalibrate-events.ics"' );
			echo $feed_ics; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}
	}

	/**
	 * Render full event details block.
	 *
	 * @param int $event_id
	 * @return string
	 */
	public static function render_event_details( int $event_id ): string {
		$start_date = (string) get_post_meta( $event_id, '_wpcal_start_date', true );
		$start_time = (string) get_post_meta( $event_id, '_wpcal_start_time', true ) ?: '09:00';
		$end_date   = (string) get_post_meta( $event_id, '_wpcal_end_date', true ) ?: $start_date;
		$end_time   = (string) get_post_meta( $event_id, '_wpcal_end_time', true ) ?: '10:00';
		$all_day    = (bool) get_post_meta( $event_id, '_wpcal_all_day', true );
		$tz_string  = (string) get_post_meta( $event_id, '_wpcal_timezone', true );
		$status     = (string) get_post_meta( $event_id, '_wpcal_status', true ) ?: 'publish';

		$event_type = (string) get_post_meta( $event_id, '_wpcal_event_type', true ) ?: 'physical';
		$venue_id   = (int) get_post_meta( $event_id, '_wpcal_venue_id', true );
		$online_url = (string) get_post_meta( $event_id, '_wpcal_online_url', true );
		$org_ids    = (array) get_post_meta( $event_id, '_wpcal_organizer_ids', true );

		$cost       = (string) get_post_meta( $event_id, '_wpcal_cost', true );
		$capacity   = (string) get_post_meta( $event_id, '_wpcal_capacity', true );
		$access     = (string) get_post_meta( $event_id, '_wpcal_accessibility_notes', true );

		$rule       = RecurrenceRule::from_post_id( $event_id );
		$tz         = DateTimeHandler::resolve_timezone( $tz_string );

		$show_venue = (bool) SettingsManager::get( 'show_single_venue', 1 );
		$show_org   = (bool) SettingsManager::get( 'show_single_organizer', 1 );
		$show_map   = (bool) SettingsManager::get( 'show_single_map', 1 );
		$show_ical  = (bool) SettingsManager::get( 'show_single_ical', 1 );
		$show_gcal  = (bool) SettingsManager::get( 'show_single_gcal', 1 );
		$enable_osm = (bool) SettingsManager::get( 'enable_osm', 1 );

		// Date formatting
		$start_dt = DateTimeHandler::create( $start_date, $start_time, $tz );
		$end_dt   = DateTimeHandler::create( $end_date, $end_time, $tz );

		$date_display = $all_day
			? ( $start_date === $end_date ? DateTimeHandler::format_date( $start_dt ) : sprintf( '%s – %s', DateTimeHandler::format_date( $start_dt ), DateTimeHandler::format_date( $end_dt ) ) )
			: ( $start_date === $end_date
				? sprintf( '%s @ %s – %s', DateTimeHandler::format_date( $start_dt ), DateTimeHandler::format_time( $start_dt ), DateTimeHandler::format_time( $end_dt ) )
				: sprintf( '%s – %s', DateTimeHandler::format_full( $start_dt ), DateTimeHandler::format_full( $end_dt ) )
			);

		$gcal_url  = IcsExporter::get_google_calendar_url( $event_id );
		$ics_url   = add_query_arg( 'wpcal_export_ics', $event_id, get_permalink( $event_id ) );

		ob_start();
		?>
		<div class="wpcal-single-event-card">
			<!-- Prominent Status Banner -->
			<?php if ( $status === 'cancelled' ) : ?>
				<div class="wpcal-alert wpcal-alert-cancelled" role="alert">
					<strong><?php esc_html_e( 'Event Cancelled:', 'wpcalibrate-events-manager' ); ?></strong>
					<?php esc_html_e( 'This event has been cancelled and will not take place.', 'wpcalibrate-events-manager' ); ?>
				</div>
			<?php elseif ( $status === 'postponed' ) : ?>
				<div class="wpcal-alert wpcal-alert-postponed" role="alert">
					<strong><?php esc_html_e( 'Event Postponed:', 'wpcalibrate-events-manager' ); ?></strong>
					<?php esc_html_e( 'This event has been postponed. Please check back for updated dates.', 'wpcalibrate-events-manager' ); ?>
				</div>
			<?php endif; ?>

			<!-- Meta Overview Grid -->
			<div class="wpcal-meta-grid">
				<div class="wpcal-meta-item">
					<div class="wpcal-meta-icon" aria-hidden="true">&#128197;</div>
					<div class="wpcal-meta-body">
						<span class="wpcal-meta-label"><?php esc_html_e( 'Date & Time', 'wpcalibrate-events-manager' ); ?></span>
						<strong class="wpcal-meta-val"><?php echo esc_html( $date_display ); ?></strong>
						<span class="wpcal-meta-sub">
							<?php if ( $all_day ) : ?>
								<span class="wpcal-badge"><?php esc_html_e( 'All Day', 'wpcalibrate-events-manager' ); ?></span>
							<?php endif; ?>
							<?php printf( esc_html__( 'Timezone: %s', 'wpcalibrate-events-manager' ), esc_html( $tz->getName() ) ); ?>
						</span>
					</div>
				</div>

				<?php if ( $rule->is_recurring() ) : ?>
					<div class="wpcal-meta-item">
						<div class="wpcal-meta-icon" aria-hidden="true">&#128260;</div>
						<div class="wpcal-meta-body">
							<span class="wpcal-meta-label"><?php esc_html_e( 'Recurrence', 'wpcalibrate-events-manager' ); ?></span>
							<strong class="wpcal-meta-val"><?php echo esc_html( $rule->get_human_summary() ); ?></strong>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $cost ) ) : ?>
					<div class="wpcal-meta-item">
						<div class="wpcal-meta-icon" aria-hidden="true">&#127991;</div>
						<div class="wpcal-meta-body">
							<span class="wpcal-meta-label"><?php esc_html_e( 'Cost', 'wpcalibrate-events-manager' ); ?></span>
							<strong class="wpcal-meta-val"><?php echo esc_html( $cost ); ?></strong>
						</div>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $capacity ) ) : ?>
					<div class="wpcal-meta-item">
						<div class="wpcal-meta-icon" aria-hidden="true">&#128101;</div>
						<div class="wpcal-meta-body">
							<span class="wpcal-meta-label"><?php esc_html_e( 'Capacity', 'wpcalibrate-events-manager' ); ?></span>
							<strong class="wpcal-meta-val"><?php printf( esc_html__( '%s attendees', 'wpcalibrate-events-manager' ), esc_html( $capacity ) ); ?></strong>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<!-- Add to Calendar Actions -->
			<?php if ( $show_ical || $show_gcal ) : ?>
				<div class="wpcal-actions-bar" role="group" aria-label="<?php esc_attr_e( 'Add to Calendar', 'wpcalibrate-events-manager' ); ?>">
					<?php if ( $show_gcal ) : ?>
						<a href="<?php echo esc_url( $gcal_url ); ?>" target="_blank" rel="noopener noreferrer" class="wpcal-btn wpcal-btn-outline">
							<span aria-hidden="true">&#43;</span> <?php esc_html_e( 'Add to Google Calendar', 'wpcalibrate-events-manager' ); ?>
						</a>
					<?php endif; ?>
					<?php if ( $show_ical ) : ?>
						<a href="<?php echo esc_url( $ics_url ); ?>" class="wpcal-btn wpcal-btn-outline">
							<span aria-hidden="true">&#128229;</span> <?php esc_html_e( 'Export iCalendar (.ics)', 'wpcalibrate-events-manager' ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<!-- Location & Venue Details -->
			<?php if ( $show_venue && ( $venue_id || ! empty( $online_url ) ) ) : ?>
				<div class="wpcal-section-panel">
					<h3 class="wpcal-section-title"><?php esc_html_e( 'Location Details', 'wpcalibrate-events-manager' ); ?></h3>

					<?php if ( $event_type === 'online' || $event_type === 'hybrid' ) : ?>
						<div class="wpcal-location-online" style="margin-bottom: 15px;">
							<span class="wpcal-badge wpcal-badge-online"><?php esc_html_e( 'Virtual Event', 'wpcalibrate-events-manager' ); ?></span>
							<?php if ( ! empty( $online_url ) ) : ?>
								<p style="margin-top: 8px;">
									<a href="<?php echo esc_url( $online_url ); ?>" target="_blank" rel="noopener noreferrer" class="wpcal-btn wpcal-btn-primary">
										<?php esc_html_e( 'Join Online Stream / Meeting &#8599;', 'wpcalibrate-events-manager' ); ?>
									</a>
								</p>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( $venue_id && ( $event_type === 'physical' || $event_type === 'hybrid' ) ) : ?>
						<?php
						$v_post    = get_post( $venue_id );
						$address   = get_post_meta( $venue_id, '_wpcal_address', true );
						$city      = get_post_meta( $venue_id, '_wpcal_city', true );
						$state     = get_post_meta( $venue_id, '_wpcal_state', true );
						$zip       = get_post_meta( $venue_id, '_wpcal_postal_code', true );
						$country   = get_post_meta( $venue_id, '_wpcal_country', true );
						$lat       = get_post_meta( $venue_id, '_wpcal_latitude', true );
						$lng       = get_post_meta( $venue_id, '_wpcal_longitude', true );
						$v_web     = get_post_meta( $venue_id, '_wpcal_website', true );
						$v_phone   = get_post_meta( $venue_id, '_wpcal_phone', true );

						$addr_parts = array_filter( [ $address, $city, $state, $zip, $country ] );
						$full_addr  = implode( ', ', $addr_parts );
						?>
						<div class="wpcal-venue-card">
							<h4 class="wpcal-venue-name"><?php echo esc_html( $v_post->post_title ); ?></h4>
							<?php if ( ! empty( $full_addr ) ) : ?>
								<p class="wpcal-venue-addr"><span aria-hidden="true">&#128205;</span> <?php echo esc_html( $full_addr ); ?></p>
							<?php endif; ?>
							<?php if ( ! empty( $v_phone ) ) : ?>
								<p class="wpcal-venue-phone"><span aria-hidden="true">&#128222;</span> <a href="tel:<?php echo esc_attr( $v_phone ); ?>"><?php echo esc_html( $v_phone ); ?></a></p>
							<?php endif; ?>
							<?php if ( ! empty( $v_web ) ) : ?>
								<p class="wpcal-venue-web"><span aria-hidden="true">&#127760;</span> <a href="<?php echo esc_url( $v_web ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $v_web ); ?> &#8599;</a></p>
							<?php endif; ?>

							<?php
							if ( $show_map && $enable_osm && ( ! empty( $lat ) || ! empty( $full_addr ) ) ) {
								$map_provider = new OpenStreetMapProvider();
								$lat_val = is_numeric( $lat ) ? (float) $lat : null;
								$lng_val = is_numeric( $lng ) ? (float) $lng : null;
								echo $map_provider->render_map( $lat_val, $lng_val, $full_addr ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<!-- Organizers Details -->
			<?php if ( $show_org && ! empty( $org_ids ) ) : ?>
				<div class="wpcal-section-panel">
					<h3 class="wpcal-section-title"><?php esc_html_e( 'Organizers', 'wpcalibrate-events-manager' ); ?></h3>
					<div class="wpcal-organizers-list">
						<?php foreach ( $org_ids as $oid ) : ?>
							<?php
							$o_post  = get_post( (int) $oid );
							if ( ! $o_post ) {
								continue;
							}
							$o_email = get_post_meta( (int) $oid, '_wpcal_organizer_email', true );
							$o_phone = get_post_meta( (int) $oid, '_wpcal_organizer_phone', true );
							$o_web   = get_post_meta( (int) $oid, '_wpcal_organizer_website', true );
							?>
							<div class="wpcal-organizer-item">
								<strong><?php echo esc_html( $o_post->post_title ); ?></strong>
								<?php if ( ! empty( $o_email ) ) : ?>
									<span class="wpcal-org-detail"><a href="mailto:<?php echo esc_attr( $o_email ); ?>"><?php echo esc_html( $o_email ); ?></a></span>
								<?php endif; ?>
								<?php if ( ! empty( $o_phone ) ) : ?>
									<span class="wpcal-org-detail"><a href="tel:<?php echo esc_attr( $o_phone ); ?>"><?php echo esc_html( $o_phone ); ?></a></span>
								<?php endif; ?>
								<?php if ( ! empty( $o_web ) ) : ?>
									<span class="wpcal-org-detail"><a href="<?php echo esc_url( $o_web ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Website &#8599;', 'wpcalibrate-events-manager' ); ?></a></span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>

			<!-- Accessibility Notes -->
			<?php if ( ! empty( $access ) ) : ?>
				<div class="wpcal-section-panel">
					<h3 class="wpcal-section-title"><?php esc_html_e( 'Accessibility Information', 'wpcalibrate-events-manager' ); ?></h3>
					<p><?php echo esc_html( $access ); ?></p>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
