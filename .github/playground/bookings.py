#!/usr/bin/env python3
"""Behavioural regression test for the 7.35.1.29 bookings fix (CVE-2026-105067).

Boots a throwaway WordPress Playground with the plugin and a test-only mu-plugin
(.github/playground/bookings-mu-plugin.php), which turns the booking module back
on (the fork forces it off), adds events and bookings, and logs every
mec_bookings query. It then checks, as a subscriber, an event's author (a
subscriber), a WPML translator and the administrator, that:

- mec_event_bookings refuses a request without a nonce or edit rights before any
  bookings query runs, and still answers the event's author and an admin;
- the edit check uses the WPML original event;
- MEC_main::get_bookings() returns [] for a bad event ID or a non-numeric
  timestamp, keeps the unlimited default limit, and builds its SQL from integers;
- the FES CSV export turns its timestamp into an integer;
- get_attach_id() and the booking verify/cancel endpoints still find their rows.

And for the 7.35.1.31 audit fixes (2026-10-21):

- the FES form refuses pages, posts and attachments, also ones the caller owns,
  and other people's events, but still saves an author's own event;
- the attendee report refuses subscribers and requests without its nonce;
- the Import / Export synchronization and Google import forms save nothing
  without their nonce, and the Google OAuth callback needs the state from the
  site's own authorization link;
- Export & Purge is disabled: it answers 410 and deletes no booking.

And for the follow-ups from the PR #48 review (7.35.1.32):

- with bookings off, as in production, the Report page's AJAX actions are not
  registered, so mec_mass_email sends nothing; with bookings on, the mailer
  still refuses subscribers and requests without its nonce, and a missing
  nonce causes no PHP warning;
- the FES remove action refuses pages, posts and attachments the caller may
  delete, but still removes an event.

All markers are harmless strings ("pgmarker", an apostrophe in a key).

Usage: python3 .github/playground/bookings.py [--php 8.4] [--wp latest] [--plugin-dir DIR]
"""
import argparse
import html
import json
import os
import re
import secrets
import shutil
import sys
import tempfile
import urllib.parse

sys.dont_write_bytecode = True  # no __pycache__ next to smoke.py
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import smoke  # noqa: E402  (shares the Playground boot/stop and HTTP helpers)

HERE = os.path.dirname(os.path.abspath(__file__))
PLUGIN = "modern-events-calendar-lite/modern-events-calendar-lite.php"

failures = []


def check(name, ok, detail=None):
    if ok:
        print(f"ok  {name}")
    else:
        failures.append(name)
        print(f"::error::{name}")
        if detail is not None:
            print(f"    got: {str(detail)[:700]}")


def login(user, password):
    opener = smoke.make_opener()
    smoke.get(opener, "/wp-login.php")  # sets the test cookie
    form = urllib.parse.urlencode({"log": user, "pwd": password, "wp-submit": "Log In",
                                   "redirect_to": smoke.BASE + "/wp-admin/", "testcookie": "1"}).encode()
    status, final, _ = smoke.get(opener, "/wp-login.php", data=form)
    if "/wp-admin" not in final:
        sys.exit(f"::error::could not log in as {user} (HTTP {status})")
    return opener


def ajax(opener, data):
    status, _, body = smoke.get(opener, "/wp-admin/admin-ajax.php", data=urllib.parse.urlencode(data).encode())
    return status, body


def ajax_json(opener, data):
    status, body = ajax(opener, data)
    start = body.find("{")
    try:
        return json.loads(body[start:]) if start >= 0 else None
    except ValueError:
        return None


class Log:
    """The mu-plugin's log of mec_bookings queries and edit_post checks."""

    def __init__(self, path):
        self.path = path
        self.offset = 0

    def since_last(self):
        if not os.path.exists(self.path):
            return []
        with open(self.path, encoding="utf-8", errors="replace") as fh:
            fh.seek(self.offset)
            text = fh.read()
            self.offset = fh.tell()
        return [line for line in text.splitlines() if line]


