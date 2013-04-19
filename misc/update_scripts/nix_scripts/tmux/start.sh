#!/usr/bin/env bash

export nzb_path="/import/nzbs"

eval $( $SED -n "/^define/ { s/.*('\([^']*\)', '*\([^']*\)'*);/export \1=\"\2\"/; p }" ../../../../www/config.php )

tmux -f tmux.conf new-session -d -s nZEDb -n Monitor 'printf "\033]2;Monitor\033\\"'
tmux selectp -t 0
tmux splitw -h -p 67 'printf "\033]2;update_binaries\033\\" && for i in {1..10000}; do php ../../update_binaries.php && sleep 1; done'

tmux selectp -t 0
tmux splitw -v -p 30 'printf "\033]2;nzbcount\033\\"'

tmux selectp -t 2
tmux splitw -v -p 75 'printf "\033]2;backfill\033\\" && sleep 10 && for i in {1..10000}; do php ../../backfill.php 20000 && sleep 1; done'
tmux splitw -v -p 67 'printf "\033]2;backfill\033\\" && php ../../../testing/nzb-import-bulk.php $nzb_path'
tmux splitw -v -p 50 'printf "\033]2;update_releases\033\\" && for i in {1..10000}; do php ../../update_releases.php 1 false && sleep 1; done'

tmux select-window -tnZEDb:0
tmux respawnp -t 0 #'cd bin && $NICE -n$NICENESS $PHP monitor.php'
tmux attach-session -d -tnZEDb



