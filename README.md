# Sortir.com

Application web d'organisation de sorties pour les étudiants de l'ENI, réalisée en équipe dans le cadre de la formation CDA (Concepteur Développeur d'Applications).

> 🚧 **Projet en cours de développement (work in progress).**
> Les fonctionnalités, la structure et ce document sont susceptibles d'évoluer.

## Stack technique

- [Symfony](https://symfony.com/) 7.4 (PHP) avec Twig et Doctrine
- [AssetMapper](https://symfony.com/doc/current/frontend/asset_mapper.html) pour la gestion des assets (sans Node.js)
- [Tailwind CSS](https://tailwindcss.com/) v4 via [`symfonycasts/tailwind-bundle`](https://symfony.com/bundles/TailwindBundle) (binaire standalone téléchargé automatiquement)
- [Flowbite](https://flowbite.com/) pour les composants d'interface (navbar responsive, etc.)
- Symfony UX : Stimulus et Turbo

## Prérequis

- PHP (version compatible avec Symfony 7.4) et [Composer](https://getcomposer.org/)
- [Symfony CLI](https://symfony.com/download) (recommandé)
- Un serveur de base de données accessible en local
- Git

## Installation

1. Cloner le dépôt puis installer les dépendances :

   ```bash
   git clone https://github.com/Nuage1985/sortir
   cd sortir
   composer install
   ```

   Cette commande installe aussi automatiquement le JavaScript de Flowbite (`importmap:install`).

2. Créer un fichier `.env.local` à la racine (il est ignoré par Git) et y renseigner sa propre connexion à la base de données :

   ```dotenv
   DATABASE_URL="..."
   ```

   Ne jamais committer ce fichier : chaque développeur a le sien.

3. Créer la base de données et appliquer les migrations :

   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

## Lancer le projet en développement

Deux commandes, dans deux terminaux :

```bash
# Compiler le CSS Tailwind (et le recompiler à chaque modification)
php bin/console tailwind:build --watch

# Démarrer le serveur
symfony serve -d
```

Au premier lancement de `tailwind:build`, le binaire Tailwind est téléchargé dans `var/tailwind/`.

> ⚠️ Si la page s'affiche sans style, c'est que `tailwind:build` n'a pas été lancé : le CSS compilé n'est pas versionné.

## Travailler en équipe

- Faire un `git pull` avant chaque `git push`.
- Privilégier des commits petits et fréquents, avec un titre court qui commence par un verbe d'action (par exemple « Ajouter la page d'accueil »).
- Éviter de travailler à plusieurs sur les mêmes fichiers partagés en même temps (`base.html.twig`, `app.css`...).
- **Migrations Doctrine** : se mettre d'accord sur qui crée quelle entité, et toujours faire un `git pull` avant de lancer `make:migration`, pour éviter des migrations qui se contredisent.

## Structure du projet

| Dossier          | Contenu                                                   |
| ---------------- | --------------------------------------------------------- |
| `src/Controller` | Contrôleurs                                               |
| `src/Entity`     | Entités Doctrine                                          |
| `src/Repository` | Repositories Doctrine                                     |
| `src/Migrations` | Migrations de base de données                             |
| `templates/`     | Templates Twig                                            |
| `assets/`        | JavaScript, CSS source (Tailwind) et contrôleurs Stimulus |
| `config/`        | Configuration de l'application                            |

## Statut

Projet pédagogique en cours de réalisation. Ce README sera complété au fil de l'avancement (fonctionnalités, jeu de données, conventions de l'équipe).
