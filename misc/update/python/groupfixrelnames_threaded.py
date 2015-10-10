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
	print(bcolors.ERROR + "\n\An invalid argument was supplied\ngroupfixrelnames_threaded.py [md5, nfo, filename, par2, miscsorter, predbft]\n" + bcolors.ENDC)
	sys.exit()

print(bcolors.HEADER + "\nfixReleaseNames {} Threaded Started at {}".format(sys.argv[1], datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)

datas = []
limit = 0

join = ""
where = ""
datelimit = "AND DATEDIFF(NOW(), r.adddate) <= 7"
groupby = "GROUP BY guidchar"
orderby = "ORDER BY guidchar ASC"
rowlimit = "LIMIT 16"
extrawhere = "AND r.preid = 0 AND r.nzbstatus = 1"
select = "DISTINCT LEFT(r.guid, 1) AS guidchar, COUNT(*) AS count"

cur[0].execute("SELECT value FROM settings WHERE setting = 'fixnamethreads'")
run_threads = cur[0].fetchone()
cur[0].execute("SELECT value FROM settings WHERE setting = 'fixnamesperrun'")
run_perrun = cur[0].fetchone()

threads = int(run_threads[0])
if threads > 16:
	threads = 16
maxperrun = int(run_perrun[0])

if sys.argv[1] == "md5":
	join = "LEFT OUTER JOIN release_files rf ON r.id = rf.releaseid AND rf.ishashed = 1"
	where = "r.ishashed = 1 AND r.dehashstatus BETWEEN -6 AND 0"
elif sys.argv[1] == "nfo":
	where = "r.proc_nfo = 0 AND r.nfostatus = 1"
elif sys.argv[1] == "filename":
	join = "INNER JOIN release_files rf ON r.id = rf.releaseid"
	where = "r.proc_files = 0"
elif sys.argv[1] == "par2":
	where = "r.proc_par2 = 0"
elif sys.argv[1] == "miscsorter":
	where = "r.nfostatus = 1 AND r.proc_nfo = 1 AND r.proc_sorter = 0 AND r.isrenamed = 0"
elif sys.argv[1] == "predbft":
	extrawhere = ""
	where = "1=1"
	rowlimit = "LIMIT %s" % (threads)

cur[0].execute("SELECT %s FROM releases r %s WHERE %s %s %s %s %s" % (select, join, where, extrawhere, groupby, orderby, rowlimit))
datas = cur[0].fetchall()

guids = int(len(datas))

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

	print(bcolors.HEADER + "We will be using a max of {} threads, a total of {} guid prefixes with a maximum of {} per thread.".format(threads, guids, maxperrun) + bcolors.ENDC)
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

	for firstguid in datas:
		if count >= threads:
			count = 0
		count += 1
		if firstguid[1] < maxperrun:
			limit = firstguid[1]
		else:
			limit = maxperrun
		time.sleep(.03)
		if limit > 0:
			my_queue.put("%s %s %s %s" % (sys.argv[1], firstguid[0], limit, count))

	my_queue.join()

	print(bcolors.HEADER + "\nfixReleaseNames Per GUID Prefix Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)
	print(bcolors.HEADER + "Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))) + bcolors.ENDC)

if __name__ == '__main__':
	main()
