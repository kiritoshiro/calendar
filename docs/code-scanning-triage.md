# Code-scanning triage (2026-10-16)

The 271 alerts open on `main` at `2889e0c` (all created 2026-09-16, still reported by the current CodeQL and Semgrep runs) were reviewed one by one. Every alert is in `modern-events-calendar-lite/`. Of the 43 alert files, 10 carry fork commits since the import (`events.php`, `fes.php`, `ix.php`, `occurrences.php`, `popup.php`, `report.php`, `main.php`, `single.php`, `backend.js`, `frontend.js`); the other 33 are untouched upstream code. Fork-owned files were read with extra care, but the verdicts below rest on data flow, not ownership.

## Result

| Verdict | Alerts |
| --- | ---: |
| Fixed in this PR (code changed at the alert) | 6 |
| Fixed at the root (alert line unchanged) | 2 |
| False positive, PHP (Semgrep) | 63 |
| False positive, JavaScript security (CodeQL) | 109 |
| Third-party library (featherlight, clusterer, jQuery copy inside `flipcount-divi.js`) | 11 |
| Quality only (upstream code) | 80 |

### The real finding: reflected XSS in a public AJAX action

`wp_ajax_nopriv_mec_full_calendar_switch_skin` builds skins from request `atts` and echoes their HTML. `MEC_skins::get_skin_dom_id()` returned `atts['instance_id']` (or `atts['id']`) unchanged, and the daily view prints that id inside a JavaScript identifier (`var mec_daily_view_init_<id> = function()`), where `esc_js()` does not help. A logged-out GET link ran script on the site's origin (reproduced in WordPress Playground, PHP 8.3/WP latest, headless Chromium showed the dialog; after the fix it does not). Every other place the id is printed (HTML attributes, JS strings) escapes it correctly; a fuzz of all 71 skin attributes through the same action found no other unescaped context. Semgrep pointed at the `echo $output` of this action (#114, #115) but not at the cause.

Upstream's `daily_view.php` (WordPress.org trunk) sets `$this->id = $this->atts['id'] ?? mt_rand(100, 999)` and uses the same identifier, so stock Modern Events Calendar Lite may be affected as well; that has not been tested.

### Hardened in the same PR

