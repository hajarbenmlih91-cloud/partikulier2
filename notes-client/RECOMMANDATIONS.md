# Partikulier — recommandations techniques et design

Ce que je ferais si je reprenais ce projet en tant que designer produit + dev senior.
Classé par retour sur investissement, pas par difficulté.

---

## Fait dans la v6.1.0 : le logo en texte

Vous aviez raison, et c'est la bonne pratique. Un logo en PNG, c'est :

| Problème | Conséquence |
|---|---|
| Rastérisé | Flou sur écrans Retina, sauf à livrer 2× et 3× |
| Fichier binaire | Une requête HTTP de plus, non compressible par Gzip |
| Texte en image | Invisible pour Google, illisible pour un lecteur d'écran |
| Couleurs figées | Impossible d'adapter au mode sombre ou à une charte saisonnière |
| Non traduisible | Un logo par langue si le nom change |

**Ce qui est livré maintenant** : la marque est du HTML stylé en CSS.

- 0 requête réseau (mesuré : aucune requête contenant « logo »)
- net à n'importe quel zoom — vérifié par capture à 3× de densité
- sélectionnable, indexable, avec `aria-label` explicite pour les lecteurs d'écran
- taille fluide `clamp(1.05rem, .95rem + .4vw, 1.32rem)` : s'adapte sans media query
- les 3 PNG sont supprimés du thème (−22 Ko)

**Nettoyage associé** : les règles CSS de la marque étaient éparpillées en **5 endroits**
(lignes 184, 540, 555, 718, 1161) avec des valeurs contradictoires. Je les ai fusionnées en
un seul bloc. Les références mortes à `logo.png` dans `class-customization.php` et
`class-jsonld.php` sont corrigées — la seconde aurait produit une URL 404 dans vos données
structurées Google.

> Si vous voulez un vrai logo dessiné plus tard, la bonne réponse est un **SVG inline**
> (vectoriel, net partout, colorable en CSS, ~1 Ko) — jamais un PNG.

---

## Ce que je corrigerais ensuite, par ordre de priorité

### 1. Le CSS est en couches sédimentaires — c'est le vrai problème

**Constat mesuré** : 1 353 lignes, 725 règles, 76 Ko, et surtout **56 sélecteurs sur 251
sont définis au moins deux fois**. `.pk-card-price` et `.pk-card-badge` le sont quatre fois.

Cette structure vient de l'historique : chaque passe d'alignement sur la maquette React a
ajouté un bloc de surcharge en fin de fichier au lieu de modifier l'existant. Résultat
concret que vous avez vécu : quand j'ai voulu aligner à gauche les cartes « Qui publie
l'annonce ? », trois blocs `.pk-role-btn` se contredisaient et j'ai dû recourir à
`!important` (il en reste 3 dans le fichier).

**Ce que je ferais** — une passe de consolidation, 2 à 3 heures :
- fusionner les 56 sélecteurs dupliqués, en gardant la valeur finale effective
- supprimer les 3 `!important` devenus inutiles une fois la cascade propre
- réorganiser en sections explicites : tokens → base → composants → pages → utilitaires
- adopter `@layer` pour que les surcharges futures soient intentionnelles, pas accidentelles

**Bénéfice** : fichier réduit d'environ 30 %, et surtout la prochaine modification prend
5 minutes au lieu d'une heure de débogage de cascade.

**Risque** : c'est une opération à faire d'un bloc, avec relecture visuelle de chaque page.
Je ne l'ai pas lancée sans votre accord parce qu'elle touche tout le site.

### 2. Découper `style.css` en fichiers, et n'en charger que le nécessaire

Aujourd'hui les 76 Ko sont chargés sur **toutes** les pages, y compris le CSS du formulaire
de dépôt quand un visiteur lit une annonce.

**Ce que je ferais** : `base.css` partout, puis `archive.css`, `single.css`, `submit.css`
chargés conditionnellement via `is_page_template()` / `is_singular()`. Gain estimé :
40 à 50 % de CSS en moins sur les pages de contenu, ce qui améliore directement le LCP.

