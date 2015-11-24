#!/usr/bin/python
# -*- coding: utf-8 -*-

import string
import os, sys, re


class bcolors:
    HEADER = '\033[38;5;011m'
    PRIMARY = '\033[38;5;010m'
    ERROR = '\033[31mError: '
    ENDC = '\033[0m'
    ALTERNATE = '\033[38;5;199m'
    def disable(self):
        self.HEADER = ''
        self.PRIMARY = ''

pathname = os.path.abspath(os.path.dirname(sys.argv[0]))


def connect():
    conf = readConfig()
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
            print(bcolors.HEADER + "\nPlease install psycopg for python 3, \ninformation can be found in INSTALL.txt\n" + bcolors.ENDC)
            sys.exit()
    cur = con.cursor()
    return cur, con


def disconnect(cur, con):
    con.close()
    con = None
    cur.close()
    cur = None


def readConfig():
    Configfile = pathname+"/../../../nzedb/config/config.php"
    file = open( Configfile, "r")

    # Match a config line
    m = re.compile('^define\(\'([A-Z_]+)\', \'?(.*?)\'?\);$', re.I)

    # The config object
    config = {}
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
            config[ match.group(1) ] = value
    return config

# Test
config = readConfig()
