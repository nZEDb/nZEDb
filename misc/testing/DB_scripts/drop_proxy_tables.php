<?php
require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");


$db = new DB();
if ($db->dbSystem() == "pgsql")
	exit("This script is only for mysql.\n");

$sql = "SHOW tables";
$tables = $db->query($sql);
foreach($tables as $row)
{
	$tbl = $row['tables_in_'.DB_NAME];
	if (preg_match('/\d+_collections/',$tbl) || preg_match('/\d+_binaries/',$tbl) || preg_match('/\d+_parts/',$tbl))
	{
		$db->queryDirect(sprintf('DROP TABLE %s', $tbl));
		printf("DROP TABLE %s;\n", $tbl);
	}
}
