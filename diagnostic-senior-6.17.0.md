# Diagnostic Senior 6.17.0 — Écarts et Causes Racines

## 1. Traçabilité (Bloqueur)
- **Problème** : Le rapport certifie un commit (`9f21353...`) et un ZIP (`6b1d26c...`) différents de ceux présents sur GitHub.
- **Cause** : Processus de packaging manuel non synchronisé avec le push final.
- **Action** : Automatiser le commit du hash et du rapport APRES la génération du ZIP final.

## 2. N+1 Estatik (Lot B)
- **Problème** : Mesuré à ~200 requêtes au lieu de 32.
- **Cause Racine** : 
    - `card-property.php` appelle `wp_get_object_terms()` individuellement pour chaque carte.
    - `update_post_term_cache` dans `pre_get_posts` ne suffit pas car `wp_get_object_terms` outrepasse souvent le cache si les arguments diffèrent ou si l'ID est passé en dur.
- **Action** : Utiliser `get_the_terms()` qui exploite le cache de `WP_Query` ou implémenter un `prime_post_caches()` manuel plus agressif.

## 3. Pagination (24/page)
- **Problème** : Bloquée à 40/page, page 4 en 404.
- **Cause Racine** : Estatik force `posts_per_page = 40` via son option interne `properties_per_page`.
- **Action** : 
    - Ajouter un filtre sur `es_settings` pour forcer `properties_per_page = 24`.
    - Augmenter la priorité du hook `pre_get_posts` à 20+ pour s'assurer de passer après Estatik.

## 4. i18n / R6
- **Problème** : `T_INLINE_HTML` non scanné, libellés FR encore visibles en AR/EN.
- **Action** : 
    - Mettre à jour `check-i18n-hardcoded.php` pour analyser `T_INLINE_HTML`.
    - Localiser "ANNONCES", "Villes populaires", "Aucune inscription obligatoire".

## 5. Hygiène du dépôt
- **Problème** : Fichiers binaires (ZIP) et rapports de test trackés dans l'historique Git.
- **Action** : `git rm --cached` massif sur les artefacts binaires.
