#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
WP_THEME="wp/wp-content/themes/partikulier"
OLD_DIR="/tmp/partikulier-theme-6.13.1"
PROFILE="/tmp/partikulier-senior-profile.jsonl"
URLS=("/annonces/" "/annonce/casablanca/appartement-lumineux-3-pieces/" "/deposer-une-annonce/" "/favoris/")
rm -rf "$OLD_DIR"
mkdir -p "$OLD_DIR" "wp/wp-content/mu-plugins"
git archive 0413e11 theme | tar -x -C "$OLD_DIR"
cp scripts/senior-http-profiler.php wp/wp-content/mu-plugins/senior-http-profiler.php
pkill -f 'php -S 0.0.0.0:8090' 2>/dev/null || true
bash scripts/start.sh >/tmp/partikulier-sql-server.log 2>&1 &
sleep 3
run_version() {
  local version="$1"
  rm -f "$PROFILE"
  for round in 1 2 3; do
    for url in "${URLS[@]}"; do
      curl -fsS -o /dev/null "http://127.0.0.1:8090${url}"
    done
  done
  cp "$PROFILE" "rapport-sql-${version}-raw.jsonl"
}
cp -a "$OLD_DIR/theme/." "$WP_THEME/"
run_version "6.13.1"
cp -a theme/. "$WP_THEME/"
run_version "6.14.1"
rm -f wp/wp-content/mu-plugins/senior-http-profiler.php
printf 'Mesure terminee: 2 versions x 3 tours x 4 URLs\n'
