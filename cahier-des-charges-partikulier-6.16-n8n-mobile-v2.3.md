# CAHIER DES CHARGES — Partikulier 6.16 « n8n / WhatsApp » — v2.3
## Lot I (durcissement automatisation) · K2 définitivement écarté

**Projet :** Partikulier.com — portail immobilier réservé aux particuliers (Maroc)
**Base :** thème **6.15.0 clôturé** — commit `384a2de`, zip SHA-256 `fb7dd8dffe6e9281c9d24171fcc6dc7812f89884034e106b15f80d1fe530d819`
**Date :** 21 août 2026 — **v2.3 FINALE — SIGNÉE**

**Changements v2.2 → v2.3 (acte de signature) :** ⑬ statut basculé `PROPOSÉ À SIGNATURE` → **`SIGNÉ — prêt à développer`** (validation humaine formalisée : revues croisées unanimes + approbation client, 21/08/2026) ; ⑭ mode `log` : **plafonnement des journaux d'échec de signature** (agrégation par `key_id`+heure, compteur au-delà de N entrées/heure) — un workflow mal configuré ou un porteur du secret ne peut pas inonder la table d'audit.

**Changements v2.1 → v2.2 :** ⑩ titre aligné sur le statut (la v2.1 titrait encore « v2 » — même faute que sur le CdC 6.15, détectée par le même contrôle) ; ⑪ tableau contractuel des modes `off/log/enforce` : **le secret partagé reste obligatoire dans les trois modes** ; ⑫ exigence d'entropie du secret (≥ 32 octets cryptographiques aléatoires, base64/hex, valeurs prévisibles refusées) + vecteurs de test signés avec une **clé explicitement non productive**.

**Changements v2 → v2.1 :** ⑦ « migration atomique » → **« migration idempotente, vérifiable et reprenable »** (`update_option`/`delete_option` ne sont pas transactionnels — ne pas promettre une atomicité non garantie) + ordre de lecture contractuel ; ⑧ limite explicite : **le stockage en base n'est PAS du chiffrement au repos**, la constante d'environnement est le mode recommandé en production ; ⑨ canonisation I-2 précisée (METHOD majuscule, PATH sans domaine ni query string, TIMESTAMP entier epoch UTC secondes, RAW_BODY octets exacts sans réencodage). Inventaire confirmé par audit dynamique live : mauvaise signature → `accepted` (HMAC absent, I-2 justifié) ; rejeu `event_id` → `duplicate:true` ; secret vide → 401.
**Statut :** **SIGNÉ — prêt à développer** · le démarrage reste conditionné au J0 (inventaire des secrets de production + vecteurs n8n)

**Changements v1.1 → v2 :**
① **I-3 corrigé — erreur factuelle de l'auditeur reconnue** : `UNIQUE KEY event_id` et la réponse `duplicate:true` existent déjà (`class-automation-bridge.php` l.45, l.102-105) ; le lot est requalifié en durcissement de la course SELECT-puis-INSERT ;
② I-1 réécrit : migration **atomique** (plus de triple règle contradictoire migrer/retirer/fallback), priorité à une constante `wp-config.php`, aucune commande de recette n'affiche un secret ;
③ I-2 réécrit : chaîne canonique signée + **timestamp anti-rejeu** + `key_id` + **rotation à double secret avec expiration** + garde-fou vérifiable « toutes les routes » ;
④ I-4 : `consent_text` = texte WhatsApp pur → `sanitize_textarea_field` (canal tranché) ;
⑤ recette : 7 scripts versionnés à code retour + **secret canari** ; ⑥ planning 2,5 j → **4-5 j**.

---

## 0. Règles héritées (non négociables)
1. **Aucun identifiant fantôme** — inventaire vérifié sur `384a2de` le 21/08/2026.
2. **Sign-off exécutable uniquement** : scripts versionnés, rapport JSON, code retour ≠ 0 en échec.
3. **Tests par le vrai chemin de production** — pas de fixtures qui fabriquent la donnée testée.
4. **Aucun secret dans le zip, dans git, dans stdout, dans les logs ni dans les rapports.**
5. Comparaisons avant/après : le « avant » est identifié par commit/hash contrôlé.

