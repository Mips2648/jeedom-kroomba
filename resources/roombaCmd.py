from __future__ import print_function
import os
import sys
import time
import logging
from roomba import Roomba

logging.basicConfig(level=logging.DEBUG)

if len(sys.argv) < 5:
  print("Usage: roombaCmd.py <command> <ip> <username> <password>")
  exit()

os.chdir('./roomba')

cmd = sys.argv[1]
ip = sys.argv[2]
username = sys.argv[3]
password = sys.argv[4]

print("Roomba(" + ip + "," + username + "," + password + ")")
myroomba = Roomba(ip,username,password)

print("myroomba.send_command(" + cmd + ")")
myroomba.connect()
for i in range(10):
    time.sleep(1)
    if myroomba.roomba_connected:
        break
if myroomba.roomba_connected:
    myroomba.send_command(cmd)
    time.sleep(1)
else:
    print("Connection error")
myroomba.disconnect()
