#!/usr/bin/python
# -*- coding: utf-8 -*-

import string
import os, sys, re

class bcolors:
	HEADER = '\033[38;5;011m'
	PRIMARY = '\033[38;5;010m'
	ERROR = '\033[38;5;009m'
	ENDC = '\033[0m'

	def disable(self):
		self.HEADER = ''
		self.PRIMARY = ''
		self.PRIMARY = ''

pathname = os.path.abspath(os.path.dirname(sys.argv[0]))
def readConfig():
	Configfile = pathname+"/../../../www/config.php"
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
