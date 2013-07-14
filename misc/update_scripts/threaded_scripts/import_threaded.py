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

print("\nNZB Import Threaded Started at %s" % (datetime.datetime.now().strftime("%H:%M:%S")))

start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))
conf = info.readConfig()

#create the connection to mysql
con = None
con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], passwd=conf['DB_PASSWORD'], db=conf['DB_NAME'], port=int(conf['DB_PORT']), unix_socket=conf['DB_SOCKET'])
cur = con.cursor()

#get valuse from db
cur.execute("select value from tmux where setting = 'IMPORT'")
use_true = cur.fetchone()
cur.execute("select (select value from site where setting = 'nzbthreads') as a, (select value from tmux where setting = 'NZBS') as b, (select value from tmux where setting = 'IMPORT_BULK') as c")
dbgrab = cur.fetchall()
run_threads = int(dbgrab[0][0])
nzbs = dbgrab[0][1]
bulk = dbgrab[0][2]

print("Sorting Folders in %s, be patient." % (nzbs))
datas = [name for name in os.listdir(nzbs) if os.path.isdir(os.path.join(nzbs, name))]

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
					if bulk == 'FALSE':
						subprocess.call(["php", pathname+"/../../testing/nzb-import.php", ""+my_id])
					else:
						subprocess.call(["php", pathname+"/../../testing/Bulk_import_linux/nzb-import-bulk.php", ""+my_id])
					time.sleep(.5)
					self.my_queue.task_done()

def main(args):
	global time_of_last_run
	time_of_last_run = time.time()

	if int(use_true[0]) == 2 or sys.argv[1] == "true":
		print("We will be using filename as searchname")
	print("We will be using a max of %s threads, a queue of %s folders" % (run_threads, "{:,}".format(len(datas))))
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
	if len(datas) != 0:
		if int(use_true[0]) == 1:
			for gnames in datas:
				my_queue.put(os.path.join(nzbs,gnames))
		elif int(use_true[0]) == 2 or sys.argv[1] == "true":
			for gnames in datas:
				my_queue.put('%s %s' % (os.path.join(nzbs,gnames), "true"))
	if len(datas) == 0:
		if int(use_true[0]) == 1:
			my_queue.put(nzbs)
		elif int(use_true[0]) == 2 or sys.argv[1] == "true":
			my_queue.put("%s %s" % (nzbs, "true"))

	my_queue.join()

	final = "true"
	subprocess.call(["php", pathname+"/../../testing/DB_scripts/populate_nzb_guid.php", ""+final])
	print("\nNZB Import Threaded Completed at %s" % (datetime.datetime.now().strftime("%H:%M:%S")))
	print("Running time: %s" % (str(datetime.timedelta(seconds=time.time() - start_time))))


if __name__ == '__main__':
	main(sys.argv[1:])
