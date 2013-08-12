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
import lib.info as info
import signal
import datetime

print("\n\nGrabNZBs Threaded Started at %s" % (datetime.datetime.now().strftime("%H:%M:%S")))

start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))
conf = info.readConfig()

#create the connection to mysql
con = None
con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], passwd=conf['DB_PASSWORD'], db=conf['DB_NAME'], port=int(conf['DB_PORT']), unix_socket=conf['DB_SOCKET'])
cur = con.cursor()

#get array of collectionhash
cur.execute("select value from site where setting = 'grabnzbs'")
grab = cur.fetchone()
if int(grab[0]) == 0:
	sys.exit("GrabNZBs is disabled")

cur.execute("select collectionhash from nzbs group by collectionhash, totalparts having count(*) >= totalparts union select distinct(collectionhash) from nzbs where dateadded < now() - interval 2 hour")
datas = cur.fetchall()
if len(datas) == 0:
	sys.exit("No NZBs to Grab")

#get threads for update_binaries
cur.execute("select value from site where setting = 'grabnzbthreads'")
run_threads = cur.fetchone()

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
					subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/grabnzbs.php", ""+my_id])
					time.sleep(.5)
					self.my_queue.task_done()

def main():
	global time_of_last_run
	time_of_last_run = time.time()

	print("We will be using a max of %s threads, a queue of %s nzbs" % (run_threads[0], "{:,}".format(len(datas[0]))))
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
	for gnames in datas:
		my_queue.put(gnames[0])

	my_queue.join()

	final = "limited"
	subprocess.call(["php", pathname+"/../../testing/DB_scripts/populate_nzb_guid.php", ""+final])
	print("\n\nGrabNZBs Threaded Completed at %s" % (datetime.datetime.now().strftime("%H:%M:%S")))
	print("Running time: %s" % (str(datetime.timedelta(seconds=time.time() - start_time))))


if __name__ == '__main__':
	main()
