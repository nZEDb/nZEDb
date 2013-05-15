<?php

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../../www/lib/site.php");

$dir = WWW_DIR;
$site = new Sites;
$db = new DB;
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
	system("cp -vR $from ".$dir."../nzbfiles/");
	system("cp -vR ".$from."../www/covers/ ".$dir."/covers/");
	$db->query("update releases set nzbstatus = 1");
	system("php ".$dir."../misc/testing/DB_scripts/nzb-reorg.php ".$level." ".$dir."../nzbfiles/");
}
?>