- `bookingcalendar.php` (#52): public `mec_booking_calendar_load_month` concatenated `event_id`, `year` and `month` into SQL, protected only by WordPress' magic quotes. Now `absint()` + `checkdate()`. The file is excluded from the release ZIP, so this is source hygiene.
- `report.php` (#76, #77): `report_event_dates` (capability + nonce) put `event_id` into SQL and an inline `onchange`; now `absint()` (`none` kept).
- Divi preview (#12, `manage_options`): `absint()` on `shortcode_id`.
- Google import (#60, #61): both lookups use `$wpdb->prepare()` (`%s` for the Google event ID, `%d` for the post ID).

## Recommendation for the rest

1. **CodeQL: ignore third-party files.** Add `modern-events-calendar-lite/assets/packages/**` and `modern-events-calendar-lite/assets/js/flipcount-divi.js` to `paths-ignore` in `.github/codeql/codeql-config.yml`, next to the existing `*.min.js` rule. 29 alerts close by themselves on the next `main` analysis.
2. **CodeQL: `security-extended` instead of `security-and-quality`.** The 80 quality alerts are in upstream code nobody edits; they would close. Fork-owned JS stays covered for security.
3. **Dismiss the remaining false positives with the reasons below** (Semgrep 63 + CodeQL ~100, as "false positive", comment pointing to this file). Alerts #114/#115 can be closed as "fixed" once this PR is merged.
4. **Semgrep scope.** The Semgrep job scans all ~3,000 upstream files with `p/security-audit`. Uploading only results for fork-owned files (as PHPStan already does via `tools/linters/fork-owned-files.sh`) would keep new alerts meaningful; alternatively keep the full scan and rely on the dismissals.

## Alerts

| Rule | File | # | Alerts | Verdict |
| --- | --- | ---: | --- | --- |
| php/echoed-request | `app/skins/default_full_calendar.php` | 1 | 114 | Fixed at the root (`get_skin_dom_id`): this echo is where the injected daily-view script came out |
| php/echoed-request | `app/skins/full_calendar.php` | 1 | 115 | Fixed at the root (`get_skin_dom_id`): this echo is where the injected daily-view script came out |
| php/echoed-request | `app/addons/divi/includes/MECShortcodesForDivi.php` | 1 | 12 | Fixed in this PR |
| php/tainted-sql-string | `app/features/bookingcalendar.php` (not in the release ZIP) | 1 | 52 | Fixed in this PR |
| php/tainted-sql-string | `app/features/ix.php` | 2 | 60, 61 | Fixed in this PR |
| php/tainted-sql-string | `app/features/report.php` | 2 | 76, 77 | Fixed in this PR |
| js/automatic-semicolon-insertion | `app/core/src/Forms/backend.js` | 1 | 312 | Quality only (upstream code) |
| js/automatic-semicolon-insertion | `assets/js/flipcount-divi.js` | 3 | 314, 316, 317 | Quality only (upstream code) |
| js/automatic-semicolon-insertion | `assets/js/flipcount.js` | 1 | 313 | Quality only (upstream code) |
| js/automatic-semicolon-insertion | `assets/js/frontend.js` | 4 | 315, 318, 319, 320 | Quality only (upstream code) |
| js/bad-tag-filter | `assets/js/flipcount-divi.js` | 1 | 123 | Vendor library (third-party code, options set by the plugin) |
| js/comparison-between-incompatible-types | `assets/js/frontend.js` | 11 | 241, 242, 243, 244, 245, 246, 247, 248, 249, 250, 251 | Quality only (upstream code) |
| js/duplicate-property | `assets/js/googlemap.js` | 1 | 252 | Quality only (upstream code) |
| js/html-constructed-from-input | `assets/js/admin-calendar.js` | 2 | 193, 194 | FP: settings.id (esc_js) and month ids from the AJAX reply; no URL input |
| js/html-constructed-from-input | `assets/js/frontend.js` | 35 | 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215, 216, 217, 218, 219, 220, 221, 222, 223, 224, 225, 226, 227, 228, 229, 230, 231, 232, 233, 234, 235, 236, 237, 238, 239 | FP: settings.id (esc_js) and month ids from the AJAX reply; no URL input |
| js/html-constructed-from-input | `assets/js/googlemap.js` | 2 | 195, 196 | FP: settings.id (esc_js) and month ids from the AJAX reply; no URL input |
| js/html-constructed-from-input | `assets/packages/featherlight/featherlight.js` | 8 | 197, 198, 199, 200, 201, 202, 203, 204 | Vendor library (third-party code, options set by the plugin) |
| js/incomplete-sanitization | `assets/js/frontend.js` | 1 | 124 | FP: escapes regex brackets of constant parameter names |
| js/index-out-of-bounds | `assets/js/flipcount-divi.js` | 1 | 322 | Quality only (upstream code) |
| js/index-out-of-bounds | `assets/js/flipcount.js` | 1 | 321 | Quality only (upstream code) |
| js/missing-variable-declaration | `assets/js/frontend.js` | 3 | 291, 292, 293 | Quality only (upstream code) |
| js/missing-variable-declaration | `assets/js/googlemap.js` | 1 | 290 | Quality only (upstream code) |
| js/redundant-operation | `assets/js/backend.js` | 1 | 253 | Quality only (upstream code) |
| js/remote-property-injection | `assets/js/frontend.js` | 1 | 240 | FP: `__proto__` set to a string is a no-op; URL values only reach `.val()` |
| js/superfluous-trailing-arguments | `assets/js/frontend.js` | 1 | 311 | Quality only (upstream code) |
| js/trivial-conditional | `assets/js/flipcount-divi.js` | 4 | 263, 264, 265, 266 | Quality only (upstream code) |
| js/unneeded-defensive-code | `assets/js/admin-upcoming.js` | 1 | 254 | Quality only (upstream code) |
| js/unneeded-defensive-code | `assets/js/backend.js` | 1 | 258 | Quality only (upstream code) |
| js/unneeded-defensive-code | `assets/js/events.js` | 3 | 255, 256, 257 | Quality only (upstream code) |
| js/unneeded-defensive-code | `assets/js/frontend.js` | 2 | 259, 260 | Quality only (upstream code) |
| js/unreachable-statement | `assets/js/flipcount-divi.js` | 1 | 261 | Quality only (upstream code) |
| js/unsafe-jquery-plugin | `assets/js/frontend.js` | 34 | 137, 138, 139, 140, 141, 142, 143, 144, 145, 146, 147, 148, 149, 150, 151, 152, 153, 154, 155, 156, 157, 158, 159, 160, 161, 162, 163, 164, 165, 166, 167, 168, 169, 170 | FP: plugin options come from server-written inline JS, never from the URL |
| js/unsafe-jquery-plugin | `assets/js/googlemap.js` | 12 | 125, 126, 127, 128, 129, 130, 131, 132, 133, 134, 135, 136 | FP: plugin options come from server-written inline JS, never from the URL |
| js/unused-local-variable | `assets/js/backend.js` | 1 | 267 | Quality only (upstream code) |
| js/unused-local-variable | `assets/js/flipcount-divi.js` | 1 | 273 | Quality only (upstream code) |
| js/unused-local-variable | `assets/js/flipcount.js` | 1 | 268 | Quality only (upstream code) |
| js/unused-local-variable | `assets/js/frontend.js` | 4 | 272, 274, 275, 276 | Quality only (upstream code) |
| js/unused-local-variable | `assets/js/mec-external.js` | 1 | 269 | Quality only (upstream code) |
| js/unused-local-variable | `assets/packages/clusterer/markerclusterer.js` | 1 | 270 | Vendor library (third-party code, options set by the plugin) |
| js/unused-local-variable | `assets/packages/featherlight/featherlight.js` | 1 | 271 | Vendor library (third-party code, options set by the plugin) |
| js/use-before-declaration | `assets/js/flipcount-divi.js` | 4 | 281, 282, 283, 284 | Quality only (upstream code) |
| js/use-before-declaration | `assets/js/flipcount.js` | 1 | 277 | Quality only (upstream code) |
| js/use-before-declaration | `assets/js/frontend.js` | 8 | 278, 279, 280, 285, 286, 287, 288, 289 | Quality only (upstream code) |
| js/use-of-returnless-function | `assets/js/frontend.js` | 1 | 262 | Quality only (upstream code) |
| js/useless-assignment-to-local | `assets/js/backend.js` | 1 | 294 | Quality only (upstream code) |
| js/useless-assignment-to-local | `assets/js/flipcount-divi.js` | 4 | 297, 298, 299, 304 | Quality only (upstream code) |
| js/useless-assignment-to-local | `assets/js/flipcount.js` | 1 | 295 | Quality only (upstream code) |
| js/useless-assignment-to-local | `assets/js/frontend.js` | 10 | 300, 301, 302, 303, 305, 306, 307, 308, 309, 310 | Quality only (upstream code) |
| js/useless-assignment-to-local | `assets/js/googlemap.js` | 1 | 296 | Quality only (upstream code) |
| js/xss-through-dom | `app/core/src/Forms/backend.js` | 1 | 171 | FP: admin screen; HTML comes from server templates or translated labels |
| js/xss-through-dom | `assets/icon.html` | 1 | 172 | FP: admin screen; HTML comes from server templates or translated labels |
| js/xss-through-dom | `assets/js/backend.js` | 9 | 173, 174, 175, 176, 177, 178, 179, 180, 181 | FP: admin screen; HTML comes from server templates or translated labels |
| js/xss-through-dom | `assets/js/events.js` | 8 | 182, 183, 184, 185, 186, 187, 188, 189 | FP: admin screen; HTML comes from server templates or translated labels |
| js/xss-through-dom | `assets/js/frontend.js` | 3 | 190, 191, 192 | FP: server label / server AJAX HTML |
| php/echoed-request | `app/core/src/BookingForm/Attendees.php` | 2 | 46, 47 | FP: stored admin settings/event meta, not request data |
| php/echoed-request | `app/features/attendees.php` (not in the release ZIP) | 4 | 48, 49, 50, 51 | FP: escaped or cast at the sink (esc_*, intval, round, urlencode, wp_json_encode) |
| php/echoed-request | `app/features/bookingcalendar.php` (not in the release ZIP) | 1 | 53 | FP: escaped or cast at the sink (esc_*, intval, round, urlencode, wp_json_encode) |
| php/echoed-request | `app/features/cart/checkout.php` (not in the release ZIP) | 1 | 54 | FP: stored admin settings/event meta, not request data |
| php/echoed-request | `app/features/events.php` | 1 | 55 | FP: download response (ICS/PDF), not HTML |
| php/echoed-request | `app/features/events.php` | 1 | 56 | FP: JSON reply of sanitized values (json_encode escapes `/`, sanitize_text_field strips tags) |
| php/echoed-request | `app/features/fes/list.php` | 1 | 59 | FP: WordPress core escapes paginate_links |
| php/echoed-request | `app/features/mec/booking.php` | 5 | 62, 63, 64, 65, 66 | FP: stored admin settings/event meta, not request data |
| php/echoed-request | `app/features/report.php` | 1 | 78 | FP: JSON reply of sanitized values (json_encode escapes `/`, sanitize_text_field strips tags) |
| php/echoed-request | `app/features/report/tpl.php` | 2 | 79, 80 | FP: escaped or cast at the sink (esc_*, intval, round, urlencode, wp_json_encode) |
| php/echoed-request | `app/features/speakers.php` | 2 | 81, 82 | FP: escaped or cast at the sink (esc_*, intval, round, urlencode, wp_json_encode) |
| php/echoed-request | `app/features/sponsors.php` | 2 | 83, 84 | FP: escaped or cast at the sink (esc_*, intval, round, urlencode, wp_json_encode) |
| php/echoed-request | `app/libraries/main.php` | 3 | 86, 87, 88 | FP: download response (ICS/PDF), not HTML |
| php/echoed-request | `app/libraries/search.php` | 1 | 89 | FP: JSON reply of sanitized values (json_encode escapes `/`, sanitize_text_field strips tags) |
| php/echoed-request | `app/modules/export/details.php` | 3 | 90, 91, 92 | FP: escaped or cast at the sink (esc_*, intval, round, urlencode, wp_json_encode) |
| php/echoed-request | `app/modules/local-time/details.php` | 2 | 93, 94 | FP: request value only selects a date; output is server data through MEC_kses |
| php/echoed-request | `app/modules/next-event/details.php` | 3 | 95, 96, 97 | FP: request value only selects a date; output is server data through MEC_kses |
| php/echoed-request | `app/modules/next-event/multiple.php` | 5 | 98, 99, 100, 101, 102 | FP: request value only selects a date; output is server data through MEC_kses |
| php/echoed-request | `app/modules/qrcode/details.php` (not in the release ZIP) | 1 | 103 | FP: escaped or cast at the sink (esc_*, intval, round, urlencode, wp_json_encode) |
| php/echoed-request | `app/modules/weather/darksky.php` | 4 | 104, 105, 106, 107 | FP: escaped or cast at the sink (esc_*, intval, round, urlencode, wp_json_encode) |
| php/echoed-request | `app/modules/weather/visualcrossing.php` | 4 | 108, 109, 110, 111 | FP: escaped or cast at the sink (esc_*, intval, round, urlencode, wp_json_encode) |
| php/echoed-request | `app/skins/agenda/tpl.php` | 1 | 112 | FP: JSON reply of sanitized values (json_encode escapes `/`, sanitize_text_field strips tags) |
| php/echoed-request | `app/skins/agenda/tpl.php` | 1 | 113 | FP: server-built script, ids through esc_js |
| php/echoed-request | `app/skins/list/tpl.php` | 1 | 116 | FP: JSON reply of sanitized values (json_encode escapes `/`, sanitize_text_field strips tags) |
| php/echoed-request | `app/skins/list/tpl.php` | 1 | 117 | FP: server-built script, ids through esc_js |
| php/echoed-request | `app/skins/single.php` | 1 | 118 | FP: cached server render; key from a validated occurrence |
| php/echoed-request | `app/skins/single.php` | 1 | 119 | FP: request value only selects a date; output is server data through MEC_kses |
| php/tainted-sql-string | `app/features/fes.php` | 2 | 57, 58 | FP: value already cast or escaped |
| php/tainted-sql-string | `app/features/occurrences.php` | 1 | 70 | FP: value already cast or escaped |
| php/tainted-sql-string | `app/features/popup.php` | 1 | 72 | FP: value already cast or escaped |
| php/tainted-sql-string | `app/features/popup.php` | 1 | 73 | FP: values rebuilt by date()/time helpers; nonce + capability |
| php/tainted-sql-string | `app/features/report.php` | 2 | 74, 75 | FP: value already cast or escaped |
| php/tainted-sql-string | `app/libraries/main.php` | 1 | 85 | FP: HTML string, not SQL |
