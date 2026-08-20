# Preuve UI dynamique — Lot K — 6.15.0

Date de recette : 20 août 2026.

Le serveur WordPress local répond désormais en HTTP 200 après ajout du routeur `wp/router.php`, utilisé par `scripts/start.sh`. La page publique a été vérifiée par Playwright sur `/`, `/annonces/` et `/deposer-une-annonce/` : aucune erreur JavaScript, aucune 404 et HTML/a11y/SEO présents.

Une session navigateur réelle a été ouverte sur `http://localhost:8090/wp-admin/admin.php?page=pk-whatsapp-leads` avec le compte administrateur de sandbox. L’écran « Leads WhatsApp » est rendu avec le menu admin, quatre KPI, deux filtres select, recherche, bouton d’export CSV, table et contrôles de suivi par ligne.

Les fixtures dynamiques K contiennent 100 lignes métier. Les mesures WordPress de `scripts/test-backoffice-k.php` sont : 6 requêtes pour 1 ligne, 6 pour 20 lignes et 6 pour 100 lignes ; la limite `<=15` est respectée. Le contrôle de neutralisation de `=SUM(A1)` et la conservation d’une valeur normale passent.

Réserve d’infrastructure corrigée pendant la recette : le dépôt ne contenait pas le `router.php` requis par `start.sh`, ce qui produisait un HTTP 500 vide. Le fichier a été ajouté au projet et le test HTTP a ensuite répondu `HTTP=200`, taille 49867 octets.

Le premier audit Playwright effectué avant la correction du routeur est invalide et doit être écarté. Le nouvel audit public post-correction est enregistré dans `rapport-audit-public-6.15.0.json`.

## Export CSV intermédiaire

La première tentative par `fetch` a utilisé `form.action`, mais le formulaire possède aussi un champ `<input name="action">`, ce qui résout `form.action` vers l’élément DOM au lieu de l’URL. La requête a donc ciblé `/wp-admin/[object HTMLInputElement]` et ne constitue pas une preuve d’export. Cette tentative est explicitement écartée. La prochaine exécution utilise `form.getAttribute('action')`, puis vérifie le statut, les headers et le contenu CSV.

## Export CSV authentifié — preuve finale

Le POST réel vers `/wp-admin/admin-post.php` a été exécuté depuis la session navigateur administrateur avec le nonce rendu par WordPress.

Résultat : `HTTP 200`, `Content-Type: text/csv; charset=UTF-8`, disposition attachment, BOM `[239, 187, 191]`. Une fixture dont le titre était `=SUM(A1)` a été exportée sous la forme `'=SUM(A1)`. Le contrôle prouve à la fois le BOM Excel et la neutralisation effective d’une formule hostile dans le fichier réellement produit par `handle_export()`.
