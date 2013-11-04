#!/usr/bin/python
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
conf = info.readConfig()
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

if len(sys.argv) == 1:
	sys.exit("\nAn argument is required, \npostprocess_threaded.py [additional, nfo, movie, tv]\n")
if len(sys.argv) == 3 and sys.argv[2] == "clean":
	print("\nPostProcess {} Clean Threaded Started at {}".format(sys.argv[1],datetime.datetime.now().strftime("%H:%M:%S")))
else:
	print("\nPostProcess {} Threaded Started at {}".format(sys.argv[1],datetime.datetime.now().strftime("%H:%M:%S")))

if sys.argv[1] == "additional":
	print("Downloaded: b = yEnc article, f= failed ;Processing: z = zip file, r = rar file");
	print("Added: s = sample image, j = jpeg image, A = audio sample, a = audio mediainfo, v = video sample");
	print("Added: m = video mediainfo, n = nfo, ^ = file details from inside the rar/zip");
elif sys.argv[1] == "nfo":
	print("* = hidden NFO, + = NFO, - = no NFO, f = download failed.")

start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))

if len(sys.argv) > 1 and (sys.argv[1] == "additional" or sys.argv[1] == "nfo"):
	cur.execute("SELECT (SELECT value FROM site WHERE setting = 'postthreads') AS a, (SELECT value FROM site WHERE setting = 'maxaddprocessed') AS b, (SELECT value FROM site WHERE setting = 'maxnfoprocessed') AS c, (SELECT value FROM site WHERE setting = 'maximdbprocessed') AS d, (SELECT value FROM site WHERE setting = 'maxrageprocessed') AS e, (SELECT value FROM site WHERE setting = 'maxsizetopostprocess') AS f, (SELECT value FROM site WHERE setting = 'tmpunrarpath') AS g, (SELECT value FROM tmux WHERE setting = 'post') AS h, (SELECT value FROM tmux WHERE setting = 'post_non') AS i")
	dbgrab = cur.fetchall()
elif len(sys.argv) > 1 and (sys.argv[1] == "movie" or sys.argv[1] == "tv"):
	cur.execute("SELECT(SELECT value FROM site WHERE setting = 'postthreadsnon') AS a, (SELECT value FROM site WHERE setting = 'maxaddprocessed') AS b, (SELECT value FROM site WHERE setting = 'maxnfoprocessed') AS c, (SELECT value FROM site WHERE setting = 'maximdbprocessed') AS d, (SELECT value FROM site WHERE setting = 'maxrageprocessed') AS e, (SELECT value FROM site WHERE setting = 'maxsizetopostprocess') AS f, (SELECT value FROM site WHERE setting = 'tmpunrarpath') AS g, (SELECT value FROM tmux WHERE setting = 'post') AS h, (SELECT value FROM tmux WHERE setting = 'post_non') AS i")
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
postnon = dbgrab[0][8]
maxsize = (int(maxsizeck * 1073741824))

datas = []
maxtries = -1

if len(sys.argv) == 3 and sys.argv[2].isdigit():
	groupID = 'AND groupid = '+sys.argv[2]
else:
	groupID = ''

if sys.argv[1] == "additional":
	while len(datas) == 0 and maxtries >= -6:
		if maxsizeck == 0:
			run = "SELECT r.id, r.guid, r.name, c.disablepreview, r.size, r.groupid, r.nfostatus, r.categoryid FROM releases r LEFT JOIN category c ON c.id = r.categoryid WHERE r.passwordstatus BETWEEN %s AND -1 AND r.haspreview = -1 AND c.disablepreview = 0 AND nzbstatus = 1 "+groupID+" AND r.id IN ( SELECT id FROM releases ORDER BY postdate DESC ) ORDER BY postdate DESC LIMIT %s"
			cur.execute(run, (maxtries, run_threads * ppperrun))
		else:
			run = "SELECT r.id, r.guid, r.name, c.disablepreview, r.size, r.groupid, r.nfostatus, r.categoryid FROM releases r LEFT JOIN category c ON c.id = r.categoryid WHERE r.size < %s AND r.passwordstatus BETWEEN %s AND -1 AND r.haspreview = -1 AND c.disablepreview = 0 AND nzbstatus = 1 "+groupID+" AND r.id IN ( SELECT id FROM releases ORDER BY postdate DESC ) ORDER BY postdate DESC LIMIT %s"
			cur.execute(run, (maxsize, maxtries, run_threads * ppperrun))
		datas = cur.fetchall()
		maxtries = maxtries - 1
