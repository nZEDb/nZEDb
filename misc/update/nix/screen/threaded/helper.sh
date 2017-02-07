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
command -v php5 >/dev/null 2>&1 && export PHP=`command -v php5` || { export PHP=`command -v php`; }
export NZEDB_SLEEP_TIME="60"

while :
do

	cd ${NZEDB_PATH}
	$PHP $THREADED_PATHED/releases.php 
	cd ${TEST_PATH}
	$PHP ${TEST_PATH}/Release/removeCrapReleases.php true 1

	echo "waiting ${NZEDB_SLEEP_TIME} seconds..."
	sleep ${NZEDB_SLEEP_TIME}

done