---

## 1. Inventaire VÉRIFIÉ (commit `384a2de`)

| Capacité | Localisation vérifiée | État |
|---|---|---|
| Route `POST /partikulier/v1/automation-event` | `class-automation-bridge.php` const ROUTE l.21 | ✅ |
| Routes 6.15 : `resend-accepted` (UUID, idempotence prouvée en recette 6.15) + annonces approuvées | `class-listing-approval.php` l.524/533, même `permission_callback` | ✅ |
| Secret partagé `hash_equals` + **repli `Authorization: Bearer`** ; **secret vide ⇒ refus systématique (401)** — propriété de sécurité, documentée ici comme telle | `class-automation-bridge.php` l.73-83 | ✅ |
| Types en liste blanche (`whatsapp_inbound`, `whatsapp_status`, `payment_status`), sources `n8n`/`payment_provider` | l.95-96 | ✅ |
| **`UNIQUE KEY event_id` déjà en place** + réponse `200 {duplicate:true}` au rejeu | l.45, l.102-105 | ✅ **corrigé en v2 — la v1.1 affirmait le contraire à tort** |
| ⚠️ Doublon détecté par **SELECT-puis-INSERT** (course possible sous concurrence) ; colonne `source` existante mais **hors** de l'unicité | l.102-115, l.40 | 🔧 objet du I-3 v2 |
| Quota 2/jour transactionnel (`FOR UPDATE`), STOP/opt-out, envoi identifiants | `class-buyer-qualification.php` l.20, l.235-268 ; `class-listing-approval.php` | ✅ intouchables |
| Secrets **en clair** dans `pk_theme_options` (autoload) | `class-settings.php` l.20, l.143-148 | 🔧 objet du I-1 |
| Docs opérateur | `theme/docs/whatsapp-n8n-setup.md`, `notes-client/VALIDATION-ET-N8N.md` | ✅ à mettre à jour |

---

## 2. Lot I — Spécifications v2

