#!/usr/bin/env bash
# Fabrique le bundle autosuffisant du CDC fermé et l’archive thème historique.
# Usage : bash scripts/package.sh 6.17.14
set -Eeuo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
THEME="$ROOT/theme"
VERSION="${1:-}"
BASE_COMMIT="6153debac8f84da46b1da95af1c810320dc7e5bf"

if [ -z "$VERSION" ]; then
  echo "Usage : bash scripts/package.sh <version>" >&2
  exit 1
fi

printf '── Alignement des versions sur %s\n' "$VERSION"
sed -i -E "s/^[[:space:]]*Version: *[0-9.]+/ Version: $VERSION/" "$THEME/style.css"
sed -i -E "s/(PARTIKULIER_VERSION', *')[0-9.]+/\1$VERSION/" "$THEME/functions.php"
sed -i -E "s/(\"version\": *\")[0-9.]+/\1$VERSION/" "$THEME/package.json"
sed -i -E "s/^Stable tag: *[0-9.]+/Stable tag: $VERSION/" "$THEME/readme.txt"
sed -i -E "s/(\"version\": *\")[0-9.]+/\1$VERSION/" "$ROOT/package.json"

printf '%s\n' '── Contrôle qualité'
bash "$ROOT/scripts/check.sh"

OUT="$ROOT/partikulier-$VERSION.zip"
THEME_OUT="$ROOT/partikulier-$VERSION-theme.zip"
rm -f "$OUT" "$THEME_OUT"
STAGE="$(mktemp -d)"
trap 'rm -rf "$STAGE"' EXIT
mkdir -p "$STAGE/theme" "$STAGE/mu-plugins" "$STAGE/scripts" "$STAGE/tests" "$STAGE/documentation" "$STAGE/.semgrep" "$STAGE/.github/workflows" "$STAGE/vendor-artifacts"

cp -a "$THEME/." "$STAGE/theme/"
cp -a "$ROOT/mu-plugins/." "$STAGE/mu-plugins/"
cp -a "$ROOT/scripts/." "$STAGE/scripts/"
cp -a "$ROOT/vendor-artifacts/." "$STAGE/vendor-artifacts/"
cp -a "$ROOT/tests/routes-contract.json" "$STAGE/tests/routes-contract.json"
cp -a "$ROOT/tests/baselines-$VERSION" "$STAGE/tests/baselines-$VERSION"
cp -a "$ROOT/.semgrep/." "$STAGE/.semgrep/"
cp -a "$ROOT/.github/workflows/." "$STAGE/.github/workflows/"
for root_file in package.json package-lock.json .gitignore; do
  [ -f "$ROOT/$root_file" ] && cp -a "$ROOT/$root_file" "$STAGE/$root_file"
done
if [ -d "$ROOT/assets-demo" ]; then cp -a "$ROOT/assets-demo" "$STAGE/assets-demo"; fi

# Le bundle reçoit uniquement la documentation de candidate et les preuves du CDC;
# les archives historiques restent dans Git mais ne polluent pas le livrable de recette.
for proof in \
  documentation/validation-v$VERSION.md \
  documentation/senior-code-review-v$VERSION.md \
  documentation/release-notes-v$VERSION.md \
  documentation/estatik-artifact-v4.3.4.md \
  documentation/environment-v$VERSION.json \
  documentation/routes-contract-v$VERSION.json \
  documentation/e2e-v$VERSION.json \
  documentation/visual-generate-v$VERSION.json \
  documentation/visual-v$VERSION.json \
  documentation/browser-detection-v$VERSION.json \
  documentation/i18n-fonts-v$VERSION.json \
  documentation/discover-i18n-family-v$VERSION.json \
  documentation/search-sorting-v$VERSION.json \
  documentation/hmac-http-v$VERSION.json \
  documentation/sql-v$VERSION-summary.json \
  documentation/sql-trace-v$VERSION-run1.json \
  documentation/sql-trace-v$VERSION-run2.json \
  documentation/sql-trace-v$VERSION-run3.json \
  documentation/semgrep-v$VERSION.json \
  documentation/semgrep-version-v$VERSION.txt \
  documentation/check-v$VERSION.log \
  documentation/install-tooling.log \
  documentation/install-v$VERSION-final.log; do
  [ -f "$ROOT/$proof" ] || { echo "Preuve requise absente : $proof" >&2; exit 1; }
  mkdir -p "$STAGE/$(dirname "$proof")"
  cp -a "$ROOT/$proof" "$STAGE/$proof"
done

# Les logs de deux builds sont facultatifs au premier passage puis embarqués
# automatiquement dans le bundle final dès qu’ils existent.
for build_log in documentation/package-v$VERSION-first.log documentation/package-v$VERSION-second.log; do
  if [ -f "$ROOT/$build_log" ]; then
    mkdir -p "$STAGE/$(dirname "$build_log")"
    cp -a "$ROOT/$build_log" "$STAGE/$build_log"
  fi
done

cat > "$STAGE/INSTALL.md" <<EOF
# Partikulier $VERSION — bundle CDC fermé

Ce bundle est autosuffisant pour la revue et la recette. Il contient le thème, le mu-plugin, les scripts racine, les contrats, les 30 baselines versionnées, les preuves JSON/logs, le ruleset Semgrep et le workflow CI.

## Provenance

- Candidate : $VERSION
- Branche source : $(git branch --show-current)
- Commit source : $(git rev-parse HEAD)
- BASE_COMMIT : $BASE_COMMIT

## Recette froide

Depuis la racine du bundle :

