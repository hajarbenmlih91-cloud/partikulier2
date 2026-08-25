#!/usr/bin/env bash
# Acceptance froide bloquante : aucune preuve locale ne peut remplacer ce parcours.
set -Eeuo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
# Toute sortie non attendue doit identifier la ligne et la commande fautive dans le log brut.
cold_acceptance_err() {
  local rc=$?
  printf 'COLD_ACCEPTANCE_ERROR status=%s line=%s command=%q\n' "$rc" "${BASH_LINENO[0]:-unknown}" "$BASH_COMMAND" >&2
  exit "$rc"
}
trap cold_acceptance_err ERR
VERSION="${PK_VERSION:-6.17.17}"
PORT="${PK_PORT:-8090}"
BASE="${PK_BASE:-http://localhost:${PORT}}"
CI_COMMIT="${PK_COMMIT:-${GITHUB_SHA:-$(git -C "$ROOT" rev-parse HEAD 2>/dev/null || true)}}"
[[ "$CI_COMMIT" =~ ^[0-9a-f]{40}$ ]] || { echo "Commit CI invalide ou absent: $CI_COMMIT" >&2; exit 2; }
RUNTIME="${PK_WP_DIR:-/tmp/partikulier-ci-wp-${VERSION}-${GITHUB_RUN_ID:-local}}"
DB_NAME="${PK_DB_NAME:-partikulier_ci_${VERSION//./_}}"
DB_USER="${PK_DB_USER:-partikulier_ci}"
DB_PASS="${PK_DB_PASS:-ci-only-password}"
ADMIN_USER="${PK_ADMIN_USER:-ciadmin}"
ADMIN_PASS="${PK_ADMIN_PASS:-ci-only-admin-password}"
ADMIN_EMAIL="${PK_ADMIN_EMAIL:-ci@example.test}"
REFERENCE_RUN_DIR="${PK_REFERENCE_RUN_DIR:-$RUNTIME/reference-$PORT}"
export PK_VERSION="$VERSION" PK_PORT="$PORT" PK_BASE="$BASE" PK_WP_DIR="$RUNTIME" PK_DB_NAME="$DB_NAME" PK_DB_USER="$DB_USER" PK_DB_PASS="$DB_PASS" PK_ADMIN_USER="$ADMIN_USER" PK_ADMIN_PASS="$ADMIN_PASS" PK_ADMIN_EMAIL="$ADMIN_EMAIL" PK_REFERENCE_RUN_DIR="$REFERENCE_RUN_DIR"

mkdir -p "$ROOT/documentation" "$ROOT/.runtime"
rm -rf "$RUNTIME"
case "$DB_NAME" in ''|*[!A-Za-z0-9_]*) echo "DB_NAME invalide pour le reset froid: $DB_NAME" >&2; exit 2;; esac
# La base est dédiée à l’acceptance : aucun schéma, URL ou fixture antérieur
# ne doit pouvoir influencer une preuve présentée comme froide.
sudo mariadb -e "DROP DATABASE IF EXISTS \`${DB_NAME}\`;" >/dev/null 2>&1 || true

bash "$ROOT/scripts/install-tooling.sh"
bash "$ROOT/scripts/install.sh" > "$ROOT/documentation/install-v${VERSION}-final.log"
if [ "${PK_SERVER_MODE:-dev}" = "reference" ]; then
  PK_SERVER_LOG="$ROOT/documentation/server-v${VERSION}.log" PK_PHP_WORKERS="${PK_PHP_WORKERS:-4}" bash "$ROOT/scripts/start-reference-web.sh"
else
  PK_SERVER_LOG="$ROOT/documentation/server-v${VERSION}.log" bash "$ROOT/scripts/start.sh"
fi

