# WPCalibrate Events Manager

[![WordPress](https://img.shields.io/badge/WordPress-6.4%2B%20to%207.1.3-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.2%20%7C%208.3-777bb4.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPLv2%2B-orange.svg)](LICENSE.txt)
[![Release](https://img.shields.io/badge/Release-v1.0.0-green.svg)](https://github.com/zeeshanraza-official/wpcalibrate-events-manager/releases)

A responsive, high-performance, and extensible event calendar and event management engine for WordPress by [WPCalibrate](https://wpcalibrate.com).

---

## About

**WPCalibrate Events Manager** is an enterprise-grade event calendar and management plugin built from the ground up for modern WordPress installations.

Many existing WordPress event calendar plugins suffer from significant architectural bottlenecks:
- Creating one WordPress post per occurrence, bloating the `wp_posts` table by thousands of rows.
- Storing date ranges only in `wp_postmeta`, which causes unindexed, full-table scans that cripple site performance.
- Failing to cleanly handle timezones, causing multi-day or all-day events to shift calendar days across Daylight Saving Time (DST) changes.

**WPCalibrate Events Manager solves this cleanly with a hybrid architecture**:
1. **Canonical Post Types**: Events (`wpcal_event`), Venues (`wpcal_venue`), and Organizers (`wpcal_organizer`) maintain all content, categories, tags, and settings without row duplication.
2. **Custom Indexed Occurrence Table**: A dedicated table (`wp_wpcalibrate_event_occurrences`) indexes every datetime occurrence instance with dual UTC and local timestamps. Range queries (`start <= range_end AND end >= range_start`) are lightning fast even on sites with tens of thousands of occurrences.
3. **Deterministic Recurrence Engine**: Mathematical occurrence expansion supports daily, weekly, monthly, and yearly recurring patterns with bounded safety limits (preventing runaway loops) and non-destructive exception handling.

---

## Features

### Flexible Event Formats
- **One-Time & Recurring Events**: Single dates or recurring series (Daily, Weekly multi-weekday, Monthly day-of-month and Nth-weekday, and Leap-year aware Yearly).
- **All-Day & Timed Events**: Exact local wall-clock span guarantees that all-day events remain pinned to their local calendar dates without timezone shifting.
- **Physical, Online & Hybrid**: Configure physical venues, streaming URLs, or both.

### High-Performance Occurrence Engine
- **Dedicated Indexed Schema**: Separate SQL table with compound indices on `(status, start_datetime_utc, end_datetime_utc)` for instant calendar queries.
- **Non-Destructive Exceptions**: Exclude specific holiday dates or reschedule individual occurrences without breaking the parent recurrence series.
- **Safety Ceilings**: Automatic bounded ceilings (default 730 occurrences / 2 years) for series configured with no end date.

### Modern Responsive Presentation
- **Calendar Views**: Interactive **Month**, **Week**, **Day**, and **List/Agenda** views.
- **AJAX Navigation**: Instant asynchronous month and week switching without full page reloads.
- **Mobile Optimized**: Touch-friendly layouts with automatic accordion collapse on compact viewports.
- **Custom Accent Colors**: Tailor the calendar's brand colors directly from the admin display settings.

### Native Gutenberg & Shortcode Support
- **Block Editor Integration**: Native Gutenberg blocks for **WPCalibrate Calendar** (`wpcalibrate/calendar`) and **Upcoming Events** (`wpcalibrate/upcoming-events`).
- **Shortcodes**: Full shortcode support (`[wpcalibrate_calendar]` and `[wpcalibrate_upcoming_events]`) compatible with Elementor, Divi, Beaver Builder, and classic content editors.

### Reusable Venues & Organizers
- **Entity Post Types**: Separate administrative management for physical venues and event organizers.
- **OpenStreetMap Embeds**: Interactive map embeds out of the box with zero third-party API keys or paid subscriptions required.
- **Coordinates & Address Resolution**: Latitude and longitude storage with automatic visual fallback.

### Standards-Compliant iCalendar Feeds
- **Single Event .ics Export**: 1-click download of RFC 5545 `.ics` calendar files.
- **Add to Google Calendar**: Direct URL creation with encoded parameters.
- **Live Public Subscription Feed**: Subscribe to upcoming site events from Apple Calendar, Microsoft Outlook, or Google Calendar.
- **Administrative .ics Importer**: Safely import external calendar events via WordPress admin tools.

### SEO & Accessibility
- **Schema.org JSON-LD**: Automatically injects valid `Event` structured data into single event pages for rich Google Search event cards.
- **WCAG 2.2 AA Accessibility**: Full keyboard navigation, ARIA attributes, semantic table landmarks, and high-contrast styling.

### Automatic In-Dashboard Updates
- Built-in GitHub Release Updater service: Automatically detects new GitHub releases and allows 1-click updates directly inside the WordPress dashboard (**Plugins > Update Available**).

---

## System Requirements

| Component | Minimum Version | Recommended Version |
| :--- | :--- | :--- |
| **WordPress** | 6.4+ | 6.7+ / 7.1+ |
| **PHP** | 8.2 | 8.2 or 8.3 |
| **MySQL / MariaDB** | MySQL 5.7+ / MariaDB 10.3+ | MySQL 8.0+ / MariaDB 10.6+ |
| **PHP Extensions** | `mbstring`, `json`, `date`, `zip` | Standard on modern hosting |

---

## Documentation & Usage

### Installation

#### Method 1: WordPress Admin Upload (Recommended)
1. Download the latest release package: [`wpcalibrate-events-manager-1.0.0.zip`](https://github.com/zeeshanraza-official/wpcalibrate-events-manager/releases/latest).
2. In your WordPress admin dashboard, go to **Plugins** &rarr; **Add New Plugin** &rarr; **Upload Plugin**.
3. Select the downloaded ZIP file and click **Install Now**.
4. Click **Activate Plugin**.

#### Method 2: Git Clone
```bash
cd wp-content/plugins
git clone https://github.com/zeeshanraza-official/wpcalibrate-events-manager.git wpcalibrate-events-manager
```

---

### In-Dashboard Automatic Updates

This plugin includes a native GitHub Release Updater. When a new release is published to GitHub:
1. WordPress will display an **"Update Available"** notice in **Plugins** (`wp-admin/plugins.php`).
2. Click **Update Now** to download and unpack the latest release automatically.
3. The plugin directory is seamlessly preserved without losing your events or calendar configuration.

---

### Shortcode Reference

#### 1. Full Calendar View: `[wpcalibrate_calendar]`

Embeds the full interactive calendar with view switching and AJAX navigation.

```text
[wpcalibrate_calendar]
```

##### Supported Attributes:
| Parameter | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `view` | `string` | `'month'` | Initial view: `'month'`, `'week'`, `'day'`, or `'list'`. |
| `category` | `string` | `''` | Filter events by category slug (comma-separated). |
| `limit` | `int` | `100` | Maximum occurrences loaded per view range. |

##### Example:
```text
[wpcalibrate_calendar view="list" category="workshops,webinars"]
```

#### 2. Upcoming Events List: `[wpcalibrate_upcoming_events]`

Embeds a compact agenda widget showcasing upcoming events.

```text
[wpcalibrate_upcoming_events limit="5" show_venue="true" show_thumbnail="true"]
```

##### Supported Attributes:
| Parameter | Type | Default | Description |
| :--- | :--- | :--- | :--- |
| `limit` | `int` | `5` | Maximum number of upcoming occurrences to display. |
| `category` | `string` | `''` | Filter by event category slug. |
| `show_venue` | `bool` | `true` | Display venue name and location. |
| `show_thumbnail` | `bool` | `true` | Display event featured image thumbnail. |

---

### Gutenberg Blocks

The plugin provides two native block types:
1. **WPCalibrate Calendar** (`wpcalibrate/calendar`): Add to any page to render an interactive calendar with sidebar controls for default view mode.
2. **Upcoming Events** (`wpcalibrate/upcoming-events`): Display an upcoming events list with real-time controls for count, venue visibility, and thumbnail toggling.

---

### Admin Navigation & Settings

Access the plugin from the WordPress admin menu under **WPCalibrate > Events**:
- **Events**: Manage all canonical events, recurrence schedules, exceptions, venues, and organizers.
- **Venues**: Manage physical locations, addresses, coordinates, and OpenStreetMap displays.
- **Organizers**: Manage event hosts, contact information, phone numbers, and websites.
- **Categories & Tags**: Dedicated taxonomies for structuring your calendar events.
- **Events Settings**:
  - **Overview**: Complete documentation, shortcode guide, and quick start reference.
  - **General**: Timezone defaults, date format, and time format.
  - **Display**: Default calendar view, start of week, and custom brand accent color.
  - **Maps**: OpenStreetMap tile layer configuration and zoom levels.
  - **iCalendar**: Feed endpoints, feed limits, and cache TTL settings.
  - **Licensing**: License key management and status.

---

### Architecture & Database Schema

The plugin maintains an optimized occurrence table created upon activation:

```sql
CREATE TABLE {$wpdb->prefix}wpcalibrate_event_occurrences (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    event_id bigint(20) unsigned NOT NULL,
    start_datetime_utc datetime NOT NULL,
    end_datetime_utc datetime NOT NULL,
    start_datetime_local datetime NOT NULL,
    end_datetime_local datetime NOT NULL,
    timezone varchar(64) NOT NULL DEFAULT 'UTC',
    all_day tinyint(1) NOT NULL DEFAULT 0,
    status varchar(20) NOT NULL DEFAULT 'publish',
    is_exception tinyint(1) NOT NULL DEFAULT 0,
    exception_type varchar(20) DEFAULT NULL,
    sequence int(10) unsigned NOT NULL DEFAULT 0,
    created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY event_id (event_id),
    KEY start_utc (start_datetime_utc),
    KEY end_utc (end_datetime_utc),
    KEY range_utc (start_datetime_utc, end_datetime_utc),
    KEY status_range (status, start_datetime_utc, end_datetime_utc),
    KEY event_sequence (event_id, sequence)
);
```

---

### Developer Hooks & Filters

#### Action Hooks
- `wpcalibrate_events_activated`: Fires upon plugin activation and schema installation.
- `wpcalibrate_events_deactivated`: Fires upon plugin deactivation.
- `wpcalibrate_occurrences_rebuilt` (`int $event_id`, `int $saved_count`): Fires when occurrences for an event are regenerated.
- `wpcalibrate_events_migrated` (`string $from_version`, `string $to_version`): Fires when database schema migrations complete.

#### Filter Hooks
- `wpcalibrate_events_post_type_args` (`array $args`): Customize Event post type registration arguments.
- `wpcalibrate_venues_post_type_args` (`array $args`): Customize Venue post type registration arguments.
- `wpcalibrate_organizers_post_type_args` (`array $args`): Customize Organizer post type registration arguments.
- `wpcalibrate_category_taxonomy_args` (`array $args`): Customize Event Category taxonomy arguments.
- `wpcalibrate_tag_taxonomy_args` (`array $args`): Customize Event Tag taxonomy arguments.

---

## Security Hardening

- **Capability Checks**: All administrative actions require `manage_options` or granular capability `manage_wpcal_events`.
- **Nonces Verification**: Every AJAX navigation request, form save, and settings update verifies cryptographically secure WordPress nonces.
- **SQL Sanitization**: All database interaction with `wp_wpcalibrate_event_occurrences` is prepared via `$wpdb->prepare()`.
- **Input Sanitization & Output Escaping**: All inputs sanitized via strict type checking and sanitization helpers; all outputs passed through `esc_html()`, `esc_attr()`, and `esc_url()`.
- **Sensitive Data Redaction**: Diagnostics and logging automatically redact license keys and authentication tokens.

---

## Changelog

### [1.0.0] - 2026-10-09
- Initial production release.
- Custom occurrence database table (`wp_wpcalibrate_event_occurrences`) with `dbDelta()` migrations.
- Deterministic recurrence engine supporting Daily, Weekly (multi-weekday), Monthly (day-of-month and Nth-weekday), and Yearly (leap-year aware) patterns.
- High-performance range query repository with dual UTC and local wall-clock datetime storage.
- Interactive calendar views: Month, Week, Day, and List/Agenda with AJAX navigation.
- Native Gutenberg blocks for Calendar (`wpcalibrate/calendar`) and Upcoming Events (`wpcalibrate/upcoming-events`).
- Shortcodes `[wpcalibrate_calendar]` and `[wpcalibrate_upcoming_events]`.
- Reusable Venues and Organizers custom post types.
- Free OpenStreetMap integration for venue mapping without API key requirements.
- Standards-compliant iCalendar: Single event `.ics` export, Google Calendar 1-click links, live subscribe feed, and admin `.ics` importer.
- Schema.org JSON-LD structured data for single events.
- Native GitHub Release Updater service for automatic 1-click in-dashboard updates.
- Shared WPCalibrate admin menu integration with collision prevention.

---

## License & Credits

- **Author**: [WPCalibrate](https://wpcalibrate.com)
- **Website**: [https://wpcalibrate.com](https://wpcalibrate.com)
- **Support**: [support@wpcalibrate.com](mailto:support@wpcalibrate.com)
- **License**: [GNU General Public License v2.0 or later](LICENSE.txt)
- **Copyright**: &copy; 2026 WPCalibrate. All rights reserved.
