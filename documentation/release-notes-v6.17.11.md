# Partikulier v6.17.11 — revue senior de la candidate CDC

## Résumé

v6.17.11 est une nouvelle candidate immuable issue de la revue de code senior de v6.17.10. Elle corrige la dérive des slugs de dépôt, protège les pages privées du cache public, durcit le provisioning, rend les preuves JSON strictes, suit les chaînes de redirection, vérifie réellement les tris et documente les destinations finales des captures protégées.

## Validation

Les contrôles froids rejoués sont disponibles dans le bundle : routes **16/16**, E2E **16/16**, visuel **30/30**, navigateur/cookie/robots **10/10**, police/RTL **3/3**, HMAC **5 rondes avec 4 rejets 401**, SQL **49/45/45 sous le seuil 56**, tri **3 ordres sur 24 lignes**, et Semgrep **0 finding bloquant**.

## Réserves explicites

Le workflow GitHub contrôle la syntaxe, le lockfile, les baselines, Semgrep, la structure JSON et le package, mais ne rejoue pas encore toute la recette WordPress/MariaDB/HTTP/HMAC/SQL. La source Estatik 4.3.4 reste l’URL générique WordPress.org avec vérification de version post-installation, car l’URL versionnée testée est indisponible. Ces limites sont détaillées dans `documentation/senior-code-review-v6.17.11.md` et empêchent un sign-off CDC fermé inconditionnel.

## Provenance

La base de comparaison est `v6.17.7@6153debac8f84da46b1da95af1c810320dc7e5bf`. Le parent immédiat est `v6.17.10@85d59798b0ea0c9bf93f32ec7d02c883f3831493`. Le tag v6.17.7 et la release v6.17.10 ne sont pas réécrits.
