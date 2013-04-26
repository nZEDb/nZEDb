#!/bin/sh

export NZEDB_PATH="/var/www/nZEDb/misc/update_scripts"
export THREAD_PATH="/var/www/nZEDb/misc/update_scripts/threaded_scripts"
export PHP="$(which php5)"
export PYTHON="$(which python)"
export SCREEN="$(which screen)"
export NZEDB_SLEEP_TIME="60"
	   LASTOPTIMIZE=`date +%s`
	   LASTOPTIMIZE1=`date +%s`
	   
	while :
	do
		cd ${THREAD_PATH}
		$PYTHON ${THREAD_PATH}/binaries_threaded.py
	
	CURRTIME=`date +%s`
	DIFF=$(($CURRTIME-$LASTOPTIMIZE))
	if [ "$DIFF" -gt 7200 ] || [ "$DIFF" -lt 1 ]
	then
		LASTOPTIMIZE=`date +%s`
		echo "Optimizing DB..."
		cd ${NZEDB_PATH}
		$PHP ${NZEDB_PATH}/optimise_db.php
	fi

	DIFF=$(($CURRTIME-$LASTOPTIMIZE1))
	if [ "$DIFF" -gt 43200 ] || [ "$DIFF" -lt 1 ]
	then
		LASTOPTIMIZE1=`date +%s`
		$PHP ${NZEDB_PATH}/update_tvschedule.php
		$PHP ${NZEDB_PATH}/update_theaters.php
	fi

	echo "waiting ${NZEDB_SLEEP_TIME} seconds..."
	sleep ${NZEDB_SLEEP_TIME}

done
