<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Admin;

use WPCalibrate\EventsManager\Domain\Capabilities;
use WPCalibrate\EventsManager\Domain\DateTimeHandler;
use WPCalibrate\EventsManager\Events\EventPostType;
use WPCalibrate\EventsManager\Recurrence\RecurrenceRule;
use WPCalibrate\EventsManager\Venues\VenuePostType;
use WPCalibrate\EventsManager\Organizers\OrganizerPostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles admin meta boxes for Event post type.
 */
class EventMetaBox {

	public const NONCE_ACTION = 'wpcal_save_event_meta_action';
	public const NONCE_NAME   = 'wpcal_event_nonce';

	/**
	 * Register meta box hooks.
	 */
	public function register_hooks(): void {
		add_action( 'add_meta_boxes_' . EventPostType::POST_TYPE, [ $this, 'add_meta_boxes' ] );
		add_action( 'save_post_' . EventPostType::POST_TYPE, [ $this, 'save_meta_box_data' ], 10, 2 );
	}

	/**
	 * Add meta boxes to the edit screen.
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'wpcal_event_details',
			__( 'Event Details & Settings', 'wpcalibrate-events-manager' ),
			[ $this, 'render_meta_box' ],
			EventPostType::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render the event details meta box.
	 *
	 * @param \WP_Post $post
	 */
	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$post_id    = $post->ID;
		$start_date = get_post_meta( $post_id, '_wpcal_start_date', true ) ?: current_time( 'Y-m-d' );
		$start_time = get_post_meta( $post_id, '_wpcal_start_time', true ) ?: '09:00';
		$end_date   = get_post_meta( $post_id, '_wpcal_end_date', true ) ?: $start_date;
		$end_time   = get_post_meta( $post_id, '_wpcal_end_time', true ) ?: '10:00';
		$all_day    = (bool) get_post_meta( $post_id, '_wpcal_all_day', true );
		$event_tz   = get_post_meta( $post_id, '_wpcal_timezone', true );
		$status     = get_post_meta( $post_id, '_wpcal_status', true ) ?: 'publish';

		$event_type = get_post_meta( $post_id, '_wpcal_event_type', true ) ?: 'physical';
		$venue_id   = (int) get_post_meta( $post_id, '_wpcal_venue_id', true );
		$org_ids    = (array) get_post_meta( $post_id, '_wpcal_organizer_ids', true );
		$online_url = get_post_meta( $post_id, '_wpcal_online_url', true ) ?: '';
		$ext_url    = get_post_meta( $post_id, '_wpcal_external_url', true ) ?: '';

		$cost       = get_post_meta( $post_id, '_wpcal_cost', true ) ?: '';
		$capacity   = get_post_meta( $post_id, '_wpcal_capacity', true ) ?: '';
		$access     = get_post_meta( $post_id, '_wpcal_accessibility_notes', true ) ?: '';
		$featured   = (bool) get_post_meta( $post_id, '_wpcal_featured', true );

		$rule       = RecurrenceRule::from_post_id( $post_id );
		$site_tz    = DateTimeHandler::get_site_timezone()->getName();

