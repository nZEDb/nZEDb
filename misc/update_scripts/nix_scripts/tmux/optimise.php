<?php

require_once(dirname(__FILE__)."/../../../../www/config.php");
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/tmux.php");

$db = new DB();
$DIR = WWW_DIR."/..";

$tmux = new Tmux;
$delay = $tmux->get()->MONITOR_DELAY;
$db->query("update tmux set value = 'FALSE' where setting = 'RUNNING'");
$sleep = $delay + 120;
echo "Waiting $sleep seconds for all panes to shutdown\n";
sleep($sleep);

function command_exist($cmd) {
	$returnVal = shell_exec("which $cmd");
	return (empty($returnVal) ? false : true);
}

if (command_exist("php5"))
	$PHP = "php5";
else
	$PHP = "php";
system("$PHP $DIR/misc/update_scripts/optimise_db.php");
$db->query("update tmux set value = 'TRUE' where setting = 'RUNNING'");

?>
