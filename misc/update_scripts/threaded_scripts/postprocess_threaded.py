#!/usr/bin/python
# -*- coding: utf-8 -*-

import sys, os, time
import threading
try:
    import queue
except ImportError:
    import Queue as queue
import cymysql as mdb
import subprocess
import string
import re
import shutil

pathname = os.path.abspath(os.path.dirname(sys.argv[0]))

def readConfig():
		Configfile = pathname+"/../../../www/config.php"
		file = open( Configfile, "r")

		# Match a config line
		m = re.compile('^define\(\'([A-Z_]+)\', \'?(.*?)\'?\);$', re.I)

		# The config object
		config = {}
		config['DB_PORT']=3306
		for line in file.readlines():
				match = m.search( line )
				if match:
						value = match.group(2)

						# filter boolean
						if "true" is value:
								value = True
						elif "false" is value:
								value = False

						# Add to the config
						#config[ match.group(1).lower() ] = value	   # Lower case example
						config[ match.group(1) ] = value
		return config

# Test
config = readConfig()

con = None
# The MYSQL connection.
con = mdb.connect(config['DB_HOST'], config['DB_USER'], config['DB_PASSWORD'], config['DB_NAME'], int(config['DB_PORT']))
cur = con.cursor()
if len(sys.argv) > 1 and (sys.argv[1] == "additional" or sys.argv[1] == "nfo"):
	cur.execute("select value from site where setting = 'postthreads'")
	run_threads = cur.fetchone();
elif len(sys.argv) > 1 and (sys.argv[1] == "movie" or sys.argv[1] == "tv"):
	cur.execute("select value from site where setting = 'postthreadsnon'")
	run_threads = cur.fetchone();

cur.execute("select value from site where setting = 'maxaddprocessed'")
ppperrun = cur.fetchone();
cur.execute("select value from site where setting = 'maxnfoprocessed'")
nfoperrun = cur.fetchone();
cur.execute("select value from site where setting = 'maximdbprocessed'")
movieperrun = cur.fetchone();
cur.execute("select value from site where setting = 'maxrageprocessed'")
tvrageperrun = cur.fetchone();
cur.execute("select value from site where setting = 'maxsizetopostprocess'")
maxsizeck = cur.fetchone();
cur.execute("select value from site where setting = 'tmpunrarpath'")
tmppath = cur.fetchone();

maxtries = -1
if int(maxsizeck[0]) == 0:
	maxsize = ''
else:
	maxsize = "r.size < %d and "%(int(maxsizeck[0])*1073741824)
datas = []
maxtries = -1
if len(sys.argv) > 1 and sys.argv[1] == "additional":
	while len(datas) <= int(run_threads[0])*int(ppperrun[0]) and maxtries >= -6:
		cur.execute("select r.ID, r.guid, r.name, c.disablepreview, r.size, r.groupID, r.nfostatus from releases r left join category c on c.ID = r.categoryID where %s r.passwordstatus between %d and -1 and (r.haspreview = -1 and c.disablepreview = 0) and nzbstatus = 1 order by r.postdate desc limit %d" %(maxsize, maxtries, int(run_threads[0])*int(ppperrun[0])))
		datas = cur.fetchall();
		maxtries = maxtries - 1
elif len(sys.argv) > 1 and sys.argv[1] == "nfo":
	while len(datas) <= int(run_threads[0])*int(nfoperrun[0]) and maxtries >= -6:
		cur.execute("SELECT r.ID, r.guid, r.groupID, r.name FROM releases r WHERE %s r.nfostatus between %d and -1 and r.nzbstatus = 1 order by r.postdate desc limit %d" %(maxsize, maxtries, int(run_threads[0])*int(nfoperrun[0])))
		datas = cur.fetchall();
		maxtries = maxtries - 1
elif len(sys.argv) > 1 and sys.argv[1] == "movie":
		cur.execute("SELECT searchname as name, ID, categoryID from releases where imdbID IS NULL and nzbstatus = 1 and categoryID in ( select ID from category where parentID = 2000 ) order by postdate desc limit %d" %(int(run_threads[0])*int(movieperrun[0])))
		datas = cur.fetchall();
elif len(sys.argv) > 1 and sys.argv[1] == "tv":
		cur.execute("SELECT searchname, ID from releases where rageID = -1 and nzbstatus = 1 and categoryID in ( select ID from category where parentID = 5000 ) order by postdate desc limit %d" %(int(run_threads[0])*int(tvrageperrun[0])))
		datas = cur.fetchall();
else:
	print("\nWrong argument provided\n  python3 postprocess_threaded.py additional\n  python3 postprocess_threaded.py nfo\n  python3 postprocess_threaded.py movie\n  python3 postprocess_threaded.py tv")
	sys.exit();

class WorkerThread(threading.Thread):
	def __init__(self, threadID):
		super(WorkerThread, self).__init__()
		self.threadID = threadID
		self.stoprequest = threading.Event()

	def run(self):
		while not self.stoprequest.isSet():
			try:
				dirname = self.threadID.get(True, 0.1)
				subprocess.call(["php", pathname+"/../nix_scripts/tmux/bin/postprocess_new.php", ""+dirname])
			except queue.Empty:
				continue

	def join(self, timeout=None):
		self.stoprequest.set()
		super(WorkerThread, self).join(timeout)

def main(args):
	# Create a single input and a single output queue for all threads.
	threadID = queue.Queue(100)

	# Create the "thread pool"
	pool = [WorkerThread(threadID=threadID) for i in range(int(run_threads[0]))]

	if sys.argv[1] == "additional":
		print("Fetch for: b = binary, s = sample, m = mediainfo, a = audio, j = jpeg")
		print("^ added file content, o added previous, z = doing zip, r = doing rar, n = found nfo - %s." %(time.strftime("%H:%M:%S")))
	elif sys.argv[1] == "nfo":
		print("* = hidden NFO, + = NFO, - = no NFO, f = download failed  - %s." %(time.strftime("%H:%M:%S")))

	# Start all threads
	for thread in pool:
		thread.start()

	# Give the workers some work to do
	work_count = 0
	if sys.argv[1] == "additional":
		for release in datas:
			work_count += 1
			threadID.put("%s                       %s                       %s                       %s                       %s                       %s                       %s" %(release[0], release[1], release[2], release[3], release[4], release[5], release[6]))
	elif sys.argv[1] == "nfo":
		for release in datas:
			work_count += 1
			threadID.put("%s                       %s                       %s                       %s" %(release[0], release[1], release[2], release[3]))
	elif sys.argv[1] == "movie":
		for release in datas:
			work_count += 1
			threadID.put("%s                       %s                       %s" %(release[0], release[1], release[2]))
	elif sys.argv[1] == "tv":
		for release in datas:
			work_count += 1
			threadID.put("%s                       %s" %(release[0], release[1]))

	while work_count > 0:
		# Blocking 'get' from a Queue.
		work_count -= 1

	# Ask threads to die and wait for them to do it
	for thread in pool:
		if queue.Empty:
			thread.join()

if __name__ == '__main__':
	import sys
	main(sys.argv[1:])


print("\nCompleted work on %s items" %(len(datas)))
