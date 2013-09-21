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
import lib.nntplib as nntplib
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

print("\nBackfill Safe Threaded Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")))

start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))

count = 0
first = 0
#if the group has less than 10000 to grab, just grab them, and loop another group
while (count - first) < 10000:
	#get values from db
	cur.execute("SELECT (SELECT value FROM site WHERE setting = 'backfillthreads') AS a, (SELECT value FROM tmux WHERE setting = 'BACKFILL_QTY') AS b, (SELECT value FROM tmux WHERE setting = 'BACKFILL') AS c, (SELECT value FROM tmux WHERE setting = 'BACKFILL_GROUPS') AS d, (SELECT value FROM tmux WHERE setting = 'BACKFILL_ORDER') AS e, (SELECT value FROM tmux WHERE setting = 'BACKFILL_DAYS') AS f, (SELECT value FROM site WHERE setting = 'maxmssgs') AS g")
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
		backfilldays = "datediff(curdate(),(SELECT value FROM site WHERE setting = 'safebackfilldate'))"

	#query to grab backfill groups
	if len(sys.argv) == 1:
		# Using string formatting is not the correct way to do this, but using +group is even worse
		# removing the % before the variables at the end of the query adds quotes/escapes strings
		cur.execute("SELECT name, first_record FROM groups WHERE first_record IS NOT NULL AND first_record_postdate IS NOT NULL AND backfill = 1 AND first_record_postdate != '2000-00-00 00:00:00' AND (NOW() - INTERVAL %s DAY) < first_record_postdate %s" % (backfilldays, group,))
		datas = cur.fetchone()
	else:
		run = "SELECT name, first_record FROM groups WHERE name = %s AND first_record IS NOT NULL AND first_record_postdate IS NOT NULL AND backfill = 1 AND first_record_postdate != '2000-00-00 00:00000'"
		cur.execute(run, (sys.argv[1]))
		datas = cur.fetchone()
	if not datas:
		print("No Groups enabled for backfill")
		sys.exit()

	#get first, last from nntp sever
	time.sleep(0.05)
	s = nntplib.connect(conf['NNTP_SERVER'], conf['NNTP_PORT'], conf['NNTP_SSLENABLED'], conf['NNTP_USERNAME'], conf['NNTP_PASSWORD'])
	time.sleep(0.05)
	try:
		resp, count, first, last, name = s.group(datas[0])
		time.sleep(0.05)
	except nntplib.NNTPError:
		run = "UPDATE GROUPS SET backfill = 0 WHERE name = %s"
		cur.execute(run, (datas[0]))
		con.autocommit(True)
		print("\033[38;5;9m{} not found, disabling.\033[0m\n".format(datas[0]))
	resp = s.quit

	if (datas[1] - first) < 0:
		run = "UPDATE GROUPS SET backfill = 0 WHERE name = %s"
		cur.execute(run, (datas[0]))
		con.autocommit(True)
		print("{} has invalid first_post, disabling.".format(datas[0]))

	if name:
		print("Group {} has {} articles, in the range {} to {}".format(name, "{:,}".format(int(count)), "{:,}".format(int(first)), "{:,}".format(int(last))))
		print("Our oldest post is: {}".format("{:,}".format(datas[1])))
		print("Available Posts: {}".format("{:,}".format(datas[1] - first)))
		sys.exit
		count = datas[1]

		if (datas[1] - first) < 10000 and (datas[1] - first) > 0:
			group = ("{} 10000".format(datas[0]))
			subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/safe_pull.php", ""+str(group)])

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
					subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/safe_pull.php", ""+my_id])
					time.sleep(.05)
					self.my_queue.task_done()

def main(args):
	global time_of_last_run
	time_of_last_run = time.time()

	print("We will be using a max of {} threads, a queue of {} and grabbing {} headers".format(run_threads, "{:,}".format(geteach), "{:,}".format(geteach * maxmssgs + 1000)))
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
		my_queue.put("%s %s %s %s" % (datas[0], datas[1] - i * maxmssgs - 1, datas[1] - i * maxmssgs - maxmssgs, i+1))

	my_queue.join()

	final = ("{} {} Backfill".format(datas[0], int(datas[1] - (maxmssgs * geteach))))
	subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/safe_pull.php", ""+str(final)])
	group = ("{} {}".format(datas[0], 1000))
	subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/safe_pull.php", ""+str(group)])
	if run_threads <= geteach:
		print("\nWe used {} threads, a queue of {} and grabbed {} headers".format(run_threads, "{:,}".format(geteach), "{:,}".format(geteach * maxmssgs + 1000)))
	else:
		print("\nWe used {} threads, a queue of {} and grabbed {} headers".format(geteach, "{:,}".format(geteach), "{:,}".format(geteach * maxmssgs + 1000)))

	print("Backfill Safe Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")))
	print("Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))))


if __name__ == '__main__':
	main(sys.argv[1:])
