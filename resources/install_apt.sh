#!/bin/bash
PROGRESS_FILE=/tmp/dependancy_kroomba_in_progress
BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

if [ ! -z $1 ]; then
    PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "--0%"
echo "Lancement de l'installation/mise à jour des dépendances K Roomba"
sudo mkdir -p $HOME/.local
sudo mkdir -p $HOME/.pip
sudo chown ${USER}:`groups |cut -d" " -f1` ${HOME}/.local
sudo chown ${USER}:`groups |cut -d" " -f1` ${HOME}/.pip
cd /tmp
echo 20 > ${PROGRESS_FILE}
echo "--20%"
sudo apt-get update
type git &>/dev/null
if [ $? -ne 0 ]; then
  echo 30 > ${PROGRESS_FILE}
  echo "--30%"
  echo "Installation de git"
  sudo apt-get -y install git
fi
pip uninstall -y six ; pip install six
pip uninstall -y paho-mqtt ; pip install paho-mqtt
echo 50 > ${PROGRESS_FILE}
echo "--50%"
cd ${BASEDIR}
sudo rm -Rf roomba
sudo rm -Rf Roomba980-Python
echo "Téléchargement de Roomba980-Python"
sudo git clone https://github.com/NickWaterton/Roomba980-Python.git
sudo chown -R www-data Roomba980-Python
mv  ${BASEDIR}/Roomba980-Python/roomba ${BASEDIR}
echo 100 > ${PROGRESS_FILE}
echo "--100%"
echo "Installation des dépendances K-Roomba terminée."
rm ${PROGRESS_FILE}