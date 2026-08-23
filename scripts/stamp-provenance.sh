#!/usr/bin/env bash
# Estampille les preuves JSON produites pendant une acceptance avec le commit réel.
set -Eeuo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VERSION="${1:?Usage: stamp-provenance.sh <version> [commit]}"
COMMIT="${2:-${GITHUB_SHA:-$(git -C "$ROOT" rev-parse HEAD 2>/dev/null || true)}}"

[[ "$COMMIT" =~ ^[0-9a-f]{40}$ ]] || { echo "Commit CI invalide ou absent: $COMMIT" >&2; exit 2; }
shopt -s nullglob
reports=("$ROOT"/documentation/*"v${VERSION}"*.json)
[ "${#reports[@]}" -gt 0 ] || { echo "Aucune preuve JSON v$VERSION" >&2; exit 2; }
for report in "${reports[@]}"; do
  tmp="${report}.tmp"
  jq --arg version "$VERSION" --arg commit "$COMMIT" \
    '.candidate_version = $version | .commit = $commit' "$report" > "$tmp"
  mv "$tmp" "$report"
done
if grep -RIn --include="*v${VERSION}*.json" -E 'uncommitted|placeholder' "$ROOT/documentation"; then
  echo "Placeholder de provenance détecté" >&2
  exit 1
fi
printf 'PROVENANCE_STAMP=PASS\nVERSION=%s\nCOMMIT=%s\nREPORTS=%s\n' "$VERSION" "$COMMIT" "${#reports[@]}"
