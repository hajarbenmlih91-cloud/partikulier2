# Smoke live logo localisé — candidat a58120c — 2026-08-26

## Résultat

Le harnais `scripts/verify-live-final30-smoke.mjs` a été renforcé pour vérifier explicitement le `href` du logo sur chaque archive localisée. Les exécutions live ont utilisé le staging `https://blanchedalmond-reindeer-376379.hostingersite.com` et les navigateurs Chromium, Firefox et WebKit.

| Navigateur | Statut global | Contrôles PASS | Contrôles BLOCKED | Logo FR | Logo EN | Logo AR |
|---|---|---:|---:|---|---|---|
| Chromium | BLOCKED dataset | 6 | 1 | `/fr/` | `/en/` | `/ar/` |
| Firefox | BLOCKED dataset | 6 | 1 | `/fr/` | `/en/` | `/ar/` |
| WebKit | BLOCKED dataset | 6 | 1 | `/fr/` | `/en/` | `/ar/` |

Dans les trois moteurs, les liens du logo répondent HTTP 200 et correspondent à la langue active. Le contrôle location est correctement classé `BLOCKED` : la page sélectionne `a-louer`, mais contient 21 cartes, 20 badges vente et 0 badge location. Aucun défaut de navigateur ou de responsive n’est déduit de ce blocage dataset.

## Limite de provenance

Le HTML public du staging produit les URLs localisées attendues, mais l’administration Hostinger n’a pas encore fourni une preuve du SHA réellement déployé. Cette note prouve le comportement HTTP observé et le test du harnais ; elle ne prouve pas que le staging exécute le commit `a58120c` plutôt qu’un code antérieur équivalent. Cette provenance doit être confirmée séparément par version/build/ZIP ou accès admin légitime.

## Fichiers et commandes

```bash
PK_BASE='https://blanchedalmond-reindeer-376379.hostingersite.com' \
PK_BROWSER=firefox \
PK_REPORT=/tmp/firefox.json \
node scripts/verify-live-final30-smoke.mjs
```

Le changement de code fonctionnel est dans le commit `a58120ccedfff7cd6850917465f5a0e82ed07962` sur la branche `automation/functional-release-2ff8e38`. Aucun CSS, template, carte, texte public ou structure visuelle n’a été modifié.
