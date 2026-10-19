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

All markers are harmless strings ("pgmarker", an apostrophe in a key).

Usage: python3 .github/playground/bookings.py [--php 8.4] [--wp latest] [--plugin-dir DIR]
"""
import argparse
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


def run_tests(log, php):
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
        run_tests(Log(os.path.join(logs_dir, "bookings.log")), args.php)
    finally:
        smoke.stop_server(proc)
        report_debug_log(logs_dir)
        shutil.rmtree(workdir, ignore_errors=True)
    if failures:
        sys.exit(f"{len(failures)} bookings test failure(s)")
    print("Bookings behaviour test passed.")


if __name__ == "__main__":
    main()
