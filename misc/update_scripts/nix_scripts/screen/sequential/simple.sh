#!/bin/sh

export NZEDB_PATH="/var/www/nzedb/misc/update_scripts"
export NZEDB_SLEEP_TIME="60" # in seconds
LASTOPTIMIZE=`date +%s`

while :

 do
CURRTIME=`date +%s`
cd ${NZEDB_PATH}
/usr/bin/php5 ${NZEDB_PATH}/update_binaries.php
/usr/bin/php5 ${NZEDB_PATH}/update_releases.php 1 true 

DIFF=$(($CURRTIME-$LASTOPTIMIZE))
if [ "$DIFF" -gt 43200 ] || [ "$DIFF" -lt 1 ]
then
	LASTOPTIMIZE=`date +%s`
	echo "Optimizing DB..."
	/usr/bin/php5 ${NZEDB_PATH}/optimise_db.php
	/usr/bin/php5 ${NZEDB_PATH}/update_tvschedule.php
	/usr/bin/php5 ${NZEDB_PATH}/update_theaters.php
fi

echo "waiting ${NZEDB_SLEEP_TIME} seconds..."
sleep ${NZEDB_SLEEP_TIME}

done