def run_tests(log, debug, php):
    admin = login(smoke.USER, smoke.PASSWORD)
    state = ajax_json(admin, {"action": "pgtest_state"}) or {}
    if not state.get("registered"):
        sys.exit(f"::error::mec_event_bookings is not registered; the test mu-plugin did not turn bookings on: {state}")
    print(f"PHP {state.get('php')} in Playground")
    if not str(state.get("php", "")).startswith(php + "."):
        sys.exit(f"::error::asked for PHP {php}, but Playground runs PHP {state.get('php')}")

    password = secrets.token_urlsafe(18)  # throwaway Playground users, never printed
    ids = ajax_json(admin, {"action": "pgtest_setup", "password": password})
    if not ids or "event" not in ids:
        sys.exit(f"::error::fixtures were not created: {ids}")
    users = {name: login(name, password) for name in ("pgsub", "pgauthor", "pgtranslator")}
    users["admin"] = admin
    nonces = {name: ajax_json(op, {"action": "pgtest_nonces"}) for name, op in users.items()}
    t1 = ids["t1"]
    occurrence = f"{t1}:{t1 + 3600}"
    log.since_last()

    def bookings(name, user, expect, event, nonce=True, **extra):
        """POST mec_event_bookings; expect is a refusal code, or the attendee emails listed."""
        data = {"action": "mec_event_bookings", "id": event, "occurrence": extra.pop("occurrence", occurrence), "backend": 1}
        if nonce:
            data["_wpnonce"] = nonces[user]["bookings"]
        data.update(extra)
        response = ajax_json(users[user], data) or {}
        lines = log.since_last()
        queries = [line for line in lines if line.startswith("query: ")]
        html = response.get("html", "")
        emails = sorted(set(re.findall(r"[\w.-]+@example\.test", html)))
        if isinstance(expect, str):
            check(f"{name}: refused with {expect}, before any bookings query",
                  response.get("code") == expect and not queries and not emails, [response, queries])
        else:
            check(f"{name}: lists {expect or 'no attendees'}", emails == sorted(expect) and "html" in response, [response, queries])
        return lines

    event, author_event = ids["event"], ids["author_event"]
    bookings("subscriber without a nonce", "pgsub", "NONCE_IS_INVALID", event, nonce=False)
    bookings("admin without a nonce", "admin", "NONCE_IS_INVALID", event, nonce=False)
    bookings("subscriber with a nonce, admin's event", "pgsub", "NO_ACCESS", event)
    bookings("subscriber with a nonce, another subscriber's event", "pgsub", "NO_ACCESS", author_event)
    bookings("subscriber author, own event", "pgauthor", ["author-event@example.test"], author_event)
    bookings("subscriber author, admin's event", "pgauthor", "NO_ACCESS", event)
    bookings("admin, one occurrence", "admin", ["admin-event-1@example.test"], event)
    bookings("admin, a page instead of an event", "admin", "INVALID_EVENT", ids["page"])
    bookings("admin, a non-numeric event ID", "admin", "INVALID_EVENT", "pgmarker")
    lines = bookings("admin, event ID with a marker suffix", "admin", ["admin-event-1@example.test"], f"{event}pgmarker")
    check("only the integer event ID reaches the edit check and the bookings query",
          f"edit_post: {event}" in lines and not any("pgmarker" in line for line in lines), lines)
    lines = bookings("admin, non-numeric occurrence", "admin", [], event, occurrence=f"{t1}pgmarker")
    check("a non-numeric occurrence runs no bookings query", not any(line.startswith("query: ") for line in lines), lines)
    bookings("WPML: translator may edit the original, asks for the translation", "pgtranslator",
             ["translation-event@example.test"], ids["wpml_translation"], pgtest_wpml=1)
    bookings("translator without WPML (no original to fall back on)", "pgtranslator", "NO_ACCESS", ids["wpml_translation"])

    # FES CSV export: a timestamp with a marker suffix still selects that occurrence.
    status, csv = ajax(admin, {"action": "mec_fes_csv_export", "mec_event_id": event, "fes_nonce": nonces["admin"]["fes"],
                               "timestamp": f"{t1}pgmarker", "type": "csv"})
    lines = log.since_last()
    check("FES CSV export turns the timestamp into an integer",
          status == 200 and "admin-event-1@example.test" in csv and "admin-event-2@example.test" not in csv
          and not any("pgmarker" in line for line in lines), [status, csv[:300], lines])

    unit = ajax_json(admin, {"action": "pgtest_unit"})
    if not unit or "checks" not in unit:
        check("MEC_main checks ran", False, unit)
    else:
        for item in unit["checks"]:
            check(item["name"], item["ok"], item.get("detail"))

    run_audit_tests(users, nonces, ids, t1)
    run_review_followup_tests(users, nonces, ids, log, debug)


