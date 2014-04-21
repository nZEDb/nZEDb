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
start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))
threads = 10

print(bcolors.HEADER + "\nGrab Headers Threaded Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)

if len(sys.argv) > 1:
	datas = []
	datas.append(sys.argv[1])
else:
	datas = ("alt.binaries.teevee", "alt.binaries.tv", "alt.binaries.audiobooks", "alt.binaries.moovee", "alt.binaries.e-book",  "alt.binaries.e-book.technical",  "alt.binaries.ebook", "alt.binaries.e-book.magazines")
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
					subprocess.call(["php", pathname+"/../../testing/Regex/grabheaders.php", ""+my_id])
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
	for group in datas:
		my_queue.put(group)

	my_queue.join()

	print(bcolors.HEADER + "\nGrab Headers Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)
	print(bcolors.HEADER + "Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))) + bcolors.ENDC)

if __name__ == '__main__':
	main()
