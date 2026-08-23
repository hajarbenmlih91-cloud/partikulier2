#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT="${1:-$ROOT/documentation/semgrep-v6.17.16.json}"
COMMIT="${PK_COMMIT:-${GITHUB_SHA:-$(git -C "$ROOT" rev-parse HEAD 2>/dev/null || true)}}"
[[ "$COMMIT" =~ ^[0-9a-f]{40}$ ]] || { echo "Commit Semgrep invalide ou absent: $COMMIT" >&2; exit 2; }
RAW="$(mktemp)"
trap 'rm -f "$RAW"' EXIT
cd "$ROOT"
mapfile -t PHP_TARGETS < <(find theme -type f -name '*.php' -print | sort)
[ "${#PHP_TARGETS[@]}" -eq 66 ] || { echo "Périmètre PHP inattendu: ${#PHP_TARGETS[@]} (attendu 66)" >&2; exit 2; }
semgrep --config .semgrep/partikulier.yml --json --output "$RAW" "${PHP_TARGETS[@]}"
mapfile -t rules < <(grep -E '^  - id:' .semgrep/partikulier.yml | sed -E 's/^  - id: *//')
mapfile -t targets < <(jq -r '.paths.scanned[]?' "$RAW")
bytes=0
for file in "${targets[@]}"; do size=$(wc -c < "$file"); bytes=$((bytes + size)); done
rules_json=$(printf '%s\n' "${rules[@]}" | jq -Rsc 'split("\n") | map(select(length > 0))')
paths_json=$(printf '%s\n' "${targets[@]}" | jq -Rsc 'split("\n") | map(select(length > 0))')
cat "$RAW" | jq --arg commit "$COMMIT" --arg command 'semgrep --config .semgrep/partikulier.yml --json --output documentation/semgrep-v6.17.16.json <66 explicit theme/*.php targets>' --argjson rules "$rules_json" --argjson targets "$paths_json" --argjson bytes "$bytes" \
  '. + {acceptance:{commit:$commit,command:$command,rules_used:$rules,targets_scanned:($targets|length),target_files:$targets,bytes_analyzed:$bytes,blocking_findings:([.results[] | select((.extra.severity // "ERROR") == "ERROR")] | length),errors_count:(.errors|length),raw_targets_scanned:([.paths.scanned[]?]|length)}}' > "$OUT"
cat "$OUT"
