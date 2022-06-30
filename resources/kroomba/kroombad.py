import logging
import argparse
import sys
import os
import signal
import json
import asyncio
import time
import aiohttp

from config import Config
from jeedom.jeedom import jeedom_utils, jeedom_socket, JEEDOM_SOCKET_MESSAGE

from roomba.roomba import Roomba
from roomba.password import Password


class kroomba:
    def __init__(self, config: Config) -> None:
        self._config = config
        self._listen_task = None
        self._jeedom_session = None
        self._jeedomSocket = jeedom_socket(port=self._config.socketport, address='localhost')

        basedir = os.path.dirname(__file__)
        self._roomba_configFile = os.path.abspath(basedir + '/../../data/config.ini')
        self._get_password = Password(file=self._roomba_configFile)
        self._roombas = {}

    async def main(self):
        self._jeedom_session = aiohttp.ClientSession()
        if not await self.__test_callback():
            await self._jeedom_session.close()
            return

        self.connect_all_roombas()
        await self._listen()
        await self._jeedom_session.close()

    def close(self):
        # self._listen_task.cancel()
        self.disconnect_all_roombas()

    def connect_all_roombas(self):
        self.disconnect_all_roombas()
        all_roombas = self._get_password.read_config_file()
        if not all_roombas:
            _LOGGER.warning('No roomba or config file defined, please run discovery from plugin page')
            tmp = {}
            tmp["msg"] = "NO_ROOMBA"
            asyncio.get_event_loop().create_task(self.__send_async(tmp))
        else:
            _LOGGER.debug(all_roombas)
            for ip in all_roombas.keys():
                new_roomba = Roomba(address=ip, file=self._roomba_configFile)
                new_roomba.setup_mqtt_client(self._config.host, self._config.port, self._config.user, self._config.password, self._config.topic_prefix+'/feedback', self._config.topic_prefix+'/command', self._config.topic_prefix+'/setting')
                new_roomba.connect()
                _LOGGER.info("Roomba %s with ip %s connected", new_roomba.roombaName, new_roomba.address)
                self._roombas[new_roomba.blid] = new_roomba

    def disconnect_all_roombas(self):
        for roomba in self._roombas.values():
            roomba.disconnect()
        self._roombas = {}

    async def _read_socket(self):
        global JEEDOM_SOCKET_MESSAGE
        if not JEEDOM_SOCKET_MESSAGE.empty():
            _LOGGER.debug("Message received in socket JEEDOM_SOCKET_MESSAGE")
            message = json.loads(JEEDOM_SOCKET_MESSAGE.get().decode('utf-8'))
            if message['apikey'] != _apikey:
                _LOGGER.error('Invalid apikey from socket : %s', str(message))
                return
            try:
                if message['action'] == 'discover':
                    try:
                        self._get_password.login = message['login']
                        self._get_password.password = message['password']
                        result = self._get_password.get_password()
                        response = {}
                        response["discover"] = result
                        await self.__send_async(response)
                    except Exception as e:
                        _LOGGER.error('Error during discovery: %s', e)
            except Exception as e:
                _LOGGER.error('Send command to demon error: %s', e)

    async def _listen(self):
        _LOGGER.info("Start listening")
        self._jeedomSocket.open()
        try:
            while 1:
                await asyncio.sleep(0.05)
                await self._read_socket()
        except KeyboardInterrupt:
            _LOGGER.info("End listening")
            shutdown()
        except asyncio.CancelledError:
            _LOGGER.info("listening cancelled")

    async def __test_callback(self):
        try:
            async with self._jeedom_session.get(self._config.callbackUrl + '?test=1&apikey=' + self._config.apiKey) as resp:
                if resp.status != 200:
                    _LOGGER.error("Please check your network configuration page: %s-%s", resp.status, resp.reason)
                    return False
        except Exception as e:
            _LOGGER.error('Callback error: %s. Please check your network configuration page', e)
            return False
        return True

    async def __send_async(self, payload):
        _LOGGER.debug('Send to jeedom :  %s', payload)
        async with self._jeedom_session.post(self._config.callbackUrl + '?apikey=' + self._config.apiKey, json=payload) as resp:
            if resp.status != 200:
                _LOGGER.error('Error on send request to jeedom, return %s-%s', resp.status, resp.reason)

# ----------------------------------------------------------------------------


def handler(signum=None, frame=None):
    _LOGGER.debug("Signal %i caught, exiting..." % int(signum))
    irobot.close()


def shutdown():
    _LOGGER.info("Shuting down")

    try:
        _LOGGER.debug("Removing PID file " + str(_pidfile))
        os.remove(_pidfile)
    except:
        pass

    _LOGGER.debug("Exit 0")
    sys.stdout.flush()
    os._exit(0)

# ----------------------------------------------------------------------------


_log_level = "error"
_pidfile = '/tmp/kroombad.pid'
_apikey = ''
_LOGGER = logging.getLogger(__name__)

parser = argparse.ArgumentParser(description='kroombad Daemon for Jeedom plugin')
parser.add_argument("--loglevel", help="Log Level for the daemon", type=str)
parser.add_argument("--host", help="mqtt host ip", type=str)
parser.add_argument("--port", help="mqtt host port", type=int)
parser.add_argument("--user", help="mqtt username", type=str)
parser.add_argument("--password", help="mqtt password", type=str)
parser.add_argument("--topic_prefix", help="topic_prefix", type=str)
parser.add_argument("--socketport", help="Socket Port", type=int)
parser.add_argument("--callback", help="Jeedom callback url", type=str)
parser.add_argument("--apikey", help="Plugin API Key", type=str)
parser.add_argument("--pid", help="daemon pid", type=str)

args = parser.parse_args()

_log_level = args.loglevel
_pidfile = args.pid
_apikey = args.apikey

# jeedom_utils.set_log_level(_log_level)
_LOGGER.setLevel(jeedom_utils.convert_log_level(_log_level))
logging.getLogger('asyncio').setLevel(logging.WARNING)
logging.getLogger('Roomba').setLevel(jeedom_utils.convert_log_level(_log_level))

signal.signal(signal.SIGINT, handler)
signal.signal(signal.SIGTERM, handler)

try:
    _LOGGER.info('Starting daemon')
    config = Config(**vars(args))

    _LOGGER.info('Log level: %s', _log_level)
    _LOGGER.debug('Socket port : %s', config.socketport)
    _LOGGER.debug('PID file : '+str(_pidfile))
    jeedom_utils.write_pid(str(_pidfile))

    irobot = kroomba(config)
    asyncio.get_event_loop().run_until_complete(irobot.main())
except Exception as e:
    _LOGGER.error('Fatal error: %s', e)
shutdown()
