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

print("\nPartrepair Threaded Started at %s" % (datetime.datetime.now().strftime("%H:%M:%S")))

start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))
conf = info.readConfig()

#create the connection to mysql
con = None
con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], passwd=conf['DB_PASSWORD'], db=conf['DB_NAME'], port=int(conf['DB_PORT']), unix_socket=conf['DB_SOCKET'])
cur = con.cursor()

cur.execute("select value from site where setting = 'partrepair'")
torun = cur.fetchone()
if int(torun[0]) != 2:
	sys.exit("Part Repair Threaded is disabled")
cur.execute("select (select value from site where setting = 'binarythreads') as a, (select value from site where setting = 'maxpartrepair') as b")
dbgrab = cur.fetchall()

run_threads = int(dbgrab[0][0])
maxpartrepair = int(dbgrab[0][1])
datas = []
maxtries = 0

while (len(datas) < run_threads * maxpartrepair) and maxtries < 5:
	cur.execute("select groupID, numberID from partrepair where attempts between %d and 0 limit %d" % (maxtries, run_threads * maxpartrepair))
	datas = cur.fetchall()
	maxtries = maxtries + 1

#close connection to mysql
cur.close()
con.close()

if not datas:
	print("Part Repair has no Work to Process")
	time.sleep(2)
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
					subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/binaries.php", ""+my_id])
					time.sleep(.5)
					self.my_queue.task_done()

def main():
	global time_of_last_run
	time_of_last_run = time.time()

	print("We will be using a max of %s threads, a queue of %s groups" % (run_threads, "{:,}".format(len(datas))))
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
	for release in datas:
		my_queue.put("%s %s" % (release[0], release[1]))

	my_queue.join()

	print("\nPartrepair Threaded Completed at %s" % (datetime.datetime.now().strftime("%H:%M:%S")))
	print("Running time: %s" % (str(datetime.timedelta(seconds=time.time() - start_time))))

if __name__ == '__main__':
	main()
