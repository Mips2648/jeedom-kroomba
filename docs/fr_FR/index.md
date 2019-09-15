Description 
===

Plugin permettant de piloter un aspirateur robot de la marque IRobot. 

Le plugin a été testé avec succès sur des modèles 960, 980 et i7.
Si vous testez d'autres modèles, communiquez le résultat dans le fil officel du plugin sur le forum communautaire Jeedom.

Ce plugin ne fonctionne pas avec les anciens modèles munis d'un extension matérielle (type RooWifi ou Thinking Cleaner), il ne fonctionne qu'avec les modèles récents dotés d'une liaison Wifi.

Fonctionnalités
===

Pour le moment, ce plugin permet :

-   La détection et l'apairage automatique des Roomba sur le réseau local
-   La remontée du statut visualisé par une image sur le widget :

![Charge](../images/kroomba_charge.png)	
En charge

![Dock](../images/kroomba_home.png)	
Retour en cours vers le dock

![Clean](../images/kroomba_run.png)	
En cours de nettoyage

![Stop](../images/kroomba_stop.png)	
Stoppé

![Bloqué](../images/kroomba_stuck.png)
Bloqué (<=> stuck en anglais)

![Inconnu](../images/kroomba_unknown.png)
Statut inconnu

-   L'envoi des commandes start / stop / pause / resume / dock

Il comporte un Widget desktop & mobile.

Par défaut l'état de la batterie n'est pas visible sur le Widget mais  l'information est visible dans le suivi global des équipements (Menu Analyses > Equipements > Batteries).

Si vous préférez vous pouvez également visualiser l'état de la batterie sur le widget en cochant la case "Afficher" de la commande "Batterie" dans l'onglet Commandes de l'équipement.

Dans l'onglet Commandes de l'équipement vous pouvez aussi réordonner les commandes par glisser/déposer, les afficher ou les masquer, les renommer, modifier leurs paramètres. N'oubliez pas de sauvegarder.

Configuration du plugin 
===

La procédure d'installation :

-   S'assurer que le Roomba est correctement paramétré sur le réseau local (procédure via l'application iRobot)
-   Eteindre toute application iRobot sur Android ou iOS. Attention : l'utilisation simultanée de l'application iRobot peut provoquer des blocages de communication entre le plugin et le Roomba
-   Sur jeedom, installer le plugin, attendre que les dépendances soient installées
-   S'assurer que roomba est sur sa base et pas "endormi" (appuyer brièvement sur "Clean" pour le réveiller si nécessaire).
-   Depuis la page de configuration du plugin cliquer sur le bouton "Découvrir les Roombas". Les roombas sur le réseau seront automatiquement créés en tant qu'équipement.
-   Utiliser le bouton "Récupérer le mot de passe" sur la page de configuration de l'équipement
-   Lire les instructions (appui de 2 sec sur le bouton HOME du robot jusqu'à ce que la led WIFI clignotte vert) puis appuyer sur "Continuer" dans le dialogue du plugin.
-   Dans les 30 secondes qui suivent, sur le roomba, rester appuyé sur le bouton "Maison" (seulement) pendant 2 secondes (jusqu'à ce qu'il fasse un petit bipbip).

FAQ 
===

En cas de problème : Merci d'activer le niveau de log "Debug", et de m'envoyer le résultat ainsi que votre version de Jeedom, le modèle et la version du firmware de votre Robot
(pour connaître la version du firmware du robot, lancer l'ap sur votre Smartphone et aller dans Paramètres > A propos de 'Nom de votre robot')

N'hésitez pas à partager le fonctionnement du plugin chez vous sur le fil officel du plugin du forum communautaire Jeedom (qu'il fonctionne ou pas).

Ce plugin a été créé par kavod (Brice Grichy) d'où le nom kroomba.

Son dépôt Github est là : https://github.com/kavod/kroomba . Brice n'ayant plus de Roomba il a accepté que je reprenne son plugin.

Merci Brice pour tout ton travail (c'était son premier plugin Jeedom !) et pour toute l'aide apportée lors de la reprie du plugin. 

