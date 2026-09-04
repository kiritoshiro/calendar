#!/usr/bin/env node

'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const read = (relativePath) => fs.readFileSync(path.join(root, relativePath), 'utf8');
const plugin = (relativePath) => read(path.join('modern-events-calendar-lite', relativePath));

const main = plugin('modern-events-calendar-lite.php');
const readme = plugin('readme.txt');
const init = plugin('mec-init.php');
const updater = plugin('app/libraries/github-updater.php');
const factory = plugin('app/libraries/factory.php');
const feature = plugin('app/features/mec.php');
const dashboard = plugin('app/features/mec/dashboard.php');
const settings = plugin('app/features/mec/settings.php');
const base = plugin('app/core/src/Base.php');
const skin = plugin('app/skins/general_calendar.php');
const template = plugin('app/skins/general_calendar/tpl.php');
const weeklySkin = plugin('app/skins/weekly_view.php');
const frontendJs = plugin('assets/js/frontend.js');
const frontendCss = plugin('assets/css/adventistai-calendar.css');

const headerVersion = main.match(/^\s*\*\s*Version:\s*([^\s]+)/m)?.[1];
const constantVersion = main.match(/define\('MEC_VERSION',\s*'([^']+)'\)/)?.[1];
const stableVersion = readme.match(/^Stable tag:\s*(\S+)/m)?.[1];

assert.equal(headerVersion, '7.35.1.5', 'unexpected plugin header version');
assert.equal(constantVersion, headerVersion, 'MEC_VERSION must match the plugin header');
assert.equal(stableVersion, headerVersion, 'readme stable tag must match the plugin header');
assert.match(main, /^\s*\*\s*Author:\s*adventistai\.lt\s*$/m);
assert.match(main, /^\s*\*\s*Update URI:\s*https:\/\/github\.com\/kiritoshiro\/calendar\s*$/m);
assert.doesNotMatch(main, /MEC_API_UPDATE/);
assert.doesNotMatch(main, /MEC_API_ACTIVATION/);

