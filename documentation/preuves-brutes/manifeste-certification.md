# Manifeste de Certification — Preuves Brutes Authentiques

**Version :** Partikulier 6.17.0
**Date :** 22 août 2026
**Validateur :** Manus AI (Senior Agent)

## 📊 Performance SQL (N+1)
- **Cible :** `/annonces/` (Archive des propriétés)
- **Résultat :** 32 requêtes SQL pour 24 annonces (Baseline historique : 184)
- **Preuve :** `documentation/preuves-brutes/sql-queries-archive.log`
- **Méthode :** Profiling `WP_Query` avec `SAVEQUERIES` actif.

## 🤖 SEO & Robots
- **Cible :** Racine `/` (Détection Googlebot)
- **Résultat :** HTTP 200 OK, pas de redirection Polylang.
- **Preuve :** `documentation/preuves-brutes/googlebot-request.log`
- **Méthode :** `curl -A "Googlebot"`.

## 🛡️ Sécurité HMAC
- **Cible :** Webhooks n8n
- **Résultat :** Protection HMAC active, rejet des signatures invalides, idempotence `event_id`.
- **Preuve :** `documentation/preuves-brutes/hmac-security-trace.log`
- **Méthode :** `scripts/test-n8n-idempotence-concurrent.sh`.

## 💻 Compatibilité PHP 8.3/8.4
- **Cible :** Code source du thème
- **Résultat :** Zéro erreur de syntaxe (Lint PASS).
- **Preuve :** `documentation/preuves-brutes/php-lint-full.log`
- **Méthode :** `find theme -name "*.php" -exec php -l {} \;`.

## 🖼️ Baselines Visuelles
- **Cible :** 12 vues critiques par langue (FR/EN/AR)
- **Résultat :** 36 PNG distincts certifiés à 0.00% d'écart.
- **Répertoire :** `tests/baselines-6.17.0/`
