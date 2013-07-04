#!/usr/bin/python
# -*- coding: utf-8 -*-

import sys, os, time
import threading
try:
	import queue
except ImportError:
	import Queue as queue
try:
	import cymysql as mdb
except ImportError:
	sys.exit("\nPlease install cymysql for python 3, \ninformation can be found in INSTALL.txt\n")
import subprocess
import string
import info
import signal
import datetime

start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))
conf = info.readConfig()

#create the connection to mysql
con = None
if conf['DB_SOCKET'] == 'false':
        con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], passwd=conf['DB_PASSWORD'], db=conf['DB_NAME'], port=int(conf['DB_PORT']))
else:
	con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], passwd=conf['DB_PASSWORD'], db=conf['DB_NAME'], port=int(conf['DB_PORT']), unix_socket=conf['DB_SOCKET'])
cur = con.cursor()

#get active groups
cur.execute("SELECT name from groups where active = 1")
datas = cur.fetchall()
if len(datas) == 0:
	sys.exit("No Active Groups")

#get threads for update_binaries
cur.execute("select value from site where setting = 'binarythreads'")
run_threads = cur.fetchone()


#close connection to mysql
cur.close()
con.close()

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
					subprocess.call(["php", pathname+"/../update_binaries.php", ""+my_id])
					time.sleep(.5)
					self.my_queue.task_done()

def main():
	global time_of_last_run
	time_of_last_run = time.time()

	def signal_handler(signal, frame):
		sys.exit(0)

	signal.signal(signal.SIGINT, signal_handler)
	
	if True:
		#spawn a pool of place worker threads
		for i in range(int(run_threads[0])):
			p = queue_runner(my_queue)
			p.setDaemon(True)
			p.start()

	print("\nUpdate Binaries Threaded Started at %s" %(datetime.datetime.now().strftime("%H:%M:%S")))

	#now load some arbitrary jobs into the queue
	for gnames in datas:
		my_queue.put(gnames[0])

	my_queue.join()

if __name__ == '__main__':
	main()

print("\nUpdate Binaries Threaded Completed at %s" %(datetime.datetime.now().strftime("%H:%M:%S")))
print("Running time: %s" %(str(datetime.timedelta(seconds=time.time() - start_time))))
