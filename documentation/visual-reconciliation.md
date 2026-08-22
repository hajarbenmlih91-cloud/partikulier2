# Réconciliation Visuelle v6.17.9

## Sélecteurs de stabilisation identifiés
- `.es-listings-list`, `.es-listing-card` : Listes d'annonces Estatik.
- `.pk-editorial-type small`, `.pk-editorial-place-list small` : Compteurs d'annonces dynamiques.
- `.query-monitor-nav`, `#wpadminbar` : Barres d'outils admin.
- `.pk-last-seen` : Dates de visite.

## État des tests
- **FR Accueil Desktop** : OK (0.00%)
- **EN Accueil Mobile** : OK (0.00%)
- **AR Accueil Desktop** : OK (0.00%)
- **Autres Archives/Pages** : OK (0.00%)
- **Blocages restants** : 3 vues (FR mobile, EN desktop, AR mobile) présentent encore des écarts de 3-5% malgré le masquage.

## Action corrective
Le masquage CSS `visibility: hidden` préserve l'espace occupé. Si les éléments dynamiques provoquent des changements de hauteur (layout shift), il faut utiliser `display: none` ou stabiliser les hauteurs des conteneurs.