assert.match(updater, /const REPOSITORY = 'kiritoshiro\/calendar';/);
assert.match(updater, /ADVENTISTAI_CALENDAR_GITHUB_TOKEN/);
assert.match(updater, /pre_set_site_transient_update_plugins/);
assert.match(updater, /plugins_api/);
assert.match(updater, /upgrader_source_selection/);
assert.match(updater, /select_plugin_source'\), 5, 4/);
assert.match(updater, /mec_adventistai_github_update_v2/);
assert.match(updater, /\$release_version === \$version/);
assert.match(updater, /Authorization'\] = 'Bearer ' \./);
assert.match(updater, /REMOTE_PLUGIN_FILE = 'modern-events-calendar-lite\/modern-events-calendar-lite\.php'/);
assert.match(updater, /mec_adventistai_invalid_update_package/);
assert.doesNotMatch(updater, /[?&](?:token|access_token)=/i, 'a GitHub token must never be placed in a URL');
assert.doesNotMatch(factory, /api\.webnus\.site\/v3|MEC_API_UPDATE/);
assert.doesNotMatch(init, /load_auto_update|in_plugin_update_message/);

assert.doesNotMatch(feature, /add_submenu_page\([^\n]+MEC-go-pro/);
assert.doesNotMatch(feature, /add_submenu_page\([^\n]+MEC-addons/);
assert.doesNotMatch(feature, /webnus\.net\/wp-json\/wninfo|mec-purchase/);
assert.doesNotMatch(feature, /activate_license|revoke_license|plugin_activation_request/);
assert.doesNotMatch(dashboard, /notifications\.webnus\.site|mec_custom_msg|mec-pro-notice/);
assert.doesNotMatch(settings.slice(0, 1000), /mec_custom_msg/);
assert.doesNotMatch(base, /marketing_notice|Tracking\\PostHog|Tracking\\Consent|Tracking\\Snapshot/);

const renderStart = skin.indexOf('public function gcalbar_render_bar()');
const renderEnd = skin.indexOf('public function gcalbar_render_months_row', renderStart);
const renderBar = skin.slice(renderStart, renderEnd);
assert.ok(renderStart >= 0 && renderEnd > renderStart, 'calendar bar renderer not found');
assert.match(renderBar, /mec-ymtabs-year-select/);
assert.match(renderBar, /Previous month/);
assert.match(renderBar, /Next month/);
assert.doesNotMatch(renderBar, /Previous year|Next year|mec-ymtabs-year-nav/);
assert.equal((renderBar.match(/mec-ymtabs-gcal-navigator/g) || []).length, 1, 'date and navigation must share one header');

const countStart = skin.indexOf('public function gcalbar_counts_for_year');
const countEnd = skin.indexOf('public function gcalbar_render_bar', countStart);
const countFunction = skin.slice(countStart, countEnd);
assert.match(countFunction, /COUNT\(DISTINCT CASE WHEN/);
assert.equal((countFunction.match(/\$wpdb->get_(?:row|var|results|col)/g) || []).length, 1, 'month counts must use one database read');

assert.match(template, /\.mec-ymtabs-year-select", function\(\)/);
assert.match(template, /calendar\.prev\(\)/);
assert.match(template, /calendar\.next\(\)/);
assert.match(template, /calendar\.today\(\)/);
assert.match(template, /return year \+ "-" \+ \("0" \+ month\)\.slice\(-2\) \+ "-01"/);
assert.match(template, /getUTCFullYear\(\)/);
assert.match(template, /getUTCMonth\(\)/);
assert.doesNotMatch(template, /mec-ymtabs-year-nav/);

assert.match(frontendCss, /grid-template-columns:\s*minmax\(0, 1fr\) 34px clamp\(150px, 22vw, 220px\) 34px minmax\(0, 1fr\)/);
assert.match(frontendCss, /\.mec-gcalbar-nav-prev\s*\{[\s\S]*?grid-column:\s*2;/);
assert.match(frontendCss, /\.mec-ymtabs-gcal-title\s*\{[\s\S]*?grid-column:\s*3;/);
assert.match(frontendCss, /\.mec-gcalbar-nav-next\s*\{[\s\S]*?grid-column:\s*4;/);
assert.match(frontendCss, /\.mec-ymtabs-today\s*\{[\s\S]*?white-space:\s*nowrap\s*!important;/);
assert.match(frontendCss, /\.mec-gCalendar\s*>\s*#mec-gCalendar-wrap\s*\{[\s\S]*?overflow-x:\s*auto;/);
assert.match(frontendCss, /@media\s*\(hover:\s*hover\)\s*and\s*\(pointer:\s*fine\)[\s\S]*?min-width:\s*720px;/);

assert.match(weeklySkin, /apply_filters\('mec_adventistai_calendar_locale',\s*'lt_LT'/);
assert.match(weeklySkin, /switch_to_locale\(\$calendar_locale\)/);
const miniAgendaInitStart = frontendJs.indexOf("$('.mec-magenda').each(function ()");
const miniAgendaInitEnd = frontendJs.indexOf("$(document).on('click', '.mec-magenda-day'", miniAgendaInitStart);
const miniAgendaInit = frontendJs.slice(miniAgendaInitStart, miniAgendaInitEnd);
assert.ok(miniAgendaInitStart >= 0 && miniAgendaInitEnd > miniAgendaInitStart, 'mini calendar initializer not found');
assert.match(miniAgendaInit, /loadMonth\(\$root, year, month\)/, 'mini calendar must refresh its visible month on initial load');

assert.ok(updater.includes("'requires_php' => '8.4'"));
assert.match(updater, /\/releases\/latest/);
assert.match(updater, /application\/octet-stream/);
assert.match(main, /option_mec_options/);
assert.match(main, /booking_status/);
const displayOptions = plugin('app/features/mec/meta_boxes/display_options.php');
assert.equal(displayOptions.includes('assets/img/skins'), false);
assert.equal(displayOptions.includes('wn-hover-img-sh'), false);
console.log('Customization checks passed.');
