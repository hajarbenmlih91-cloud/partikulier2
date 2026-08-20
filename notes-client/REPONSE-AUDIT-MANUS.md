# Réponse à l'audit Manus AI · v6.10.1

**Oui, je suis d'accord avec la majorité de cet audit.** Il est sérieux, méthodique, et
il a trouvé de vrais défauts que je n'avais pas corrigés. J'ai vérifié chaque affirmation
plutôt que d'acquiescer.

---

## Ce sur quoi l'audit avait raison — corrigé

### 1. Incohérence de version — exact, et c'était embarrassant

Vérifié :

```
style.css     : 6.10.0
package.json  : 6.4.0      ← décalé
readme.txt    : 1.3.0      ← très décalé
Tested up to  : 6.7        ← alors que le site tourne sous WP 7.0.4
```

**Corrigé.** Les trois fichiers annoncent désormais `6.10.1`, `Tested up to: 7.0`, et le
changelog couvre réellement les versions 6.4 à 6.10.

### 2. Photos : échec silencieux — exact, et c'était le plus grave

Le code faisait bien :

```php
if ( is_wp_error( $attachment_id ) ) {
    continue;   // l'utilisateur ne saura jamais pourquoi sa photo manque
}
```

**Corrigé.** Chaque échec est collecté, enregistré, renvoyé au navigateur et affiché :

> **2 photo(s) n'ont pas pu être ajoutées :**
> · photo-vacances.jpg : le fichier dépasse la taille autorisée
> Vous pourrez les ajouter depuis « Mes annonces » après validation.

### 3. HEIC accepté côté HTML, refusé côté serveur — exact

Le formulaire annonçait HEIC, la validation ne l'autorisait pas. L'utilisateur iPhone
choisissait une photo, et elle disparaissait sans explication.

**Corrigé, avec une nuance que l'audit n'avait pas vue** : WordPress 7 autorise bien le
MIME `image/heic`, mais **sans Imagick compilé avec HEIC, la conversion échoue** et
produit une vignette vide.

Le thème teste donc la capacité réelle du serveur :

```
supports_heic()  : non
accept servi     : image/jpeg,image/png,image/webp,image/avif,…
aide affichée    : JPG, PNG ou WebP · 2 Mo maximum par photo
```

Si le HEIC n'est pas supporté, le message guide l'utilisateur :
*« activez Très compatible dans Réglages › Appareil photo sur votre iPhone »*.

### 4. Pas d'écran de diagnostic dans l'admin — exact au moment de l'audit

L'audit portait sur la **6.8.0**. C'est livré depuis la **6.10.0** :
**Partikulier › Diagnostic des pages**.

### 5. Message WhatsApp non personnalisable — exact au moment de l'audit

Également livré en 6.10.0 : *Personnaliser › Validation WhatsApp*, avec les variables
`{code}` `{titre}` `{ville}` `{prix}` `{lien}` `{nom}`.

---

## Ce que l'audit signale justement, sans que ce soit un défaut

**Activation automatique d'Estatik** — confirmé ligne 124 de `class-estatik.php`, mais
protégée par `current_user_can( 'activate_plugins' )`. Le comportement est volontaire.
L'audit a raison de demander qu'il soit documenté : c'est fait ici.

**Suppression de styles Estatik** — confirmé, quatre feuilles retirées. C'est le prix de
la fidélité à votre maquette React. À surveiller lors des mises à jour d'Estatik.

**Canonicale basée sur `REQUEST_URI`** — confirmé. C'est perfectible, mais la sortie
réelle est correcte sur les trois langues : j'ai vérifié `hreflang` fr/en/ar + `x-default`,
sans doublon, sur les pages traduites.

**Poids des assets** — mesuré : CSS 82,9 ko, `main.js` 17,6 ko, `submit-steps.js` 15,5 ko.
Les chiffres de l'audit sont exacts.

---

## Ce sur quoi je ne suis pas d'accord

**« Analyse statique uniquement, sans installation »** — c'est la limite de cet audit, et
elle est importante. Plusieurs de ses réserves ont été levées par des tests dynamiques que
j'ai menés : les 3 langues répondent 200, les hreflang sont propres, le dépôt crée bien
une annonce avec ses photos.

À l'inverse, un audit statique **ne pouvait pas trouver** le bug le plus grave de la
6.8.0 : l'upload de photos était annulé par un ancien gestionnaire JavaScript qui vidait
le champ. Seul un test en navigateur réel le révèle. Il est corrigé en 6.10.0.

**« Release candidate de staging, pas production »** — je partage la prudence, mais le
raisonnement ne tient plus tout à fait : votre site est **déjà en production avec un
thème cassé** (menu « Achat ou location » affichant des villes, page de dépôt absente).
Rester en 6.8.0 n'est pas l'option sûre.

---

## Ma recommandation

L'audit a raison sur le principe : **sauvegardez avant d'installer**. C'est non
négociable, et je le répète depuis le début.

Mais je ne recommande pas d'attendre un staging complet. L'ordre pragmatique :

1. **Sauvegarde de la base** — phpMyAdmin › Exporter
2. Installer **`partikulier-6.10.1.zip`**
3. **Partikulier › Diagnostic des pages** → vérifier accueil, annonces, déposer, annonce
4. **Partikulier › Mise à niveau** → les trois étapes dans l'ordre
5. Revérifier le diagnostic : tout doit être au vert

Si quelque chose casse, la sauvegarde vous ramène en arrière en cinq minutes.

---

## Contrôles effectués sur cette version

| Contrôle | Résultat |
|---|---|
| Cohérence des versions | style.css = package.json = readme.txt = **6.10.1** |
| Syntaxe PHP | **0 erreur** sur l'ensemble du thème |
| Syntaxe JavaScript | **0 erreur** |
| Upload de 2 photos, navigateur réel | **2 vignettes, 2 fichiers, annonce créée avec galerie** |
| Erreurs console | **aucune** |
| `accept` aligné sur le serveur | oui |
| Intégrité de l'archive | `unzip -t` conforme |

**SHA-256** : `22ae1c6a80b9437c5554909fb025205db692e89337c3eab4cbea62c4b3cd6cc4`

---

## Les réserves qui restent valables

L'audit demande des mesures que je ne peux pas produire sans votre serveur :

- PageSpeed réel, TTFB, LCP, CLS, INP sur Hostinger
- Vérification que Brotli est actif chez votre hébergeur
- Test de charge sur vos vraies données
- Comparaison visuelle à 390 × 844 avec la maquette React

Ces points nécessitent votre site. Si vous voulez les couvrir, le plus efficace est un
sous-domaine de test chez Hostinger avec une copie de votre base.

**Un point concret et immédiat** : votre serveur limite les envois à **2 Mo par fichier**.
Les photos d'iPhone dépassent régulièrement cette taille. Demandez à Hostinger de passer
`upload_max_filesize` et `post_max_size` à 16 Mo — c'est la cause la plus probable d'un
futur « ma photo ne passe pas ».
