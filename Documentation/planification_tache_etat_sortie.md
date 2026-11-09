# Création d'une tâche planifiée sous Windows (Refresh Sorties)

## 1. Contexte

Machine concernée : **PC de dev (Windows, environnement WAMP)**.

La tâche exécute périodiquement la commande Symfony `app:sortie:refresh-status`, située dans le projet `C:\wamp64\www\sortir`.

## 2. Prérequis

- Le **service de base de données** de WAMP (`wampmysqld64` ou `wampmariadb64`) doit être démarré. La vérification s'effectue dans `services.msc`. Un type de démarrage **Automatique** évite de lancer WAMP manuellement.
- **Apache** et le **serveur Symfony** (`symfony server:start`, `php -S`) ne sont pas nécessaires : une commande `bin/console` s'exécute en ligne de commande, indépendamment de tout serveur web.
- Le dossier `vendor` doit être présent (`composer install` déjà exécuté).
- L'extension `pdo_mysql` doit être activée dans le `php.ini` du **PHP en ligne de commande** (`C:\wamp64\bin\php\php<version>\php.ini`), distinct de `phpForApache.ini`.
- La **version exacte de PHP** doit être relevée dans le dossier `C:\wamp64\bin\php\` (par exemple `php8.3.14`). Le texte `php8.x.x` utilisé dans cette documentation est indicatif et doit être remplacé.

## 3. Création de la tâche

- Ouvrir le **Planificateur de tâches**.
- Cliquer sur **Créer une tâche** (et non « Créer une tâche de base », qui ne propose pas tous les réglages nécessaires).

### 3.1. Onglet « Général »

- Nom : `Refresh Sorties`
- Choisir l'une des deux options suivantes :
    - **« Exécuter uniquement si l'utilisateur est connecté »** : la tâche ne s'exécute que pendant une session ouverte.
    - **« Exécuter même si l'utilisateur n'est pas connecté »** : la tâche s'exécute en arrière-plan. Windows demande alors le mot de passe du compte lors de l'enregistrement.

### 3.2. Onglet « Déclencheurs »

Le type de déclencheur à utiliser se nomme **« À l'heure programmée »**. L'intitulé « À l'heure spécifiée » ne correspond à aucune option de l'interface française. « Une fois » est une option de la zone « Paramètres », et non un type de déclencheur.

1. Cliquer sur **Nouveau**.
2. Dans la liste **Lancer la tâche**, conserver **« À l'heure programmée »**.
3. Dans la zone **Paramètres**, laisser **« Une fois »** coché, puis renseigner la date et l'heure de début (par exemple l'heure actuelle).
4. Dans **Paramètres avancés**, cocher **« Répéter la tâche toutes les »**.
5. Sélectionner `5 minutes` ou `1 heure` dans la liste. Pour `1 minute`, saisir la valeur manuellement dans le champ, celle-ci n'étant pas proposée dans la liste.
6. Dans **« pour une durée de »**, sélectionner **« Indéfiniment »**. La valeur par défaut (`1 jour`) arrêterait la répétition au bout de 24 heures.
7. Laisser décochées les cases **« Arrêter toutes les tâches à l'issue de la durée de répétition »** et **« Expiration »**.
8. Vérifier que la case **« Activée »** est cochée, puis valider avec **OK**.

### 3.3. Onglet « Actions »

Cliquer sur **Nouveau**, puis renseigner les champs suivants.

#### Option A : appel direct de PHP

- Action : **Démarrer un programme**
- Programme/script :

  ```text
  C:\wamp64\bin\php\php8.x.x\php.exe
  ```

  (`8.x.x` est à remplacer par la version de PHP installée dans WAMP.)

- Ajouter des arguments :

  ```text
  C:\wamp64\www\sortir\bin\console app:sortie:refresh-status
  ```

- Démarrer dans :

  ```text
  C:\wamp64\www\sortir
  ```

#### Option B (recommandée) : appel via un fichier `.bat`

Cette option facilite le diagnostic grâce à la génération d'un fichier de log (voir section 4).

- Action : **Démarrer un programme**
- Programme/script :

  ```text
  C:\wamp64\www\sortir\refresh_sorties.bat
  ```

- Démarrer dans :

  ```text
  C:\wamp64\www\sortir
  ```

### 3.4. Onglet « Conditions »

- Décocher **« Démarrer la tâche uniquement si l'ordinateur est sur secteur »** (bloquant fréquent sur un ordinateur portable).
- Décocher **« Démarrer uniquement si une connexion réseau est disponible »** si la commande n'utilise pas le réseau.

## 4. Fichier `.bat` avec journalisation

Créer le fichier `C:\wamp64\www\sortir\refresh_sorties.bat` avec le contenu suivant :

```bat
@echo off
rem "rem" introduit un commentaire : ces lignes ne sont pas exécutées

