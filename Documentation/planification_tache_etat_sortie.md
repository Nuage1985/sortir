# Création d'une tâche planifiée sous Windows (Refresh Sorties)

## 1. Contexte

Machine concernée : **PC de dev (Windows, environnement WAMP)**.

La tâche exécute périodiquement la commande Symfony `app:sortie:refresh-status`, située dans le projet `C:\wamp64\www\sortir`.

## 2. Création de la tâche

- Ouvrir le **Planificateur de tâches**.
- Cliquer sur **Créer une tâche** (et non « Créer une tâche de base », qui ne propose pas tous les réglages nécessaires).
## 3. Onglet « Général »

- Nom : `Refresh Sorties`
- Choisir l'une des deux options suivantes :
    - **« Exécuter uniquement si l'utilisateur est connecté »** : la tâche ne s'exécute que pendant une session ouverte.
    - **« Exécuter même si l'utilisateur n'est pas connecté »** : la tâche s'exécute en arrière-plan. Windows demande alors le mot de passe du compte lors de l'enregistrement.
## 4. Onglet « Déclencheurs »

Le type de déclencheur à utiliser se nomme **« À l'heure programmée »**. L'intitulé « À l'heure spécifiée » ne correspond à aucune option de l'interface française. « Une fois » est une option de la zone « Paramètres », et non un type de déclencheur.

1. Cliquer sur **Nouveau**.
2. Dans la liste **Lancer la tâche**, conserver **« À l'heure programmée »**.
3. Dans la zone **Paramètres**, laisser **« Une fois »** coché, puis renseigner la date et l'heure de début (par exemple l'heure actuelle).
4. Dans **Paramètres avancés**, cocher **« Répéter la tâche toutes les »**.
5. Sélectionner `5 minutes` ou `1 heure` dans la liste. Pour `1 minute`, saisir la valeur manuellement dans le champ, celle-ci n'étant pas proposée dans la liste.
6. Dans **« pour une durée de »**, sélectionner **« Indéfiniment »**. La valeur par défaut (`1 jour`) arrêterait la répétition au bout de 24 heures.
7. Laisser décochées les cases **« Arrêter toutes les tâches à l'issue de la durée de répétition »** et **« Expiration »**.
8. Vérifier que la case **« Activée »** est cochée, puis valider avec **OK**.
## 5. Onglet « Actions »

Cliquer sur **Nouveau**, puis renseigner les champs suivants.

### Option A : appel direct de PHP

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

### Option B (recommandée) : appel via un fichier `.bat`

Cette option facilite le diagnostic grâce à la génération d'un fichier de log (voir section 6).

- Action : **Démarrer un programme**
- Programme/script :
```text
  C:\wamp64\www\sortir\refresh_sorties.bat
```

- Démarrer dans :
```text
  C:\wamp64\www\sortir
```

## 6. Vérification par fichier de log

Créer le fichier `C:\wamp64\www\sortir\refresh_sorties.bat` avec le contenu suivant :

```bat
@echo off
rem Exécute la commande Symfony et ajoute la sortie standard (>>) et les erreurs (2>&1) au fichier de log
C:\wamp64\bin\php\php8.x.x\php.exe C:\wamp64\www\sortir\bin\console app:sortie:refresh-status >> C:\wamp64\www\sortir\var\logs\sortie_refresh.log 2>&1
```

Ce fichier `.bat` est ensuite utilisé comme action de la tâche planifiée (option B de la section 5), à la place du lancement direct de `php.exe`.

Le fichier de log à consulter est :

```text
C:\wamp64\www\sortir\var\logs\sortie_refresh.log
```

Il permet de vérifier que la commande s'exécute correctement et d'identifier d'éventuelles erreurs. Le dossier `var\logs` doit exister ; à défaut, le créer manuellement.

## 7. Test et diagnostic

- Pour tester sans attendre le prochain déclenchement : clic droit sur la tâche, puis **Exécuter**.
- Consulter ensuite `sortie_refresh.log`.
- En cas d'échec, l'onglet **Historique** de la tâche (à activer dans le panneau de droite) indique la cause exacte.