elif sys.argv[1] == "nfo":
	while len(datas) == 0 and maxtries >= -6:
		run = "SELECT id, guid, groupid, name FROM releases WHERE nfostatus BETWEEN %s AND -1 AND nzbstatus = 1 "+groupID+" AND id IN ( SELECT id FROM releases ORDER BY postdate DESC ) ORDER BY postdate DESC LIMIT %s"
		cur.execute(run, (maxtries, run_threads * nfoperrun))
		datas = cur.fetchall()
		maxtries = maxtries - 1
elif sys.argv[1] == "movie" and len(sys.argv) == 3 and sys.argv[2] == "clean":
		run = "SELECT searchname AS name, id, categoryid FROM releases WHERE relnamestatus NOT IN (0, 1) AND imdbid IS NULL AND nzbstatus = 1 AND categoryid IN ( SELECT id FROM category WHERE parentid = 2000 ) AND id IN ( SELECT id FROM releases ORDER BY postdate DESC ) ORDER BY postdate DESC LIMIT %s"
		cur.execute(run, (run_threads * movieperrun))
		datas = cur.fetchall()
elif sys.argv[1] == "movie":
		run = "SELECT searchname AS name, id, categoryid FROM releases WHERE imdbid IS NULL AND nzbstatus = 1 AND categoryid IN ( SELECT id FROM category WHERE parentid = 2000 ) AND id IN ( SELECT id FROM releases ORDER BY postdate DESC ) ORDER BY postdate DESC LIMIT %s"
		cur.execute(run, (run_threads * movieperrun))
		datas = cur.fetchall()
elif sys.argv[1] == "tv" and len(sys.argv) == 3 and sys.argv[2] == "clean":
		run = "SELECT searchname, id FROM releases WHERE relnamestatus NOT IN (0, 1) AND rageid = -1 AND nzbstatus = 1 AND categoryid IN ( SELECT id FROM category WHERE parentid = 5000 ) AND id IN ( SELECT id FROM releases ORDER BY postdate DESC ) ORDER BY postdate DESC LIMIT %s"
		cur.execute(run, (run_threads * tvrageperrun))
		datas = cur.fetchall()
elif sys.argv[1] == "tv":
		run = "SELECT searchname, id FROM releases WHERE rageid = -1 AND nzbstatus = 1 AND categoryid IN ( SELECT id FROM category WHERE parentid = 5000 ) AND id IN ( SELECT id FROM releases ORDER BY postdate DESC ) ORDER BY postdate DESC LIMIT %s"
		cur.execute(run, (run_threads * tvrageperrun))
		datas = cur.fetchall()

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
					#print(os.getloadavg())
					time_of_last_run = time.time()
					subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/postprocess_new.php", ""+my_id])
					#subprocess.call(['timeout', '300', 'php', pathname+'/../nix_scripts/tmux/bin/postprocess_new.php', my_id])
					time.sleep(.05)
					self.my_queue.task_done()

def main(args):
	global time_of_last_run
	time_of_last_run = time.time()

	if sys.argv[1] == "additional" or sys.argv[1] == "nfo":
		print("We will be using a max of {} threads, a queue of {} {} releases. Query range {} to -1".format(run_threads, "{:,}".format(len(datas)), sys.argv[1], maxtries+1))
	else:
		print("We will be using a max of {} threads, a queue of {} {} releases.".format(run_threads, "{:,}".format(len(datas)), sys.argv[1]))
	time.sleep(2)

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
	if sys.argv[1] == "additional":
		for release in datas:
			time.sleep(.1)
			my_queue.put("%s           =+=            %s           =+=            %s           =+=            %s           =+=            %s           =+=            %s           =+=            %s           =+=            %s" % (release[0], release[1], release[2], release[3], release[4], release[5], release[6], release[7]))
	elif sys.argv[1] == "nfo":
		for release in datas:
			time.sleep(.1)
			my_queue.put("%s           =+=            %s           =+=            %s           =+=            %s" % (release[0], release[1], release[2], release[3]))
	elif sys.argv[1] == "movie":
		for release in datas:
			time.sleep(.1)
			my_queue.put("%s           =+=            %s           =+=            %s" % (release[0], release[1], release[2]))
	elif sys.argv[1] == "tv":
		for release in datas:
			time.sleep(.1)
			my_queue.put("%s           =+=            %s" % (release[0], release[1]))

	my_queue.join()

	if sys.argv[1] == "nfo":
		cur.execute("SELECT id FROM releases WHERE nfostatus <= -6")
		final = cur.fetchall()
		if len(datas) > 0:
			for item in final:
				run = "DELETE FROM releasenfo WHERE nfo IS NULL AND releaseid = %s"
				cur.execute(run, (item[0]))
				final = cur.fetchall()

	#close connection to mysql
	cur.close()
	con.close()

	print("\nPostProcess {} Threaded Completed at {}".format(sys.argv[1],datetime.datetime.now().strftime("%H:%M:%S")))
	print("Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))))

if __name__ == '__main__':
	main(sys.argv[1:])