rem Se placer dans le dossier du projet (évite les problèmes de chemin relatif)
cd /d C:\wamp64\www\sortir

rem Créer le dossier de logs s'il n'existe pas (la redirection >> ne le crée pas)
if not exist var\logs mkdir var\logs

rem Horodater le début de l'exécution
echo [%date% %time%] Debut >> var\logs\sortie_refresh.log

rem Exécuter la commande Symfony ; la sortie standard (>>) et les erreurs (2>&1) vont dans le log
rem Remplacer 8.x.x par la version réellement présente dans C:\wamp64\bin\php\ (exemple : php8.3.14)
C:\wamp64\bin\php\php8.x.x\php.exe bin\console app:sortie:refresh-status >> var\logs\sortie_refresh.log 2>&1

rem Consigner le code de retour de PHP (0 = succès)
echo [%date% %time%] Fin, code retour : %errorlevel% >> var\logs\sortie_refresh.log
```

### 4.1. Points d'attention

- `rem` (pour *remark*) désigne un commentaire dans un fichier `.bat`. Ces lignes peuvent être conservées ou supprimées sans effet sur le fonctionnement.
- Le chemin `php8.x.x` doit impérativement être remplacé par le nom exact du dossier de la version de PHP installée. À défaut, le chemin n'existe pas et la commande échoue.
- Le dossier `var\logs` n'est pas créé par Symfony (qui utilise `var\log`, sans « s »). La ligne `if not exist ... mkdir` garantit son existence. Sans elle, la redirection `>>` échoue, le fichier de log n'est pas créé et la tâche se termine en erreur (`0x1`) sans lancer PHP.

### 4.2. Fichier de log

Le fichier à consulter est :

```text
C:\wamp64\www\sortir\var\logs\sortie_refresh.log
```

Chaque exécution y ajoute une ligne de début, la sortie de la commande, puis une ligne de fin avec le code de retour.

## 5. Test et diagnostic

1. Lancer le `.bat` à la main (double-clic ou invite de commandes) et vérifier l'apparition de `var\logs\sortie_refresh.log`.
2. Lancer la tâche depuis le Planificateur : clic droit, puis **Exécuter**.
3. Contrôler la colonne **Résultat de la dernière exécution** :
    - `0x0` : succès ;
    - `0x1` : erreur dans le script ou la commande (consulter le log) ;
    - `0x2` : fichier introuvable (vérifier le chemin du `.bat` dans l'onglet « Actions »).
4. En cas d'échec, activer l'historique (panneau de droite : **Activer l'historique de toutes les tâches**) et consulter l'onglet **Historique** de la tâche.

### 5.1. Erreurs fréquentes

| Message ou symptôme | Cause probable | Correction |
|---|---|---|
| Fichier de log absent, code `0x1` | Dossier `var\logs` inexistant | Utiliser le `.bat` de la section 4 |
| `php.exe` non reconnu ou chemin introuvable | `php8.x.x` non remplacé | Relever la version dans `C:\wamp64\bin\php\` |
| `could not find driver` | Extension `pdo_mysql` désactivée | L'activer dans le `php.ini` du PHP en ligne de commande |
| `Connection refused` ou `SQLSTATE[HY000] [2002]` | Service MySQL/MariaDB arrêté | Démarrer le service `wampmysqld64` |
| `There are no commands defined in the "app:sortie" namespace` | Nom de commande incorrect | Exécuter `php bin/console list app` et comparer |
| Erreur de cache ou de permissions | Dossier `var\cache` inaccessible | Exécuter `php bin/console cache:clear` |
