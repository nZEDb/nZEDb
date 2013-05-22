#!/bin/sh

export NZEDB_PATH="/var/www/nZEDb/misc/update_scripts"
export NZEDB_SLEEP_TIME="1" # in seconds
command -v php5 >/dev/null 2>&1 && export PHP=`command -v php5` || { export PHP=`command -v php`; }

while :

 do
CURRTIME=`date +%s`
cd ${NZEDB_PATH}
$PHP ${NZEDB_PATH}/backfill.php safe 20000
read -r -p "Press any key to continue..." key
if [ $? -eq 0 ]; then
    echo continuing...
else
    echo No key was pressed.
fi

echo "waiting ${NZEDB_SLEEP_TIME} seconds..."
sleep ${NZEDB_SLEEP_TIME}

done
