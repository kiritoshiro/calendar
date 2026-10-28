#!/usr/bin/env python3
"""Behavioural regression test for 7.35.1.33: FES guest accounts and the event
gallery upload (2026-10-27 assessment, finding 3).

Boots a throwaway WordPress Playground with the plugin and a test-only mu-plugin
(.github/playground/fes-guest-mu-plugin.php), which turns on guest submission,
guest account creation and the event gallery, limits FES to administrators and
subscribers, and stops all e-mail. It then checks that:

- the gallery upload refuses a contributor (no upload_files, not an FES role), a
  request without its nonce, and a guest while guest submission is off; it still
  accepts a subscriber in the FES roles, a guest while guest submission is on,
  and an administrator;
- publishing a guest's event never makes an existing account its author;
- publishing a guest's event with a new address creates a subscriber (not an
  Author) and makes it the event's author;
- a logged-in user's event creates no account and keeps its author, whatever
  guest address the request carries.

Usage: python3 .github/playground/fes_guest.py [--php 8.4] [--wp latest] [--plugin-dir DIR]
"""
import argparse
import base64
import json
import os
import secrets
import shutil
import sys
import tempfile
import urllib.error
import urllib.request
import uuid

sys.dont_write_bytecode = True
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
import smoke  # noqa: E402
import bookings  # noqa: E402  (login, AJAX and check helpers)
from bookings import ajax_json, check, login  # noqa: E402

HERE = os.path.dirname(os.path.abspath(__file__))

# A valid 1x1 PNG, so WordPress's file type check accepts it.
PNG = base64.b64decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==")


def upload(opener, fields):
    """POST a multipart gallery upload with one PNG; return the JSON answer."""
    boundary = uuid.uuid4().hex
    parts = []
    for name, value in fields.items():
        parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{name}"\r\n\r\n{value}\r\n'.encode())
    parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="images[]"; filename="pgmarker.png"\r\n'
                 f'Content-Type: image/png\r\n\r\n'.encode() + PNG + b"\r\n")
    parts.append(f"--{boundary}--\r\n".encode())
    request = urllib.request.Request(smoke.BASE + "/wp-admin/admin-ajax.php", data=b"".join(parts), headers={
        "User-Agent": "playground-smoke", "Content-Type": f"multipart/form-data; boundary={boundary}"})
    try:
        with opener.open(request, timeout=120) as resp:
            body = resp.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as err:
        body = err.read().decode("utf-8", "replace")
    start = body.find("{")
    try:
        return json.loads(body[start:]) if start >= 0 else {"raw": body[:200]}
    except ValueError:
        return {"raw": body[:200]}


