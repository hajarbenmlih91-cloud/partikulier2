# Qualification Partikulier 6.16.0 — n8n / WhatsApp

**Base :** Partikulier 6.15.0  
**CDC appliqué :** `cahier-des-charges-partikulier-6.16-n8n-mobile(4).md` — v2.2 finale gelée  
**Date de recette :** 2026-08-21  
**Environnement :** WordPress 7.1, PHP 8.4, Estatik 4.3.4, Polylang 3.8.7, WP-CLI

## Résumé exécutif

Le périmètre technique I-1 à I-4 du CDC 6.16 a été implémenté dans le thème. Les sept scripts de preuve ont été exécutés dans une sandbox WordPress reconstruite depuis `scripts/install.sh`, avec Estatik 4.3.4 et Polylang 3.8.7 installés et activés. Les tests hérités H/K et Polylang ont été rejoués après l’intégration.

> **Qualification : implémentation 6.16.0 validée sur sandbox pour les parcours automatisés couverts par les scripts ; sign-off de production encore conditionné à la recette J0 avec le workflow n8n réel, à l’inventaire des secrets de production et à la fenêtre `log → enforce` prévue par le CDC.**

## Corrections livrées

| Exigence | Implémentation |
|---|---|
| I-1 | Module `class-n8n-security.php`, option `pk_n8n_settings` non autoloadée, migration idempotente avec états `pending`, `verified`, `completed`, priorité à `PARTIKULIER_N8N_SECRET`, fallback legacy temporaire. |
| I-2 | HMAC SHA-256 sur `METHOD + route + timestamp + corps brut`, fenêtre anti-rejeu de 300 secondes, `key_id`, modes `off/log/enforce`, double clé pendant rotation et wrapper `register_route()`. |
| I-3 | Préfixes `n8n-` et `pay-`, insertion SQL directe sur index UNIQUE `event_id`, collision transformée en `200 duplicate:true`, sans double traitement. |
| I-4 | Quota configurable de 1 à 10 via `quota_per_day`, conservé dans la transaction avec verrou `FOR UPDATE`, texte de consentement et URL de canal HTTPS. |
| Documentation | Changelog 6.16.0 ajouté ; script de packaging corrigé pour les en-têtes WordPress indentés. |

## Résultats dynamiques

| Preuve | Résultat | Rapport |
|---|---:|---|
| Migration I-1 | PASS | `test-n8n-settings-migration.json` |
| Modes HMAC | PASS | `test-n8n-hmac-modes.json` |
| Anti-rejeu / rejeu `event_id` | PASS | `test-n8n-replay.json` |
| Rotation double clé | PASS | `test-n8n-secret-rotation.json` |
| Idempotence duplicate-key | PASS | `test-n8n-idempotence-race.json` |
| Garde-fou des routes | PASS — 8 routes protégées | `test-n8n-route-guard.json` |
| Canari secret / absence de fuite | PASS | `test-n8n-canary.json` |

Tous les rapports ont `runtime_messages: []`. Le premier passage a détecté un accès direct à la clé absente `hmac_mode` pendant la migration legacy ; le code a été corrigé avec une valeur par défaut explicite, puis toute la batterie a été rejouée avec succès.

## Non-régression 6.15

Le Lot H passe. Le Lot K passe avec rendu `WP_List_Table`, export CSV hostile neutralisé, zéro warning PHP 8.4 et budget SQL de 7 requêtes à une ligne puis 6 requêtes à 20 et 100 lignes. La synchronisation Polylang E2E passe avec remplacement manuel EN, mise en brouillon de l’ancienne auto-traduction et conservation de la traduction manuelle.

## Packaging

Le contrôle qualité global passe sur **66 fichiers PHP** et **2 fichiers JavaScript**. Les quatre versions sont alignées sur `6.16.0` dans `style.css`, `functions.php`, `package.json` et `readme.txt`. L’archive est valide selon `unzip -t`.

```text
Archive : partikulier-6.16.0.zip
Taille  : 543K
SHA-256: 24a2cc3428ce6fa80ce907f5bbd183f382b4347186edacb0a6a876398759ac4f
```

## Limites honnêtes du sign-off

Les preuves exécutées appellent les callbacks WordPress directement dans WP-CLI afin de rendre les scénarios déterministes. Elles démontrent la logique applicative, mais ne remplacent pas le test J0 avec le workflow n8n réel, le proxy HTTPS réel et les secrets de production. Avant activation de `enforce`, il faut donc injecter les secrets via l’environnement, exécuter les vecteurs HMAC documentés, vérifier les réponses HTTP réelles et observer la période `log → enforce`. Aucun secret de production n’a été utilisé dans la sandbox.

## Fichiers de preuve

Les rapports JSON bruts sont conservés dans ce dossier, avec le hash indépendant `package-6.16.0.sha256`. Les scripts de recette sont versionnés dans `scripts/test-n8n-*.php` et utilisent `scripts/lib-n8n-test.php`.
