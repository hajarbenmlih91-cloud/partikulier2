# Requalification senior — Partikulier 6.17.0

**Date :** 21 août 2026  
**Objet :** vérification de la livraison depuis l’artefact bundle, et non depuis le répertoire source seul.

## Verdict

La release est **techniquement requalifiée sur le chemin de livraison bundle**. Le ZIP principal contient désormais le thème, le mu-plugin SEO précoce et un guide d’installation. La sandbox de validation a été alimentée par extraction du ZIP, puis les recettes HTTP, Playwright, police, SEO, provisioning Polylang et qualité ont été rejouées.

Le sign-off contractuel final reste **conditionnel** tant que les deux attestations de relecture native AR et EN prévues par le CdC v1.5 ne sont pas jointes. La qualification technique ne remplace pas cette exigence linguistique externe.

## Artefacts

| Élément | Valeur |
|---|---|
| Bundle principal | `partikulier-6.17.0.zip` |
| SHA-256 | `dc97e55a148056c770a7c7ca8afdcdb79ed4d9ab67864d037872f29ab6f3878c` |
| Archive thème complémentaire | `partikulier-6.17.0-theme.zip` |
| Contenu obligatoire vérifié | `theme/`, `mu-plugins/partikulier-early-seo.php`, `theme/languages/ar.mo`, `theme/languages/en_US.mo`, `INSTALL.md` |

Le mu-plugin est maintenant livré dans l’artefact principal. Il ne faut pas installer uniquement l’archive thème si l’on veut bénéficier de l’exemption robots exécutée avant Polylang. Les deux TTF arabes inutilisés ont été retirés ; le bundle contient uniquement les WOFF2 déclarés par le CSS.

## Corrections appliquées

Le provisioning Polylang force désormais `browser=1` après les opérations de création et de liaison, vérifie immédiatement la valeur, et réapplique la valeur à l’arrêt si une écriture tardive la supprimait. La preuve finale contient `"browser":1`.

Le registre public a été corrigé de `France` vers `Maroc`, avec replis `Morocco` et `المغرب`. Les chaînes calculées de la carte principale, de la fiche fallback et du gabarit Estatik d’archive utilisent maintenant gettext et les pluriels WordPress. Le footer sert également `Maroc`.

Le sélecteur de langue Lot 5 utilise désormais des SVG inline pour le Maroc, la France et les États-Unis, avec code et nom natif. Sur la page AR, quatre occurrences SVG `pk-flag` ont été observées et l’ancien globe n’a pas été détecté. Aucune image externe n’est utilisée pour ces drapeaux.

Le garde-fou R6 est branché dans `scripts/check.sh`. Il possède un fichier d’exceptions versionné et un test négatif qui injecte `3 chambres ou plus` dans une copie temporaire et exige l’échec du contrôle. La recette SEO a également été corrigée pour découvrir le post type Estatik `properties` et la famille Polylang FR/EN/AR dynamiquement, sans slug historique ; elle refuse désormais toute famille absente ou toute réponse autre que 200 avant analyse.

## Recettes exécutées depuis le bundle extrait

| Recette | Résultat |
|---|---|
| Détection navigateur, cookie, robots et headers root | PASS |
| Parcours Playwright mobile AR/EN à 390 px | PASS |
| Contrôle police Noto Sans Arabic | PASS |
| SEO hreflang, JSON-LD, meta et cache localisé, famille découverte dynamiquement | PASS |
| Provisioning Polylang avec assertion browser | PASS |
| `check.sh` | PASS |
| Lint PHP | 66 fichiers, aucune erreur |
| Lint JavaScript | 2 fichiers, aucune erreur |
| R6 positif | PASS |
| R6 négatif par injection | PASS |

La recette HTTP a confirmé `private, no-store` sur le 302 de détection et sur le 200 de la racine, avec `Vary: Accept-Language, Cookie`. Les robots Googlebot, Bingbot, YandexBot, DuckDuckBot, Applebot et Facebook External Hit ont reçu un 200 sans redirection. Les variantes localisées conservent leur politique publique de cache.

Les parcours mobiles AR et EN ont confirmé l’accueil, l’archive, une fiche réelle et le dépôt. Trois champs libres ont été détectés sur le dépôt dans chaque langue. Les fiches ont fourni quatre alternates hreflang et les valeurs de langue attendues.

## Réserves de sign-off

La relecture native arabe et la relecture native anglaise restent obligatoires selon le CdC v1.5. Elles doivent être jointes au rapport avant une déclaration de conformité contractuelle définitive.

Le contenu éditorial de personnalisation et les textes juridiques définitifs restent hors périmètre de génération technique et doivent être validés par le client ou un prestataire compétent. Le bundle est techniquement livrable ; ces attestations et validations éditoriales sont des conditions de signature, pas des défauts masqués par la recette automatisée.

## Conclusion

Le défaut critique identifié par l’audit — mu-plugin présent dans la sandbox mais absent de l’archive — est corrigé et contrôlé dans le ZIP principal. La release peut être publiée comme **candidate techniquement requalifiée**, mais elle ne doit pas être déclarée **conforme à 100 % au CdC** avant réception des attestations natives AR/EN.
