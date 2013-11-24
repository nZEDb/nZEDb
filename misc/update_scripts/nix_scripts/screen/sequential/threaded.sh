#!/usr/bin/env bash

#	This is a simple sequential script the uses many of the threaded scripts
#	Just set the variables and uncomment what you would like to run.

if [ -e "nZEDbBase.php" ]
then
	export NZEDB_ROOT="$(pwd)"
elif [ -e "../../../nZEDbBase.php" ]
then
	export NZEDB_ROOT="$(php ../../../nZEDbBase.php)"
elif [ -e "../../../../nZEDbBase.php" ]
then
	export NZEDB_ROOT="$(php ../../../../nZEDbBase.php)"
else
	export NZEDB_ROOT="$(php ../../../../../nZEDbBase.php)"
fi

export niceness=10
export START_PATH="${NZEDB_ROOT}"
export NZEDB_PATH="${NZEDB_ROOT}/misc/update_scripts"
export TEST_PATH="${NZEDB_ROOT}/misc/testing/Release_scripts"
export DEV_PATH="${NZEDB_ROOT}/misc/testing/Dev_testing"
export DB_PATH="${NZEDB_ROOT}/misc/testing/DB_scripts"
export THREADED_PATH="${NZEDB_ROOT}/misc/update_scripts/python_scripts"
export NZEDB_SLEEP_TIME="60" # in seconds

command -v php5 >/dev/null 2>&1 && export PHP=`command -v php5` || { export PHP=`command -v php`; }
command -v python3 >/dev/null 2>&1 && export PYTHON=`command -v python3` || { export PYTHON=`command -v python`; }
export PHP="nice -n$niceness $PHP"
export PYTHON="nice -n$niceness $PYTHON"

#delete stale tmpunrar folders
export count=`find $NZEDB_PATH/../../nzbfiles/tmpunrar -type d -print| wc -l`
if [ $count != 1 ]
then
	rm -r $NZEDB_PATH/../../nzbfiles/tmpunrar/*
fi
if [[ $1 != "true" ]]
then
	$PHP ${NZEDB_PATH}/nix_scripts/tmux/bin/resetdelaytime.php
fi

loop=1
while [ $loop -ge 1 ]
do
	#The process that I use is get binaries, create releases, rename, post process
	#I only use the threaded scripts with the exception of decrypt_hashes.php and I do not use removeCrapReleases.php
	#decrypt hashes and fixReleasenames md5 are very similar, decrypt hashes should be run first, because it is faster
	#but fixReleasenames also looks at release files for a match, which is a plus, so both should be run, after
	#jonnyboy

	date1=`date +%s`
	clear
	echo
	echo
	if [[ $# -eq 1 && $1 == "true" ]]
	then
		loop=0
	fi
#	Uncomment this if statement only if using nntpproxy
#	if [[ $loop -eq 1 ]]
#	then
#		tmux kill-session -t NNTPProxy
#		$PHP ${NZEDB_PATH}/nntpproxy.php
#		sleep 1
#	else
#		tmux respawnp -k -t $tmux_session:2.0 "python ${THREADED_PATH}/nntpproxy.py ${THREADED_PATH}/lib/nntpproxy.conf"
		##Uncomment the next line only if you are using alternate nntp settings also
#		tmux respawnp -k -t$tmux_session:2.1 "python ${THREADED_PATH}/nntpproxy.py ${THREADED_PATH}/lib/nntpproxy_a.conf"
#	fi
#	$PHP ${TEST_PATH}/removeCrapReleases.php true full size
#	$PHP ${TEST_PATH}/removeCrapReleases.php true full scr
#	$PHP ${TEST_PATH}/removeCrapReleases.php true full passwordurl
#	$PHP ${TEST_PATH}/removeCrapReleases.php true full passworded
#	$PHP ${TEST_PATH}/removeCrapReleases.php true full installbin
#	$PHP ${TEST_PATH}/removeCrapReleases.php true full executable
#	$PHP ${TEST_PATH}/removeCrapReleases.php true full short
#	$PHP ${NZEDB_PATH}/update_binaries.php alt.binaries.classic.tv.shows
#   $PYTHON -OOu ${THREADED_PATH}/binaries_safe_threaded.py
#	$PYTHON -OOu ${THREADED_PATH}/binaries_threaded.py
#	$PYTHON -OOu ${THREADED_PATH}/backfill_threaded.py all
#	$PYTHON -OOu ${THREADED_PATH}/backfill_safe_threaded.py
#	$PYTHON -OOu ${THREADED_PATH}/grabnzbs_threaded.py
#	$PHP ${NZEDB_PATH}/update_releases.php 1 false
#	$PHP ${NZEDB_PATH}/decrypt_hashes.php full
#	$PHP ${DEV_PATH}/renametopre.php 4
#	$PYTHON -OOu ${THREADED_PATH}/releases_threaded.py
#	$PHP ${TEST_PATH}/fixReleaseNames.php 2 true all yes
#	$PHP ${TEST_PATH}/fixReleaseNames.php 4 true all yes
#	$PHP ${TEST_PATH}/fixReleaseNames.php 6 true all no
#	$PHP ${NZEDB_PATH}/nix_scripts/tmux/bin/postprocess_pre.php
#	$PYTHON -OOu ${THREADED_PATH}/postprocess_threaded.py nfo
#	$PYTHON -OOu ${THREADED_PATH}/requestid_threaded.py
#	$PHP ${DB_PATH}/populate_nzb_guid.php limited
#	$PHP ${DB_PATH}/populate_nzb_guid.php true
#	$PYTHON -OOu ${THREADED_PATH}/postprocess_threaded.py additional
#	$PYTHON -OOu ${THREADED_PATH}/postprocess_threaded.py nfo
#	$PYTHON -OOu ${THREADED_PATH}/fixreleasenames_threaded.py md5
#	$PYTHON -OOu ${THREADED_PATH}/fixreleasenames_threaded.py nfo
#	$PYTHON -OOu ${THREADED_PATH}/fixreleasenames_threaded.py filename
#   $PYTHON -OOu ${THREADED_PATH}/fixreleasenames_threaded.py par2
#   $PYTHON -OOu ${THREADED_PATH}/fixreleasenames_threaded.py miscsorter  ##I do not know if misc sorter works or ever worked with nZEDb, ugo has not been around in many months
#	$PYTHON -OOu ${THREADED_PATH}/postprocess_threaded.py movie clean
#	$PYTHON -OOu ${THREADED_PATH}/postprocess_threaded.py tv clean
#	$PHP ${TEST_PATH}/fixReleaseNames.php 4 true all yes
#	$PYTHON -OOu ${THREADED_PATH}/postprocess_old_threaded.py amazon
#	$PHP ${NZEDB_PATH}/nix_scripts/tmux/bin/showsleep.php $NZEDB_SLEEP_TIME
	date2=`date +%s`
	diff=$(($date2-$date1))
	echo "Total Running Time: $(($diff / 60)) minutes and $(($diff % 60)) seconds."
	sleep 2
done
