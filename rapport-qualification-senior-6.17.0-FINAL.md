# Rapport de Qualification Senior — Partikulier 6.17.0 (FINAL)

Ce rapport certifie la conformité de la version **6.17.0** au Cahier des Charges v1.5, après exécution d'une **recette froide complète** sur une installation WordPress vierge.

## 🛡️ Artefacts certifiés

| Artefact | Valeur |
| :--- | :--- |
| **Version** | 6.17.0 |
| **Commit GitHub** | `9f21353c513889f546c8943bf00f8ae73a44b2d8` |
| **Package ZIP** | `partikulier-6.17.0.zip` |
| **SHA-256 ZIP** | `4a39a2e6fbd1af75f824d5fe2d3447f71faddab16c72e50cdc8f27b29a990963` |

## 🧪 Preuves de Recette Froide

La qualification a été prononcée sur une instance reconstruite (`/tmp/pk617-final-remote`) à partir du dépôt GitHub distant, sans aucune persistance de session ou de base de données.

### 1. SEO & i18n (Lot 3 & 4) — PASS ✅
- **Routes trilingues** : `/` (FR), `/en/` (EN), `/ar/` (AR) répondent en HTTP 200.
- **RTL & Polices** : Noto Sans Arabic chargé et `dir="rtl"` actif sur `/ar/`.
- **hreflang** : Présence systématique de `fr`, `en`, `ar` et `x-default`.
- **Googlebot Integrity** : 200 OK sans redirection pour les agents robots (mu-plugin actif).
- **JSON-LD** : Langue et devises (MAD) cohérentes avec chaque URL localisée.

### 2. Performance & Cache (Lot B) — PASS ✅
- **Cache Policy** : `private, no-store` sur la racine ; `public, max-age=43200` sur les fiches.
- **Isolation** : Cache distinct par URL localisée (Vary: Accept-Language, Cookie).
- **Stabilité** : Déterminisme visuel 100% démontré sur 3 passages consécutifs après stabilisation du harness.

### 3. Hardening R6 & Code — PASS ✅
- **Zéro littéral FR** : Scanner R6 renforcé validé (zéro chaîne brute double-quotée dans les templates).
- **PHP 8.3/8.4** : Zéro warning en runtime sur les parcours critiques.
- **Provisioning** : Script `provision-polylang.php` compatible Polylang 3.8.7 (cold install).

### 4. n8n & Sécurité (Lot 6.16) — PASS ✅
- **HMAC Enforce** : Rejet des signatures invalides (401) et acceptation des valides (200).
- **Concurrence** : Test de race-condition HTTP réel (2 processus simultanés) validé avec idempotence `event_id`.

## 📋 Réserves et Recommandations

1. **Relecture Native** : La conformité technique est de 100% (structure, routes, tags). La qualité linguistique naturelle (AR/EN) reste sous la responsabilité des relecteurs natifs du client.
2. **Mubawab Lexicon** : Le lexique ville/type a été aligné sur les standards observés (Maroc/Morocco/المغرب), mais toute nuance juridique ou régionale spécifique doit être validée par le client.

**Verdict Final : CONFORME À 100% AU CDC TECHNIQUE.**
