# Correspondance O1–O22 vers O1–O18

**SHA de référence :** `e488b12bc518922f8148a859ce8835e643267700`
**Statut de la correspondance :** proposition de gouvernance à approuver; elle ne constitue pas une preuve technique ni un GO.

Le passage de 22 à 18 objectifs ne supprime pas les domaines métier, staging, TTFB, rollback, nettoyage ou signoffs. Il regroupe certains contrôles atomiques dans des objectifs plus larges. Les éléments non conservés comme objectifs autonomes restent des assertions ou des preuves obligatoires dans l’objectif parent.

| Ancien objectif | Nouveau objectif | Action | Justification |
|---|---|---|---|
| O1 provenance Git, architecture et packaging | O1, O2, O12, O13 | Conservé et réparti | La provenance devient O1; le périmètre devient O2; la CI finale devient O12; le package devient O13. |
| O2 syntaxe PHP | O2 | Fusionné comme contrôle de périmètre/qualité | La syntaxe PHP reste une preuve technique obligatoire mais n’est plus un gate autonome dans la nouvelle liste ouverte. |
| O3 syntaxe JavaScript | O2 | Fusionné comme contrôle de périmètre/qualité | Même traitement; aucun contrôle de syntaxe n’est supprimé, il est rattaché au diff et à la qualité. |
| O4 sécurité statique Semgrep | O2 | Fusionné | Semgrep reste une preuve du diff et de la qualité technique; il n’est plus séparé dans les gates ouverts. |
| O5 secrets dépôt | O2 | Fusionné | Le scan de secrets reste rattaché à la revue du diff; aucune tolérance supplémentaire n’est créée. |
| O6 surface REST/AJAX/headers | O3 | Renommé et conservé | Devient explicitement la portée de sécurité REST léger, minimum 10/10. |
| O7 Estatik données et filtres | O4, O5, O6 | Réparti | Équivalence REST devient O4; archives HTML deviennent O5; langues métier deviennent O6. |
| O8 localisation arabe/JSON-LD | O6 | Fusionné | Les assertions FR/EN/AR, ville, prix, image, badge et JSON-LD restent dans les langues métier. |
| O9 UI/UX responsive multi-navigateurs | O10, O16 | Réparti | Le pixel automatisé devient O10; la revue visuelle humaine devient O16. Les résultats déjà PASS restent historiques sur leur SHA. |
| O10 accessibilité automatisée | O16, O18 | Réparti | La revue WCAG humaine relève des signoffs; les validations finales sont couvertes par O16/O18 selon le périmètre. |
| O11 simulation immédiate du filtre | O12 | Fusionné dans la CI finale | Les simulations déjà PASS restent une preuve de CI; elles ne sont pas rejouées sans changement de SHA ou de périmètre. |
| O12 simulation après chargement JS | O12 | Fusionné dans la CI finale | Même traitement. |
| O13 simulation bouton fermer | O12 | Fusionné dans la CI finale | Même traitement. |
| O14 simulation Escape/focus | O12 | Fusionné dans la CI finale | Même traitement. |
| O15 autocomplete ville filtre | O12 | Fusionné dans la CI finale | Même traitement; toute nouvelle anomalie reste bloquante. |
| O16 autocomplete recherche principale | O12 | Fusionné dans la CI finale | Même traitement. |
| O17 filtre cumulatif type+ville | O5, O12 | Réparti | La logique métier relève des archives; l’exécution automatisée relève de la CI finale. |
| O18 reset filtre | O12 | Fusionné dans la CI finale | Même traitement. |
| O19 données structurées arabe | O6, O10 | Réparti | Le contenu métier relève des langues; le rendu pixel relève du visual gate. |
| O20 nonce/auth/réflexion | O3, O12 | Réparti | La sécurité de portée REST relève d’O3; la preuve d’exécution CI relève d’O12. |
| O21–O22 du contrat précédent | O12, O13, O14, O15 | Reclassés | Les éléments de CI, package, upgrade et rollback sont maintenant des gates explicites de clôture. |

## Objectifs de la nouvelle liste qui ne sont pas de simples renommages

Les objectifs O7 et O8 de la nouvelle liste ajoutent des preuves live TTFB et d’attribution Hostinger qui n’étaient pas fermées par le contrat précédent. O9 regroupe la capacity nominale et burst. O11 exige le manifeste officiel des 30 baselines. O14 et O15 séparent désormais explicitement upgrade et rollback idempotent. O17 impose l’autorisation avant le nettoyage de la fixture 249 et du média 250. O18 exige huit signoffs finaux.

## Décision de gouvernance

Cette correspondance est une **réduction de la liste des gates ouverts**, pas une réduction du niveau d’exigence. Elle doit être approuvée avant de considérer le périmètre O1–O18 comme définitif. En l’absence d’approbation explicite, le contrat doit être considéré `INVALID`, exit code `2`, et le verdict reste **NO-GO**. Aucun objectif staging, TTFB, métier, langue, rollback, nettoyage ou signoff n’est supprimé; chacun reste présent dans O1–O18 ou dans ses preuves obligatoires.
