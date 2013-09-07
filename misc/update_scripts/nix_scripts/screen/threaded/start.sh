#!/bin/sh

export NZEDB_PATH="/var/www/nZEDb/misc/update_scripts"
export HELP_PATH="/var/www/nZEDb/misc/update_scripts/nix_scripts/screen/threaded"
export THREAD_PATH="/var/www/nZEDb/misc/update_scripts/threaded_scripts"
export TEST_PATH="/var/www/nZEDb/misc/testing/Release_scripts"
command -v php5 >/dev/null 2>&1 && export PHP=`command -v php5` || { export PHP=`command -v php`; }
export PYTHON="$(which python)"
export SCREEN="$(which screen)"
export NZEDB_SLEEP_TIME="60"
	   LASTOPTIMIZE=`date +%s`
	   LASTOPTIMIZE1=`date +%s`
	   LASTOPTIMIZE2=`date +%s`

#delete stale tmpunrar folders
export count=`find $NZEDB_PATH/../../nzbfiles/tmpunrar -type d -print| wc -l`
if [ $count != 1 ]
then
	rm -r $NZEDB_PATH/../../nzbfiles/tmpunrar/*
fi

while :
do
	sleep 1
	CURRTIME=`date +%s`
	cd ${NZEDB_PATH}
	if ! $SCREEN -list | grep -q "POSTP"; then
		cd $NZEDB_PATH && $SCREEN -dmS POSTP $SCREEN $PHP $NZEDB_PATH/postprocess.php allinf true
	fi

	cd ${THREAD_PATH}
	$PYTHON -OO ${THREAD_PATH}/binaries_threaded.py

	cd ${HELP_PATH}
	if ! $SCREEN -list | grep -q "RELEASES"; then
		cd $HELP_PATH && $SCREEN -dmS RELEASES $SCREEN sh $HELP_PATH/helper.sh
	fi

	cd ${TEST_PATH}
	DIFF=$(($CURRTIME-$LASTOPTIMIZE))
	if [ "$DIFF" -gt 900 ] || [ "$DIFF" -lt 1 ]
	then
		LASTOPTIMIZE=`date +%s`
		echo "Cleaning DB..."
		$PHP ${TEST_PATH}/fixReleaseNames.php 1 true all yes
		$PHP ${TEST_PATH}/fixReleaseNames.php 3 true other yes
		$PHP ${TEST_PATH}/fixReleaseNames.php 5 true other yes
	fi

	cd ${NZEDB_PATH}
	DIFF=$(($CURRTIME-$LASTOPTIMIZE1))
	if [ "$DIFF" -gt 7200 ] || [ "$DIFF" -lt 1 ]
	then
		LASTOPTIMIZE1=`date +%s`
		echo "Optimizing DB..."
		$PHP ${NZEDB_PATH}/optimise_db.php
	fi

	DIFF=$(($CURRTIME-$LASTOPTIMIZE2))
	if [ "$DIFF" -gt 43200 ] || [ "$DIFF" -lt 1 ]
	then
		LASTOPTIMIZE2=`date +%s`
		echo "Updating schedules..."
		$PHP ${NZEDB_PATH}/update_tvschedule.php
		$PHP ${NZEDB_PATH}/update_theaters.php
	fi

	echo "waiting ${NZEDB_SLEEP_TIME} seconds..."
	sleep ${NZEDB_SLEEP_TIME}

done
