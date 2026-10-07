=== Modern Events Calendar Lite — adventistai.lt ===
Contributors: adventistai.lt
Tags: calendar, events, google calendar
Requires at least: 6.6
Tested up to: 7.0.2
Requires PHP: 8.4
Stable tag: 7.35.1.17
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
= 7.35.1.17 =
* Faster pages with only the agenda calendar (Weekly View): they load a small stylesheet with just the agenda's styles (about 25 KB instead of 640 KB, 5 KB compressed) and no icon-font stylesheet; the full styles load when an event popup opens. The agenda's scripts also no longer hold up the page.

= 7.35.1.16 =
* Agenda calendar (Weekly View): when events are set to open in a popup, they now open in the popup on the same page instead of a new browser window.

= 7.35.1.15 =
* Faster pages with only the agenda calendar (Weekly View): the search, carousel, lightbox, countdown and event-form libraries it does not use are no longer loaded there (7 calendar files instead of 19).

= 7.35.1.14 =
* Block editor: the calendar shortcode blocks use the current block API, so the editor no longer logs a deprecation warning for each of them.

= 7.35.1.13 =
* PHP 8.4: replace six `(boolean)` casts, which PHP 8.4 deprecates and which logged a notice on calendar pages, with `(bool)`.

= 7.35.1.12 =
* Security: logged-in accounts without the right permissions can no longer run the Google Calendar export, read attendee lists, export another event's bookings, import the setup wizard's demo events or create event categories.
* Running the Google Calendar export before authenticating now shows a message instead of a PHP error.
* The vendored libraries' GitHub templates are no longer shipped.

= 7.35.1.11 =
* Security: a crafted link could add styling and other attributes to the calendar search box. The search values are now escaped.
* Security: logged-out visitors can no longer upload images or add speakers and sponsors unless guest event submission is enabled.
* Security: draft, private and password-protected events can no longer be opened through the event pop-up or the e-mail calendar export.
* After activation the plugin opens its dashboard instead of the removed setup wizard, which showed an error page.

= 7.35.1.10 =
* Faster pages: with assets in the footer, calendar files load only on pages that show a calendar.
* The General Calendar library loads only where that view is shown.
* Removed unused libraries, the old update checker and unused translations.
* Fixed "MS Excel Export" in the Events list failing with a PHP error.

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
