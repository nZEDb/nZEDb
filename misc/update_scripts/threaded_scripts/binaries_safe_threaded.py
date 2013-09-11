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

print("\nBinary Safe Threaded Started at %s" % (datetime.datetime.now().strftime("%H:%M:%S")))

start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))
conf = info.readConfig()

count = 0

#create the connection to mysql
con = None
con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], passwd=conf['DB_PASSWORD'], db=conf['DB_NAME'], port=int(conf['DB_PORT']), unix_socket=conf['DB_SOCKET'])
cur = con.cursor()

#get values from db
cur.execute("select (select value from site where setting = 'binarythreads') as a, (select value from site where setting = 'maxmssgs') as b")
dbgrab = cur.fetchall()
run_threads = int(dbgrab[0][0])
maxmssgs = int(dbgrab[0][1])

#query to grab all active groups
cur.execute("SELECT name, last_record FROM groups where active = 1 and last_record != 0 and last_record_postdate IS NOT NULL")
datas = cur.fetchall()
if not datas:
	print("No Groups activated")
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
					subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/backfill_safe.php", ""+my_id])
					time.sleep(.1)
					self.my_queue.task_done()

def main():
	global time_of_last_run
	time_of_last_run = time.time()

	#print("We will be using a max of %s threads, a queue of %s and grabbing %s headers" % (run_threads, "{:,}".format(geteach), "{:,}".format(geteach * maxmssgs)))
	#time.sleep(2)

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
	time.sleep(0.01)
	print("Connectiong to USP")
	s = nntplib.connect(conf['NNTP_SERVER'], conf['NNTP_PORT'], conf['NNTP_SSLENABLED'], conf['NNTP_USERNAME'], conf['NNTP_PASSWORD'])
	time.sleep(0.01)
	run = 0
	finals = []
	groups = []
	for group in datas:
		try:
			resp, count, first, last, name = s.group(group[0])
			time.sleep(0.01)
		except nntplib.NNTPError:
			cur.execute("update groups set active = 0 where name = %s" % (mdb.escape_string(group[0])))
			con.autocommit(True)
			print("%s not found, disabling." %(datas[0]))
		if name:
			count = last - group[1] - 1
			if count > 0:
				#print("%s has %s articles, in the range %s to %s" % (name, "{:,}".format(int(count)), "{:,}".format(group[1]+1), "{:,}".format(int(last))))
				groups.append(group[0])
				finals.append(int(last))
			if count <= maxmssgs and count > 0:
				run += 1							
				my_queue.put("%s %d %d %d" % (group[0], int(last), group[1]+1, run))
			elif count > 0:
				geteach = math.floor(count / maxmssgs)
				remaining = count - geteach * maxmssgs
				for loop in range(int(geteach)):
					run += 1
					my_queue.put("%s %d %d %d" % (group[0], group[1] + loop * maxmssgs + maxmssgs, group[1] + loop * maxmssgs + 1, run))
				run += 1
				my_queue.put("%s %d %d %d" % (group[0], group[1] + (loop + 1) * maxmssgs + remaining + 1, group[1] + (loop + 1) * maxmssgs + 1, run))
	my_queue.join()
	resp = s.quit
	for group in list(zip(groups, finals)):
		run +=1
		final = ("%s %d Binary" % (group[0], group[1]))
		subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/backfill_safe.php", ""+str(final)])

if __name__ == '__main__':
	main()
