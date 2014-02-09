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
		sys.exit()
elif conf['DB_SYSTEM'] == "pgsql":
	try:
		import psycopg2 as mdb
		con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], password=conf['DB_PASSWORD'], dbname=conf['DB_NAME'], port=int(conf['DB_PORT']))
	except ImportError:
		print(bcolors.ERROR + "\nPlease install psycopg for python 3, \ninformation can be found in INSTALL.txt\n" + bcolors.ENDC)
		sys.exit()
cur = con.cursor()

print(bcolors.HEADER + "\nNZB Import Threaded Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)

start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))

#get values from db
cur.execute("SELECT value FROM tmux WHERE setting = 'import'")
use_true = cur.fetchone()
cur.execute("SELECT (SELECT value FROM site WHERE setting = 'nzbthreads') AS a, (SELECT value FROM tmux WHERE setting = 'nzbs') AS b")
dbgrab = cur.fetchall()
run_threads = int(dbgrab[0][0])
nzbs = dbgrab[0][1]

if int(use_true[0]) == 2 or ( len(sys.argv) >= 2 and sys.argv[1] == "true"):
	print(bcolors.HEADER + "We will be using filename as searchname" + bcolors.ENDC)
print(bcolors.HEADER + "Sorting Folders in {}, be patient.".format(nzbs) + bcolors.ENDC)
datas = [name for name in os.listdir(nzbs) if os.path.isdir(os.path.join(nzbs, name))]

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
					subprocess.call(["php", pathname+"/../../testing/nzb-import.php", ""+my_id])
					time.sleep(.03)
					self.my_queue.task_done()

def main(args):
	global time_of_last_run
	time_of_last_run = time.time()

	if len(datas) != 0:
		print(bcolors.HEADER + "We will be using a max of {} threads, a queue of {} folders".format(run_threads, "{:,}".format(len(datas))) + bcolors.ENDC)
	else:
		print(bcolors.HEADER + "We will be using a max of {} threads, a queue of 1 folder".format(run_threads) + bcolors.ENDC)
	if int(use_true[0]) == 2 or ( len(sys.argv) >= 2 and sys.argv[1] == "true"):
		print(bcolors.HEADER + "We will be using filename as searchname" + bcolors.ENDC)
	time.sleep(2)

	def signal_handler(signal, frame):
		sys.exit(0)

	signal.signal(signal.SIGINT, signal_handler)

	if True:
		#spawn a pool of place worker threads
		for i in range(run_threads):
			p = queue_runner(my_queue)
			p.setDaemon(False)
			p.start()

	#now load some arbitrary jobs into the queue
	if len(datas) != 0:
		if (int(use_true[0]) == 0 or int(use_true[0]) == 1) and len(sys.argv) == 1:
			for gnames in datas:
				time.sleep(.03)
				my_queue.put(os.path.join(nzbs,gnames))
		elif int(use_true[0]) == 2 or ( len(sys.argv) >= 2 and sys.argv[1] == "true"):
			for gnames in datas:
				time.sleep(.03)
				my_queue.put("%s   %s" % (os.path.join(nzbs,gnames), "true"))
	if len(datas) == 0:
		if (int(use_true[0]) == 0 or int(use_true[0]) == 1) and len(sys.argv) == 1:
			time.sleep(.03)
			my_queue.put(nzbs)
		elif int(use_true[0]) == 2 or ( len(sys.argv) >= 2 and sys.argv[1] == "true"):
			time.sleep(.03)
			my_queue.put("%s   %s" % (nzbs, "true"))

	my_queue.join()

	final = "true"
	subprocess.call(["php", pathname+"/../../testing/DB/populate_nzb_guid.php", ""+final])
	print(bcolors.HEADER + "\nNZB Import Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)
	print(bcolors.HEADER + "Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))) + bcolors.ENDC)

if __name__ == '__main__':
	main(sys.argv[1:])
