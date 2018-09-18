<?php
require_once realpath(dirname(__DIR__, 3) . '/app/config/bootstrap.php');

use nzedb\ColorCLI;
use nzedb\db\DB;

$cli = new ColorCLI();

$pdo = new DB(['checkVersion' => true]);

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
	printf($cli->header("Converting $tbl"));
	$pdo->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=COMPRESSED");
} else if (isset($argv[1]) && isset($argv[2]) && $argv[2] == "dinnodb") {
	$tbl = $argv[1];
	printf($cli->header("Converting $tbl"));
	$pdo->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=DYNAMIC");
} else if (isset($argv[1]) && $argv[1] == "fmyisam") {
	$sql = 'SHOW TABLE STATUS WHERE (Engine != "MyIsam" OR Row_format != "FIXED") AND Engine != "SPHINX"';
	$tables = $pdo->query($sql);
	foreach ($tables as $row) {
		$tbl = $row['name'];
		printf($cli->header("Converting $tbl"));
		$pdo->queryExec("ALTER TABLE $tbl ENGINE=MYISAM ROW_FORMAT=FIXED");
	}
} else if (isset($argv[1]) && $argv[1] == "dmyisam") {
	$sql = 'SHOW TABLE STATUS WHERE (Engine != "MyIsam" OR Row_format != "Dynamic") AND Engine != "SPHINX"';
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
		if ($tbl !== 'release_search_data' &&
			$tbl !== 'bookinfo' &&
			$tbl !== 'consoleinfo' &&
			$tbl !== 'musicinfo'
		) {
			printf($cli->header("Converting $tbl"));
			$pdo->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=DYNAMIC");
		}
	}
	$sql = 'SHOW TABLE STATUS WHERE Name IN ("release_search_data", "bookinfo", "consoleinfo", "musicinfo") AND (Engine != "InnoDB" || Row_format != "Dynamic")';
	$tables = $pdo->query($sql);
	foreach ($tables as $row) {
		$tbl = $row['name'];
		printf($cli->header("Converting $tbl"));
		$pdo->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=DYNAMIC");
	}
} else if (isset($argv[1]) && $argv[1] == "cinnodb") {
	$sql = 'SHOW TABLE STATUS WHERE (Engine != "InnoDB" OR Row_format != "Compressed") AND Engine != "SPHINX"';
	$tables = $pdo->query($sql);
	foreach ($tables as $row) {
		$tbl = $row['name'];
		if ($tbl !== 'release_nfos' &&
			$tbl !== 'release_search_data' &&
			$tbl !== 'bookinfo' &&
			$tbl !== 'consoleinfo' &&
			$tbl !== 'musicinfo'
		) {
			printf($cli->header("Converting $tbl"));
			$pdo->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=COMPRESSED");
		}
	}
	$sql = 'SHOW TABLE STATUS WHERE Name = "release_nfos" AND (Engine != "InnoDB" || Row_format != "Dynamic")';
	$tables = $pdo->query($sql);
	foreach ($tables as $row) {
		$tbl = $row['name'];
		printf($cli->header("Converting $tbl"));
		$pdo->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=DYNAMIC");
	}
	$sql = 'SHOW TABLE STATUS WHERE Name IN ("release_search_data", "bookinfo", "consoleinfo", "musicinfo") AND (Engine != "InnoDB" || Row_format != "Compressed")';
	$tables = $pdo->query($sql);
	foreach ($tables as $row) {
		$tbl = $row['name'];
		printf($cli->header("Converting $tbl"));
		$pdo->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=COMPRESSED");
	}
} else if (isset($argv[1]) && $argv[1] == "cinnodb-noparts") {
	$sql = 'SHOW TABLE STATUS WHERE (Engine != "InnoDB" OR Row_format != "Compressed") AND Engine != "SPHINX"';
	$tables = $pdo->query($sql);
	foreach ($tables as $row) {
		$tbl = $row['name'];
		if ($tbl !== 'release_nfos' &&
			$tbl !== 'release_search_data' &&
			$tbl !== 'bookinfo' &&
			$tbl !== 'consoleinfo' &&
			$tbl !== 'musicinfo' &&
			!preg_match('/parts/', $tbl)
		) {
			printf($cli->header("Converting $tbl"));
			$pdo->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=COMPRESSED");
		}
	}
	$sql = 'SHOW TABLE STATUS WHERE Name = "release_nfos" AND (Engine != "InnoDB" || Row_format != "Dynamic")';
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
	$sql = 'SHOW TABLE STATUS WHERE Name IN ("release_search_data", "bookinfo", "consoleinfo", "musicinfo") AND (Engine != "InnoDB" || Row_format != "Compressed")';
	$tables = $pdo->query($sql);
	foreach ($tables as $row) {
		$tbl = $row['name'];
		printf($cli->header("Converting $tbl"));
		$pdo->queryExec("ALTER TABLE $tbl ENGINE=INNODB ROW_FORMAT=COMPRESSED");
	}
} else if (isset($argv[1]) && $argv[1] == "collections") {
	$arr = ["parts", "binaries", "collections"];
	foreach ($arr as $row) {
		$tbl = $row;
		printf($cli->header("Converting $tbl"));
		$pdo->queryExec("ALTER TABLE $tbl ENGINE=MYISAM ROW_FORMAT=FIXED");
	}
} else {
	exit($cli->error(
		"\nThis script will convert your tables to a new engine/format. Only tables not meeting the new engine/format will be converted.\n"
		. "A comparison of these, https://github.com/nZEDb/nZEDb/wiki/MySQL-Storage-Engine-Comparison\n\n"
		. "php convert_mysql_tables.php dmyisam                                        ...: Converts all the tables to Myisam Dynamic. This is the default and is recommended where ram is limited.\n"
		. "php convert_mysql_tables.php fmyisam                                        ...: Converts all the tables to Myisam Fixed. This can be faster, but to fully convert all tables requires changing varchar columns to char.\n"
		. "                                                                                 This will use much more space than dynamic.\n"
		. "php convert_mysql_tables.php dinnodb                                        ...: Converts all the tables to InnoDB Dynamic. This is recommended when the total data and indexes can fit into the innodb_buffer_pool.\n"
		. "                                                                                 NB if your innodb version < 5.6 bookinfo / consoleinfo / musicinfo / release_search_data will not be converted as fulltext indexes are not supported.\n"
		. "php convert_mysql_tables.php cinnodb                                        ...: Converts all the tables to InnoDB Compressed. All tables except release_nfos will be converted to Compressed row format.\n"
		. "                                                                                 This is recommended when the total data and indexes can not fit into the innodb_buffer_pool using DYNAMIC row format.\n"
		. "                                                                                 NB if your innodb version < 5.6 bookinfo / consoleinfo / musicinfo / release_search_data will not be converted as fulltext indexes are not supported.\n"
		. "php convert_mysql_tables.php cinnodb-noparts                                ...: Converts all the tables to InnoDB Compressed. All tables except parts and release_nfos will be converted to Compressed row format.\n"
		. "                                                                                 Alls parts* will be converted to MyISAM Dynamic. This is recommended when using Table Per Group.\n"
		. "                                                                                 NB if your innodb version < 5.6 bookinfo / consoleinfo / musicinfo / release_search_data will not be converted as fulltext indexes are not supported.\n"
		. "php convert_mysql_tables.php collections                                    ...: Converts collections, binaries, parts to MyIsam.\n"
		. "php convert_mysql_tables.php table [ fmyisam, dmyisam, dinnodb, cinnodb ]   ...: Converts 1 table to Engine, row_format specified.\n"
		. "                                                                                 NB if converting to innodb and your innodb version < 5.6 release_search_data will not be converted as fulltext indexes are not supported.\n"
	));
}
