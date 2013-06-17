#!/usr/bin/python
# -*- coding: utf-8 -*-

import sys, os, time
import threading, Queue
import MySQLdb as mdb
import subprocess
import string
import re

pathname = os.path.abspath(os.path.dirname(sys.argv[0]))

def readConfig():
		Configfile = pathname+"/../../../www/config.php"
		file = open( Configfile, "r")

		# Match a config line
		m = re.compile('^define\(\'([A-Z_]+)\', \'?(.*?)\'?\);$', re.I)

		# The config object
		config = {}
		config['DB_PORT']=3306
		for line in file.readlines():
				match = m.search( line )
				if match:
						value = match.group(2)

						# filter boolean
						if "true" is value:
								value = True
						elif "false" is value:
								value = False

						# Add to the config
						#config[ match.group(1).lower() ] = value	   # Lower case example
						config[ match.group(1) ] = value
		return config

# Test
config = readConfig()

con = None
# The MYSQL connection.
con = mdb.connect(config['DB_HOST'], config['DB_USER'], config['DB_PASSWORD'], config['DB_NAME'], int(config['DB_PORT']));

# The group names.
cur = con.cursor()
cur.execute("select value from site where setting = 'backfillthreads'");
run_threads = cur.fetchone();
cur.execute("select value from tmux where setting = 'SEQUENTIAL'");
seq = cur.fetchone();
cur.execute("select value from tmux where setting = 'BACKFILL'");
type = cur.fetchone();
cur.execute("select value from tmux where setting = 'BACKFILL_GROUPS'");
groups = cur.fetchone();
cur.execute("select value from tmux where setting = 'BACKFILL_ORDER'");
order = cur.fetchone();
intorder = int(order[0])

if intorder == 1:
    group = "ORDER BY first_record_postdate DESC"
elif intorder == 2:
    group = "ORDER BY first_record_postdate ASC"
elif intorder == 3:
    group = "ORDER BY name ASC"
else:
    group = "ORDER BY name DESC"

if len(sys.argv) > 1 and sys.argv[1] == "all":
	print sys.argv[1]
	cur.execute("%s %s" %("SELECT name, first_record from groups where first_record IS NOT NULL and backfill = 1 and first_record_postdate != '2000-00-00 00:00:00' and (now() - interval backfill_target day) < first_record_postdate ", group))
else:
	cur.execute("%s %s %s %d" %("SELECT name, first_record from groups where first_record IS NOT NULL and backfill = 1 and first_record_postdate != '2000-00-00 00:00:00' and (now() - interval backfill_target day) < first_record_postdate ", group, " limit ", int(groups[0])))
datas = cur.fetchall()
if not datas:
	print "No Groups enabled for backfill"
	sys.exit()


class WorkerThread(threading.Thread):
	def __init__(self, threadID, result_q):
		super(WorkerThread, self).__init__()
		self.threadID = threadID
		self.result_q = result_q
		self.stoprequest = threading.Event()

	def run(self):
		while not self.stoprequest.isSet():
			try:
				dirname = self.threadID.get(True, 0.05)
				if len(sys.argv) > 1 and sys.argv[1] == "all":
					print '\n%s: Backfill All %s started.' % (self.name, dirname)
					subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/backfill_all_quick.php", ""+dirname])
				else:
					print '\n%s: Backfill %s started.' % (self.name, dirname)
					subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/backfill_interval.php", ""+dirname])
				self.result_q.put((self.name, dirname))
			except Queue.Empty:
				continue

	def join(self, timeout=None):
		self.stoprequest.set()
		super(WorkerThread, self).join(timeout)

def main(args):
	# Create a single input and a single output queue for all threads.
	threadID = Queue.Queue()
	result_q = Queue.Queue()

	# Create the "thread pool"
	pool = [WorkerThread(threadID=threadID, result_q=result_q) for i in range(int(run_threads[0]))]

	# Start all threads
	for thread in pool:
		thread.start()

	# Give the workers some work to do
	work_count = 0
	for gnames in datas:
		work_count += 1
		threadID.put("%s %s" %(gnames[0], type[0]))

	print 'Assigned %s groups to workers' % work_count

	while work_count > 0:
		# Blocking 'get' from a Queue.
		result = result_q.get()
		print '\n%s: Backfill on %s finished.' % (result[0], result[1])
		work_count -= 1

	# Ask threads to die and wait for them to do it
	for thread in pool:
		thread.join()

if __name__ == '__main__':
	import sys
	main(sys.argv[1:])
