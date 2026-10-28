# adventistai.lt fork changelog

## 7.35.1.33

- Security fixes from the 2026-10-27 WordPress administration and data security assessment (finding 3, static leads confirmed in code; upstream MEC behaviour, unchanged in the last public wordpress.org release, 6.5.6):
  - FES guest accounts (only with "Automatically create users after event publish" on): when an event was published, `status_changed()` read the `fes_guest_email` the submitter typed, which any FES user could send. An address that belonged to an existing account, the administrator's included, made that account the event's author; a new address got a new account with the Author role, which may publish posts and upload files. Now:
    - only events a logged-out guest submitted are considered. New FES events from guests are flagged with `mec_fes_guest_submission`, so a logged-in user's event never changes author. Guest events submitted before this release are not flagged and get no account when published;
    - an address that already has an account changes nothing; the guest name and email stay on the event, as before, for the moderator;
    - a new account gets the `subscriber` role, or what the new `mec_fes_guest_user_role` filter returns if that role exists. It still becomes the event's author, so its owner can edit the event through FES when subscribers are among the FES access roles; such edits are saved as pending, not published. WordPress sends the set-password link to the address, as before.
  - Event gallery upload (`mec_event_gallery_image_upload`, only with the event gallery on): it checked only the nonce and, for guests, guest submission. Any logged-in user with the nonce, which the event meta box prints for contributors, could add images to the Media Library. Users without `upload_files` now need FES submission access (`current_user_can_submit_event()`), the same gate as the FES featured image upload: guest submission for logged-out visitors, one of the FES access roles for logged-in users.
  - The new Playground test `.github/playground/fes_guest.py` covers both in WordPress.

## 7.35.1.31

