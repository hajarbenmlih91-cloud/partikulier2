# Dry-run nettoyage staging — 2026-08-26

Aucune suppression n’est exécutée. L’éditeur WordPress a confirmé pour l’ID **155** : titre `Appartement lumineux 3 pièces`, type `properties`, langue `fr`, statut `Publié`, visibilité `Publique`, ville sélectionnée `Casablanca`. Les liens Polylang indiquent une traduction anglaise ID **157** et une traduction arabe ID **159**. L’URL publique FR de l’ID 155 est `/fr/annonce/casablanca/appartement-lumineux-3-pieces/`.

Le lien de corbeille de l’ID 155 est présent dans l’interface mais n’a pas été suivi. La suppression de 155 aurait donc un impact public et casserait vraisemblablement ses traductions liées ; toute suppression reste soumise à confirmation explicite après inventaire complet.

IDs encore à inventorier : propriétés 153, 154, 156, 157, 158, 159 et contenus WordPress 21, 22, 23. Les résultats doivent préciser existence, type, statut, langue, relations Polylang, visibilité publique et impact.

## ID 157

L’éditeur confirme `Maison contemporaine avec jardin`, type `properties`, langue `en`, statut `Publié`, visibilité `Publique`. La traduction française est l’ID 155 et la traduction arabe l’ID 159. Le lien de corbeille existe mais n’a pas été suivi.

## ID 159

L’éditeur confirme `Appartement T2 vue mer avec balcon`, type `properties`, langue `ar`, statut `Publié`, visibilité `Publique`. Les traductions liées sont l’ID 157 en anglais et l’ID 155 en français. La fiche publique stable est `/ar/annonce/marrakech/appartement-t2-vue-mer-avec-balcon/`. Le lien de corbeille existe mais n’a pas été suivi.

## ID 153

L’éditeur confirme l’existence d’une propriété `properties` en langue `fr`, statut **Brouillon**, titre vide, visibilité affichée **Publique**, avec liens de création de traductions mais aucune traduction existante détectée. Le lien de corbeille existe mais n’a pas été suivi. Ce brouillon doit être traité comme objet potentiellement orphelin, jamais supprimé sans confirmation et vérification de l’impact.

## ID 154

L’éditeur confirme l’existence d’une propriété `properties` en langue `fr`, statut **Brouillon**, titre vide, visibilité affichée **Publique**, sans traduction existante détectée. Le lien de corbeille existe mais n’a pas été suivi. Comme l’ID 153, l’objet doit être considéré comme potentiellement orphelin jusqu’à vérification des métadonnées et de l’historique.

## IDs 156 et 158

Les URLs d’édition directes des IDs **156** et **158** renvoient toutes deux l’erreur WordPress : **« Vous tentez de modifier un contenu qui n’existe pas. Peut-être a-t-il été supprimé ? »**. Ils sont donc absents du contenu éditable actuel et aucune action de suppression n’a été tentée.

## ID WordPress 21

L’éditeur WordPress identifie l’ID **21** comme une page intitulée `Déposer une annonce`. Son lien public FR est `/fr/deposer/`. Les champs de statut/visibilité n’étaient pas rendus dans cette vue partiellement chargée et aucun lien de corbeille n’a été suivi. Cette page est fonctionnelle et son ID ne doit pas être supprimé sans justification séparée.

## IDs WordPress 22 et 23

L’ID **22** renvoie `Désolé, vous n’avez pas l’autorisation de modifier les entrées dans ce type de publication.` depuis `post.php`, sans titre ni statut lisible. Il faut donc l’identifier via une liste adaptée au type de publication avant toute décision.

L’ID **23** renvoie `Vous tentez de modifier un contenu qui n’existe pas. Peut-être a-t-il été supprimé ?`. Aucune suppression n’a été tentée pour l’un ou l’autre.

**Décision dry-run :** l’inventaire est suffisant pour conclure que 21 est une page fonctionnelle, 22 nécessite une identification par type/capacité, 23/156/158 sont absents de l’éditeur, 153/154 sont des brouillons vides et 155/157/159 forment une chaîne FR/EN/AR publique. Aucun nettoyage ne sera exécuté sans confirmation explicite et plan d’impact.
