#!/usr/bin/python
# -*- coding: utf-8 -*-

from __future__ import print_function
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

print("\nBackfill Threaded Started at %s" % (datetime.datetime.now().strftime("%H:%M:%S")))

start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))
conf = info.readConfig()

#create the connection to mysql
con = None
con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], passwd=conf['DB_PASSWORD'], db=conf['DB_NAME'], port=int(conf['DB_PORT']), unix_socket=conf['DB_SOCKET'])
cur = con.cursor()

#get values from db
cur.execute("SELECT (SELECT value FROM site where setting = 'backfillthreads') as a, (SELECT value FROM tmux where setting = 'BACKFILL') as c, (SELECT value FROM tmux where setting = 'BACKFILL_GROUPS') as d, (SELECT value FROM tmux where setting = 'BACKFILL_ORDER') as e, (SELECT value FROM tmux where setting = 'BACKFILL_DAYS') as f")
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
	backfilldays = "datediff(curdate(),(SELECT value FROM site where setting = 'safebackfilldate'))"

#query to grab backfill groups
if len(sys.argv) > 1 and sys.argv[1] == "all":
	cur.execute("%s %s" % ("SELECT name, first_record FROM groups where first_record IS NOT NULL and backfill = 1 ", group))
else:
	cur.execute("%s %s %s %s %s %d" % ("SELECT name, first_record FROM groups where first_record IS NOT NULL and first_record_postdate IS NOT NULL and backfill = 1 and first_record_postdate != '2000-00-00 00:00:00' and (now() - interval", backfilldays, " day) < first_record_postdate ", group, " limit ", groups))
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
					time.sleep(.01)
					self.my_queue.task_done()

def main(args):
	global time_of_last_run
	time_of_last_run = time.time()

	print("We will be using a max of %s threads, a queue of %s groups" % (run_threads, "{:,}".format(len(datas))))
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
		my_queue.put("%s %s" % (gnames[0], type))

	my_queue.join()

	print("\nBackfill Threaded Completed at %s" % (datetime.datetime.now().strftime("%H:%M:%S")))
	print("Running time: %s" % (str(datetime.timedelta(seconds=time.time() - start_time))))

if __name__ == '__main__':
	main(sys.argv[1:])
