#!/usr/bin/env python
# -*- coding: utf-8 -*-

from __future__ import print_function
import sys, os, time
import threading
try:
	import queue
except ImportError:
	import Queue as queue
import subprocess
import string
import signal
import datetime

import lib.info as info
from lib.info import bcolors
conf = info.readConfig()
con = None
if conf['DB_SYSTEM'] == "mysql":
	try:
		import cymysql as mdb
		con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], passwd=conf['DB_PASSWORD'], db=conf['DB_NAME'], port=int(conf['DB_PORT']), unix_socket=conf['DB_SOCKET'], charset="utf8")
	except ImportError:
		print(bcolors.ERROR + "\nPlease install cymysql for python 3, \ninformation can be found in INSTALL.txt\n" + bcolors.ENDC)
elif conf['DB_SYSTEM'] == "pgsql":
	try:
		import psycopg2 as mdb
		con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], password=conf['DB_PASSWORD'], dbname=conf['DB_NAME'], port=int(conf['DB_PORT']))
	except ImportError:
		print(bcolors.ERROR + "\nPlease install psycopg for python 3, \ninformation can be found in INSTALL.txt\n" + bcolors.ENDC)
cur = con.cursor()

print(bcolors.HEADER + "\nBinaries Threaded Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)

start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))

#get active groups
if len(sys.argv) == 2:
	cur.execute("SELECT name FROM groups WHERE name = '" + sys.argv[1] + "'")
else:
	cur.execute("SELECT name FROM groups WHERE active = 1")
datas = cur.fetchall()
if len(datas) == 0:
	print(bcolors.ERROR + "No Active Groups" + bcolors.ENDC)

cur.execute("SELECT (SELECT value FROM site WHERE setting = 'binarythreads') AS a, (SELECT value FROM site WHERE setting = 'hashcheck') AS b")
dbgrab = cur.fetchall()
run_threads = int(dbgrab[0][0])
hashcheck = int(dbgrab[0][1])
if hashcheck == 0:
	print(bcolors.ERROR + "We have updated the way collections are created, the collection table has to be updated to use the new changes.\nphp misc/testing/DB/reset_Collections.php true" + bcolors.ENDC)

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
				my_id = self.my_queue.get(True, .5)
			except:
				if time.time() - time_of_last_run > 3:
					return
			else:
				if my_id:
					time_of_last_run = time.time()
					subprocess.call(["php", pathname+"/../update_binaries.php", ""+my_id])
					self.my_queue.task_done()

def main():
	global time_of_last_run
	time_of_last_run = time.time()

	print(bcolors.HEADER + "We will be using a max of {} threads, a queue of {} groups".format(run_threads, "{:,}".format(len(datas))) + bcolors.ENDC)
	time.sleep(2)

	def signal_handler(signal, frame):
		sys.exit(0)

	signal.signal(signal.SIGINT, signal_handler)

	if True:
		#spawn a pool of worker threads
		for i in range(run_threads):
			p = queue_runner(my_queue)
			#p.setDaemon(False)
			p.start()

	#now load some arbitrary jobs into the queue
	for gnames in datas:
		time.sleep(.03)
		my_queue.put(gnames[0])

	my_queue.join()

	print(bcolors.HEADER + "\nBinaries Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)
	print(bcolors.HEADER + "Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))) + bcolors.ENDC)

if __name__ == '__main__':
	main()
