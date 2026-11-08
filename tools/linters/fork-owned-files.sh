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

# 7.35.1.35 renamed the plugin folder and text domain from the upstream slug
# to this one. A file whose only change since the import is that rename (its
# path, and the old slug replaced in its text) is still vendored code.
old_slug='modern-events-calendar-lite'
new_slug='renginiu-kalendorius'

only_renamed() {
    local old_hash
    # -b keeps CRLF line endings intact under Git for Windows.
    old_hash="$(git cat-file blob "$base:$1" | sed -b "s/$old_slug/$new_slug/g" | git hash-object --no-filters --stdin)"
    [ "$old_hash" = "$(git rev-parse "HEAD:$2")" ]
}

# Rename detection needs both sides of a rename, so git diff runs without the
# pathspec; git ls-files applies it (with full pathspec semantics: ./dir, .,
# globs) to the tracked files, and only those are printed.
declare -A wanted=()
while IFS= read -r -d '' file; do
    wanted["$file"]=1
done < <(git ls-files -z -- "$@")

git -c diff.renameLimit=10000 diff -z --name-status -M --diff-filter=d "$base" HEAD |
while IFS= read -r -d '' status && IFS= read -r -d '' path; do
    case "$status" in
        R*|C*)
            IFS= read -r -d '' new_path
            ;;
    esac
    case "$status" in
        R100)
            continue
            ;;
        R*)
            only_renamed "$path" "$new_path" && continue
            path="$new_path"
            ;;
        C*)
            path="$new_path"
            ;;
        A)
            # Files of a line or two fall below rename detection's
            # similarity threshold; look for them under the old slug.
            old_path="${path//$new_slug/$old_slug}"
            if [ "$old_path" != "$path" ] && git cat-file -e "$base:$old_path" 2>/dev/null                 && only_renamed "$old_path" "$path"; then
                continue
            fi
            ;;
    esac
    [ -n "${wanted[$path]:-}" ] && printf '%s
' "$path"
done | grep -E "$pattern" || true