		// Fetch existing venues and organizers
		$venues     = get_posts( [ 'post_type' => VenuePostType::POST_TYPE, 'posts_per_page' => -1, 'post_status' => 'publish' ] );
		$organizers = get_posts( [ 'post_type' => OrganizerPostType::POST_TYPE, 'posts_per_page' => -1, 'post_status' => 'publish' ] );
		?>
		<div class="wpcal-metabox-wrapper">
			<style>
				.wpcal-tabs { display: flex; border-bottom: 1px solid #ccd0d4; margin-bottom: 16px; background: #f6f7f7; }
				.wpcal-tab-btn { padding: 10px 18px; border: none; background: none; cursor: pointer; font-weight: 600; font-size: 13px; color: #50575e; border-bottom: 2px solid transparent; }
				.wpcal-tab-btn.active { color: #2271b1; border-bottom-color: #2271b1; background: #fff; }
				.wpcal-tab-panel { display: none; padding: 6px 4px; }
				.wpcal-tab-panel.active { display: block; }
				.wpcal-form-row { margin-bottom: 14px; display: flex; flex-wrap: wrap; align-items: center; }
				.wpcal-form-row label.field-label { width: 170px; font-weight: 600; font-size: 13px; color: #1d2327; }
				.wpcal-form-row .field-input { flex: 1; min-width: 260px; }
				.wpcal-help-text { font-size: 12px; color: #646970; margin-top: 4px; display: block; }
				.wpcal-badge-info { background: #e7f5fb; color: #0073aa; padding: 4px 8px; border-radius: 4px; display: inline-block; font-size: 12px; }
			</style>

			<div class="wpcal-tabs">
				<button type="button" class="wpcal-tab-btn active" data-tab="timing"><?php esc_html_e( 'Date & Time', 'wpcalibrate-events-manager' ); ?></button>
				<button type="button" class="wpcal-tab-btn" data-tab="recurrence"><?php esc_html_e( 'Recurrence', 'wpcalibrate-events-manager' ); ?></button>
				<button type="button" class="wpcal-tab-btn" data-tab="location"><?php esc_html_e( 'Location & Venue', 'wpcalibrate-events-manager' ); ?></button>
				<button type="button" class="wpcal-tab-btn" data-tab="organizer"><?php esc_html_e( 'Organizers', 'wpcalibrate-events-manager' ); ?></button>
				<button type="button" class="wpcal-tab-btn" data-tab="additional"><?php esc_html_e( 'Additional Details', 'wpcalibrate-events-manager' ); ?></button>
			</div>

			<!-- TAB 1: DATE & TIME -->
			<div class="wpcal-tab-panel active" id="wpcal-panel-timing">
				<div class="wpcal-form-row">
					<label class="field-label" for="wpcal_all_day"><?php esc_html_e( 'All Day Event?', 'wpcalibrate-events-manager' ); ?></label>
					<div class="field-input">
						<label><input type="checkbox" name="wpcal_all_day" id="wpcal_all_day" value="1" <?php checked( $all_day ); ?>> <?php esc_html_e( 'Yes, this event runs all day', 'wpcalibrate-events-manager' ); ?></label>
					</div>
				</div>

				<div class="wpcal-form-row">
					<label class="field-label" for="wpcal_start_date"><?php esc_html_e( 'Start Date & Time', 'wpcalibrate-events-manager' ); ?></label>
					<div class="field-input">
						<input type="date" name="wpcal_start_date" id="wpcal_start_date" value="<?php echo esc_attr( $start_date ); ?>" required>
						<span class="wpcal-time-wrap" style="<?php echo $all_day ? 'display:none;' : ''; ?>">
							<input type="time" name="wpcal_start_time" id="wpcal_start_time" value="<?php echo esc_attr( $start_time ); ?>">
						</span>
					</div>
				</div>

				<div class="wpcal-form-row">
					<label class="field-label" for="wpcal_end_date"><?php esc_html_e( 'End Date & Time', 'wpcalibrate-events-manager' ); ?></label>
					<div class="field-input">
						<input type="date" name="wpcal_end_date" id="wpcal_end_date" value="<?php echo esc_attr( $end_date ); ?>" required>
						<span class="wpcal-time-wrap" style="<?php echo $all_day ? 'display:none;' : ''; ?>">
							<input type="time" name="wpcal_end_time" id="wpcal_end_time" value="<?php echo esc_attr( $end_time ); ?>">
						</span>
					</div>
				</div>

				<div class="wpcal-form-row">
					<label class="field-label" for="wpcal_timezone"><?php esc_html_e( 'Timezone', 'wpcalibrate-events-manager' ); ?></label>
					<div class="field-input">
						<select name="wpcal_timezone" id="wpcal_timezone">
							<option value=""><?php printf( esc_html__( 'Site Default (%s)', 'wpcalibrate-events-manager' ), esc_html( $site_tz ) ); ?></option>
							<?php
							$all_tz = timezone_identifiers_list();
							foreach ( $all_tz as $tz_id ) {
								printf(
									'<option value="%s" %s>%s</option>',
									esc_attr( $tz_id ),
									selected( $event_tz, $tz_id, false ),
									esc_html( $tz_id )
								);
							}
							?>
						</select>
						<span class="wpcal-help-text"><?php esc_html_e( 'Leave as default unless this specific event occurs in another timezone.', 'wpcalibrate-events-manager' ); ?></span>
					</div>
				</div>

				<div class="wpcal-form-row">
					<label class="field-label" for="wpcal_status"><?php esc_html_e( 'Event Status', 'wpcalibrate-events-manager' ); ?></label>
					<div class="field-input">
						<select name="wpcal_status" id="wpcal_status">
							<option value="publish" <?php selected( $status, 'publish' ); ?>><?php esc_html_e( 'Scheduled / Active', 'wpcalibrate-events-manager' ); ?></option>
							<option value="cancelled" <?php selected( $status, 'cancelled' ); ?>><?php esc_html_e( 'Cancelled', 'wpcalibrate-events-manager' ); ?></option>
							<option value="postponed" <?php selected( $status, 'postponed' ); ?>><?php esc_html_e( 'Postponed', 'wpcalibrate-events-manager' ); ?></option>
						</select>
					</div>
				</div>
			</div>

			<!-- TAB 2: RECURRENCE -->
			<div class="wpcal-tab-panel" id="wpcal-panel-recurrence">
				<div class="wpcal-form-row">
					<label class="field-label" for="wpcal_recurrence_enabled"><?php esc_html_e( 'Repeat Event?', 'wpcalibrate-events-manager' ); ?></label>
					<div class="field-input">
						<label><input type="checkbox" name="wpcal_recurrence_enabled" id="wpcal_recurrence_enabled" value="1" <?php checked( $rule->is_recurring() ); ?>> <?php esc_html_e( 'Enable recurring schedule', 'wpcalibrate-events-manager' ); ?></label>
					</div>
				</div>

				<div id="wpcal-recurrence-options" style="<?php echo ! $rule->is_recurring() ? 'display:none;' : ''; ?>">
					<div class="wpcal-form-row">
						<label class="field-label" for="wpcal_recurrence_frequency"><?php esc_html_e( 'Frequency', 'wpcalibrate-events-manager' ); ?></label>
						<div class="field-input">
							<select name="wpcal_recurrence_frequency" id="wpcal_recurrence_frequency">
								<option value="daily" <?php selected( $rule->frequency, 'daily' ); ?>><?php esc_html_e( 'Daily', 'wpcalibrate-events-manager' ); ?></option>
								<option value="weekly" <?php selected( $rule->frequency, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'wpcalibrate-events-manager' ); ?></option>
								<option value="monthly" <?php selected( $rule->frequency, 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'wpcalibrate-events-manager' ); ?></option>
								<option value="yearly" <?php selected( $rule->frequency, 'yearly' ); ?>><?php esc_html_e( 'Yearly', 'wpcalibrate-events-manager' ); ?></option>
							</select>
						</div>
					</div>

					<div class="wpcal-form-row">
						<label class="field-label" for="wpcal_recurrence_interval"><?php esc_html_e( 'Repeat Every', 'wpcalibrate-events-manager' ); ?></label>
						<div class="field-input">
							<input type="number" min="1" max="100" name="wpcal_recurrence_interval" id="wpcal_recurrence_interval" value="<?php echo esc_attr( (string) $rule->interval ); ?>" style="width: 80px;">
							<span id="wpcal-interval-unit"><?php esc_html_e( 'interval(s)', 'wpcalibrate-events-manager' ); ?></span>
						</div>
					</div>

					<!-- Weekly weekdays -->
					<div class="wpcal-form-row" id="wpcal-weekly-days-row">
						<label class="field-label"><?php esc_html_e( 'Repeat On Days', 'wpcalibrate-events-manager' ); ?></label>
						<div class="field-input">
							<?php
							$day_names = [
								0 => __( 'Sun', 'wpcalibrate-events-manager' ),
								1 => __( 'Mon', 'wpcalibrate-events-manager' ),
								2 => __( 'Tue', 'wpcalibrate-events-manager' ),
								3 => __( 'Wed', 'wpcalibrate-events-manager' ),
								4 => __( 'Thu', 'wpcalibrate-events-manager' ),
								5 => __( 'Fri', 'wpcalibrate-events-manager' ),
								6 => __( 'Sat', 'wpcalibrate-events-manager' ),
							];
							foreach ( $day_names as $k => $day_name ) {
								$checked = in_array( $k, $rule->weekdays, true );
								printf(
									'<label style="margin-right:12px;"><input type="checkbox" name="wpcal_recurrence_weekdays[]" value="%d" %s> %s</label>',
									$k,
									checked( $checked, true, false ),
									esc_html( $day_name )
								);
							}
							?>
						</div>
					</div>

					<!-- Monthly options -->
					<div class="wpcal-form-row" id="wpcal-monthly-row" style="display:none;">
						<label class="field-label"><?php esc_html_e( 'Monthly Pattern', 'wpcalibrate-events-manager' ); ?></label>
						<div class="field-input">
							<label style="display:block; margin-bottom: 6px;">
								<input type="radio" name="wpcal_recurrence_monthly_type" value="day_of_month" <?php checked( $rule->monthly_type, 'day_of_month' ); ?>>
								<?php esc_html_e( 'On the same day of the month', 'wpcalibrate-events-manager' ); ?>
							</label>
							<label style="display:block;">
								<input type="radio" name="wpcal_recurrence_monthly_type" value="day_of_week" <?php checked( $rule->monthly_type, 'day_of_week' ); ?>>
								<?php esc_html_e( 'On the', 'wpcalibrate-events-manager' ); ?>
								<select name="wpcal_recurrence_monthly_week">
									<option value="first" <?php selected( $rule->monthly_week, 'first' ); ?>><?php esc_html_e( 'First', 'wpcalibrate-events-manager' ); ?></option>
									<option value="second" <?php selected( $rule->monthly_week, 'second' ); ?>><?php esc_html_e( 'Second', 'wpcalibrate-events-manager' ); ?></option>
									<option value="third" <?php selected( $rule->monthly_week, 'third' ); ?>><?php esc_html_e( 'Third', 'wpcalibrate-events-manager' ); ?></option>
									<option value="fourth" <?php selected( $rule->monthly_week, 'fourth' ); ?>><?php esc_html_e( 'Fourth', 'wpcalibrate-events-manager' ); ?></option>
									<option value="last" <?php selected( $rule->monthly_week, 'last' ); ?>><?php esc_html_e( 'Last', 'wpcalibrate-events-manager' ); ?></option>
								</select>
								<select name="wpcal_recurrence_monthly_weekday">
									<option value="1" <?php selected( $rule->monthly_weekday, 1 ); ?>><?php esc_html_e( 'Monday', 'wpcalibrate-events-manager' ); ?></option>
									<option value="2" <?php selected( $rule->monthly_weekday, 2 ); ?>><?php esc_html_e( 'Tuesday', 'wpcalibrate-events-manager' ); ?></option>
									<option value="3" <?php selected( $rule->monthly_weekday, 3 ); ?>><?php esc_html_e( 'Wednesday', 'wpcalibrate-events-manager' ); ?></option>
									<option value="4" <?php selected( $rule->monthly_weekday, 4 ); ?>><?php esc_html_e( 'Thursday', 'wpcalibrate-events-manager' ); ?></option>
									<option value="5" <?php selected( $rule->monthly_weekday, 5 ); ?>><?php esc_html_e( 'Friday', 'wpcalibrate-events-manager' ); ?></option>
									<option value="6" <?php selected( $rule->monthly_weekday, 6 ); ?>><?php esc_html_e( 'Saturday', 'wpcalibrate-events-manager' ); ?></option>
									<option value="0" <?php selected( $rule->monthly_weekday, 0 ); ?>><?php esc_html_e( 'Sunday', 'wpcalibrate-events-manager' ); ?></option>
								</select>
							</label>
						</div>
					</div>

					<!-- Recurrence End -->
					<div class="wpcal-form-row">
						<label class="field-label"><?php esc_html_e( 'Recurrence Ends', 'wpcalibrate-events-manager' ); ?></label>
						<div class="field-input">
							<label style="display:block; margin-bottom: 6px;">
								<input type="radio" name="wpcal_recurrence_end_type" value="never" <?php checked( $rule->end_type, 'never' ); ?>>
								<?php esc_html_e( 'Never (bounded automatically up to 2 years ahead)', 'wpcalibrate-events-manager' ); ?>
							</label>
							<label style="display:block; margin-bottom: 6px;">
								<input type="radio" name="wpcal_recurrence_end_type" value="until" <?php checked( $rule->end_type, 'until' ); ?>>
								<?php esc_html_e( 'On date: ', 'wpcalibrate-events-manager' ); ?>
								<input type="date" name="wpcal_recurrence_until" value="<?php echo esc_attr( (string) $rule->until ); ?>">
							</label>
							<label style="display:block;">
								<input type="radio" name="wpcal_recurrence_end_type" value="count" <?php checked( $rule->end_type, 'count' ); ?>>
								<?php esc_html_e( 'After ', 'wpcalibrate-events-manager' ); ?>
								<input type="number" min="1" max="500" name="wpcal_recurrence_count" value="<?php echo esc_attr( (string) $rule->count ); ?>" style="width: 70px;">
								<?php esc_html_e( 'occurrences', 'wpcalibrate-events-manager' ); ?>
							</label>
						</div>
					</div>

					<div class="wpcal-form-row">
						<label class="field-label" for="wpcal_excluded_dates"><?php esc_html_e( 'Excluded Dates', 'wpcalibrate-events-manager' ); ?></label>
						<div class="field-input">
							<textarea name="wpcal_excluded_dates" id="wpcal_excluded_dates" rows="2" style="width:100%; max-width:400px;" placeholder="<?php esc_attr_e( 'YYYY-MM-DD (one per line or comma-separated)', 'wpcalibrate-events-manager' ); ?>"><?php echo esc_textarea( implode( "\n", $rule->excluded_dates ) ); ?></textarea>
							<span class="wpcal-help-text"><?php esc_html_e( 'Specific dates where the event will not occur.', 'wpcalibrate-events-manager' ); ?></span>
						</div>
					</div>

					<div class="wpcal-form-row">
						<label class="field-label"><?php esc_html_e( 'Current Summary', 'wpcalibrate-events-manager' ); ?></label>
						<div class="field-input">
							<span class="wpcal-badge-info"><?php echo esc_html( $rule->get_human_summary() ); ?></span>
							<p class="description" style="margin-top:6px; color:#d63638;">
								<?php esc_html_e( 'Notice: Saving changes to recurrence will deterministically rebuild occurrences for this event.', 'wpcalibrate-events-manager' ); ?>
							</p>
						</div>
					</div>
				</div>
			</div>

			<!-- TAB 3: LOCATION & VENUE -->
			<div class="wpcal-tab-panel" id="wpcal-panel-location">
				<div class="wpcal-form-row">
					<label class="field-label" for="wpcal_event_type"><?php esc_html_e( 'Event Type', 'wpcalibrate-events-manager' ); ?></label>
					<div class="field-input">
						<select name="wpcal_event_type" id="wpcal_event_type">
							<option value="physical" <?php selected( $event_type, 'physical' ); ?>><?php esc_html_e( 'Physical Location', 'wpcalibrate-events-manager' ); ?></option>
							<option value="online" <?php selected( $event_type, 'online' ); ?>><?php esc_html_e( 'Online / Virtual', 'wpcalibrate-events-manager' ); ?></option>
							<option value="hybrid" <?php selected( $event_type, 'hybrid' ); ?>><?php esc_html_e( 'Hybrid (Physical + Online)', 'wpcalibrate-events-manager' ); ?></option>
						</select>
					</div>
				</div>

				<div class="wpcal-form-row" id="wpcal-venue-select-row">
					<label class="field-label" for="wpcal_venue_id"><?php esc_html_e( 'Select Venue', 'wpcalibrate-events-manager' ); ?></label>
					<div class="field-input">
						<select name="wpcal_venue_id" id="wpcal_venue_id">
							<option value=""><?php esc_html_e( '-- Select Venue --', 'wpcalibrate-events-manager' ); ?></option>
							<?php foreach ( $venues as $v ) : ?>
								<option value="<?php echo esc_attr( (string) $v->ID ); ?>" <?php selected( $venue_id, $v->ID ); ?>>
									<?php echo esc_html( $v->post_title ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . VenuePostType::POST_TYPE ) ); ?>" target="_blank" class="button button-secondary" style="margin-left: 8px;">
							<?php esc_html_e( '+ New Venue', 'wpcalibrate-events-manager' ); ?>
						</a>
					</div>
				</div>

				<div class="wpcal-form-row" id="wpcal-online-url-row">
					<label class="field-label" for="wpcal_online_url"><?php esc_html_e( 'Online Stream / Meeting URL', 'wpcalibrate-events-manager' ); ?></label>
					<div class="field-input">
						<input type="url" name="wpcal_online_url" id="wpcal_online_url" value="<?php echo esc_url( $online_url ); ?>" style="width: 100%; max-width: 450px;" placeholder="https://zoom.us/j/... or https://meet.google.com/...">
					</div>
				</div>
			</div>

			<!-- TAB 4: ORGANIZERS -->
			<div class="wpcal-tab-panel" id="wpcal-panel-organizer">
				<div class="wpcal-form-row">
					<label class="field-label" for="wpcal_organizer_ids"><?php esc_html_e( 'Select Organizers', 'wpcalibrate-events-manager' ); ?></label>
					<div class="field-input">
						<select name="wpcal_organizer_ids[]" id="wpcal_organizer_ids" multiple style="width: 100%; max-width: 400px; min-height: 100px;">
							<?php foreach ( $organizers as $o ) : ?>
								<option value="<?php echo esc_attr( (string) $o->ID ); ?>" <?php echo in_array( $o->ID, $org_ids, true ) ? 'selected' : ''; ?>>
									<?php echo esc_html( $o->post_title ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . OrganizerPostType::POST_TYPE ) ); ?>" target="_blank" class="button button-secondary" style="margin-left: 8px; vertical-align: top;">
							<?php esc_html_e( '+ New Organizer', 'wpcalibrate-events-manager' ); ?>
						</a>
						<span class="wpcal-help-text"><?php esc_html_e( 'Hold Ctrl (Windows) or Cmd (Mac) to select multiple organizers.', 'wpcalibrate-events-manager' ); ?></span>
					</div>
				</div>
			</div>

			<!-- TAB 5: ADDITIONAL DETAILS -->
			<div class="wpcal-tab-panel" id="wpcal-panel-additional">
				<div class="wpcal-form-row">
					<label class="field-label" for="wpcal_featured"><?php esc_html_e( 'Featured Event?', 'wpcalibrate-events-manager' ); ?></label>
					<div class="field-input">
						<label><input type="checkbox" name="wpcal_featured" id="wpcal_featured" value="1" <?php checked( $featured ); ?>> <?php esc_html_e( 'Highlight as a featured event', 'wpcalibrate-events-manager' ); ?></label>
					</div>
				</div>

				<div class="wpcal-form-row">
					<label class="field-label" for="wpcal_external_url"><?php esc_html_e( 'Event Website / Registration URL', 'wpcalibrate-events-manager' ); ?></label>
					<div class="field-input">
						<input type="url" name="wpcal_external_url" id="wpcal_external_url" value="<?php echo esc_url( $ext_url ); ?>" style="width:100%; max-width:450px;">
					</div>
				</div>

				<div class="wpcal-form-row">
					<label class="field-label" for="wpcal_cost"><?php esc_html_e( 'Event Cost (Display Text)', 'wpcalibrate-events-manager' ); ?></label>
					<div class="field-input">
						<input type="text" name="wpcal_cost" id="wpcal_cost" value="<?php echo esc_attr( $cost ); ?>" placeholder="<?php esc_attr_e( 'e.g. Free, $25, or £10 - £30', 'wpcalibrate-events-manager' ); ?>" style="width:250px;">
						<span class="wpcal-help-text"><?php esc_html_e( 'Informational text only. No ticketing or payment processing in Version 1.', 'wpcalibrate-events-manager' ); ?></span>
					</div>
				</div>

				<div class="wpcal-form-row">
					<label class="field-label" for="wpcal_capacity"><?php esc_html_e( 'Max Capacity', 'wpcalibrate-events-manager' ); ?></label>
					<div class="field-input">
						<input type="number" min="0" name="wpcal_capacity" id="wpcal_capacity" value="<?php echo esc_attr( (string) $capacity ); ?>" style="width:120px;">
						<span class="wpcal-help-text"><?php esc_html_e( 'Optional informational capacity limit.', 'wpcalibrate-events-manager' ); ?></span>
					</div>
				</div>

				<div class="wpcal-form-row">
					<label class="field-label" for="wpcal_accessibility_notes"><?php esc_html_e( 'Accessibility Notes', 'wpcalibrate-events-manager' ); ?></label>
					<div class="field-input">
						<textarea name="wpcal_accessibility_notes" id="wpcal_accessibility_notes" rows="2" style="width:100%; max-width:450px;" placeholder="<?php esc_attr_e( 'Wheelchair accessible, ASL interpreter available upon request, etc.', 'wpcalibrate-events-manager' ); ?>"><?php echo esc_textarea( $access ); ?></textarea>
					</div>
				</div>
			</div>
		</div>

		<script>
		document.addEventListener('DOMContentLoaded', function() {
			// Tab switching
			const tabBtns = document.querySelectorAll('.wpcal-tab-btn');
			const tabPanels = document.querySelectorAll('.wpcal-tab-panel');
			tabBtns.forEach(btn => {
				btn.addEventListener('click', function() {
					tabBtns.forEach(b => b.classList.remove('active'));
					tabPanels.forEach(p => p.classList.remove('active'));
					this.classList.add('active');
					const target = document.getElementById('wpcal-panel-' + this.dataset.tab);
					if (target) target.classList.add('active');
				});
			});

			// All day toggle
			const allDayCheck = document.getElementById('wpcal_all_day');
			const timeWraps = document.querySelectorAll('.wpcal-time-wrap');
			if (allDayCheck) {
				allDayCheck.addEventListener('change', function() {
					timeWraps.forEach(w => w.style.display = this.checked ? 'none' : 'inline');
				});
			}

			// Recurrence enable toggle
			const recCheck = document.getElementById('wpcal_recurrence_enabled');
			const recOptions = document.getElementById('wpcal-recurrence-options');
			if (recCheck && recOptions) {
				recCheck.addEventListener('change', function() {
					recOptions.style.display = this.checked ? 'block' : 'none';
				});
			}

			// Recurrence frequency logic
			const freqSelect = document.getElementById('wpcal_recurrence_frequency');
			const weeklyRow = document.getElementById('wpcal-weekly-days-row');
			const monthlyRow = document.getElementById('wpcal-monthly-row');
			const unitSpan = document.getElementById('wpcal-interval-unit');
			function updateFreq() {
				if (!freqSelect) return;
				const val = freqSelect.value;
				if (weeklyRow) weeklyRow.style.display = val === 'weekly' ? 'flex' : 'none';
				if (monthlyRow) monthlyRow.style.display = val === 'monthly' ? 'flex' : 'none';
				if (unitSpan) {
					const units = { daily: 'day(s)', weekly: 'week(s)', monthly: 'month(s)', yearly: 'year(s)' };
					unitSpan.textContent = units[val] || 'interval(s)';
				}
			}
			if (freqSelect) {
				freqSelect.addEventListener('change', updateFreq);
				updateFreq();
			}
		});
		</script>
		<?php
	}

	/**
	 * Save meta box data when event post is saved.
	 *
	 * @param int      $post_id
	 * @param \WP_Post $post
	 */
	public function save_meta_box_data( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE_NAME ] ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( Capabilities::EDIT_EVENTS, $post_id ) ) {
			return;
		}

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Dates & Times
		$start_date = ! empty( $_POST['wpcal_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcal_start_date'] ) ) : current_time( 'Y-m-d' );
		$start_time = ! empty( $_POST['wpcal_start_time'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcal_start_time'] ) ) : '09:00';
		$end_date   = ! empty( $_POST['wpcal_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcal_end_date'] ) ) : $start_date;
		$end_time   = ! empty( $_POST['wpcal_end_time'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcal_end_time'] ) ) : '10:00';
		$all_day    = ! empty( $_POST['wpcal_all_day'] ) ? 1 : 0;
		$timezone   = ! empty( $_POST['wpcal_timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcal_timezone'] ) ) : '';
		$status     = ! empty( $_POST['wpcal_status'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcal_status'] ) ) : 'publish';

		// Validate dates: If end precedes start, clamp end to start
		if ( $end_date < $start_date ) {
			$end_date = $start_date;
		}

		update_post_meta( $post_id, '_wpcal_start_date', $start_date );
		update_post_meta( $post_id, '_wpcal_start_time', $start_time );
		update_post_meta( $post_id, '_wpcal_end_date', $end_date );
		update_post_meta( $post_id, '_wpcal_end_time', $end_time );
		update_post_meta( $post_id, '_wpcal_all_day', $all_day );
		update_post_meta( $post_id, '_wpcal_timezone', $timezone );
		update_post_meta( $post_id, '_wpcal_status', $status );

		// Location & Venue
		$event_type = ! empty( $_POST['wpcal_event_type'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcal_event_type'] ) ) : 'physical';
		$venue_id   = ! empty( $_POST['wpcal_venue_id'] ) ? absint( $_POST['wpcal_venue_id'] ) : 0;
		$online_url = ! empty( $_POST['wpcal_online_url'] ) ? esc_url_raw( wp_unslash( $_POST['wpcal_online_url'] ) ) : '';

		update_post_meta( $post_id, '_wpcal_event_type', $event_type );
		update_post_meta( $post_id, '_wpcal_venue_id', $venue_id );
		update_post_meta( $post_id, '_wpcal_online_url', $online_url );

		// Organizers
		$organizer_ids = [];
		if ( ! empty( $_POST['wpcal_organizer_ids'] ) && is_array( $_POST['wpcal_organizer_ids'] ) ) {
			$organizer_ids = array_map( 'absint', $_POST['wpcal_organizer_ids'] );
		}
		update_post_meta( $post_id, '_wpcal_organizer_ids', $organizer_ids );

		// Additional Details
		$featured   = ! empty( $_POST['wpcal_featured'] ) ? 1 : 0;
		$ext_url    = ! empty( $_POST['wpcal_external_url'] ) ? esc_url_raw( wp_unslash( $_POST['wpcal_external_url'] ) ) : '';
		$cost       = ! empty( $_POST['wpcal_cost'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcal_cost'] ) ) : '';
		$capacity   = ! empty( $_POST['wpcal_capacity'] ) ? absint( $_POST['wpcal_capacity'] ) : '';
		$access     = ! empty( $_POST['wpcal_accessibility_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wpcal_accessibility_notes'] ) ) : '';

		update_post_meta( $post_id, '_wpcal_featured', $featured );
		update_post_meta( $post_id, '_wpcal_external_url', $ext_url );
		update_post_meta( $post_id, '_wpcal_cost', $cost );
		update_post_meta( $post_id, '_wpcal_capacity', $capacity );
		update_post_meta( $post_id, '_wpcal_accessibility_notes', $access );

		// Recurrence Settings
		$rec_enabled = ! empty( $_POST['wpcal_recurrence_enabled'] ) ? 1 : 0;
		if ( $rec_enabled ) {
			$frequency   = ! empty( $_POST['wpcal_recurrence_frequency'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcal_recurrence_frequency'] ) ) : 'daily';
			$interval    = ! empty( $_POST['wpcal_recurrence_interval'] ) ? max( 1, absint( $_POST['wpcal_recurrence_interval'] ) ) : 1;
			$weekdays    = ! empty( $_POST['wpcal_recurrence_weekdays'] ) && is_array( $_POST['wpcal_recurrence_weekdays'] )
				? array_map( 'absint', $_POST['wpcal_recurrence_weekdays'] )
				: [];

			$month_type  = ! empty( $_POST['wpcal_recurrence_monthly_type'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcal_recurrence_monthly_type'] ) ) : 'day_of_month';
			$month_week  = ! empty( $_POST['wpcal_recurrence_monthly_week'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcal_recurrence_monthly_week'] ) ) : 'first';
			$month_wday  = isset( $_POST['wpcal_recurrence_monthly_weekday'] ) ? absint( $_POST['wpcal_recurrence_monthly_weekday'] ) : 1;

			$end_type    = ! empty( $_POST['wpcal_recurrence_end_type'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcal_recurrence_end_type'] ) ) : 'never';
			$until       = ! empty( $_POST['wpcal_recurrence_until'] ) ? sanitize_text_field( wp_unslash( $_POST['wpcal_recurrence_until'] ) ) : null;
			$count       = ! empty( $_POST['wpcal_recurrence_count'] ) ? absint( $_POST['wpcal_recurrence_count'] ) : null;

			$excluded_raw = ! empty( $_POST['wpcal_excluded_dates'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wpcal_excluded_dates'] ) ) : '';
			$excluded_arr = [];
			if ( $excluded_raw ) {
				$lines = preg_split( '/[\r\n,]+/', $excluded_raw );
				if ( is_array( $lines ) ) {
					foreach ( $lines as $line ) {
						$trimmed = trim( $line );
						if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $trimmed ) ) {
							$excluded_arr[] = $trimmed;
						}
					}
				}
			}

			$rule = new RecurrenceRule(
				frequency: $frequency,
				interval: $interval,
				weekdays: $weekdays,
				monthly_type: $month_type,
				monthly_week: $month_week,
				monthly_weekday: $month_wday,
				end_type: $end_type,
				until: $until,
				count: $count,
				excluded_dates: array_unique( $excluded_arr )
			);
			$rule->save_to_post( $post_id );
		} else {
			$rule = new RecurrenceRule( RecurrenceRule::FREQ_NONE );
			$rule->save_to_post( $post_id );
		}
	}
}
