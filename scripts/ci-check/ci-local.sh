#!/usr/bin/env bash
# =============================================================================
# ci-local.sh — exécute LOCALEMENT les étapes des jobs `static-contracts` et
# `deterministic-package` du workflow .github/workflows/cdc-v1.7.1-candidate.yml,
# puis écrit un manifeste d'équivalence lisible par une machine.
#
# Pourquo : le dépôt ne déclenche que des `workflow_dispatch` (380/380 runs mesurés),
# et la branche de travail n'est pas dans `on.push`/`on.pull_request`. Ce script ferme
# le trou de preuve SANS toucher aux workflows et SANS droit d'écriture GitHub.
#
# Usage :
#   bash scripts-arena/ci-local.sh                 # jobs statiques + packaging
#   JOBS="static" bash scripts-arena/ci-local.sh   # statiques seuls
#   PK_REPO=/chemin/vers/repo bash scripts-arena/ci-local.sh
#
# Sortie : documentation... enfin: $OUT_DIR/ci-local-manifest.json (+ stdout lisible)
# Code 0 = toutes les étapes exécutables sont vertes.
# =============================================================================
set -uo pipefail
export TMPDIR=${TMPDIR:-/home/user/.tooling}
REPO=${PK_REPO:-/opt/pk/repo}
VERSION=${PK_VERSION:-6.17.22}
JOBS=${JOBS:-static,package}
OUT_DIR=${PK_CI_OUT:-$REPO/documentation}
RUNNER_TEMP=${RUNNER_TEMP:-$(mktemp -d)}
SEMGREP_VERSION=${SEMGREP_VERSION:-1.132.0}
mkdir -p "$RUNNER_TEMP" "$OUT_DIR"
MAN="$OUT_DIR/ci-local-manifest.json"
LOGD="$OUT_DIR/ci-local-logs"; mkdir -p "$LOGD"
[ -d "$REPO/.git" ] || { echo "FATAL: $REPO n'est pas un dépôt git" >&2; exit 2; }
SHA=$(git -C "$REPO" rev-parse HEAD)
RUN_ID="local-$(date +%Y%m%dT%H%M%SZ)"

steps=(); results=()
step(){ # $1 nom  $2 commande  [$3 requis=yes/no]
  local name="$1" cmd="$2" req="${3:-yes}" t0 t1 dur st log
  log="$LOGD/$(printf '%s' "$name" | tr -cs 'a-zA-Z0-9.-' '_').log"
  printf '\n>>> [%s] %s\n' "$JOBS" "$name"; t0=$(date +%s%N)
  if bash -c "cd $REPO && $cmd" >"$log" 2>&1; then st=PASS; else st=FAIL; fi
  t1=$(date +%s%N); dur=$(( (t1-t0)/1000000 ))
  printf '    %s (%s ms)  log=%s\n' "$st" "$dur" "${log##*/}"
  [ "$st" = FAIL ] && tail -6 "$log" | sed 's/^/      | /'
  results+=("{\"step\":$(printf '%s' "$name" | python3 -c 'import json,sys;print(json.dumps(sys.stdin.read()))'),\"status\":\"$st\",\"duration_ms\":$dur,\"required\":\"$req\",\"log\":\"${log##*/}\"}")
  [ "$st" = FAIL ] && [ "$req" = yes ] && return 1
  return 0
}
fail=0

echo "=== environnement ==="
printf '  dépôt   : %s\n  SHA     : %s\n  version : %s\n  runner  : %s\n  PHP     : %s\n  node    : %s\n' \
  "$REPO" "$SHA" "$VERSION" "$(uname -srm)" "$(php -v|head -1|awk '{print $2}')" "$(node -v)"
echo

