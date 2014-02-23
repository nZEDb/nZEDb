#!/usr/bin/env python
# -*- coding: utf-8 -*-

from __future__ import print_function
from __future__ import unicode_literals
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
			if conf['DB_PORT'] != '':
				con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], passwd=conf['DB_PASSWORD'], db=conf['DB_NAME'], port=int(conf['DB_PORT']), unix_socket=conf['DB_SOCKET'], charset="utf8")
			else:
				con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], passwd=conf['DB_PASSWORD'], db=conf['DB_NAME'], unix_socket=conf['DB_SOCKET'], charset="utf8")
    except ImportError:
        print(bcolors.ERROR + "\nPlease install cymysql for python 3, \ninformation can be found in INSTALL.txt\n" + bcolors.ENDC)
        sys.exit()
elif conf['DB_SYSTEM'] == "pgsql":
    try:
        import psycopg2 as mdb
        con = mdb.connect(host=conf['DB_HOST'], user=conf['DB_USER'], password=conf['DB_PASSWORD'], dbname=conf['DB_NAME'], port=int(conf['DB_PORT']))
    except ImportError:
        print(bcolors.ERROR + "\nPlease install psycopg for python 3, \ninformation can be found in INSTALL.txt\n" + bcolors.ENDC)
        sys.exit()
cur = con.cursor()

if len(sys.argv) == 1:
    print(bcolors.ERROR + "\nWrong set of arguments.\nThe first argument [additional, nfo, movie, clean] determines the postprocessing to do.\nThe optional second argument for [additional, nfo] [groupid, categoryid] allows to process only that group or category.\nThe optional second argument for [movies, tv] [clean] allows processing only properly renamed releases.\n\npython postprocess_threaded.py [additional, nfo] (optional [groupid, categoryid])\npython postprocess_threaded.py [movie, tv] (optional [clean])\n" + bcolors.ENDC)
    sys.exit()
