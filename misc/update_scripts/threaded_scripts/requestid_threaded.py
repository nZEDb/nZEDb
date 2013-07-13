#!/usr/bin/python
# -*- coding: utf-8 -*-

import sys, os, time
import threading
try:
	import queue
except ImportError:
	import Queue as queue
try:
	import cymysql as mdb
except ImportError:
	sys.exit("\nPlease install cymysql for python 3, \ninformation can be found in INSTALL.txt\n")
import subprocess
import string
import lib.info as info
import signal
import datetime

threads = 10
start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))
conf = info.readConfig()

#create the connection to mysql
con = None
con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], passwd=conf['DB_PASSWORD'], db=conf['DB_NAME'], port=int(conf['DB_PORT']), unix_socket=conf['DB_SOCKET'])
con.autocommit(True)
cur = con.cursor()

cur.execute("UPDATE releases SET reqidstatus = -1 WHERE reqidstatus = 0 AND nzbstatus = 1 AND relnamestatus = 1 AND name REGEXP '^\\[[[:digit:]]+\\]' = 0")
cur.execute("SELECT r.ID, r.name, g.name groupName FROM releases r LEFT JOIN groups g ON r.groupID = g.ID WHERE relnamestatus = 1 AND nzbstatus = 1 AND reqidstatus = 0 AND r.name REGEXP '^\\[[[:digit:]]+\\]' = 1")
datas = cur.fetchall()

#close connection to mysql
cur.close()
con.close()

if not datas:
	print("No Work to Process")
	sys.exit()

my_queue = queue.Queue()
time_of_last_run = time.time()

class queue_runner(threading.Thread):
	def __init__(self, my_queue):
		threading.Thread.__init__(self)
		self.my_queue = my_queue

	def run(self):
		global time_of_last_run

		while True:
			try:
				my_id = self.my_queue.get(True, 1)
			except:
				if time.time() - time_of_last_run > 3:
					return
			else:
				if my_id:
					time_of_last_run = time.time()
					subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/requestID.php", ""+my_id])
					time.sleep(.05)
					self.my_queue.task_done()

def main():
	global time_of_last_run
	time_of_last_run = time.time()

	def signal_handler(signal, frame):
		sys.exit(0)

	signal.signal(signal.SIGINT, signal_handler)

	if True:
		#spawn a pool of place worker threads
		for i in range(threads):
			p = queue_runner(my_queue)
			p.setDaemon(False)
			p.start()


	#now load some arbitrary jobs into the queue
	for release in datas:
		my_queue.put("%s                       %s                       %s" % (release[0], release[1], release[2]))

	my_queue.join()

if __name__ == '__main__':
	main()
