#!/usr/bin/env python
# -*- coding: utf-8 -*-

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
import math

import lib.info as info
from lib.info import bcolors
conf = info.readConfig()

def connect():
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
	con.autocommit(True)
	cur = con.cursor()
	return cur, con

def disconnect(cur, con):
	con.close()
	con = None
	cur.close()
	cur = None

start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))

print(bcolors.HEADER + "\n\nGrabNZBs Threaded Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)

#get array of collectionhash
cur = connect()
cur[0].execute("SELECT (SELECT value FROM site WHERE setting = 'grabnzbs') AS a, (SELECT value FROM site WHERE setting = 'delaytime') AS b, (SELECT value FROM site WHERE setting = 'maxgrabnzbs') AS c")
dbgrab = cur[0].fetchall()
grab = int(dbgrab[0][0])
delay = int(dbgrab[0][1])
maxnzb = dbgrab[0][2]

if grab == 0:
	print(bcolors.ERROR + "GrabNZBs is disabled" + bcolors.ENDC)
	sys.exit()

#delete from nzbs where size greater than x
cur[0].execute("SELECT collectionhash FROM nzbs GROUP BY collectionhash, totalparts HAVING COUNT(*) > "+maxnzb)
delnzbs = cur[0].fetchall()
for delnzb in delnzbs:
	cur[0].execute("DELETE FROM nzbs WHERE collectionhash = '"+delnzb[0]+"'")
print(bcolors.HEADER + "Deleted %s collections exceeding %s parts from nzbs " % (len(delnzbs), maxnzb))

if conf['DB_SYSTEM'] == "mysql":
	run = "SELECT collectionhash FROM nzbs GROUP BY collectionhash, totalparts HAVING COUNT(*) >= totalparts UNION SELECT DISTINCT(collectionhash) FROM nzbs WHERE dateadded < NOW() - INTERVAL %s HOUR"
elif conf['DB_SYSTEM'] == "pgsql":
	run = "SELECT collectionhash FROM nzbs GROUP BY collectionhash, totalparts HAVING COUNT(*) >= totalparts UNION SELECT DISTINCT(collectionhash) FROM nzbs WHERE dateadded < NOW() - INTERVAL '%s HOURS'"
cur[0].execute(run, (delay))
datas = cur[0].fetchall()
if len(datas) == 0:
	print(bcolors.ERROR + "No NZBs to Grab\n" + bcolors.ENDC)
	sys.exit()

#get threads for update_binaries
cur[0].execute("SELECT value FROM site WHERE setting = 'grabnzbthreads'")
run_threads = cur[0].fetchone()
disconnect(cur[0], cur[1])

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
					subprocess.call(["php", pathname+"/../nix/tmux/bin/grabnzbs.php", ""+my_id])
					time.sleep(.03)
					self.my_queue.task_done()

def main():
	global time_of_last_run
	time_of_last_run = time.time()

	print(bcolors.HEADER + "We will be using a max of {} threads, a queue of {} nzbs".format(run_threads[0], "{:,}".format(len(datas))) + bcolors.ENDC)
	print(bcolors.HEADER + "+ = nzb imported, - = probably not nzb, ! = duplicate, f = download failed" + bcolors.ENDC)
	time.sleep(2)

	def signal_handler(signal, frame):
		sys.exit(0)

	signal.signal(signal.SIGINT, signal_handler)

	if True:
		#spawn a pool of place worker threads
		for i in range(int(run_threads[0])):
			p = queue_runner(my_queue)
			p.setDaemon(False)
			p.start()

	#now load some arbitrary jobs into the queue
	for gnames in datas:
		time.sleep(.03)
		my_queue.put(gnames[0])

	my_queue.join()

	print(bcolors.HEADER + "\n\nPopulate nzb_guids Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)
	final = "limited"
	subprocess.call(["php", pathname+"/../../testing/DB/populate_nzb_guid.php", ""+final])
	print(bcolors.HEADER + "\n\nPopulate nzb_guids Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)
	print(bcolors.HEADER + "\n\nGrabNZBs Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)
	print(bcolors.HEADER + "Running time: {}".format(str(datetime.timedelta(seconds=time.time() - start_time))) + bcolors.ENDC)

if __name__ == '__main__':
	main()
