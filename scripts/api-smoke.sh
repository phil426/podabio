#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost}" # override when running in CI
COOKIE_JAR="${COOKIE_JAR:-.cookies.txt}"

function log() {
  printf "[api-smoke] %s\n" "$1"
}

if [[ -z "${CSRF_TOKEN:-}" ]]; then
  log "CSRF_TOKEN environment variable not set. Export CSRF_TOKEN for authenticated requests."
  exit 1
fi

log "Checking page snapshot..."
curl -fsS -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  "$BASE_URL/api/page.php?action=get_snapshot" >/dev/null

log "Checking token history..."
curl -fsS -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  "$BASE_URL/api/tokens.php?action=history" >/dev/null

log "Checking theme library..."
curl -fsS -b "$COOKIE_JAR" -c "$COOKIE_JAR" \
  "$BASE_URL/api/themes.php?scope=all" >/dev/null

log "Smoke tests complete."
