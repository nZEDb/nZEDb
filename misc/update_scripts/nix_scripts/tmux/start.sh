#!/usr/bin/env bash

export nzb_path="/import/nzbs"

tmux -f /var/www/nzedb/misc/update_scripts/nix_scripts/tmux/tmux.conf new-session -d -s nZEDb -n Monitor 'printf "\033]2;Monitor\033\\"'
tmux selectp -t 0
tmux splitw -h -p 67 'printf "\033]2;update_binaries\033\\"'

tmux selectp -t 0
tmux splitw -v -p 67 'printf "\033]2;postprocessing\033\\"'
tmux splitw -v -p 50 'printf "\033]2;postprocessing\033\\"'

tmux selectp -t 3
tmux splitw -v -p 75 'printf "\033]2;backfill\033\\"'
tmux splitw -v -p 67 'printf "\033]2;backfill\033\\"'
tmux splitw -v -p 50 'printf "\033]2;update_releases\033\\"'

tmux select-window -tnZEDb:1
tmux respawnp -t 0 'php /var/www/nzedb/misc/update_scripts/nix_scripts/tmux/monitor.php'
tmux attach-session -d -tnZEDb