wait_for_http() {
  local response_file="$RUNTIME/health-response.html"
  local status="000"
  for _ in $(seq 1 30); do
    status="$(curl --silent --show-error --connect-timeout 2 --max-time 5 -o "$response_file" -w '%{http_code}' "$BASE/fr/" || true)"
    if [[ "$status" =~ ^2[0-9][0-9]$ ]]; then
      printf 'REFERENCE_HEALTH_HTTP=%s\n' "$status"
      return 0
    fi
    sleep 1
  done
  printf 'WordPress non disponible sur %s (last_http_status=%s)\n' "$BASE" "$status" >&2
  if [ -f "$response_file" ]; then tail -c 2000 "$response_file" >&2 || true; fi
  ss -ltnp || true
  pgrep -af 'php -S|php-fpm|nginx' || true
  if [ -f "$PK_WP_DIR/partikulier-server.log" ]; then tail -100 "$PK_WP_DIR/partikulier-server.log" >&2; fi
  exit 1
}
wait_for_http
# Le provisioning Estatik intervient après l’activation du core : synchroniser une
# fois explicitement avant les assertions, jamais depuis chaque requête HTTP.
wp --path="$RUNTIME" eval '$repository = new \Partikulier\Core\ListingRepository(); echo "SYNCED=" . $repository->syncEstatikProperties() . PHP_EOL;' --allow-root > "$ROOT/documentation/estatik-sync-v${VERSION}.log"

PK_WP_DIR="$RUNTIME" PK_VERSION="$VERSION" PK_COMMIT="$CI_COMMIT" PK_RUN_ID="${GITHUB_RUN_ID:-local}" php "$ROOT/partikulier-core/tests/core-contract.php" > "$ROOT/documentation/core-contract-v${VERSION}.json"
PK_WP_DIR="$RUNTIME" PK_VERSION="$VERSION" PK_COMMIT="$CI_COMMIT" PK_RUN_ID="${GITHUB_RUN_ID:-local}" php "$ROOT/partikulier-core/tests/services-contract.php" > "$ROOT/documentation/core-services-contract-v${VERSION}.json"
PK_WP_DIR="$RUNTIME" PK_VERSION="$VERSION" PK_COMMIT="$CI_COMMIT" PK_RUN_ID="${GITHUB_RUN_ID:-local}" php "$ROOT/scripts/theme-contract.php" > "$ROOT/documentation/theme-contract-v${VERSION}.json"
PK_VERSION="$VERSION" PK_COMMIT="$CI_COMMIT" node "$ROOT/scripts/routes-contract.mjs" > "$ROOT/documentation/routes-contract-v${VERSION}.json" 2> "$ROOT/documentation/routes-contract-v${VERSION}.summary.log"
PK_VERSION="$VERSION" PK_COMMIT="$CI_COMMIT" node "$ROOT/scripts/parcours.mjs" > "$ROOT/documentation/e2e-v${VERSION}.json" 2> "$ROOT/documentation/e2e-v${VERSION}.summary.log"
# Exécuter les contrôles UI v1.8 avant le comparateur historique pour conserver
# les preuves DOM/crawl/responsive même si le gate pixel immuable échoue.
ui_exit=0
rm -rf "$ROOT/documentation/ui-v1.8"
for browser in chromium firefox webkit; do
  ui_out="$ROOT/documentation/ui-v1.8"
  if [ "$browser" != chromium ]; then ui_out="$ROOT/documentation/ui-v1.8/$browser"; fi
  mkdir -p "$ui_out"
  PK_BROWSER="$browser" PK_BASE="$BASE" PK_VERSION="$VERSION" PK_COMMIT="$CI_COMMIT" PK_RUN_ID="${GITHUB_RUN_ID:-local}" PK_UI_OUT="$ui_out" node "$ROOT/scripts/test-ui-v1.8.mjs" > "$ROOT/documentation/ui-v1.8-${browser}.log" 2>&1 || ui_exit=1
