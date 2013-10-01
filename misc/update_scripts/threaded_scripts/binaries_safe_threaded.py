#!/usr/bin/python
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

print("\nBinary Safe Threaded Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")))

start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))

count = 0

#get values from db
cur.execute("SELECT (SELECT value FROM site WHERE setting = 'binarythreads') AS a, (SELECT value FROM site WHERE setting = 'maxmssgs') AS b, (SELECT value FROM site WHERE setting = 'hashcheck') AS c")
dbgrab = cur.fetchall()
run_threads = int(dbgrab[0][0])-1
maxmssgs = int(dbgrab[0][1])
hashcheck = int(dbgrab[0][2])

if hashcheck == 0:
	sys.exit("We have updated the way collections are created, the collection table has to be updated to use the new changes.\nphp misc/testing/DB_scripts/reset_Collections.php true")
	
#before we get the groups, lets update allgroups
subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/update_groups.php", ""])

#create new db connection, to get current values
cur.close()
con.close()
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

#query to grab all active groups
cur.execute("SELECT g.name AS groupname, g.last_record AS our_last, MAX(a.last_record) AS thier_last FROM groups g INNER JOIN allgroups a ON g.active = 1 AND g.name = a.name GROUP BY g.name ORDER BY a.last_record DESC")
datas = cur.fetchall()

if not datas:
	print("No Groups activated")
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
					subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/safe_pull.php", ""+my_id])
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
		#start new groups using binaries.php, no need to check nntp
		if group[1] == 0:
			run += 1
			my_queue.put("binupdate %s" % (group[0]))
			time.sleep(0.01)
		elif group[1] != 0:
			count = group[2] - group[1]
			#run small groups using binaries.php
			if count <= maxmssgs * 2:
				run += 1
				my_queue.put("binupdate %s" % (group[0]))
			#thread large groups using backfill.php
			else:
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
	if s != "":
		resp = s.quit
	for group in list(zip(groups, finals)):
		final = ("{} {} Binary".format(group[0], group[1]))
		subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/safe_pull.php", ""+str(final)])

	print("\nUpdate Binaries Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")))
	print("Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))))

if __name__ == '__main__':
	main()
