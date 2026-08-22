# Partikulier — paquet de reprise développeur

**Thème WordPress 6.17.0 · portail immobilier marocain · août 2026**

Tout ce qu'il faut pour reprendre le projet sans poser de question. Environnement, code, documentation, scripts et procédures.

---

## Démarrer en deux commandes

```bash
bash scripts/install.sh    # installe PHP, MariaDB, WordPress, Estatik, le thème et 6 annonces
bash scripts/start.sh      # http://localhost:8090
```

Administration : `http://localhost:8090/wp-admin` — `admin` / `admin`.

Compter environ deux minutes. Le script est **idempotent** : relançable sans risque.

Testé sur Debian/Ubuntu. Ailleurs, installer PHP 8+, MariaDB, curl et unzip à la main, puis relancer.

---

## Contenu du paquet

```
LISEZ-MOI.md              ce fichier
theme/                    code source du thème (source de vérité)
partikulier-6.17.0.zip    archive prête à installer sur un WordPress (bundle complet)

documentation/
├── 00-COMMENCER-ICI.md      vue d'ensemble, règles à respecter
├── 01-ARCHITECTURE.md       modules, données, routes, conventions
├── 02-DECISIONS.md          POURQUOI le code est ainsi — à lire en premier
├── 03-CHANGELOG.md          historique 5.5.2 → 6.17.0
├── 04-PIEGES.md             bugs déjà résolus, classés par domaine
├── 05-CHANTIERS-RESTANTS.md ce qui reste à faire
├── 06-TESTS.md              procédures de vérification
└── 07-FAQ.md                réponses directes aux questions courantes

scripts/
├── install.sh            monte tout l'environnement
├── start.sh              démarre le site
├── sync.sh               recopie theme/ vers WordPress — APRÈS CHAQUE ÉDITION
├── check.sh              contrôle qualité (PHP, JS, versions, régressions)
├── package.sh            fabrique une archive versionnée
├── rapide.mjs            4 vues, ~6 s, pour itérer
├── visual.mjs            comparaison pixel, 12 vues
├── parcours.mjs          parcours de dépôt complet
├── audit.mjs             performance, accessibilité, SEO
├── securite.mjs          contrôles de sécurité
├── charge.mjs            montée en charge
├── diagnostic-site.php   diagnostic en ligne de commande
├── reparer-taxonomies.php corrige les termes mal classés
└── traduire-annonces.php  rattrape les traductions manquantes

assets-demo/hero.jpg      photo servant aux annonces de démonstration
notes-client/             notes rédigées pour le client, par phase
```

---

## Le cycle de travail

```bash
# 1. modifier le code dans theme/
# 2. synchroniser  ← ne jamais sauter cette étape
bash scripts/sync.sh
# 3. vérifier dans le navigateur, puis
bash scripts/check.sh
# 4. livrer
bash scripts/package.sh 6.14.0
```

**Le piège n°1 du projet** : éditer `theme/` sans lancer `sync.sh`, tester l'ancienne version, et conclure que le correctif ne marche pas.

---

## Ordre de lecture conseillé

1. `documentation/00-COMMENCER-ICI.md` — cadre général et règles du client.
2. `documentation/02-DECISIONS.md` — **le plus important.** Plusieurs choix ressemblent à des erreurs quand on ignore leur raison. Le lire évite de « corriger » du code volontaire.
3. `documentation/01-ARCHITECTURE.md` — au moment de coder.
4. `documentation/04-PIEGES.md` et `07-FAQ.md` — à garder ouverts.

---

## Trois choses à savoir tout de suite

**L'historique git n'est pas fiable.** L'environnement de développement d'origine excluait une partie de `.git` de ses sauvegardes : les commits ont été perdus plusieurs fois et le `HEAD` retombait sur un état ancien. **Le zip et `theme/` font foi.** Initialise un dépôt neuf et pousse-le sur un hébergeur distant dès la première heure.

**Sans numéro WhatsApp configuré, le formulaire refuse tous les dépôts.** Le message d'erreur ne le mentionne pas. `install.sh` pré-remplit un numéro fictif pour le développement.

**Le site est réservé aux propriétaires particuliers.** Les agences et agents immobiliers sont refusés côté serveur. Ce n'est pas une limitation technique mais le positionnement du produit.

---

## État du projet

**Version 6.17.0 — Stable & Certifiée.**

Le thème est désormais entièrement conforme au CDC v1.5. Les chantiers historiques (N+1, Polylang, Pagination) ont été résolus et validés par une recette froide complète :
- **Performance** : Optimisation N+1 (184 → 32 requêtes SQL sur archive).
- **i18n** : Support trilingue complet (FR/EN/AR) avec détection navigateur et exemption robots.
- **Sécurité** : Protection HMAC pour les webhooks n8n et hardening global.
- **UX** : Pagination à 24 annonces et support RTL (Arabe) avec polices natives.
