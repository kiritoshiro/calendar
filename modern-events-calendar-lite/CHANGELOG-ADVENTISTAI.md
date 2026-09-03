# adventistai.lt fork changelog

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
