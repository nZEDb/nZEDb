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

print("\nBackfill Threaded Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")))

start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))

#get values from db
cur.execute("SELECT (SELECT value FROM site WHERE setting = 'backfillthreads') as a, (SELECT value FROM tmux WHERE setting = 'BACKFILL') as c, (SELECT value FROM tmux WHERE setting = 'BACKFILL_GROUPS') as d, (SELECT value FROM tmux WHERE setting = 'BACKFILL_ORDER') as e, (SELECT value FROM tmux WHERE setting = 'BACKFILL_DAYS') as f")
dbgrab = cur.fetchall()
run_threads = int(dbgrab[0][0])
type = int(dbgrab[0][1])
groups = int(dbgrab[0][2])
intorder = int(dbgrab[0][3])
intbackfilltype = int(dbgrab[0][4])

#get the correct oder by for the query
if intorder == 1:
	group = "ORDER BY first_record_postdate DESC"
elif intorder == 2:
	group = "ORDER BY first_record_postdate ASC"
elif intorder == 3:
	group = "ORDER BY name ASC"
elif intorder == 4:
	group = "ORDER BY name DESC"
elif intorder == 5:
	group = "ORDER BY first_record DESC"
else:
	group = "ORDER BY first_record ASC"

#backfill days or safe backfill date
if intbackfilltype == 1:
	backfilldays = "backfill_target"
elif intbackfilltype == 2:
	backfilldays = "datediff(curdate(),(SELECT value FROM site WHERE setting = 'safebackfilldate'))"

#exit is set to safe backfill
if len(sys.argv) == 1 and type == 4:
	sys.exit("Tmux is set for Safe Backfill, no groups to process.")

#query to grab backfill groups
if len(sys.argv) > 1 and sys.argv[1] == "all":
	# Using string formatting is not the correct way to do this, but using +group is even worse
	# removing the % before the variables at the end of the query adds quotes/escapes strings
	cur.execute("SELECT name, first_record FROM groups WHERE first_record IS NOT NULL AND backfill = 1 %s" % (group))
else:
	if conf['DB_SYSTEM'] == "mysql":
		cur.execute("SELECT name, first_record FROM groups WHERE first_record IS NOT NULL AND first_record_postdate IS NOT NULL AND backfill = 1 AND first_record_postdate != '2000-00-00 00:00:00' AND (NOW() - interval %s DAY) < first_record_postdate %s LIMIT %s" % (backfilldays, group, groups))
	elif conf['DB_SYSTEM'] == "pgsql":
		cur.execute("SELECT name, first_record FROM groups WHERE first_record IS NOT NULL AND first_record_postdate IS NOT NULL AND backfill = 1 AND first_record_postdate != '2000-00-00 00:00:00' AND (NOW() - interval '%s DAYS') < first_record_postdate %s LIMIT %s" % (backfilldays, group, groups))

datas = cur.fetchall()
if not datas:
	print("No Groups enabled for backfill")
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
					if len(sys.argv) > 1 and sys.argv[1] == "all":
						subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/backfill_all_quick.php", ""+my_id])
					else:
						subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/backfill_interval.php", ""+my_id])
					time.sleep(.05)
					self.my_queue.task_done()

def main(args):
	global time_of_last_run
	time_of_last_run = time.time()

	print("We will be using a max of {} threads, a queue of {} groups".format(run_threads, "{:,}".format(len(datas))))
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
	for gnames in datas:
		time.sleep(.5)
		my_queue.put("%s %s" % (gnames[0], type))

	my_queue.join()

	print("\nBackfill Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")))
	print("Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))))

if __name__ == '__main__':
	main(sys.argv[1:])
