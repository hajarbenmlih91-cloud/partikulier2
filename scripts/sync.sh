#!/bin/bash
# Recopie theme/ vers le WordPress de test.
#
# À LANCER APRÈS CHAQUE MODIFICATION DU CODE.
# Oublier cette étape, c'est tester l'ancienne version et conclure à tort
# que le correctif ne fonctionne pas. C'est le piège n°1 du projet.
set -u
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP_DIR="${PK_WP_DIR:-$ROOT/wp}"
DEST="$WP_DIR/wp-content/themes/partikulier"

[ -d "$ROOT/theme" ] || { echo "theme/ introuvable."; exit 1; }
[ -d "$WP_DIR" ]     || { echo "WordPress absent. Lancez : bash scripts/install.sh"; exit 1; }

rsync -a --delete \
  --exclude '.git' --exclude 'node_modules' --exclude '.DS_Store' \
  "$ROOT/theme/" "$DEST/"

# Le cache de pages servirait sinon l'ancien HTML.
rm -rf "$WP_DIR/wp-content/uploads/partikulier-cache/"* 2>/dev/null

echo "Thème synchronisé et cache purgé."