def run_tests(php):
    admin = login(smoke.USER, smoke.PASSWORD)
    password = secrets.token_urlsafe(18)  # throwaway Playground users, never printed
    setup = ajax_json(admin, {"action": "pgfes_setup", "password": password}) or {}
    if "users" not in setup:
        sys.exit(f"::error::fixtures were not created: {setup}")
    users = {name: login(name, password) for name in ("pgfsub", "pgfcontrib")}
    users["admin"] = admin
    users["guest"] = smoke.make_opener()
    nonces = {name: ajax_json(op, {"action": "pgfes_nonces"}) or {} for name, op in users.items()}

    def state(title=""):
        return ajax_json(admin, {"action": "pgfes_state", "title": title}) or {}

    # Gallery upload.
    def gallery(name, user, expect, **extra):
        no_nonce = extra.pop("no_nonce", False)
        before = state().get("attachments")
        fields = dict({"action": "mec_event_gallery_image_upload", "_wpnonce": nonces[user].get("gallery", "")}, **extra)
        if no_nonce:
            fields.pop("_wpnonce")
        response = upload(users[user], fields)
        after = state().get("attachments")
        if expect == "uploaded":
            check(f"gallery upload: {name} is accepted", response.get("success") == 1 and after == before + 1, [response, before, after])
        else:
            check(f"gallery upload: {name} is refused with {expect}", response.get("code") == expect and after == before, [response, before, after])

    gallery("contributor (no upload_files, not an FES role)", "pgfcontrib", "NO_ACCESS")
    gallery("subscriber without a nonce", "pgfsub", "NONCE_MISSING", no_nonce=1)
    gallery("guest while guest submission is off", "guest", "NO_ACCESS", pgfes_guest_off=1)
    gallery("subscriber in the FES roles", "pgfsub", "uploaded")
    gallery("guest while guest submission is on", "guest", "uploaded")
    gallery("administrator", "admin", "uploaded")

    # Guest accounts on publication.
    def submit(user, title, email):
        return ajax_json(users[user], {
            "action": "mec_fes_form", "_wpnonce": nonces[user].get("fes_form", ""), "mec[post_id]": "-1",
            "mec[title]": title, "mec[content]": "pgmarker content",
            "mec[date][start][date]": "2027-01-10", "mec[date][start][hour]": "8", "mec[date][start][minutes]": "00", "mec[date][start][ampm]": "AM",
            "mec[date][end][date]": "2027-01-10", "mec[date][end][hour]": "6", "mec[date][end][minutes]": "00", "mec[date][end][ampm]": "PM",
            "mec[fes_guest_email]": email, "mec[fes_guest_name]": "Pg Marker",
        }) or {}

    def submit_and_publish(name, user, email):
        title = f"pgmarker {name}"
        response = submit(user, title, email)
        event = state(title).get("event")
        if not event:
            check(f"{name}: the event was submitted", False, response)
            return None, None
        published = ajax_json(admin, {"action": "pgfes_publish", "id": event["id"]}) or {}
        after = state(title)
        check(f"{name}: submitted pending and published", event["status"] == "pending" and published.get("ok")
              and after.get("event", {}).get("status") == "publish", [response, event, published, after.get("event")])
        return event, after

    existing = setup["users"]["pgfexisting"]
    event, after = submit_and_publish("guest event, existing address", "guest", "pgfexisting@example.test")
    if event:
        check("guest event, existing address: flagged as a guest submission", event["guest_flag"], event)
        check("guest event, existing address: the existing account does not become the author",
              after["event"]["author"] != existing and after["users"]["pgfexisting@example.test"] == {"id": existing, "roles": ["subscriber"]},
              [after["event"], after["users"].get("pgfexisting@example.test")])
        check("guest event, existing address: the guest address stays on the event", after["event"]["guest_email"] == "pgfexisting@example.test", after["event"])

    event, after = submit_and_publish("guest event, new address", "guest", "pgfnew@example.test")
    if event:
        new_user = after["users"].get("pgfnew@example.test")
        check("guest event, new address: a subscriber account is created, not an Author",
              new_user and new_user["roles"] == ["subscriber"], after["users"])
        check("guest event, new address: the new account is the event's author",
              new_user and after["event"]["author"] == new_user["id"], [after["event"], new_user])

    sub = setup["users"]["pgfsub"]
    event, after = submit_and_publish("subscriber's event with a guest address", "pgfsub", "pgfother@example.test")
    if event:
        check("subscriber's event with a guest address: not flagged as a guest submission", not event["guest_flag"], event)
        check("subscriber's event with a guest address: no account is created and the author stays",
              "pgfother@example.test" not in after["users"] and after["event"]["author"] == sub, [after["event"], sorted(after["users"])])


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

    with open(os.path.join(HERE, "fes-guest-mu-plugin.php"), encoding="utf-8") as fh:
        mu_plugin = fh.read()
    config = {
        "mounts": [{"host": os.path.abspath(args.plugin_dir), "vfs": "/wordpress/wp-content/plugins/modern-events-calendar-lite"}],
        "plugins": [bookings.PLUGIN],
        "setup": [{"step": "writeFile", "path": "/wordpress/wp-content/mu-plugins/pgtest-fes-guest.php", "data": mu_plugin}],
    }
    workdir = tempfile.mkdtemp(prefix="playground-fes-guest-")
    logs_dir = os.path.join(workdir, "logs")
    os.makedirs(logs_dir)
    if smoke.port_in_use():
        sys.exit(f"::error::port {smoke.PORT} is already in use")
    proc = smoke.start_server(args, config, os.getcwd(), logs_dir, workdir)
    try:
        run_tests(args.php)
    finally:
        smoke.stop_server(proc)
        bookings.report_debug_log(logs_dir)
        shutil.rmtree(workdir, ignore_errors=True)
    if bookings.failures:
        sys.exit(f"{len(bookings.failures)} FES guest/gallery test failure(s)")
    print("FES guest and gallery test passed.")


if __name__ == "__main__":
    main()
