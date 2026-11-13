# Sortir.com

Application web d'organisation de sorties pour les étudiants de l'ENI, réalisée en équipe dans le cadre de la formation CDA (Concepteur Développeur d'Applications).

> 🚧 **Projet en cours de développement (work in progress).**
> Les fonctionnalités, la structure et ce document sont susceptibles d'évoluer.

## Description

Sortir.com permet aux étudiants de l'ENI de créer, gérer et s'inscrire à des sorties autour de leur campus. L'application propose :

- **Gestion des sorties** : création, modification, annulation, inscription/désinscription des participants.
- **Machine à états automatique** : les sorties traversent automatiquement les états `EnCreation → Ouverte → Clôturée → EnCours → Terminée → Historisée` (ou `Annulée`) selon les dates configurées.
- **Filtrage et pagination** : recherche par nom, dates, organisateur, campus, avec pagination de 10 résultats par page.
- **Profil utilisateur** : consultation et modification du profil (nom, prénom, téléphone, email, mot de passe, photo de profil).
- **Panneau d'administration** : gestion des villes, campus et participants ; import de participants via CSV ; activation/désactivation de comptes.
- **API REST** : endpoint JSON pour récupérer les sorties d'un campus avec filtres par état et date, authentifié par JWT.

## Stack technique

- **PHP** ≥ 8.2
- [**Symfony**](https://symfony.com/) 7.4 avec Twig et Doctrine ORM 3.7
- [**AssetMapper**](https://symfony.com/doc/current/frontend/asset_mapper.html) pour la gestion des assets (sans Node.js)
- [**Tailwind CSS**](https://tailwindcss.com/) v4 via [`symfonycasts/tailwind-bundle`](https://symfony.com/bundles/TailwindBundle) (binaire standalone téléchargé automatiquement)
- [**Flowbite**](https://flowbite.com/) pour les composants d'interface (navbar responsive, etc.)
- **Symfony UX** : Stimulus et Turbo
- [**Lexik JWT Authentication**](https://github.com/lexik/LexikJWTAuthenticationBundle) pour l'API REST
- **Base de données** : PostgreSQL 16 (via Docker Compose) ou base locale
- **Tests** : PHPUnit 13.4 avec Faker pour les fixtures

## Prérequis

- **PHP** ≥ 8.2 avec les extensions `sodium` et `openssl` activées
- [**Composer**](https://getcomposer.org/)
- [**Symfony CLI**](https://symfony.com/download) (recommandé)
- [**Docker & Docker Compose**](https://docs.docker.com/compose/) (optionnel, pour la base de données)
- **Git**

> Pour vérifier les extensions PHP : `php -m`
> Si `sodium` ou `openssl` sont absents, décommentez `extension=sodium` et `extension=openssl` dans votre `php.ini` (`php --ini` pour le localiser), puis redémarrez les services.
>
> Sur Windows, en cas d'erreur `error:80000003:system`, installez [Win32 OpenSSL](https://slproweb.com/products/Win32OpenSSL.html).

## Installation

### 1. Cloner le dépôt et installer les dépendances

```bash
git clone https://github.com/Nuage1985/sortir
cd sortir
composer install
```

### 2. Générer les clés JWT

```bash
php bin/console lexik:jwt:generate-keypair
```

Les clés sont créées dans `config/jwt/` (non versionnées).

### 3. Configurer l'environnement

Créer un fichier `.env.local` à la racine (il est ignoré par Git) et y renseigner :

```dotenv
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8"
JWT_PASSPHRASE="votre_phrase_secrete_ici"
```

> ⚠️ Ne jamais committer ce fichier : chaque développeur a le sien.

### 4. Base de données

#### Option A — Docker Compose (recommandé)

```bash
docker compose up -d
```

#### Option B — Base de données locale

Assurez-vous qu'un serveur PostgreSQL est accessible avec les paramètres de `DATABASE_URL`.

### 5. Créer la base de données et appliquer les migrations

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 6. Charger les données de test (optionnel)

```bash
php bin/console doctrine:fixtures:load
```

Les fixtures créent des participants, campus, villes, lieux et sorties de démonstration.

## Lancer le projet en développement

Deux commandes, dans deux terminaux :

```bash
# Terminal 1 : Compiler le CSS Tailwind (et le recompiler à chaque modification)
php bin/console tailwind:build --watch

# Terminal 2 : Démarrer le serveur
symfony serve -d
```

Au premier lancement de `tailwind:build`, le binaire Tailwind est téléchargé dans `var/tailwind/`.

> ⚠️ Si la page s'affiche sans style, c'est que `tailwind:build` n'a pas été lancé : le CSS compilé n'est pas versionné.

## Fonctionnalités détaillées

### Authentification & rôles

- **Connexion** via formulaire (route `/`) avec Remember Me (1 semaine).
- **Deux rôles** : `ROLE_USER` (participant) et `ROLE_ADMIN` (administrateur).
- **UserChecker** : un compte désactivé (`actif = false`) ne peut pas se connecter.
- **API JWT** : authentification stateless via `/api/login_check` (email + mot de passe en JSON).

### Gestion des sorties

| Action               | Accès requis | Description                                                    |
|----------------------|--------------|----------------------------------------------------------------|
| Lister les sorties   | `ROLE_USER`  | Page d'accueil avec filtres (nom, dates, campus, organisateur) et pagination |
| Voir le détail       | `ROLE_USER`  | Page détaillée avec liste des participants                     |
| Créer une sortie     | `ROLE_USER`  | Formulaire avec titre, date, durée, lieu, campus, places max   |
| Modifier une sortie  | Organisateur | Modification des informations de sa propre sortie              |
| S'inscrire           | `ROLE_USER`  | Inscription/désinscription à une sortie ouverte                |
| Annuler une sortie   | Organisateur ou Admin | Formulaire avec motif d'annulation obligatoire (10 caractères min) |

### Machine à états des sorties

```
EnCreation → Ouverte → Clôturée → EnCours → Terminée → Historisée
   └───────→ Annulée (depuis EnCreation, Ouverte ou Cloturee)
```

- **Manuel** : `EnCreation` (brouillon), `Ouverte` (publication), `Annulée` (organisateur/admin).
- **Automatique** : `Clôturée` (date limite atteinte), `EnCours` (début de la sortie), `Terminée` (fin de la sortie), `Historisée` (fin + 30 jours).

La commande `app:sortie:refresh-status` met à jour les états automatiquement :

```bash
php bin/console app:sortie:refresh-status
```

### Panneau d'administration (`/admin`)

Accès réservé aux administrateurs (`ROLE_ADMIN`) :

- **Dashboard** : vue d'ensemble.
- **Gestion des villes** : ajout, modification, suppression (avec protection si utilisée).
- **Gestion des campus** : ajout, modification, suppression.
- **Gestion des participants** : création, modification, activation/désactivation, filtrage par nom et campus.
- **Import CSV** : importation en masse de participants via fichier CSV.

### API REST

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/api/login_check` | `POST` | Authentification JWT (body JSON : `email` + `password`) |
| `/api/campus/{id}/sorties` | `GET` | Liste des sorties d'un campus (filtres : `etat`, `date`) |

L'API retourne du JSON avec les groupes de sérialisation `sortie:read`. L'authentification se fait via l'en-tête `Authorization: Bearer <token>`.

## Structure du projet

| Dossier/Fichier              | Contenu                                                    |
|------------------------------|------------------------------------------------------------|
| `src/Controller/`            | 6 contrôleurs (Main, Security, Sortie, Profile, Admin, ApiSortie) |
| `src/Entity/`                | 5 entités (Participant, Sortie, Campus, Lieu, Ville)       |
| `src/Repository/`            | 5 repositories Doctrine                                    |
| `src/Service/`               | Services métier (SortieService, SortieStatusService, FileUploaderService, ImporterCSVService) |
| `src/Commands/`              | Commande console `app:sortie:refresh-status`               |
| `src/Enum/`                  | `EtatSortie` — machine à états des sorties                 |
| `src/Form/`                  | 6 types de formulaire Symfony                              |
| `src/Models/`                | 3 DTOs de filtrage (FiltreSortieDTO, FiltreSortieApiDTO, FiltreParticipantDTO) |
| `src/Security/`              | `UserChecker` — vérifie qu'un compte est actif             |
| `src/DataFixtures/`          | 4 fixtures (Campus, Lieu, Participant, Sortie) avec Faker  |
| `src/Migrations/`            | Migrations de base de données                              |
| `templates/`                 | 18 templates Twig (base, navbar, admin, profil, sorties)   |
| `assets/`                    | CSS source (Tailwind), JavaScript, contrôleurs Stimulus    |
| `config/`                    | Configuration Symfony (services, routes, sécurité, JWT)    |
| `tests/`                     | Tests PHPUnit (SortieControllerTest)                       |
| `translations/`              | Fichiers de traduction                                     |

## Tests

```bash
php bin/phpunit
```

Les tests utilisent les fixtures Doctrine et Faker pour peupler la base de test.

## Travailler en équipe

- Faire un `git pull` avant chaque `git push`.
- Privilégier des commits petits et fréquents, avec un titre court qui commence par un verbe d'action (par exemple « Ajouter la page d'accueil »).
- Éviter de travailler à plusieurs sur les mêmes fichiers partagés en même temps (`base.html.twig`, `app.css`...).
- **Migrations Doctrine** : se mettre d'accord sur qui crée quelle entité, et toujours faire un `git pull` avant de lancer `make:migration`, pour éviter des migrations qui se contredisent.

## Statut

Projet pédagogique en cours de réalisation. Ce README sera complété au fil de l'avancement.
