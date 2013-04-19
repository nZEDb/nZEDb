#!/bin/sh

export NZEDB_PATH="/var/www/nzedb/misc/update_scripts"
export PHP="$(which php5)"
export SCREEN="$(which screen)"
export NZEDB_SLEEP_TIME="60"
	   LASTOPTIMIZE=`date +%s`
	   LASTOPTIMIZE1=`date +%s`
	   
	while :
	do
		cd ${NZEDB_PATH}
		$PHP ${NZEDB_PATH}/update_binaries.php
	
	CURRTIME=`date +%s`
	DIFF=$(($CURRTIME-$LASTOPTIMIZE))
	if [ "$DIFF" -gt 7200 ] || [ "$DIFF" -lt 1 ]
	then
		LASTOPTIMIZE=`date +%s`
		echo "Optimizing DB..."
		$PHP ${NZEDB_PATH}/optimise_db.php
	fi

	DIFF=$(($CURRTIME-$LASTOPTIMIZE1))
	if [ "$DIFF" -gt 43200 ] || [ "$DIFF" -lt 1 ]
	then
		LASTOPTIMIZE1=`date +%s`
		cd ${NZEDB_PATH}
		$PHP ${NZEDB_PATH}/update_tvschedule.php
		$PHP ${NZEDB_PATH}/update_theaters.php
	fi

	echo "waiting ${NZEDB_SLEEP_TIME} seconds..."
	sleep ${NZEDB_SLEEP_TIME}

done