# ---------------- JOB 1 : static-contracts ----------------
if [[ "$JOBS" == *static* ]]; then
  command -v composer >/dev/null || true
  step "Setup/Install locked Node dependencies" "npm ci --ignore-scripts --no-audit --no-fund" no || fail=1
  step "Syntax gates (bash -n / php -l / node --check)" "
    find scripts -maxdepth 1 -type f -name '*.sh' -print0 | xargs -0 -n1 bash -n
    find theme mu-plugins partikulier-core scripts -type f -name '*.php' -print0 | xargs -0 -n1 php -l >/dev/null
    find scripts -maxdepth 1 -type f -name '*.mjs' -print0 | xargs -0 -r node --check
  " || fail=1
  step "Validate CDC + visual scenario contract" "
    python3 scripts/validate-cdc-v1.7.1.py
    python3 scripts/validate-visual-contract-v1.7.1.py
    python3 -m json.tool documentation/visual-scenarios-v1.7.1.json >/dev/null
    python3 -m json.tool documentation/schemas/visual-scenarios.schema.json >/dev/null
  " || fail=1
  step "Validate measurable objectives contract" "python3 scripts/validate-objectives-v1.0.py documentation/objectives-contract-v1.0.json" || fail=1
  step "Verify pinned dependencies and SBOM" "
    bash scripts/verify-dependencies.sh documentation/dependency-manifest-v1.7.1.json
    test -s documentation/sbom-v1.7.1.json
    test -s documentation/sbom-v1.7.1.sha256
    sha256sum -c documentation/sbom-v1.7.1.sha256 >/dev/null
    npm sbom --sbom-format=cyclonedx --omit=dev > '$RUNNER_TEMP/npm-sbom.json'
    jq -e '.bomFormat == \"CycloneDX\" and (.components|length)>0' '$RUNNER_TEMP/npm-sbom.json' >/dev/null
  " || fail=1
  if command -v semgrep >/dev/null || [ -x "$HOME/.local/bin/semgrep" ]; then
    export PATH="$HOME/.local/bin:$PATH"
    step "Semgrep pinned $(printf %s "$SEMGREP_VERSION") scan exact scope" "
      semgrep --version >/dev/null
      PK_COMMIT='$SHA' bash scripts/run-semgrep-v1.7.1.sh '$RUNNER_TEMP/semgrep-v$VERSION.json'
      jq -e '.acceptance.targets_scanned == 66 and .acceptance.raw_targets_scanned == 66 and .acceptance.blocking_findings == 0 and .acceptance.errors_count == 0 and (.acceptance.commit|test(\"^[0-9a-f]{40}\$\"))' '$RUNNER_TEMP/semgrep-v$VERSION.json' >/dev/null
    " || fail=1
  else
    step "Semgrep (ABSENT de cette machine)" "false" no
  fi
  step "Secret scan" "bash scripts/scan-secrets.sh '$OUT_DIR/secrets-scan-v1.7.1.json'" || fail=1
  step "Qualification report" "python3 scripts/qualification-report-v1.7.1.py" || fail=1
fi

