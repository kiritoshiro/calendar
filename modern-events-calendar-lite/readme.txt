=== Modern Events Calendar Lite — adventistai.lt ===
Contributors: adventistai.lt
Tags: calendar, events, google calendar
Requires at least: 6.6
Tested up to: 7.0.2
Requires PHP: 8.4
Stable tag: 7.35.1.9
License: GPLv2 or later

Focused calendar build maintained by adventistai.lt.

== Included ==
* Event management and calendar views
* Google Calendar import, export, and synchronization only
* Lithuanian-first interface
* English and Russian catalogs
* Private GitHub repository updates

== Removed ==
Booking, attendees, payments, WooCommerce, SMS, PDF invoices, non-Google importers, vendor promotions, the Setup Wizard, and image-heavy skin previews are not shipped. Existing booking data is not deleted during upgrades.

== Installation ==
Upload the ZIP, activate it, then configure Google Calendar under M.E. Calendar → Import / Export. For private updates define ADVENTISTAI_CALENDAR_GITHUB_TOKEN in wp-config.php with repository read access.

== Changelog ==
= 7.35.1.9 =
* Fixed update checks never running: a GitHub token is no longer required.
* Fixed unauthenticated release downloads receiving JSON instead of the plugin ZIP.
* The update warning now appears only when a check fails, and states the reason.

= 7.35.1.8 =
* Moved System Information and Debug Log into the M.E. Calendar view.
* Removed the separate Support submenu and page.
* Fixed PHP warnings raised by the general calendar skin on every calendar request.

= 7.35.1.7 =
* Removed the Setup Wizard and restricted Import / Export actions to Google Calendar.
* Fixed WordPress package validation order and stale updater caching.
* Slim calendar-only package with Google Calendar integration.
* PHP 8.4 support.
