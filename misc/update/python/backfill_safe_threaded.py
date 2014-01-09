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
			print(bcolors.HEADER + "\nPlease install psycopg for python 3, \ninformation can be found in INSTALL.txt\n" + bcolors.ENDC)
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

print(bcolors.HEADER + "\nBackfill Safe Threaded Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)

cur = connect()
cur[0].execute("SELECT name FROM shortgroups")
dorun = cur[0].fetchone()
disconnect(cur[0], cur[1])
if not dorun:
	#before we get the groups, lets update shortgroups
	subprocess.call(["php", pathname+"/../nix/tmux/bin/update_groups.php", ""])
elif len(sys.argv) > 1 and sys.argv[1] not in dorun:
	#if not dorun:
	#before we get the groups, lets update shortgroups
	subprocess.call(["php", pathname+"/../nix/tmux/bin/update_groups.php", ""])

count = 0
previous = "'alt.binaries.crap'"

#if the group has less than 10000 to grab, just grab them, and loop another group
while count < 10000:
	#get values from db
	cur = connect()
	cur[0].execute("SELECT (SELECT value FROM site WHERE setting = 'backfillthreads') AS a, (SELECT value FROM tmux WHERE setting = 'backfill_qty') AS b, (SELECT value FROM tmux WHERE setting = 'backfill') AS c, (SELECT value FROM tmux WHERE setting = 'backfill_order') AS e, (SELECT value FROM tmux WHERE setting = 'backfill_days') AS f, (SELECT value FROM site WHERE setting = 'maxmssgs') AS g")
	dbgrab = cur[0].fetchall()
	run_threads = int(dbgrab[0][0])
	backfill_qty = int(dbgrab[0][1])
	type = int(dbgrab[0][2])
	intorder = int(dbgrab[0][3])
	intbackfilltype = int(dbgrab[0][4])
	maxmssgs = int(dbgrab[0][5])

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
		group = "ORDER BY a.last_record DESC"
	else:
		group = "ORDER BY a.last_record ASC"

	#backfill days or safe backfill date
	if intbackfilltype == 1:
		backfilldays = "backfill_target"
	elif intbackfilltype == 2:
		backfilldays = "datediff(curdate(),(SELECT value FROM site WHERE setting = 'safebackfilldate'))"

	#query to grab backfill groups
	if len(sys.argv) == 1:
		if conf['DB_SYSTEM'] == "mysql":
			cur[0].execute("SELECT g.name, g.first_record AS our_first, MAX(a.first_record) AS thier_first, MAX(a.last_record) AS their_last FROM groups g INNER JOIN shortgroups a ON g.name = a.name WHERE g.first_record IS NOT NULL AND g.first_record_postdate IS NOT NULL AND g.backfill = 1 AND (NOW() - INTERVAL %s DAY) < g.first_record_postdate AND g.name NOT IN (%s) GROUP BY a.name, a.last_record, g.name, g.first_record %s LIMIT 1" % (backfilldays, previous, group))
		elif conf['DB_SYSTEM'] == "pgsql":
			cur[0].execute("SELECT g.name, g.first_record AS our_first, MAX(a.first_record) AS thier_first, MAX(a.last_record) AS their_last FROM groups g INNER JOIN shortgroups a ON g.name = a.name WHERE g.first_record IS NOT NULL AND g.first_record_postdate IS NOT NULL AND g.backfill = 1 AND (NOW() - INTERVAL '%s DAYS') < g.first_record_postdate GROUP BY a.name, a.last_record, g.name, g.first_record %s LIMIT 1" % (backfilldays, group, groups))
		datas = cur[0].fetchone()
	else:
		run = "SELECT g.name, g.first_record AS our_first, MAX(a.first_record) AS thier_first, MAX(a.last_record) AS their_last FROM groups g INNER JOIN shortgroups a ON g.name = a.name WHERE g.name = %s AND g.first_record IS NOT NULL AND g.first_record_postdate IS NOT NULL AND g.backfill = 1 LIMIT 1"
		cur[0].execute(run, (sys.argv[1]))
		datas = cur[0].fetchone()
	if not datas or datas[0] is None:
		print(bcolors.ERROR + "No Groups enabled for backfill" + bcolors.ENDC)
		disconnect(cur[0], cur[1])
		sys.exit()
	disconnect(cur[0], cur[1])

	previous += ", '%s'" % datas[0]
	count = datas[1] - datas[2]
	if count < 0:
		print(bcolors.ERROR + "USP returned an invalid first_post for {}, skipping it.".format(datas[0]) + bcolors.ENDC)
		if len(sys.argv) == 2:
			sys.exit()

	if count == 0:
		if len(sys.argv) == 2:
			print(bcolors.ERROR + "We have hit the maximum we can backfill for {}, disabling it".format(datas[0]) + bcolors.ENDC)
			remove = "UPDATE groups SET backfill = 0 WHERE name = %s"
			cur = connect()
			cur[0].execute(remove, (sys.argv[1]))
			cur[1].autocommit(True)
			disconnect(cur[0], cur[1])
			sys.exit()
		else:
			print(bcolors.ERROR + "We have hit the maximum we can backfill for {}, skipping it".format(datas[0]) + bcolors.ENDC)

	if count < 10000 and count > 0:
		print(bcolors.PRIMARY + "Group {} has {} articles, in the range {} to {}".format(datas[0], "{:,}".format(count), "{:,}".format(datas[2]), "{:,}".format(datas[3])) + bcolors.ENDC)
		print(bcolors.PRIMARY + "Our oldest post is: {}".format("{:,}".format(datas[1])) + bcolors.ENDC)
		print(bcolors.PRIMARY + "Available Posts: {}".format("{:,}".format(count)) + bcolors.ENDC)
		group = ("{} {} BackfillAll".format(datas[0], count))
		subprocess.call(["php", pathname+"/../nix/tmux/bin/safe_pull.php", ""+str(group)])

#calculate the number of items for queue
if (count > (backfill_qty * run_threads)):
	geteach = math.ceil((backfill_qty * run_threads) / maxmssgs)
else:
	geteach = int(count / maxmssgs)

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
					time.sleep(.05)
					self.my_queue.task_done()

def main(args):
	global time_of_last_run
	time_of_last_run = time.time()

	print(bcolors.HEADER + "We will be using a max of {} threads, a queue of {} and grabbing {} headers".format(run_threads, "{:,}".format(geteach), "{:,}".format(geteach * maxmssgs)) + bcolors.ENDC)
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
		time.sleep(.1)
		my_queue.put("%s %s %s %s" % (datas[0], datas[1] - i * maxmssgs - 1, datas[1] - i * maxmssgs - maxmssgs, i+1))

	my_queue.join()

	#get postdate
	final = ("{} {} Backfill".format(datas[0], int(datas[1] - (maxmssgs * geteach))))
	subprocess.call(["php", pathname+"/../nix/tmux/bin/safe_pull.php", ""+str(final)])

	group = ("{} {}".format(datas[0], 1000))
	subprocess.call(["php", pathname+"/../nix/tmux/bin/safe_pull.php", ""+str(group)])
	if run_threads <= geteach:
		print(bcolors.HEADER + "\nWe used {} threads, a queue of {} and grabbed {} headers".format(run_threads, "{:,}".format(geteach), "{:,}".format(geteach * maxmssgs)) + bcolors.ENDC)
	else:
		print(bcolors.HEADER + "\nWe used {} threads, a queue of {} and grabbed {} headers".format(geteach, "{:,}".format(geteach), "{:,}".format(geteach * maxmssgs)) + bcolors.ENDC)

	print(bcolors.HEADER + "\nBackfill Safe Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)
	print(bcolors.HEADER + "Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))) + bcolors.ENDC)


if __name__ == '__main__':
	main(sys.argv[1:])
