#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
MANIFEST="${1:-$ROOT/documentation/dependency-manifest-v1.7.1.json}"
[ -f "$MANIFEST" ] || { echo "Manifest absent: $MANIFEST" >&2; exit 2; }
count=$(jq '.dependencies|length' "$MANIFEST")
[ "$count" -gt 0 ] || { echo 'Aucune dépendance dans le manifest' >&2; exit 1; }
while IFS=$'\t' read -r archive sha; do
  [ -n "$archive" ] || continue
  path="$ROOT/$archive"
  [ -f "$path" ] || { echo "Archive absente: $archive" >&2; exit 1; }
  actual=$(sha256sum "$path" | awk '{print $1}')
  [ "$actual" = "$sha" ] || { echo "SHA mismatch: $archive" >&2; exit 1; }
  unzip -tq "$path" >/dev/null
  printf 'OK %s %s\n' "$archive" "$actual"
done < <(jq -r '.dependencies[] | [.archive,.sha256] | @tsv' "$MANIFEST")
printf 'DEPENDENCY_VERIFY=PASS\nCOUNT=%s\n' "$count"
