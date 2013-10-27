<?php
//This script converts tables to myisam , innodb dynamic or innodb compressed, or tokudb. Run like this : php convert_mysql_tables.php dinnodb

require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");

$db = new DB();
if($db->dbSystem() == "pgsql")
	exit("Currently only for mysql.\n");

if (isset($argv[1]) && $argv[1] == "myisam")
{
	$sql = 'SHOW table status WHERE Engine != "MyIsam"';
	$tables = $db->query($sql);
	foreach($tables as $row)
	{
		$tbl = $row['name'];
		printf("Converting $tbl\n");
		$db->queryExec("ALTER TABLE $tbl ENGINE=MYISAM");
	}
}
else if (isset($argv[1]) && $argv[1] == "dinnodb")
{
	$sql = 'SHOW table status WHERE Engine != "InnoDB" OR Row_format != "Dynamic"';
	$tables = $db->query($sql);
	foreach($tables as $row)
	{
		$tbl = $row['name'];
		printf("Converting $tbl\n");
		$db->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=DYNAMIC");
	}
}
else if (isset($argv[1]) && $argv[1] == "cinnodb")
{
	$sql = 'SHOW table status WHERE Engine != "InnoDB" OR Row_format != "Compressed"';
	$tables = $db->query($sql);
	foreach($tables as $row)
	{
		$tbl = $row['name'];
		printf("Converting $tbl\n");
		$db->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=COMPRESSED");
	}
}
else if (isset($argv[1]) && $argv[1] == "collections")
{
	$arr = array("parts", "binaries", "collections");
	foreach($arr as $row)
	{
		$tbl = $row['name'];
		printf("Converting $tbl\n");
		$db->queryExec("ALTER TABLE $tbl ENGINE=MYISAM");
	}
}
else if (isset($argv[1]) && $argv[1] == "tokudb")
{
	$sql = 'SHOW table status WHERE Engine != "TokuDB"';
	$tables = $db->query($sql);
	foreach($tables as $row)
	{
		$tbl = $row['name'];
		printf("Converting $tbl\n");
		if ($tbl != "parts" && $tbl != "binaries" && $tbl != "collections")
			$sql = "ALTER TABLE $tbl ENGINE=TokuDB row_format=tokudb_quicklz";
		else
			$sql = "ALTER TABLE $tbl ENGINE=TokuDB row_format=tokudb_uncompressed";
		$db->queryExec($sql);
		$db->queryExec("OPTIMIZE TABLE $tbl");
	}
}
else
{
	exit("\nERROR: Wrong argument.\n\n"
		."php convert_mysql_tables.php myisam	...: Converts all the tables to Myisam Dynamic.\n"
		."php convert_mysql_tables.php dinnodb	...: Converts all the tables to InnoDB Dynamic.\n"
		."php convert_mysql_tables.php cinnodb	...: Converts all the tables to InnoDB Compressed.\n"
		."php convert_mysql_tables.php collections : Converts collections, binaries, parts to MyIsam.\n"
		."php convert_mysql_tables.php tokudb	...: Converts all the tables to Tokutek DB.\n\n");
}
