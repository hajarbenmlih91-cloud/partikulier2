# Rapport de recette réelle — Partikulier 6.14.1

Date de recette : 2026-08-20. Environnement : WordPress sandbox local, thème Partikulier 6.14.1, Estatik 4.3.4, Polylang 3.8.7, Query Monitor 4.0.7.

## Polylang AUTO — D

Le provisioning réel configure les langues `fr`, `en` et `ar`. Les décomptes mesurés sont `fr=6`, `en=6` et `ar=6`. Les six familles d’annonces françaises possèdent chacune une traduction anglaise et arabe reliée par Polylang. Les routes `/annonces/`, `/en/annonces/` et `/ar/annonces/` répondent HTTP 200 et produisent les balises canonical et hreflang attendues.

**Résultat : PASS.**

## Taxonomies — F

Le dry-run de `scripts/reparer-taxonomies.php` est exploitable et conclut : trois termes dans `es_category`, zéro terme hors place et zéro annonce à déplacer. Le contrôle métier complémentaire porte sur 18 annonces publiées : aucune n’est dépourvue d’action ou de localisation après réparation des traductions (`missing_action_ids=[]`, `missing_location_ids=[]`).

La réparation a affecté les taxonomies Estatik aux 12 traductions EN/AR existantes, avec 36 affectations manquantes corrigées.

**Résultat : PASS pour l’état de la sandbox et le dry-run.**

## Assets et interactions Estatik — E4/E5

Les parcours `/annonces/`, une fiche réelle, `/deposer-une-annonce/` et `/favoris/` répondent HTTP 200 sans erreur JavaScript. Les scripts Estatik réellement chargés incluent `public.min.js`, `ajax-entities.min.js`, `framework.js`, Select2, Magnific Popup et Slick. La fiche réelle contient quatre éléments de galerie et trois images.

Le test interactif clique réellement sur un favori d’annonce, vérifie `aria-pressed=true`, vérifie l’écriture de l’identifiant dans `localStorage`, reclique, puis vérifie le retour à `aria-pressed=false` et la suppression du stockage. Le test se termine par `PASS`, sans erreur console.

**Résultat : PASS.**

## N+1 — B

La collecte compatible Query Monitor/SAVEQUERIES donne zéro motif de requête dupliqué signalé sur les parcours mesurés. L’archive reste à trois requêtes dans les deux versions. Les compteurs bruts relevés sont toutefois sensibles au contexte multilingue et aux routes de fiche : la référence 6.13.1 mesurée sur la fiche historique est à 56 requêtes, tandis que la 6.14.1 avec Polylang provisionné est à 79 requêtes sur une fiche FR. Les pages dépôt et favoris sont respectivement à 52/52 dans la mesure finale.

Cette différence brute ne permet pas de prétendre à un gain N+1 démontré ; elle montre l’absence de motif dupliqué détecté, mais pas une baisse globale du nombre de requêtes. Le lot B est donc **non signable comme “gain mesuré”** sans un protocole CDC précisant le même jeu de données et le même contexte de plugins pour l’avant/après.

**Résultat : PARTIEL — absence de doublons détectés, gain non démontré.**

## Baseline historique — comparaison 6.13.1 / 6.14.1

Une baseline indépendante 6.13.1 a été générée depuis le thème historique, puis comparée aux 12 captures 6.14.1 avec les mêmes dimensions. Les vues desktop accueil, dépôt, 404 et espace sont à 0 %. Les écarts desktop archive sont de 0,0163 %. Les écarts mobiles sont de 2,5127 % sur l’accueil et 3,2781 % sur l’archive, concentrés dans l’ordre et les titres des cartes d’annonces après provisionnement multilingue ; la structure, le hero, les filtres et le pied de page restent alignés.

**Résultat : PASS pour la comparaison authentifiée, mais pas “zéro pixel” sur toutes les vues.**

## Verdict

La recette réelle valide D, F et E4/E5. Le lot B reste partiel, et la comparaison historique révèle des écarts mobiles liés aux données rendues. La livraison ne doit donc toujours pas être annoncée comme conforme à 100 % au CDC tant que le protocole N+1 n’est pas harmonisé et que les écarts mobiles ne sont pas soit corrigés, soit explicitement acceptés comme variations de données.
