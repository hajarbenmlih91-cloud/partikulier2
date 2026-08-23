#!/usr/bin/env bash
set -Eeuo pipefail
BASE="${PK_BASE:-http://localhost:8090}"
WP_DIR="${PK_WP_DIR:-}"
VERSION="${PK_VERSION:-1.7.1}"
COMMIT="${PK_COMMIT:-$(git rev-parse HEAD 2>/dev/null || true)}"
RUN_ID="${PK_RUN_ID:-local-$(date -u +%Y%m%dT%H%M%SZ)}"
REPORT="${PK_LOAD_REPORT:-documentation/load-test-${VERSION}.json}"
WARMUP="${PK_WARMUP_REQUESTS:-30}"
MEASURED="${PK_MEASURED_REQUESTS:-100}"
MIN_FIXTURE="${PK_MIN_LISTINGS:-1000}"
CONCURRENCY="${PK_CONCURRENCY:-4}"
STARTED="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
[[ "$COMMIT" =~ ^[0-9a-f]{40}$ ]] || { echo 'source commit absent ou invalide' >&2; exit 2; }
[ -n "$WP_DIR" ] && [ -f "$WP_DIR/wp-load.php" ] || { echo 'PK_WP_DIR absent' >&2; exit 2; }
count=$(wp --path="$WP_DIR" post list --post_type=properties --post_status=publish --format=count --allow-root)
[ "$count" -ge "$MIN_FIXTURE" ] || { echo "NOT_RUN: fixture=$count < $MIN_FIXTURE" >&2; exit 3; }
mkdir -p "$(dirname "$REPORT")"
export VERSION COMMIT RUN_ID WARMUP MEASURED MIN_FIXTURE CONCURRENCY REPORT STARTED
for i in $(seq 1 "$WARMUP"); do curl -fsS -o /dev/null "$BASE/wp-json/partikulier/v1/listings?locale=fr&per_page=24"; done
raw=$(mktemp)
trap 'rm -f "$raw"' EXIT
export BASE
seq 1 "$MEASURED" | xargs -P "$CONCURRENCY" -I{} sh -c 'curl -sS -o /dev/null -w "%{http_code} %{time_total}\n" "$BASE/wp-json/partikulier/v1/listings?locale=fr&per_page=24"' > "$raw"
errors=$(awk '$1 !~ /^2/ {n++} END{print n+0}' "$raw")
values=$(awk '$1 ~ /^2/ {print $2}' "$raw" | sort -n)
received=$(printf '%s\n' "$values" | sed '/^$/d' | wc -l)
[ "$received" -gt 0 ] || { echo 'Aucune réponse HTTP valide' >&2; exit 1; }
pct(){ awk -v p="$1" -v n="$received" 'NR==int((p*n)+0.999999){print; exit}' <<<"$values"; }
p50=$(pct .50); p95=$(pct .95); p99=$(pct .99)
python3 - "$p50" "$p95" "$p99" "$errors" "$MEASURED" <<'PY'
import json,sys
p50,p95,p99,errors,total=map(float,sys.argv[1:])
status='PASS' if p95<=1.5 and p99<=3.0 and errors/total<=0.001 else 'FAIL'
obj={'test_id':'LOAD-HTTP-001','candidate_version':__import__('os').environ['VERSION'],'source_commit':__import__('os').environ['COMMIT'],'source_ref':__import__('os').environ.get('GITHUB_REF','local'),'run_id':__import__('os').environ['RUN_ID'],'started_at_utc':None,'finished_at_utc':None,'command':'scripts/load-test-http.sh','fixture':f"properties>={__import__('os').environ['MIN_FIXTURE']}",'status':status,'exit_code':0 if status=='PASS' else 1,'warmup_requests':int(__import__('os').environ['WARMUP']),'measured_requests':int(total),'concurrency':int(__import__('os').environ['CONCURRENCY']),'metrics':{'p50_seconds':p50,'p95_seconds':p95,'p99_seconds':p99,'errors':int(errors),'error_rate':errors/total},'limitations':[],'started_at_utc':__import__('os').environ['STARTED'],'finished_at_utc':__import__('datetime').datetime.now(__import__('datetime').timezone.utc).isoformat().replace('+00:00','Z')}
print(json.dumps(obj,ensure_ascii=False))
with open(__import__('os').environ['REPORT'],'w') as f: json.dump(obj,f,ensure_ascii=False,indent=2)
if status!='PASS': raise SystemExit(1)
PY
