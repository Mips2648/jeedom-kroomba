Description 
===

Plugin permettant de piloter un roomba 960 ou 980.
Pour cette version de Roomba, pas besoin d'extension matérielle (type RooWifi ou Thinking Cleaner).

Pour le moment, ce plugin permet :

-   La détection et appareillage automatique des Roomba sur le réseau local
-   La remontée du statut :

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

L'état de la batterie n'est pas visible sur le Widget mais  l'information est visible dans le suivi global des équipements (Menu Analyses > Equipements > Batteries).

Configuration du plugin 
===

La procédure d'installation :

-   S'assurer que le Roomba est correctement paramétré sur le réseau local (procédure via l'application iRobot)
-   Eteindre toute application iRobot sur Android ou iOS. Attention : l'utilisation simultanée de l'application iRobot peut provoquer des blocages de communication entre le plugin et le Roomba
-   Sur jeedom, installer le plugin, attendre que les dépendances soient installées
-   S'assurer que roomba est sur sa base
-   Faire une recherche d'équipement depuis la page de configuration. Les roombas sur le réseaux seront automatiquement créés en tant qu'équipement.
-   Utiliser le bouton "Récupérer le mot de passe" sur la page de configuration de l'équipement
-   Lire les instructions (appuis de 2 sec sur HOME jusqu'à ce que la led WIFI clignotte vert) puis appuyer sur "Continuer"
-   Dans les 30 secondes qui suivent, sur le roomba, rester appuyé sur le bouton "Maison" (seulement) pendant 2 secondes (jusqu'à ce qu'il fasse un petit bipbip).

FAQ 
===

Problèmes connus :
Pour connaitre votre version, rendez-vous sur l'application iRobot, Paramètres > A propos de Roomba.

Une nouvelle version du firmware (v2.0.0-34) est en cours de déploiement. Cette mise à jour rendra le plugin non-fonctionnel. ==> corrigé !

Nouvelle version firmware v2.2.5-2 en approche qui rendra très probablement le plugin de nouveau inopérant.

En cas de problème :

Merci d'activer le niveau de log "Debug", et de m'envoyer le résultat ainsi que votre version firmware (voir Problèmes connus)

N'hésitez pas à partager le fonctionnement du plugin chez vous (qu'il fonctionne ou pas).

Merci par avance pour votre bienveillance : il s'agit de mon tout premier plugin Jeedom ! 

