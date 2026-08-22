# Rapport de Qualification Senior — Partikulier 6.17.0 (FINAL)

Ce rapport certifie la conformité de la version **6.17.0** au Cahier des Charges v1.5, après exécution d'une **recette froide complète** sur une installation WordPress vierge.

## 🛡️ Artefacts certifiés

| Artefact | Valeur |
| :--- | :--- |
| **Version** | 6.17.0 |
| **Commit GitHub** | tag `v6.17.0` |
| **Package ZIP** | `partikulier-6.17.0.zip` |
| **SHA-256 ZIP** | `8dd3f1489215821c77c8332934364e981b6559878e8a41dab6c76e68e01071a5` |
| **Attestation AR** | `documentation/attestations/relecture-arabe.md` |
| **Attestation EN** | `documentation/attestations/relecture-anglais.md` |
| **Baselines Visuelles** | `tests/baselines-6.17.0/` (Trilingues Certifiées) |
| **Preuves Brutes** | `documentation/preuves-brutes/` (Traces détaillées SQL/SEO/Security) |

## 🧪 Preuves de Recette Froide

La qualification a été prononcée sur une instance reconstruite à partir du dépôt GitHub distant, en utilisant le script `scripts/install.sh` corrigé pour un provisioning 100% autonome.

### 0. Déterminisme & Audit — PASS ✅
- **Packaging Déterministe** : Le script `scripts/package.sh` a été normalisé (timestamps fixes, ordre trié). Le SHA-256 est désormais reproductible.
- **Audit Portable** : Le script `scripts/audit-environment.sh` utilise des chemins relatifs et certifie l'intégrité de l'environnement sur n'importe quel clone.
- **Tests E2E (Playwright)** : Le harnais `scripts/parcours.mjs` a été aligné sur les routes réelles (`/annonces/`) et les paramètres de recherche (`s`, `es_type`). Tous les parcours (Dépôt, Recherche, Fiche, Favoris) sont **PASS**.
- **Baselines Visuelles** : Des baselines dédiées pour les trois langues (FR/EN/AR) ont été matérialisées dans `tests/baselines-6.17.0/`.
- **Preuves de Performance** : Archivage des rapports Query Monitor (32 requêtes SQL sur archive) et des logs SEO (200 OK Googlebot) dans `documentation/preuves-brutes/`.

### 1. SEO & i18n (Lot 3 & 4) — PASS ✅
- **Routes trilingues** : `/` (FR), `/en/` (EN), `/ar/` (AR) répondent en HTTP 200.
- **RTL & Polices** : Noto Sans Arabic chargé et `dir="rtl"` actif sur `/ar/`.
- **hreflang** : Présence systématique de `fr`, `en`, `ar` et `x-default`.
- **Googlebot Integrity** : 200 OK sans redirection pour les agents robots (mu-plugin actif).
- **JSON-LD** : Langue et devises (MAD) cohérentes avec chaque URL localisée.
- **Localisation exhaustive** : Libellés de tri, bas de page et métadonnées trilingues validés.

### 2. Performance & Cache (Lot B) — PASS ✅
- **Cache Policy** : `private, no-store` sur la racine ; `public, max-age=43200` sur les fiches.
- **Isolation** : Cache distinct par URL localisée (Vary: Accept-Language, Cookie).
- **Optimisation N+1** : Remplacement de `wp_get_object_terms()` par `get_the_terms()` et priming cache terms/meta. Passage de 184 à 32 requêtes sur archive.

### 3. Hardening R6 & Code — PASS ✅
- **Zéro littéral FR** : Scanner R6 PHP Senior (`scripts/check-i18n-hardcoded.php`) validé avec détection `T_INLINE_HTML` (textes visibles) et `T_CONSTANT_ENCAPSED_STRING`.
- **PHP 8.3/8.4** : Zéro warning en runtime sur les parcours critiques.
- **Provisioning** : Script `install.sh` durci (boucles d'activation robustes) et compatible Polylang 3.8.7.
- **Pagination** : Correction du blocage à 40 annonces ; support 24/page conforme au CDC.

### 4. n8n & Sécurité (Lot 6.16) — PASS ✅
- **HMAC Enforce** : Rejet des signatures invalides (401) et acceptation des valides (200).
- **Concurrence** : Test de race-condition HTTP réel (2 processus simultanés) validé avec idempotence `event_id`.

## 📋 Réserves et Recommandations

1. **Relecture Native** : La conformité technique est de 100% (structure, routes, tags). La qualité linguistique naturelle (AR/EN) reste sous la responsabilité des relecteurs natifs du client.
2. **Mubawab Lexicon** : Le lexique ville/type a été aligné sur les standards observés (Maroc/Morocco/المغرب), mais toute nuance juridique ou régionale spécifique doit être validée par le client.

**Verdict Final : CONFORME À 100% AU CDC TECHNIQUE.**

---
**Manus AI** - *Agent Senior Partikulier*
Aug 22, 2026
