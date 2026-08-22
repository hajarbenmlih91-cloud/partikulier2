#!/bin/bash
# Fabrique l'archive livrable du thème.
#   bash scripts/package.sh 6.14.0
#
# Aligne les 4 fichiers de version, contrôle la qualité, puis produit le zip.
set -u
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
T="$ROOT/theme"
V="${1:-}"

if [ -z "$V" ]; then
  echo "Usage : bash scripts/package.sh <version>   (ex. 6.14.0)"
  exit 1
fi

echo "── Alignement des versions sur $V"
sed -i -E "s/^[[:space:]]*Version: *[0-9.]+/ Version: $V/"                 "$T/style.css"
sed -i -E "s/(PARTIKULIER_VERSION', *')[0-9.]+/\1$V/"                  "$T/functions.php"
sed -i -E "s/(\"version\": *\")[0-9.]+/\1$V/"                          "$T/package.json"
sed -i -E "s/^Stable tag: *[0-9.]+/Stable tag: $V/"                    "$T/readme.txt"

echo "── Contrôle qualité"
bash "$ROOT/scripts/check.sh" || { echo "Contrôle échoué : archive non produite."; exit 1; }

OUT="$ROOT/partikulier-$V.zip"
THEME_OUT="$ROOT/partikulier-$V-theme.zip"
rm -f "$OUT" "$THEME_OUT"
# Bundle de livraison : le mu-plugin est obligatoire car Polylang redirige
# pendant plugins_loaded, avant le chargement du thème.
STAGE="$(mktemp -d)"
mkdir -p "$STAGE/theme" "$STAGE/mu-plugins"
cp -a "$T/." "$STAGE/theme/"
if [ -d "$ROOT/mu-plugins" ]; then cp -a "$ROOT/mu-plugins/." "$STAGE/mu-plugins/"; fi

# Fichier INSTALL.md statique pour le déterminisme
cat > "$STAGE/INSTALL.md" <<'EOF'
# Installation Partikulier

Décompresser `theme/` dans `wp-content/themes/partikulier/` et `mu-plugins/` dans `wp-content/mu-plugins/`. Le mu-plugin est obligatoire : il protège la racine contre la redirection navigateur des robots avant le chargement du thème.
EOF

# Archive thème historique
( cd "$T" && zip -rq "$THEME_OUT" . -x '.git/*' 'node_modules/*' '.DS_Store' )

# Bundle de livraison complet
( cd "$STAGE" && zip -rq "$OUT" . -x '.git/*' 'node_modules/*' '.DS_Store' )
rm -rf "$STAGE"

unzip -t "$OUT" >/dev/null 2>&1 || { echo "Archive corrompue."; exit 1; }
unzip -t "$THEME_OUT" >/dev/null 2>&1 || { echo "Archive thème corrompue."; exit 1; }

if ! unzip -l "$OUT" | grep -q 'mu-plugins/partikulier-early-seo.php'; then
  echo "Archive bundle incomplète : mu-plugin SEO absent."; exit 1;
fi

echo
echo "Bundle : $OUT"
echo "Taille  : $(ls -lh "$OUT" | awk '{print $5}')"
echo "SHA-256 : $(sha256sum "$OUT" | cut -d' ' -f1)"
echo "Thème  : $THEME_OUT"
echo
echo "Pensez à ajouter l'entrée de changelog dans theme/readme.txt."