### 3. Fiabiliser la détection des gabarits

Le bug de la page « Déposer » vide venait de gabarits rangés dans `templates/`, que
WordPress n'inspecte pas. J'ai ajouté un module de rattrapage par slug — ça marche, mais
c'est un pansement.

**La solution propre** : déplacer `page-deposer-annonce.php` et `page-mes-annonces.php` à
la racine du thème, comme WordPress l'attend. Ce sont deux `git mv` et une mise à jour des
chemins. Le module de rattrapage peut rester en filet de sécurité.

### 4. Mettre le thème sous tests automatiques

Neuf bugs ont été trouvés par vos captures d'écran, aucun par mes vérifications. C'est
l'enseignement le plus important de ces échanges.

**Ce que je mettrais en place** :
- **PHPCS** avec le standard WordPress — attrape les erreurs d'échappement et de nommage
- **Tests de non-régression visuelle** (Playwright + comparaison d'images) sur 6 pages clés,
  en 1440 px et 390 px : toute différence de plus de 0,1 % fait échouer le test
- **Un test fonctionnel par parcours critique** : déposer une annonce, rechercher, mettre
  en favori

Sans ça, chaque correction risque d'en casser une autre — c'est exactement ce qui s'est
produit quand mon intégration du logo a introduit le `">` visible dans le header.

### 5. Points de design que je changerais

- **Densité verticale** : vos pages respirent moins que la maquette React parce que les
  espacements sont écrits en dur, valeur par valeur. Je passerais à une échelle d'espacement
  (`--pk-space-1` à `--pk-space-8`) appliquée partout. C'est ce qui donne l'impression de
  rigueur d'un site professionnel.
- **États de chargement** : les cartes apparaissent d'un coup. Des squelettes de chargement
  rendent la navigation nettement plus fluide à la perception.
- **Focus clavier** : présent sur certains composants, absent sur d'autres. À unifier — c'est
  une exigence d'accessibilité, et de plus en plus une obligation légale en Europe.
- **Le menu de secours** : limité à 3 entrées codées en dur. Avec Polylang, créez un vrai
  menu par langue dans *Apparence › Menus*, sinon vos visiteurs anglophones et arabophones
  verront un menu en français.

### 6. Performance — à mesurer avant d'agir

Je n'ai pas fait tourner Lighthouse sur votre site réel, donc je ne donnerai pas de
chiffres inventés. Les pistes probables, par ordre d'impact habituel :
- l'image du hero (`hero.jpg`) doit être en AVIF/WebP avec `fetchpriority="high"`
- la police DM Sans est auto-hébergée : bien. Ajouter `<link rel="preload">` sur le woff2.
- le plugin Estatik charge jQuery, Select2, Slick et Magnific Popup **sur toutes les pages**,
  y compris là où rien ne les utilise. Les retirer sélectivement avec `wp_dequeue_script()`
  est probablement le plus gros gain disponible, sans toucher au thème.

---

## Ce que je ne ferais pas

- **Réécrire le thème en blocs (FSE)**. À la mode, mais votre thème classique fonctionne et
  la migration coûterait des semaines pour un bénéfice utilisateur nul.
- **Ajouter un framework CSS** (Tailwind, Bootstrap). Le problème n'est pas l'absence
  d'outil, c'est l'accumulation de couches. En ajouter une de plus l'aggraverait.
- **Chercher le « 100 % de ressemblance »** avec la maquette React. Vous y êtes
  fonctionnellement. Les écarts restants tiennent au volume de contenu réel, pas au code.

---

## Si je devais choisir trois choses

1. **Consolider le CSS** (points 1 et 2) — c'est ce qui débloque tout le reste
2. **Mettre en place les tests visuels** (point 4) — pour arrêter de découvrir les bugs par capture
3. **Alléger Estatik** (point 6) — le meilleur rapport gain/effort sur la performance

Le reste est du confort. Ces trois-là changent la façon dont le projet vieillit.
