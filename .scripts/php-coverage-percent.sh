#!/usr/bin/env sh
set -eu

RAW_FILE="${1:-coverage-php.txt}"

if [ ! -f "$RAW_FILE" ]; then
  echo "ERROR: coverage output file not found: $RAW_FILE" >&2
  exit 1
fi

# PHPUnit --coverage-text with colors=true prefixes lines with ANSI escapes, so "^[[:space:]]*Lines:"
# never matches the global "Summary" line. Strip SGR sequences, then take the summary line:
# "  Lines:   94.14% (...)" — class rows also contain "Lines:" but include "Methods:" on the same line.
VALUE="$(
  sed 's/\x1b\[[0-9;]*m//g' "$RAW_FILE" | awk '
    /^[[:space:]]*Lines:[[:space:]]+/ && !/Methods:/ {
      gsub(/%/, "", $2)
      print $2
      exit
    }
  '
)"

if [ -z "${VALUE:-}" ]; then
  echo "ERROR: Could not extract PHP Lines coverage percentage from ${RAW_FILE}" >&2
  exit 1
fi

echo "Global PHP coverage (Lines): ${VALUE}%"
