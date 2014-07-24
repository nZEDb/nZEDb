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
cur = info.connect()
start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))

print(bcolors.HEADER + "\nBackfill Threaded Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)

#get values from db
cur[0].execute("SELECT (SELECT value FROM settings WHERE setting = 'backfillthreads') as a, (SELECT value FROM tmux WHERE setting = 'backfill') as c, (SELECT value FROM tmux WHERE setting = 'backfill_groups') as d, (SELECT value FROM tmux WHERE setting = 'backfill_order') as e, (SELECT value FROM tmux WHERE setting = 'backfill_days') as f")
dbgrab = cur[0].fetchall()
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
	backfilldays = "datediff(curdate(),(SELECT value FROM settings WHERE setting = 'safebackfilldate'))"

#exit is set to safe backfill
if len(sys.argv) == 1 and type == 4:
	print(bcolors.ERROR + "Tmux is set for Safe Backfill, no groups to process." + bcolors.ENDC)
	info.disconnect(cur[0], cur[1])
	sys.exit()

#query to grab backfill groups
if len(sys.argv) > 1 and sys.argv[1] == "all":
	# Using string formatting is not the correct way to do this, but using +group is even worse
	# removing the % before the variables at the end of the query adds quotes/escapes strings
	cur[0].execute("SELECT name, first_record FROM groups WHERE first_record != 0 AND backfill = 1 %s" % (group))
else:
	if conf['DB_SYSTEM'] == "mysql":
		cur[0].execute("SELECT name, first_record FROM groups WHERE first_record != 0 AND first_record_postdate IS NOT NULL AND backfill = 1 AND (NOW() - interval %s DAY) < first_record_postdate %s LIMIT %s" % (backfilldays, group, groups))
	elif conf['DB_SYSTEM'] == "pgsql":
		cur[0].execute("SELECT name, first_record FROM groups WHERE first_record != 0 AND first_record_postdate IS NOT NULL AND backfill = 1 AND (NOW() - interval '%s DAYS') < first_record_postdate %s LIMIT %s" % (backfilldays, group, groups))

datas = cur[0].fetchall()

#close connection to mysql
info.disconnect(cur[0], cur[1])

if not datas:
	print(bcolors.ERROR + "No Groups enabled for backfill" + bcolors.ENDC)
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
					if len(sys.argv) > 1 and sys.argv[1] == "all":
						subprocess.call(["php", pathname+"/../nix/multiprocessing/.do_not_run/switch.php", "python  backfill_all_quick  "+my_id])
					else:
						subprocess.call(["php", pathname+"/../nix/multiprocessing/.do_not_run/switch.php", "python  backfill  "+my_id])
					time.sleep(.03)
					self.my_queue.task_done()

def main(args):
	global time_of_last_run
	time_of_last_run = time.time()

	print(bcolors.HEADER + "We will be using a max of {} threads, a queue of {} groups".format(run_threads, "{:,}".format(len(datas))) + bcolors.ENDC)
	time.sleep(2)

	def signal_handler(signal, frame):
		sys.exit()

	signal.signal(signal.SIGINT, signal_handler)

	if True:
		#spawn a pool of place worker threads
		for i in range(run_threads):
			p = queue_runner(my_queue)
			p.setDaemon(False)
			p.start()

	#now load some arbitrary jobs into the queue
	for gnames in datas:
		time.sleep(.03)
		my_queue.put("%s  %s" % (gnames[0], type))

	my_queue.join()

	print(bcolors.HEADER + "\nBackfill Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)
	print(bcolors.HEADER + "Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))) + bcolors.ENDC)

if __name__ == '__main__':
	main(sys.argv[1:])
