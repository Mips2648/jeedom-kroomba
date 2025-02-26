#!/usr/bin/env python3
# -*- coding: utf-8 -*-

from __future__ import annotations

import asyncio
from pathlib import Path
from pprint import pformat
import json
import logging
import socket
import ssl
from ast import literal_eval
import configparser
import requests

from .const import BROADCAST_IP, DEFAULT_TIMEOUT


class iRobotConfig(object):
    def __init__(self, blid: str, data: dict):
        self.__blid: str = blid
        self.__data: dict = data

        self.__password: str | None = self.__data.get('password', None)
        self.__ip: str = str(self.__data.get('ip', ''))
        self.__name: str = str(self.__data.get('robotname', 'unknown'))

    @property
    def blid(self):
        return self.__blid

    @property
    def password(self):
        return self.__password

    @password.setter
    def password(self, value):
        self.__password = value

    @property
    def ip(self):
        return self.__ip

    @ip.setter
    def ip(self, value):
        self.__ip = value

    @property
    def name(self):
        return self.__name

    @name.setter
    def name(self, value: str):
        self.__name = value

    @property
    def version(self):
        return int(self.__data.get('ver', 3))

    def toJSON(self):
        return self.__data


class iRobotConfigs:
    '''
    Manage the configuration of the iRobot devices
    '''

    config_dicts = ['data', 'mapsize', 'pmaps', 'regions']

    def __init__(self, path: Path):
        self.__path = path
        self._logger = logging.getLogger()

        ini_file = self.__path/'config.ini'
        self.__json_file = self.__path/'config.json'

        self.__robots: dict[str, iRobotConfig] = {}

        if ini_file.exists():
            self.__convert_config(ini_file)
            ini_file.unlink()

        self.__load_config()

    def __convert_config(self, file: Path):
        Config = configparser.ConfigParser()
        old_configs = {}
        try:
            Config.read(file)
            self._logger.info("convert config file %s", file)
            old_configs = {s: {k: literal_eval(v) if k in self.config_dicts else v for k, v in Config.items(s)} for s in Config.sections()}
        except Exception as e:
            self._logger.exception(e)

        new_configs = {}
        for ip, value in old_configs.items():
            if value['blid'] in new_configs.keys():
                continue
            new_configs[value['blid']] = iRobotConfig(value['blid'], value['data']).toJSON()

        self.__json_file.write_text(json.dumps(new_configs, indent=2), encoding='utf-8')

    def __load_config(self):
        if self.__json_file.exists():
            self._logger.info("Load config file %s", self.__json_file)
            configs = json.loads(self.__json_file.read_text(encoding='utf-8'))
            for blid, data in configs.items():
                self.__robots[blid] = iRobotConfig(blid, data)

    def __save_config_file(self):
        data = {}
        for robot in self.__robots.values():
            data[robot.blid] = robot.toJSON()
        self.__json_file.write_text(json.dumps(data, indent=2), encoding='utf-8')
        return True

    @property
    def robots(self):
        return self.__robots

    async def __receive_udp(self, timeout: int = DEFAULT_TIMEOUT, address: str = BROADCAST_IP):
        # set up UDP socket to receive data from robot
        port = 5678
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.settimeout(timeout)
        if address == BROADCAST_IP:
            s.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
        s.bind(("", port))  # bind all interfaces to port
        self._logger.debug("waiting on port: %s for data", port)
        message = 'irobotmcs'
        s.sendto(message.encode(), (address, port))
        configs: dict[str, iRobotConfig] = {}
        while True:
            try:
                udp_data, addr = s.recvfrom(1024)  # wait for udp data
                if udp_data and udp_data.decode() != message:
                    try:
                        parsedMsg = json.loads(udp_data.decode())
                        blid = self.__parse_blid(parsedMsg)
                        if blid not in configs.keys():
                            s.sendto(message.encode(), (address, port))
                            self._logger.debug('Robot at IP: %s Data: %s', addr[0], json.dumps(parsedMsg))
                            new_robot = iRobotConfig(blid, parsedMsg)
                            if new_robot.version < 2:
                                self._logger.warning("%s robot at address %s does not have the correct firmware version. Your version info is: %s", new_robot.name, new_robot.ip, new_robot.version)
                                continue
                            self._logger.info("Found robot %s at IP %s", new_robot.name, new_robot.ip)
                            configs[blid] = new_robot
                    except Exception as e:
                        self._logger.info("json decode error: %s", e)
                        self._logger.info('RECEIVED: %s', pformat(udp_data))

            except socket.timeout:
                break
        s.close()
        return configs

    def __parse_blid(self, payload: dict):
        return payload.get('robotid', payload.get("hostname", "").split('-')[1])

    async def discover(self, address: str = BROADCAST_IP, cloud_login: str = None, cloud_password: str = None):
        '''
        Discover robots on the network, retrieve their password from the cloud if not already known and save the configuration
        '''
        self._logger.info("Discovering robots on network...")

        discovered_robots = await self.__receive_udp(timeout=15, address=address)
        if len(discovered_robots) == 0:
            if address == BROADCAST_IP:
                self._logger.warning("No robots found on network, make sure your robots are powered on (green lights on) and connected on the same network then try again...")
            return False
        self._logger.info("Found %i robots on network", len(discovered_robots))

        robots_with_missing_pswd: dict[str, iRobotConfig] = {}

        for robot in discovered_robots.values():

            if robot.blid in self.__robots.keys():
                self._logger.info("Robot %s already configured, updating ip & name", robot.name)
                self.__robots[robot.blid].ip = robot.ip
                self.__robots[robot.blid].name = robot.name
            else:
                if robot.password is None:
                    robots_with_missing_pswd[robot.blid] = robot
                else:
                    self._logger.info("Robot %s added to configuration with password received during discovery: %s", robot.name, robot.password)
                    self.__robots[robot.blid] = robot

        if len(robots_with_missing_pswd) > 0:
            if cloud_login and cloud_password:
                try:
                    self._logger.info("Try to get missing robots password from cloud...")
                    cloud_data = await self.__get_passwords_from_cloud(cloud_login, cloud_password)
                except requests.HTTPError as e:
                    self._logger.error("Error getting cloud data: %s", e)
                else:
                    self._logger.debug("Got cloud data: %s", json.dumps(cloud_data))
                    self._logger.info("Found %i robots defined in the cloud", len(cloud_data))
                    for id, data in cloud_data.items():
                        if id in robots_with_missing_pswd.keys():
                            robots_with_missing_pswd[id].password = data.get('password')
                            self._logger.info("Robot %s added to configuration with password from cloud", robot.name)
                            self.__robots[id] = robots_with_missing_pswd[id]
            else:
                for robot in robots_with_missing_pswd.values():
                    self._logger.info("To add/update your robot details,"
                                      "make sure your robot (%s) at IP %s is on the Home Base and "
                                      "powered on (green lights on). Then press and hold the HOME "
                                      "button on your robot until it plays a series of tones "
                                      "(about 2 seconds). Release the button and your robot will "
                                      "flash WIFI light.", robot.name, robot.ip)
                    await asyncio.sleep(10)
                    data = await self.__get_password_from_robot(robot.ip)
                    if len(data) <= 7:
                        self._logger.warning('Cannot get password for robot %s at ip %s, received %i bytes. Follow the instructions and try again.', robot.name, robot.ip, len(data))
                        continue
                    # Convert password to str
                    robot.password = str(data[7:].decode().rstrip('\x00'))
                    self._logger.info("Robot %s added to configuration with password from robot", robot.name)
                    self.__robots[robot.blid] = robot

        return self.__save_config_file()

    async def __get_password_from_robot(self, ip):
        '''
        Send MQTT magic packet to addr
        this is 0xf0 (mqtt reserved) 0x05(data length) 0xefcc3b2900 (data)
        Should receive 37 bytes containing the password for robot at addr
        This is is 0xf0 (mqtt RESERVED) length (0x23 = 35) 0xefcc3b2900 (magic packet),
        followed by 0xXXXX... (30 bytes of password). so 7 bytes, followed by 30 bytes of password
        total of 37 bytes
        Uses 10 second timeout for socket connection
        '''
        data = b''
        packet = bytes.fromhex('f005efcc3b2900')
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.settimeout(10)

        # context = ssl.SSLContext(ssl.PROTOCOL_TLS)
        context = ssl.SSLContext()
        context.check_hostname = False
        context.verify_mode = ssl.CERT_NONE
        # context.set_ciphers('DEFAULT@SECLEVEL=1:HIGH:!DH:!aNULL')
        wrappedSocket = context.wrap_socket(sock)

        try:
            wrappedSocket.connect((ip, 8883))
            self._logger.debug('Connection Successful')
            wrappedSocket.send(packet)
            self._logger.debug('Waiting for data')

            while len(data) < 37:
                data_received = wrappedSocket.recv(1024)
                data += data_received
                if len(data_received) == 0:
                    self._logger.info("socket closed")
                    break

            wrappedSocket.close()
            return data

        except socket.timeout as e:
            self._logger.error('Connection Timeout Error (for %s): %s', ip, e)
        except (ConnectionRefusedError, OSError) as e:
            if e.errno == 111:  # errno.ECONNREFUSED
                self._logger.error('Robot %s found but connection is refused, make sure nothing else is connected (app?), as only one connection at a time is allowed', ip)
            elif e.errno == 113:  # errno.No Route to Host
                self._logger.error('Unable to contact robot on ip %s; Is the ip correct?', ip)
            else:
                self._logger.error("Connection Error (for %s): %s", ip, e)
        except Exception as e:
            self._logger.exception(e)

        self._logger.error('Unable to get password from robot')
        return data

    async def __get_passwords_from_cloud(self, login: str, password: str) -> dict:
        r = requests.get("https://disc-prod.iot.irobotapi.com/v1/discover/endpoints?country_code=US")
        r.raise_for_status()
        response = r.json()
        deployment = response['deployments'][next(iter(response['deployments']))]
        self.httpBase = deployment['httpBase']
        # iotBase = deployment['httpBaseAuth']
        # iotUrl = urllib.parse.urlparse(iotBase)
        # self.iotHost = iotUrl.netloc
        # region = deployment['awsRegion']

        self.apikey = response['gigya']['api_key']
        self.gigyaBase = response['gigya']['datacenter_domain']

        data = {"apiKey": self.apikey,
                "targetenv": "mobile",
                "loginID": login,
                "password": password,
                "format": "json",
                "targetEnv": "mobile",
                }

        self._logger.debug("Post accounts.login request")
        r = requests.post("https://accounts.%s/accounts.login" % self.gigyaBase, data=data)
        r.raise_for_status()
        response = r.json()
        self._logger.debug("response: %s", response)
        '''
        data = {"timestamp": int(time.time()),
                "nonce": "%d_%d" % (int(time.time()), random.randint(0, 2147483647)),
                "oauth_token": response.get('sessionInfo', {}).get('sessionToken', ''),
                "targetEnv": "mobile"}
        '''
        uid = response['UID']
        uidSig = response['UIDSignature']
        sigTime = response['signatureTimestamp']

        data = {
            "app_id": "ANDROID-C7FB240E-DF34-42D7-AE4E-A8C17079A294",
            "assume_robot_ownership": "0",
            "gigya": {
                "signature": uidSig,
                "timestamp": sigTime,
                "uid": uid,
            }
        }

        header = {
            "Content-Type": "application/json",
            "host": "unauth1.prod.iot.irobotapi.com"
        }

        self._logger.debug("Post login request to %s with data %s", self.httpBase, data)
        r = requests.post("%s/v2/login" % self.httpBase, json=data, headers=header)
        r.raise_for_status()
        response = r.json()
        self._logger.debug("response: %s", response)
        # access_key = response['credentials']['AccessKeyId']
        # secret_key = response['credentials']['SecretKey']
        # session_token = response['credentials']['SessionToken']

        return response['robots']
