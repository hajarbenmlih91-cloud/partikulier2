#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT="${1:-$ROOT/documentation/semgrep-v6.17.12.json}"
RAW="$(mktemp)"
trap 'rm -f "$RAW"' EXIT
cd "$ROOT"
semgrep --config .semgrep/partikulier.yml --json --output "$RAW" theme
mapfile -t rules < <(grep -E '^  - id:' .semgrep/partikulier.yml | sed -E 's/^  - id: *//')
mapfile -t targets < <(find theme -type f -name '*.php' -print | sort)
bytes=0
for file in "${targets[@]}"; do size=$(wc -c < "$file"); bytes=$((bytes + size)); done
rules_json=$(printf '%s\n' "${rules[@]}" | jq -Rsc 'split("\n") | map(select(length > 0))')
paths_json=$(printf '%s\n' "${targets[@]}" | jq -Rsc 'split("\n") | map(select(length > 0))')
cat "$RAW" | jq --arg commit "$(git rev-parse HEAD 2>/dev/null || echo uncommitted)" --arg command 'semgrep --config .semgrep/partikulier.yml --json --output documentation/semgrep-v6.17.12.json theme' --argjson rules "$rules_json" --argjson targets "$paths_json" --argjson bytes "$bytes" \
  '. + {acceptance:{commit:$commit,command:$command,rules_used:$rules,targets_scanned:($targets|length),target_files:$targets,bytes_analyzed:$bytes,blocking_findings:([.results[] | select((.extra.severity // "ERROR") == "ERROR")] | length),errors_count:(.errors|length)}}' > "$OUT"
cat "$OUT"
