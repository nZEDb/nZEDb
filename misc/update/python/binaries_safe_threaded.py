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
			if conf['DB_PORT'] != '':
				con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], passwd=conf['DB_PASSWORD'], db=conf['DB_NAME'], port=int(conf['DB_PORT']), unix_socket=conf['DB_SOCKET'], charset="utf8")
			else:
				con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], passwd=conf['DB_PASSWORD'], db=conf['DB_NAME'], unix_socket=conf['DB_SOCKET'], charset="utf8")
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
	return cur, con

def disconnect(cur, con):
	con.close()
	con = None
	cur.close()
	cur = None

start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))

print(bcolors.HEADER + "\nBinaries Safe Threaded Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)

count = 0

#get values from db
cur = connect()
cur[0].execute("SELECT (SELECT value FROM site WHERE setting = 'binarythreads') AS a, (SELECT value FROM site WHERE setting = 'maxmssgs') AS b, (SELECT value FROM site WHERE setting = 'hashcheck') AS c")
dbgrab = cur[0].fetchall()
disconnect(cur[0], cur[1])
run_threads = int(dbgrab[0][0])
maxmssgs = int(dbgrab[0][1])
hashcheck = int(dbgrab[0][2])

if hashcheck == 0:
	print(bcolors.ERROR + "We have updated the way collections are created, the collection table has to be updated to use the new changes.\nphp misc/testing/DB/reset_Collections.php true" + bcolors.ENDC)
	sys.exit()

#before we get the groups, lets update shortgroups
subprocess.call(["php", pathname+"/../nix/tmux/bin/update_groups.php", ""])

#query to grab all active groups
cur = connect()
cur[0].execute("SELECT g.name AS groupname, g.last_record AS our_last, a.last_record AS their_last FROM groups g INNER JOIN shortgroups a ON g.active = 1 AND g.name = a.name ORDER BY a.last_record DESC")
datas = cur[0].fetchall()
disconnect(cur[0], cur[1])

if not datas:
	print(bcolors.ERROR + "No Groups activated" + bcolors.ENDC)
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
					subprocess.call(["php", pathname+"/../nix/tmux/bin/safe_pull.php", ""+my_id])
					time.sleep(.03)
					self.my_queue.task_done()

def main():
	global time_of_last_run
	time_of_last_run = time.time()

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
	run = 0
	finals = []
	groups = []
	s = name = ""
	for group in datas:
		time.sleep(.03)
		#start new groups using binaries.php, no need to check nntp
		if group[1] == 0:
			run += 1
			my_queue.put("binupdate %s" % (group[0]))
			time.sleep(0.01)
		elif group[1] != 0:
			#only process if more that 20k headers available and skip the first 20k
			count = group[2] - group[1] - 20000
			#run small groups using binaries.php
			if count <= maxmssgs * 2:
				run += 1
				my_queue.put("binupdate %s" % (group[0]))
			#thread large groups using backfill.php
			else:
				my_queue.put("%s %s" % (group[0], 'partrepair'))
				geteach = math.floor(count / maxmssgs)
				remaining = count - geteach * maxmssgs
				for loop in range(int(geteach)):
					run += 1
					my_queue.put("%s %s %s %s" % (group[0], group[1] + loop * maxmssgs + maxmssgs, group[1] + loop * maxmssgs + 1, run))
				run += 1
				my_queue.put("%s %s %s %s" % (group[0], group[1] + (loop + 1) * maxmssgs + remaining + 1, group[1] + (loop + 1) * maxmssgs + 1, run))
				groups.append(group[0])
				finals.append(int(group[2]))

	my_queue.join()

	for group in list(zip(groups, finals)):
		final = ("{} {} Binary".format(group[0], group[1]))
		subprocess.call(["php", pathname+"/../nix/tmux/bin/safe_pull.php", ""+str(final)])

	print(bcolors.HEADER + "\nBinaries Safe Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)
	print(bcolors.HEADER + "Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))) + bcolors.ENDC)

if __name__ == '__main__':
	main()