- Security fixes from the 2026-10-21 audit of 7.35.1.30 (Wordfence's October advisories against MEC Lite < 7.37.0). These are the fork's own findings; they are not confirmed matches for a CVE.
  - Import / Export (A1): the Synchronization form saved its settings with no nonce check, and the Google import forms printed no nonce at all. Each now sends its own `mec_ix_nonce` (`mec_ix_sync`, `mec_ix_g_calendar_import`), and a request without it saves and imports nothing and says the session expired. The menu capability check stays. The Google export OAuth callback stored the site's tokens for any returned `code`; the authorization link now carries a `state` nonce (`mec_ix_g_calendar_oauth`), and the callback refuses a missing or wrong state with 403. Clearer messages explain how to restart authentication, and Google sign-in errors stop before any token request.
  - FES (A2): `current_user_can_upsert_event()` let a logged-in user rewrite any page, post or attachment they authored (title, content, excerpt and status) through `mec_fes_form`, even without `edit_post` on it. It now accepts only existing `mec-events` posts, for the edit check and the author fallback alike. The FES CSV export uses the same check.
  - Report (A3): the retained `admin_post_mec_export_purge` handler crashed on the removed `MEC_feature_books` class. Behind that class it would have deleted whole bookings that only partly fell before the cutoff and left the CSV at a guessable public uploads URL. The handler now answers 410 "not available" and touches nothing, and the report page no longer shows the Export & Purge form.
  - The Playground bookings test (`.github/playground/bookings.py`) covers all three in WordPress, plus the `mec_attendees` access checks.

## 7.35.1.30

- Fix: single event pages logged `include(.../app/modules/progress-bar/single.php): Failed to open stream` (73 warnings on adventistai.lt on 2026-10-19, after the update from 7.35.1.9). The slim release left out `app/modules/progress-bar/`, but the single event skins (`single/default.php`, `modern.php`, `m1.php`, `m2.php`) and the list skin always call `MEC_main::display_progress_bar()`.
  - The module ships again (2 files). It is the "Enable progress bar module" option under Settings → Modules, which shows how far an ongoing event has run. It renders nothing unless that option is on, and could not work while the file was missing.
  - `MEC_main::module()` returns an empty string for a module file that doesn't exist. That covers the booking, attendees-list and qrcode modules, which stay out of the slim release. In WordPress Playground with default settings, a single event page on the slim build also asked for `attendees-list` and `qrcode`.
  - Verified in Playground (PHP 8.4.25) on copies built like the release: before, a single event page logged 12 include warnings (`progress-bar`, `attendees-list`, `qrcode`); after, none, with the progress bar setting off or on.

## 7.35.1.29

- Security: CVE-2026-105067 (Modern Events Calendar Lite < 7.37.0, authenticated SQL injection; Wordfence and Patchstack, published 2026-10-12). The advisories don't name the vulnerable code, and the upstream 7.37.0 change isn't public. The fork's matching paths are fixed here:
  - `MEC_main::get_bookings()` concatenated `event_id`, `timestamp`, `user_id` and `limit` into SQL. They are now cast to integers. A missing or non-positive event ID, or a non-numeric timestamp, returns no bookings, the same result the broken query used to give. This covers all 14 callers.
  - `wp_ajax_mec_event_bookings` (the attendees box, registered when the booking module is on) had no nonce and no capability check, so any logged-in user could list any event's attendee names and emails. It now needs the `mec_event_bookings` nonce, which the attendees box sends, and edit rights on the event (or, as in FES, being its logged-in author).
  - The FES CSV export's `timestamp` is now `absint`.
  - The attendees box clears the previous list and shows a message when a request is refused.
- Found by the Codex cross-review, same class, also fixed (prepared with `%s`):
  - `MEC_main::get_attach_id()` put an image URL into a quoted SQL string unescaped. FES submissions reach it through the featured image (`set_featured_image()`), as does the term importer.
  - The booking verification and cancellation endpoints (`do_endpoints()`, `verify`/`cancel` query vars) did the same with the key from the URL.
- Not established: which code the advisory means. This release fixes every matching path found in the fork, but Wordfence compares versions, so it keeps flagging 7.35.1.x against "< 7.37.0". If the alert is ignored on the site, record that it is based on this review, not on the vendor's patch.

## 7.35.1.28

- Robustness, follow-up to 7.35.1.27. The darius@ cross-review of PR #39 found these cases; #39 was merged before the fix reached it. `request_date_part()` in 7.35.1.27 accepted any 1–4 digits.
  - `mec_month=001` or `102` (or `sf[month]=001`, which `sf_apply()` copies into it) made `strtotime()` read `Y-001-01` as an ordinal date. The weekly view's week loop (`weekly_view/tpl.php`) then ran past the week's last day: `strtotime(null)` is false, so it never ended and held a PHP worker until the time limit. 7.35.1.26 did the same.
  - Month `0`/`13` and years such as `20260` also got through.
  - Now a year must be four digits and a month 1–12 (`7` or `07`); anything else gets the handler's default. The week loop also stops at the week's last day.
- `MEC_search::refine()` keeps `sf[fields]` again. `frontend.js` sends it, and refine doesn't read it, but the `mec_map_*_query` filters may. 7.35.1.27 dropped it with the other array values.
- Verified in WordPress Playground (PHP 8.4.25, logged out) on the same code as this release:
  - 4,816 fuzz requests to 24 public actions, with 0 fatal and 0 hang. That is the 4,492 of 7.35.1.27 plus 324 digit edge cases (`001`, `102`, `0`, `00`, `13`, `7`, a 20-digit year, `20260`), sent as `mec_*` and as `sf[year]`/`sf[month]`.
  - The review's repros: on 7.35.1.26 the weekly requests got no reply in 60 s and a 20-digit year returned 500; now all return 200.
  - On the same site, 74 of 82 well-formed requests are byte-identical to 7.35.1.26. 7 print a random skin id (same length), and 1 is the removed general calendar action.

## 7.35.1.27

- Robustness (finishes what 7.35.1.25 listed as not covered; WordPress Playground, PHP 8.4.25, logged out): the other public AJAX actions read request values the same unchecked way, and a wrong-shaped value ended in a PHP fatal `TypeError` (HTTP 500). They are each view's `load_month`/`load_more` (monthly, weekly, daily, yearly, list, grid, tile, timetable and its weekly program, agenda, custom, masonry, timeline), `mec_map_get_markers` and `mec_refine_search_items`. The fuzz sent 4,492 single-value requests to 24 public actions: every att as `[]`, the array atts as strings, each option of the view's `sk-options` group as `[]`, every `sf` key as `[]`, the request scalars (`mec_month`, `mec_year`, `mec_next_page`, `mec_start_date`…) as `[]` and as junk strings, and nested `sf[fields]`. Before the fix, 1,099 failed and 3 never returned. After it, none fail and none hang. On the same Playground site, 74 of the run's 82 well-formed requests return byte-identical output before and after. Seven print a random skin id and vary between repeats either way, but keep the same length. The last is `mec_general_calendar_load_month` (below).
  - Every handler now passes request `sf` and `atts` through `MEC_skins::normalize_request_values()` with the same key lists as `switch_skin()` (34 reads in 13 skins).
  - `mec_year`/`mec_month` are read with a new `MEC_skins::request_date_part()`, which accepts 1 to 4 digits only (a 20-digit year overflowed to a float and still failed in `mktime()`) and otherwise uses the handler's existing default. `mec_year=x`, `[]` or a scalar `sf[year]=x` (copied into it by `sf_apply()`) reached `mktime()` in the monthly templates. The weekly view's `load_month` with an empty or array `mec_year` never returned: it held a PHP worker until the time limit.
  - `mec_next_page` must be a string before `trim()`/`explode()` (list, grid, tile, agenda, masonry, timeline).
  - `meta_query()` checks that `fields[<id>][date_min|date_max]` are strings before `trim()` (`sf[fields][5][date_min][]`).
  - `MEC_search::refine()` keeps only scalar `sf` values; it reads each as a string.
  - The general calendar registered `mec_general_calendar_load_month` (and its logged-in twin) for a `load_month()` method it never had, so every call was a fatal. The registration is removed; nothing in the plugin sends that action.
  - Not a security fix: it stops cheap 500s, error-log noise and the hung weekly request. Two PHP warnings that well-formed requests already raised are unchanged: `book.php` hour/minutes and the category walker in the search refine.

## 7.35.1.26

- Robustness (follow-up to 7.35.1.25, same Playground fuzz, PHP 8.4.25): a single option inside a skin's `sk-options` group sent as an array to `mec_full_calendar_switch_skin` still ended in a PHP fatal `TypeError` (HTTP 500). `sk-options[full_calendar][limit|include_local_time|display_price|image_size|monthly_style|start_date|date_format_list|end_date_type_*|maximum_date_range_*][]`, which `prepare_skin_options()` copies into the view, failed in `trim()` or as an array key; so did grid and list date formats, and tile `count`, `end_date_type`, `maximum_date_range` and `order_method`. 114 of 1,628 single-option requests (7 skins, every option of the view's and the full calendar's group, as `[]` and `[a][]`) failed; after the fix none do, and the 14 well-formed requests of the run (with a full `sk-options[full_calendar]` group, `yearly_view[months][1..12]` and `sf-options`) return byte-identical output. `MEC_skins::normalize_request_values()` now drops array values inside each skin's `sk-options` group, except the yearly view's `months[n]` list (the only array option in the shortcode form), which keeps its scalar entries. `sf-options` per-field values were fuzzed too (string, `[type][]`) and did not fail, so they are unchanged.

## 7.35.1.25

- Robustness (found by fuzzing all skin atts of the public `mec_full_calendar_switch_skin` action in WordPress Playground, PHP 8.4): a request att of the wrong shape ended in a PHP fatal `TypeError` (HTTP 500, "critical error"). `sk-options` or `sk-options[<view>]` sent as a string failed in `prepare_skin_options()` ("Cannot access offset of type string on string"), and 32 scalar atts (`category[]`, `show_past_events[]`, `s[]`…) or `sf[month]`/`sf[year]`/`sf[s]` sent as arrays failed in `trim()`. 228 of 1,239 single-att requests failed; after the fix none do, and the 548 well-formed requests of the run return the same output as before. `MEC_skins::normalize_request_values()` now drops such values before the skin starts: arrays are kept only for `sk-options`, `sf-options`, `icons`, `include`, `exclude` and `fields` (and `sf[fields]`), and the two option groups keep only their per-view arrays. Both `switch_skin()` handlers (full calendar and the archive's default full calendar) use it, and so do the two places in `MEC_render` that read `$_REQUEST['sf']` again (also used by the first page render, so `?sf[s][]=x` on a calendar page no longer fails either). Not a security fix; it stops cheap 500s and error-log noise. Not covered: single options inside a view's group sent as arrays (for example `sk-options[full_calendar][limit][]`) still fail; some options there are real arrays (`months`), so they need a per-option check. Also not covered yet: the other public skin AJAX actions (each view's `load_month`/`load_more`, such as `mec_monthly_view_load_month` or `mec_list_load_more`) still pass raw `atts`/`sf` to `sf_apply()`/`initialize()` and can fail the same way; `mec_month[]` and nested `sf[fields][<id>][date_min][]` are not checked either. A scalar `sf[sk-options]`/`sf[sf-options]` is dropped too (found in cross-review).

## 7.35.1.24

- Reflected XSS (found while triaging the code-scanning backlog; reproduced logged out in WordPress Playground): `wp_ajax_nopriv_mec_full_calendar_switch_skin` passes request `atts` to the skins and echoes HTML, and the daily view prints the skin id inside a JavaScript identifier (`var mec_daily_view_init_<id> = function()`), where `esc_js()` does not help, so a crafted `atts[instance_id]` in a logged-out link ran script. `MEC_skins::get_skin_dom_id()` now keeps only `[A-Za-z0-9_]` of `instance_id`/`id` (shortcode ids such as `mec1` are unchanged).
- Hardening, same triage: `mec_booking_calendar_load_month` (public in the source, but `bookingcalendar.php` is excluded from the release ZIP) casts `event_id`, `year` and `month` with `absint()` before they reach its SQL and checks the month with `checkdate()` (the string was only protected by WordPress' magic quotes); the attendee report's `report_event_dates` casts `event_id` (it reached SQL and an inline `onchange`); the Divi shortcode preview casts `shortcode_id`; the Google import's two lookups use `$wpdb->prepare()` (`%s` Google event ID, `%d` post ID).

## 7.35.1.23

- Magenda DOM size (Lighthouse "Optimize DOM size": on the front page the calendar was 375 elements / 69 KB, with all 16 October events, 11 of them past, and two hidden pickers): the page's agenda starts at today. `weekly_view/tpl.php` sets `magenda_agenda_from` to the site's date for the page render only (not for the `mec_weekly_view_load_month` reply, and not for `show_only_past_events` calendars), and `weekly_view/render.php` skips earlier days. The grid's per-day counts are unchanged. Under page caching (Cloudflare) that date can only fall behind, so a cached page still holds today's events. The client then moves "today" forward: `data-timezone` (`wp_timezone_string()`) gives it the site's date, which is used only when it is 1–2 days after the page's (a cache keeps a page for hours; a wrong browser clock can't move it further or back). A new month is then loaded straight away; `cur-year`/`cur-month` change only once it is shown, so if that request fails the page's month and its day stay as they are.
- The month and year pickers (2 + 25 elements) are no longer printed. `frontend.js` builds them in the same place when a label is first opened, using `data-month-names`.
- The month replies of `mec_weekly_view_load_month` are kept in memory for 10 minutes and parsed in an inert document, so their images do not download until shown. Once a month has loaded, only the selected day's agenda is in the DOM. Other days, including the past days of the current month, are inserted from memory when selected, and a day picked while its month is loading is kept. The visible month is still refreshed on load. After that, once per calendar, when it first comes on screen (`IntersectionObserver`, 10 %; never without it) and the browser is idle (`requestIdleCallback`, or 1.5 s), the previous and next months are prefetched. These admin-ajax requests are not cached by Cloudflare and each runs the event query, so calendars that are never seen don't send them. The month an arrow leads to is also prefetched when the pointer or keyboard reaches it. Neither prefetch runs with Save-Data; a click still loads its month. Prev/next then switch at once without a request. Only the last month asked for is shown, whatever order the replies arrive in.

## 7.35.1.22

- Simple Line Icons (Lighthouse "Network dependency tree": `Simple-Line-Icons.woff2`, 30 KB, end of a ~1.07 s chain): the clock (`.mec-sl-clock-o`) is the only icon the magenda agenda shows, so inside `.mec-magenda` it is drawn as an SVG mask in `currentColor` and its element no longer uses the icon font. Pages whose only MEC output is the magenda then never download the font. Added to the magenda section of `frontend.css`/`frontend.min.css`; `magenda.min.css` regenerated.
- Heading order (Lighthouse "Heading elements are not in a sequentially-descending order"): Weekly View event titles were `<h4>` straight after the page's `<h1>`/`<h2>`. `weekly_view/render.php` prints them as `<h3>` (same computed style on adventistai.lt), and `magenda_render_shell()` starts the calendar with a visually hidden `<h2 class="mec-magenda-sr-title">` ("Renginių kalendorius" in Lithuanian, "Events calendar" otherwise).

## 7.35.1.21

- Icon fonts (PageSpeed "Font display", ~130 ms): every `@font-face` for Simple Line Icons (and FontAwesome in `iconfonts.css`) gets `font-display: swap`, in `frontend.css`/`frontend.min.css`, the RTL pair and `iconfonts.css`; `magenda.min.css` regenerated with `tests/build-magenda-css.js`. Lighthouse/DevTools only accept `swap` or `optional`; `optional` could leave the magenda arrows without icons on a slow first visit.

## 7.35.1.20

- Updates: "Check again" on Dashboard → Updates (`update-core.php?force-check=1`) now finds a new GitHub release at once. WordPress' button only forces its own core check, and the plugin check reused WordPress' update data (re-checked at most once a minute there) and this updater's 6-hour release cache. `force_check()` on `load-update-core.php` priority 9 (before `wp_update_plugins` at 10) deletes the `update_plugins` transient for users who can update plugins, which also clears this updater's cache through `clear_cache()`.

## 7.35.1.19

- Magenda contrast (WCAG AA, flagged by PageSpeed): weekday letters #9ca3af → #646b78 (2.4:1 → 5.1:1) and other-month day numbers and their event-count badges #c3c8d1 → #676e7a (1.7:1 → 5.1:1 on white, 4.6:1 on the Saturday tints), in `frontend.css`/`frontend.min.css`; `magenda.min.css` regenerated.
- Release package: `scripts/minify-package-js.cjs` minifies every `assets/**/*.js` that is not already `*.min.js` inside the staged package with esbuild 0.28.2 (pinned in `package-lock.json`, installed with `npm ci --ignore-scripts`; build tooling only, not shipped). Whitespace, syntax and local names only; top-level globals such as `mecSingleEventDisplayer` and the syntax level are unchanged, and each result is parsed once. 19 scripts, 1,115,355 → 706,950 bytes; `frontend.js` 27.8 KB gzipped (PageSpeed listed 40.7 KB), `flipcount.js` 3.8 KB, `featherlight.js` 3.6 KB. The repository keeps the readable sources.

## 7.35.1.18

- Trimmed magenda stylesheet, safety guards from PR #20: the full `frontend.min.css` (and `iconfonts.css`) stay when `mec-frontend-style` was already queued or printed, when `assets/css/magenda.min.css` is unreadable, or when the `mec_weekly_view_css` filter returns false. PR #20's alternative 48.6 KB bundle and its PostCSS tooling were not adopted; `docs/weekly-view-css.md` describes the kept approach.

## 7.35.1.17

- Lean assets, styles: pages whose only MEC output is the magenda (Weekly View, no search form, LTR) load `assets/css/magenda.min.css` instead of `frontend.min.css` (597 KB) and `iconfonts.css` (48 KB). It holds every magenda rule, the base MEC rules and icon glyphs the magenda uses, and the `@font-face` rules: 23.9 KB, 4.5 KB gzipped. `tests/build-magenda-css.js` builds it from `frontend.min.css` (and `--check` in CI keeps it in step). Computed styles of every magenda element matched the full stylesheet on adventistai.lt's front page (several months, open month picker, selected day, 340 px width). The event popup's page needs the full styles: `mecdata.full_styles` lists both files and `mecSingleEventDisplayer.loadFullStyles()` adds them when a popup opens, or earlier when the pointer or keyboard reaches an event link.
- Lean assets, scripts (PR #18): on those pages featherlight, flipcount, the tooltip script and frontend.js load with `defer`.

## 7.35.1.16

- Magenda (Weekly View): with the single event display set to the popup, MEC gives event links `target="m1"`. The magenda had no handler for it, so the browser opened a new window named "m1". A delegated click handler now calls `mecSingleEventDisplayer.getSinglePage()` (MEC's featherlight popup), as the other skins do; Ctrl/Cmd/Shift/Alt/middle-click still open the event page normally. Without featherlight it falls back to the event page in the same tab.
- Lean assets: when a rendered skin uses the popup, featherlight (JS and CSS) and flipcount are kept.

## 7.35.1.15

- Assets: when assets load in the footer and a page's only MEC output is the magenda (Weekly View) without a search form, MEC no longer loads jquery.typewatch, featherlight, select2, the jQuery UI datepicker, events.js, lity, colorbrightness, owl carousel or flipcount. The magenda's code in frontend.js uses none of them, and its event links are plain links. That page now loads 7 MEC files instead of 19. Other skins, search forms, other MEC shortcodes and event, archive and taxonomy pages keep the full set. A search form is detected from the rendered output, because `sf_status` is on by default even with no search fields.

## 7.35.1.14

- Block editor: the `mec/blockeditor-N` shortcode blocks register with block API 3 and wrap their edit view in `useBlockProps()`, so WordPress 6.9+ no longer logs "Block with API version 2 or lower is deprecated" for each of them. The saved shortcode is unchanged; the script also depends on `wp-block-editor` and `wp-element` in the block editor.

## 7.35.1.13

- PHP 8.4 deprecations: `(boolean)` casts in `app/features/archive.php`, `events.php`, `mec.php` (2), `app/libraries/skins.php` and `app/skins/tile.php` are now `(bool)`. The WordPress Playground smoke test added in PR #13 found them in `debug.log`.

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
