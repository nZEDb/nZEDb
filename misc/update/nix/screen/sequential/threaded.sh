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
export NZEDB_PATH="${NZEDB_ROOT}/misc/update"
export TEST_PATH="${NZEDB_ROOT}/misc/testing/Release"
export DEV_PATH="${NZEDB_ROOT}/misc/testing/Dev"
export DB_PATH="${NZEDB_ROOT}/misc/testing/DB"
export THREADED_PATH="${NZEDB_ROOT}/misc/update/python"
export MULTI_PATH="${NZEDB_ROOT}/misc/update/nix/multiprocessing"
export NZEDB_SLEEP_TIME="60" # in seconds

command -v php5 >/dev/null 2>&1 && export PHP=`command -v php5` || { export PHP=`command -v php`; }
command -v python3 >/dev/null 2>&1 && export PYTHON=`command -v python3` || { export PYTHON=`command -v python`; }
export PHP="nice -n$niceness $PHP"
export PYTHON="nice -n$niceness $PYTHON"

if [[ $1 != "true" ]]
then
	$PHP ${NZEDB_PATH}/nix/tmux/bin/resetdelaytime.php
fi

loop=1
while [ $loop -ge 1 ]
do
	#The process that I use is get binaries, create releases, rename, post process only properly renamed releases
	#I only use the threaded scripts with the exception of decrypt_hashes.php and fixReleaseNames.php
	#I do not use removeCrapReleases.php
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
#		tmux respawnp -k -t $tmux_session:3.0 "python ${THREADED_PATH}/nntpproxy.py ${THREADED_PATH}/lib/nntpproxy.conf"
		##if you are using alternate nntp settings then:
#		tmux respawnp -k -t$tmux_session:3.1 "python ${THREADED_PATH}/nntpproxy.py ${THREADED_PATH}/lib/nntpproxy_a.conf"
#	fi

### If you want to try the new multi-processing php scripts (preferred over python threaded scripts)
### uncomment those below that you want to use
#
## Update all by group
# $PHP ${MULTI_PATH}/								#update groups (update_binaries/backFill/update_releases/postprocess)
#													#one group per thread.
## Binaries options
#	$PHP ${MULTI_PATH}/binaries.php 500000			#run binaries one active group per thread upto a max of 500k headers
#	$PHP ${MULTI_PATH}/binaries.php 0				#run binaries one active group per thread pulling all new headers
#	$PHP ${MULTI_PATH}/safe.php binaries			#run safe binaries - pull all new headers split across all threads
#													#This will queue all new headers for active groups for download but optimize
#													#your thread usage as the number of messages to download for each group will
#													#be split across the threads.
#
## Backfill options
#	$PHP ${MULTI_PATH}/backfill.php 500000			#run backfill one active backfill group per thread upto a max of 500k headers
#	$PHP ${MULTI_PATH}/backfill.php 				#run backfill one active backfill group per thread upto groups backfill target date
#	$PHP ${MULTI_PATH}/safe.php backfill			#run safe backfill - split across all threads
#													#This will queue all headers for active backfill groups for download but
#													#optimize your thread usage as the number of messages to download for each
#													#group will be split across the threads (uses Tmux settings for safe backfill).
#
## NZB Import
#	$PHP ${MULTI_PATH}/import.php					#NZB import - see /misc/update/nix/multiprocessing/import.php for required arguments.
#
## Release Processing
#	$PHP ${MULTI_PATH}/releases.php					#Creates releases from complete collections one group per thread.
#
## Post-Processing options
#	$PHP ${MULTI_PATH}/requestid.php				#run local lookup for requestID matching.
#	$PHP ${MULTI_PATH}/postprocess.php add			#run postprocess additional - check for passworded releases / jpeg / previews etc.
#	$PHP ${MULTI_PATH}/postprocess.php nfo			#run postprocess nfo - process nfo files
#
#	$PHP ${MULTI_PATH}/postprocess.php mov			#run postprocess movies - lookup IMDB info / covers etc
#	$PHP ${MULTI_PATH}/postprocess.php mov true		#run postprocess movies - lookup IMDB info / covers etc but ONLY for renamed releases
#
#	$PHP ${MULTI_PATH}/postprocess.php tv			#run postprocess tv - lookup TVRage/Trakt info / covers etc.
#	$PHP ${MULTI_PATH}/postprocess.php tv true		#run postprocess tv - lookup TVRage/Trakt info / covers etc but ONLY for renamed releases.
#
#	$PHP ${MULTI_PATH}/postprocess.php ama			#Do amazon processing, this does not use multi-processing, because of amazon API restrictions.
#	$PHP ${MULTI_PATH}/postprocess.php sha			#Do sharing processing, this does not using multi-processing.
#
## Fix Release names options
#
#	$PHP ${MULTI_PATH}/fixrelnames.php nfo			#Attempt to fix release name using the nfo.
#	$PHP ${MULTI_PATH}/fixrelnames.php filename		#Attempt to fix release name using the filenames.
#	$PHP ${MULTI_PATH}/fixrelnames.php predbft		#Attempt to fix release name using Predb full text matching.
#	$PHP ${MULTI_PATH}/fixrelnames.php miscsorter	#Attempt to fix release name using magic.
#	$PHP ${MULTI_PATH}/fixrelnames.php md5			#Attempt to fix release name using the MD5.
#	$PHP ${MULTI_PATH}/fixrelnames.php par2			#Attempt to fix release name using the PAR2.
#
### End of Multi-processing scripts

