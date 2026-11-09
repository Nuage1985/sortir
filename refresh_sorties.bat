@echo off
rem Se place dans le dossier du projet
cd /d C:\wamp64\www\sortir

rem Crée le dossier de logs s'il n'existe pas (la redirection >> ne le crée pas)
if not exist var\logs mkdir var\logs

rem Horodate le début de l'exécution
echo [%date% %time%] Debut >> var\logs\sortie_refresh.log

rem Mettre par la version réellement présente dans C:\wamp64\bin\php\
C:\wamp64\bin\php\php8.4.15\php.exe bin\console app:sortie:refresh-status >> var\logs\sortie_refresh.log 2>&1

rem Consigne le code de retour de PHP (0 = succès)
echo [%date% %time%] Fin, code retour : %errorlevel% >> var\logs\sortie_refresh.log
