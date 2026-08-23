#!/usr/bin/env bash
# Acceptance froide bloquante : aucune preuve locale ne peut remplacer ce parcours.
set -Eeuo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VERSION="${PK_VERSION:-6.17.16}"
PORT="${PK_PORT:-8090}"
BASE="${PK_BASE:-http://localhost:${PORT}}"
CI_COMMIT="${GITHUB_SHA:-$(git -C "$ROOT" rev-parse HEAD 2>/dev/null || true)}"
[[ "$CI_COMMIT" =~ ^[0-9a-f]{40}$ ]] || { echo "Commit CI invalide ou absent: $CI_COMMIT" >&2; exit 2; }
RUNTIME="${PK_WP_DIR:-$ROOT/.runtime/ci-wp-${VERSION}}"
DB_NAME="${PK_DB_NAME:-partikulier_ci_${VERSION//./_}}"
DB_USER="${PK_DB_USER:-partikulier_ci}"
DB_PASS="${PK_DB_PASS:-ci-only-password}"
ADMIN_USER="${PK_ADMIN_USER:-ciadmin}"
ADMIN_PASS="${PK_ADMIN_PASS:-ci-only-admin-password}"
ADMIN_EMAIL="${PK_ADMIN_EMAIL:-ci@example.test}"
export PK_VERSION="$VERSION" PK_PORT="$PORT" PK_BASE="$BASE" PK_WP_DIR="$RUNTIME" PK_DB_NAME="$DB_NAME" PK_DB_USER="$DB_USER" PK_DB_PASS="$DB_PASS" PK_ADMIN_USER="$ADMIN_USER" PK_ADMIN_PASS="$ADMIN_PASS" PK_ADMIN_EMAIL="$ADMIN_EMAIL"

mkdir -p "$ROOT/documentation" "$ROOT/.runtime"
rm -rf "$RUNTIME"
case "$DB_NAME" in ''|*[!A-Za-z0-9_]*) echo "DB_NAME invalide pour le reset froid: $DB_NAME" >&2; exit 2;; esac
# La base est dédiée à l’acceptance : aucun schéma, URL ou fixture antérieur
# ne doit pouvoir influencer une preuve présentée comme froide.
sudo mariadb -e "DROP DATABASE IF EXISTS \`${DB_NAME}\`;" >/dev/null 2>&1 || true

bash "$ROOT/scripts/install-tooling.sh"
bash "$ROOT/scripts/install.sh" > "$ROOT/documentation/install-v${VERSION}-final.log"
PK_SERVER_LOG="$ROOT/documentation/server-v${VERSION}.log" bash "$ROOT/scripts/start.sh"

wait_for_http() {
  for _ in $(seq 1 30); do
    if curl --fail --silent --show-error --connect-timeout 2 --max-time 5 "$BASE/fr/" >/dev/null; then return 0; fi
    sleep 1
  done
  echo "WordPress non disponible sur $BASE" >&2
  ss -ltnp || true
  pgrep -af 'php -S' || true
  if [ -f "$PK_WP_DIR/partikulier-server.log" ]; then tail -100 "$PK_WP_DIR/partikulier-server.log" >&2; fi
  exit 1
}
wait_for_http

