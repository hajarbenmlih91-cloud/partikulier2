# Contrat du webhook sortant Estatik → n8n

## Périmètre

Lorsqu’une annonce est approuvée ou qu’un renvoi d’identifiants est demandé, le thème publie l’annonce puis tente une notification n8n. La publication WordPress et la notification sont deux étapes distinctes : un échec n8n ne doit pas être considéré comme un envoi réussi.

## Transport obligatoire

Le webhook configuré doit être une URL `https://` avec un hôte non vide. Une URL `http://`, absente ou mal formée est refusée avant l’envoi. Le secret est fourni par `PARTIKULIER_N8N_SECRET` ou par le réglage administrateur ; il ne doit jamais être exposé dans le navigateur ni écrit dans le rapport.

## Headers et signature

Le thème envoie le JSON exact dans le corps de la requête `POST` avec les headers suivants :

| Header | Valeur |
| --- | --- |
| `Content-Type` | `application/json` |
| `X-Partikulier-Automation` | secret partagé, à comparer en temps constant |
| `X-Partikulier-Timestamp` | timestamp Unix UTC |
| `X-Partikulier-Key-Id` | identifiant de la clé active |
| `X-Partikulier-Signature` | `sha256=` suivi de HMAC-SHA256 |

La chaîne canonique est :

```text
POST
/path[?query]
timestamp
corps JSON exact
```

La signature est `sha256=HMAC-SHA256(canonique, clé)`. Pour une clé encodée en base64 d’au moins 32 octets, le décodage base64 est utilisé ; une clé hexadécimale d’au moins 64 caractères peut être décodée en octets. Le workflow n8n doit refuser un timestamp âgé de plus de cinq minutes, une clé inconnue, une signature invalide ou un corps modifié.

## Observabilité WordPress

Après chaque tentative, le thème écrit uniquement des métadonnées d’état : `_pk_n8n_attempted_at`, `_pk_n8n_status`, `_pk_n8n_response_code`, `_pk_n8n_sent` ou `_pk_n8n_error`. Le mot de passe destiné à WhatsApp n’est pas conservé en clair dans le transient d’avis administrateur : cet avis est chiffré brièvement avec AES-256-GCM et supprimé après lecture.

Un statut HTTP hors 2xx, une erreur réseau, un secret absent ou une URL non HTTPS donne un état `error`. Le workflow n8n doit retourner un code 2xx uniquement après avoir accepté et traité l’événement, puis appeler la route d’accusé prévue pour les renvois.

## Compatibilité et migration

La signature sortante est ajoutée sans changer les champs métier du JSON. Le workflow n8n doit être mis à jour pour vérifier la signature avant de traiter le mot de passe. Pendant cette migration, le secret partagé reste transmis dans `X-Partikulier-Automation` pour identifier la clé, mais il ne remplace pas la vérification HMAC.
