# Weekly View (magenda) stylesheet

Public pages whose only MEC output is the magenda (Weekly View) without a search form load `assets/css/magenda.min.css` (about 24 KB, 4.5 KB gzipped) instead of `frontend.min.css` (597 KB) and `iconfonts.css` (48 KB). The footer-time skin detector (`lean_assets_only()`) decides this after the page has rendered. Event pages, archives/taxonomies, mixed calendars, search forms, editor previews, RTL pages and head loading keep the full stylesheets.

The trimmed file holds every rule that mentions `mec-magenda` (in all states), the base MEC rules the magenda uses (`ANCHORS` in `tests/build-magenda-css.js`), icon glyphs and the `@font-face` rules. The event popup's page needs the full styles: `mecdata.full_styles` lists both full files and `mecSingleEventDisplayer.loadFullStyles()` adds them when a popup opens, or earlier when the pointer or keyboard reaches an event link.

The full stylesheet is kept (the trimmed one is not used) when:

- `mec-frontend-style` was already queued or printed on the page, for example by another plugin or skin;
- `assets/css/magenda.min.css` is missing or unreadable;
- a site opts out:

```php
add_filter('mec_weekly_view_css', '__return_false');
```

When `frontend.min.css` or the magenda styles change, rebuild and commit the file; CI fails while it is out of date:

```sh
node tests/build-magenda-css.js
node tests/build-magenda-css.js --check
```

Before adding anchors, compare computed styles with the full stylesheet on a magenda page: several months, month and year pickers, a selected day, an empty month, phone and desktop widths, and an event popup.

History: PR #19 introduced the trimmed file and on-demand popup styles (7.35.1.17). PR #20 proposed a larger conservative bundle (48.6 KB gzipped, popup styles included, PostCSS build); the two were reconciled by keeping #19's file and adopting #20's guards and opt-out filter (7.35.1.18).
