#!/usr/bin/env bash

cd /var/www/nZEDb
git pull origin dev
commit=`git log | grep "^commit" | wc -l`
commit=`expr $commit + 1`

sed -i -e "s/\$version=.*$/\$version=\"0.1r$commit\";/"  /var/www/nZEDb/misc/update_scripts/nix_scripts/tmux/monitor.php

git commit -a


