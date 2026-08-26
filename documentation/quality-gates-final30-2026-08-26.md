# Gates locaux final30 — 2026-08-26

Cette note décrit uniquement les contrôles exécutés sur l’arbre source local final30 avant publication. Elle ne constitue pas une preuve de réussite de la CI froide, du TTFB Hostinger ni des tests live multi-navigateurs.

| Contrôle | Résultat | Preuve / portée |
|---|---|---|
| `bash scripts/check.sh` | PASS | 66 fichiers PHP, 21 fichiers JavaScript, versions 6.17.22 concordantes, R6 à 0 texte visible en dur |
| Lint PHP/JavaScript | PASS | Tous les fichiers PHP du thème/scripts et fichiers JS/MJS contrôlés |
| `git diff --check` | PASS | Arbre sans espaces finaux ni erreurs de patch |
| `npm ci --dry-run --ignore-scripts` | PASS | Racine et `theme/`, lockfiles respectés |
| Semgrep `p/default` | PASS | 0 finding sur 345 cibles ; sortie JSON `semgrep-p-default-final30.json` |
| WPCS sécurité borné | PASS avec avertissements | 0 erreur ; avertissements documentés sur `exec`, filesystem direct, nonce GET et base64 de chiffrement |
| PHPCS full legacy | NON BLOQUANT / PASSIF À TRAITER | Le standard complet remonte surtout le style historique, les docblocks et les noms de fichiers des modules existants ; il n’est pas présenté comme vert |
| PHPStan niveau 5 borné | PASS | 4 fichiers analysés, mémoire explicite 1024M, 0 erreur ; configuration `phpstan.neon` |
| Composer | PASS | `composer validate --strict` sans avertissement |

Le gate WPCS de sécurité utilise `phpcs-security.xml.dist` et échoue sur toute erreur, mais ignore la sortie non nulle liée aux avertissements intentionnels afin de ne pas confondre avertissement d’hébergement avec vulnérabilité. Le rapport complet reste disponible via `composer run phpcs:legacy-audit` et doit faire l’objet d’un chantier de normalisation séparé.

La preuve p/default a été exécutée avec Semgrep 1.132.0 sur le dépôt suivi, en excluant uniquement les dépendances installées, les tests et la documentation historique. Elle est conservée au format JSON pour permettre une relecture indépendante.
