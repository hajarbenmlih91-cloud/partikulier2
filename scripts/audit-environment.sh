#!/bin/bash
# Script d'audit de l'environnement de validation Partikulier 6.17.0
# Ce script vérifie la présence et les versions de tous les outils nécessaires.

echo "--- 🛠️ Audit Environnement Partikulier 6.17.0 ---"
date

echo -e "\n[1] Système & Langages"
php -v | head -n 1
node -v | sed 's/^/Node.js: /'
pnpm -v | sed 's/^/pnpm: /'
git --version
curl --version | head -n 1
jq --version

echo -e "\n[2] Extensions PHP Critiques"
php -m | grep -E "mbstring|gd|mysqli|curl|xml|zip|intl|bcmath" | sort | sed 's/^/- /'

echo -e "\n[3] Outils WordPress & CLI"
wp --version
if command -v wp &> /dev/null; then
    echo "- WP-CLI est installé et fonctionnel"
else
    echo "❌ ERREUR: WP-CLI est manquant"
fi

echo -e "\n[4] Dépendances Node.js (E2E & Visual)"
if [ -f "/home/ubuntu/partikulier2/package.json" ]; then
    echo "- package.json détecté"
    grep -E "playwright|pixelmatch|canvas" /home/ubuntu/partikulier2/package.json | sed 's/^[[:space:]]*/  /'
else
    echo "⚠️ package.json absent dans le dossier courant"
fi

echo -e "\n[5] Inventaire des Scripts de Recette"
SCRIPTS_DIR="/home/ubuntu/partikulier2/scripts"
if [ -d "$SCRIPTS_DIR" ]; then
    echo "- Dossier scripts/ détecté"
    echo "- Scanner R6: $([ -f "$SCRIPTS_DIR/check-i18n-hardcoded.php" ] && echo "✅" || echo "❌")"
    echo "- Qualité Gate (check.sh): $([ -f "$SCRIPTS_DIR/check.sh" ] && echo "✅" || echo "❌")"
    echo "- Recette SEO: $([ -f "$SCRIPTS_DIR/test-i18n-seo.sh" ] && echo "✅" || echo "❌")"
    echo "- Tests n8n: $([ -f "$SCRIPTS_DIR/lib-n8n-test.php" ] && echo "✅" || echo "❌")"
    echo "- Visual Regression: $([ -f "$SCRIPTS_DIR/visual.mjs" ] && echo "✅" || echo "❌")"
else
    echo "❌ ERREUR: Dossier scripts/ introuvable"
fi

echo -e "\n[6] Artefacts de Livraison"
ZIP_PATH="/home/ubuntu/partikulier2/partikulier-6.17.0.zip"
REPORT_PATH="/home/ubuntu/partikulier2/rapport-qualification-senior-6.17.0-FINAL.md"

if [ -f "$ZIP_PATH" ]; then
    echo "- ZIP: $(basename "$ZIP_PATH") ✅"
    sha256sum "$ZIP_PATH"
else
    echo "❌ ZIP manquant"
fi

if [ -f "$REPORT_PATH" ]; then
    echo "- Rapport: $(basename "$REPORT_PATH") ✅"
    grep "Commit GitHub" "$REPORT_PATH"
else
    echo "❌ Rapport manquant"
fi

echo -e "\n--- Fin de l'Audit ---"
