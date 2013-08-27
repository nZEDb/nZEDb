<?php
//This script converts tables to myisam , innodb dynamic or innodb compressed, or tokudb. Run like this : php convert_mysql_tables.php dinnodb

require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");

$sql = "SHOW tables";
$db = new DB();
if($db->dbSystem() == "pgsql")
	exit("Currently only for mysql.\n");

if (isset($argv[1]) && $argv[1] == "myisam")
{ 
	$tables = $db->query($sql);
	foreach($tables as $row)
	{
		$tbl = $row['Tables_in_'.DB_NAME];
		printf("Converting $tbl\n");
		$db->query("ALTER TABLE $tbl ENGINE=MYISAM");
	}
}
else if (isset($argv[1]) && $argv[1] == "dinnodb")
{ 
	$tables = $db->query($sql);
	foreach($tables as $row)
	{
		$tbl = $row['Tables_in_'.DB_NAME];
		printf("Converting $tbl\n");
		$db->query("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=DYNAMIC");
	}
}
else if (isset($argv[1]) && $argv[1] == "cinnodb")
{ 
	$tables = $db->query($sql);
	foreach($tables as $row)
	{
		$tbl = $row['Tables_in_'.DB_NAME];
		printf("Converting $tbl\n");
		$db->query("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=COMPRESSED");
	}
}
else if (isset($argv[1]) && $argv[1] == "tokudb")
{
	$tables = $db->query($sql);
	foreach($tables as $row)
	{
		$tbl = $row['Tables_in_'.DB_NAME];
		printf("Converting $tbl\n");
		if ($tbl != "parts" || $tbl != "binaries" || $tbl != "collections")
			$sql = "ALTER TABLE $tbl ENGINE=TokuDB row_format=tokudb_quicklz";
		else
			$sql = "ALTER TABLE $tbl ENGINE=TokuDB row_format=tokudb_uncompressed";
		$db->query($sql);
		$db->queryDirect("OPTIMIZE TABLE $tbl");
	}
}
else
{
	exit("\nERROR: Wrong argument.\n\n"
		."php convert_mysql_tables.php myisam	...: Converts all the tables to Myisam Dynamic.\n"
		."php convert_mysql_tables.php dinnodb	...: Converts all the tables to InnoDB Dynamic.\n"
		."php convert_mysql_tables.php cinnodb	...: Converts all the tables to InnoDB Compressed.\n"
		."php convert_mysql_tables.php tokudb	...: Converts all the tables to Tokutek DB.\n\n");
}
