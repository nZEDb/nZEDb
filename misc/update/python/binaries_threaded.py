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
cur = info.connect()
start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))

if len(sys.argv) == 1:
	print(bcolors.HEADER + "\nThis script will run update_binaries per group."
		"\nThis script can run on 1 group, an array of groups or all groups.\n"
		"\nEach group is processed in a single thread, for all groups. For example, 10 groups, 10 threads, upto max threads.\n"
		"\npython " + sys.argv[0] + " 155              ...: To run against group_id 155."
		"\npython " + sys.argv[0] + " '(155, 52)'      ...: To run against group_id 155 and 52."
		"\npython " + sys.argv[0] + " alt.binaries.tv  ...: To run against group alt.binaries.teevee."
		"\npython " + sys.argv[0] + "                  ...: To run against all active groups." + bcolors.ENDC)

print(bcolors.HEADER + "\nBinaries Threaded Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)


#get active groups
if len(sys.argv) == 2:
	try:
		cur[0].execute("SELECT name FROM groups WHERE id IN " + sys.argv[1])
		datas = cur[0].fetchall()
	except:
		cur[0].execute("SELECT name FROM groups WHERE name = '" + sys.argv[1] + "'")
		datas = cur[0].fetchall()
		if len(datas) == 0:
			cur[0].execute("SELECT name FROM groups WHERE id = " + sys.argv[1])
			datas = cur[0].fetchall()
			if len(datas) == 0:
				print(bcolors.ERROR + "No Active Groups" + bcolors.ENDC)
else:
	cur[0].execute("SELECT name FROM groups WHERE active = 1")
	datas = cur[0].fetchall()

if len(datas) == 0:
	print(bcolors.ERROR + "No Active Groups" + bcolors.ENDC)
	info.disconnect(cur[0], cur[1])
	sys.exit

cur[0].execute("SELECT (SELECT value FROM settings WHERE setting = 'binarythreads') AS a")
dbgrab = cur[0].fetchall()
run_threads = int(dbgrab[0][0])

#close connection to mysql
info.disconnect(cur[0], cur[1])

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
				my_id = self.my_queue.get(True, .5)
			except:
				if time.time() - time_of_last_run > 3:
					return
			else:
				if my_id:
					time_of_last_run = time.time()
					subprocess.call(["php", pathname+"/../update_binaries.php", ""+my_id])
					self.my_queue.task_done()

def main():
	global time_of_last_run
	time_of_last_run = time.time()

	print(bcolors.HEADER + "We will be using a max of {} threads, a queue of {} groups".format(run_threads, "{:,}".format(len(datas))) + bcolors.ENDC)
	time.sleep(2)

	def signal_handler(signal, frame):
		sys.exit(0)

	signal.signal(signal.SIGINT, signal_handler)

	if True:
		#spawn a pool of worker threads
		for i in range(run_threads):
			p = queue_runner(my_queue)
			#p.setDaemon(False)
			p.start()

	#now load some arbitrary jobs into the queue
	for gnames in datas:
		time.sleep(.03)
		my_queue.put(gnames[0])

	my_queue.join()

	print(bcolors.HEADER + "\nBinaries Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)
	print(bcolors.HEADER + "Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))) + bcolors.ENDC)

if __name__ == '__main__':
	main()