if len(sys.argv) == 3 and sys.argv[2] == "clean":
    print(bcolors.HEADER + "\nPostProcess {} Clean Threaded Started at {}".format(sys.argv[1],datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)
else:
    print(bcolors.HEADER + "\nPostProcess {} Threaded Started at {}".format(sys.argv[1],datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)

if sys.argv[1] == "additional":
    print(bcolors.HEADER + "Downloaded: b = yEnc article, f= failed ;Processing: z = zip file, r = rar file" + bcolors.ENDC);
    print(bcolors.HEADER + "Added: s = sample image, j = jpeg image, A = audio sample, a = audio mediainfo, v = video sample" + bcolors.ENDC);
    print(bcolors.HEADER + "Added: m = video mediainfo, n = nfo, ^ = file details from inside the rar/zip" + bcolors.ENDC);
elif sys.argv[1] == "nfo":
    print(bcolors.HEADER + "* = hidden NFO, + = NFO, - = no NFO, f = download failed." + bcolors.ENDC)

# You can limit postprocessing for additional and nfo by groupid or categoryid
if len(sys.argv) == 3 and sys.argv[2].isdigit() and len(sys.argv[2]) < 4:
    groupID = 'AND groupid = '+sys.argv[2]
    print(bcolors.HEADER + "Using groupid "+sys.argv[2] + bcolors.ENDC)
elif len(sys.argv) == 3 and sys.argv[2].isdigit() and len(sys.argv[2]) == 4:
    if sys.argv[2] == '1000':
        groupID = 'AND categoryid BETWEEN 1000 AND 1999'
        print(bcolors.HEADER + "Using categoryids 1000-1999" + bcolors.ENDC)
    elif sys.argv[2] == '2000':
        groupID = 'AND categoryid BETWEEN 2000 AND 2999'
        print(bcolors.HEADER + "Using categoryids 2000-2999" + bcolors.ENDC)
    elif sys.argv[2] == '3000':
        groupID = 'AND categoryid BETWEEN 3000 AND 3999'
        print(bcolors.HEADER + "Using categoryids 3000-3999" + bcolors.ENDC)
    elif sys.argv[2] == '4000':
        groupID = 'AND categoryid BETWEEN 4000 AND 4999'
        print(bcolors.HEADER + "Using categoryids 4000-4999" + bcolors.ENDC)
    elif sys.argv[2] == '5000':
        groupID = 'AND categoryid BETWEEN 5000 AND 5999'
        print(bcolors.HEADER + "Using categoryids 5000-5999" + bcolors.ENDC)
    elif sys.argv[2] == '6000':
        groupID = 'AND categoryid BETWEEN 6000 AND 6999'
        print(bcolors.HEADER + "Using categoryids 6000-6999" + bcolors.ENDC)
    elif sys.argv[2] == '7000':
        groupID = 'AND categoryid BETWEEN 7000 AND 7999'
        print(bcolors.HEADER + "Using categoryids 7000-7999" + bcolors.ENDC)
    elif sys.argv[2] == '8000':
        groupID = 'AND categoryid BETWEEN 8000 AND 8999'
        print(bcolors.HEADER + "Using categoryids 8000-8999" + bcolors.ENDC)
    else:
        groupID = 'AND categoryid = '+sys.argv[2]
        print(bcolors.HEADER + "Using categoryid "+sys.argv[2] + bcolors.ENDC)
else:
    groupID = ''

#you can sort tv releases by searchname
if len(sys.argv) == 3 and (sys.argv[2] == "asc" or sys.argv[2] == "desc"):
    orderBY = 'ORDER BY searchname '+sys.argv[2]
    print(bcolors.HEADER + "Using ORDER BY searchname "+sys.argv[2] + bcolors.ENDC)
if len(sys.argv) == 4 and (sys.argv[3] == "asc" or sys.argv[3] == "desc"):
    orderBY = 'ORDER BY searchname '+sys.argv[3]
    print(bcolors.HEADER + "Using CLEAN - ORDER BY searchname "+sys.argv[3] + bcolors.ENDC)
else:
    orderBY = 'ORDER BY postdate DESC'

start_time = time.time()
pathname = os.path.abspath(os.path.dirname(sys.argv[0]))

if len(sys.argv) > 1 and sys.argv[1] == "additional":
    cur.execute("SELECT (SELECT value FROM site WHERE setting = 'postthreads') AS a, (SELECT value FROM site WHERE setting = 'maxaddprocessed') AS b, (SELECT value FROM site WHERE setting = 'maxnfoprocessed') AS c, (SELECT value FROM site WHERE setting = 'maximdbprocessed') AS d, (SELECT value FROM site WHERE setting = 'maxrageprocessed') AS e, (SELECT value FROM site WHERE setting = 'maxsizetopostprocess') AS f, (SELECT value FROM site WHERE setting = 'tmpunrarpath') AS g, (SELECT value FROM tmux WHERE setting = 'post') AS h, (SELECT value FROM tmux WHERE setting = 'post_non') AS i, (SELECT count(*) FROM releases WHERE haspreview = -1 and passwordstatus = -1 "+groupID+") as j, (SELECT count(*) FROM releases WHERE haspreview = -1 and passwordstatus = -2 "+groupID+") as k, (SELECT count(*) FROM releases WHERE haspreview = -1 and passwordstatus = -3 "+groupID+") as l, (SELECT count(*) FROM releases WHERE haspreview = -1 and passwordstatus = -4 "+groupID+") as m, (SELECT count(*) FROM releases WHERE haspreview = -1 and passwordstatus = -5 "+groupID+") as n, (SELECT count(*) FROM releases WHERE haspreview = -1 and passwordstatus = -6 "+groupID+") as o")
    dbgrab = cur.fetchall()
    ps1 = format(int(dbgrab[0][9]))
    ps2 = format(int(dbgrab[0][10]))
    ps3 = format(int(dbgrab[0][11]))
    ps4 = format(int(dbgrab[0][12]))
    ps5 = format(int(dbgrab[0][13]))
    ps6 = format(int(dbgrab[0][14]))
elif len(sys.argv) > 1 and sys.argv[1] == "nfo":
    cur.execute("SELECT (SELECT value FROM site WHERE setting = 'postthreads') AS a, (SELECT value FROM site WHERE setting = 'maxaddprocessed') AS b, (SELECT value FROM site WHERE setting = 'maxnfoprocessed') AS c, (SELECT value FROM site WHERE setting = 'maximdbprocessed') AS d, (SELECT value FROM site WHERE setting = 'maxrageprocessed') AS e, (SELECT value FROM site WHERE setting = 'maxsizetopostprocess') AS f, (SELECT value FROM site WHERE setting = 'tmpunrarpath') AS g, (SELECT value FROM tmux WHERE setting = 'post') AS h, (SELECT value FROM tmux WHERE setting = 'post_non') AS i, (SELECT count(*) FROM releases WHERE nfostatus = -1 "+groupID+") as j, (SELECT count(*) FROM releases WHERE nfostatus = -2 "+groupID+") as k, (SELECT count(*) FROM releases WHERE nfostatus = -3 "+groupID+") as l, (SELECT count(*) FROM releases WHERE nfostatus = -4 "+groupID+") as m, (SELECT count(*) FROM releases WHERE nfostatus = -5 "+groupID+") as n, (SELECT count(*) FROM releases WHERE nfostatus = -6 "+groupID+") as o")
    dbgrab = cur.fetchall()
    ps1 = format(int(dbgrab[0][9]))
    ps2 = format(int(dbgrab[0][10]))
    ps3 = format(int(dbgrab[0][11]))
    ps4 = format(int(dbgrab[0][12]))
    ps5 = format(int(dbgrab[0][13]))
    ps6 = format(int(dbgrab[0][14]))
elif len(sys.argv) > 1 and (sys.argv[1] == "movie" or sys.argv[1] == "tv"):
    cur.execute("SELECT(SELECT value FROM site WHERE setting = 'postthreadsnon') AS a, (SELECT value FROM site WHERE setting = 'maxaddprocessed') AS b, (SELECT value FROM site WHERE setting = 'maxnfoprocessed') AS c, (SELECT value FROM site WHERE setting = 'maximdbprocessed') AS d, (SELECT value FROM site WHERE setting = 'maxrageprocessed') AS e, (SELECT value FROM site WHERE setting = 'maxsizetopostprocess') AS f, (SELECT value FROM site WHERE setting = 'tmpunrarpath') AS g, (SELECT value FROM tmux WHERE setting = 'post') AS h, (SELECT value FROM tmux WHERE setting = 'post_non') AS i")
    dbgrab = cur.fetchall()
else:
    print(bcolors.ERROR + "\nAn argument is required, \npostprocess_threaded.py [additional, nfo, movie, tv]\n" + bcolors.ENDC)
    sys.exit()

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

if sys.argv[1] == "additional" or sys.argv[1] == "nfo":
    print(bcolors.HEADER + "Available to process: -6 = {}, -5 = {}, -4 = {}, -3 = {}, -2 = {}, -1 = {}".format(ps6, ps5, ps4, ps3, ps2, ps1) + bcolors.ENDC);

if maxsize == 0:
    maxsize = ''
else:
    maxsize = 'AND r.size < '+str(maxsizeck * 1073741824)

datas = []
maxtries = -1

process_additional = run_threads * ppperrun
process_nfo = run_threads * nfoperrun

if sys.argv[1] == "additional":
    run = "SELECT r.id, r.guid, r.name, c.disablepreview, r.size, r.groupid, r.nfostatus, r.categoryid from releases r LEFT JOIN category c ON c.id = r.categoryid WHERE nzbstatus = 1 "+maxsize+" AND r.passwordstatus = -1 AND r.haspreview = -1 AND c.disablepreview = 0 "+groupID+" ORDER BY postdate DESC LIMIT %s"
    cur.execute(run, process_additional)
    datas = cur.fetchall()
    maxtries = -1
    if len(datas) < process_additional:
        run = "SELECT r.id, r.guid, r.name, c.disablepreview, r.size, r.groupid, r.nfostatus, r.categoryid from releases r LEFT JOIN category c ON c.id = r.categoryid WHERE nzbstatus = 1 "+maxsize+" AND r.passwordstatus = -2 AND r.haspreview = -1 AND c.disablepreview = 0 "+groupID+" ORDER BY postdate DESC LIMIT %s"
        cur.execute(run, (process_additional - len(datas)))
        datas += cur.fetchall()
        maxtries = -2
        if len(datas) < process_additional:
            run = "SELECT r.id, r.guid, r.name, c.disablepreview, r.size, r.groupid, r.nfostatus, r.categoryid from releases r LEFT JOIN category c ON c.id = r.categoryid WHERE nzbstatus = 1 "+maxsize+" AND r.passwordstatus = -3 AND r.haspreview = -1 AND c.disablepreview = 0 "+groupID+" ORDER BY postdate DESC LIMIT %s"
            cur.execute(run, (process_additional - len(datas)))
            datas += cur.fetchall()
            maxtries = -3
            if len(datas) < process_additional:
                run = "SELECT r.id, r.guid, r.name, c.disablepreview, r.size, r.groupid, r.nfostatus, r.categoryid from releases r LEFT JOIN category c ON c.id = r.categoryid WHERE nzbstatus = 1 "+maxsize+" AND r.passwordstatus = -4 AND r.haspreview = -1 AND c.disablepreview = 0 "+groupID+" ORDER BY postdate DESC LIMIT %s"
                cur.execute(run, (process_additional - len(datas)))
                datas += cur.fetchall()
                maxtries = -4
                if len(datas) < process_additional:
                    run = "SELECT r.id, r.guid, r.name, c.disablepreview, r.size, r.groupid, r.nfostatus, r.categoryid from releases r LEFT JOIN category c ON c.id = r.categoryid WHERE nzbstatus = 1 "+maxsize+" AND r.passwordstatus = -5 AND r.haspreview = -1 AND c.disablepreview = 0 "+groupID+" ORDER BY postdate DESC LIMIT %s"
                    cur.execute(run, (process_additional - len(datas)))
                    datas += cur.fetchall()
                    maxtries = -5
                    if len(datas) < process_additional:
                        run = "SELECT r.id, r.guid, r.name, c.disablepreview, r.size, r.groupid, r.nfostatus, r.categoryid from releases r LEFT JOIN category c ON c.id = r.categoryid WHERE nzbstatus = 1 "+maxsize+" AND r.passwordstatus = -6 AND r.haspreview = -1 AND c.disablepreview = 0 "+groupID+" ORDER BY postdate DESC LIMIT %s"
                        cur.execute(run, (process_additional - len(datas)))
                        datas += cur.fetchall()
                        maxtries = -6

elif sys.argv[1] == "nfo":
    cur.execute("SELECT id, guid, groupid, name from releases WHERE nzbstatus = 1 AND nfostatus = -1 "+groupID+" ORDER BY postdate DESC LIMIT "+str(process_nfo))
    datas = cur.fetchall()
    maxtries = -1
    if len(datas) < process_nfo:
        cur.execute("SELECT id, guid, groupid, name from releases WHERE nzbstatus = 1 AND nfostatus = -2 "+groupID+" ORDER BY postdate DESC LIMIT "+str(process_nfo - len(datas)))
        datas += cur.fetchall()
        maxtries = -2
        if len(datas) < process_nfo:
            cur.execute("SELECT id, guid, groupid, name from releases WHERE nzbstatus = 1 AND nfostatus = -3 "+groupID+" ORDER BY postdate DESC LIMIT "+str(process_nfo - len(datas)))
            datas += cur.fetchall()
            maxtries = -3
            if len(datas) < process_nfo:
                cur.execute("SELECT id, guid, groupid, name from releases WHERE nzbstatus = 1 AND nfostatus = -4 "+groupID+" ORDER BY postdate DESC LIMIT "+str(process_nfo - len(datas)))
                datas += cur.fetchall()
                maxtries = -4
                if len(datas) < process_nfo:
                    cur.execute("SELECT id, guid, groupid, name from releases WHERE nzbstatus = 1 AND nfostatus = -5 "+groupID+" ORDER BY postdate DESC LIMIT "+str(process_nfo - len(datas)))
                    datas += cur.fetchall()
                    maxtries = -5
                    if len(datas) < process_nfo:
                        cur.execute("SELECT id, guid, groupid, name from releases WHERE nzbstatus = 1 AND nfostatus = -6 "+groupID+" ORDER BY postdate DESC LIMIT "+str(process_nfo - len(datas)))
                        datas += cur.fetchall()
                        maxtries = -6


elif sys.argv[1] == "movie" and len(sys.argv) == 3 and sys.argv[2] == "clean":
        run = "SELECT DISTINCT searchname AS name, id, categoryid from releases WHERE nzbstatus = 1 AND isrenamed = 1 AND searchname IS NOT NULL AND imdbid IS NULL AND categoryid IN (SELECT id FROM category WHERE parentid = 2000) ORDER BY postdate DESC LIMIT %s"
        cur.execute(run, (run_threads * movieperrun))
        datas = cur.fetchall()
elif sys.argv[1] == "movie":
        run = "SELECT searchname AS name, id, categoryid from releases WHERE nzbstatus = 1 AND searchname IS NOT NULL AND imdbid IS NULL AND categoryid IN (SELECT id FROM category WHERE parentid = 2000) ORDER BY postdate DESC LIMIT %s"
        cur.execute(run, (run_threads * movieperrun))
        datas = cur.fetchall()
elif sys.argv[1] == "tv" and len(sys.argv) == 3 and sys.argv[2] == "clean":
        run = "SELECT searchname, id from releases WHERE nzbstatus = 1 AND isrenamed = 1 AND searchname IS NOT NULL AND rageid = -1 AND categoryid IN (SELECT id FROM category WHERE parentid = 5000 ) "+orderBY+" LIMIT %s"
        cur.execute(run, (run_threads * tvrageperrun))
        datas = cur.fetchall()
elif sys.argv[1] == "tv":
        run = "SELECT searchname, id from releases WHERE nzbstatus = 1 AND searchname IS NOT NULL AND rageid = -1 AND categoryid IN (SELECT id FROM category WHERE parentid = 5000 ) "+orderBY+" LIMIT %s"
        cur.execute(run, (run_threads * tvrageperrun))
        datas = cur.fetchall()

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
                    subprocess.call(["php", pathname+"/../nix/tmux/bin/postprocess_new.php", ""+my_id])
                    time.sleep(.02)
                    self.my_queue.task_done()

def u(x):
    if sys.version_info[0] < 3:
        import codecs
        return codecs.unicode_escape_decode(x)[0]
    else:
        return x

def main(args):
    global time_of_last_run
    time_of_last_run = time.time()

    if sys.argv[1] == "additional":
        print(bcolors.HEADER + "We will be using a max of {} threads, a queue of {} {} releases. passwordstatus range {} to -1".format(run_threads, "{:,}".format(len(datas)), sys.argv[1], maxtries) + bcolors.ENDC)
    elif sys.argv[1] == "nfo":
        print(bcolors.HEADER + "We will be using a max of {} threads, a queue of {} {} releases. nfostatus range {} to -1".format(run_threads, "{:,}".format(len(datas)), sys.argv[1], maxtries) + bcolors.ENDC)
    else:
        print(bcolors.HEADER + "We will be using a max of {} threads, a queue of {} {} releases.".format(run_threads, "{:,}".format(len(datas)), sys.argv[1]) + bcolors.ENDC)
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
            time.sleep(.02)
            my_queue.put(u("%s           =+=            %s           =+=            %s           =+=            %s           =+=            %s           =+=            %s           =+=            %s           =+=            %s") % (release[0], release[1], release[2], release[3], release[4], release[5], release[6], release[7]))
    elif sys.argv[1] == "nfo":
        for release in datas:
            time.sleep(.02)
            my_queue.put(u("%s           =+=            %s           =+=            %s           =+=            %s") % (release[0], release[1], release[2], release[3]))
    elif sys.argv[1] == "movie":
        for release in datas:
            time.sleep(.02)
            my_queue.put(u("%s           =+=            %s           =+=            %s") % (release[0], release[1], release[2]))
    elif sys.argv[1] == "tv":
        for release in datas:
            time.sleep(.02)
            my_queue.put(u("%s           =+=            %s") % (release[0], release[1]))

    my_queue.join()

    if sys.argv[1] == "nfo":
        cur.execute("SELECT id from releases WHERE nfostatus <= -6")
        final = cur.fetchall()
        if len(datas) > 0:
            for item in final:
                run = "DELETE FROM releasenfo WHERE nfo IS NULL AND releaseid = %s"
                cur.execute(run, (item[0]))
                final = cur.fetchall()

    #close connection to mysql
    cur.close()
    con.close()

    print(bcolors.HEADER + "\nPostProcess {} Threaded Completed at {}".format(sys.argv[1],datetime.datetime.now().strftime("%H:%M:%S")) + bcolors.ENDC)
    print(bcolors.HEADER + "Running time: {}\n\n".format(str(datetime.timedelta(seconds=time.time() - start_time))) + bcolors.ENDC)

if __name__ == '__main__':
    main(sys.argv[1:])
