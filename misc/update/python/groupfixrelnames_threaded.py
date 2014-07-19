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

if len(sys.argv) == 1:
	print(bcolors.ERROR + "\nAn argument is required\n\n"
		+ "python " + sys.argv[0] + " [md5, nfo, filename, par2, miscsorter, predbft]     ...: To process all previously unprocessed releases, using [md5, nfo, filename, par2, miscsorter, predbft].\n"
		+ bcolors.ENDC)
	sys.exit()

if sys.argv[1] != "nfo" and sys.argv[1] != "filename" and sys.argv[1] != "md5" and sys.argv[1] != "par2" and sys.argv[1] != "miscsorter" and sys.argv[1] != "predbft":
	print(bcolors.ERROR + "\n\An invalid argument was supplied\npostprocess_threaded.py [md5, nfo, filename, par2, miscsorter, predbft]\n" + bcolors.ENDC)
	sys.exit()

print(bcolors.HEADER + "\nfixReleaseNames Per Group Threaded Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)

datas = []

cur[0].execute("SELECT value FROM settings WHERE setting = 'fixnamethreads'")
run_threads = cur[0].fetchone()
cur[0].execute("SELECT value FROM settings WHERE setting = 'fixnamesperrun'")
run_perrun = cur[0].fetchone()
cur[0].execute("SELECT id FROM groups WHERE active = 1 ORDER BY CAST(last_record AS SIGNED) - CAST(first_record AS SIGNED) DESC")
datas = cur[0].fetchall()

threads = int(run_threads[0])
groups = int(len(datas))
maxperrun = int(run_perrun[0])

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
					subprocess.call(["php", pathname+"/../nix/tmux/bin/groupfixrelnames.php", ""+my_id])
					self.my_queue.task_done()

def main():
	global time_of_last_run
	time_of_last_run = time.time()

	print(bcolors.HEADER + "We will be using a max of {} threads, a queue of {} groups with a maximum of {} per group using {}.".format(threads, groups, maxperrun, sys.argv[1]) + bcolors.ENDC)
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

	for groupid in datas:
		if count >= threads:
			count = 0
		count += 1
		time.sleep(.03)
		my_queue.put("%s %s %s" % (sys.argv[1], groupid[0], maxperrun))

	my_queue.join()

	print(bcolors.HEADER + "\nfixReleaseNames Per Group Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)
	print(bcolors.HEADER + "Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))) + bcolors.ENDC)

if __name__ == '__main__':
	main()
