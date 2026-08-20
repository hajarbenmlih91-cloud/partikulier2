# Qualification senior — Partikulier 6.15.0

## Verdict

La version 6.15.0 est **implémentée et validée sur la sandbox WordPress 6.4.3 provisionnée avec Estatik et Polylang 3.5.4**. Les lots H et K passent leurs tests dynamiques principaux. La recette a également détecté puis corrigé un défaut réel du parcours Estatik : la métadonnée `es_property_gallery` était stockée en JSON texte, alors que l’override ne traitait que les tableaux PHP.

La conformité déclarée ici porte sur le code et les parcours testés dans cette sandbox. Les scripts JSON joints constituent les preuves reproductibles ; ils ne remplacent pas une recette sur une base de production anonymisée avant déploiement.

## Résultats par lot

| Lot | Résultat | Preuve |
|---|---|---|
| H — options thème | PASS | `rapport-lot-h-dynamic-6.15.0.json` |
| H — defaults/fallback FR | PASS | `rapport-lot-h-dynamic-6.15.0.json` |
| H — hooks cache création/mise à jour | PASS | `rapport-lot-h-dynamic-6.15.0.json` |
| K — tableau Leads et export | PASS | `rapport-lot-k-dynamic-6.15.0.json`, `rapport-ui-backoffice-k-6.15.0.md` |
| K — budget SQL 1/20/100 lignes | PASS | 6 requêtes mesurées pour chaque cardinalité dans `rapport-lot-k-dynamic-6.15.0.json` |
| K — neutralisation CSV hostile | PASS | `rapport-lot-k-dynamic-6.15.0.json` et preuve navigateur |
| D — Polylang FR/EN/AR, vrai `sync()` | PASS | `rapport-polylang-e2e-6.15.0.json` |
| D — migration dry-run/apply | PASS | `rapport-polylang-migration-e2e-6.15.0.json` |
| Estatik archive/fiche/favoris | PASS | `rapport-estatik-interactions-6.15.0.json` |
| Estatik galerie/images | PASS | `gallery: 4`, `images: 3`, `consoleErrors: []` |
| Sécurité dynamique | PASS | 15/15 dans `rapport-securite-6.15.0-final.txt` |
| Contrôle syntaxe et versions | PASS | `rapport-check-6.15.0-final.txt` |

## Preuve Polylang

Le provisioning réel a créé les langues FR, EN et AR ainsi que les familles de traductions. Le scénario E2E appelle `Partikulier_Listing_Translations::sync()` sans préparer manuellement les métadonnées. L’auto EN seule reste publiée. Après remplacement par une EN manuelle et vraie écriture `save_post`, l’auto EN passe en `draft` et la manuelle reste `publish`. Une ancienne valeur `fr` est signalée comme `invalid_source_meta` au lieu d’être castée silencieusement.

La migration prouve séparément le dry-run non destructif, puis l’application idempotente : `_pk_translation_source` devient l’ID numérique source, `_pk_translation_source_legacy` conserve `fr` et `_pk_source_lang` vaut `fr`.

## Preuve Estatik

Le test initial a révélé un échec de fixture réel, non masqué : l’URL Marc historique n’était pas une fiche publique et `es_property_gallery` était un JSON texte non décodé. Le test utilise maintenant la fiche Sofia réellement provisionnée et l’override décode le JSON avant de construire la galerie. Le parcours final constate : fiche HTTP 200, galerie détectée, 3 images, favoris ajoutés puis retirés, aucune erreur console.

## Réserves et limites

Le test K export autonome par `wp eval-file` ne constitue pas une preuve indépendante fiable de téléchargement HTTP, car le handler de téléchargement dépend du contexte de requête et de nonce WordPress. La preuve d’export retenue est celle du formulaire admin réel dans la session navigateur authentifiée, complétée par le test K qui vérifie la neutralisation CSV et le budget SQL.

Le budget SQL de 6 requêtes est mesuré sur la sandbox et reste à comparer à la production avec Query Monitor si le volume ou les extensions actives diffèrent. Les parcours publics ont été rejoués après synchronisation effective du thème 6.15.0 ; aucune erreur JavaScript n’a été constatée sur les vues testées.

## Reproduction

```bash
bash scripts/install.sh
wp --path=wp plugin install polylang --version=3.5.4 --activate
wp --path=wp eval-file scripts/provision-polylang.php
wp --path=wp eval-file scripts/test-backoffice-h.php
wp --path=wp eval-file scripts/test-backoffice-k.php
wp --path=wp eval-file scripts/test-polylang-sync-e2e.php
wp --path=wp eval-file scripts/test-polylang-migration.php
npm run test:securite
npm run test:audit
bash scripts/check.sh
```

**Conclusion :** lots H et K validés par tests dynamiques sur la sandbox ; correction Estatik validée ; Polylang et migration validés ; aucune signature de conformité production ne doit être donnée avant la recette sur la base de production anonymisée et la validation du téléchargement CSV dans l’environnement cible.
