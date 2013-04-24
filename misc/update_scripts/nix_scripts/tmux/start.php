<?php

require_once(dirname(__FILE__)."/../../../../www/config.php");
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/tmux.php");

$db = new DB();
$DIR = WWW_DIR."/..";

$tmux = new Tmux;
$session = $tmux->get()->TMUX_SESSION;

//reset collections dateadded to now
print("Resetting expired collections dateadded to now. This could take a minute or two.");
$db->query("update collections set dateadded = now() WHERE dateadded > (now() - interval 1.5 hour)");

//create tmux
shell_exec("tmux -f $DIR/misc/update_scripts/nix_scripts/tmux/tmux.conf new-session -d -s $session -n Monitor 'printf \"\033]2;Monitor\033\\\"'");
shell_exec("tmux selectp -t 0 && tmux splitw -h -p 67 'printf \"\033]2;update_binaries\033\\\"'");
shell_exec("tmux selectp -t 0 && tmux splitw -v -p 50 'printf \"\033]2;postprocess_nfos\033\\\"' && tmux splitw -v -p 50 'printf \"\033]2;postprocess_all\033\\\"'");
shell_exec("tmux selectp -t 3 && tmux splitw -v -p 75 'printf \"\033]2;backfill\033\\\"' && tmux splitw -v -p 67 'printf \"\033]2;nzb-import-bulk\033\\\"' && tmux splitw -v -p 50 'printf \"\033]2;update_releases\033\\\"'");
shell_exec("tmux respawnp -t 0 'php $DIR/misc/update_scripts/nix_scripts/tmux/monitor.php'");
shell_exec("tmux select-window -t$session:1 && tmux attach-session -d -t$session");

?>
