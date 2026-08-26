# Remédiation capacity et navigateurs — 2026-08-26

## Candidat évalué

Le run GitHub Actions `32966025417` a évalué le commit `a8fd5ec715118f2a974e352c4299f62c428e6fb0` dans un runtime WordPress/MariaDB froid. Les contrats statiques ont réussi en 57 secondes. Le job froid a terminé en échec après 56 min 54 s uniquement au gate capacity line 145.

## Résultat capacity exact

| Phase | Résultat | Mesure principale | Ressource / observation |
|---|---|---:|---|
| Lecture soutenue 10 RPS / 900 s | PASS | 10,000 RPS effectifs ; p95 0,106 s ; p99 0,113 s | CPU moyen non bloquant |
| Burst lecture 25 RPS / 60 s | FAIL | 22,817 RPS effectifs ; p95 1,173 s ; p99 1,197 s | 0 erreur HTTP |
| Écriture 2 RPS / 900 s | PASS | 2,000 RPS effectifs ; p95 0,111 s ; p99 0,118 s | CPU moyen 5,075 % |
| 50 sessions concurrentes | PASS | 50 requêtes ; 0 erreur | CPU moyen 91,647 % |
| Probe saturation 50 RPS / 10 s | FAIL | 27,6 RPS effectifs ; p95 2,193 s ; p99 2,206 s | CPU moyen 90,5 %, 0 erreur HTTP |

Le test a supprimé 1 800 posts de fixture et 50 utilisateurs temporaires. Les index SQL `status_locale` et variantes de tri étaient déjà présents. Le runtime de référence utilisait 4 workers PHP-FPM, et APCu n’était pas installé ; le rate limiter public suivait donc le fallback WordPress Transient à chaque requête.

## Retests navigateurs live

Le smoke final30 retesté sur le staging a passé les archives FR/EN/AR, le filtre vente, JSON-LD AR et la popup/images mobile sur Firefox et WebKit. Après correction du harnais, le filtre location est désormais classé `BLOCKED` lorsqu’il renvoie 21 cartes de vente et 0 badge location, au lieu d’être présenté comme un PASS HTTP trompeur.

| Navigateur | Résultat technique | Location | Images mobile |
|---|---|---|---|
| Firefox | 6 PASS / 1 BLOCKED dataset | 21 cartes, 20 badges vente, 0 location | 21 images valides, aucun overflow aux largeurs 320/360/375/390 |
| WebKit | 6 PASS / 1 BLOCKED dataset | 21 cartes, 20 badges vente, 0 location | 21 images valides, aucun overflow aux largeurs 320/360/375/390 |

## Correction suivante proposée

Le candidat suivant diffère les classes et le scheduling du `JobRunner` sur les pages publiques, conserve les classes REST nécessaires, corrige le comportement d’un filtre transactionnel non résolu pour éviter de renvoyer silencieusement les ventes complètes, et renforce les contrôles typage/sécurité APCu et variables serveur. Aucun fichier CSS, template, carte, JavaScript UX, texte public ou structure visuelle n’est modifié.

Le TTFB Hostinger/HCDN, les baselines visuelles et les signoffs humains restent indépendants de cette note et ne sont pas déclarés conformes.
