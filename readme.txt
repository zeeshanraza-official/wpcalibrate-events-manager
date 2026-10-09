=== WPCalibrate Events Manager ===
Contributors: wpcalibrate
Tags: events, calendar, event calendar, recurring events, venue, organizer, icalendar, openstreetmap
Requires at least: 6.4
Tested up to: 7.1.3
Requires PHP: 8.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A responsive, high-performance, and extensible event calendar and event management engine for WordPress by WPCalibrate.

== Description ==

**WPCalibrate Events Manager** provides a complete, modern event calendar and management system for WordPress. Designed from the ground up for speed, precision, and extensibility, it empowers administrators to manage one-time and recurring events with custom recurrence rules, venues, organizers, maps, and standards-compliant calendar feeds.

### Key Features
* **Flexible Event Formats**: Support for one-time, daily, weekly, monthly, and yearly recurring events. Multi-day, all-day, timed, online, physical, and hybrid event formats.
* **High-Performance Occurrence Engine**: Recurrence is powered by a dedicated custom indexed database table (`{$wpdb->prefix}wpcalibrate_event_occurrences`). Range queries are lightning-fast without degrading post meta performance or duplicating WordPress posts.
* **Deterministic Recurrence & Exceptions**: Cancel single occurrences or adjust specific times without breaking the parent recurrence rule. Exclude specific holiday dates with ease.
* **Responsive Views**: Beautiful Month, Week, Day, and List/Agenda views engineered with mobile responsiveness and touch support.
* **Strict Timezone Handling**: Event-specific timezone configuration with UTC storage guarantees that events and all-day occurrences never shift across daylight-saving boundaries.
* **Gutenberg Blocks & Shortcodes**: Native Gutenberg blocks for Calendar and Upcoming Events, plus Elementor-compatible shortcodes `[wpcalibrate_calendar]` and `[wpcalibrate_upcoming_events]`.
* **Reusable Venues & Organizers**: Entity post types with geocoordinates, address details, contact information, and OpenStreetMap embeds without requiring paid third-party API keys.
* **Standards-Compliant iCalendar**: Export individual events to `.ics`, Add to Google Calendar with one click, subscribe via live public feed, and safely import administrative `.ics` calendar files.
* **SEO & Accessibility**: Fully valid Schema.org `Event` JSON-LD structured data and WCAG 2.2 AA compliant keyboard and screen-reader accessibility.
* **Shared WPCalibrate Integration**: Reuses the central WPCalibrate administrative menu without collision, keeping settings strictly isolated.

== Installation ==

1. Upload the `wpcalibrate-events-manager` folder to the `/wp-content/plugins/` directory, or install the `wpcalibrate-events-manager.zip` file directly through the WordPress admin panel (**Plugins > Add New > Upload Plugin**).
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Access **WPCalibrate > Events** in the WordPress admin menu to start creating venues, organizers, and events.
4. Review default calendar preferences under **WPCalibrate > Events Settings**.

== Frequently Asked Questions ==

= Does WPCalibrate Events Manager require WooCommerce? =
No. Version 1 is completely independent of WooCommerce. It provides clean extension points so ticketing or paid registrations can be added in future modules.

= How does the recurrence engine prevent duplicate posts? =
Only one WordPress post is created for each event. The recurrence engine calculates occurrence instances and indexes them in a lightweight, dedicated custom database table.

= Can I customize the calendar accent color? =
Yes. Navigate to **WPCalibrate > Events Settings > Display** and select your preferred brand accent color.

= How do I display the calendar on a page? =
You can insert the "WPCalibrate Calendar" Gutenberg block into any page or post, or use the shortcode `[wpcalibrate_calendar]`.

== Changelog ==

= 1.0.0 =
* Initial production release.
* Added custom occurrence database table schema with dbDelta migrations.
* Added deterministic recurrence engine supporting daily, weekly, monthly, and yearly intervals.
* Added Month, Week, Day, and List calendar views with AJAX navigation.
* Added Gutenberg Calendar and Upcoming Events blocks.
* Added OpenStreetMap embed integration.
* Added single event Schema.org JSON-LD structured data.
* Added .ics export, feed generator, and admin .ics import.
