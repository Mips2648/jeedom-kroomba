import os

from roomba.roomba import Roomba
from roomba.password import Password

from jeedomdaemon.base_daemon import BaseDaemon

from config import iRobotConfig

class kroomba(BaseDaemon):
    def __init__(self) -> None:
        self._config = iRobotConfig()
        super().__init__(self._config, self.on_start, self.on_message, self.on_stop)

        self.set_logger_log_level('Roomba')
        basedir = os.path.dirname(__file__)
        self._roomba_configFile = os.path.abspath(basedir + '/../../data/config.ini')
        self._get_password = Password(file=self._roomba_configFile)
        self._roombas = {}

    async def on_start(self):
        self._disconnect_all_roombas()
        all_roombas = self._get_password.read_config_file()
        if not all_roombas:
            self._logger.warning('No roomba or config file defined, please run discovery from plugin page')
            await self._jeedom_publisher.send_to_jeedom({'msg': "NO_ROOMBA"})
        else:
            for ip in all_roombas.keys():
                data = all_roombas[ip]
                if data.get('blid') in self._config.excluded_blid:
                    self._logger.debug("Exclude blid: %s", data.get('blid'))
                    continue

                new_roomba = Roomba(address=ip, file=self._roomba_configFile)
                new_roomba.setup_mqtt_client(self._config.host, self._config.port, self._config.user, self._config.password, self._config.topic_prefix+'/feedback', self._config.topic_prefix+'/command', self._config.topic_prefix+'/setting')
                new_roomba.connect()
                self._logger.info("Try to connect to iRobot %s with ip %s", new_roomba.roombaName, new_roomba.address)
                self._roombas[new_roomba.address] = new_roomba

    def on_stop(self):
        self._disconnect_all_roombas()

    def _disconnect_all_roombas(self):
        roomba: Roomba
        for roomba in self._roombas.values():
            roomba.disconnect()
        self._roombas = {}

    async def on_message(self, message: list):
        if message['action'] == 'discover':
            try:
                self._get_password.login = message['login']
                self._get_password.password = message['password']
                self._get_password.address = message['address']
                result = self._get_password.get_password()
                await self._jeedom_publisher.send_to_jeedom({'discover': result})
            except Exception as e:
                self._logger.error('Error during discovery: %s', e)



kroomba().run()
