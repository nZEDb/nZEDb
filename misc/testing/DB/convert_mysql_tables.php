<?php

require_once dirname(__FILE__) . '/../../../www/config.php';
$c = new ColorCLI();

$db = new DB();
if($db->dbSystem() == "pgsql")
	exit($c->error("\nCurrently only for mysql."));

if (isset($argv[1]) && isset($argv[2]) && $argv[2] == "fmyisam") {
	$tbl = $argv[1];
	printf($c->header("Converting $tbl"));
	$db->queryExec("ALTER TABLE $tbl ENGINE=MYISAM ROW_FORMAT=FIXED");
} else if (isset($argv[1]) && isset($argv[2]) && $argv[2] == "dmyisam") {
	$tbl = $argv[1];
	printf($c->header("Converting $tbl"));
	$db->queryExec("ALTER TABLE $tbl ENGINE=MYISAM ROW_FORMAT=DYNAMIC");
} else if (isset($argv[1]) && isset($argv[2]) && $argv[2] == "cinnodb") {
	$tbl = $argv[1];
	printf($c->header("Converting $tbl"));
	$db->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=COMPRESSED");
} else if (isset($argv[1]) && isset($argv[2]) && $argv[2] == "dinnodb") {
	$tbl = $argv[1];
	printf($c->header("Converting $tbl"));
	$db->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=DYNAMIC");
} else if (isset($argv[1]) && $argv[1] == "fmyisam") {
	$sql = 'SHOW table status WHERE Engine != "MyIsam" ||  Row_format != "FIXED"';
	$tables = $db->query($sql);
	foreach($tables as $row) {
		$tbl = $row['name'];
		printf($c->header("Converting $tbl"));
		$db->queryExec("ALTER TABLE $tbl ENGINE=MYISAM ROW_FORMAT=FIXED");
	}
} else if (isset($argv[1]) && $argv[1] == "dmyisam") {
	$sql = 'SHOW table status WHERE Engine != "MyIsam" ||  Row_format != "Dynamic"';
	$tables = $db->query($sql);
	foreach($tables as $row) {
		$tbl = $row['name'];
		printf($c->header("Converting $tbl"));
		$db->queryExec("ALTER TABLE $tbl ENGINE=MYISAM ROW_FORMAT=DYNAMIC");
	}
} else if (isset($argv[1]) && $argv[1] == "dinnodb") {
	$sql = 'SHOW table status WHERE Engine != "InnoDB" OR Row_format != "Dynamic"';
	$tables = $db->query($sql);
	foreach($tables as $row) {
		$tbl = $row['name'];
		printf($c->header("Converting $tbl"));
		$db->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=DYNAMIC");
	}
} else if (isset($argv[1]) && $argv[1] == "cinnodb") {
	$sql = 'SHOW table status WHERE Engine != "InnoDB" OR Row_format != "Compressed"';
	$tables = $db->query($sql);
	foreach($tables as $row) {
		$tbl = $row['name'];
		if ($tbl !== 'releasenfo') {
		printf($c->header("Converting $tbl"));
		$db->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=COMPRESSED");
		}
	}
	$sql = 'SHOW table status WHERE Name = "releasenfo" AND (Engine != "InnoDB" || Row_format != "Dynamic")';
	$tables = $db->query($sql);
	foreach($tables as $row) {
		$tbl = $row['name'];
		printf($c->header("Converting $tbl"));
		$db->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=DYNAMIC");
	}
} else if (isset($argv[1]) && $argv[1] == "cinnodb-noparts") {
	$sql = 'SHOW table status WHERE Engine != "InnoDB" OR Row_format != "Compressed"';
	$tables = $db->query($sql);
	foreach($tables as $row) {
		$tbl = $row['name'];
		if ($tbl !== 'releasenfo' && !preg_match('/parts/', $tbl)) {
		printf($c->header("Converting $tbl"));
		$db->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=COMPRESSED");
		}
	}
	$sql = 'SHOW table status WHERE Name = "releasenfo" AND (Engine != "InnoDB" || Row_format != "Dynamic")';
	$tables = $db->query($sql);
	foreach($tables as $row) {
		$tbl = $row['name'];
		printf($c->header("Converting $tbl"));
		$db->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=DYNAMIC");
	}
	$sql = 'SHOW table status WHERE Name LIKE "parts%" AND (Engine != "MyISAM" || Row_format != "Dynamic")';
	$tables = $db->query($sql);
	foreach($tables as $row) {
		$tbl = $row['name'];
		printf($c->header("Converting $tbl"));
		$db->queryExec("ALTER TABLE $tbl ENGINE=MyISAM ROW_FORMAT=DYNAMIC");
	}
} else if (isset($argv[1]) && $argv[1] == "collections") {
	$arr = array("parts", "binaries", "collections");
	foreach($arr as $row) {
		$tbl = $row;
		printf($c->header("Converting $tbl"));
		$db->queryExec("ALTER TABLE $tbl ENGINE=MYISAM ROW_FORMAT=FIXED");
	}
} else if (isset($argv[1]) && $argv[1] == "mariadb-tokudb") {
	$tables = $db->query('SHOW table status WHERE Engine != "TokuDB" OR Create_options != "`COMPRESSION`=tokudb_lzma"');
	foreach($tables as $row) {
		$tbl = $row['name'];
		printf($c->header("Converting $tbl"));
		$sql = "ALTER TABLE $tbl ENGINE=TokuDB Compression=tokudb_lzma";
		$db->queryExec($sql);
		$db->queryExec("OPTIMIZE TABLE $tbl");
	}
} else if (isset($argv[1]) && $argv[1] == "tokudb") {
	$tables = $db->query('SHOW table status WHERE Engine != "TokuDB" OR ROW_FORMAT="tokudb_lzma" OR Create_options != "`COMPRESSION`=tokudb_lzma"');
	foreach($tables as $row) {
		$tbl = $row['name'];
		printf($c->header("Converting $tbl"));
		$sql = "ALTER TABLE $tbl ENGINE=TokuDB row_format=tokudb_lzma";
		$db->queryExec($sql);
		$db->queryExec("OPTIMIZE TABLE $tbl");
	}
} else {
	exit($c->error("\nThis script will convert your tables to a new engine/format. Only tables not meeting the new engine/format will be converted.\n"
		. "A comparison of these, excluding TokuDB, https://github.com/nZEDb/nZEDb/wiki/MySQL-Storage-Engine-Comparison\n\n"
		. "php convert_mysql_tables.php dmyisam                                        ...: Converts all the tables to Myisam Dynamic. This is the default and is recommended where ram is limited.\n"
		. "php convert_mysql_tables.php fmyisam                                        ...: Converts all the tables to Myisam Fixed. This can be faster, but to fully convert all tables requires changing varchar columns to char.\n"
		. "                                                                                 This will use mucgh more space than dynamic.\n"
		. "php convert_mysql_tables.php dinnodb                                        ...: Converts all the tables to InnoDB Dynamic. This is recommended when the total data and indexes can fit into the innodb_buffer_pool.\n"
		. "php convert_mysql_tables.php cinnodb                                        ...: Converts all the tables to InnoDB Compressed. All tables except releasenfo will be converted to Compressed row format.\n"
		. "                                                                                 This is recommended when the total data and indexes can not fit into the innodb_buffer_pool using DYNAMIC row format.\n"
		. "php convert_mysql_tables.php cinnodb-noparts                                ...: Converts all the tables to InnoDB Compressed. All tables except parts and releasenfo will be converted to Compressed row format.\n"
		. "                                                                                 Alls parts* will be converted to MyISAM Dynamic. This is recommended when using Table Per Group.\n"
		. "php convert_mysql_tables.php collections                                    ...: Converts collections, binaries, parts to MyIsam.\n"
		. "php convert_mysql_tables.php mariadb-tokudb                                 ...: Converts all the tables to MariaDB Tokutek DB. Use this is you installed mariadb-tokudb-engine. \n"
		. "                                                                                 The TokuDB engine needs to be activated first.\n"
		. "                                                                                 https://mariadb.com/kb/en/how-to-enable-tokudb-in-mariadb/\n"
		. "php convert_mysql_tables.php tokudb                                         ...: Converts all the tables to Tokutek DB. Use this if you downloaded and installed the TokuDB binaries.\n"
		. "                                                                                 http://www.tokutek.com/resources/support/gadownloads/\n"
		. "php convert_mysql_tables.php table [ fmyisam, dmyisam, dinnodb, cinnodb ]   ...: Converts 1 table to Engine, row_format specified.\n"));
}
