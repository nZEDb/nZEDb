#!/usr/bin/python
# -*- coding: utf-8 -*-

from __future__ import print_function
import sys, os, time
import threading
import subprocess
import string
import signal
import datetime
import re
try:
	import queue
except ImportError:
	import Queue as queue

import lib.info as info
conf = info.readConfig()
con = None
if conf['DB_SYSTEM'] == "mysql":
	try:
		import cymysql as mdb
		con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], passwd=conf['DB_PASSWORD'], db=conf['DB_NAME'], port=int(conf['DB_PORT']), unix_socket=conf['DB_SOCKET'])
	except ImportError:
		sys.exit("\nPlease install cymysql for python 3, \ninformation can be found in INSTALL.txt\n")
elif conf['DB_SYSTEM'] == "pgsql":
	try:
		import psycopg2 as mdb
		con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], password=conf['DB_PASSWORD'], dbname=conf['DB_NAME'], port=int(conf['DB_PORT']))
	except ImportError:
		sys.exit("\nPlease install psycopg for python 3, \ninformation can be found in INSTALL.txt\n")
con.autocommit(True)
cur = con.cursor()

print("\n\nRequestID Threaded Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")))

threads = 5
start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))

#cur.execute("UPDATE releases SET reqidstatus = -1 WHERE reqidstatus = 0 AND nzbstatus = 1 AND relnamestatus in (0, 1, 20, 21, 22) AND name REGEXP '^\\[[[:digit:]]+\\]' = 0")
cur.execute("SELECT r.id, r.name, g.name AS groupname FROM releases r LEFT JOIN groups g ON r.groupid = g.id WHERE relnamestatus in (20, 21, 22) AND nzbstatus = 1 AND reqidstatus in (0, -1) AND r.name REGEXP '^\\[[[:digit:]]+\\]' = 1 limit 100")
datas = cur.fetchall()

if not datas:
	print("No Work to Process")
	sys.exit()

#close connection to mysql
cur.close()
con.close()

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
		print("We will be using a max of {} threads, a queue of {} items".format(threads, "{:,}".format(len(datas))))
		time.sleep(2)

		#spawn a pool of place worker threads
		for i in range(threads):
			p = queue_runner(my_queue)
			p.setDaemon(False)
			p.start()

	#now load some arbitrary jobs into the queue
	for release in datas:
		time.sleep(.1)
		my_queue.put("%s                       %s                       %s" % (release[0], release[1], release[2]))

	my_queue.join()

	print("\nRequestID Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")))
	print("Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))))

if __name__ == '__main__':
	main()
