#!/bin/sh

export NEWZNAB_PATH="/var/www/newznab/misc/update_scripts"
export NIX_PATH="/var/www/newznab/misc/update_scripts/nix_scripts"
export NEWZNAB_SLEEP_TIME="60"
export PHP="$(which php5)"
export SCREEN="$(which screen)"

while :

do

cd ${NIX_PATH}
if ! $SCREEN -list | grep -q "binaries"; then
	cd $NIX_PATH && $SCREEN -dmS binaries $SCREEN sh $NIX_PATH/update.sh
fi

cd ${NEWZNAB_PATH}
$PHP ${NEWZNAB_PATH}/update_releases.php 3

echo "waiting ${NEWZNAB_SLEEP_TIME} seconds..."
sleep ${NEWZNAB_SLEEP_TIME}
done
