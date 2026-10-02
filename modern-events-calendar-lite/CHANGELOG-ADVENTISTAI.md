# adventistai.lt fork changelog

## 7.35.1.12

Security fixes from a review of the AJAX actions any logged-in account can call. Items 1-5 were reproduced on 7.35.1.11 with a subscriber and an author account, then re-tested:

1. `mec_ix_add_to_g_calendar` ran the Google Calendar export (optionally with attendees) for any logged-in user. The export and the Google settings save (`mec_ix_g_calendar_authenticate`) now need `manage_options` or `mec_import_export` and a nonce from the Import / Export page. The settings save itself was already protected by `save_ix_options()`.
2. The OAuth callback (`?mec-ix-action=google-calendar-export-get-token`) exchanged codes for anyone, even logged out. It now needs the same access.
3. `mec_attendees` returned any event's attendee table. It now needs `mec_report` and the admin nonce, as `report_event_dates` does.
4. `mec_fes_csv_export` exported any event's bookings, guarded only by `fes_nonce`, which every page prints. It now needs an event the user can edit, or authored while logged in.
5. `wizard_import_dummy_events` and `_shortcodes` published 5 demo events and 15 calendars per call, with no checks. The wizard is not shipped, so they are no longer registered.
6. `mec_popup_event_category` created categories for anyone. It now needs the event popup's nonce and the right to create categories (editors and administrators). The category name is now URL-encoded, so names with `&` work.
7. Hardening, not reachable in this Lite build (the occurrences feature needs Pro): `mec_occurrences_add` put the event ID and date unescaped into SQL, and `save()` updated occurrence rows by ID alone with unescaped JSON. Values are now integers or escaped, rows are limited to the event being saved, and add/delete check `edit_post`.
- The Google Calendar export no longer fails with a PHP fatal error when run before authenticating.
- The package no longer ships vendored `.github` folders (PR #10).

## 7.35.1.11

Security fixes from an audit of everything a logged-out visitor can reach:

- Attribute injection: the search form printed `sf[s]`, `sf[address]` and the price filters from the URL into `value="..."` without escaping. The form passes through `MEC_kses::full`, which removed event handlers, so no script ran, but a crafted link could add `style`, `class` and `data-*` attributes (for example, a full-page overlay). The values are now escaped.
- Every calendar page gives all visitors the frontend-submission nonces. `current_user_can_submit_event()` returned true for logged-out visitors even with guest submission off, so anyone could upload images (`mec_fes_upload_featured_image`) and create speaker and sponsor terms. Logged-out visitors are now refused unless guest submission is enabled. The event form, the featured-image upload and the event-gallery upload enforce the same rule.
- The frontend form let a logged-out visitor edit any event whose author is 0, because `0 === 0` matched. Author matching now needs a logged-in user.
- `mec_load_single_page` (public AJAX) rendered any post ID, including drafts, private and password-protected events. It now renders only events the visitor could open anyway.
- `?method=ical-email` used `md5(book_id)` as its key, which anyone can compute, and exported any event ID. It now requires a published event that the booking belongs to.
- Hardening: the "load more" offset is cast to an integer; the unused attendee query builder escapes its `IN` values; the taxonomy import form escapes its URL.
- All admin redirects use `wp_safe_redirect`. After activation the plugin redirects to its dashboard instead of the removed setup wizard, which showed a "WordPress Error" page.

## 7.35.1.10

- With "assets in footer", calendar scripts and styles load only on pages that show calendar output, or on event, archive and taxonomy pages. A plain post went from about 1.6 MB of calendar files to none.
- The General Calendar library (about 300 KB) loads only where the General Calendar view is shown.
- The filter `mec_frontend_assets_needed` forces the assets on when needed.
- Removed unused code from the repository:
  - the Twilio, Stripe, TFPDF, Campaign Monitor, Meetup and add-on catalogue libraries (the package already left them out);
  - the original plugin's update checker (`app/core/puc`), which nothing loaded;
  - 14 unused translation languages.
- The package also leaves out the unminified `frontend.css`, `backend.css`, `a11y.css` and `a11y-backend.css`. Only the `.min` files are loaded.
- Fixed "MS Excel Export" in the Events list. The package had left out its XLSX writer (52 KB), so the export ended in a PHP error. The writer now ships.

## 7.35.1.9

- Update checks no longer require a GitHub token. The repository is public, so the updater ran into its own token requirement and reported nothing on every site without one.
- Always send the GitHub API headers, so an unauthenticated release download receives the ZIP instead of JSON metadata.
- Replaced the standing "token missing" notice with one that appears only when an update check actually fails, and says why.

## 7.35.1.8

- Moved System Information and Debug Log onto the M.E. Calendar dashboard.
- Removed the standalone Support submenu, page template, and related admin-screen wiring.
- Fixed an undefined language variable and an undeclared load-more property in the general calendar skin.

## 7.35.1.7

- Removed the Setup Wizard from the admin menu and install package.
- Restricted Import / Export routes and synchronization settings to Google Calendar.
- Excluded legacy Facebook, Meetup, XML, third-party, and test-data screens from the install package.

## 7.35.1.6

- Removed the vendor tutorial video, license/add-on prompts, and upstream changelog from the calendar dashboard.
- Reduced Support to System Information and Debug Log.
- Made the Debug Log panel safe when the debug log does not yet exist.


## 7.35.1.5

- Run nested GitHub package selection before WordPress validates the plugin.
- Invalidate updater metadata cached by the earlier zipball-based implementation.
- Use a release asset only when its tag matches the version on `main`.
- Build future release names dynamically from the plugin header.


## 7.35.1.4

- Slimmed the package to calendar rendering and Google Calendar integration.
- Removed booking, attendee, payment, WooCommerce, SMS, PDF, promotion, and unrelated integration payloads.
- Retained Lithuanian-first behavior plus English and Russian catalogs.
- Replaced image-heavy admin skin previews with compact color swatches.
- Raised the supported runtime to PHP 8.4.


## 7.35.1.3

- Prevented the Lithuanian “Šiandien” navigation label from wrapping.
- Added horizontal scrolling for the seven-column General Calendar when
  desktop zoom makes it wider than its content area, without changing the
  existing touch/mobile layout.
- Made the mini calendar refresh its initially displayed month from the same
  live endpoint used for month navigation, avoiding incomplete event badges
  from stale or partial page markup.
- Kept mini-calendar AJAX responses in Lithuanian regardless of a logged-in
  WordPress user's profile language.

## 7.35.1.2

- Reviewed the official Webnus changelog through 7.36.2, published on
  1 September 2026.
- Backported the relevant 7.36.2 Lite-package correction by removing the
  vendor Pro-license AJAX endpoints, activation API, and leftover settings
  status UI from this Lite-only fork.
- Intentionally skipped the new Webnus license system, Pro booking and Stripe
  work, marketing/tracking additions, and unrelated editor redesigns.
- This remains a selective 7.35.1-based fork; version 7.35.1.2 does not claim
  to contain the complete upstream 7.36.2 release.

## 7.35.1.1

- Merged the General Calendar date and navigation controls into the blue
  header, reducing the desktop navigation panel from four rows to three.
- Limited arrows to previous/next month navigation and fixed their positions.
- Made the displayed year a direct year selector.
- Reduced year-tab event-count work from twelve database queries to one.
- Replaced Webnus update checks with authenticated private GitHub updates.
- Rebranded plugin ownership to adventistai.lt.
- Removed active Pro promotions, vendor marketing requests, and telemetry.
- Retained the official MEC 7.35.1 security fixes, including the fixes shipped
  in 7.34.0 and 7.35.0 for unauthenticated SQL injection vulnerabilities.
