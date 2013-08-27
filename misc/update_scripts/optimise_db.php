<?php
require_once(dirname(__FILE__)."/config.php");
require_once(WWW_DIR."lib/framework/db.php");

$db = new DB();
$type = $db->dbSystem();
if ($type == "mysql")
{
	$a = "MySQL";
	$b = "Optimizing";
	$c = "Optimized";
}
if ($type == "pgsql")
{
	$a = "PostgreSQL";
	$b = "Vacuuming";
	$c = "Vacuumed";
}
echo "{$b} {$a} tables, this can take a while...\n";
$tablecnt = $db->optimise();
if ($tablecnt > 0)
	exit ("{$c} {$tablecnt} {$a} tables succesfuly.\n");
else
	exit ("No {$a} tables to optimize.\n");
