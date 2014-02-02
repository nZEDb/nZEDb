<?php
//This script converts tables to myisam , innodb dynamic or innodb compressed, or tokudb. Run like this : php convert_mysql_tables.php dinnodb

require_once dirname(__FILE__) . '/../../../www/config.php';
//require_once nZEDb_LIB . 'framework/db.php';
//require_once nZEDb_LIB . 'ColorCLI.php';

$c = new ColorCLI();

$db = new DB();
if($db->dbSystem() == "pgsql")
	exit($c->error("\nCurrently only for mysql."));

if (isset($argv[1]) && isset($argv[2]) && $argv[2] == "myisam")
{
	$tbl = $argv[1];
	echo($c->header("Converting $tbl"));
	$db->queryExec("ALTER TABLE $tbl ENGINE=MYISAM ROW_FORMAT=FIXED");
}
else if (isset($argv[1]) && isset($argv[2]) && $argv[2] == "cinnodb")
{
	$tbl = $argv[1];
	echo($c->header("Converting $tbl"));
	$db->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=COMPRESSED");
}
else if (isset($argv[1]) && isset($argv[2]) && $argv[2] == "dinnodb")
{
	$tbl = $argv[1];
	echo($c->header("Converting $tbl"));
	$db->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=DYNAMIC");
}
else if (isset($argv[1]) && $argv[1] == "myisam")
{
	$sql = 'SHOW table status WHERE Engine != "MyIsam" ||  Row_format != "FIXED"';
	$tables = $db->query($sql);
	foreach($tables as $row)
	{
		$tbl = $row['name'];
		echo($c->header("Converting $tbl"));
		$db->queryExec("ALTER TABLE $tbl ENGINE=MYISAM ROW_FORMAT=FIXED");
	}
}
else if (isset($argv[1]) && $argv[1] == "dinnodb")
{
	$sql = 'SHOW table status WHERE Engine != "InnoDB" OR Row_format != "Dynamic"';
	$tables = $db->query($sql);
	foreach($tables as $row)
	{
		$tbl = $row['name'];
		echo($c->header("Converting $tbl"));
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
		if ($tbl !== 'releasenfo') {
		echo($c->header("Converting $tbl"));
		$db->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=COMPRESSED");
		}
	}
	$sql = 'SHOW table status WHERE Name = "releasenfo" AND (Engine != "InnoDB" || Row_format != "Dynamic")';
	$tables = $db->query($sql);
	foreach($tables as $row)
	{
		$tbl = $row['name'];
		echo($c->header("Converting $tbl"));
		$db->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=DYNAMIC");
	}
}
else if (isset($argv[1]) && $argv[1] == "collections")
{
	$arr = array("parts", "binaries", "collections");
	foreach($arr as $row)
	{
		$tbl = $row;
		echo($c->header("Converting $tbl"));
		$db->queryExec("ALTER TABLE $tbl ENGINE=MYISAM ROW_FORMAT=FIXED");
	}
}
else if (isset($argv[1]) && $argv[1] == "mariadb-tokudb")
{
	$tables = $db->query('SHOW table status WHERE Engine != "TokuDB" OR Create_options != "`COMPRESSION`=tokudb_lzma"');
	foreach($tables as $row)
	{
		$tbl = $row['name'];
		echo($c->header("Converting $tbl"));
		$sql = "ALTER TABLE $tbl ENGINE=TokuDB Compression=tokudb_lzma";
		$db->queryExec($sql);
		$db->queryExec("OPTIMIZE TABLE $tbl");
	}
}
else if (isset($argv[1]) && $argv[1] == "tokudb")
{
	$tables = $db->query('SHOW table status WHERE Engine != "TokuDB" OR ROW_FORMAT="tokudb_lzma" OR Create_options != "`COMPRESSION`=tokudb_lzma"');
	foreach($tables as $row)
	{
		$tbl = $row['name'];
		echo($c->header("Converting $tbl"));
		$sql = "ALTER TABLE $tbl ENGINE=TokuDB row_format=tokudb_lzma";
		$db->queryExec($sql);
		$db->queryExec("OPTIMIZE TABLE $tbl");
	}
}
else
{
	exit($c->error("\nWrong argument.\n\n"
		."php convert_mysql_tables.php myisam                               ...: Converts all the tables to Myisam Dynamic.\n"
		."php convert_mysql_tables.php dinnodb                              ...: Converts all the tables to InnoDB Dynamic.\n"
		."php convert_mysql_tables.php cinnodb                              ...: Converts all the tables to InnoDB Compressed.\n"
		."php convert_mysql_tables.php collections                          ...: Converts collections, binaries, parts to MyIsam.\n"
		."php convert_mysql_tables.php mariadb-tokudb                       ...: Converts all the tables to MariaDB Tokutek DB.\n"
		."php convert_mysql_tables.php tokudb                               ...: Converts all the tables to Tokutek DB.\n"
		."php convert_mysql_tables.php table [ myisam, dinnodb, cinnodb ]   ...: Converts 1 table to Engine, row_format specified.\n"));
}
