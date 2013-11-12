#!/usr/bin/env bash

export NZEDB_ROOT="$(php ../../../../nZEDbBase.php)"
nano Changelog
cd ${NZEDB_ROOT}
commit=`git log | grep "^commit" | wc -l`
commit=`expr $commit + 1`

sed -i -e "s/\$version=.*$/\$version=\"0.3r$commit\";/"  ${NZEDB_ROOT}/misc/update_scripts/nix_scripts/tmux/monitor.php

git commit -a


