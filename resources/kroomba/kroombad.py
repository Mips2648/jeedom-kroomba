import os

from irobot.irobot import iRobot
from irobot.password import Password

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
        self._robots: dict[str, iRobot] = {}

    async def on_start(self):
        all_robots = self._get_password.read_config_file()
        if not all_robots:
            self._logger.warning('No robot or config file defined, please run discovery from plugin page')
            await self.send_to_jeedom({'msg': "NO_ROOMBA"})
        else:
            for ip in all_robots.keys():
                data = all_robots[ip]
                if data.get('blid') in self._config.excluded_blid:
                    self._logger.debug("Exclude blid: %s", data.get('blid'))
                    continue

                new_roomba = iRobot(address=ip, file=self._roomba_configFile)
                new_roomba.setup_mqtt_client(
                    self._config.host,
                    self._config.port,
                    self._config.user,
                    self._config.password,
                    brokerFeedback=self._config.topic_prefix+'/feedback',
                    brokerCommand=self._config.topic_prefix+'/command',
                    brokerSetting=self._config.topic_prefix+'/setting'
                )
                self._logger.info("Try to connect to iRobot %s with ip %s", new_roomba.roombaName, new_roomba.address)
                await new_roomba.async_connect()
                self._robots[new_roomba.address] = new_roomba

    async def on_stop(self):
        for robot in self._robots.values():
            robot.disconnect()
        self._robots.clear()

    async def on_message(self, message: list):
        if message['action'] == 'discover':
            try:
                self._get_password.login = message['login']
                self._get_password.password = message['password']
                self._get_password.address = message['address']
                result = self._get_password.get_password()
                await self.send_to_jeedom({'discover': result})
            except Exception as e:
                self._logger.error('Error during discovery: %s', e)


kroomba().run()
