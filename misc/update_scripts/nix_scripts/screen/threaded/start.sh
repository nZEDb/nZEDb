#!/bin/sh

export NZEDB_PATH="/var/www/nzedb/misc/update_scripts"
export NIX_PATH="/var/www/nzedb/misc/update_scripts/nix_scripts/screen/threaded"
export NZEDB_SLEEP_TIME="60"
export PHP="$(which php5)"
export SCREEN="$(which screen)"

while :

do

clear

cd ${NZEDB_PATH}
if ! $SCREEN -list | grep -q "POSTP"; then
	cd $NZEDB_PATH && $SCREEN -dmS POSTP $SCREEN $PHP $NZEDB_PATH/postprocess_releases.php
fi

cd ${NZEDB_PATH}
$PHP ${NZEDB_PATH}/update_releases.php 1 false

cd ${NIX_PATH}
if ! $SCREEN -list | grep -q "BINARIES"; then
	cd $NIX_PATH && $SCREEN -dmS BINARIES $SCREEN sh $NIX_PATH/helper.sh
fi

echo "waiting ${NZEDB_SLEEP_TIME} seconds..."
sleep ${NZEDB_SLEEP_TIME}
done
