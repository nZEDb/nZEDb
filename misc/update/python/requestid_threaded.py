#!/usr/bin/env python
# -*- coding: utf-8 -*-

from __future__ import print_function
import sys, os, time
import threading
import subprocess
import string
import signal
import datetime
import re
try:
	import queue
except ImportError:
	import Queue as queue

try:
	import urllib2
except ImportError:
	import urllib.request as urllib2

import lib.info as info
from lib.info import bcolors
conf = info.readConfig()
cur = info.connect()
start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))
cur[0].execute("SELECT value FROM settings WHERE setting = 'reqidthreads'")
threads = cur[0].fetchone()
threads = int(threads[0])


print(bcolors.HEADER + "\n\nRequestID Threaded Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)

cur[0].execute("SELECT value FROM settings WHERE setting = 'request_hours'")
dbgrab = cur[0].fetchone()
request_hours = str(dbgrab[0])
cur[0].execute("SELECT DISTINCT(g.id) FROM releases r INNER JOIN groups g ON r.groups_id = g.id WHERE r.nzbstatus = 1 AND r.predb_id = 0 AND r.isrequestid = 1 AND r.reqidstatus in (0, -1) OR (r.reqidstatus = -3 AND r.adddate > NOW() - INTERVAL " + request_hours + " HOUR)")
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
					subprocess.call(["php", pathname+"/../nix/multiprocessing/.do_not_run/switch.php", "python  requestid  "+my_id])
					time.sleep(.03)
					self.my_queue.task_done()

def main():
	global time_of_last_run
	time_of_last_run = time.time()

	def signal_handler(signal, frame):
		sys.exit(0)

	signal.signal(signal.SIGINT, signal_handler)

	if True:
		print(bcolors.HEADER + "We will be using a max of {} threads, a queue of {} items".format(threads, "{:,}".format(len(datas))) + bcolors.ENDC)
		time.sleep(2)

		#spawn a pool of place worker threads
		for i in range(threads):
			p = queue_runner(my_queue)
			p.setDaemon(False)
			p.start()

	#now load some arbitrary jobs into the queue
	for release in datas:
		my_queue.put("%s" % (release[0]))

	my_queue.join()

	print(bcolors.HEADER + "\nRequestID Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)
	print(bcolors.HEADER + "Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))) + bcolors.ENDC)

if __name__ == '__main__':
	main()
