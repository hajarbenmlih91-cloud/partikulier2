# Rapport de qualification senior — Partikulier 6.14.1

## Verdict

La livraison 6.14.1 est qualifiée **conforme aux critères corrigés du CDC sur la sandbox de recette**, après correction des défauts signalés par les audits externes. Les preuves ont été rejouées après synchronisation du thème dans WordPress. Le verdict est fondé sur les résultats ci-dessous et non sur une baseline régénérée seule.

## Correctifs appliqués

| Sujet | Correction | Fichier |
|---|---|---|
| JSON-LD | Remplacement de la seconde valeur `EUR` par `MAD` dans `priceSpecification`. Le pays reste `MA`. | `theme/inc/class-jsonld.php` |
| Archive legacy | Interception `parse_request` prioritaire, en plus de `template_redirect`, pour garantir les 301 `/property/` et `/property/page/N/` avant un éventuel 404. | `theme/inc/class-listing-urls.php` |
| Polylang | Normalisation préalable de toutes les annonces publiées sans langue vers `fr`, puis création idempotente des familles EN/AR. | `scripts/provision-polylang.php` |
| Estatik E4 | Ajout des vrais handles Estatik 4.3.4 (`es-select2`, `es-slick`, `es-magnific`, `es-datetime-picker`) au nettoyage conditionnel hors parcours immobilier. | `theme/inc/class-estatik.php` |

## Preuves finales

### Contrôles statiques

- `scripts/check.sh` : PASS.
- 65 fichiers PHP : aucune erreur de syntaxe.
- 2 fichiers JavaScript : aucune erreur de syntaxe.
- Versions cohérentes : `6.14.1` dans les quatre fichiers contrôlés.
- Favoris : un seul gestionnaire `addEventListener` détecté.
- Recherche source `EUR` dans `theme/` et `scripts/` : aucune occurrence de devise résiduelle.

### Routes et SEO

| URL | Résultat observé |
|---|---|
| `/annonces/` | HTTP 200 |
| `/en/annonces/` | HTTP 200 |
| `/ar/annonces/` | HTTP 200 |
| `/property/` | HTTP 301 vers `/annonces/` |
| `/property/page/2/` | HTTP 301 vers `/annonces/page/2/` |

Sur une fiche réelle, le JSON-LD retourné contient deux occurrences `priceCurrency`, toutes deux à `MAD`. L’ancienne incohérence `MAD`/`EUR` n’est plus présente après synchronisation du thème.

### Polylang et taxonomies

Le provisioning a été rejoué avec Polylang 3.8.7 actif. La sandbox a produit 18 sources FR et les routes linguistiques FR/EN/AR répondent en 200. Le script est maintenant idempotent sur les annonces existantes : il affecte d’abord les annonces publiées sans langue à FR avant de rechercher les sources FR et de créer les traductions.

Le rapport de taxonomies doit être interprété comme une preuve de dataset de recette : les traductions et leurs termes sont vérifiés par les rapports JSON existants. Toute migration de production doit néanmoins être exécutée en dry-run puis validée par une sauvegarde de base.

### Estatik et interactions

Les quatre parcours réels ont été rejoués avec Estatik 4.3.4 : archive, fiche, dépôt et favoris. Résultats observés :

| Parcours | HTTP | Console | Éléments observés |
|---|---:|---:|---|
| Archive | 200 | 0 erreur | 18 favoris, 18 images, 17 filtres |
| Fiche | 200 | 0 erreur | galerie 4, images 5, 3 contrôles favoris |
| Dépôt | 200 | 0 erreur | 5 formulaires, 7 filtres |
| Favoris | 200 | 0 erreur | page chargée correctement |

Le test interactif a ajouté puis supprimé le favori `postId=50`, avec `aria-pressed` passant à `true` puis `false`, et `localStorage` revenant à une liste vide. Statut : PASS.

### Lot B — N+1

La qualification retenue est **non-régression SQL**, pas gain de performance global. Aucun motif N+1 nouveau attribuable au thème n’a été détecté dans les profils avant/après existants. Les répétitions résiduelles liées à Polylang doivent rester surveillées séparément ; elles ne sont pas présentées comme supprimées.

### Visuel

La baseline propre 6.14.1 passe à 0,00 % sur les 12 vues. La comparaison historique 6.13.1/6.14.1 conserve les écarts documentés liés au changement fonctionnel du filtre et au contenu multilingue. Ils ne doivent pas être présentés comme un « zéro pixel diff historique ».

## Procédure de reprise

Depuis la racine du dépôt :

```bash
bash scripts/install.sh
bash scripts/start.sh
wp --path=wp plugin install polylang --activate
wp --path=wp plugin install query-monitor --activate
wp --path=wp eval-file scripts/provision-polylang.php
wp --path=wp rewrite flush
bash scripts/check.sh
npm install --no-audit --no-fund
npx playwright install chromium
node scripts/real-ui-evidence.mjs
node scripts/test-estatik-interactions.mjs
bash scripts/package.sh 6.14.1
```

Les sorties doivent être conservées dans `rapport-*`, puis comparées aux rapports présents dans `preuves/6.14.1/INDEX.md`. Une installation de production doit être précédée d’une sauvegarde et d’un dry-run des taxonomies.

## Limites assumées

Cette qualification ne prétend pas démontrer un gain absolu de performance contre toutes les configurations d’hébergement. Elle démontre l’absence de régression fonctionnelle et de nouveau motif N+1 dans le protocole de recette disponible. Les tests visuels historiques doivent utiliser exactement le même snapshot de données et les mêmes assets si un zéro pixel diff historique est exigé.

**Conclusion :** les défauts bloquants signalés par les audits — devise EUR résiduelle, pagination legacy instable, provisioning Polylang non idempotent et handles Estatik incomplets — ont été corrigés et vérifiés sur la sandbox active. Le dépôt doit être livré avec ce rapport, les scripts et les rapports bruts associés.
