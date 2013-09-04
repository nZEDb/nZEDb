#!/bin/bash

##	This is a simple sequential script the uses many of the threaded scripts
##	Just set the variables and uncomment what you would like to run.

export NZEDB_PATH="/var/www/nZEDb/misc/update_scripts"
export TEST_PATH="/var/www/nZEDb/misc/testing/Release_scripts"
export DEV_PATH="/var/www/nZEDb/misc/testing/Dev_testing"
export THREADED_PATH="/var/www/nZEDb/misc/update_scripts/threaded_scripts"
export NZEDB_SLEEP_TIME="5" # in seconds

command -v php5 >/dev/null 2>&1 && export PHP=`command -v php5` || { export PHP=`command -v php`; }
command -v python3 >/dev/null 2>&1 && export PYTHON=`command -v python3` || { export PYTHON=`command -v python`; }

#delete stale tmpunrar folders
export count=`find $NZEDB_PATH/../../nzbfiles/tmpunrar -type d -print| wc -l`
if [ $count != 1 ]
then
	rm -r $NZEDB_PATH/../../nzbfiles/tmpunrar/*
fi

while :
do

	export CMD="$PYTHON -OO ${THREADED_PATH}/partrepair_threaded.py"
	echo "Running $CMD"
	$CMD
	sleep $NZEDB_SLEEP_TIME

	export CMD="$PYTHON -OO ${THREADED_PATH}/binaries_threaded.py"
	echo "Running $CMD"
	$CMD
	sleep $NZEDB_SLEEP_TIME

	export CMD="$PYTHON -OO ${THREADED_PATH}/backfill_threaded.py all"
	echo "Running $CMD"
	$CMD
	sleep $NZEDB_SLEEP_TIME

	export CMD="$PYTHON -OO ${THREADED_PATH}/backfill_safe_threaded.py"
	echo "Running $CMD"
	$CMD
	sleep $NZEDB_SLEEP_TIME

	export CMD="$PYTHON -OO ${THREADED_PATH}/grabnzbs_threaded.py"
	echo "Running $CMD"
	$CMD
	sleep $NZEDB_SLEEP_TIME

	export CMD="$PHP ${NZEDB_PATH}/update_releases.php 1 true"
	echo "Running $CMD"
	$CMD
	sleep $NZEDB_SLEEP_TIME

	export CMD="$PHP ${TEST_PATH}/fixReleaseNames.php 2 true all no"
	echo "Running $CMD"
	$CMD
	sleep $NZEDB_SLEEP_TIME

	export CMD="$PHP ${TEST_PATH}/fixReleaseNames.php 4 true all yes"
	echo "Running $CMD"
	$CMD
	sleep $NZEDB_SLEEP_TIME

	export CMD="$PHP ${TEST_PATH}/fixReleaseNames.php 6 true all no"
	echo "Running $CMD"
	$CMD
	sleep $NZEDB_SLEEP_TIME

	export CMD="$PHP ${NZEDB_PATH}/nix_scripts/tmux/bin/postprocess_pre.php"
	echo "Running $CMD"
	$CMD
	sleep $NZEDB_SLEEP_TIME

	export CMD="$PHP ${NZEDB_PATH}/decrypt_hashes.php"
	echo "Running $CMD"
	$CMD
	sleep $NZEDB_SLEEP_TIME

	export CMD="$PHP ${DEV_PATH}/test_misc_sorter.php"
	echo "Running $CMD"
	$CMD
	sleep $NZEDB_SLEEP_TIME

	export CMD="$PYTHON -OO ${THREADED_PATH}/postprocess_threaded.py additional"
	echo "Running $CMD"
	$CMD
	sleep $NZEDB_SLEEP_TIME

	export CMD="$PYTHON -OO ${THREADED_PATH}/postprocess_threaded.py nfo"
	echo "Running $CMD"
	$CMD
	sleep $NZEDB_SLEEP_TIME

	export CMD="$PYTHON -OO ${THREADED_PATH}/postprocess_threaded.py movie"
	echo "Running $CMD"
	$CMD
	sleep $NZEDB_SLEEP_TIME

	export CMD="$PYTHON -OO ${THREADED_PATH}/postprocess_threaded.py tv"
	echo "Running $CMD"
	$CMD
	sleep $NZEDB_SLEEP_TIME

	export CMD="$PYTHON -OO ${THREADED_PATH}/postprocess_old_threaded.py amazon"
	echo "Running $CMD"
	$CMD
	sleep $NZEDB_SLEEP_TIME
done
