<?php

require_once(dirname(__FILE__)."/../../../../www/config.php");
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/tmux.php");
require_once(WWW_DIR."/lib/site.php");

$db = new DB();
$DIR = WWW_DIR."/..";

$tmux = new Tmux;
$running = $tmux->get()->RUNNING;
$delay = $tmux->get()->MONITOR_DELAY;
$db->query("update tmux set value = 'FALSE' where setting = 'RUNNING'");
echo "Waiting for all panes to shutdown\n";
sleep($delay + 5);
system("php $DIR/misc/update_scripts/optimise_db.php");
$db->query(sprintf("update tmux set value = 'TRUE' where setting = '%s'",$running));

?>
