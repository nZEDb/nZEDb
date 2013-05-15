#!/bin/sh

export NZEDB_PATH="/var/www/nZEDb/misc/update_scripts"
export TEST_PATH="/var/www/nZEDb/misc/testing/Release_scripts"
export NZEDB_SLEEP_TIME="60" # in seconds
LASTOPTIMIZE=`date +%s`
LASTOPTIMIZE1=`date +%s`

while :

 do
CURRTIME=`date +%s`
cd ${NZEDB_PATH}
/usr/bin/php5 ${NZEDB_PATH}/update_binaries.php
/usr/bin/php5 ${NZEDB_PATH}/update_releases.php 1 true 

cd ${TEST_PATH}
DIFF=$(($CURRTIME-$LASTOPTIMIZE))
if [ "$DIFF" -gt 900 ] || [ "$DIFF" -lt 1 ]
then
	LASTOPTIMIZE=`date +%s`
	echo "Cleaning DB..."
	/usr/bin/php5 ${TEST_PATH}/fixReleaseNames.php 3 true other yes
	/usr/bin/php5 ${TEST_PATH}/fixReleaseNames.php 5 true other yes
	/usr/bin/php5 ${TEST_PATH}/removeCrapReleases.php true 2
fi

cd ${NZEDB_PATH}
DIFF=$(($CURRTIME-$LASTOPTIMIZE1))
if [ "$DIFF" -gt 43200 ] || [ "$DIFF" -lt 1 ]
then
	LASTOPTIMIZE1=`date +%s`
	echo "Optimizing DB..."
	/usr/bin/php5 ${NZEDB_PATH}/optimise_db.php
	/usr/bin/php5 ${NZEDB_PATH}/update_tvschedule.php
	/usr/bin/php5 ${NZEDB_PATH}/update_theaters.php
fi

echo "waiting ${NZEDB_SLEEP_TIME} seconds..."
sleep ${NZEDB_SLEEP_TIME}

done
