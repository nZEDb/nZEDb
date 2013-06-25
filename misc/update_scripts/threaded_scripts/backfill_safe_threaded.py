#!/usr/bin/python
# -*- coding: utf-8 -*-

import sys, os, time
import threading
try:
    import queue
except ImportError:
    import Queue as queue
import pymysql as mdb
import subprocess
import string
import re
import nntplib
import datetime

start_time = time.time()
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
cur.execute("select value from tmux where setting = 'BACKFILL_ORDER'");
order = cur.fetchone();
intorder = int(order[0])

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

cur.execute("select value from tmux where setting = 'BACKFILL_DAYS'");
backfilltype = cur.fetchone();
intbackfilltype = int(backfilltype[0])
if intbackfilltype == 1:
	backfilldays = "backfill_target"
elif intbackfilltype == 2:
	backfilldays = "datediff(curdate(),(select value from site where setting = 'safebackfilldate'))"

cur.execute("%s %s %s %s %s" %("SELECT name, first_record from groups where first_record IS NOT NULL and backfill = 1 and first_record_postdate != '2000-00-00 00:00:00' and (now() - interval ", backfilldays, " day) < first_record_postdate ", group, " limit 1"))
datas = cur.fetchall()
cur.execute("select value from site where setting = 'backfillthreads'");
run_threads = cur.fetchone();
cur.execute("select value from tmux where setting = 'BACKFILL_QTY'");
backfill_qty = cur.fetchone();
cur.execute("select value from site where setting = 'maxmssgs'");
maxmssgs = cur.fetchone();

if not datas:
	print("No Groups enabled for backfill")
	sys.exit()

s = nntplib.connect(config['NNTP_SERVER'], config['NNTP_PORT'], config['NNTP_SSLENABLED'], config['NNTP_USERNAME'], config['NNTP_PASSWORD'])

resp, count, first, last, name = s.group(datas[0][0])
print('Group', name, 'has', count, 'articles, range', first, 'to', last)
print("Our oldest post is: ", datas[0][1])

while (datas[0][1] - first) < 1000:
	group = ("%s %d" %(datas[0][0], 1000))
	subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/backfill_safe.php", ""+str(group)])
	sys.exit()

if (datas[0][1] - first) > int(backfill_qty[0]) * int(run_threads[0]):
	geteach = int(int(backfill_qty[0]) * int(run_threads[0]) / int(maxmssgs[0]))
else:
	geteach = ((datas[0][1] - first) / int(maxmssgs[0]))

resp = s.quit()

#sys.exit()

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
				#print("\n%s: Backfill All %s started." %(self.name, dirname))
				subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/backfill_safe.php", ""+dirname])
				self.result_q.put((self.name, dirname))
			except queue.Empty:
				continue

	def join(self, timeout=None):
		self.stoprequest.set()
		super(WorkerThread, self).join(timeout)

def main(args):
	# Create a single input and a single output queue for all threads.
	threadID = queue.Queue(100)
	result_q = queue.Queue(100)

	# Create the "thread pool"
	pool = [WorkerThread(threadID=threadID, result_q=result_q) for i in range(int(run_threads[0]))]

	# Start all threads
	for thread in pool:
		thread.start()

	# Give the workers some work to do
	work_count = 0
	threads = int(run_threads[0])
	for i in range(0, geteach):
		threadID.put("%s %d %d %d" %(datas[0][0], datas[0][1] - i * int(maxmssgs[0]) - 1, datas[0][1] - i * int(maxmssgs[0]) - int(maxmssgs[0]), i+1))
		#threadID.put("%s %d %d %d" %(datas[0][0], datas[0][1] - i * geteach - 1, datas[0][1] - i * geteach - geteach, i+1))
		#threadID.put("%s %d %d %d" %(datas[0][0], datas[0][1] - i * geteach - geteach, datas[0][1] - i * geteach - 1, i+1))
		work_count += 1


	while work_count > 0:
		# Blocking 'get' from a Queue.
		result = result_q.get()
		work_count -= 1

	# Ask threads to die and wait for them to do it
	for thread in pool:
		thread.join(timeout=60)

if __name__ == '__main__':
	import sys
	main(sys.argv[1:])

final = ("%s %d %s" %(datas[0][0], datas[0][1] - (int(maxmssgs[0]) * geteach), geteach))
subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/backfill_safe.php", ""+str(final)])
group = ("%s %d" %(datas[0][0], 1000))
subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/backfill_safe.php", ""+str(group)])
print("Backfill Safe running time: %s for parts %s" %(str(datetime.timedelta(seconds=time.time() - start_time)), "{:,}".format(int(maxmssgs[0]) * geteach)))
