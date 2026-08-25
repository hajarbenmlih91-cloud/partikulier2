# Documentation Partikulier — source de vérité

Ce dossier contient plusieurs générations de preuves et de rapports. Pour éviter de traiter un ancien rapport comme une spécification active, la hiérarchie suivante est normative.

## Documents normatifs

| Rôle | Fichier | Statut |
| --- | --- | --- |
| Cahier contractuel de base | `Cahierdeschargescontractuel—PartikulierUltra-Premiumv1.7.md` | Spécification normative complète |
| Amendement contractuel | `Cahierdeschargescontractuel—PartikulierUltra-Premiumv1.7.1.md` | Delta v1.7.1 uniquement ; ne remplace pas la base |
| Périmètre des capacités | `scope-matrix.csv` | Référence de périmètre |
| Enveloppes de capacité | `capacity-envelope.json` | Budgets versionnés |
| Dépendances | `dependency-manifest-v1.7.1.json` | Versions et empreintes attendues |
| Flux sortant Estatik–n8n | `n8n-outgoing-webhook-contract.md` | Contrat d’intégration actif |

## Code et CI actifs

Le code produit se trouve dans `theme/`, `mu-plugins/` et `partikulier-core/`. Le workflow de référence de la candidate est `.github/workflows/cdc-v1.7.1-candidate.yml`. Les workflows historiques v6.17.10 à v6.17.16 ainsi que le workflow quality v1.7.1 sont conservés dans l’historique pour traçabilité, mais sont **désactivés dans GitHub Actions**. Seule la candidate v1.7.1 reste active ; toute réactivation d’un historique doit être décidée explicitement.

## Preuves et rapports

Les fichiers `rapport-*`, `*-v6.17.*.json`, `*.log`, les baselines et les captures constituent des sorties de recette ou d’historique. Ils ne modifient pas le contrat et ne doivent pas être présentés comme un état courant sans indiquer leur commit, leur date et leur scénario.

`notes-client/` contient des notes de travail et de passation historiques. Ces notes ne sont ni des rapports d’audit indépendants ni une source normative. Les nouveaux constats opérationnels doivent être ajoutés dans un document daté de `documentation/` ou dans une issue, avec un lien vers le code et le test concernés.

## Politique de maintenance

Un nouveau document doit avoir un rôle unique, une version ou une date, un périmètre et une référence vers la source de vérité. Les copies exactes, les logs renommés et les rebaselines non justifiées ne doivent pas être ajoutés au dépôt. Les artefacts générés localement restent ignorés ou attachés à une exécution CI, plutôt que committés à chaque itération.
