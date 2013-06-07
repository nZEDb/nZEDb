#!/bin/sh

export NZEDB_PATH="/var/www/nZEDb/misc/update_scripts"
export TEST_PATH="/var/www/nZEDb/misc/testing/Release_scripts"
command -v php5 >/dev/null 2>&1 && export PHP=`command -v php5` || { export PHP=`command -v php`; }
export NZEDB_SLEEP_TIME="60"

while :
do

	cd ${NZEDB_PATH}
	$PHP $NZEDB_PATH/update_releases.php 1 false
	cd ${TEST_PATH}
	$PHP ${TEST_PATH}/removeCrapReleases.php true 1
	
	echo "waiting ${NZEDB_SLEEP_TIME} seconds..."
	sleep ${NZEDB_SLEEP_TIME}
	
done
