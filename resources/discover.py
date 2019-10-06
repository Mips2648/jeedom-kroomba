from __future__ import print_function
from pprint import pformat
import socket, traceback
import json
import time

UDP_IP = "255.255.255.255"
UDP_PORT = 5678
MESSAGE = "irobotmcs"
TIMEOUT = 10

#print("UDP target IP:", UDP_IP)
#print("UDP target port:", UDP_PORT)
#print("message:", MESSAGE)

sock = socket.socket(socket.AF_INET, # Internet
                     socket.SOCK_DGRAM) # UDP
#sock.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
sock.setsockopt(socket.SOL_SOCKET, socket.SO_BROADCAST, 1)
sock.bind(('',5678))
sock.settimeout(TIMEOUT)
sock.sendto(MESSAGE.encode(), (UDP_IP, UDP_PORT))

start = time.time()

try:
    while 1:
        try:
            udp_data, address = sock.recvfrom(1024)
            if udp_data.decode() != MESSAGE:
                response = json.loads(udp_data.decode())
                hostname = response["hostname"].split('-')
                if hostname[0] == 'Roomba' or hostname[0] == 'iRobot':  #for i7 robot name is now iRobot
                    blid = hostname[1]
                print("IP:{0},blid:{1}".format(response['ip'],blid))
        except (KeyboardInterrupt, SystemExit):
            raise
#        except:
#            traceback.print_exc()
except:
    pass
finally:
    sock.close()