### I-1 — Secrets : constante d'abord, migration idempotente et reprenable [P1]
1. **Priorité à l'environnement** : si `PARTIKULIER_N8N_SECRET` (et `PARTIKULIER_N8N_WEBHOOK_URL`) sont définis dans `wp-config.php`, ils **priment** sur toute valeur en base ; l'écran admin affiche alors « configuré par l'environnement » sans champ éditable.
2. À défaut, option **`pk_n8n_settings`** non autoloadée — **qui n'est PAS du chiffrement au repos** : le secret reste lisible par tout administrateur WordPress et par la base ; c'est un repli d'hébergement, la constante d'environnement est le mode recommandé en production.
   **Migration idempotente, vérifiable et reprenable** (`update_option`/`delete_option` ne sont pas transactionnels — aucune atomicité n'est promise) : écrire la nouvelle option → vérifier son exploitabilité **sans exposer le secret** → poser l'état de migration → supprimer les anciennes clés → marquer `_pk_n8n_settings_migrated_at`. **Une relance reprend proprement selon l'état enregistré.**
   **Ordre de lecture contractuel** : constante d'environnement → `pk_n8n_settings` valide → anciennes clés **uniquement si la migration n'est pas marquée terminée** → **refus sûr (401) si aucun secret valide**.
3. Écran `Partikulier > Réglages n8n` : secrets masqués (`••••` + « Remplacer »), jamais réaffichés ; `esc_url_raw` https ; **secret généré aléatoirement, ≥ 32 octets cryptographiques (`random_bytes`), encodé base64 ou hex** — la longueur seule ne suffit pas, les valeurs humaines répétitives ou prévisibles sont refusées à l'enregistrement. Les **vecteurs de test publiés** (I-2.2) emploient une clé explicitement non productive, jamais une valeur réelle.
4. **Recette sans fuite** : les scripts testent présence/longueur/empreinte (`hash('sha256', $secret)` tronqué) — **jamais** `wp option get` du secret. Le contrôle « non autoloadée » teste la **sémantique** (l'option n'est pas dans le cache alloptions), pas la valeur littérale `'no'` (WordPress moderne utilise aussi `off`/`auto-off`).

### I-2 — Signature des routes entrantes : intégrité ET fraîcheur [P1, conditionné prod]
**Périmètre : toutes les routes d'automatisation** (`automation-event`, `resend-accepted`, annonces approuvées, futures).
1. **En-têtes contractuels** : `X-Partikulier-Timestamp` (epoch UTC), `X-Partikulier-Key-Id`, `X-Partikulier-Signature`.
2. **Chaîne canonique documentée et gelée** (le même octet-à-octet côté n8n et WP) :
   `METHOD + "\n" + PATH + "\n" + TIMESTAMP + "\n" + RAW_BODY` → `hash_hmac('sha256', …, secret_du_key_id)`, avec normalisation **contractuelle** :
   - `METHOD` : **majuscules** ;
   - `PATH` : **chemin URI seul** — sans domaine, sans query string ; le slash final est significatif et doit être identique des deux côtés ;
   - `TIMESTAMP` : **entier epoch UTC en secondes** ;
   - `RAW_BODY` : **octets exacts reçus** (`php://input`), **jamais** un JSON ré-encodé.
   Un test de vecteurs (payloads de référence signés, publiés dans la doc) valide l'identité des deux implémentations avant tout codage.
3. **Anti-rejeu** : timestamp hors fenêtre **±5 min** ⇒ refus ; format invalide ⇒ refus ; erreurs **génériques** (aucun oracle secret/timestamp) ; journalisation par identifiant de requête haché, jamais le secret. **En mode `log`, les échecs de signature sont plafonnés** : agrégation par `key_id` + fenêtre horaire, simple compteur au-delà du plafond — la table d'audit ne peut pas être inondée par un émetteur authentifié mal configuré.
4. **Modes `off → log → enforce`** (option `hmac_mode`) : déploiement en `log`, observation prod, bascule `enforce` ensuite. Interdit de livrer en `enforce` direct. Le contrat de signature est **testé manuellement avec le workflow n8n réel avant tout codage WP** (pré-requis J0).
   **Comportement contractuel des modes — le secret partagé historique reste obligatoire dans les trois** (aucun mode n'ouvre la route) :

   | Mode | Secret partagé | Signature HMAC |
   |---|---|---|
   | `off` | **obligatoire** | ignorée |
   | `log` | **obligatoire** | vérifiée et journalisée, non bloquante |
   | `enforce` | **obligatoire** | obligatoire et bloquante |
5. **Rotation à double secret** : `secret actif (key_id N)` + `secret précédent (key_id N-1)` accepté pendant une **fenêtre de chevauchement à expiration obligatoire** (date stockée) ; rollback documenté ; tests : signature valide avec chaque clé pendant le chevauchement, rejet après expiration.
6. **Garde-fou « toutes les routes » rendu vérifiable** (la promesse seule ne vaut rien) : enregistrement des routes d'automatisation **exclusivement** via un wrapper unique `Partikulier_Automation_Bridge::register_route()` + **test de recette qui énumère les routes `partikulier/v1` entrantes et échoue si l'une n'utilise pas le callback partagé** + règle `check.sh` interdisant `register_rest_route` direct pour l'automatisation.

### I-3 — Idempotence : durcir l'existant, ne pas le réinventer [P2 — requalifié]
L'index UNIQUE et le `duplicate:true` **existent** (inventaire corrigé). Reste :
1. **Éliminer la course** : remplacer SELECT-puis-INSERT par **INSERT direct + capture contrôlée de l'erreur duplicate-key** → `200 {duplicate:true}`. Jamais de fenêtre entre lecture et écriture.
2. **Portée de l'unicité — décision actée** : l'unicité **globale** sur `event_id` est **conservée** (déjà déployée, zéro migration) ; en contrepartie, le contrat n8n/fournisseur de paiement impose des `event_id` **préfixés par la source** (`n8n-…`, `pay-…`) — vérifié par la liste blanche à réception. Si ce contrat est refusé côté n8n, bascule vers `UNIQUE(source, event_id)` avec migration.
3. **Audit J0 avant tout dbDelta** : `SELECT event_id, COUNT(*) … GROUP BY event_id HAVING COUNT(*)>1` — les doublons historiques sont traités **avant** toute évolution de schéma.

### I-4 — Paramétrage métier [P3]
- `quota_per_day` (1-10, défaut **2**) : lu par `class-buyer-qualification.php` **à l'intérieur de la transaction existante** — le `SELECT … FOR UPDATE` n'est ni supprimé ni contourné ;
- `consent_text` : **texte WhatsApp pur** (canal tranché : message envoyé, pas de rendu web) → `sanitize_textarea_field`, **aucun HTML** ;
- `channel_url` : `esc_url_raw`, https obligatoire.

---

## 3. K2 — refus définitif (inchangé)
Motifs actés en 6.14.1 v3 : auth maison, Tailwind CDN vs CSP, rate-limit IP/NAT, duplication wp-admin. Alternative : 6.15-K (livrée). Réouverture = CdC sécurité dédié avec audit.

---

## 4. Recette (scripts versionnés uniquement — [À CRÉER])
```text
scripts/test-n8n-settings-migration.php   # migration idempotente/reprenable : relance à chaque état intermédiaire, anciennes clés supprimées, trace posée, AUCUN secret imprimé
scripts/test-n8n-hmac-modes.php           # off/log/enforce sur LES TROIS routes ; log n'apporte aucun blocage mais journalise
scripts/test-n8n-replay.php               # timestamp hors fenêtre ±5min -> refus ; rejeu event_id -> duplicate:true SANS double traitement
scripts/test-n8n-secret-rotation.php      # double clé pendant chevauchement, rejet après expiration, rollback
scripts/test-n8n-idempotence-race.php     # 2 insertions concurrentes même event_id -> 1 ligne, 1 duplicate:true (preuve anti-course)
scripts/test-n8n-route-guard.php          # énumère les routes partikulier/v1 entrantes -> échec si une route échappe au callback partagé
scripts/test-n8n-canary.php               # secret canari de recette : son empreinte n'apparaît NI dans le zip NI dans les rapports NI dans debug.log
```
Chaque script : rapport JSON, exit ≠ 0 en échec, données créées par le chemin métier réel, nettoyage, **zéro secret en sortie**.
**Interdits** : tester le quota par INSERT manuel ; livrer `enforce` sans période `log` ; imprimer un secret ; « avant » non hashé.

## 5. Planning (revu)
| Phase | Contenu | Charge |
|---|---|---|
| J0 | Contrat de signature validé avec le workflow n8n réel + audit doublons + inventaire secrets prod (empreintes) | 0,5 j |
| J1 | I-1 (constante + option + migration idempotente/reprenable + écran) | 1 j |
| J2-J3 | I-2 (canonique, timestamp, key_id, rotation, wrapper + garde-fou) | 1,5-2 j |
| J3 | I-3 (insert-catch anti-course) + I-4 | 0,5 j |
| J4 | Recette complète (7 scripts) + docs + package 6.16.0 | 1 j |
| **Total** | | **4-5 j** + fenêtre d'observation `log` en prod avant `enforce` |

## 6. Sign-off (exécutable uniquement)
- [ ] Les 7 scripts §4 verts sur sandbox fraîche, rapports JSON archivés ;
- [ ] `check.sh` vert (avec la nouvelle règle route-guard) ; versions 6.16.0 alignées ; `visual.mjs` 12 vues conformes (aucun impact public attendu) ;
- [ ] canari : empreinte absente du zip, des rapports et des logs ;
- [ ] procédure exploitant : bascule `log → enforce` + rotation + rollback, écrites et relues ;
- [ ] `bash scripts/package.sh 6.16.0` + SHA-256 publié.
