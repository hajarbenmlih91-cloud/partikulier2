# Rapport de qualification — Partikulier 6.17.0

**Date de la recette :** 21 août 2026. **Base :** thème Partikulier 6.17.0, sandbox WordPress réelle avec Polylang 3.8.7, PHP 8.4 de recette, navigateur mobile Chromium à 390 px. Les preuves brutes sont archivées dans `rapport-6.17.0/preuves/`.

## Verdict exécutif

La validation technique dynamique du périmètre i18n/UX est **verte**. Les parcours AR et EN, la détection navigateur, l’exemption des robots, le cache de la racine, les pages localisées, le RTL, la police arabe, le formulaire de dépôt et les contrôles SEO ont été rejoués sur la sandbox active. Le package est intègre et son hash est archivé.

Je ne prononce toutefois pas une signature linguistique définitive « conforme à 100 % au CdC » tant que les **deux attestations de relecture native AR et EN** exigées au §5 et au §6 du CdC v1.5 ne sont pas jointes. La recette technique ne remplace pas cette validation humaine. Les baselines visuelles dédiées AR/EN et la preuve de soumission métier complète doivent également être conservées comme livrables séparés si elles sont requises pour le sign-off de production.

## Corrections livrées pendant cette recette

La protection SEO précoce exempte Googlebot, Bingbot, YandexBot, DuckDuckBot, Applebot et `facebookexternalhit` de la redirection navigateur Polylang. Les réponses de la racine, y compris le 302 de détection et le 200 robot, portent `Cache-Control: private, no-store` et `Vary: Accept-Language, Cookie`.

Le chargement gettext a été corrigé pour recharger explicitement `ar.mo` ou `en_US.mo` après la définition de la langue active par Polylang. Les chaînes critiques du formulaire de dépôt ont été ajoutées aux tables de compilation. Le cache des URLs localisées annonce désormais `public, max-age=43200` et `Vary: Accept-Encoding`, tout en conservant l’exclusion des réponses qui posent un cookie.

Les alternates hreflang du thème ont été dédoublonnés : Polylang émet les trois langues et le thème complète `x-default`. Les variantes du mot personnel conservent `lang="fr"` et portent un intitulé localisé. Les pages structurelles et légales du Lot 4bis sont présentes et reliées en FR/EN/AR ; le slug légal livré est `conditions-utilisation`.

## Résultats dynamiques

| Contrôle | Résultat | Preuve |
|---|---:|---|
| Détection humaine AR/EN/FR et cookie prioritaire | PASS | `preuves/browser-detection.json` |
| Exemption des six familles de robots | PASS | `preuves/browser-detection.json` |
| Racine `private, no-store` et `Vary` | PASS | `preuves/browser-detection.json` |
| Parcours mobile AR : accueil, archive, fiche, dépôt | PASS | `preuves/journey-ar-en.json` |
| Parcours mobile EN : accueil, archive, fiche, dépôt | PASS | `preuves/journey-ar-en.json` |
| Formulaire : trois champs libres annotés, titre et extra présents | PASS | `preuves/journey-ar-en.json`, inspection HTML |
| RTL AR | PASS | `preuves/journey-ar-en.json` |
| Noto Sans Arabic chargé sur AR uniquement | PASS | `preuves/fonts.json` |
| Aucun chargement de police arabe sur FR/EN | PASS | `preuves/fonts.json` |
| HTML lang, dir, JSON-LD `inLanguage`, OG locale | PASS | `preuves/seo.json` |
| Hreflang : FR/EN/AR + `x-default`, sans doublon | PASS | `preuves/seo.json` |
| Cache localisé : deux HIT publics après échauffement | PASS | `preuves/seo.json` |
| Pages structurelles et légales trilingues reliées | PASS | `preuves/pages-4bis.json` |
| PHP lint : 66 fichiers, aucune erreur | PASS | `preuves/package.log` |
| Contrôle qualité du thème | PASS | `preuves/package.log` |

## Artefact livrable

| Élément | Valeur |
|---|---|
| Archive | `partikulier-6.17.0.zip` |
| Taille | 914 Ko environ |
| SHA-256 | `3427c4eaaa4188784b3105ba299a24b24b41ed96fe2348487cdb4d32e6363b04` |
| Versions | `style.css`, `functions.php`, `package.json`, `readme.txt` alignés sur `6.17.0` |
| Intégrité ZIP | `unzip -t` PASS |

## Réserves de sign-off

La sortie est **techniquement qualifiée**, mais la signature finale de production reste conditionnée à la réception des attestations de relecture native arabe et anglaise prévues explicitement par le CdC. Il faut également conserver une preuve de validation visuelle des baselines AR/EN et, si le client exige la preuve métier complète, un test de soumission de dépôt jusqu’à l’état de modération avec fixture isolée et sans notification externe réelle.

Le contrôle SEO utilise des fixtures dont les meta descriptions sont des contenus de test (`Security fixture` et `Manual test`). Elles prouvent la structure, la locale et l’intégrité des balises ; elles ne constituent pas une relecture éditoriale des textes métier. Les textes éditoriaux de la page d’accueil doivent rester remplis et relus dans la page Personnalisation, conformément au Lot 4.

> **Conclusion :** 6.17.0 est prête pour revue et intégration avec une recette technique verte. Elle ne doit pas être annoncée comme signée à 100 % tant que les attestations linguistiques natives et les éventuelles preuves visuelles finales ne sont pas jointes.

## Références internes

- CdC gelé : `cahier-des-charges-partikulier-6.17-i18n(3).md`.
- Preuves JSON : `rapport-6.17.0/preuves/`.
- Empreinte package : `rapport-6.17.0/package.sha256`.
