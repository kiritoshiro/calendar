# adventistai.lt fork changelog

## 7.35.1.8

- Moved System Information and Debug Log onto the M.E. Calendar dashboard.
- Removed the standalone Support submenu, page template, and related admin-screen wiring.

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