def page_text(body):
    return html.unescape(re.sub(r"\s+", " ", re.sub(r"<(script|style)\b.*?</\1>|<[^>]+>", " ", body, flags=re.S | re.I)))


def form_nonce(body):
    match = re.search(r'name="mec_ix_nonce" value="([0-9a-f]+)"', body)
    return match.group(1) if match else None


def run_audit_tests(users, nonces, ids, t1):
    """The 7.35.1.31 fixes from the 2026-10-21 security audit."""
    admin = users["admin"]

    def state():
        return ajax_json(admin, {"action": "pgtest_state"}) or {}

    # A2: FES only edits events. pgsub owns a page, a post and an attachment but
    # has no edit rights on them; pgauthor owns an event.
    before = state().get("posts", {})

    def fes(name, user, target, expect_ok):
        response = ajax_json(users[user], {"action": "mec_fes_form", "_wpnonce": nonces[user]["fes_form"],
                                           "mec[post_id]": ids[target], "mec[title]": "pgmarker title",
                                           "mec[content]": "pgmarker content"}) or {}
        post = state().get("posts", {}).get(target, {})
        if expect_ok:
            check(f"FES: {name} is saved", response.get("success") == 1 and post.get("title") == "pgmarker title", [response, post])
        else:
            check(f"FES: {name} is refused and unchanged",
                  response.get("code") == "NO_ACCESS" and post == before.get(target), [response, post, before.get(target)])

    fes("subscriber's own page", "pgsub", "sub_page", False)
    fes("subscriber's own post", "pgsub", "sub_post", False)
    fes("subscriber's own attachment", "pgsub", "sub_attachment", False)
    fes("subscriber, admin's page", "pgsub", "page", False)
    fes("subscriber, another author's event", "pgsub", "author_event", False)
    fes("admin, a page (FES edits events only)", "admin", "page", False)
    fes("subscriber author, own event", "pgauthor", "author_event", True)

    # Attendee report (CVE-2026-110069): report users with the settings nonce only.
    attendees = {"action": "mec_attendees", "id": ids["event"], "occurrence": f"{t1}:{t1 + 3600}"}
    response = ajax_json(users["pgsub"], dict(attendees, nonce=nonces["pgsub"]["report"])) or {}
    check("mec_attendees refuses a subscriber", response.get("code") == "ADMIN_ONLY", response)
    response = ajax_json(admin, attendees) or {}
    check("mec_attendees refuses a request without its nonce", response.get("code") == "NONCE_IS_INVALID", response)
    response = ajax_json(admin, dict(attendees, nonce=nonces["admin"]["report"])) or {}
    check("mec_attendees lists the occurrence's attendees for an admin",
          "admin-event-1@example.test" in response.get("html", "") and "admin-event-2@example.test" not in response.get("html", ""), response)

    # A1: Import / Export forms need their own nonce.
    sync_path = "/wp-admin/admin.php?page=MEC-ix&tab=MEC-sync"
    status, _, body = smoke.get(admin, sync_path)
    sync_nonce = form_nonce(body)
    check("the synchronization form prints its nonce", status == 200 and sync_nonce, status)
    expired = "Your session expired"
    for name, extra in (("without a nonce", {}), ("with another form's nonce", {"mec_ix_nonce": nonces["admin"]["ix_import"]})):
        data = dict({"mec-ix-action": "save-sync-options", "ix[sync_g_import]": "1", "ix[sync_g_export]": "1"}, **extra)
        status, _, body = smoke.get(admin, sync_path, data=urllib.parse.urlencode(data).encode())
        ix = state().get("ix", {})
        check(f"synchronization settings are not saved {name}",
              expired in page_text(body) and str(ix.get("sync_g_import", 0)) != "1" and str(ix.get("sync_g_export", 0)) != "1", [status, ix])
    data = {"mec-ix-action": "save-sync-options", "ix[sync_g_import]": "1", "ix[sync_g_export]": "0", "mec_ix_nonce": sync_nonce or ""}
    status, _, body = smoke.get(admin, sync_path, data=urllib.parse.urlencode(data).encode())
    ix = state().get("ix", {})
    check("synchronization settings are saved with the form's nonce",
          expired not in page_text(body) and str(ix.get("sync_g_import")) == "1" and str(ix.get("sync_g_export")) == "0", [status, ix])

    import_path = "/wp-admin/admin.php?page=MEC-ix"
    status, _, body = smoke.get(admin, import_path)
    import_nonce = form_nonce(body)
    check("the Google import form prints its nonce", status == 200 and import_nonce, status)
    data = {"mec-ix-action": "google-calendar-import-start", "ix[google_import_api_key]": "pgmarker-key", "ix[google_import_calendar_id]": "pgmarker-calendar"}
    status, _, body = smoke.get(admin, import_path, data=urllib.parse.urlencode(data).encode())
    check("Google import start saves nothing without a nonce",
          expired in page_text(body) and state().get("ix", {}).get("google_import_api_key") != "pgmarker-key", status)
    data = {"mec-ix-action": "google-calendar-import-start", "ix[google_import_api_key]": "", "ix[google_import_calendar_id]": "", "mec_ix_nonce": import_nonce or ""}
    status, _, body = smoke.get(admin, import_path, data=urllib.parse.urlencode(data).encode())
    check("Google import start accepts the form's nonce", "API key and Calendar ID are required" in page_text(body), [status, page_text(body)[:300]])
    data = {"mec-ix-action": "google-calendar-import-do", "g-events[]": "pgmarker", "ix[google_import_api_key]": "pgmarker-key", "ix[google_import_calendar_id]": "pgmarker-calendar"}
    status, _, body = smoke.get(admin, import_path, data=urllib.parse.urlencode(data).encode())
    check("Google import refuses to import without a nonce", expired in page_text(body), [status, page_text(body)[:300]])
    data = {"mec-ix-action": "google-calendar-import-do", "mec_ix_nonce": import_nonce or ""}
    status, _, body = smoke.get(admin, import_path, data=urllib.parse.urlencode(data).encode())
    check("Google import accepts the form's nonce", "Please select events to import" in page_text(body), [status, page_text(body)[:300]])

    callback = "/wp-admin/admin.php?page=MEC-ix&tab=MEC-g-calendar-export&mec-ix-action=google-calendar-export-get-token&code=pgmarker"
    stale_link = "This Google sign-in link has expired or is invalid"
    for name, suffix in (("without a state", ""), ("with a forged state", "&state=pgmarker")):
        status, _, body = smoke.get(admin, callback + suffix)
        check(f"the Google OAuth callback is refused {name}", status == 403 and stale_link in page_text(body), [status, page_text(body)[:300]])
    response = ajax_json(admin, {"action": "mec_ix_g_calendar_authenticate", "mec_ix_nonce": nonces["admin"]["ix_export"],
                                 "ix[google_export_client_id]": "pgmarker-client", "ix[google_export_client_secret]": "pgmarker-secret",
                                 "ix[google_export_calendar_id]": "pgmarker-calendar"}) or {}
    oauth_state = re.search(r"[?&;]state=([0-9a-f]+)", html.unescape(response.get("message", "")))
    valid = oauth_state and (ajax_json(admin, {"action": "pgtest_oauth_state", "state": oauth_state.group(1)}) or {}).get("valid")
    check("the Google authorization link carries a valid state", response.get("success") == 1 and valid, response)
    # Google's error path accepts the state without making a token request.
    status, _, body = smoke.get(admin, callback.replace("&code=pgmarker", "") + "&" + urllib.parse.urlencode(
        {"state": oauth_state.group(1) if oauth_state else "", "error": "access_denied"}))
    check("the Google OAuth callback accepts the authorization link's state on cancellation",
          status == 400 and stale_link not in page_text(body)
          and "Google sign-in was cancelled or failed. Try Authenticate again." in page_text(body), [status, page_text(body)[:300]])

    # A3: Export & Purge is disabled and deletes nothing.
    counts = state()
    status, _, body = smoke.get(admin, "/wp-admin/admin-post.php", data=urllib.parse.urlencode(
        {"action": "mec_export_purge", "_wpnonce": nonces["admin"]["purge"], "mec_export_purge_cutoff": "2100-01-01"}).encode())
    after = state()
    check("Export & Purge answers 410 and deletes no booking",
          status == 410 and "not available" in page_text(body)
          and (after.get("booking_rows"), after.get("booking_posts")) == (counts.get("booking_rows"), counts.get("booking_posts")) and counts.get("booking_rows"),
          [status, counts.get("booking_rows"), after.get("booking_rows"), counts.get("booking_posts"), after.get("booking_posts")])
    status, _, body = smoke.get(admin, "/wp-admin/admin.php?page=MEC-report&tab=export_purge")
    check("the report page offers no Export & Purge form",
          status == 200 and "not available" in page_text(body) and "mec_export_purge" not in body, status)