#	$PHP ${TEST_PATH}/removeCrapReleases.php true full size
#	$PHP ${TEST_PATH}/removeCrapReleases.php true full scr
#	$PHP ${TEST_PATH}/removeCrapReleases.php true full passwordurl
#	$PHP ${TEST_PATH}/removeCrapReleases.php true full passworded
#	$PHP ${TEST_PATH}/removeCrapReleases.php true full installbin
#	$PHP ${TEST_PATH}/removeCrapReleases.php true full executable
#	$PHP ${TEST_PATH}/removeCrapReleases.php true full short
#	$PHP ${NZEDB_PATH}/update_binaries.php alt.binaries.classic.tv.shows
#	$PYTHON -OOu ${THREADED_PATH}/binaries_safe_threaded.py
#	$PYTHON -OOu ${THREADED_PATH}/binaries_threaded.py
#	$PYTHON -OOu ${THREADED_PATH}/backfill_threaded.py all
#	$PYTHON -OOu ${THREADED_PATH}/backfill_safe_threaded.py
	$PYTHON -OOu ${THREADED_PATH}/releases_threaded.py
	$PHP ${NZEDB_PATH}/update_releases.php 1 false
	$PHP ${NZEDB_PATH}/decrypt_hashes.php full
#	$PHP ${DEV_PATH}/renametopre.php 4
	$PHP ${TEST_PATH}/fixReleaseNames.php 6 true all yes					#This is faster than threaded and uses less load
	$PHP ${TEST_PATH}/fixReleaseNames.php 4 true all yes					#Threaded is faster but uses more load
	$PHP ${TEST_PATH}/fixReleaseNames.php 2 true all yes					#decrypt_hashes.php is faster, but this uses filenames also and should be run after
	$PHP ${TEST_PATH}/fixReleaseNames.php 8 true all yes					#This should only be run after all pp additional has been completed
#	$PHP ${NZEDB_PATH}/nix/tmux/bin/postprocess_pre.php						#This is better run in a dedicated screen instance, 24x7
#	$PYTHON -OOu ${THREADED_PATH}/postprocess_threaded.py nfo
	$PHP ${NZEDB_PATH}/requestid.php full									#This runs a local lookup only and is much faster, should be run before threaded
#   $PYTHON -OOu ${THREADED_PATH}/requestid_threaded.py						#This is much slower for local lookup, but is faster for remote lookup
#	$PHP ${DB_PATH}/populate_nzb_guid.php limited
#	$PHP ${DB_PATH}/populate_nzb_guid.php true
	$PYTHON -OOu ${THREADED_PATH}/postprocess_threaded.py additional		#you can run per group_id, categoryid or parentid
	$PYTHON -OOu ${THREADED_PATH}/postprocess_threaded.py nfo
	$PYTHON -OOu ${THREADED_PATH}/fixreleasenames_threaded.py md5			#fixReleaseNames.php should be used instead
	$PYTHON -OOu ${THREADED_PATH}/fixreleasenames_threaded.py nfo
	$PYTHON -OOu ${THREADED_PATH}/fixreleasenames_threaded.py filename		#fixReleaseNames.php should be used instead
	$PYTHON -OOu ${THREADED_PATH}/fixreleasenames_threaded.py par2			#fixReleaseNames.php should be used instead
	$PYTHON -OOu ${THREADED_PATH}/fixreleasenames_threaded.py miscsorter	#This works, but I do not know how well
	$PYTHON -OOu ${THREADED_PATH}/postprocess_threaded.py movie clean
	$PYTHON -OOu ${THREADED_PATH}/postprocess_threaded.py tv clean
	$PYTHON -OOu ${THREADED_PATH}/postprocess_old_threaded.py amazon
	$PHP ${NZEDB_PATH}/nix/tmux/bin/showsleep.php $NZEDB_SLEEP_TIME
	date2=`date +%s`
	diff=$(($date2-$date1))
	echo "Total Running Time: $(($diff / 60)) minutes and $(($diff % 60)) seconds."
	sleep 2
done
