=== Modern Events Calendar Lite — adventistai.lt ===
Contributors: adventistai.lt
Tags: calendar, events, google calendar
Requires at least: 6.6
Tested up to: 7.0.2
Requires PHP: 8.4
Stable tag: 7.35.1.32
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
= 7.35.1.32 =
* Security: the booking report's background requests, including one that sent e-mail from the site, are no longer available while bookings are switched off, as they are in this build. The frontend "Remove Event" button removes events only, never pages, posts or media.

= 7.35.1.31 =
* Security: the Import / Export forms and the Google Calendar sign-in return check that the request came from the site's own page. The frontend event form edits events only, never pages or posts. The unused Export & Purge booking tool is switched off.

= 7.35.1.30 =
* Fix: single event pages no longer log a PHP warning about the missing progress bar module; the module is included again.

= 7.35.1.29 =
* Security: the event bookings list is only shown to people who may edit the event, and booking lookups accept only numeric IDs and dates (CVE-2026-105067). Image and booking-link lookups use prepared queries.

= 7.35.1.28 =
* Fix: a weekly calendar request with an unusual month number (such as "001") no longer keeps the server busy until it times out.

= 7.35.1.27 =
* Fix: loading another month, loading more events or refining a search with a malformed request no longer ends in a "critical error" in any calendar view.

= 7.35.1.26 =
* Fix: a view-switching request with a malformed calendar view option no longer ends in a "critical error" page either.

= 7.35.1.25 =
* Fix: a malformed view-switching request no longer ends in a "critical error" page; the calendar ignores the malformed values and answers normally.

= 7.35.1.24 =
* Security: a crafted link to the calendar's view-switching request could run script in a visitor's browser on the site. Fixed, along with stricter number checks in the attendee report, Divi preview and Google import requests.

= 7.35.1.23 =
* Speed: the calendar widget prints only today's and later events of the month and builds the month/year pickers when opened, so the front page has about 180 fewer elements. Other days and the neighbouring months load from the calendar's own data, the next and previous months in the background once the calendar is on screen.

= 7.35.1.22 =
* Speed: the agenda's clock icon is drawn without the 30 KB icon font, so pages showing only the calendar widget no longer download it.
* Accessibility: the calendar has a heading for screen readers, and its event titles are level-3 headings, so the page's heading order no longer skips a level.

= 7.35.1.21 =
* Speed: the calendar icon font no longer holds back text rendering (font-display: swap, flagged by PageSpeed).

= 7.35.1.20 =
* Updates: "Check again" on Dashboard → Updates now finds a new calendar release straight away instead of up to 6 hours later.

= 7.35.1.19 =
* Agenda calendar: weekday letters and the days of the previous and next month are darker, so they are readable (WCAG AA contrast).
* Smaller downloads: the calendar's scripts are minified in the plugin package (for example the main front-end script is about 28 KB compressed instead of 41 KB).

= 7.35.1.18 =
* Agenda calendar (Weekly View): the small stylesheet is skipped, and the full one used, when the full one is already on the page or the small file is missing; sites can turn it off with the `mec_weekly_view_css` filter.

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