done
visual_exit=0
PK_VERSION="$VERSION" PK_COMMIT="$CI_COMMIT" PK_RUN_ID="${GITHUB_RUN_ID:-local}" node "$ROOT/scripts/visual-contract-v1.7.1.mjs" > "$ROOT/documentation/visual-contract-v${VERSION}.json" 2> "$ROOT/documentation/visual-contract-v${VERSION}.summary.log" || visual_exit=$?
# Les baselines sont versionnées dans Git : la CI ne les régénère jamais et
# produit seulement une fiche de contrôle non ambiguë.
jq -n --arg version "$VERSION" --arg commit "$CI_COMMIT" --arg manifest "tests/baselines-$VERSION/SHA256SUMS" --argjson count 30 '{version:$version,candidate_version:$version,commit:$commit,mode:"committed-baselines-validation",baseline_count:$count,manifest:$manifest,regenerated:false}' > "$ROOT/documentation/visual-generate-v${VERSION}.json"
printf 'VISUAL_BASELINES_MODE=committed\nVERSION=%s\nCOMMIT=%s\nCOUNT=30\n' "$VERSION" "$CI_COMMIT" > "$ROOT/documentation/visual-generate-v${VERSION}.summary.log"
PK_URL="$BASE" PK_REPORT="$ROOT/documentation/browser-detection-v${VERSION}.json" bash "$ROOT/scripts/test-i18n-browser-detection.sh" > "$ROOT/documentation/browser-detection-v${VERSION}.log"
PK_BASE="$BASE" PK_VERSION="$VERSION" PK_COMMIT="$CI_COMMIT" PK_RUN_ID="${GITHUB_RUN_ID:-local}" PK_A11Y_REPORT="$ROOT/documentation/accessibility-v${VERSION}.json" node "$ROOT/scripts/test-accessibility.mjs" > "$ROOT/documentation/accessibility-v${VERSION}.log"
PK_BASE="$BASE" PK_REPORT="$ROOT/documentation/i18n-fonts-v${VERSION}.json" node "$ROOT/scripts/test-i18n-fonts.mjs" > "$ROOT/documentation/i18n-fonts-v${VERSION}.log"
PK_SORT_REPORT="$ROOT/documentation/search-sorting-v${VERSION}.json" php "$ROOT/scripts/test-search-sorting.php" > "$ROOT/documentation/search-sorting-v${VERSION}.log"

SECRET_B64="$(openssl rand -base64 32)"
PARTIKULIER_N8N_SECRET="$SECRET_B64" PK_HMAC_LOG="$ROOT/documentation/hmac-http-v${VERSION}.json" bash "$ROOT/scripts/test-hmac-http.sh" > "$ROOT/documentation/hmac-http-v${VERSION}.log"
for run in 1 2 3; do
  PK_SQL_REPORT="$ROOT/documentation/sql-trace-v${VERSION}-run${run}.json" php "$ROOT/scripts/measure-sql-senior.php" > "$ROOT/documentation/sql-v${VERSION}-run${run}.log"
done
q1="$(jq -er '.queries_total' "$ROOT/documentation/sql-trace-v${VERSION}-run1.json")"
q2="$(jq -er '.queries_total' "$ROOT/documentation/sql-trace-v${VERSION}-run2.json")"
q3="$(jq -er '.queries_total' "$ROOT/documentation/sql-trace-v${VERSION}-run3.json")"
jq -n --arg version "$VERSION" --arg commit "$CI_COMMIT" --arg source_ref "${GITHUB_REF:-local}" --arg run_id "${GITHUB_RUN_ID:-local}" --argjson q1 "$q1" --argjson q2 "$q2" --argjson q3 "$q3" \
  '{version:$version,scope:"mesure du template d’archive sous SAVEQUERIES",runs:[$q1,$q2,$q3],threshold:56,all_below_threshold:([$q1,$q2,$q3]|all(. <= 56)),commit:$commit,source_ref:$source_ref,run_id:$run_id,trace_files:["documentation/sql-trace-v"+$version+"-run1.json","documentation/sql-trace-v"+$version+"-run2.json","documentation/sql-trace-v"+$version+"-run3.json"]}' > "$ROOT/documentation/sql-v${VERSION}-summary.json"

bash "$ROOT/scripts/run-semgrep-v1.7.1.sh" "$ROOT/documentation/semgrep-v${VERSION}.json" > "$ROOT/documentation/semgrep-v${VERSION}.log"
semgrep --version > "$ROOT/documentation/semgrep-version-v${VERSION}.txt"
bash "$ROOT/scripts/check.sh" > "$ROOT/documentation/check-v${VERSION}.log"
bash "$ROOT/scripts/stamp-provenance.sh" "$VERSION" "$CI_COMMIT" > "$ROOT/documentation/provenance-v${VERSION}.log"