def run_review_followup_tests(users, nonces, ids, log, debug):
    """7.35.1.32: the follow-ups from the PR #48 review."""
    admin = users["admin"]

    def state(**extra):
        return ajax_json(admin, dict({"action": "pgtest_state"}, **extra)) or {}

    # The Report page's AJAX actions exist only with the booking module on.
    report_actions = ["report_event_dates", "mec_attendees", "mec_mass_email", "mec_report_mass"]
    off = state(pgtest_bookings_off=1)
    check("with bookings off (as in production), no report AJAX action is registered",
          off.get("report_ajax") == [] and not off.get("registered"), [off.get("report_ajax"), off.get("registered")])
    check("with bookings on, the report AJAX actions are registered", state().get("report_ajax") == report_actions, state().get("report_ajax"))

    mail = {"action": "mec_mass_email", "nonce": nonces["admin"]["report"], "mail_recipients_info": "Pg:.:pgmarker@example.test",
            "mail_subject": "pgmarker subject", "mail_content": "<p>pgmarker</p>"}

    def mass_email(name, user, data, expect_sent):
        log.since_last()
        status, body = ajax(users[user], data)
        mails = [line for line in log.since_last() if line.startswith("mail: ")]
        if expect_sent:
            check(f"mec_mass_email {name} sends the e-mail", body.strip() == "1" and mails == ["mail: pgmarker@example.test pgmarker subject"], [status, body[:200], mails])
        else:
            check(f"mec_mass_email {name} sends nothing", body.strip() != "1" and not mails, [status, body[:200], mails])
        return status, body

    status, body = mass_email("with bookings off", "admin", dict(mail, pgtest_bookings_off=1), False)
    check("with bookings off, admin-ajax does not know mec_mass_email", status == 400 and body.strip() == "0", [status, body[:200]])
    debug.since_last()
    mass_email("without a nonce", "admin", {key: value for key, value in mail.items() if key != "nonce"}, False)
    _, body = ajax(admin, {"action": "report_event_dates", "event_id": ids["event"]})
    check("report_event_dates answers nothing without a nonce", body.strip() == "", body[:200])
    warnings = [line for line in debug.since_last() if "features/report.php" in line]
    check("a missing report nonce causes no PHP warning", not warnings, warnings)
    status, body = mass_email("for a subscriber with their own nonce", "pgsub", dict(mail, nonce=nonces["pgsub"]["report"]), False)
    check("mec_mass_email refuses a subscriber", '"NO_ACCESS"' in body, [status, body[:200]])
    mass_email("for an admin with the nonce (bookings on)", "admin", mail, True)

    # FES removes events only, even where WordPress lets the caller delete the post.
    before = state().get("posts", {})

    def remove(name, user, target, expect):
        response = ajax_json(users[user], {"action": "mec_fes_remove", "_wpnonce": nonces[user]["fes_remove"], "post_id": ids[target]}) or {}
        post = state().get("posts", {}).get(target)
        if expect == "removed":
            # wp_delete_post() trashes only posts and pages; an event is deleted.
            check(f"FES remove: {name} is removed", response.get("success") == 1 and before.get(target) and post is None, [response, post])
        else:
            check(f"FES remove: {name} is refused with {expect} and kept",
                  response.get("code") == expect and post and post == before.get(target), [response, post, before.get(target)])

    remove("admin, a page", "admin", "page", "INVALID_EVENT")
    remove("admin, a subscriber's post", "admin", "sub_post", "INVALID_EVENT")
    remove("admin, a subscriber's attachment", "admin", "sub_attachment", "INVALID_EVENT")
    remove("subscriber, admin's event", "pgsub", "remove_event", "USER_CANNOT_REMOVE_EVENT")
    remove("admin, an event", "admin", "remove_event", "removed")


