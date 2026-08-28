# Statut exact du SHA e488b12

**SHA:** `e488b12bc518922f8148a859ce8835e643267700`
**Branche:** `automation/capacity-apcu-a58942c`
**Run CI:** [33140371484](https://github.com/hajarbenmlih91-cloud/partikulier2/actions/runs/33140371484)
**Date du run:** 2026-08-28, UTC
**Verdict:** **NO-GO**

## Ce qui a été réellement exécuté

Le job static est PASS, y compris le validateur du contrat O1–O18. Le cold acceptance a terminé avec exit code `1`. Le log officiel indique `VISUAL_EXIT=1`, `CAPACITY_EXIT=0`, `UPGRADE_EXIT=0` et `UI_V18_EXIT=0`. Le package job n’a pas été exécuté parce que le cold acceptance a échoué.

Le résumé pixel officiel du SHA exact est `total=30 pass=26 fail=4`. Les quatre échecs sont : `archive-ar-desktop` à `2,2919 %`, `archive-ar-mobile` à `2,9357 %`, `single-ar-desktop` à `11,0918 %` et `single-ar-mobile` à `14,9053 %`, pour un seuil de `0,5 %`. Les compteurs UI Firefox, Chromium et WebKit sont séparément `30/30` sur le contrôle UI non-pixel, mais cela ne transforme pas le pixel visual en PASS.

## Correspondance de périmètre

Le contrat précédent O1–O22 a été regroupé vers O1–O18; le tableau détaillé est dans `o1-o22-mapping-e488b12.md`. Cette correspondance est une proposition de regroupement, pas une approbation utilisateur. Tant que la réduction de périmètre n’est pas approuvée, elle doit être considérée INVALID, exit code `2` pour la gouvernance du périmètre. Aucun domaine n’est considéré fermé uniquement parce qu’il a été fusionné.

## Non exécuté ou non prouvé

Le staging est resté sur `a58942c`; aucun déploiement Hostinger/Cloudflare n’a été effectué. Le TTFB et son attribution Hostinger ne sont pas fermés. Les archives métier HTML, les langues métier, le rollback final, la revue visuelle humaine, le nettoyage de la fixture 249 et du média 250, ainsi que les huit signoffs restent non prouvés. La fixture 249 et le média 250 sont conservés.

## Règle de décision

La CI exacte sur `e488b12` ne constitue pas un GO : le cold acceptance est FAIL et le pixel visual est `26/30`. Aucune modification CSS, template, asset ou baseline n’a été faite pour masquer l’échec. La cause des quatre écarts arabes doit être reproduite et attribuée avant toute correction.
