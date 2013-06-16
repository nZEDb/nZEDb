<?php

require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/tmux.php");

$db = new DB();
$DIR = MISC_DIR;
$smarty = SMARTY_DIR."templates_c/";
$dbname = DB_NAME;

function command_exist($cmd) {
	$returnVal = shell_exec("which $cmd");
	return (empty($returnVal) ? false : true);
}

if (command_exist("php5"))
	$PHP = "php5";
else
	$PHP = "php";

if(isset($argv[1]) && $argv[1] == "true")
{
	$tmux = new Tmux();
	$running = $tmux->get()->RUNNING;
	$delay = $tmux->get()->MONITOR_DELAY;
	$patch = $tmux->get()->PATCHDB;
	$restart = "false";

	if ( $running == "TRUE" )
	{
		$db->query("update tmux set value = 'FALSE' where setting = 'RUNNING'");
		$sleep = $delay;
		echo "Stopping tmux scripts and waiting $sleep seconds for all panes to shutdown\n";
		$restart = "true";
		sleep($sleep);
	}

	if ( $patch == "TRUE" )
	{
		exec("cd $DIR && git pull");

		//remove folders from smarty
		if ((count(glob("${smarty}*"))) > 0)
		{
			echo "Removing old stuff from ".$smarty."\n";
			exec("rm -rf ".$smarty."*");
		}
		else
		{
			echo "Nothing to remove from ".$smarty."\n";
		}

		echo "Patching database - $dbname\n";
		exec("$PHP ${DIR}testing/DB_scripts/patchmysql.php");
	}

	$alltables = $db->query("show table status where Data_free > 0");
	$tablecnt = sizeof($alltables);
	if ($tablecnt > 0)
	{
		foreach ($alltables as $tablename)
		{
			$name = $tablename['Name'];
			echo "Optimizing table: ".$name.".\n";
			if (strtolower($tablename['Engine']) == "myisam")
				$db->queryDirect("REPAIR TABLE `".$name."`");
			$db->queryDirect("OPTIMIZE TABLE `".$name."`");
			if (strtolower($tablename['Engine']) == "myisam")
				$db->queryDirect("FLUSH TABLES");
		}
		if ($tablecnt = 1)
			echo $tablecnt." table Optimized\n";
		else
			echo $tablecnt." tables Optimized\n";
	}
	if ( $restart == "true" )
	{
		echo "Starting tmux scripts\n";
		$db->query("update tmux set value = 'TRUE' where setting = 'RUNNING'");
	}
}
else
{
	exit("If you have set the settings in adin tmux, then this script will automatically do a git pull, patch the DB and delete the smarty folder contents and optimize the database.\nphp optimize.php true\n");
}

?>