PK_WP_DIR="$RUNTIME" PK_VERSION="$VERSION" PK_COMMIT="$CI_COMMIT" PK_RUN_ID="${GITHUB_RUN_ID:-local}" php "$ROOT/partikulier-core/tests/core-contract.php" > "$ROOT/documentation/core-contract-v${VERSION}.json"
PK_COMMIT="$CI_COMMIT" node "$ROOT/scripts/routes-contract.mjs" > "$ROOT/documentation/routes-contract-v${VERSION}.json" 2> "$ROOT/documentation/routes-contract-v${VERSION}.summary.log"
PK_COMMIT="$CI_COMMIT" node "$ROOT/scripts/parcours.mjs" > "$ROOT/documentation/e2e-v${VERSION}.json" 2> "$ROOT/documentation/e2e-v${VERSION}.summary.log"
PK_COMMIT="$CI_COMMIT" node "$ROOT/scripts/visual.mjs" > "$ROOT/documentation/visual-v${VERSION}.json" 2> "$ROOT/documentation/visual-v${VERSION}.summary.log"
# Les baselines sont versionnées dans Git : la CI ne les régénère jamais et
# produit seulement une fiche de contrôle non ambiguë.
jq -n --arg version "$VERSION" --arg commit "$CI_COMMIT" --arg manifest "tests/baselines-$VERSION/SHA256SUMS" --argjson count 30 '{version:$version,candidate_version:$version,commit:$commit,mode:"committed-baselines-validation",baseline_count:$count,manifest:$manifest,regenerated:false}' > "$ROOT/documentation/visual-generate-v${VERSION}.json"
printf 'VISUAL_BASELINES_MODE=committed\nVERSION=%s\nCOMMIT=%s\nCOUNT=30\n' "$VERSION" "$CI_COMMIT" > "$ROOT/documentation/visual-generate-v${VERSION}.summary.log"
PK_URL="$BASE" PK_REPORT="$ROOT/documentation/browser-detection-v${VERSION}.json" bash "$ROOT/scripts/test-i18n-browser-detection.sh" > "$ROOT/documentation/browser-detection-v${VERSION}.log"
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
jq -n --arg version "$VERSION" --argjson q1 "$q1" --argjson q2 "$q2" --argjson q3 "$q3" \
  '{version:$version,scope:"mesure du template d’archive sous SAVEQUERIES",runs:[$q1,$q2,$q3],threshold:56,all_below_threshold:([$q1,$q2,$q3]|all(. <= 56)),trace_files:["documentation/sql-trace-v"+$version+"-run1.json","documentation/sql-trace-v"+$version+"-run2.json","documentation/sql-trace-v"+$version+"-run3.json"]}' > "$ROOT/documentation/sql-v${VERSION}-summary.json"

bash "$ROOT/scripts/run-semgrep-v${VERSION}.sh" "$ROOT/documentation/semgrep-v${VERSION}.json" > "$ROOT/documentation/semgrep-v${VERSION}.log"
semgrep --version > "$ROOT/documentation/semgrep-version-v${VERSION}.txt"
bash "$ROOT/scripts/check.sh" > "$ROOT/documentation/check-v${VERSION}.log"
bash "$ROOT/scripts/stamp-provenance.sh" "$VERSION" "$CI_COMMIT" > "$ROOT/documentation/provenance-v${VERSION}.log"

for report in "$ROOT"/documentation/*"v${VERSION}".json; do jq empty "$report"; done
jq -e '.passed == true and (.orders|length) == 3' "$ROOT/documentation/search-sorting-v${VERSION}.json" >/dev/null
jq -e '.failed == 0 and .passed == .total' "$ROOT/documentation/core-contract-v${VERSION}.json" >/dev/null
jq -e '.failed == 0 and .passed == .total' "$ROOT/documentation/routes-contract-v${VERSION}.json" >/dev/null
jq -e '.failed == 0 and .passed == .total' "$ROOT/documentation/e2e-v${VERSION}.json" >/dev/null
jq -e '.failed == 0 and .passed == .total' "$ROOT/documentation/visual-v${VERSION}.json" >/dev/null
jq -e '.negative.invalid_secret == "401" and .negative.invalid_signature == "401" and .negative.expired_timestamp == "401" and .negative.missing_shared_header == "401" and (.rounds_detail|length) == 5 and (.negative.details|length) == 4 and .secret_included == false and (.commit|test("^[0-9a-f]{40}$"))' "$ROOT/documentation/hmac-http-v${VERSION}.json" >/dev/null
jq -e '.all_below_threshold == true and (.commit|test("^[0-9a-f]{40}$"))' "$ROOT/documentation/sql-v${VERSION}-summary.json" >/dev/null
jq -e '.acceptance.targets_scanned == 66 and .acceptance.raw_targets_scanned == 66 and .acceptance.blocking_findings == 0 and .acceptance.errors_count == 0 and (.acceptance.commit|test("^[0-9a-f]{40}$"))' "$ROOT/documentation/semgrep-v${VERSION}.json" >/dev/null
for report in "$ROOT"/documentation/*"v${VERSION}".json; do jq -e '(.commit|type) == "string" and (.commit|test("^[0-9a-f]{40}$"))' "$report" >/dev/null; done
printf 'COLD_ACCEPTANCE=PASS\nVERSION=%s\nBASE=%s\n' "$VERSION" "$BASE"