\`\`\`bash
bash scripts/install-tooling.sh
PK_WP_DIR="\$PWD/.runtime/wp-$VERSION" PK_DB_NAME=partikulier$VERSION PK_DB_USER=partikulier PK_DB_PASS='local-test-only' PK_PORT=8090 bash scripts/install.sh
PK_WP_DIR="\$PWD/.runtime/wp-$VERSION" PK_PORT=8090 bash scripts/start.sh
\`\`\`

Le provisioning attend WordPress 7.1, Estatik 4.3.4, Polylang 3.8.7, Query Monitor 4.0.7 et 30 annonces publiées. L’archive Estatik est embarquée dans \`vendor-artifacts/\` et vérifiée par SHA-256. Les secrets de recette sont éphémères et ne doivent jamais être commités. Le fallback générique exige explicitement \`PK_ALLOW_UNPINNED_ESTATIK=1\` et ne constitue pas une recette reproductible.

## Contrôles livrés

\`\`\`bash
bash scripts/check.sh
PK_BASE=http://localhost:8090 node scripts/routes-contract.mjs
PK_BASE=http://localhost:8090 node scripts/parcours.mjs
PK_BASE=http://localhost:8090 node scripts/visual.mjs
PK_VERSION=$VERSION bash scripts/test-i18n-browser-detection.sh
PK_BASE=http://localhost:8090 PK_VERSION=$VERSION node scripts/test-i18n-fonts.mjs
PK_WP_DIR="\$PWD/.runtime/wp-$VERSION" PK_VERSION=$VERSION php scripts/test-search-sorting.php
bash scripts/test-hmac-http.sh
PK_WP_DIR="\$PWD/.runtime/wp-$VERSION" php scripts/measure-sql-senior.php
bash scripts/run-semgrep-v$VERSION.sh documentation/semgrep-v$VERSION.json
\`\`\`

Pour produire le package depuis le clone complet, exécuter : bash scripts/package.sh VERSION.
EOF

cat > "$STAGE/documentation/candidate-$VERSION.json" <<EOF
{
  "candidate_version": "$VERSION",
  "base_commit": "$BASE_COMMIT",
  "source_branch": "$(git branch --show-current)",
  "source_commit": "$(git rev-parse HEAD)",
  "bundle_scope": "theme + mu-plugins + scripts + tests + documentation proofs + CI + Semgrep",
  "required_proofs": ["routes-contract", "e2e", "visual-30", "browser-detection", "i18n-fonts", "hmac-http", "sql-3-runs", "semgrep"]
}
EOF

# Inventaire et empreintes internes : le manifeste s’auto-exclut pour éviter
# une dépendance circulaire, tandis que l’empreinte du ZIP reste externe.
( cd "$STAGE" && find . -type f -print | sort | sed 's#^./##' > documentation/bundle-inventory-v$VERSION.txt )
( cd "$STAGE" && while IFS= read -r file; do sha256sum "$file"; done < documentation/bundle-inventory-v$VERSION.txt > documentation/bundle-files-v$VERSION.sha256 )

export TZ=UTC
find "$THEME" -type f -not -path '*/.*' -print | sort | xargs touch -t 202608220000
( cd "$THEME" && find . -type f -not -path '*/.*' -print | sort | zip -Xrq "$THEME_OUT" -@ )
find "$STAGE" -type f -print | sort | xargs touch -t 202608220000
( cd "$STAGE" && find . -type f -print | sort | zip -Xrq "$OUT" -@ )

unzip -tq "$OUT" >/dev/null
unzip -tq "$THEME_OUT" >/dev/null
required=(
  INSTALL.md scripts/check.sh scripts/package.sh scripts/install.sh scripts/start.sh scripts/ci-cold-acceptance.sh scripts/routes-contract.mjs scripts/parcours.mjs scripts/visual.mjs
  scripts/test-hmac-http.sh scripts/measure-sql-senior.php scripts/test-search-sorting.php tests/routes-contract.json tests/baselines-$VERSION/SHA256SUMS vendor-artifacts/estatik-4.3.4.zip vendor-artifacts/estatik-4.3.4.zip.sha256
  documentation/candidate-$VERSION.json documentation/routes-contract-v$VERSION.json documentation/e2e-v$VERSION.json
  documentation/visual-v$VERSION.json documentation/search-sorting-v$VERSION.json documentation/hmac-http-v$VERSION.json documentation/sql-v$VERSION-summary.json
  documentation/semgrep-v$VERSION.json documentation/senior-code-review-v$VERSION.md documentation/release-notes-v$VERSION.md documentation/estatik-artifact-v4.3.4.md documentation/bundle-inventory-v$VERSION.txt documentation/bundle-files-v$VERSION.sha256 .semgrep/partikulier.yml .github/workflows/cdc-v$VERSION.yml
)
for entry in "${required[@]}"; do
  unzip -l "$OUT" | grep -E "[[:space:]]${entry//./\\.}$" >/dev/null || { echo "Bundle incomplet : $entry" >&2; exit 1; }
done
baseline_count="$(unzip -l "$OUT" | grep -c "tests/baselines-$VERSION/.*\.png$")"
[ "$baseline_count" -eq 30 ] || { echo "Bundle incomplet : $baseline_count baselines au lieu de 30" >&2; exit 1; }

printf '\nBundle : %s\n' "$OUT"
printf 'Taille  : %s\n' "$(ls -lh "$OUT" | awk '{print $5}')"
printf 'SHA-256 : %s\n' "$(sha256sum "$OUT" | cut -d' ' -f1)"
printf 'Thème   : %s\n' "$THEME_OUT"
printf 'Baselines dans bundle : %s\n' "$baseline_count"
