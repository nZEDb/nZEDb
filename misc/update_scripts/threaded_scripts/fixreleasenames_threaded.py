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

print("\nfixReleasesNames Threaded Started at %s" % (datetime.datetime.now().strftime("%H:%M:%S")))
if len(sys.argv) == 1:
	sys.exit("\nAn argument is required, \npostprocess_threaded.py [md5, nfo, filename]\n")

start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))
conf = info.readConfig()

#create the connection to mysql
con = None
con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], passwd=conf['DB_PASSWORD'], db=conf['DB_NAME'], port=int(conf['DB_PORT']), unix_socket=conf['DB_SOCKET'])
cur = con.cursor()

cur.execute("SELECT value FROM site WHERE setting = 'fixnamethreads'")
run_threads = cur.fetchone()
	
if len(sys.argv) > 1 and (sys.argv[1] == "nfo"):
	cur.execute("SELECT DISTINCT rel.id AS releaseid FROM releases rel INNER JOIN releasenfo nfo ON (nfo.releaseid = rel.id) WHERE categoryid != 5070 AND relnamestatus IN (0, 1, 21) ORDER BY postdate DESC LIMIT 10000")
	datas = cur.fetchall()
elif len(sys.argv) > 1 and (sys.argv[1] == "filename"):
	cur.execute("SELECT DISTINCT rel.id AS releaseid FROM releases rel INNER JOIN releasefiles relfiles ON (relfiles.releaseid = rel.id) WHERE categoryid != 5070 AND relnamestatus IN (0, 1, 20) ORDER BY postdate DESC LIMIT 10000")
	datas = cur.fetchall()
elif len(sys.argv) > 1 and (sys.argv[1] == "md5"):
	cur.execute("SELECT DISTINCT rel.id FROM releases rel LEFT JOIN releasefiles rf ON rel.id = rf.releaseid WHERE rel.relnamestatus IN (0, 1, 20, 21) AND rel.passwordstatus >= -1 AND (rel.name REGEXP'[a-fA-F0-9]{32}' OR rf.name REGEXP'[a-fA-F0-9]{32}') ORDER BY postdate DESC LIMIT 10000")
	datas = cur.fetchall()

#close connection to mysql
cur.close()
con.close()

if not datas:
	print("No Work to Process")
	sys.exit()

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
					subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/fixreleasenames.php", ""+my_id])
					time.sleep(.1)
					self.my_queue.task_done()

def main():
	global time_of_last_run
	time_of_last_run = time.time()

	print("We will be using a max of %s threads, a queue of %s %s releases" % (run_threads[0], "{:,}".format(len(datas)), sys.argv[1]))
	time.sleep(2)

	def signal_handler(signal, frame):
		sys.exit(0)

	signal.signal(signal.SIGINT, signal_handler)

	if True:
		#spawn a pool of place worker threads
		for i in range(int(run_threads[0])):
			p = queue_runner(my_queue)
			p.setDaemon(False)
			p.start()

	#now load some arbitrary jobs into the queue
	if sys.argv[1] == "nfo":
		for release in datas:
			my_queue.put("%s %s" % ("nfo", release[0]))
	elif sys.argv[1] == "filename":
		for release in datas:
			my_queue.put("%s %s" % ("filename", release[0]))
	elif sys.argv[1] == "md5":
		for release in datas:
			my_queue.put("%s %s" % ("md5", release[0]))

	my_queue.join()

	print("\nfixReleaseNames Threaded Completed at %s" % (datetime.datetime.now().strftime("%H:%M:%S")))
	print("Running time: %s" % (str(datetime.timedelta(seconds=time.time() - start_time))))

if __name__ == '__main__':
	main()