# ---------------- JOB 2 : deterministic-package (partie constructible sans CI) ----------------
if [[ "$JOBS" == *package* ]]; then
  step "Build deterministic package" "
    SOURCE_DATE_EPOCH=1767225600 PK_COMMIT='$SHA' PK_RUN_ID='$RUN_ID' bash scripts/package-deterministic-v1.7.1.sh '$VERSION'
    sha256sum -c 'partikulier-$VERSION-deterministic.zip.sha256'
    unzip -tq 'partikulier-$VERSION-deterministic.zip'
    python3 scripts/validate-visual-contract-v1.7.1.py --require-baselines
  " || fail=1
  step "Rebuild from detached copy and compare bytes (PACKAGE-REPRO-001)" "
    tmp='$RUNNER_TEMP/detached'; rm -rf \"\$tmp\"; mkdir -p \"\$tmp\"
    git -C $REPO archive '$SHA' | tar x -C \"\$tmp\"
    ( cd \"\$tmp\" && SOURCE_DATE_EPOCH=1767225600 PK_COMMIT='$SHA' PK_RUN_ID='$RUN_ID' bash scripts/package-deterministic-v1.7.1.sh '$VERSION' )
    cmp --silent '$REPO/partikulier-$VERSION-deterministic.zip' \"\$tmp/partikulier-$VERSION-deterministic.zip\"
    P=\$(sha256sum '$REPO/partikulier-$VERSION-deterministic.zip' | cut -d' ' -f1)
    echo \"package_sha256=\$P\"
    jq -n --arg commit '$SHA' --arg package \"\$P\" --arg version '$VERSION' --arg run '$RUN_ID' \
      '{test_id:\"PACKAGE-REPRO-001\",candidate_version:\$version,source_commit:\$commit,run_id:\$run,status:\"PASS\",branch_sha256:\$package,detached_sha256:\$package,comparison:\"cmp byte-identical\",runner:\"local (no GitHub runner)\"}' > '$OUT_DIR/reproducibility-v1.7.1.json'
  " || fail=1
  step "Verify candidate artifact independently" "
    mkdir -p '$RUNNER_TEMP/verify'
    cp '$REPO/partikulier-$VERSION-deterministic.zip'* '$RUNNER_TEMP/verify/' 2>/dev/null || true
    cp '$REPO/partikulier-$VERSION-evidence.tar.gz'* '$RUNNER_TEMP/verify/' 2>/dev/null || true
    cp '$OUT_DIR/reproducibility-v1.7.1.json' '$RUNNER_TEMP/verify/' 2>/dev/null || true
    python3 scripts/verify-candidate-artifact-v1.7.1.py '$RUNNER_TEMP/verify' --commit '$SHA' --run-id '$RUN_ID' --output '$OUT_DIR/candidate-artifact-verification-v1.7.1.json'
  " || fail=1
  PKG_SHA=$(cd "$REPO" && sha256sum partikulier-$VERSION-deterministic.zip 2>/dev/null | cut -d' ' -f1)
  PKG_BYTES=$(cd "$REPO" && stat -c%s partikuler-$VERSION-deterministic.zip 2>/dev/null || stat -c%s partikulier-$VERSION-deterministic.zip 2>/dev/null)
  [ -n "${PKG_SHA:-}" ] && printf '\n  paquet : partikulier-%s-deterministic.zip\n  octets : %s\n  sha256 : %s\n' "$VERSION" "${PKG_BYTES:-?}" "$PKG_SHA"
fi

# ---------------- manifeste ----------------
python3 - "$SHA" "$VERSION" "$RUN_ID" "$fail" "$OUT_DIR" "$(printf '%s,' "${results[@]}" | sed 's/,$//')" <<'PY'
import json,sys,datetime
sha,ver,run,fail,out,items=sys.argv[1:7]
res=json.loads('['+items+']')
man={"test_id":"CI-LOCAL-EQUIVALENCE-001","candidate_version":ver,"source_commit":sha,"run_id":run,
     "generated_at":datetime.datetime.now(datetime.timezone.utc).strftime('%Y-%m-%dT%H:%M:%SZ'),
     "workflow_ref":"github.com/hajarbenmlih91-cloud/partikulier2/.github/workflows/cdc-v1.7.1-candidate.yml",
     "jobs_emulated":["static-contracts","deterministic-package (partie sans artifact cold-acceptance)"],
     "not_emulated":["cold-acceptance (15 min de charge + 30 captures navigateur, exige le mode reference)",
                     "release (exige un tag approuve)"],
     "steps":res,"status":"PASS" if fail=="0" else "FAIL",
     "note":"Preuve locale, rejouable par n'importe qui avec le meme script. Ne remplace pas un run GitHub: il prouve que le SHA se valide hors GitHub."}
p=out+"/ci-local-manifest.json"; json.dump(man,open(p,'w'),indent=2,ensure_ascii=False)
ok=sum(1 for r in res if r['status']=='PASS'); ko=[r['step'] for r in res if r['status']=='FAIL' and r['required']=='yes']
print(f"\n=== MANIFESTE : {p}\n    etapes PASS = {ok} | FAIL requis = {len(ko)} | statut global = {man['status']}")
for k in ko: print('    ECHEC REQUIS:',k)
PY
exit $fail
