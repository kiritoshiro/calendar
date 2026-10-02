#!/usr/bin/env python3
"""Compare WordPress Plugin Check results with the committed legacy baseline.

The imported Modern Events Calendar tree carries thousands of findings that
predate this fork. Rather than ignoring the whole scan, the job fails only when
a file gains findings for a check code beyond its baseline count, so new
problems block while the legacy backlog is reduced over time.

Usage:
    plugin-check-baseline.py RESULTS BASELINE           compare (exit 1 on new findings)
    plugin-check-baseline.py RESULTS BASELINE --write   rewrite the baseline from RESULTS

RESULTS is the `wp plugin check --format=json` output written by
WordPress/plugin-check-action: "FILE: <path>" lines, each followed by a JSON
array of findings for that file.
"""

import json
import os
import re
import sys
from collections import Counter

FILE_LINE = re.compile(r'^FILE: (.+?)\s*$')


def parse(path):
    """Returns {file: {"TYPE code": count}}."""
    results = {}
    current = None
    with open(path, encoding='utf-8', errors='replace') as handle:
        for raw in handle:
            line = raw.strip()
            match = FILE_LINE.match(line)
            if match:
                current = match.group(1)
                continue
            if current and line.startswith('['):
                findings = json.loads(line)
                counts = Counter(f"{item['type']} {item['code']}" for item in findings)
                results[current] = dict(sorted(counts.items()))
                current = None
    return results


def total(data):
    return sum(sum(codes.values()) for codes in data.values())


def summary(lines):
    target = os.environ.get('GITHUB_STEP_SUMMARY')
    if target:
        with open(target, 'a', encoding='utf-8') as handle:
            handle.write('\n'.join(lines) + '\n')
    print('\n'.join(lines))


def main(argv):
    if len(argv) < 3:
        print(__doc__)
        return 2

    results_path, baseline_path = argv[1], argv[2]
    if not os.path.isfile(results_path):
        print(f'::error::Plugin Check produced no results file at {results_path}.')
        return 1

    current = parse(results_path)

    if '--write' in argv[3:]:
        with open(baseline_path, 'w', encoding='utf-8', newline='\n') as handle:
            json.dump(dict(sorted(current.items())), handle, indent=1, sort_keys=True)
            handle.write('\n')
        print(f'Wrote {total(current)} findings in {len(current)} files to {baseline_path}.')
        return 0

    with open(baseline_path, encoding='utf-8') as handle:
        baseline = json.load(handle)

    if not current and total(baseline):
        print('::error::No findings could be read from the Plugin Check results; the output format may have changed.')
        return 1

    new = []
    resolved = 0
    for file, codes in sorted(current.items()):
        known = baseline.get(file, {})
        for code, count in codes.items():
            allowed = known.get(code, 0)
            if count > allowed:
                new.append((file, code, count - allowed))
    for file, codes in baseline.items():
        for code, count in codes.items():
            resolved += max(0, count - current.get(file, {}).get(code, 0))

    lines = [
        '### WordPress Plugin Check (legacy baseline)',
        f'- Findings now: {total(current)} in {len(current)} files; baseline: {total(baseline)}.',
        f'- Resolved since the baseline: {resolved}.',
    ]
    if new:
        lines.append(f'- **New findings: {sum(n for _, _, n in new)}** (fix them, or justify and refresh the baseline):')
        lines += [f'  - `{file}`: {code} (+{n})' for file, code, n in new]
        summary(lines)
        for file, code, n in new:
            print(f'::error file=modern-events-calendar-lite/{file}::{n} new Plugin Check finding(s): {code}')
        return 1

    lines.append('- No new findings.')
    if resolved:
        lines.append('- Refresh the baseline with `--write` to lock in the improvement.')
    summary(lines)
    return 0


if __name__ == '__main__':
    sys.exit(main(sys.argv))
