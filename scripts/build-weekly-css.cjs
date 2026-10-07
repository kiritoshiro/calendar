// Keep shared, Weekly View, tooltip and popup styles in their original cascade.
// This is a layout split, not a purge based on a single rendered DOM snapshot.
const fs = require('node:fs');
const path = require('node:path');
const zlib = require('node:zlib');
const postcss = require('postcss');
const selectorParser = require('postcss-selector-parser');

const root = path.resolve(__dirname, '..');
const dir = path.join(root, 'modern-events-calendar-lite/assets/css');
const source = fs.readFileSync(path.join(dir, 'frontend.min.css'), 'utf8');
// These roots belong to other listing/calendar skins. Generic event classes,
// all single-event/modal modules, and Weekly View states are deliberately kept.
const otherLayouts = [
    /^mec-event-list-(?!weekly(?:-|$))/,
    /^mec-event-(?:grid|carousel|countdown|cover|agenda|tile|masonry)(?:-|$)/,
    /^mec-skin-(?:list|grid|carousel|countdown|cover|daily|full|map|masonry|monthly|slider|tile)(?:-|$)/,
    /^mec-calendar$/,
    /^mec-calendar-(?:daily|timetable)(?:-|$)/,
    /^mec-daily-(?:view|contents|today)(?:-|$)/,
    /^mec-(?:yearly|timetable|timeline|tile|toggle|ymtabs)(?:-|$)/,
    /^mec-slider-t[1-5](?:-|$)/,
    /^mec-events-(?:agenda|toggle)(?:-|$)/,
    /^mec-totalcal/,
    /^mec-fluent-wrap$/,
    /^mec-owl-/,
    /^mec-event-calendar-classic(?:-|$)/,
    /^mec-event-container-(?:classic|novel|simple)(?:-|$)/,
    /^event-(?:carousel|grid|tile)(?:-|$)/,
    /^owl-(?:carousel|nav|dots|dot|item|prev|next|page|wrapper-outer)(?:-|$)/,
    /^fc(?:-|$)/,
    // The lean detector rejects every search form/FES shortcode. The event
    // popup uses Featherlight, not Lity. Retain its single-event and booking
    // styles even if they're not present in today's fixture.
    /^mec-fes-/,
    /^mec-search-/,
    /^lity(?:-|$)/,
];

const css = postcss.parse(source);
css.walkRules(rule => {
    // Keyframe selectors aren't DOM selectors.
    if (rule.parent.type === 'atrule' && /keyframes$/i.test(rule.parent.name)) return;
    const selectors = selectorParser().astSync(rule.selector);
    for (const selector of [...selectors.nodes]) {
        let excluded = false;
        selector.walkClasses(node => {
            // A class under :not/:is/:where/:has isn't an unconditional layout
            // requirement. Retain that selector rather than guessing its logic.
            if (node.parent === selector && otherLayouts.some(pattern => pattern.test(node.value))) excluded = true;
        });
        if (excluded) selector.remove();
    }
    if (!selectors.nodes.length) rule.remove();
    else rule.selector = selectors.toString();
});
// Drop empty media/supports groups, retaining every animation/font definition.
for (let pass = 0; pass < 3; pass++) css.walkAtRules(rule => {
    if (rule.nodes && !rule.nodes.length) rule.remove();
});
const output = '/*! Generated from frontend.min.css by scripts/build-weekly-css.cjs; do not edit. */\n' + css.toString() + '\n';
const target = path.join(dir, 'weekly-view.min.css');
if (process.argv.includes('--check')) {
    if (!fs.existsSync(target) || fs.readFileSync(target, 'utf8') !== output) {
        console.error('Weekly View CSS is stale. Run npm run build:weekly-css.');
        process.exitCode = 1;
    }
} else fs.writeFileSync(target, output);
console.log(JSON.stringify({sourceBytes:Buffer.byteLength(source),weeklyBytes:Buffer.byteLength(output),sourceGzip:zlib.gzipSync(source).length,weeklyGzip:zlib.gzipSync(output).length}));
