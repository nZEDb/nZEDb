#!/bin/sh
# call this script from within screen to get binaries, processes releases and 
# every half day get tv/theatre info and optimise the database

set -e

export NEWZNAB_PATH="/var/www/newznab/misc/update_scripts"
export NEWZNAB_SLEEP_TIME="60" # in seconds
LASTOPTIMIZE=`date +%s`

while :

 do
CURRTIME=`date +%s`
cd ${NEWZNAB_PATH}
/usr/bin/php5 ${NEWZNAB_PATH}/update_binaries.php
/usr/bin/php5 ${NEWZNAB_PATH}/update_releases.php 3

DIFF=$(($CURRTIME-$LASTOPTIMIZE))
if [ "$DIFF" -gt 43200 ] || [ "$DIFF" -lt 1 ]
then
	LASTOPTIMIZE=`date +%s`
	echo "Optimizing DB..."
	/usr/bin/php5 ${NEWZNAB_PATH}/optimise_db.php
	/usr/bin/php5 ${NEWZNAB_PATH}/update_tvschedule.php
	/usr/bin/php5 ${NEWZNAB_PATH}/update_theaters.php
fi

echo "waiting ${NEWZNAB_SLEEP_TIME} seconds..."
sleep ${NEWZNAB_SLEEP_TIME}
done
