#!/bin/sh

export NEWZNAB_PATH="/var/www/newznab/misc/update_scripts"
export NIX_PATH="/var/www/newznab/misc/update_scripts/nix_scripts"
export NEWZNAB_SLEEP_TIME="60"
export PHP="$(which php5)"
export SCREEN="$(which screen)"

while :

do

clear

cd ${NEWZNAB_PATH}
if ! $SCREEN -list | grep -q "POSTP"; then
	cd $NEWZNAB_PATH && $SCREEN -dmS POSTP $SCREEN $PHP $NEWZNAB_PATH/postprocess_releases.php
fi

cd ${NEWZNAB_PATH}
$PHP ${NEWZNAB_PATH}/update_releases.php 1 false

cd ${NIX_PATH}
if ! $SCREEN -list | grep -q "BINARIES"; then
	cd $NIX_PATH && $SCREEN -dmS BINARIES $SCREEN sh $NIX_PATH/binaries.sh
fi

echo "waiting ${NEWZNAB_SLEEP_TIME} seconds..."
sleep ${NEWZNAB_SLEEP_TIME}
done
