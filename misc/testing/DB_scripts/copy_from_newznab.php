<?php
require dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'site.php';
require_once nZEDb_LIB . 'util.php';

$dir = nZEDb_WWW;
$misc = nZEDb_MISC;
$site = new Sites();
$db = new DB();
$level = $site->get()->nzbsplitlevel;

if (isWindows() === true)
	exit("Curently this is only for linux.\n");

if (!isset($argv[1]))
	exit("Usage php copy_from_newznab.php newznab_path_to_nzbs\n");
elseif (isset($argv[1]) && !file_exists($argv[1]))
	exit("$argv[1]) is an invalid path\n");
else
{
	$from = $argv[1];
	echo "Copying nzbs from ".$from."\n";
	system("cp -R ".$from."/* ".$dir."../nzbfiles/");
	echo "Copying covers from ".$from."/../www/covers\n";
	system("cp -R ".$from."/../www/covers/* ".$dir."/covers/");
	echo "Setting nzbstatus for all releases\n";
	$db->queryExec("update releases set nzbstatus = 1");
	system("php ".$misc."testing/DB_scripts/nzb-reorg.php ".$level." ".$dir."../nzbfiles/");
}
?>