def report_debug_log(logs_dir):
    """PHP fatals fail the test; warnings in the booking module (disabled in production) are only reported."""
    path = os.path.join(logs_dir, "debug.log")
    if not os.path.exists(path):
        return
    seen = set()
    with open(path, encoding="utf-8", errors="replace") as fh:
        for line in fh:
            if not smoke.PHP_LOG_LINE.search(line):
                continue
            message = line.split("] ", 1)[-1].strip()
            if message in seen:
                continue
            seen.add(message)
            if re.search(r"PHP (Fatal|Parse|Recoverable fatal) error", message):
                failures.append("debug.log: " + message[:400])
                print("::error::debug.log: " + message[:400])
            else:
                print("::warning::debug.log: " + message[:300])


def main():
    parser = argparse.ArgumentParser()
    parser.add_argument("--php", default="8.4")
    parser.add_argument("--wp", default="latest")
    parser.add_argument("--plugin-dir", default="modern-events-calendar-lite",
                        help="plugin folder to mount (another copy, to check that the test catches a reverted guard)")
    parser.add_argument("--port", type=int, default=smoke.PORT)
    parser.add_argument("--boot-timeout", type=int, default=420)
    args = parser.parse_args()
    smoke.PORT, smoke.BASE = args.port, f"http://127.0.0.1:{args.port}"

    with open(os.path.join(HERE, "bookings-mu-plugin.php"), encoding="utf-8") as fh:
        mu_plugin = fh.read()
    config = {
        "mounts": [{"host": os.path.abspath(args.plugin_dir), "vfs": "/wordpress/wp-content/plugins/modern-events-calendar-lite"}],
        "plugins": [PLUGIN],
        "preferredVersions": {"php": args.php, "wp": args.wp},
        "setup": [{"step": "writeFile", "path": "/wordpress/wp-content/mu-plugins/pgtest-bookings.php", "data": mu_plugin}],
    }
    workdir = tempfile.mkdtemp(prefix="playground-bookings-")
    logs_dir = os.path.join(workdir, "logs")
    os.makedirs(logs_dir)
    if smoke.port_in_use():
        sys.exit(f"::error::port {smoke.PORT} is already in use")
    proc = smoke.start_server(args, config, os.getcwd(), logs_dir, workdir)
    try:
        run_tests(Log(os.path.join(logs_dir, "bookings.log")), Log(os.path.join(logs_dir, "debug.log")), args.php)
    finally:
        smoke.stop_server(proc)
        report_debug_log(logs_dir)
        shutil.rmtree(workdir, ignore_errors=True)
    if failures:
        sys.exit(f"{len(failures)} bookings test failure(s)")
    print("Bookings behaviour test passed.")


if __name__ == "__main__":
    main()
