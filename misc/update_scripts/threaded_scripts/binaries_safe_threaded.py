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

print("\nBinary Safe Threaded Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")))

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
					subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/safe_pull.php", ""+my_id])
					time.sleep(.1)
					self.my_queue.task_done()

def main():
	global time_of_last_run
	time_of_last_run = time.time()

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
	time.sleep(0.1)
	run = 0
	finals = []
	groups = []
	for group in datas:
		try:
			resp, count, first, last, name = s.group(group[0])
			time.sleep(0.01)
		except nntplib.NNTPError:
			cur.execute("update groups set active = 0 where name = {}".format(mdb.escape_string(group[0])))
			con.autocommit(True)
			print("{} not found, disabling.".format(datas[0]))
		if name:
			count = last - group[1] - 1
			#only update groups that have at least 1000 headers to grab
			if count > 1000:
				if (int(count) - maxmssgs) > 0:
					remains = "{:,}".format(int(count) - maxmssgs)
				else:
					remains = 0
				print("\nGetting {} articles ({} to {}) from {} \033[1;33m({} articles in queue).\033[0m".format("{:,}".format(int(count)), "{:,}".format(group[1]+1), "{:,}".format(int(last)), name, remains))
				groups.append(group[0])
				finals.append(int(last))
			if count <= maxmssgs and count > 0:
				run += 1							
				my_queue.put("{} {} {} {}".format(mdb.escape_string(group[0]), int(last), group[1]+1, run))
			elif count > 0:
				geteach = math.floor(count / maxmssgs)
				remaining = count - geteach * maxmssgs
				for loop in range(int(geteach)):
					run += 1
					my_queue.put("{} {} {} {}".format(mdb.escape_string(group[0]), group[1] + loop * maxmssgs + maxmssgs, group[1] + loop * maxmssgs + 1, run))
				run += 1
				my_queue.put("{} {} {} {}".format(mdb.escape_string(group[0]), group[1] + (loop + 1) * maxmssgs + remaining + 1, group[1] + (loop + 1) * maxmssgs + 1, run))
	my_queue.join()
	resp = s.quit
	for group in list(zip(groups, finals)):
		run +=1
		final = ("{} {} Binary".format(mdb.escape_string(group[0]), group[1]))
		subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/safe_pull.php", ""+str(final)])

	print("\nUpdate Binaries Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")))
	print("Running time: {}".format(str(datetime.timedelta(seconds=time.time() - start_time))))

if __name__ == '__main__':
	main()
