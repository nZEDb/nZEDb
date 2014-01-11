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
con = None
if conf['DB_SYSTEM'] == "mysql":
	try:
		import cymysql as mdb
		con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], passwd=conf['DB_PASSWORD'], db=conf['DB_NAME'], port=int(conf['DB_PORT']), unix_socket=conf['DB_SOCKET'], charset="utf8")
	except ImportError:
		print(bcolors.ERROR + "\nPlease install cymysql for python 3, \ninformation can be found in INSTALL.txt\n" + bcolors.ENDC)
		sys.exit()
elif conf['DB_SYSTEM'] == "pgsql":
	try:
		import psycopg2 as mdb
		con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], password=conf['DB_PASSWORD'], dbname=conf['DB_NAME'], port=int(conf['DB_PORT']))
	except ImportError:
		print(bcolors.ERROR + "\nPlease install cymysql for python [2, 3], \nInformation can be found in INSTALL.txt\n" + bcolors.ENDC)
		sys.exit()
cur = con.cursor()

threads = 15
print(bcolors.HEADER + "\nUpdate Per Group Threaded Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)

start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))
conf = info.readConfig()

cur.execute("SELECT value FROM site WHERE setting = 'tablepergroup'")
allowed = cur.fetchone()
if int(allowed[0]) == 0:
	print(bcolors.ERROR + "Table per group not enabled" + bcolors.ENDC)
	sys.exit()

cur.execute("SELECT id FROM groups WHERE active = 1 ORDER by cast(last_record as signed) - cast(first_record as signed) DESC")
datas = cur.fetchall()

if not datas:
	print(bcolors.HEADER + "No Work to Process" + bcolors.ENDC)
	cur.close()
	con.close()
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
					subprocess.call(["php", pathname+"/../nix/tmux/bin/update_per_group.php", ""+my_id])
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
		my_queue.put("%s  %s" % (str(release[0]), count))

	my_queue.join()
	cur.close()
	con.close()

	#stage7b
	final = "Stage7b"
	subprocess.call(["php", pathname+"/../nix/tmux/bin/update_releases.php", ""+str(final)])

	print(bcolors.HEADER + "\nUpdate Releases Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)
	print(bcolors.HEADER + "Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))) + bcolors.ENDC)

if __name__ == '__main__':
	main()
