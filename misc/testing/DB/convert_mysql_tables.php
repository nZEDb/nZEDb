<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$cli = new \ColorCLI();

$pdo = new Settings(['checkVersion' => true]);
$ftinnodb = $pdo->isDbVersionAtLeast('5.6');

if (isset($argv[1]) && isset($argv[2]) && $argv[2] == "fmyisam") {
	$tbl = $argv[1];
	printf($cli->header("Converting $tbl"));
	$pdo->queryExec("ALTER TABLE $tbl ENGINE=MYISAM ROW_FORMAT=FIXED");
} else if (isset($argv[1]) && isset($argv[2]) && $argv[2] == "dmyisam") {
	$tbl = $argv[1];
	printf($cli->header("Converting $tbl"));
	$pdo->queryExec("ALTER TABLE $tbl ENGINE=MYISAM ROW_FORMAT=DYNAMIC");
} else if (isset($argv[1]) && isset($argv[2]) && $argv[2] == "cinnodb") {
	$tbl = $argv[1];
	if ($ftinnodb || (!$ftinnodb && $tbl !== 'releasesearch' && $tbl !== 'predbhash')) {
		printf($cli->header("Converting $tbl"));
		$pdo->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=COMPRESSED");
	} else {
		printf($cli->header("Not converting releasesearch / predbhash as your INNODB version does not support fulltext indexes"));
	}
} else if (isset($argv[1]) && isset($argv[2]) && $argv[2] == "dinnodb") {
	$tbl = $argv[1];
	if ($ftinnodb || (!$ftinnodb && $tbl !== 'releasesearch' && $tbl !== 'predbhash')) {
		printf($cli->header("Converting $tbl"));
		$pdo->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=DYNAMIC");
	} else {
		printf($cli->header("Not converting releasesearch / predbhash as your INNODB version does not support fulltext indexes"));
	}
} else if (isset($argv[1]) && $argv[1] == "fmyisam") {
	$sql = 'SHOW TABLE STATUS WHERE (Engine != "MyIsam" OR Row_format != "FIXED") AND Engine != "SPHINX"';
	$tables = $pdo->query($sql);
	foreach ($tables as $row) {
		$tbl = $row['name'];
		printf($cli->header("Converting $tbl"));
		$pdo->queryExec("ALTER TABLE $tbl ENGINE=MYISAM ROW_FORMAT=FIXED");
	}
} else if (isset($argv[1]) && $argv[1] == "dmyisam") {
	$sql = 'SHOW TABLE STATUS WHERE (Engine != "MyIsam" OR Row_format != "Dynamic) AND Engine != "SPHINX"';
	$tables = $pdo->query($sql);
	foreach ($tables as $row) {
		$tbl = $row['name'];
		printf($cli->header("Converting $tbl"));
		$pdo->queryExec("ALTER TABLE $tbl ENGINE=MYISAM ROW_FORMAT=DYNAMIC");
	}
} else if (isset($argv[1]) && $argv[1] == "dinnodb") {
	$sql = 'SHOW TABLE STATUS WHERE (Engine != "InnoDB" OR Row_format != "Dynamic") AND Engine != "SPHINX"';
	$tables = $pdo->query($sql);
	foreach ($tables as $row) {
		$tbl = $row['name'];
		if ($tbl !== 'releasesearch' && $tbl !== 'predbhash') {
			printf($cli->header("Converting $tbl"));
			$pdo->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=DYNAMIC");
		}
	}
	if ($ftinnodb) {
		$sql = 'SHOW TABLE STATUS WHERE Name IN ("releasesearch", "predbhash") AND (Engine != "InnoDB" || Row_format != "Dynamic")';
		$tables = $pdo->query($sql);
		foreach ($tables as $row) {
			$tbl = $row['name'];
			printf($cli->header("Converting $tbl"));
			$pdo->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=DYNAMIC");
		}
	} else {
		printf($cli->header("Not converting releasesearch as your INNODB version does not support fulltext indexes"));
	}
} else if (isset($argv[1]) && $argv[1] == "cinnodb") {
	$sql = 'SHOW TABLE STATUS WHERE (Engine != "InnoDB" OR Row_format != "Compressed") AND Engine != "SPHINX"';
	$tables = $pdo->query($sql);
	foreach ($tables as $row) {
		$tbl = $row['name'];
		if ($tbl !== 'releasenfo' && $tbl !== 'releasesearch') {
			printf($cli->header("Converting $tbl"));
			$pdo->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=COMPRESSED");
		}
	}
	$sql = 'SHOW TABLE STATUS WHERE Name = "releasenfo" AND (Engine != "InnoDB" || Row_format != "Dynamic")';
	$tables = $pdo->query($sql);
	foreach ($tables as $row) {
		$tbl = $row['name'];
		printf($cli->header("Converting $tbl"));
		$pdo->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=DYNAMIC");
	}
	if ($ftinnodb) {
		$sql = 'SHOW TABLE STATUS WHERE Name IN ("releasesearch", "predbhash") AND (Engine != "InnoDB" || Row_format != "Compressed")';
		$tables = $pdo->query($sql);
		foreach ($tables as $row) {
			$tbl = $row['name'];
			printf($cli->header("Converting $tbl"));
			$pdo->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=COMPRESSED");
		}
	} else {
		printf($cli->header("Not converting releasesearch / predbhash as your INNODB version does not support fulltext indexes"));
	}
} else if (isset($argv[1]) && $argv[1] == "cinnodb-noparts") {
	$sql = 'SHOW TABLE STATUS WHERE (Engine != "InnoDB" OR Row_format != "Compressed") AND Engine != "SPHINX"';
	$tables = $pdo->query($sql);
	foreach ($tables as $row) {
		$tbl = $row['name'];
		if ($tbl !== 'releasenfo' && $tbl !== 'releasesearch' && !preg_match('/parts/', $tbl)) {
			printf($cli->header("Converting $tbl"));
			$pdo->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=COMPRESSED");
		}
	}
	$sql = 'SHOW TABLE STATUS WHERE Name = "releasenfo" AND (Engine != "InnoDB" || Row_format != "Dynamic")';
	$tables = $pdo->query($sql);
	foreach ($tables as $row) {
		$tbl = $row['name'];
		printf($cli->header("Converting $tbl"));
		$pdo->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=DYNAMIC");
	}
	$sql = 'SHOW TABLE STATUS WHERE Name LIKE "parts%" AND (Engine != "MyISAM" || Row_format != "Dynamic")';
	$tables = $pdo->query($sql);
	foreach ($tables as $row) {
		$tbl = $row['name'];
		printf($cli->header("Converting $tbl"));
		$pdo->queryExec("ALTER TABLE $tbl ENGINE=MyISAM ROW_FORMAT=DYNAMIC");
	}
	if ($ftinnodb) {
		$sql = 'SHOW TABLE STATUS WHERE Name IN ("releasesearch", "predbhash") AND (Engine != "InnoDB" || Row_format != "Compressed")';
		$tables = $pdo->query($sql);
		foreach ($tables as $row) {
			$tbl = $row['name'];
			printf($cli->header("Converting $tbl"));
			$pdo->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=COMPRESSED");
		}
	} else {
		printf($cli->header("Not converting releasesearch / predbhash as your INNODB version does not support fulltext indexes"));
	}
} else if (isset($argv[1]) && $argv[1] == "collections") {
	$arr = array("parts", "binaries", "collections");
	foreach ($arr as $row) {
		$tbl = $row;
		printf($cli->header("Converting $tbl"));
		$pdo->queryExec("ALTER TABLE $tbl ENGINE=MYISAM ROW_FORMAT=FIXED");
	}
} else if (isset($argv[1]) && $argv[1] == "mariadb-tokudb") {
	$tables = $pdo->query('SHOW TABLE STATUS WHERE (Engine != "TokuDB" OR Create_options != "`COMPRESSION`=tokudb_lzma") AND Engine != "SPHINX"');
	foreach ($tables as $row) {
		$tbl = $row['name'];
		if ($tbl !== 'releasesearch') {
			printf($cli->header("Converting $tbl"));
			$sql = "ALTER TABLE $tbl ENGINE=TokuDB Compression=tokudb_lzma";
			$pdo->queryExec($sql);
			$pdo->queryExec("OPTIMIZE TABLE $tbl");
		}
	}
} else if (isset($argv[1]) && $argv[1] == "tokudb") {
	$tables = $pdo->query('SHOW TABLE STATUS WHERE (Engine != "TokuDB" OR ROW_FORMAT="tokudb_lzma" OR Create_options != "`COMPRESSION`=tokudb_lzma") AND Engine != "SPHINX"');
	foreach ($tables as $row) {
		$tbl = $row['name'];
		if ($tbl !== 'releasesearch') {
			printf($cli->header("Converting $tbl"));
			$sql = "ALTER TABLE $tbl ENGINE=TokuDB row_format=tokudb_lzma";
			$pdo->queryExec($sql);
			$pdo->queryExec("OPTIMIZE TABLE $tbl");
		}
	}
} else {
	exit($cli->error(
		"\nThis script will convert your tables to a new engine/format. Only tables not meeting the new engine/format will be converted.\n"
		. "A comparison of these, excluding TokuDB, https://github.com/nZEDb/nZEDb/wiki/MySQL-Storage-Engine-Comparison\n\n"
		. "php convert_mysql_tables.php dmyisam                                        ...: Converts all the tables to Myisam Dynamic. This is the default and is recommended where ram is limited.\n"
		. "php convert_mysql_tables.php fmyisam                                        ...: Converts all the tables to Myisam Fixed. This can be faster, but to fully convert all tables requires changing varchar columns to char.\n"
		. "                                                                                 This will use much more space than dynamic.\n"
		. "php convert_mysql_tables.php dinnodb                                        ...: Converts all the tables to InnoDB Dynamic. This is recommended when the total data and indexes can fit into the innodb_buffer_pool.\n"
		. "                                                                                 NB if your innodb version < 5.6 releasesearch / predbhash will not be converted as fulltext indexes are not supported.\n"
		. "php convert_mysql_tables.php cinnodb                                        ...: Converts all the tables to InnoDB Compressed. All tables except releasenfo will be converted to Compressed row format.\n"
		. "                                                                                 This is recommended when the total data and indexes can not fit into the innodb_buffer_pool using DYNAMIC row format.\n"
		. "                                                                                 NB if your innodb version < 5.6 releasesearch / predbhash will not be converted as fulltext indexes are not supported.\n"
		. "php convert_mysql_tables.php cinnodb-noparts                                ...: Converts all the tables to InnoDB Compressed. All tables except parts and releasenfo will be converted to Compressed row format.\n"
		. "                                                                                 Alls parts* will be converted to MyISAM Dynamic. This is recommended when using Table Per Group.\n"
		. "                                                                                 NB if your innodb version < 5.6 releasesearch / predbhash will not be converted as fulltext indexes are not supported.\n"
		. "php convert_mysql_tables.php collections                                    ...: Converts collections, binaries, parts to MyIsam.\n"
		. "php convert_mysql_tables.php mariadb-tokudb                                 ...: Converts all the tables to MariaDB Tokutek DB. Use this is you installed mariadb-tokudb-engine. \n"
		. "                                                                                 The TokuDB engine needs to be activated first.\n"
		. "                                                                                 https://mariadb.com/kb/en/how-to-enable-tokudb-in-mariadb/\n"
		. "                                                                                 NB releasesearch will not be converted as tokudb does not support fulltext indexes.\n"
		. "php convert_mysql_tables.php tokudb                                         ...: Converts all the tables to Tokutek DB. Use this if you downloaded and installed the TokuDB binaries.\n"
		. "                                                                                 http://www.tokutek.com/resources/support/gadownloads/\n"
		. "                                                                                 NB releasesearch will not be converted as tokudb does not support fulltext indexes.\n"
		. "php convert_mysql_tables.php table [ fmyisam, dmyisam, dinnodb, cinnodb ]   ...: Converts 1 table to Engine, row_format specified.\n"
		. "                                                                                 NB if converting to innodb and your innodb version < 5.6 releasesearch / predbhash will not be converted as fulltext indexes are not supported.\n"
	));
}