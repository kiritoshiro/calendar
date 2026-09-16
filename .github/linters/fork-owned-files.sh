#!/usr/bin/env bash
#
# Prints the files this fork maintains, one per line.
#
# The repository's first commit is the unmodified Modern Events Calendar Lite
# import, so everything touched after it is adventistai.lt's own surface. Deep
# static analysis runs against that surface only: the remaining ~3000 vendored
# files predate these standards and would bury real findings in noise.
#
# Usage: fork-owned-files.sh <extension-regex> [pathspec...]
# Override the import commit with UPSTREAM_IMPORT_REF if history is ever
# rewritten or upstream is re-imported.
set -euo pipefail

pattern="${1:?usage: fork-owned-files.sh <extension-regex> [pathspec...]}"
shift

base="${UPSTREAM_IMPORT_REF:-}"
if [ -z "$base" ]; then
    base="$(git rev-list --max-parents=0 HEAD | tail -n 1)"
fi

if [ -z "$base" ]; then
    echo 'Could not determine the upstream import commit.' >&2
    exit 1
fi

git diff --name-only --diff-filter=d "$base" HEAD -- "$@" | grep -E "$pattern" || true
