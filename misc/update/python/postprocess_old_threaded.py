#!/usr/bin/env python
# -*- coding: utf-8 -*-

from __future__ import print_function
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
start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))
run_threads = 1

#get number of threads from db
if len(sys.argv) == 1 or sys.argv[1] != "amazon":
	print(bcolors.ERROR + "\nAn argument is required, \npostprocess_old_threaded.py amazon\n" + bcolors.ENDC)
	sys.exit()

print(bcolors.HEADER + "\nPostProcess Old Threaded Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)

#create a list
try:
	datas = list(range(1, int(run_threads) + 1))
except:
	datas = list(xrange(1, int(run_threads) + 1))

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
					if len(sys.argv) > 1 and sys.argv[1] == "amazon":
						subprocess.call(["php", pathname+"/../nix/tmux/bin/postprocess_amazon.php", ""+my_id])
					time.sleep(.03)
					self.my_queue.task_done()

def main(args):
	global time_of_last_run
	time_of_last_run = time.time()

	print(bcolors.HEADER + "We will be using a max of 1 threads to process Amazon Lookups".format(run_threads, "{:,}".format(len(datas))) + bcolors.ENDC)
	time.sleep(2)

	def signal_handler(signal, frame):
		sys.exit(0)

	signal.signal(signal.SIGINT, signal_handler)

	if True:
		#spawn a pool of place worker threads
		for i in range(int(run_threads)):
			p = queue_runner(my_queue)
			p.setDaemon(False)
			p.start()

	#now load some arbitrary jobs into the queue
	for gnames in datas:
		time.sleep(.03)
		my_queue.put(str(gnames))

	my_queue.join()

	print(bcolors.HEADER + "\nPostProcess Old Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)
	print(bcolors.HEADER + "Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))) + bcolors.ENDC)

if __name__ == '__main__':
	main(sys.argv[1:])
