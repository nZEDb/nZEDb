#!/usr/bin/python
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
cur = con.cursor()

if sys.argv[1] != "nfo" and sys.argv[1] != "filename" and sys.argv[1] != "md5" and sys.argv[1] != "par2":
	sys.exit("\nAn invalid argument was supplied\npostprocess_threaded.py [md5, nfo, filename, par2]\n")

print("\nfixReleasesNames {} Threaded Started at {}".format(sys.argv[1],datetime.datetime.now().strftime("%H:%M:%S")))
if len(sys.argv) == 1:
	sys.exit("\nAn argument is required\npostprocess_threaded.py [md5, nfo, filename, par2]\n")

start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))

cur.execute("SELECT value FROM site WHERE setting = 'fixnamethreads'")
run_threads = cur.fetchone()
cur.execute("SELECT value FROM site WHERE setting = 'fixnamesperrun'")
perrun = cur.fetchone()

datas = []
maxtries = 0

if len(sys.argv) > 1 and (sys.argv[1] == "nfo"):
	run = "SELECT DISTINCT rel.id AS releaseid FROM releases rel INNER JOIN releasenfo nfo ON (nfo.releaseid = rel.id) WHERE categoryid != 5070 AND rel.relnamestatus in (0, 1, 21, 22) LIMIT %s"
	cur.execute(run, (int(perrun[0]) * int(run_threads[0])))
	datas = cur.fetchall()
elif len(sys.argv) > 1 and (sys.argv[1] == "filename"):
	run = "SELECT DISTINCT rel.id AS releaseid FROM releases rel INNER JOIN releasefiles relfiles ON (relfiles.releaseid = rel.id) WHERE categoryid != 5070 AND rel.relnamestatus in (0, 1, 20, 22) LIMIT %s"
	cur.execute(run, (int(perrun[0]) * int(run_threads[0])))
	datas = cur.fetchall()
elif len(sys.argv) > 1 and (sys.argv[1] == "md5"):
	while len(datas) == 0 and maxtries >= -5:
		run = "SELECT DISTINCT rel.id FROM releases rel LEFT JOIN releasefiles rf ON rel.id = rf.releaseid WHERE rel.dehashstatus <= 0 AND rel.dehashstatus >= %s AND rel.relnamestatus in (0, 1, 20, 21, 22) AND rel.passwordstatus >= -1 AND (rel.hashed=true OR rf.name REGEXP'[a-fA-F0-9]{32}') LIMIT %s"
		cur.execute(run, (maxtries, int(perrun[0])*int(run_threads[0])))
		datas = cur.fetchall()
		maxtries = maxtries - 1
elif len(sys.argv) > 1 and (sys.argv[1] == "par2"):
	#This one does from oldest posts to newest posts, since nfo pp does same thing but newest to oldest
	run = "SELECT id AS releaseid, guid, groupid FROM releases WHERE categoryid = 7010 AND relnamestatus IN (0, 1, 20, 21) LIMIT %s"
	cur.execute(run, (int(perrun[0]) * int(run_threads[0])))
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
					subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/fixreleasenames.php", ""+my_id])
					time.sleep(.05)
					self.my_queue.task_done()

def main():
	global time_of_last_run
	time_of_last_run = time.time()

	print("We will be using a max of {} threads, a queue of {} releases using {}".format(run_threads[0], "{:,}".format(len(datas)), sys.argv[1]))
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
	if sys.argv[1] == "nfo":
		for release in datas:
			time.sleep(.1)
			my_queue.put("%s %s" % ("nfo", release[0]))
	elif sys.argv[1] == "filename":
		for release in datas:
			time.sleep(.1)
			my_queue.put("%s %s" % ("filename", release[0]))
	elif sys.argv[1] == "md5":
		for release in datas:
			time.sleep(.1)
			my_queue.put("%s %s" % ("md5", release[0]))
	elif sys.argv[1] == "par2":
		for release in datas:
			time.sleep(.1)
			my_queue.put("%s %s %s %s" % ("par2", release[0], release[1], release[2]))

	my_queue.join()

	print("\nfixReleaseNames {} Threaded Completed at {}".format(sys.argv[1],datetime.datetime.now().strftime("%H:%M:%S")))
	print("Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))))

if __name__ == '__main__':
	main()
