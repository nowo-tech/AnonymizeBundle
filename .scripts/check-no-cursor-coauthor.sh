#!/usr/bin/env sh
# Fail if git history contains Cursor agent Co-authored-by trailers (REQ-GIT-001).
set -eu

REF="${1:-HEAD}"

if ! git rev-parse --verify "${REF}" >/dev/null 2>&1; then
  echo "ERROR: git ref not found: ${REF}" >&2
  exit 1
fi

MATCHES="$(
  git log "${REF}" --format=%B \
    | grep -E '^Co-authored-by: Cursor <cursoragent@cursor.com>$|^Co-authored-by:.*cursoragent@cursor\.com' \
    || true
)"

if [ -n "${MATCHES}" ]; then
  echo "ERROR: Cursor co-author trailers found in git history (ref: ${REF})" >&2
  echo "Run: git log ${REF} --format=%B | grep -i co-authored-by" >&2
  echo "${MATCHES}" | head -5 >&2
  exit 1
fi

echo "OK: no Cursor co-author trailers in git history (ref: ${REF})"
