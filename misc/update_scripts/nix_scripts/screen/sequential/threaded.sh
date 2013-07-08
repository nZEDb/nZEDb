#!/bin/bash

##	This is a simple sequential script the uses many of the threaded scripts
##	Just set the variables and uncomment what you would like to run.

export NZEDB_PATH="/var/www/nZEDb/misc/update_scripts"
export TEST_PATH="/var/www/nZEDb/misc/testing/Release_scripts"
export DEV_PATH="/var/www/nZEDb/misc/testing/Dev_testing"
export THREADED_PATH="/var/www/nZEDb/misc/update_scripts/threaded_scripts"
export NZEDB_SLEEP_TIME="60" # in seconds

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
#	$PYTHON -OO ${THREADED_PATH}/partrepair_threaded.py
#	$PYTHON -OO ${THREADED_PATH}/binaries_threaded.py
#	$PYTHON -OO ${THREADED_PATH}/backfill_threaded.py all
#	$PYTHON -OO ${THREADED_PATH}/backfill_safe_threaded.py
#	$PYTHON -OO ${THREADED_PATH}/grabnzbs_threaded.py
#	$PHP ${NZEDB_PATH}/update_releases.php 1 false
#	$PHP ${TEST_PATH}/fixReleaseNames.php 2 true all no
#	$PHP ${TEST_PATH}/fixReleaseNames.php 4 true all true
#	$PHP ${TEST_PATH}/fixReleaseNames.php 6 true all no
#	$PHP ${NZEDB_PATH}/nix_scripts/tmux/bin/postprocess_pre.php
#	$PHP ${NZEDB_PATH}/nzbx_ws_hashdecrypt.php
#	$PHP ${DEV_PATH}/test_misc_sorter.php
#	$PYTHON -OO ${THREADED_PATH}/postprocess_threaded.py additional
#	$PYTHON -OO ${THREADED_PATH}/postprocess_threaded.py nfo
#	$PYTHON -OO ${THREADED_PATH}/postprocess_threaded.py non_amazon
#	$PYTHON -OO ${THREADED_PATH}/postprocess_threaded.py amazon
	sleep $NZEDB_SLEEP_TIME
done
