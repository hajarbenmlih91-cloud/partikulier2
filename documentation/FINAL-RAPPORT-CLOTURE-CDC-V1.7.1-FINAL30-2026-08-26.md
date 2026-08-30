# Rapport de clôture CDC v1.7.1 — Partikulier final30

**Date :** 26 août 2026
**Version candidate :** 6.17.22
**Commit source final publié :** [`9ffe32c`](https://github.com/hajarbenmlih91-cloud/partikulier2/commit/9ffe32cc05984e4a8897cd22422aefae6d5a0702)
**Dépôt :** [`hajarbenmlih91-cloud/partikulier2`](https://github.com/hajarbenmlih91-cloud/partikulier2/tree/automation/release-approval-gate-v1.7.1)
**Environnement WordPress :** staging Hostinger uniquement ; aucune action de production.

## Verdict

> **NO-GO final.** La candidate final30 n’est pas certifiable et aucune release ni tag de production ne doit être publié.

Le thème final30 a été installé et activé sur le WordPress de test. La preuve publique observée est `wp-theme-partikulier-cdc-v18-final30`. Le package thème déployé est [`partikulier-cdc-v18-final30.zip`](https://github.com/hajarbenmlih91-cloud/partikulier2/tree/automation/release-approval-gate-v1.7.1), avec le SHA-256 local `cbe953ed197903e6683dee8bcf0ca2737f5f78a3870e1f4f903010a3da4c3b5a`.

## Matrice de décision

| Domaine | Résultat | Preuve | Conséquence |
|---|---:|---|---|
| Gates statiques CI | **PASS** | Run [`32934222939`](https://github.com/hajarbenmlih91-cloud/partikulier2/actions/runs/32934222939), job statique vert | Ne suffit pas à autoriser la release |
| `scripts/check.sh` local | **PASS** | R6 à 0 texte visible en dur ; syntaxe et versions cohérentes | Conforme sur le périmètre local |
| Semgrep projet CI | **PASS** | 0 finding bloquant sur 66 cibles, Semgrep 1.132.0 | Gate projet vert |
| Semgrep `p/default` global local | **PASS** | 0 finding sur 345 cibles après corrections | Aucun finding global résiduel connu |
| WPCS sécurité borné | **PASS avec avertissements** | 0 erreur ; avertissements intentionnels documentés | Le passif de style complet reste ouvert |
| PHPStan borné niveau 5 | **PASS** | 0 erreur à 1 Go, périmètre explicite | Ne remplace pas un audit PHP exhaustif |
| PHPCS complet historique | **NON PASS** | 171 erreurs de style/documentation héritées | Dette technique non bloquante mais non résolue |
| Accessibilité CI froide | **PASS** | 6/6 FR/EN/AR desktop/mobile | La revue WCAG humaine indépendante manque |
| Smoke Chromium live | **PASS** | 7/7 : archives, vente, location observation, JSON-LD AR, popup/images | Un seul navigateur ne clôt pas la matrice |
| Smoke Firefox live | **FAIL** | 6/7 ; images invalides sur le viewport 320 lors du run | Gate multi-navigateurs non vert |
| Smoke WebKit live | **NOT PASS** | Processus bloqué après code d’échec, arrêté sans preuve verte | Gate WebKit non démontré |
| Visual contract CI | **BLOCKED/FAIL** | Manifest `tests/baselines-6.17.22/SHA256SUMS` absent ; aucune rebaseline inventée | Revue et approbation visuelles obligatoires |
| Load test froid 1000 annonces | **PASS** | 1000 annonces ; p95 0,170716 s ; p99 0,172643 s ; 0 erreur | Valide uniquement le runtime CI de référence |
| Capacity sustained read | **PASS** | 10 RPS pendant 900 s ; 9000 réponses HTTP 200 | Une phase du contrat seulement |
| Capacity write API | **PASS** | 2 RPS pendant 900 s ; 1800 réponses HTTP 201 ; nettoyage effectué | Une phase du contrat seulement |
| Capacity 50 sessions | **PASS** | 50 sessions, 0 erreur | La saturation reste en échec |
| Capacity burst read | **FAIL** | 25 RPS cible ; 23,267 RPS effectifs ; CPU moyen 91,845 % | Dépassement du seuil CPU et de livraison |
| Capacity saturation 50 RPS | **FAIL** | 28,2 RPS effectifs ; p95 2,1837 s ; CPU 91,863 % | Enveloppe contractuelle non tenue |
| Upgrade/rollback technique | **PASS pour upgrade** | Upgrade v6.17.16→v6.17.22 : données, réglages, compte, idempotence et sentinelle préservés | Le rollback production reste séparé |
| TTFB live Hostinger | **FAIL** | 2,524–11,514 s sur la mesure finale ; répétitions chaudes 1,68–3,9 s | Seuil contractuel `<800 ms` non atteint |
| LCP live | **NOT RUN/PASS non démontré** | Pas de mesure stable sous le seuil avec TTFB actuel | Ne peut pas être déclaré conforme |
| Filtre vente | **PASS** | `es_action=a-vendre`, cartes et badges observés | Chemin démontré |
| Filtre location | **NOT PROVEN** | `a-louer` répond HTTP 200 mais le dataset live ne fournit pas de preuve de résultat locatif | Fixture rental et preuve dédiée nécessaires |
| Signoffs humains UX/langues/visuel | **BLOCKED** | Attestations natives FR/EN/AR, revue WCAG, revue UX et revue visuelle absentes | Bloque toute certification |
| Approbation commerciale | **BLOCKED** | Décision produit non fournie | Bloque la release commerciale |

## Performance et infrastructure

Le patch de cache applicatif est bien présent dans la candidate et le cache HCDN atteint parfois `HIT`. Toutefois, le serveur public répond avec `server: hcdn`, `platform: hostinger` et un TTFB largement supérieur au contrat. LiteSpeed Cache 7.9 est actif sur le staging, mais aucun header serveur LiteSpeed exploitable ni connexion QUIC.cloud n’a permis de démontrer un cache HTML serveur conforme. Le plugin seul ne compense pas le coût du bootstrap WordPress et ne constitue donc pas une preuve de performance.

La correction requise est infrastructurelle ou architecturale : obtenir une vraie couche de page cache/CDN HTML compatible avec les routes localisées, ou réduire le coût serveur jusqu’à respecter `<800 ms` à chaud, sans mettre en cache les sessions, routes privées ou utilisateurs authentifiés. Cette étape doit être re-mesurée avec plusieurs répétitions et une mesure LCP indépendante.

## CI et capacité

Le premier run [`32930165203`](https://github.com/hajarbenmlih91-cloud/partikulier2/actions/runs/32930165203) a été annulé à 55 minutes avant la clôture des phases longues. Le second run unique conservé, [`32934222939`](https://github.com/hajarbenmlih91-cloud/partikulier2/actions/runs/32934222939), a été autorisé à 90 minutes. Il a terminé la recette froide, mais le gate capacity a échoué sur le burst et la saturation ; le job global est donc **FAIL** et les jobs package/release ont été ignorés.

Le run a néanmoins démontré l’upgrade technique de v6.17.16 vers v6.17.22 avec un dataset de 1001 annonces et une sentinelle préservée. Cela ne transforme pas l’échec capacity en PASS et ne remplace pas les signoffs humains.

## Correction de gouvernance appliquée

Les scripts et le contrat ont été réalignés sur 6.17.22 : defaults acceptance/capacity/upgrade/visual/approvals, matrice de périmètre, nom de base CI, baseline directory et timeout de job. Le contrat visuel pointe désormais vers `tests/baselines-6.17.22`, mais le manifest n’existe pas encore. Cette absence est volontairement conservée comme blocage : aucune baseline n’a été régénérée ou approuvée automatiquement pour masquer les changements visuels.

Les actions GitHub utilisées par les workflows actifs sont référencées par SHA immuable. Les anciens workflows redondants ont été retirés de la branche candidate. Aucun secret, cookie, mot de passe, `wp-config`, dump SQL ou clé n’a été publié.

## Actions obligatoires avant une nouvelle décision

1. Corriger la couche HTML cache/serveur Hostinger ou l’architecture de rendu, puis fournir des mesures TTFB et LCP reproductibles sous les seuils contractuels.
2. Diagnostiquer et corriger la capacité burst/saturation : la cible 25 RPS et la limite CPU doivent être satisfaites sans diminuer les seuils.
3. Capturer les 30 baselines visuelles final30 dans un environnement stable, les faire examiner et approuver indépendamment, puis publier le manifest SHA-256 correspondant.
4. Rejouer Firefox et WebKit avec des résultats verts reproductibles ; l’échec image Firefox et l’absence de preuve WebKit restent ouverts.
5. Créer uniquement dans le runtime de test une fixture de location explicitement traçable, puis prouver `a-louer`, badge, cartes, URLs et éventuellement JSON-LD ; ne pas extrapoler depuis HTTP 200.
6. Obtenir les attestations indépendantes WCAG/UX/design et les validations natives FR/EN/AR, puis une décision commerciale explicite.
7. Rejouer le run CI complet depuis le nouveau commit après correction et ne publier aucun tag tant que le job froid, le package déterministe et tous les gates ne sont pas verts.

**Conclusion :** le travail de code final30 est publié sur GitHub et déployé sur le WordPress de test, mais les preuves obligatoires ne permettent pas un GO. Le verdict professionnel reste **NO-GO**.
