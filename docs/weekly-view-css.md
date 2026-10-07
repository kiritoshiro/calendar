# Weekly View stylesheet

Public pages rendered with only Weekly View (the magenda month grid and day agenda), without a search form or other MEC shortcode, can load `assets/css/weekly-view.min.css` instead of the full frontend stylesheet. This uses the existing footer-time skin detector. Event pages, archives/taxonomies, mixed calendars, editor previews, RTL pages and early/head loading retain their current full stylesheet. An already queued or printed full stylesheet is respected.

The `mec-frontend-style` handle remains the same, including dependencies, version and inline CSS. Only its registered source URL changes after page rendering identifies an eligible request. Tooltip/icon/Featherlight assets retain their existing loaders. Every shared rule, single-event/popup module, animation, font definition and Weekly View state stays in the generated bundle; only selectors requiring another listing layout, search/FES form or Lity are omitted. Classes inside functional pseudo-classes are conservatively retained. This does not purge against a homepage DOM snapshot.

The generated file is committed and ships in the ordinary plugin package. When changing `frontend.min.css` or the layout exclusions:

```sh
npm ci --ignore-scripts
npm run build:weekly-css
npm run check:weekly-css
```

CI verifies that the file is reproducible and audits the development-only parser dependencies. It does not run Node or generate CSS on the WordPress site. The full stylesheet remains the source and fallback.

Custom skin extensions can preserve the full CSS with:

```php
add_filter('mec_weekly_view_css', '__return_false');
```

Before extending the exclusions, compare real WordPress output with the full stylesheet at mobile/tablet/desktop widths, including dark mode, popups, month/year pickers, day selection, AJAX loading/empty states and keyboard controls. Check full/mixed/search/RTL/editor fallbacks and custom inline styles. A shared class can appear in Weekly View even when its name suggests another skin: `mec-event-list-weekly-date` is one example.

Current gzip measurement at the default Node compression level: 87,427 bytes for the full file and 48,613 bytes for Weekly View, about 44% less. Production Brotli/gzip settings and PageSpeed results may differ; no deployed performance score is implied.
