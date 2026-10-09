<?php
declare(strict_types=1);

namespace WPCalibrate\EventsManager\Admin;

use WPCalibrate\EventsManager\Events\EventPostType;
use WPCalibrate\EventsManager\Venues\VenuePostType;
use WPCalibrate\EventsManager\Organizers\OrganizerPostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the Overview tab detailing plugin purpose, how to use, methods, and quick actions.
 */
class OverviewSection {

	/**
	 * Render the full Overview tab content.
	 */
	public static function render(): void {
		$icon_url    = WPCAL_PLUGIN_URL . 'branding/icon-dark.png';
		$new_event   = admin_url( 'post-new.php?post_type=' . EventPostType::POST_TYPE );
		$all_events  = admin_url( 'edit.php?post_type=' . EventPostType::POST_TYPE );
		$venues_url  = admin_url( 'edit.php?post_type=' . VenuePostType::POST_TYPE );
		$orgs_url    = admin_url( 'edit.php?post_type=' . OrganizerPostType::POST_TYPE );
		$feed_url    = home_url( '/?wpcal_feed=ical' );
		?>
		<div class="wpcal-overview-wrap" style="max-width: 1000px; margin-top: 15px;">
			<style>
				.wpcal-hero {
					background: #fff;
					border: 1px solid #ccd0d4;
					border-radius: 8px;
					padding: 24px;
					margin-bottom: 24px;
					display: flex;
					align-items: center;
					gap: 20px;
					box-shadow: 0 1px 3px rgba(0,0,0,0.04);
				}
				.wpcal-hero-icon {
					width: 64px;
					height: 64px;
					border-radius: 8px;
					flex-shrink: 0;
				}
				.wpcal-hero-title {
					margin: 0 0 6px;
					font-size: 22px;
					font-weight: 700;
					color: #1d2327;
				}
				.wpcal-hero-desc {
					margin: 0;
					font-size: 14px;
					line-height: 1.6;
					color: #50575e;
				}
				.wpcal-quick-grid {
					display: grid;
					grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
					gap: 16px;
					margin-bottom: 28px;
				}
				.wpcal-quick-card {
					background: #fff;
					border: 1px solid #ccd0d4;
					border-radius: 8px;
					padding: 18px;
					text-decoration: none;
					color: #1d2327;
					display: flex;
					flex-direction: column;
					justify-content: space-between;
					transition: all 0.2s ease;
					box-shadow: 0 1px 2px rgba(0,0,0,0.03);
				}
				.wpcal-quick-card:hover {
					border-color: #2271b1;
					box-shadow: 0 3px 8px rgba(0,0,0,0.08);
					transform: translateY(-1px);
					color: #2271b1;
				}
				.wpcal-quick-card h4 {
					margin: 0 0 6px;
					font-size: 15px;
					display: flex;
					align-items: center;
					gap: 8px;
				}
				.wpcal-quick-card p {
					margin: 0;
					font-size: 12px;
					color: #646970;
					line-height: 1.5;
				}
				.wpcal-section-box {
					background: #fff;
					border: 1px solid #ccd0d4;
					border-radius: 8px;
					padding: 24px;
					margin-bottom: 24px;
					box-shadow: 0 1px 3px rgba(0,0,0,0.04);
				}
				.wpcal-section-box h3 {
					margin: 0 0 14px;
					font-size: 18px;
					color: #1d2327;
					display: flex;
					align-items: center;
					gap: 8px;
					padding-bottom: 10px;
					border-bottom: 1px solid #f0f0f1;
				}
				.wpcal-step-list {
					list-style: none;
					padding: 0;
					margin: 0;
					display: flex;
					flex-direction: column;
					gap: 18px;
				}
				.wpcal-step-item {
					display: flex;
					gap: 16px;
					align-items: flex-start;
				}
				.wpcal-step-badge {
					background: #2271b1;
					color: #fff;
					font-weight: 700;
					font-size: 13px;
					width: 28px;
					height: 28px;
					border-radius: 50%;
					display: flex;
					align-items: center;
					justify-content: center;
					flex-shrink: 0;
				}
				.wpcal-step-body h4 {
					margin: 0 0 4px;
					font-size: 15px;
					color: #1d2327;
				}
				.wpcal-step-body p {
					margin: 0 0 6px;
					font-size: 13px;
					color: #50575e;
					line-height: 1.6;
				}
				.wpcal-code-badge {
					display: inline-block;
					background: #f6f7f7;
					border: 1px solid #dcdcde;
					padding: 2px 8px;
					border-radius: 4px;
					font-family: monospace;
					font-size: 12px;
					color: #2271b1;
				}
				.wpcal-pill {
					display: inline-block;
					background: #e7f5fb;
					color: #0073aa;
					padding: 2px 8px;
					border-radius: 12px;
					font-size: 11px;
					font-weight: 600;
					margin-right: 4px;
				}
			</style>

			<!-- HERO / PURPOSE -->
			<div class="wpcal-hero">
				<img src="<?php echo esc_url( $icon_url ); ?>" alt="WPCalibrate" class="wpcal-hero-icon">
				<div>
					<h2 class="wpcal-hero-title"><?php esc_html_e( 'Welcome to WPCalibrate Events Manager', 'wpcalibrate-events-manager' ); ?></h2>
					<p class="wpcal-hero-desc">
						<?php esc_html_e( 'WPCalibrate Events Manager is an enterprise-grade WordPress calendar and events management engine engineered for high performance, timezone precision, deterministic recurrence, and accessible presentation.', 'wpcalibrate-events-manager' ); ?>
					</p>
					<div style="margin-top: 10px;">
						<span class="wpcal-pill"><?php esc_html_e( 'Zero Post Bloat', 'wpcalibrate-events-manager' ); ?></span>
						<span class="wpcal-pill"><?php esc_html_e( 'Indexed Occurrence Table', 'wpcalibrate-events-manager' ); ?></span>
						<span class="wpcal-pill"><?php esc_html_e( 'Timezone-Safe Storage', 'wpcalibrate-events-manager' ); ?></span>
						<span class="wpcal-pill"><?php esc_html_e( 'WCAG 2.2 AA Accessible', 'wpcalibrate-events-manager' ); ?></span>
					</div>
				</div>
			</div>

			<!-- QUICK ACTIONS -->
			<div class="wpcal-quick-grid">
				<a href="<?php echo esc_url( $new_event ); ?>" class="wpcal-quick-card">
					<h4><span aria-hidden="true">&#10133;</span> <?php esc_html_e( 'Add New Event', 'wpcalibrate-events-manager' ); ?></h4>
					<p><?php esc_html_e( 'Create one-time, multi-day, all-day, or recurring events with custom rules.', 'wpcalibrate-events-manager' ); ?></p>
				</a>

				<a href="<?php echo esc_url( $all_events ); ?>" class="wpcal-quick-card">
					<h4><span aria-hidden="true">&#128197;</span> <?php esc_html_e( 'View All Events', 'wpcalibrate-events-manager' ); ?></h4>
					<p><?php esc_html_e( 'Manage events, filter by upcoming/past dates, and review recurrence summaries.', 'wpcalibrate-events-manager' ); ?></p>
				</a>

				<a href="<?php echo esc_url( $venues_url ); ?>" class="wpcal-quick-card">
					<h4><span aria-hidden="true">&#128205;</span> <?php esc_html_e( 'Manage Venues', 'wpcalibrate-events-manager' ); ?></h4>
					<p><?php esc_html_e( 'Add reusable physical locations with OpenStreetMap integration and contact info.', 'wpcalibrate-events-manager' ); ?></p>
				</a>

				<a href="<?php echo esc_url( $orgs_url ); ?>" class="wpcal-quick-card">
					<h4><span aria-hidden="true">&#128101;</span> <?php esc_html_e( 'Manage Organizers', 'wpcalibrate-events-manager' ); ?></h4>
					<p><?php esc_html_e( 'Set up reusable event organizers with email, phone, websites, and avatars.', 'wpcalibrate-events-manager' ); ?></p>
				</a>
			</div>

			<!-- HOW TO USE / STEP BY STEP METHOD -->
			<div class="wpcal-section-box">
				<h3><span aria-hidden="true">&#128218;</span> <?php esc_html_e( 'How to Use WPCalibrate Events Manager (Step-by-Step)', 'wpcalibrate-events-manager' ); ?></h3>
				<ol class="wpcal-step-list">
					<li class="wpcal-step-item">
						<div class="wpcal-step-badge">1</div>
						<div class="wpcal-step-body">
							<h4><?php esc_html_e( 'Step 1: Configure Site Defaults', 'wpcalibrate-events-manager' ); ?></h4>
							<p>
								<?php esc_html_e( 'Head over to the General and Calendar tabs above to set your site-wide event timezone, starting day of the week (Sunday, Monday, or Saturday), default calendar view (Month, Week, Day, or List), and brand accent color.', 'wpcalibrate-events-manager' ); ?>
							</p>
						</div>
					</li>

					<li class="wpcal-step-item">
						<div class="wpcal-step-badge">2</div>
						<div class="wpcal-step-body">
							<h4><?php esc_html_e( 'Step 2: Set Up Venues and Organizers', 'wpcalibrate-events-manager' ); ?></h4>
							<p>
								<?php esc_html_e( 'Under WPCalibrate in your admin menu, create reusable Venues and Organizers. When you enter a street address or coordinates for a venue, WPCalibrate automatically renders an interactive OpenStreetMap embed on the single event page without requiring expensive third-party API keys.', 'wpcalibrate-events-manager' ); ?>
							</p>
						</div>
					</li>

					<li class="wpcal-step-item">
						<div class="wpcal-step-badge">3</div>
						<div class="wpcal-step-body">
							<h4><?php esc_html_e( 'Step 3: Create & Schedule Events', 'wpcalibrate-events-manager' ); ?></h4>
							<p>
								<?php esc_html_e( 'Navigate to WPCalibrate > Add Event. Use the tabbed panels to configure:', 'wpcalibrate-events-manager' ); ?>
							</p>
							<p>
								&bull; <strong><?php esc_html_e( 'Date & Time:', 'wpcalibrate-events-manager' ); ?></strong> <?php esc_html_e( 'Set start/end dates and times, toggle All Day, and customize per-event timezone if applicable.', 'wpcalibrate-events-manager' ); ?><br>
								&bull; <strong><?php esc_html_e( 'Recurrence:', 'wpcalibrate-events-manager' ); ?></strong> <?php esc_html_e( 'Enable repeating schedules: Daily, Weekly (select specific weekdays), Monthly (by day of month or Nth weekday, e.g., 1st Monday), or Yearly (leap-year safe).', 'wpcalibrate-events-manager' ); ?><br>
								&bull; <strong><?php esc_html_e( 'Exclusions & Exceptions:', 'wpcalibrate-events-manager' ); ?></strong> <?php esc_html_e( 'Exclude specific holiday dates or mark individual occurrences cancelled without breaking the parent recurring series.', 'wpcalibrate-events-manager' ); ?><br>
								&bull; <strong><?php esc_html_e( 'Location & Details:', 'wpcalibrate-events-manager' ); ?></strong> <?php esc_html_e( 'Assign physical venues, virtual stream URLs (for Zoom/Google Meet webinars), organizers, informational cost text, and accessibility notes.', 'wpcalibrate-events-manager' ); ?>
							</p>
						</div>
					</li>

					<li class="wpcal-step-item">
						<div class="wpcal-step-badge">4</div>
						<div class="wpcal-step-body">
							<h4><?php esc_html_e( 'Step 4: Display the Calendar on Your Website', 'wpcalibrate-events-manager' ); ?></h4>
							<p>
								<?php esc_html_e( 'You have three flexible methods to display the calendar or upcoming events on any page, post, or template:', 'wpcalibrate-events-manager' ); ?>
							</p>
							<p>
								<strong>A. Gutenberg Blocks:</strong> <?php esc_html_e( 'Insert the "WPCalibrate Calendar" or "WPCalibrate Upcoming Events" block directly in the WordPress block editor with live settings.', 'wpcalibrate-events-manager' ); ?><br>
								<strong>B. Main Calendar Shortcode:</strong> <span class="wpcal-code-badge">[wpcalibrate_calendar]</span> — <?php esc_html_e( 'Renders the responsive calendar with Month, Week, Day, and List view switcher, date navigation, and search.', 'wpcalibrate-events-manager' ); ?><br>
								<strong>C. Upcoming Events Shortcode:</strong> <span class="wpcal-code-badge">[wpcalibrate_upcoming_events limit="5"]</span> — <?php esc_html_e( 'Displays a compact upcoming events agenda widget.', 'wpcalibrate-events-manager' ); ?><br>
								<strong>D. Elementor:</strong> <?php esc_html_e( 'Both shortcodes work seamlessly inside Elementor Shortcode widgets without requiring Elementor Pro.', 'wpcalibrate-events-manager' ); ?>
							</p>
						</div>
					</li>

					<li class="wpcal-step-item">
						<div class="wpcal-step-badge">5</div>
						<div class="wpcal-step-body">
							<h4><?php esc_html_e( 'Step 5: Calendar Feeds & Subscriptions', 'wpcalibrate-events-manager' ); ?></h4>
							<p>
								<?php esc_html_e( 'Visitors can add single events to Google Calendar with one click or export an RFC 5545 compatible .ics file. Additionally, your site offers a live calendar subscription feed at:', 'wpcalibrate-events-manager' ); ?>
								<br><a href="<?php echo esc_url( $feed_url ); ?>" target="_blank" class="wpcal-code-badge" style="margin-top: 4px;"><?php echo esc_html( $feed_url ); ?> &#8599;</a>
							</p>
						</div>
					</li>
				</ol>
			</div>

			<!-- ARCHITECTURE & METHODOLOGY -->
			<div class="wpcal-section-box">
				<h3><span aria-hidden="true">&#9881;</span> <?php esc_html_e( 'Under the Hood: Architecture & Methodology', 'wpcalibrate-events-manager' ); ?></h3>
				<p style="font-size: 13px; line-height: 1.6; color: #50575e; margin-bottom: 12px;">
					<?php esc_html_e( 'Most WordPress calendar plugins suffer from two major problems: they either create a new WordPress post for every recurring occurrence (clogging wp_posts with thousands of rows), or they use heavy postmeta queries that slow down the entire database. WPCalibrate Events Manager solves this through an advanced architectural approach:', 'wpcalibrate-events-manager' ); ?>
				</p>
				<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 14px;">
					<div style="background: #f6f7f7; padding: 14px; border-radius: 6px;">
						<strong style="color: #1d2327; display: block; margin-bottom: 4px;"><?php esc_html_e( '1. Dedicated Occurrence Table', 'wpcalibrate-events-manager' ); ?></strong>
						<span style="font-size: 12px; color: #646970; line-height: 1.5; display: block;">
							<?php esc_html_e( 'Events are created as a single post. Occurrence instances are deterministically calculated and indexed in wp_wpcalibrate_event_occurrences, making range queries virtually instantaneous.', 'wpcalibrate-events-manager' ); ?>
						</span>
					</div>

					<div style="background: #f6f7f7; padding: 14px; border-radius: 6px;">
						<strong style="color: #1d2327; display: block; margin-bottom: 4px;"><?php esc_html_e( '2. Dual UTC & Local Timezone Storage', 'wpcalibrate-events-manager' ); ?></strong>
						<span style="font-size: 12px; color: #646970; line-height: 1.5; display: block;">
							<?php esc_html_e( 'Database queries run in UTC for absolute chronological ordering, while local wall-clock dates ensure all-day events never shift days due to daylight-saving transitions.', 'wpcalibrate-events-manager' ); ?>
						</span>
					</div>

					<div style="background: #f6f7f7; padding: 14px; border-radius: 6px;">
						<strong style="color: #1d2327; display: block; margin-bottom: 4px;"><?php esc_html_e( '3. Bounded Safety Ceilings', 'wpcalibrate-events-manager' ); ?></strong>
						<span style="font-size: 12px; color: #646970; line-height: 1.5; display: block;">
							<?php esc_html_e( 'Recurring events without an explicit end date are safely capped up to 2 years ahead and incrementally extended via background WP-Cron, preventing infinite loops or memory spikes.', 'wpcalibrate-events-manager' ); ?>
						</span>
					</div>

					<div style="background: #f6f7f7; padding: 14px; border-radius: 6px;">
						<strong style="color: #1d2327; display: block; margin-bottom: 4px;"><?php esc_html_e( '4. Schema.org Event JSON-LD', 'wpcalibrate-events-manager' ); ?></strong>
						<span style="font-size: 12px; color: #646970; line-height: 1.5; display: block;">
							<?php esc_html_e( 'Full Schema.org Event structured data is automatically injected on single event pages for Google search snippets, accurately reporting event status, virtual URLs, and venues.', 'wpcalibrate-events-manager' ); ?>
						</span>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
