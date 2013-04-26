#!/bin/sh

export NZEDB_PATH="/var/www/nZEDb/misc/update_scripts"
export PHP="$(which php5)"
export NZEDB_SLEEP_TIME="60"

while :
do

	cd ${NZEDB_PATH}
	$PHP $NZEDB_PATH/update_releases.php 1 false
	
	echo "waiting ${NZEDB_SLEEP_TIME} seconds..."
	sleep ${NZEDB_SLEEP_TIME}
	
done
