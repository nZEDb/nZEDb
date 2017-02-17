#!/bin/sh

if [ -e "nZEDbBase.php" ]
then
	export NZEDB_ROOT="$(pwd)"
else
	export NZEDB_ROOT="$(php ../../../../../nZEDbBase.php)"
fi

export NZEDB_PATH="${NZEDB_ROOT}/misc/update"
export THREADED_PATH="${NZEDB_ROOT}/misc/update/nix/multiprocessing"
export TEST_PATH="${NZEDB_ROOT}/misc/testing"
export NZEDB_SLEEP_TIME="60" # in seconds
LASTOPTIMIZE=`date +%s`
LASTOPTIMIZE1=`date +%s`
command -v php5 >/dev/null 2>&1 && export PHP=`command -v php5` || { export PHP=`command -v php`; }

while :

 do
CURRTIME=`date +%s`

#tmux kill-session -t NNTPProxy
#$PHP ${NZEDB_PATH}/nntpproxy.php

cd ${NZEDB_PATH}
$PHP ${NZEDB_PATH}/update_binaries.php


$PHP ${THREADED_PATH}/releases.php 	# Set thread count to 1 in site-admin for sequential processing

$PHP ${NZEDB_PATH}/postprocess.php all true

$PHP ${NZEDB_PATH}/decrypt_hashes.php full show
$PHP ${NZEDB_PATH}/match_prefiles.php 150 show 150

cd ${TEST_PATH}
DIFF=$(($CURRTIME-$LASTOPTIMIZE))
if [ "$DIFF" -gt 900 ] || [ "$DIFF" -lt 1 ]
then
	LASTOPTIMIZE=`date +%s`
	echo "Cleaning DB..."
	$PHP ${TEST_PATH}/Release/fixReleaseNames.php 1 true all yes
	$PHP ${TEST_PATH}/Release/fixReleaseNames.php 3 true other yes
	$PHP ${TEST_PATH}/Release/fixReleaseNames.php 5 true other yes
	$PHP ${TEST_PATH}/Release/removeCrapReleases.php true 2
	$PHP ${NZEDB_PATH}/decrypt_hashes.php full show
	$PHP ${NZEDB_PATH}/match_prefiles.php full show
fi

cd ${NZEDB_PATH}
DIFF=$(($CURRTIME-$LASTOPTIMIZE1))
if [ "$DIFF" -gt 43200 ] || [ "$DIFF" -lt 1 ]
then
	LASTOPTIMIZE1=`date +%s`
	echo "Optimizing DB..."
	$PHP ${NZEDB_PATH}/optimise_db.php space
	#$PHP ${NZEDB_PATH}/update_tvschedule.php
	#$PHP ${NZEDB_PATH}/update_theaters.php
	$PHP ${NZEDB_PATH}/decrypt_hashes.php full show
	$PHP ${NZEDB_PATH}/match_prefiles.php full show
fi

echo "waiting ${NZEDB_SLEEP_TIME} seconds..."
sleep ${NZEDB_SLEEP_TIME}

done
