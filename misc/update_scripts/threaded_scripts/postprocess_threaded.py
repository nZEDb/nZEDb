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
con = mdb.connect(config['DB_HOST'], config['DB_USER'], config['DB_PASSWORD'], config['DB_NAME'], int(config['DB_PORT']))
cur = con.cursor()
cur.execute("select value from site where setting = 'postthreads'")
run_threads = cur.fetchone()

# The array.
datas = list(xrange(1, int(run_threads[0]) + 1))

class WorkerThread(threading.Thread):
	def __init__(self, threadID):
		super(WorkerThread, self).__init__()
		self.threadID = threadID
		self.stoprequest = threading.Event()

	def run(self):
		while not self.stoprequest.isSet():
			try:
				dirname = self.threadID.get(True, 0.05)
				if sys.argv[1] == "additional":
					subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/postprocess_additional.php", ""+dirname])
				elif sys.argv[1] == "amazon":
					subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/postprocess_amazon.php", ""+dirname])
				elif sys.argv[1] == "non_amazon":
					subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/postprocess_non_amazon.php", ""+dirname])
				elif sys.argv[1] == "all":
					subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/postprocess.php", ""+dirname])
			except Queue.Empty:
				continue

	def join(self, timeout=None):
		self.stoprequest.set()
		super(WorkerThread, self).join(timeout)

def main(args):
	# Create a single input and a single output queue for all threads.
	threadID = Queue.Queue()

	# Create the "thread pool"
	pool = [WorkerThread(threadID=threadID) for i in range(int(run_threads[0]))]

	# Start all threads
	for thread in pool:
		thread.start()

	# Give the workers some work to do
	work_count = 0
	for gnames in datas:
		work_count += 1
		threadID.put(str(gnames))

	#print 'Assigned %s Postprocesses to workers' % work_count
	while work_count > 0:
		# Blocking 'get' from a Queue.
		work_count -= 1

	# Ask threads to die and wait for them to do it
	for thread in pool:
		thread.join(timeout=60)

if __name__ == '__main__':
	import sys
	main(sys.argv[1:])