PK_WP_DIR="$RUNTIME" PK_MIN_LISTINGS=1000 PK_VERSION="$VERSION" PK_COMMIT="$CI_COMMIT" PK_RUN_ID="${GITHUB_RUN_ID:-local}" php "$ROOT/scripts/provision-load-fixture.php" > "$ROOT/documentation/load-fixture-v${VERSION}.json"
PK_WP_DIR="$RUNTIME" PK_BASE="$BASE" PK_VERSION="$VERSION" PK_COMMIT="$CI_COMMIT" PK_RUN_ID="${GITHUB_RUN_ID:-local}" PK_LOAD_REPORT="$ROOT/documentation/load-test-v${VERSION}.json" bash "$ROOT/scripts/load-test-http.sh" > "$ROOT/documentation/load-test-v${VERSION}.log"
capacity_exit=0
capacity_cgroup_path=""
if [ -f "$REFERENCE_RUN_DIR/cgroup.path" ]; then capacity_cgroup_path="$(cat "$REFERENCE_RUN_DIR/cgroup.path")"; fi
PK_WP_DIR="$RUNTIME" PK_BASE="$BASE" PK_VERSION="$VERSION" PK_COMMIT="$CI_COMMIT" PK_RUN_ID="${GITHUB_RUN_ID:-local}" PK_CAPACITY_CGROUP_PATH="$capacity_cgroup_path" PK_CAPACITY_CGROUP_REQUIRED="1" PK_CAPACITY_REPORT="$ROOT/documentation/capacity-envelope-v${VERSION}.json" python3 "$ROOT/scripts/test-capacity-envelope-v1.7.1.py" > "$ROOT/documentation/capacity-envelope-v${VERSION}.log" || capacity_exit=$?
upgrade_exit=0
PK_WP_DIR="$RUNTIME" PK_DB_NAME="$DB_NAME" PK_DB_USER="$DB_USER" PK_DB_PASS="$DB_PASS" PK_VERSION="$VERSION" PK_COMMIT="$CI_COMMIT" PK_RUN_ID="${GITHUB_RUN_ID:-local}" PK_UPGRADE_REPORT="$ROOT/documentation/upgrade-v6.17.16-to-v${VERSION}.json" bash "$ROOT/scripts/test-upgrade-v1.7.1.sh" > "$ROOT/documentation/upgrade-v6.17.16-to-v${VERSION}.log" || upgrade_exit=$?
# These reports are created after the first stamp; stamp them again so every
# candidate JSON carries the same exact commit field before gate evaluation.
bash "$ROOT/scripts/stamp-provenance.sh" "$VERSION" "$CI_COMMIT" > "$ROOT/documentation/provenance-v${VERSION}-final.log"

