#!/usr/bin/env python
# Author: Nic Wolfe <nic@wolfeden.ca>
# URL: http://www.newznab.com/
#
# This file is part of Newznab
#
# Newznab is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# Newznab is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with Newznab.  If not, see <http://www.gnu.org/licenses/>.

NEWZNAB_PATH = "/usr/local/www/newznab/misc/update_scripts"
NEWZNAB_RUN_TIME = 600 # in seconds

update_scripts = ["update_binaries.php", "update_releases.php"]

import datetime
import os
import subprocess
import time

last_execution = datetime.datetime.today()
last_optimise = None

# just do this forever
while True:

	# run all our scripts
	for cur_script in update_scripts:
		cmd = ["php", cur_script]
		subprocess.call(cmd, cwd=NEWZNAB_PATH)

	# if it's time to optimise then do it
	if datetime.datetime.today().hour in (3,15):
		if not last_optimise or datetime.datetime.today() - last_optimise > datetime.timedelta(hours=2):
			print 'Optimizing database...'
			subprocess.call(["php", "optimise_db.php"], cwd=NEWZNAB_PATH)
			last_optimise = datetime.datetime.today()
		
	cur_finish_time = datetime.datetime.today()
	run_duration = cur_finish_time - last_execution
		
	# if we need to sleep then do it, but only sleep the remainder
	if run_duration.seconds < NEWZNAB_RUN_TIME:
		sleep_duration = NEWZNAB_RUN_TIME - run_duration.seconds
		print 'Sleeping for', sleep_duration, 'seconds'
		time.sleep(sleep_duration)
	else:
		print 'Last run took too long, starting immediately'
	
	last_execution = datetime.datetime.today()
