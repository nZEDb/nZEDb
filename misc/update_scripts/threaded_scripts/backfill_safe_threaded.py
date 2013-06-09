#!/usr/bin/python
# -*- coding: utf-8 -*-

import sys, os, time
import threading, Queue
import MySQLdb as mdb
import subprocess
import string
import re
from nntplib import NNTP
import ssl

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
cur = con.cursor()
cur.execute("SELECT name, first_record from groups where first_record IS NOT NULL and backfill = 1 and first_record_postdate != '2000-00-00 00:00:00' and (now() - interval backfill_target day) < first_record_postdate ORDER BY name DESC limit 1")
datas = cur.fetchall()
cur.execute("select value from site where setting = 'backfillthreads'");
run_threads = cur.fetchone();

if not datas:
	print "No Groups enabled for backfill"
	sys.exit()

s = NNTP(config['NNTP_SERVER'], 119, config['NNTP_USERNAME'], config['NNTP_PASSWORD'],)
resp, count, first, last, name = s.group(datas[0][0])
print 'Group', name, 'has', count, 'articles, range', first, 'to', last
print datas[0][1]
geteach = (datas[0][1] - long(first)) / int(run_threads[0])
if geteach > 20000:
	geteach = 20000



#sys.exit()


#resp, subs = s.xhdr('subject', first + '-' + last)
resp = s.quit()


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
				#print '\n%s: Backfill All %s started.' % (self.name, dirname)
				subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/backfill_safe.php", ""+dirname])
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
	pool = [WorkerThread(threadID=threadID, result_q=result_q) for i in range(1, int(run_threads[0]))]

	# Start all threads
	for thread in pool:
		thread.start()

	# Give the workers some work to do
	work_count = 0
	for gnames in datas:
		work_count += 1
		threadID.put(gnames[0])

	threads = int(run_threads[0])
	for i in range(0, threads):
		threadID.put("%s %d %d %d" %(datas[0][0], datas[0][1] - i * geteach - 1, datas[0][1] - i * geteach - geteach, i+1))
		#threadID.put("%s %d %d %d" %(datas[0][0], datas[0][1] - i * geteach - geteach, datas[0][1] - i * geteach - 1, i+1))
		work_count += 1


	while work_count > 0:
		# Blocking 'get' from a Queue.
		result = result_q.get()
		work_count -= 1

	# Ask threads to die and wait for them to do it
	for thread in pool:
		thread.join()

if __name__ == '__main__':
	import sys
	main(sys.argv[1:])
