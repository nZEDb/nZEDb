#!/usr/bin/python
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
conf = info.readConfig()
def connect():
    con = None
    if conf['DB_SYSTEM'] == "mysql":
        try:
            import cymysql as mdb
            con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], passwd=conf['DB_PASSWORD'], db=conf['DB_NAME'], port=int(conf['DB_PORT']), unix_socket=conf['DB_SOCKET'])
        except ImportError:
            sys.exit("\nPlease install cymysql for python 3, \ninformation can be found in INSTALL.txt\n")
    elif conf['DB_SYSTEM'] == "pgsql":
        try:
            import psycopg2 as mdb
            con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], password=conf['DB_PASSWORD'], dbname=conf['DB_NAME'], port=int(conf['DB_PORT']))
        except ImportError:
            sys.exit("\nPlease install psycopg for python 3, \ninformation can be found in INSTALL.txt\n")
    cur = con.cursor()
    return cur, con

def disconnect(cur, con):
    con.close()
    con = None
    cur.close()
    cur = None

start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))

print("\nUpdate Releases Threaded Started at {}".format(datetime.datetime.now().strftime("%H:%M:%S")))

cur = connect()
cur[0].execute("SELECT (SELECT value FROM site WHERE setting = 'tablepergroup') AS a, (SELECT value FROM site WHERE setting = 'releasesthreads') AS b")
dbgrab = cur[0].fetchall()
allowed = int(dbgrab[0][0])
threads = int(dbgrab[0][1])
if allowed == 0:
	sys.exit("Table per group not enabled")

cur[0].execute("SELECT id FROM groups")
datas = cur[0].fetchall()
disconnect(cur[0], cur[1])

if not datas:
	print("No Work to Process")
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
					subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/update_releases.php", ""+my_id])
					self.my_queue.task_done()

def main():
	global time_of_last_run
	time_of_last_run = time.time()

	print("We will be using a max of {} threads, a queue of {} groups".format(threads, "{:,}".format(len(datas))))
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

	#stage7b
	final = "Stage7b"
	subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/update_releases.php", ""+str(final)])

	print("\nUpdate Releases Threaded Completed at {}".format(datetime.datetime.now().strftime("%H:%M:%S")))
	print("Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))))

if __name__ == '__main__':
	main()
