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
import math

import lib.info as info
from lib.info import bcolors
conf = info.readConfig()
cur = info.connect()
start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))
count = 0

print(bcolors.HEADER + "\nBinaries Safe Threaded Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)

#get values from db
cur[0].execute("SELECT (SELECT value FROM settings WHERE setting = 'binarythreads') AS a, (SELECT value FROM settings WHERE setting = 'maxmssgs') AS b")
dbgrab = cur[0].fetchall()

run_threads = int(dbgrab[0][0])
maxmssgs = int(dbgrab[0][1])

#before we get the groups, lets update short_groups
subprocess.call(["php", pathname+"/../nix/tmux/bin/update_groups.php", ""])

#query to grab all active groups
cur = info.connect()
cur[0].execute("SELECT g.name AS groupname, g.last_record AS our_last, a.last_record AS their_last FROM groups g INNER JOIN short_groups a ON g.active = 1 AND g.name = a.name ORDER BY a.last_record DESC")
datas = cur[0].fetchall()

#close connection to mysql
info.disconnect(cur[0], cur[1])

if not datas:
	print(bcolors.ERROR + "No Groups activated" + bcolors.ENDC)
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
					subprocess.call(["php", pathname+"/../nix/multiprocessing/.do_not_run/switch.php", "python  "+my_id])
					time.sleep(.03)
					self.my_queue.task_done()

def main():
	global time_of_last_run
	time_of_last_run = time.time()

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
	run = 0
	finals = []
	groups = []
	s = name = ""
	for group in datas:
		time.sleep(.03)
		#start new groups using binaries.php, no need to check pynntp
		if group[1] == 0:
			run += 1
			my_queue.put("update_group_headers  %s" % (group[0]))
			time.sleep(0.01)
		elif group[1] != 0:
			#only process if more that 20k headers available and skip the first 20k
			count = group[2] - group[1] - 20000
			#run small groups using binaries.php
			if count <= maxmssgs * 2:
				run += 1
				my_queue.put("update_group_headers  %s" % (group[0]))
			#thread large groups using backfill.php
			else:
				my_queue.put("part_repair  %s" % (group[0]))
				geteach = math.floor(count / maxmssgs)
				remaining = count - geteach * maxmssgs
				for loop in range(int(geteach)):
					run += 1
					my_queue.put("get_range  binaries  %s  %s  %s  %s" % (group[0], group[1] + loop * maxmssgs + 1, group[1] + loop * maxmssgs + maxmssgs, run))
				run += 1
				my_queue.put("get_range  binaries  %s  %s  %s  %s" % (group[0], group[1] + (loop + 1) * maxmssgs + 1, group[1] + (loop + 1) * maxmssgs + remaining + 1, run))
				groups.append(group[0])
				finals.append(int(group[2]))

	my_queue.join()

	print(bcolors.HEADER + "\nBinaries Safe Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)
	print(bcolors.HEADER + "Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))) + bcolors.ENDC)

if __name__ == '__main__':
	main()