for report in "$ROOT"/documentation/*"v${VERSION}".json; do jq empty "$report"; done
jq -e '.passed == true and (.orders|length) == 3' "$ROOT/documentation/search-sorting-v${VERSION}.json" >/dev/null
jq -e '.failed == 0 and .passed == .total' "$ROOT/documentation/core-contract-v${VERSION}.json" >/dev/null
jq -e '.failed == 0 and .passed == .total' "$ROOT/documentation/core-services-contract-v${VERSION}.json" >/dev/null
jq -e '.failed == 0 and .passed == .total' "$ROOT/documentation/theme-contract-v${VERSION}.json" >/dev/null
jq -e '.failed == 0 and .passed == .total' "$ROOT/documentation/routes-contract-v${VERSION}.json" >/dev/null
jq -e '.failed == 0 and .passed == .total' "$ROOT/documentation/e2e-v${VERSION}.json" >/dev/null
jq -e '.failed == 0 and .passed == .total and .total == 30' "$ROOT/documentation/visual-contract-v${VERSION}.json" >/dev/null || visual_exit=1
jq -e '.failed == 0 and .passed == .total' "$ROOT/documentation/accessibility-v${VERSION}.json" >/dev/null
jq -e '.negative.invalid_secret == "401" and .negative.invalid_signature == "401" and .negative.expired_timestamp == "401" and .negative.missing_shared_header == "401" and (.rounds_detail|length) == 5 and (.negative.details|length) == 4 and .secret_included == false and (.commit|test("^[0-9a-f]{40}$"))' "$ROOT/documentation/hmac-http-v${VERSION}.json" >/dev/null
jq -e '.all_below_threshold == true and (.commit|test("^[0-9a-f]{40}$"))' "$ROOT/documentation/sql-v${VERSION}-summary.json" >/dev/null
jq -e '.acceptance.targets_scanned == 66 and .acceptance.raw_targets_scanned == 66 and .acceptance.blocking_findings == 0 and .acceptance.errors_count == 0 and (.acceptance.commit|test("^[0-9a-f]{40}$"))' "$ROOT/documentation/semgrep-v${VERSION}.json" >/dev/null
jq -e '.status == "PASS" and .after >= 1000' "$ROOT/documentation/load-fixture-v${VERSION}.json" >/dev/null
jq -e '.status == "PASS" and .metrics.errors == 0 and .metrics.p95_seconds <= 1.5 and .metrics.p99_seconds <= 3.0' "$ROOT/documentation/load-test-v${VERSION}.json" >/dev/null
jq -e '.failed == 0 and .passed == .scenario_count' "$ROOT/documentation/ui-v1.8/ui-summary.json" >/dev/null || ui_exit=1
jq -e '.image_dom_passed == .image_dom_total and .image_dom_total == .scenario_count' "$ROOT/documentation/ui-v1.8/ui-summary.json" >/dev/null || ui_exit=1
jq -e '.link_crawl_passed == .link_crawl_total and .link_crawl_total == .scenario_count' "$ROOT/documentation/ui-v1.8/ui-summary.json" >/dev/null || ui_exit=1
jq -e '.responsive_passed == .responsive_total and .responsive_total > 0 and .browser == "chromium"' "$ROOT/documentation/ui-v1.8/ui-summary.json" >/dev/null || ui_exit=1
for browser in firefox webkit; do
  report="$ROOT/documentation/ui-v1.8/$browser/ui-summary.json"
  jq -e --arg browser "$browser" '.failed == 0 and .passed == .scenario_count and .image_dom_passed == .image_dom_total and .link_crawl_passed == .link_crawl_total and .responsive_passed == .responsive_total and .browser == $browser' "$report" >/dev/null || ui_exit=1
done
jq -e '.status == "PASS" and .scale == 1 and ([.phases[] | select(.name == "sustained_read_10rps") | .target_rps == 10 and .duration_seconds == 900] | any) and ([.phases[] | select(.name == "burst_read_25rps") | .target_rps == 25 and .duration_seconds == 60] | any) and ([.phases[] | select(.name == "write_api_2rps") | .target_rps == 2 and .duration_seconds == 900] | any) and ([.phases[] | select(.name == "concurrent_sessions_50") | .target_concurrency == 50 and .status == "PASS"] | any)' "$ROOT/documentation/capacity-envelope-v${VERSION}.json" >/dev/null
jq -e '.status == "PASS" and .from_tag == "v6.17.16" and .to_version == "6.17.17" and ([.checks[] | select(.test_id == "UPGRADE-DATA-001" or .test_id == "UPGRADE-SETTINGS-001" or .test_id == "UPGRADE-IDEMPOTENT-001") | .status == "PASS"] | all)' "$ROOT/documentation/upgrade-v6.17.16-to-v${VERSION}.json" >/dev/null
if [ "$visual_exit" -ne 0 ] || [ "$capacity_exit" -ne 0 ] || [ "$upgrade_exit" -ne 0 ] || [ "$ui_exit" -ne 0 ]; then
  printf 'VISUAL_EXIT=%s\nCAPACITY_EXIT=%s\nUPGRADE_EXIT=%s\nUI_V18_EXIT=%s\n' "$visual_exit" "$capacity_exit" "$upgrade_exit" "$ui_exit" >&2
  exit 1
fi
for report in "$ROOT"/documentation/*"v${VERSION}".json; do jq -e '(.commit|type) == "string" and (.commit|test("^[0-9a-f]{40}$"))' "$report" >/dev/null; done
printf 'COLD_ACCEPTANCE=PASS\nVERSION=%s\nBASE=%s\n' "$VERSION" "$BASE"
