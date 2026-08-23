#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VERSION="${1:?Usage: package-deterministic-v1.7.1.sh <product-version>}"
COMMIT="${PK_COMMIT:-$(git -C "$ROOT" rev-parse HEAD 2>/dev/null || true)}"
SOURCE_DATE_EPOCH="${SOURCE_DATE_EPOCH:-1767225600}"
OUT="$ROOT/partikulier-$VERSION-deterministic.zip"
THEME_OUT="$ROOT/partikulier-$VERSION-theme.zip"
EVIDENCE_OUT="$ROOT/partikulier-$VERSION-evidence.tar.gz"
[[ "$COMMIT" =~ ^[0-9a-f]{40}$ ]] || { echo 'PK_COMMIT invalide' >&2; exit 2; }
STAGE=$(mktemp -d)
EVIDENCE=$(mktemp -d)
trap 'rm -rf "$STAGE" "$EVIDENCE"' EXIT
mkdir -p "$STAGE" "$EVIDENCE" "$STAGE/documentation" "$STAGE/documentation/schemas" "$STAGE/tests" "$STAGE/.github"
cp -a "$ROOT/theme" "$STAGE/theme"
cp -a "$ROOT/partikulier-core" "$STAGE/partikulier-core"
cp -a "$ROOT/mu-plugins" "$STAGE/mu-plugins"
cp -a "$ROOT/scripts" "$STAGE/scripts"
cp -a "$ROOT/vendor-artifacts" "$STAGE/vendor-artifacts"
cp -a "$ROOT/.semgrep" "$STAGE/.semgrep"
cp -a "$ROOT/.github/workflows" "$STAGE/.github/workflows"
cp -a "$ROOT/tests/routes-contract.json" "$STAGE/tests/routes-contract.json"
if [ -d "$ROOT/tests/baselines-$VERSION" ]; then cp -a "$ROOT/tests/baselines-$VERSION" "$STAGE/tests/baselines-$VERSION"; fi
for file in package.json package-lock.json; do [ -f "$ROOT/$file" ] && cp -a "$ROOT/$file" "$STAGE/$file"; done
[ -d "$ROOT/assets-demo" ] && cp -a "$ROOT/assets-demo" "$STAGE/assets-demo"
for file in \
  'Cahierdeschargescontractuel—PartikulierUltra-Premiumv1.7.md' \
  'Cahierdeschargescontractuel—PartikulierUltra-Premiumv1.7.1.md' \
  scope-matrix.csv capacity-envelope.json compatibility-matrix.json data-contract.json technical-design.md implementation-deviations.md operations-runbook-v1.7.1.md dependency-manifest-v1.7.1.json sbom-v1.7.1.json sbom-v1.7.1.sha256; do
  [ -f "$ROOT/documentation/$file" ] || { echo "Documentation stable absente: $file" >&2; exit 1; }
  mkdir -p "$STAGE/documentation"
  cp -a "$ROOT/documentation/$file" "$STAGE/documentation/$file"
done
for schema in "$ROOT"/documentation/schemas/*.json; do mkdir -p "$STAGE/documentation/schemas"; cp -a "$schema" "$STAGE/documentation/schemas/"; done
cat > "$STAGE/INSTALL.md" <<EOF
# Partikulier $VERSION — produit déterministe CDC v1.7.1

Ce ZIP contient le produit, le core M0, les contrats, les scripts, les baselines approuvées et les dépendances vendor checksumées. Les preuves CI volatiles sont publiées dans le sidecar evidence associé et ne contribuent pas aux octets de ce ZIP.

## Identité déterministe

- candidate_version: $VERSION
- source_commit: $COMMIT
- source_ref_policy: commit-only
- source_date_epoch: $SOURCE_DATE_EPOCH
- run_id/source_ref/timestamps: sidecar uniquement

Le même commit et les mêmes entrées doivent produire les mêmes octets, indépendamment de la branche ou du checkout détaché.
EOF
cat > "$STAGE/documentation/candidate-$VERSION.json" <<EOF
{
  "candidate_version": "$VERSION",
  "source_commit": "$COMMIT",
  "source_ref_policy": "commit-only",
  "source_date_epoch": $SOURCE_DATE_EPOCH,
  "volatile_evidence": "sidecar",
  "deterministic_inputs": ["theme", "partikulier-core", "mu-plugins", "scripts", "vendor-artifacts", "tests", "documentation-static", ".semgrep", ".github/workflows"]
}
EOF
# Normalize all product filesystem metadata before inventory and ZIP creation.
find "$STAGE" -type f -exec touch -d "@${SOURCE_DATE_EPOCH}" {} +
( cd "$STAGE" && find . -type f -print | sort | sed 's#^./##' > documentation/bundle-inventory-$VERSION.txt )
( cd "$STAGE" && while IFS= read -r file; do sha256sum "$file"; done < documentation/bundle-inventory-$VERSION.txt > documentation/bundle-files-$VERSION.sha256 )
find "$STAGE" -type f -exec touch -d "@${SOURCE_DATE_EPOCH}" {} +
rm -f "$OUT" "$THEME_OUT" "$EVIDENCE_OUT"
( cd "$STAGE/theme" && find . -type f -print | sort | zip -Xrq "$THEME_OUT" -@ )
( cd "$STAGE" && find . -type f -print | sort | zip -Xrq "$OUT" -@ )
unzip -tq "$OUT" >/dev/null
unzip -tq "$THEME_OUT" >/dev/null
sha256sum "$OUT" > "$OUT.sha256"
if [ -n "${PK_EVIDENCE_DIR:-}" ] && [ -d "$PK_EVIDENCE_DIR" ]; then cp -a "$PK_EVIDENCE_DIR/." "$EVIDENCE/"; fi
printf '{"format":"partikulier-evidence-sidecar-v1","candidate_version":"%s","source_commit":"%s","package_sha256":"%s","source_ref":"%s","run_id":"%s"}\n' "$VERSION" "$COMMIT" "$(cut -d' ' -f1 "$OUT.sha256")" "${GITHUB_REF:-local}" "${GITHUB_RUN_ID:-local}" > "$EVIDENCE/attestation.json"
find "$EVIDENCE" -type f -exec touch -d "@${SOURCE_DATE_EPOCH}" {} +
tar --sort=name --mtime="@${SOURCE_DATE_EPOCH}" --owner=0 --group=0 --numeric-owner -czf "$EVIDENCE_OUT" -C "$EVIDENCE" .
sha256sum "$EVIDENCE_OUT" > "$EVIDENCE_OUT.sha256"
for required in INSTALL.md partikulier-core/partikulier-core.php partikulier-core/migrations/001_initial.sql scripts/install.sh scripts/backup.sh scripts/restore.sh scripts/rollback.sh documentation/candidate-$VERSION.json documentation/scope-matrix.csv documentation/capacity-envelope.json documentation/data-contract.json documentation/dependency-manifest-v1.7.1.json documentation/sbom-v1.7.1.json; do unzip -l "$OUT" | grep -E "[[:space:]]${required//./\\.}$" >/dev/null || { echo "Bundle incomplet: $required" >&2; exit 1; }; done
printf 'PRODUCT=%s\nPRODUCT_SHA256=%s\nEVIDENCE=%s\nEVIDENCE_SHA256=%s\nSOURCE_COMMIT=%s\n' "$OUT" "$(cut -d' ' -f1 "$OUT.sha256")" "$EVIDENCE_OUT" "$(cut -d' ' -f1 "$EVIDENCE_OUT.sha256")" "$COMMIT"
