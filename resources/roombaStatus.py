from __future__ import print_function
import os
import sys
import time
import logging
import json
from roomba import Roomba

#logging.basicConfig(level=logging.DEBUG)

if len(sys.argv) < 4:
  print("Usage: roombaStatus.py <ip> <username> <password>")
  exit()

os.chdir('./roomba')

ip = sys.argv[1]
username = sys.argv[2]
password = sys.argv[3]

#print("Roomba(" + ip + "," + username + "," + password + ")")
myroomba = Roomba(ip,username,password)

myroomba.connect()
for i in range(10):
    time.sleep(1)
    if myroomba.roomba_connected:
        break
if myroomba.roomba_connected:
    print(json.dumps(myroomba.master_state))
    time.sleep(1)
else:
    print("Connection error")
myroomba.disconnect()
