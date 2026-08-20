# Rattrapage des traductions · v6.8.1

Script livré : `tests/traduire-annonces.php`. Il crée les versions arabe et anglaise de
vos annonces déjà publiées, et les relie entre elles pour Google.

---

## Comment il travaille

Vos annonces existantes n'ont pas été déposées via le nouveau formulaire : elles n'ont
donc pas de « réponses de formulaire » à traduire.

Le script **reconstruit ces données depuis la base** :

| Information | Reconstruite depuis |
|---|---|
| Type de bien | taxonomie `es_type` |
| Vendre / Louer | nom du terme `es_category` |
| Ville et quartier | `es_location` (le terme enfant est le quartier) |
| Surface, prix, pièces | metas Estatik |
| Étage, garage, ascenseur, terrasse | metas du thème |

Puis il rédige le titre, la description et la meta description dans chaque langue.

---

## Mode d'emploi

### Étape 1 — Simulation (ne modifie rien)

```
https://VOTRE-SITE.com/wp-content/themes/partikulier/tests/traduire-annonces.php
```

Vous verrez, pour chaque annonce, ce qui serait créé :

```
[6] Appartement lumineux 3 pièces
      lieu    : Casablanca
      manque  : en, ar
      en -> Apartment of 72 sqm for sale in Casablanca
      ar -> شقة بمساحة 72 م² للبيع في الدار البيضاء
```

### Étape 2 — Sauvegardez la base

hPanel › phpMyAdmin › Exporter. Le script crée des dizaines de pages : une sauvegarde
reste la seule marche arrière propre.

### Étape 3 — Application

```
https://VOTRE-SITE.com/wp-content/themes/partikulier/tests/traduire-annonces.php?appliquer=1
```

Il crée les traductions, les relie via Polylang, aligne leur statut sur l'annonce
d'origine, régénère les permaliens et vide le cache.

---

## Résultats du test

Sur 15 annonces, dont 9 nécessitant un traitement :

| Contrôle | Résultat |
|---|---|
| Simulation | 6 annonces détectées, **0 écriture** (15 avant, 15 après) |
| Application | **12 traductions créées, 0 échec** |
| URL des 3 langues | **200** sur toutes |
| Page arabe | H1, meta et contenu en arabe, `lang="ar"`, `dir="rtl"` |
| hreflang | **4 balises** par page, sans doublon |
| **Relance du script** | **0 doublon** — les annonces complètes sont ignorées |

Ce dernier point compte : vous pouvez le relancer sans risque après avoir ajouté de
nouvelles annonces.

---

## Ce qu'il ne fait pas — volontairement

**Il ne touche pas à vos textes français.** Les titres et descriptions que vous avez
écrits à la main restent intacts. Seules les versions AR et EN sont créées.

**Il ignore les annonces déjà traduites.** Si vous avez traduit certaines pages
manuellement dans Polylang, elles sont laissées telles quelles.

**Il ne recopie aucun mot personnel**, puisque vos anciennes annonces n'en ont pas.

---

## Un point à surveiller

Si une annonce n'a **aucune ville rattachée**, le script le signale :

```
ATTENTION : aucune ville rattachée, texte moins riche.
```

La traduction est quand même créée, mais sans le lieu — donc avec un intérêt SEO réduit.
C'est lié au problème de taxonomies mélangées de votre site. **Lancez d'abord
`reparer-taxonomies.php`**, puis ce script : vous obtiendrez de bien meilleurs textes.

Ordre recommandé :

1. `reparer-taxonomies.php` — remettre les villes dans `es_location`
2. Réassigner « À vendre » / « À louer » à vos annonces
3. `traduire-annonces.php` — créer les versions AR et EN

---

## Après l'opération

Vérifiez deux ou trois annonces en arabe et en anglais, puis **supprimez les scripts du
serveur** (`tests/traduire-annonces.php`, `tests/reparer-taxonomies.php`,
`tests/diagnostic-site.php`). Le thème fonctionne sans eux.

Installez d'abord **`partikulier-6.8.1.zip`**.
