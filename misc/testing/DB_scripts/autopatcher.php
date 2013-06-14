<?php

require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/tmux.php");

$db = new DB();
$DIR = MISC_DIR;
$smarty = SMARTY_DIR."templates_c/";
$dbname = DB_NAME;
$restart = "false";

function command_exist($cmd) {
	$returnVal = shell_exec("which $cmd");
	return (empty($returnVal) ? false : true);
}

if(isset($argv[1]) && $argv[1] == "true")
{
	$tmux = new Tmux();
	$running = $tmux->get()->RUNNING;
	$delay = $tmux->get()->MONITOR_DELAY;

	if ( $running == "TRUE" )
	{
		$db->query("update tmux set value = 'FALSE' where setting = 'RUNNING'");
		$sleep = $delay;
		echo "Stopping tmux scripts and waiting $sleep seconds for all panes to shutdown\n";
		sleep($sleep);
		$restart = "true";
	}

	system("cd $DIR && git pull");

	//remove folders from smarty
	if ((count(glob("${smarty}*"))) > 0)
	{
		echo "Removing old stuff from ".$smarty."\n";
		system("rm -rf ".$smarty."*");
	}
	else
	{
		echo "Nothing to remove from ".$smarty."\n";
	}

	if (command_exist("php5"))
		$PHP = "php5";
	else
		$PHP = "php";

	echo "Patching database - $dbname\n";
	system("$PHP ${DIR}testing/DB_scripts/patchmysql.php");
	if ( $restart == "true" )
	{
		echo "Starting tmux scripts\n";
		$db->query("update tmux set value = 'TRUE' where setting = 'RUNNING'");
	}
}
else
{
	exit("This script will automatically do a git pull, patch the DB and delete the smarty folder contents.\nIf you are sure you want to run it, type php autopatcher.php true\n");
}

?>
