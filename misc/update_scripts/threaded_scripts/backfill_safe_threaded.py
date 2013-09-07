#!/usr/bin/python
# -*- coding: utf-8 -*-

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
import signal
import lib.nntplib as nntplib
import lib.info as info
import datetime
import math

print("\nBackfill Safe Threaded Started at %s" % (datetime.datetime.now().strftime("%H:%M:%S")))

start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))
conf = info.readConfig()

count = 0
first = 0
#if the group has less than 10000 to grab, just grab them, and loop another group
while (count - first) < 10000:

	#create the connection to mysql
	con = None
	con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], passwd=conf['DB_PASSWORD'], db=conf['DB_NAME'], port=int(conf['DB_PORT']), unix_socket=conf['DB_SOCKET'])
	cur = con.cursor()

	#get values from db
	cur.execute("select (select value from site where setting = 'backfillthreads') as a, (select value from tmux where setting = 'BACKFILL_QTY') as b, (select value from tmux where setting = 'BACKFILL') as c, (select value from tmux where setting = 'BACKFILL_GROUPS') as d, (select value from tmux where setting = 'BACKFILL_ORDER') as e, (select value from tmux where setting = 'BACKFILL_DAYS') as f, (select value from site where setting = 'maxmssgs') as g")
	dbgrab = cur.fetchall()
	run_threads = int(dbgrab[0][0])
	backfill_qty = int(dbgrab[0][1])
	type = int(dbgrab[0][2])
	groups = int(dbgrab[0][3])
	intorder = int(dbgrab[0][4])
	intbackfilltype = int(dbgrab[0][5])
	maxmssgs = int(dbgrab[0][6])

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
		backfilldays = "datediff(curdate(),(select value from site where setting = 'safebackfilldate'))"

	#query to grab backfill groups
	if len(sys.argv) == 1:
		cur.execute("SELECT name, first_record from groups where first_record IS NOT NULL and first_record_postdate IS NOT NULL and backfill = 1 and first_record_postdate != '2000-00-00 00:00:00' and (now() - interval %s day) < first_record_postdate %s" % (backfilldays, group))
		datas = cur.fetchone()
	else:
		cur.execute("SELECT name, first_record from groups where name = '%s' and first_record IS NOT NULL and first_record_postdate IS NOT NULL and backfill = 1 and first_record_postdate != '2000-00-00 00:00:00' and (now() - interval %s day) < first_record_postdate %s" % (sys.argv[1], backfilldays, group))
		datas = cur.fetchone()
	if not datas:
		print("No Groups enabled for backfill")
		sys.exit()

	#get first, last from nntp sever
	time.sleep(0.01)
	s = nntplib.connect(conf['NNTP_SERVER'], conf['NNTP_PORT'], conf['NNTP_SSLENABLED'], conf['NNTP_USERNAME'], conf['NNTP_PASSWORD'])
	time.sleep(0.01)
	try:
		resp, count, first, last, name = s.group(datas[0])
		time.sleep(0.1)
	except nntplib.NNTPError:
		cur.execute("update groups set backfill = 0 where name = %s" % (mdb.escape_string(datas[0])))
		con.autocommit(True)
		print("%s not found, disabling." %(datas[0]))
	resp = s.quit()

	if (datas[1] - first) < 0:
		cur.execute("update groups set backfill = 0 where name = %s" % (mdb.escape_string(datas[0])))
		con.autocommit(True)
		print("%s has invalid first_post, disabling." %(datas[0]))

	if name:
		print("Group %s has %s articles, in the range %s to %s" % (name, "{:,}".format(int(count)), "{:,}".format(int(first)), "{:,}".format(int(last))))
		print("Our oldest post is: %s" % ("{:,}".format(datas[1])))
		print("Available Posts: %s" % ("{:,}".format(datas[1] - first)))
		count = datas[1]

		if (datas[1] - first) < 10000 and (datas[1] - first) > 0:
			group = ("%s 10000" % (datas[0]))
			subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/backfill_safe.php", ""+str(group)])
			cur.close()
			con.close()

#close connection to mysql
cur.close()
con.close()

#calculate the number of items for queue
if ((datas[1] - first) > (backfill_qty * run_threads)):
	geteach = math.ceil((backfill_qty * run_threads) / maxmssgs)
else:
	geteach = int((datas[1] - first) / maxmssgs)

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
					subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/backfill_safe.php", ""+my_id])
					time.sleep(.5)
					self.my_queue.task_done()

def main(args):
	global time_of_last_run
	time_of_last_run = time.time()

	print("We will be using a max of %s threads, a queue of %s and grabbing %s headers" % (run_threads, "{:,}".format(geteach), "{:,}".format(geteach * maxmssgs)))
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
	for i in range(0, int(geteach)):
		my_queue.put("%s %d %d %d" % (datas[0], datas[1] - i * maxmssgs - 1, datas[1] - i * maxmssgs - maxmssgs, i+1))

	my_queue.join()

	final = ("%s %d %s" % (datas[0], int(datas[1] - (maxmssgs * geteach)), geteach))
	subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/backfill_safe.php", ""+str(final)])
	group = ("%s %d" % (datas[0], 1000))
	subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/backfill_safe.php", ""+str(group)])
	if run_threads <= geteach:
		print("\nWe used %s threads, a queue of %s and grabbed %s headers" % (run_threads, "{:,}".format(geteach), "{:,}".format(geteach * maxmssgs + 1000)))
	else:
		print("\nWe used %s threads, a queue of %s and grabbed %s headers" % (geteach, "{:,}".format(geteach), "{:,}".format(geteach * maxmssgs + 1000)))

	print("Backfill Safe Threaded Completed at %s" % (datetime.datetime.now().strftime("%H:%M:%S")))
	print("Running time: %s" % (str(datetime.timedelta(seconds=time.time() - start_time))))


if __name__ == '__main__':
	main(sys.argv[1:])
