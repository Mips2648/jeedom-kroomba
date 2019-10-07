PROGRESS_FILE=/tmp/dependancy_kroomba_in_progress
BASEDIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

if [ ! -z $1 ]; then
  PROGRESS_FILE=$1
fi
touch ${PROGRESS_FILE}
echo 0 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation des dépendances             *"
echo "********************************************************"
cd ${BASEDIR};
#remove old files
sudo rm -rf roomba > /dev/null 2>&1
sudo rm -rf Roomba980-Python > /dev/null 2>&1
sudo apt-get update
sudo apt install -y python3-pip
echo 55 > ${PROGRESS_FILE}
sudo apt-get install -y python3-setuptools
echo 56 > ${PROGRESS_FILE}
sudo python3 -m pip uninstall -y six
echo 57 > ${PROGRESS_FILE}
sudo python3 -m pip uninstall -y paho-mqtt
echo 58 > ${PROGRESS_FILE}
sudo python3 -m pip install setuptools
echo 60 > ${PROGRESS_FILE}
sudo python3 -m pip install six
echo 62 > ${PROGRESS_FILE}
sudo python3 -m pip install paho-mqtt
echo 65 > ${PROGRESS_FILE}

sudo git clone https://github.com/NickWaterton/Roomba980-Python.git
sudo mv Roomba980-Python/roomba roomba
sudo chown -R www-data roomba
sudo chmod 777 -R roomba
echo 100 > ${PROGRESS_FILE}
echo "********************************************************"
echo "*             Installation terminée                    *"
echo "********************************************************"
rm ${PROGRESS_FILE}
