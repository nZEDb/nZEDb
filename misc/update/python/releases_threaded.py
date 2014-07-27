#!/usr/bin/env python
# -*- coding: utf-8 -*-

import sys, os, time
import threading
try:
	import queue
except ImportError:
	import Queue as queue
import subprocess
import string
import signal
import datetime

import lib.info as info
from lib.info import bcolors
conf = info.readConfig()
cur = info.connect()
start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))

print(bcolors.HEADER + "\nUpdate Releases Threaded Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)

cur[0].execute("SELECT (SELECT value FROM settings WHERE setting = 'tablepergroup') AS a, (SELECT value FROM settings WHERE setting = 'releasesthreads') AS b")
dbgrab = cur[0].fetchall()
allowed = int(dbgrab[0][0])
threads = int(dbgrab[0][1])
if allowed == 0:
	print(bcolors.ERROR + "Table per group not enabled" + bcolors.ENDC)
	info.disconnect(cur[0], cur[1])
	sys.exit()

cur[0].execute("SELECT table_name FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '"+conf['DB_NAME']+"' AND table_rows > 0 AND table_name LIKE 'collections_%'")
datas = cur[0].fetchall()

#close connection to mysql
info.disconnect(cur[0], cur[1])

if not datas:
	print(bcolors.HEADER + "No Work to Process" + bcolors.ENDC)
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
					subprocess.call(["php", pathname+"/../nix/multiprocessing/.do_not_run/switch.php", "python  releases  "+my_id])
					self.my_queue.task_done()

def main():
	global time_of_last_run
	time_of_last_run = time.time()

	print(bcolors.HEADER + "We will be using a max of {} threads, a queue of {} groups".format(threads, "{:,}".format(len(datas))) + bcolors.ENDC)
	time.sleep(2)

	def signal_handler(signal, frame):
		sys.exit(0)

	signal.signal(signal.SIGINT, signal_handler)

	if True:
		#spawn a pool of place worker threads
		for i in range(threads):
			p = queue_runner(my_queue)
			p.setDaemon(False)
			p.start()

	#now load some arbitrary jobs into the queue
	count = 0
	for release in datas:
		if count >= threads:
			count = 0
		count += 1
		my_queue.put("%s  %s" % (release[0].replace('collections_', ''), count))

	my_queue.join()

	#stage7b
	subprocess.call(["php", pathname+"/../nix/multiprocessing/.do_not_run/switch.php", "python  releases  "+str(count)+"_"])

	print(bcolors.HEADER + "\nUpdate Releases Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)
	print(bcolors.HEADER + "Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))) + bcolors.ENDC)

if __name__ == '__main__':
	main()
