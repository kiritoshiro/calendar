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
const ix = plugin('app/features/ix.php');
const buildWorkflow = read('.github/workflows/build-plugin.yml');
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

assert.equal(headerVersion, '7.35.1.32', 'unexpected plugin header version');
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
assert.match(updater, /add_action\('load-update-core\.php', array\(\$this, 'force_check'\), 9\);/, '"Check again" must refresh before wp_update_plugins (priority 10)');
assert.match(updater, /empty\(\$_GET\['force-check'\]\) \|\| !current_user_can\('update_plugins'\)/);
assert.match(updater, /delete_site_transient\('update_plugins'\);/);
assert.match(updater, /\$release_version === \$version/);
assert.match(updater, /Authorization'\] = 'Bearer ' \./);
assert.match(updater, /REMOTE_PLUGIN_FILE = 'modern-events-calendar-lite\/modern-events-calendar-lite\.php'/);
assert.match(updater, /mec_adventistai_invalid_update_package/);
assert.doesNotMatch(updater, /[?&](?:token|access_token)=/i, 'a GitHub token must never be placed in a URL');
assert.doesNotMatch(updater, /mec_adventistai_missing_github_token/, 'update checks must not require a token while the repository is public');
assert.match(updater, /ERROR_KEY = 'mec_adventistai_github_update_error'/);
const authorizeStart = updater.indexOf('public function authorize_github_request');
const authorizeEnd = updater.indexOf('public function select_plugin_source', authorizeStart);
assert.ok(authorizeStart >= 0 && authorizeEnd > authorizeStart, 'authorize_github_request not found');
const authorize = updater.slice(authorizeStart, authorizeEnd);
assert.ok(
    authorize.indexOf("'Accept'") < authorize.indexOf('$this->get_token()'),
    'Accept must be set before the token lookup, or unauthenticated asset downloads return JSON instead of the ZIP'
);
assert.doesNotMatch(factory, /api\.webnus\.site\/v3|MEC_API_UPDATE/);
assert.doesNotMatch(init, /load_auto_update|in_plugin_update_message/);

assert.doesNotMatch(feature, /add_submenu_page\([^\n]+MEC-go-pro/);
assert.doesNotMatch(feature, /MEC-wizard|setup_wizard|display_wizard/);
assert.doesNotMatch(feature, /add_submenu_page\([^\n]+MEC-addons/);
assert.doesNotMatch(feature, /webnus\.net\/wp-json\/wninfo|mec-purchase/);
assert.doesNotMatch(feature, /activate_license|revoke_license|plugin_activation_request/);
assert.doesNotMatch(dashboard, /notifications\.webnus\.site|mec_custom_msg|mec-pro-notice|youtube\.com|iframe|License Activation|Change Log/);
assert.match(dashboard, /Upcoming Events/);
assert.doesNotMatch(dashboard, /youtube\.com|webnus\.net|Create a Support Ticket|Quick Setup Video|WooCommerce Video/);
assert.match(dashboard, /System Information/);
assert.match(dashboard, /Debug Log/);
assert.match(dashboard, /is_readable\(\$log_file\)/);
assert.match(dashboard, /mec-download-log-file=1/);
assert.doesNotMatch(feature, /MEC-support|support_menu|support_page|display_support/);
assert.doesNotMatch(factory, /MEC-support/);
assert.equal(fs.existsSync(path.join(root, 'modern-events-calendar-lite', 'app', 'features', 'mec', 'support-page.php')), false, 'the standalone support page must stay removed');
assert.doesNotMatch(ix, /include_meetup_api|MEC-f-calendar-import|MEC-meetup-import|MEC-thirdparty|MEC-test-data|MEC-export|MEC-import|sync_f_import|sync_meetup_import/);
for (const removed of ['app/features/mec/wizard.php', 'app/features/ix/export.php', 'app/features/ix/import.php', 'app/features/ix/import_f_calendar.php', 'app/features/ix/import_meetup.php', 'app/features/ix/thirdparty.php', 'app/features/ix/test_data.php']) {
    assert.match(buildWorkflow, new RegExp(`--exclude='${removed.replaceAll('/', '\\/')}'`));
}
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

// Lean first render: the page's agenda starts at today, the pickers are built
// on first open, and nearby months are prefetched into memory.
const weeklyTpl = plugin('app/skins/weekly_view/tpl.php');
const weeklyRenderFile = plugin('app/skins/weekly_view/render.php');
assert.match(weeklyTpl, /\$this->magenda_agenda_from = \(empty\(\$this->atts\['return_items'\]\) && !\$this->show_only_expired_events\) \? current_time\('Y-m-d'\) : '';/, 'only the page render (not AJAX, not past-only calendars) starts at today');
assert.ok(weeklyTpl.indexOf('$this->magenda_agenda_from =') < weeklyTpl.indexOf('include $render_path;'), 'the agenda start is set before render.php runs');
assert.match(weeklyRenderFile, /if\(\$this->magenda_agenda_from !== '' and \$date < \$this->magenda_agenda_from\)\s*\{\s*if\(!count\(\$events\) and !isset\(\$has_events\[\$week\]\)\) \$has_events\[\$week\] = 'printed';\s*continue;\s*\}/, 'earlier days are skipped but keep the week placeholder rule');
assert.doesNotMatch(weeklySkin, /mec-magenda-picker/, 'the month and year pickers are not printed by PHP');
assert.match(weeklySkin, /data-month-names="<\?php echo esc_attr\(wp_json_encode\(\$month_names\)\); \?>"/);
assert.match(weeklySkin, /data-timezone="<\?php echo esc_attr\(wp_timezone_string\(\)\); \?>"/);
assert.match(frontendJs, /function buildPicker\(\$root, which\)/);
assert.match(frontendJs, /var \$picker = buildPicker\(\$root, which\);/, 'a label click builds its picker');
assert.match(frontendJs, /createHTMLDocument\(''\)/, 'month replies are parsed in an inert document');
assert.match(frontendJs, /\$root\.find\('\.mec-magenda-agenda \.mec-weekly-view-dates-events'\)\.html\(days\[dateStr\] \|\| ''\);/, 'only the selected day is in the DOM once a month is loaded');
assert.match(frontendJs, /if \(hit && Date\.now\(\) - hit\.at < MONTH_CACHE_TTL\) return hit\.request;/);
assert.match(miniAgendaInit, /whenIdle\(function \(\) \{\s*prefetchMonth\(\$root, -1\);\s*prefetchMonth\(\$root, 1\);/, 'neighbouring months are prefetched when idle');
assert.match(frontendJs, /window\.requestIdleCallback\(callback, \{ timeout: 3000 \}\);\s*else window\.setTimeout\(callback, 1500\);/);
assert.match(miniAgendaInit, /if \(saveData\(\)\) return;\s*whenVisible\(\$root\[0\], function \(\) \{\s*whenIdle\(function \(\) \{/, 'the idle prefetch waits until the calendar is on screen, and not with Save-Data');
assert.match(frontendJs, /if \(!window\.IntersectionObserver\) return;/, 'no idle prefetch without IntersectionObserver');
assert.match(frontendJs, /observer\.disconnect\(\);\s*callback\(\);/, 'the visibility prefetch runs once per calendar');
assert.match(frontendJs, /\}, \{ threshold: 0\.1 \}\);/);
assert.match(frontendJs, /\$\(document\)\.on\('mouseenter focusin', '\.mec-magenda-prev, \.mec-magenda-next', function \(\) \{\s*if \(saveData\(\)\) return;\s*prefetchMonth\(/, 'arrows prefetch on hover and focus, not with Save-Data');
assert.match(miniAgendaInit, /var today = currentToday\(pageToday, siteToday\(\$root\.data\('timezone'\), Date\.now\(\)\)\);/);
assert.doesNotMatch(miniAgendaInit, /\$root\.data\('cur-(year|month)',/, 'the shown month changes only in showMonth()');
assert.match(miniAgendaInit, /year = parseInt\(today\.slice\(0, 4\), 10\);\s*month = parseInt\(today\.slice\(5, 7\), 10\);/, 'a new month is passed to loadMonth() directly');
assert.match(miniAgendaInit, /selectDay\(\$root, sameMonth \? defaultDayFor\(\$root, year, month\) : pageToday\);/, 'the page day stays selected until a new month is shown');

// siteToday() works out the site's date in the browser for a cached page.
const extractFunction = (source, name) => {
    const start = source.indexOf(`function ${name}(`);
    assert.ok(start >= 0, `${name}() not found`);
    let depth = 0;
    for (let i = source.indexOf('{', start); i < source.length; i++) {
        if (source[i] === '{') depth++;
        else if (source[i] === '}' && --depth === 0) return source.slice(start, i + 1);
    }
    throw new Error(`${name}() is not closed`);
};
const vm = require('node:vm');
const siteToday = vm.runInNewContext(`${extractFunction(frontendJs, 'pad')}\n${extractFunction(frontendJs, 'siteToday')}\nsiteToday`, { Intl, Date });
assert.equal(siteToday('+03:00', Date.UTC(2026, 9, 16, 21, 30)), '2026-10-17');
assert.equal(siteToday('-05:00', Date.UTC(2026, 9, 16, 21, 30)), '2026-10-16');
assert.equal(siteToday('+00:00', Date.UTC(2026, 11, 31, 23, 59)), '2026-12-31');
assert.equal(siteToday('Europe/Vilnius', Date.UTC(2026, 9, 31, 22, 30)), '2026-11-01', 'zone names follow daylight saving (EET after 25 October)');
assert.equal(siteToday('Europe/Vilnius', Date.UTC(2026, 9, 16, 20, 59)), '2026-10-16');
assert.equal(siteToday('Not/AZone', Date.UTC(2026, 9, 16)), '', 'an unknown zone falls back to the page date');

// currentToday() only moves a cached page's today 1-2 days forward.
const maxLag = /var MAX_TODAY_LAG_DAYS = (\d+);/.exec(frontendJs);
assert.ok(maxLag && maxLag[1] === '2', 'MAX_TODAY_LAG_DAYS is 2');
const currentToday = vm.runInNewContext(`var MAX_TODAY_LAG_DAYS = ${maxLag[1]};\n${extractFunction(frontendJs, 'currentToday')}\ncurrentToday`, { Date });
assert.equal(currentToday('2026-10-16', '2026-10-16'), '2026-10-16');
assert.equal(currentToday('2026-10-16', '2026-10-17'), '2026-10-17');
assert.equal(currentToday('2026-10-31', '2026-11-02'), '2026-11-02', 'two days on, across a month');
assert.equal(currentToday('2026-12-31', '2027-01-01'), '2027-01-01', 'across a year');
assert.equal(currentToday('2026-10-16', '2026-10-19'), '2026-10-16', 'more than two days ahead is not trusted');
assert.equal(currentToday('2026-10-16', '2027-10-16'), '2026-10-16');
assert.equal(currentToday('2026-10-16', '2026-10-15'), '2026-10-16', 'never moves back');
assert.equal(currentToday('2026-10-16', ''), '2026-10-16', 'no browser date keeps the page date');
assert.equal(currentToday('', '2026-10-16'), '', 'no page date is left alone');

assert.ok(updater.includes("'requires_php' => '8.4'"));
assert.match(updater, /\/releases\/latest/);
assert.match(updater, /application\/octet-stream/);
assert.match(main, /option_mec_options/);
assert.match(main, /booking_status/);
const displayOptions = plugin('app/features/mec/meta_boxes/display_options.php');
assert.equal(displayOptions.includes('assets/img/skins'), false);
assert.equal(displayOptions.includes('wn-hover-img-sh'), false);

// Footer-loaded frontend assets only on pages that render MEC output, and the
// General Calendar library only where that skin is used.
const skinsLibrary = plugin('app/libraries/skins.php');
assert.match(factory, /public static function mark_rendered\(/);
assert.match(factory, /add_filter\('pre_do_shortcode_tag'/);
assert.match(factory, /\$late && !\$this->frontend_assets_needed\(\)\) return;/);
assert.match(factory, /if \(\$general_calendar\) wp_enqueue_script\('mec-general-calendar-script'\);/);
assert.match(factory, /if \(\$general_calendar\) wp_enqueue_style\('mec-general-calendar-style'\);/);
assert.match(factory, /apply_filters\('mec_frontend_assets_needed'/);
assert.match(skinsLibrary, /MEC_factory::mark_rendered\(\$this->skin\);/);

// Pages whose only MEC output is the magenda (Weekly View, no search form)
// skip the libraries it does not use.
assert.match(factory, /private const LEAN_SKINS = \['weekly_view'\];/);
assert.match(factory, /\$lean = \$late && \$this->lean_assets_only\(\);/);
assert.match(factory, /\$popup = !\$lean \|\| self::\$rendered_event_popup;/);
assert.match(factory, /if \(\$popup\) wp_enqueue_script\('featherlight'\);/);
assert.match(skinsLibrary, /MEC_factory::mark_event_popup\(\);/);
assert.match(read('modern-events-calendar-lite/assets/js/frontend.js'), /\.mec-magenda a\[data-event-id\]\[target="m1"\]/);
assert.match(factory, /if \(!\$lean && !defined\("SHOW_CT_BUILDER"\)\) wp_enqueue_script\('jquery-ui-datepicker'\);/);

// Those pages load the trimmed magenda stylesheet; the popup adds the full one.
assert.match(factory, /'mec-magenda-style' => \$this->main->asset\('css\/magenda\.min\.css'\)/);
assert.match(factory, /\$trimmed = \$lean && !is_rtl\(\) && !is_admin\(\)/);
assert.match(factory, /&& !wp_style_is\('mec-frontend-style', 'enqueued'\)\s+&& !wp_style_is\('mec-frontend-style', 'done'\)/);
assert.match(factory, /&& is_readable\(MEC_ABSPATH \. 'assets\/css\/magenda\.min\.css'\)\s+&& apply_filters\('mec_weekly_view_css', true\);/);
assert.equal(fs.existsSync(path.join(root, 'modern-events-calendar-lite/assets/css/weekly-view.min.css')), false, 'only one trimmed stylesheet ships');
assert.match(factory, /if \(\$trimmed\) wp_enqueue_style\('mec-magenda-style'\);/);
assert.match(factory, /'full_styles' => \$trimmed \? \$this->style_urls\(\['mec-font-icons', 'mec-frontend-style'\]\) : \[\]/);
assert.match(frontendJs, /mecSingleEventDisplayer\.loadFullStyles\(\);\s+if \(jQuery\('\.mec-modal-result'\)/);
const magendaCss = plugin('assets/css/magenda.min.css');
// Event titles sit under the calendar's hidden h2, so heading levels do not skip.
const weeklyRender = plugin('app/skins/weekly_view/render.php');
assert.ok(!/<h4 class="mec-event-title"/.test(weeklyRender) && (weeklyRender.match(/<h3 class="mec-event-title">/g) || []).length === 2, 'Weekly View event titles are h3');
assert.match(plugin('app/skins/weekly_view.php'), /<h2 class="mec-magenda-sr-title"><\?php echo esc_html\(\$calendar_heading\); \?><\/h2>/);
// The agenda's clock is an SVG mask, so the icon font is not downloaded for it.
assert.match(plugin('assets/css/magenda.min.css'), /\.mec-magenda \.mec-sl-clock-o\s*\{\s*font-family:\s*inherit !important;?\s*\}/);
assert.match(plugin('assets/css/magenda.min.css'), /\.mec-magenda \.mec-magenda-sr-title\s*\{[^}]*clip:\s*rect\(0, 0, 0, 0\)/);
assert.ok(magendaCss.length < 40000 && /\.mec-magenda\s*\{[^}]*position:\s*relative/.test(magendaCss) && /@font-face\s*\{[^}]*simple-line-icons/.test(magendaCss), 'magenda.min.css holds the magenda styles and the icon font');
assert.match(factory, /\|\| self::\$rendered_search_form\) return false;/);
assert.match(skinsLibrary, /private function note_search_form\(\$html\)/);
assert.match(skinsLibrary, /strpos\(\$html, 'mec-search-form'\) !== false\) MEC_factory::mark_rendered\(\$this->skin, true\);/);

// Unused libraries, the upstream update checker and unused locales stay out of
// the repository; unminified stylesheets stay out of the package.
for (const removed of ['app/api/Twilio', 'app/api/TFPDF', 'app/api/Stripe', 'app/api/Campaign_Monitor', 'app/api/Meetup', 'app/api/addons-api', 'app/core/puc']) {
    assert.equal(fs.existsSync(path.join(root, 'modern-events-calendar-lite', removed)), false, `${removed} must stay removed`);
}
// A published package is never replaced, publishing waits for the security
// checks, and Plugin Check fails only on findings beyond the legacy baseline.
const pluginChecks = read('.github/workflows/plugin-checks.yml');
assert.doesNotMatch(buildWorkflow, /--clobber/);
// The package's scripts are minified with the pinned esbuild; sources stay readable.
assert.match(buildWorkflow, /npm ci --ignore-scripts/);
assert.match(buildWorkflow, /node scripts\/minify-package-js\.cjs "\$stage\/modern-events-calendar-lite"/);
assert.equal(JSON.parse(read('package.json')).devDependencies.esbuild, '0.28.2');
assert.ok(read('modern-events-calendar-lite/assets/js/frontend.js').includes('\n    getSinglePage: function'), 'frontend.js stays readable in the repository');
assert.match(buildWorkflow, /uses: \.\/\.github\/workflows\/security-gate\.yml/);
assert.match(buildWorkflow, /needs: security-gate\b/);
const securityGate = read('.github/workflows/security-gate.yml');
for (const called of ['cybersecurity.yml', 'plugin-checks.yml', 'codeql.yml']) {
    assert.match(securityGate, new RegExp(`uses: \\./\\.github/workflows/${called.replace('.', '\\.')}`), `the security gate must run ${called}`);
}
assert.match(securityGate, /name: All security checks passed/);
assert.match(pluginChecks, /plugin-check-baseline\.py "\$RUNNER_TEMP\/plugin-check-results\.txt" tools\/linters\/plugin-check-baseline\.json/);
assert.ok(fs.existsSync(path.join(root, 'tools', 'linters', 'plugin-check-baseline.json')), 'the Plugin Check baseline must exist');
// The linter tooling lives outside .github/: Dependabot may only change files
// there for GitHub Actions updates, so a composer manifest under .github/
// could never be updated. Workflows and dependabot.yml must agree on it.
assert.ok(fs.existsSync(path.join(root, 'tools', 'linters', 'composer.json')), 'the linter composer.json must be in tools/linters');
assert.match(read('.github/dependabot.yml'), /directory: \/tools\/linters\r?\n/, 'Dependabot must watch tools/linters');
const staticAnalysis = read('.github/workflows/static-analysis.yml');
assert.match(staticAnalysis, /composer install --working-dir=tools\/linters /, 'static analysis must install tools/linters');
assert.doesNotMatch(staticAnalysis + pluginChecks + read('.github/dependabot.yml'), /\.github\/linters/, 'no workflow may still use .github/linters');
for (const file of ['app/features/events.php', 'app/features/mec.php', 'app/libraries/main.php']) {
    assert.doesNotMatch(plugin(file), /(?<!safe_)wp_redirect\(/, `${file} must use wp_safe_redirect`);
}

// The Events list offers "MS Excel Export"; its writer must ship.
assert.ok(fs.existsSync(path.join(root, 'modern-events-calendar-lite', 'app', 'api', 'XLSX', 'xlsxwriter.class.php')), 'the XLSX writer must stay');
assert.doesNotMatch(buildWorkflow, /--exclude='app\/api\/XLSX\/'/);
// Vendored libraries' GitHub templates are not shipped.
assert.match(buildWorkflow, /--exclude='\.github\/'/);
const languages = fs.readdirSync(path.join(root, 'modern-events-calendar-lite', 'languages')).sort();
assert.deepEqual(languages, ['index.html', 'modern-events-calendar-lite-en_US.mo', 'modern-events-calendar-lite-en_US.po', 'modern-events-calendar-lite-lt_LT.po', 'modern-events-calendar-lite-ru_RU.mo', 'modern-events-calendar-lite-ru_RU.po', 'modern-events-calendar-lite.pot']);
for (const css of ['frontend', 'backend', 'a11y', 'a11y-backend']) {
    assert.match(buildWorkflow, new RegExp(`--exclude='assets\\/css\\/${css}\\.css'`));
    assert.ok(fs.existsSync(path.join(root, 'modern-events-calendar-lite', 'assets', 'css', `${css}.min.css`)), `${css}.min.css must exist`);
}

// Public-input audit (7.35.1.11): visitor values in the search form are
// escaped, guests need guest submission enabled, and public endpoints only
// expose events a visitor could already open.
for (const key of ['s', 'address', 'event-cost-min', 'event-cost-max']) {
    assert.doesNotMatch(skinsLibrary, new RegExp(`value="' \\. \\(\\$this->atts\\['${key}'\\]`), `search field ${key} must be escaped`);
}
const fesFeature = plugin('app/features/fes.php');
assert.match(fesFeature, /if \(!\$this->guest_submission_allowed\(\)\) return false;/);
assert.match(fesFeature, /get_current_user_id\(\) && \(int\) \$post->post_author === get_current_user_id\(\)/);
assert.equal((fesFeature.match(/if \(!\$this->current_user_can_submit_event\(\)\) \$this->main->response/g) || []).length, 2, 'the event form and image upload must check submission access');
assert.match(plugin('app/features/events.php'), /if \(!is_user_logged_in\(\) and \(!isset\(\$this->settings\['fes_guest_status'\]\)/);
assert.match(plugin('app/skins/single.php'), /\$viewable = is_post_publicly_viewable\(\$post\)/);
assert.match(plugin('app/libraries/main.php'), /get_post_meta\(\(int\) \$book_id, 'mec_event_id', true\) === \(int\) \$event->ID/);
// Logged-in handler audit (7.35.1.12): admin AJAX handlers check capabilities,
// not only nonces that every page prints.
const ixFeature = plugin('app/features/ix.php');
assert.match(ixFeature, /current_user_can\('manage_options'\) \|\| current_user_can\('mec_import_export'\)/);
assert.equal((ixFeature.match(/\$this->verify_g_calendar_export_request\(\);/g) || []).length, 2, 'both Google export AJAX actions must verify access');
assert.match(ixFeature, /if \(!\$this->can_manage_g_calendar_export\(\)\) return;/);
assert.equal((plugin('app/features/ix/export_g_calendar.php').match(/wp_nonce_field\('mec_ix_g_calendar_export', 'mec_ix_nonce', false\)/g) || []).length, 2);
assert.match(plugin('app/features/report.php'), /public function attendees\(\)\s*\{\s*\/\/[^\n]*\n[^\n]*\n\s*if \(!current_user_can\('mec_report'\)\)/);
assert.match(plugin('assets/js/backend.js'), /action: "mec_attendees",\s*nonce: mec_admin_localize\.ajax_nonce/);
assert.match(plugin('app/features/fes.php'), /\$event_id <= 0 \|\| !\$this->current_user_can_upsert_event\(\$event_id\)/);
assert.doesNotMatch(plugin('app/libraries/factory.php'), /wp_ajax_wizard_import_dummy/);
assert.match(plugin('app/features/popup.php'), /current_user_can\(\$taxonomy->cap->edit_terms\)/);
assert.match(plugin('app/features/popup/event.php'), /_mecnonce="\+encodeURIComponent\(jQuery\("#_mecnonce"\)\.val\(\)\)/);
const occurrencesFeature = plugin('app/features/occurrences.php');
assert.match(occurrencesFeature, /VALUES \(%d, %d, %s\)", \$id, \$occurrence, json_encode\(array\(\)\)\)/);
assert.match(occurrencesFeature, /SET `params`='"\.\$this->db->escape\(json_encode\(\$occurrence, JSON_UNESCAPED_UNICODE\)\)/);
// Code-scanning triage (7.35.1.24): the public mec_full_calendar_switch_skin
// took atts[instance_id] from the request, and the daily view prints the skin
// id inside a JS identifier (var mec_daily_view_init_<id>), where esc_js()
// does not stop an injected expression. The id keeps only [A-Za-z0-9_].
assert.match(skinsLibrary, /private function sanitize_skin_dom_id\(\$id\)[\s\S]*?preg_replace\('\/\[\^A-Za-z0-9_\]\/', '', \(string\) \$id\)/);
assert.equal((skinsLibrary.match(/\$id = \$this->sanitize_skin_dom_id\(\$this->atts\['(instance_id|id)'\]\);/g) || []).length, 2, 'both skin id sources must be sanitized');
assert.doesNotMatch(skinsLibrary, /return \$this->atts\['(instance_id|id)'\];/);
// Request shape (7.35.1.25): switch_skin passed request atts/sf straight to the
// skins, so atts[sk-options]=x or atts[category][]=x ended in a fatal TypeError
// (HTTP 500). Arrays are kept only where the skins expect them.
assert.match(skinsLibrary, /public function normalize_request_values\(\$values, \$array_keys = \[\]\)[\s\S]*?if \(is_array\(\$value\) !== in_array\(\$key, \$array_keys, true\)\) unset\(\$values\[\$key\]\);[\s\S]*?foreach \(\['sk-options', 'sf-options'\] as \$key\)[\s\S]*?if \(is_array\(\$values\[\$key\]\)\) \$values\[\$key\] = array_filter\(\$values\[\$key\], 'is_array'\);\s*else unset\(\$values\[\$key\]\);/, 'a scalar sf[sk-options] must be dropped, not passed to array_filter');
for (const file of ['app/skins/full_calendar.php', 'app/skins/default_full_calendar.php']) {
    const switchSkin = plugin(file).match(/public function switch_skin\(\)[\s\S]*?\n    \}/)?.[0] || '';
    assert.match(switchSkin, /\$this->normalize_request_values\(\$this->main->sanitize_deep_array\(\$_REQUEST\['sf'\]\), \['fields'\]\)/, `${file} must normalise sf`);
    assert.match(switchSkin, /\$this->normalize_request_values\(\$this->main->sanitize_deep_array\(\$_REQUEST\['atts'\]\), \['sk-options', 'sf-options', 'icons', 'include', 'exclude', 'fields'\]\)/, `${file} must normalise atts`);
    assert.match(switchSkin, /\$atts = \$this->sf_apply\(\$atts, \$this->sf, \$apply_sf_date\);/, `${file} must normalise atts before sf_apply`);
}
// Per-option shape (7.35.1.26): one option inside a skin's group sent as an
// array (sk-options[full_calendar][limit][]=x, [include_local_time][], tile
// [count][]...) still ended in trim()/isset TypeErrors. Every option there is
// a scalar except the yearly view's months[n] list.
const normalizeRequest = skinsLibrary.match(/public function normalize_request_values\([\s\S]*?\n    \}/)?.[0] || '';
assert.match(normalizeRequest, /foreach \(\$values\['sk-options'\] as \$skin => \$options\)[\s\S]*?foreach \(\$options as \$option => \$value\)[\s\S]*?if \(!is_array\(\$value\)\) continue;/, 'each sk-options option must be checked');
assert.match(normalizeRequest, /if \(\$option === 'months'\) \$values\['sk-options'\]\[\$skin\]\[\$option\] = array_filter\(\$value, 'is_scalar'\);\s*else unset\(\$values\['sk-options'\]\[\$skin\]\[\$option\]\);/, 'only months may stay an array');
assert.match(plugin('app/skins/yearly_view.php'), /\$this->months_to_display = \(isset\(\$this->skin_options\['months'\]\) and is_array\(\$this->skin_options\['months'\]\)/, 'yearly view must still accept a scalar months value');
// The render also reads $_REQUEST['sf'] (initial page render and every AJAX skin).
const renderLibrary = plugin('app/libraries/render.php');
assert.equal((renderLibrary.match(/\$SKO->normalize_request_values\(\$this->main->sanitize_deep_array\(\$_REQUEST\['sf'\]\), \['fields'\]\)/g) || []).length, 2, 'both render paths must normalise sf');
assert.doesNotMatch(renderLibrary, /\? \$this->main->sanitize_deep_array\(\$_REQUEST\['sf'\]\) :/);
// Other public skin actions (7.35.1.27): each view's load_month/load_more,
// the map markers, the timetable's weekly program and the yearly view read
// request sf/atts the same way and failed the same way (sf[month][]=x,
// atts[sk-options]=x, mec_month[]=x, mec_next_page[]=x ...).
const skinFiles = fs.readdirSync(path.join(root, 'modern-events-calendar-lite/app/skins')).filter((file) => file.endsWith('.php')).map((file) => `app/skins/${file}`);
let normalisedSkinReads = 0;
for (const file of skinFiles) {
    const source = plugin(file);
    assert.doesNotMatch(source, /\? \$this->main->sanitize_deep_array\(\$_REQUEST\['(sf|atts)'\]\) :/, `${file} must normalise request sf/atts`);
    assert.doesNotMatch(source, /\? \$_REQUEST\['mec_month'\] :|: \(\$_REQUEST\['mec_month'\] \?\? ''\)/, `${file} must sanitise mec_month before trim()`);
    assert.doesNotMatch(source, /isset\(\$_REQUEST\['mec_next_page'\]\) and trim\(/, `${file} must check mec_next_page is a string`);
    assert.doesNotMatch(source, /sanitize_text_field\(\$_REQUEST\['mec_(year|month)'\]/, `${file} must read mec_year/mec_month with request_date_part()`);
    normalisedSkinReads += (source.match(/\$this->normalize_request_values\(\$this->main->sanitize_deep_array\(\$_REQUEST\['(sf|atts)'\]\), \[/g) || []).length;
}
// mec_year=x (or [], or a scalar sf[year]=x copied into it) reached mktime() in
// the monthly templates as a string: a TypeError on PHP 8. So did a 20-digit
// year, and mec_month=001 made strtotime() read "2023-001-01" as an ordinal
// date, which kept the weekly view's week loop running until the time limit.
assert.match(skinsLibrary, /public function request_date_part\(\$key, \$default = null\)[\s\S]*?is_string\(\$_REQUEST\[\$key\]\)\) \? trim\(\$_REQUEST\[\$key\]\) : '';\s*\$pattern = \$key === 'mec_year' \? '\/\^\\d\{4\}\$\/' : '\/\^\(0\?\[1-9\]\|1\[0-2\]\)\$\/';\s*return preg_match\(\$pattern, \$value\) \? \$value : \$default;/, 'mec_year must be four digits and mec_month 1-12, else the default');
assert.match(plugin('app/skins/weekly_view/tpl.php'), /while\(isset\(\$week\[\$i\]\) and strtotime\(\$first_week_day\) < \$current_month_time\)/, 'the weekly week loop must stop at the last day of the week');
// 34 new reads in 13 skins plus the 4 switch_skin() reads from 7.35.1.25.
assert.equal(normalisedSkinReads, 38, 'every skin handler must normalise both sf and atts');
assert.match(skinsLibrary, /isset\(\$field_value\['date_min'\], \$field_value\['date_max'\]\) and is_string\(\$field_value\['date_min'\]\) and is_string\(\$field_value\['date_max'\]\) and trim\(/, 'sf[fields][<id>][date_min][] must not reach trim()');
assert.match(plugin('app/libraries/search.php'), /\$sf = \(isset\(\$_POST\['sf'\]\) and is_array\(\$_POST\['sf'\]\)\) \? array_filter\(\$this->main->sanitize_deep_array\(\$_POST\['sf'\]\), function\(\$value, \$key\)\s*\{\s*return is_scalar\(\$value\) or \$key === 'fields';\s*\}, ARRAY_FILTER_USE_BOTH\) : \[\];/, 'the search refine must drop array sf values except fields');
assert.doesNotMatch(plugin('app/skins/general_calendar.php'), /'wp_ajax_(nopriv_)?mec_general_calendar_load_month'/, 'the general calendar has no load_month() method to register');
const bookingCalendar = plugin('app/features/bookingcalendar.php');
for (const key of ['event_id', 'year', 'month']) {
    assert.match(bookingCalendar, new RegExp(`isset\\(\\$_REQUEST\\['${key}'\\]\\) \\? absint\\(\\$_REQUEST\\['${key}'\\]\\)`), `booking calendar ${key} must be an integer`);
}
assert.match(plugin('app/features/report.php'), /if \(\$event_id !== 'none'\) \$event_id = absint\(\$event_id\) \?: 'none';/);
assert.match(plugin('app/skins/grid/render.php'), /gmapSkin\('\.json_encode\(\$events_data\)\.', "'\.esc_js\(\$this->id\)\.'"\)/);
assert.match(plugin('app/addons/divi/includes/MECShortcodesForDivi.php'), /\$post_id = absint\(\$_POST\['shortcode_id'\] \?\? 0\);/);
assert.match(ixFeature, /\$this->db->select\(\$wpdb->prepare\("SELECT `post_id` FROM `#__postmeta` WHERE `meta_value`=%s AND `meta_key`='mec_gcal_id'", \$gcal_id\), 'loadResult'\)/);
assert.match(ixFeature, /\$this->db->select\(\$wpdb->prepare\("SELECT `dstart` FROM `#__mec_dates` WHERE `post_id`=%d ORDER BY `tstart` ASC LIMIT 50", \$post_id\), 'loadColumn'\)/);
// CVE-2026-105067: get_bookings() concatenates its arguments into SQL, and the
// logged-in bookings list and the FES CSV export passed request values to it.
const getBookings = plugin('app/libraries/main.php').match(/public function get_bookings\([^)]*\)\s*\{([\s\S]*?)\$records = \$db->select/)?.[1] ?? '';
assert.match(getBookings, /\$event_id = \(int\) \$event_id;\s*if \(\$event_id <= 0\) return \[\];/, 'get_bookings must cast the event ID');
assert.match(getBookings, /if \(\$timestamp and !is_numeric\(\$timestamp\)\) return \[\];\s*\$timestamp = \$timestamp \? \(int\) \$timestamp : null;/, 'get_bookings must cast the timestamp');
assert.match(getBookings, /\$user_id = \$user_id \? \(int\) \$user_id : null;/, 'get_bookings must cast the user ID');
assert.match(getBookings, /\$limit = is_numeric\(\$limit\) \? \(int\) \$limit : -1;/, 'get_bookings must cast the limit');
const eventBookings = plugin('app/features/events.php').match(/public function mec_event_bookings\(\)\s*\{([\s\S]*?)get_bookings\(/)?.[1] ?? '';
assert.match(eventBookings, /wp_verify_nonce\(\$wpnonce, 'mec_event_bookings'\)/, 'mec_event_bookings must check its nonce');
assert.match(eventBookings, /current_user_can\('edit_post', \$this->main->get_original_event\(\$id\)\)/, 'mec_event_bookings must check edit rights');
assert.match(eventBookings, /\$id = isset\(\$_POST\['id'\]\) \? absint\(\$_POST\['id'\]\) : 0;/);
assert.match(plugin('app/core/src/FES/FormBuilder.php'), /action=mec_event_bookings&id=<\?php echo esc_js\(\$post->ID\); \?>&_wpnonce=<\?php echo esc_js\(wp_create_nonce\('mec_event_bookings'\)\); \?>/, 'the attendees box must send the bookings nonce');
assert.match(plugin('app/features/fes.php'), /\$timestamp = isset\(\$_REQUEST\['timestamp'\]\) \? absint\(\$_REQUEST\['timestamp'\]\) : 0;/, 'the FES CSV export timestamp must be an integer');
assert.match(plugin('app/core/src/FES/FormBuilder.php'), /if \(response\.html\) \$attendees\.html\(response\.html\);\s*\/\/[^\n]*\n\s*else \$attendees\.empty\(\)/, 'a refused bookings request must clear the attendees list');
// Same class, found by the 7.35.1.29 cross-review: quoted request strings in SQL.
const mainLib = plugin('app/libraries/main.php');
assert.match(mainLib, /\$db->select\(\$wpdb->prepare\("SELECT `ID` FROM `#__posts` WHERE `guid`=%s", \(string\) \$image_url\), 'loadResult'\)/, 'get_attach_id must prepare the URL');
for (const key of ['verification', 'cancellation']) {
    assert.match(mainLib, new RegExp(`\\$wpdb->prepare\\("SELECT \`post_id\` FROM \`#__postmeta\` WHERE \`meta_key\`='mec_${key}_key' AND \`meta_value\`=%s", \\$key\\)`), `the ${key} endpoint must prepare its key`);
}
assert.doesNotMatch(mainLib, /`guid`='\$image_url'|`meta_value`='\$key'/, 'no quoted request string may be interpolated into these lookups');
// 7.35.1.30: single event pages always call display_progress_bar(), so the
// progress-bar module ships, and a module left out of the slim build renders
// nothing instead of a PHP include warning.
assert.doesNotMatch(buildWorkflow, /--exclude='app\/modules\/progress-bar\/'/, 'the progress-bar module must ship');
assert.ok(fs.existsSync(path.join(root, 'modern-events-calendar-lite', 'app', 'modules', 'progress-bar', 'single.php')), 'progress-bar/single.php must exist');
assert.match(plugin('app/libraries/main.php'), /\$path = MEC::import\('app\.modules\.' \. \$module, true, true\);\s*\/\/[^\n]*\n\s*\/\/[^\n]*\n\s*if \(!\$path \|\| !is_file\(\$path\)\) return '';/, 'module() must skip a missing module file');
// Shell slips once committed empty files ("atts[", "id") to the plugin root,
// and the release ZIP shipped them.
const emptyRootFiles = fs.readdirSync(path.join(root, 'modern-events-calendar-lite'), { withFileTypes: true })
    .filter((entry) => entry.isFile() && entry.name !== 'index.html' && fs.statSync(path.join(root, 'modern-events-calendar-lite', entry.name)).size === 0)
    .map((entry) => entry.name);
assert.deepEqual(emptyRootFiles, [], 'no empty files may sit in the plugin root');
// 7.35.1.31 (2026-10-21 audit): Import / Export forms need their own nonce, the
// OAuth callback its state, FES edits events only, and Export & Purge is off.
assert.match(ixFeature, /if \(\$this->action == 'save-sync-options' and !\$this->verify_ix_form_nonce\('mec_ix_sync'\)\)/, 'the sync form must check its nonce before saving');
assert.match(ixFeature, /in_array\(\$this->action, \['google-calendar-import-start', 'google-calendar-import-do'\], true\) and !\$this->verify_ix_form_nonce\('mec_ix_g_calendar_import'\)\) \$this->response = /, 'both Google import steps must check their nonce');
assert.match(plugin('app/features/ix/sync.php'), /wp_nonce_field\('mec_ix_sync', 'mec_ix_nonce', false\)/);
assert.equal((plugin('app/features/ix/import_g_calendar.php').match(/wp_nonce_field\('mec_ix_g_calendar_import', 'mec_ix_nonce', false\)/g) || []).length, 2, 'both Google import forms must print their nonce');
assert.match(ixFeature, /\$client->setState\(wp_create_nonce\('mec_ix_g_calendar_oauth'\)\);/, 'the Google authorization link must carry a state');
assert.match(ixFeature, /if \(!\$state or !wp_verify_nonce\(\$state, 'mec_ix_g_calendar_oauth'\)\) wp_die\(/, 'the OAuth callback must check its state');
const oauthCallback = ixFeature.slice(ixFeature.indexOf('public function g_calendar_export_get_token()'), ixFeature.indexOf('public function g_calendar_export_do()'));
assert.match(oauthCallback, /wp_verify_nonce\(\$state, 'mec_ix_g_calendar_oauth'\)[\s\S]*if \(isset\(\$_GET\['error'\]\)\) wp_die\([^;]*;[\s\S]*\$client->authenticate\(/, 'the OAuth callback must handle errors after checking state and before authenticating');
assert.match(fesFeature, /if \(\$post_id == -1\) return true;\s*(\/\/[^\n]*\n\s*)+if \(\(int\) \$post_id <= 0 \|\| get_post_type\(\$post_id\) !== \$this->PT\) return false;/, 'FES may only edit events');
const reportFeature = plugin('app/features/report.php');
assert.match(reportFeature, /public function export_purge\(\)\s*\{\s*wp_die\([^;]*'response' => 410[^;]*;\s*\}/, 'Export & Purge must only answer 410');
assert.doesNotMatch(reportFeature, /new MEC_feature_books|mec-exports|wp_delete_post/, 'Export & Purge must stay disabled');
assert.doesNotMatch(plugin('app/features/report/tpl.php'), /mec_export_purge/, 'the report page must not offer the Export & Purge form');
// 7.35.1.32 (PR #48 review): the Report page's AJAX actions, among them a mailer,
// exist only with the booking module on; FES removes events only.
const reportInit = reportFeature.slice(reportFeature.indexOf('public function init()'), reportFeature.indexOf('public function menu()'));
const reportGate = reportInit.indexOf('if (!$this->booking_enabled()) return;');
assert.ok(reportGate > 0, 'the report AJAX actions must be gated on the booking module');
for (const action of ['report_event_dates', 'mec_attendees', 'mec_mass_email', 'mec_report_mass']) {
    const at = reportInit.indexOf(`'wp_ajax_${action}'`);
    assert.ok(at > reportGate, `wp_ajax_${action} must only be registered with the booking module on`);
}
assert.match(reportFeature, /private function booking_enabled\(\)\s*\{\s*return isset\(\$this->settings\['booking_status'\]\) && \$this->settings\['booking_status'\];\s*\}/);
assert.match(reportFeature, /public function menu\(\)\s*\{\s*if \(\$this->booking_enabled\(\)\)/, 'the Report menu must use the same gate');
assert.doesNotMatch(reportFeature, /\$_REQUEST\['nonce'\]\)/, 'report nonces must be read with ?? so a missing one causes no warning');
assert.match(fesFeature, /\$post_id = isset\(\$_POST\['post_id'\]\) \? absint\(\$_POST\['post_id'\]\) : 0;\s*(\/\/[^\n]*\n\s*)+if \(!\$post_id \|\| get_post_type\(\$post_id\) !== \$this->PT\) \$this->main->response\(\['success' => 0, 'code' => 'INVALID_EVENT'\]\);\s*(\/\/[^\n]*\n\s*)*if \(!current_user_can\('delete_post', \$post_id\)\)/, 'FES may only remove events');
console.log('Customization checks passed.');
