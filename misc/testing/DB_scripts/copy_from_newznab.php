<?php

require(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/site.php");

$dir = WWW_DIR;
$misc = MISC_DIR;
$site = new Sites();
$db = new DB();
$level = $site->get()->nzbsplitlevel;

if (!isset($argv[1]))
{
	echo "Usage php copy_from_newznab.php newznab_path_to_nzbs\n\n";
}
elseif (isset($argv[1]) && !file_exists($argv[1]))
{
	echo "$argv[1]) is an invalid path\n\n";
}
else
{
	$from = $argv[1];
	echo "Copying nzbs from ".$from."\n";
	system("cp -R ".$from."/* ".$dir."../nzbfiles/");
    echo "Copying covers from ".$from."/../www/covers\n";
	system("cp -R ".$from."/../www/covers/* ".$dir."/covers/");
	echo "Setting nzbstatus for all releases\n";
	$db->query("update releases set nzbstatus = 1");
	system("php ".$misc."testing/DB_scripts/nzb-reorg.php ".$level." ".$dir."../nzbfiles/");
}
?>
