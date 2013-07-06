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
con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], passwd=conf['DB_PASSWORD'], db=conf['DB_NAME'], port=int(conf['DB_PORT']), unix_socket=conf['DB_SOCKET'])
cur = con.cursor()

if len(sys.argv) > 1 and (sys.argv[1] == "additional" or sys.argv[1] == "nfo"):
	cur.execute("select (select value from site where setting = 'postthreads') as a, (select value from site where setting = 'maxaddprocessed') as b, (select value from site where setting = 'maxnfoprocessed') as c, (select value from site where setting = 'maximdbprocessed') as d, (select value from site where setting = 'maxrageprocessed') as e, (select value from site where setting = 'maxsizetopostprocess') as f, (select value from site where setting = 'tmpunrarpath') as g, (select value from tmux where setting = 'POST') as h")
	dbgrab = cur.fetchall()
elif len(sys.argv) > 1 and (sys.argv[1] == "movie" or sys.argv[1] == "tv"):
	cur.execute("select(select value from site where setting = 'postthreadsnon') as a, (select value from site where setting = 'maxaddprocessed') as b, (select value from site where setting = 'maxnfoprocessed') as c, (select value from site where setting = 'maximdbprocessed') as d, (select value from site where setting = 'maxrageprocessed') as e, (select value from site where setting = 'maxsizetopostprocess') as f, (select value from site where setting = 'tmpunrarpath') as g")
	dbgrab = cur.fetchall()
else:
	sys.exit("\nAn argument is required, \npostprocess_threaded.py [additional, nfo, movie, tv]\n")

run_threads = int(dbgrab[0][0])
ppperrun = int(dbgrab[0][1])
nfoperrun = int(dbgrab[0][2])
movieperrun = int(dbgrab[0][3])
tvrageperrun = int(dbgrab[0][4])
maxsizeck = int(dbgrab[0][5])
tmppath = dbgrab[0][6]
posttorun = int(dbgrab[0][7])
if posttorun == 0:
	sys.exit()
	
maxtries = -1
if maxsizeck == 0:
	maxsize = ''
else:
	maxsize = "r.size < %d and "%(int(maxsizeck * 1073741824))
datas = []
maxtries = -1

if sys.argv[1] == "additional" and (posttorun == 1 or posttorun == 3):
	while len(datas) < run_threads * ppperrun and maxtries >= -5:
		cur.execute("select r.ID, r.guid, r.name, c.disablepreview, r.size, r.groupID, r.nfostatus from releases r left join category c on c.ID = r.categoryID where %s r.passwordstatus between %d and -1 and (r.haspreview = -1 and c.disablepreview = 0) and nzbstatus = 1 order by r.postdate desc limit %d" %(maxsize, maxtries, run_threads * ppperrun))
		datas = cur.fetchall()
		maxtries = maxtries - 1
elif sys.argv[1] == "nfo" and (posttorun == 2 or posttorun == 3):
	while len(datas) < run_threads * nfoperrun and maxtries >= -5:
		cur.execute("SELECT r.ID, r.guid, r.groupID, r.name FROM releases r WHERE %s r.nfostatus between %d and -1 and r.nzbstatus = 1 order by r.postdate desc limit %d" %(maxsize, maxtries, run_threads * nfoperrun))
		datas = cur.fetchall()
		maxtries = maxtries - 1
elif sys.argv[1] == "movie":
		cur.execute("SELECT searchname as name, ID, categoryID from releases where imdbID IS NULL and nzbstatus = 1 and categoryID in ( select ID from category where parentID = 2000 ) order by postdate desc limit %d" %(run_threads * movieperrun))
		datas = cur.fetchall()
elif sys.argv[1] == "tv":
		cur.execute("SELECT searchname, ID from releases where rageID = -1 and nzbstatus = 1 and categoryID in ( select ID from category where parentID = 5000 ) order by postdate desc limit %d" %(run_threads * tvrageperrun))
		datas = cur.fetchall()

#close connection to mysql
cur.close()
con.close()

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
					subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/postprocess_new.php", ""+my_id])
					time.sleep(.5)
					self.my_queue.task_done()

def main():
	global time_of_last_run
	time_of_last_run = time.time()

	def signal_handler(signal, frame):
		sys.exit(0)

	signal.signal(signal.SIGINT, signal_handler)

	if sys.argv[1] == "additional":
		print("Fetch for: b = binary, s = sample, m = mediainfo, a = audio, j = jpeg")
		print("^ added file content, o added previous, z = doing zip, r = doing rar, n = found nfo - %s." %(time.strftime("%H:%M:%S")))
	elif sys.argv[1] == "nfo":
		print("* = hidden NFO, + = NFO, - = no NFO, f = download failed  - %s." %(time.strftime("%H:%M:%S")))

	if True:
		#spawn a pool of place worker threads
		for i in range(run_threads):
			p = queue_runner(my_queue)
			p.setDaemon(True)
			p.start()

	print("\nPostProcess Threaded Started at %s" %(datetime.datetime.now().strftime("%H:%M:%S")))

	#now load some arbitrary jobs into the queue
	if sys.argv[1] == "additional":
		for release in datas:
			my_queue.put("%s                       %s                       %s                       %s                       %s                       %s                       %s" %(release[0], release[1], release[2], release[3], release[4], release[5], release[6]))
	elif sys.argv[1] == "nfo":
		for release in datas:
			my_queue.put("%s                       %s                       %s                       %s" %(release[0], release[1], release[2], release[3]))
	elif sys.argv[1] == "movie":
		for release in datas:
			my_queue.put("%s                       %s                       %s" %(release[0], release[1], release[2]))
	elif sys.argv[1] == "tv":
		for release in datas:
			my_queue.put("%s                       %s" %(release[0], release[1]))

	my_queue.join()

if __name__ == '__main__':
	main()

#create the connection to mysql
con = None
con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], passwd=conf['DB_PASSWORD'], db=conf['DB_NAME'], port=int(conf['DB_PORT']), unix_socket=conf['DB_SOCKET'])
cur = con.cursor()

cur.execute("Select ID from releases where nfostatus <= -6")
final = cur.fetchall()

for item in final:
	cur.execute("DELETE FROM releasenfo WHERE nfo IS NULL and releaseID = %d" %(item))
	final = cur.fetchall()

#close connection to mysql
cur.close()
con.close()

print("\nPostProcess Threaded Completed at %s" %(datetime.datetime.now().strftime("%H:%M:%S")))
print("Running time: %s" %(str(datetime.timedelta(seconds=time.time() - start_time))))
