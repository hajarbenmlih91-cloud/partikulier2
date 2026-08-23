# Artefact Estatik 4.3.4

L’archive a été récupérée le 23 août 2026 depuis l’URL officielle du répertoire WordPress.org : <https://downloads.wordpress.org/plugin/estatik.zip>.

Le répertoire officiel Estatik indique la version **4.3.4** et le changelog officiel mentionne la publication du 3 août 2026, notamment la validation du destinataire du formulaire et l’ajout d’un rate limiting [1]. Le fichier `estatik/estatik.php` de l’archive téléchargée déclare `Version: 4.3.4`.

L’archive a passé `unzip -tq` sans erreur. Elle est figée dans `vendor-artifacts/estatik-4.3.4.zip` et son empreinte SHA-256 est :

```text
9aad4e7b0bd0f35e3a918a0cf68a3dbfef473df09ca8e6b3a471bb4213e965d5  estatik-4.3.4.zip
```

La vérification doit être exécutée depuis le répertoire `vendor-artifacts/` avec `sha256sum --check --strict estatik-4.3.4.zip.sha256`. L’installateur privilégie cet artefact local. Un artefact externe est accepté uniquement avec `PK_ESTATIK_URL` et `PK_ESTATIK_SHA256`. Le téléchargement générique sans checksum est conservé uniquement derrière `PK_ALLOW_UNPINNED_ESTATIK=1`, avec un avertissement explicite et un état non reproductible.

Le code source de l’artefact reste distribué par WordPress.org et est installé sous sa licence déclarée. Le checksum publié ici garantit l’identité binaire de l’archive utilisée par la recette ; il ne constitue pas une signature cryptographique de l’éditeur.

## Références

[1]: https://wordpress.org/plugins/estatik/ "Estatik Real Estate Plugin — WordPress.org, version et changelog"
[2]: https://downloads.wordpress.org/plugin/estatik.zip "Archive Estatik téléchargée"
