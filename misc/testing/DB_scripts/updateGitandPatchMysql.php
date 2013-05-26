<?php

require(dirname(__FILE__)."/../../../www/config.php");
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

if(isset($argv[1]) && $argv[1] == "true")
{
	$output = exec("cd $DIR && git pull");
	echo "<pre>$output<pre>";


	#remove folders from smarty
	$smarty = $DIR."/www/lib/smarty/templates_c/";
	if ((count(glob("$smarty/*",GLOB_ONLYDIR))) > 0)
	{
		echo "Removing old stuff from ".$smarty."\n";
		$output = shell_exec("rm -r ".$smarty."/*");
		echo "<pre>$output<pre>";
	}

	if (command_exist("php5"))
		$PHP = "php5";
	else
		$PHP = "php";
	system("$PHP $DIR/misc/testing/DB_scripts/patchmysql.php");
	$db->query("update tmux set value = 'TRUE' where setting = 'RUNNING'");
}
else
{
	exit("This script will automatically do a git pull, patch the DB and delete the smarty folder contents.\nIf you are sure you want to run it, type php updateGitandPatchMysql.php true\n");
}

?>
