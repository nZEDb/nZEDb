<?php
require_once dirname(__FILE__) . '/../../../www/config.php';


//	This script can dump all tables or just collections/binaries/parts/partrepair/groups.

$db = new DB();
$c = new ColorCLI();
if ($db->dbSystem() == "pgsql") {
	exit($c->error("\nThis script is only for mysql.\n"));
}

$exportopts = "";
$mysqlplatform = "";

//determine mysql platform Percona or Other
if($db->dbSystem() == "mysql") {
	$mysqlplatform = exec('mysqladmin version | grep "Percona"', $mysqlplatform);
	if (count($mysqlplatform) > 0) {
		//Percona only has --innodb-optimize-keys
		$exportopts = "--opt --innodb-optimize-keys --complete-insert --skip-quick";
	} else {
		//generic (or unknown) instance of MySQL
		$exportopts = "--opt --complete-insert --skip-quick";
	}
}

function newname($filename)
{
	rename($filename, dirname($filename)."/".basename($filename,".gz")."_".date("Y_m_d_His", filemtime($filename)).".gz");
}

function builddefaultsfile()
{
	//generate file contents
	$filetext = "[mysqldump]"
				."\n"
				."user = " . DB_USER
				."\n"
				."password = " . DB_PASSWORD
				."\n[mysql]"
				."\n"
				."user = " . DB_USER
				."\n"
				."password = " . DB_PASSWORD;

	$filehandle = fopen("mysql-defaults.txt", "w+");
	if(!$filehandle) {
		exit("Unable to write mysql defaults file! Exiting");
	} else {
		fwrite($filehandle, $filetext);
		fclose($filehandle);
		chmod("mysql-defaults.txt", 0600);
	}
}

$dbhost = DB_HOST;
$dbport = DB_PORT;
$dbsocket = DB_SOCKET;
$dbuser = DB_USER;
$dbpass = DB_PASSWORD;
$dbname = DB_NAME;

if (DB_SOCKET != '') {
	$use = "-S $dbsocket";
} else {
	$use = "-P$dbport";
}

if($db->dbSystem() == "mysql") {
	//generate defaults file used to store database login information so it is not in cleartext in ps command for mysqldump
	builddefaultsfile();
}

if((isset($argv[1]) && $argv[1] == "db") && (isset($argv[2]) && $argv[2] == "dump") && (isset($argv[3]) && file_exists($argv[3]))) {
	$filename = $argv[3]."/".$dbname.".gz";
	echo $c->header("Dumping $dbname.");
	if (file_exists($filename)) {
		newname($filename);
	}
	$command = "mysqldump --defaults-file=mysql-defaults.txt $exportopts -h$dbhost $use "."$dbname | gzip -9 > $filename";
	system($command);
} else if((isset($argv[1]) && $argv[1] == "db") && (isset($argv[2]) && $argv[2] == "restore") && (isset($argv[3]) && file_exists($argv[3]))) {
	$filename = $argv[3]."/".$dbname.".gz";
	if (file_exists($filename)) {
		echo $c->header("Restoring $dbname.");
		$command = "zcat < $filename | mysql --defaults-file=mysql-defaults.txt -h$dbhost $use $dbname";
		$db->queryExec("SET FOREIGN_KEY_CHECKS=0");
		system($command);
		$db->queryExec("SET FOREIGN_KEY_CHECKS=1");
	}
} else if((isset($argv[1]) && $argv[1] == "all") && (isset($argv[2]) && $argv[2] == "dump") && (isset($argv[3]) && file_exists($argv[3]))) {
	$sql = "SHOW tables";
	$tables = $db->query($sql);
	foreach($tables as $row) {
		$tbl = $row['tables_in_'.DB_NAME];
		$filename = $argv[3]."/".$tbl.".gz";
		echo $c->header("Dumping $tbl.");
		if (file_exists($filename)) {
			newname($filename);
		}
		$command = "mysqldump --defaults-file=mysql-defaults.txt $exportopts -h$dbhost $use "."$dbname $tbl | gzip -9 > $filename";
		system($command);
	}
} else if((isset($argv[1]) && $argv[1] == "all") && (isset($argv[2]) && $argv[2] == "restore") && (isset($argv[3]) && file_exists($argv[3]))) {
	$sql = "SHOW tables";
	$tables = $db->query($sql);
	$db->queryExec("SET FOREIGN_KEY_CHECKS=0");
	foreach($tables as $row) {
		$tbl = $row['tables_in_'.DB_NAME];
		$filename = $argv[3]."/".$tbl.".gz";
		if (file_exists($filename)) {
			echo $c->header("Restoring $tbl.");
			$command = "zcat < $filename | mysql --defaults-file=mysql-defaults.txt -h$dbhost $use $dbname";
			system($command);
		}
	}
	$db->queryExec("SET FOREIGN_KEY_CHECKS=1");
} else if((isset($argv[1]) && $argv[1] == "test") && (isset($argv[2]) && $argv[2] == "dump") && (isset($argv[3]) && file_exists($argv[3]))) {
	$arr = array("parts", "binaries", "collections", "partrepair", "groups");
	foreach ($arr as &$tbl) {
		$filename = $argv[3]."/".$tbl.".gz";
		echo $c->header("Dumping $tbl..");
		if (file_exists($filename)) {
			newname($filename);
		}
		$command = "mysqldump --defaults-file=mysql-defaults.txt $exportopts -h$dbhost $use "."$dbname $tbl | gzip -9 > $filename";
		system($command);
	}
} else if((isset($argv[1]) && $argv[1] == "test") && (isset($argv[2]) && $argv[2] == "restore") && (isset($argv[3]) && file_exists($argv[3]))) {
	$arr = array("parts", "binaries", "collections", "partrepair", "groups");
	$db->queryExec("SET FOREIGN_KEY_CHECKS=0");
	foreach ($arr as &$tbl) {
		$filename = $argv[3]."/".$tbl.".gz";
		if (file_exists($filename)) {
			echo $c->header("Restoring $tbl.");
			$command = "zcat < $filename | mysql --defaults-file=mysql-defaults.txt -h$dbhost $use $dbname";
			system($command);
		}
	}
	$db->queryExec("SET FOREIGN_KEY_CHECKS=1");
} else if((isset($argv[1]) && $argv[1] == "all") && (isset($argv[2]) && $argv[2] == "outfile") && (isset($argv[3]) && file_exists($argv[3]))) {
	$sql = "SHOW tables";
	$tables = $db->query($sql);
	foreach($tables as $row) {
		$tbl = $row['tables_in_'.DB_NAME];
		$filename = $argv[3].$tbl.".csv";
		echo $c->header("Dumping $tbl.");
		if (file_exists($filename)) {
			newname($filename);
		}
		$db->queryDirect(sprintf("SELECT * INTO OUTFILE %s FROM %s", $db->escapeString($filename), $tbl));
	}
} else if((isset($argv[1]) && $argv[1] == "all") && (isset($argv[2]) && $argv[2] == "infile") && (isset($argv[3]) && is_dir($argv[3]))) {
	$sql = "SHOW tables";
	$tables = $db->query($sql);
	$db->queryExec("SET FOREIGN_KEY_CHECKS=0");
	foreach($tables as $row) {
		$tbl = $row['tables_in_'.DB_NAME];
		$filename = $argv[3].$tbl.".csv";
		if (file_exists($filename)) {
			echo $c->header("Restoring $tbl.");
			$db->queryExec(sprintf("LOAD DATA INFILE %s INTO TABLE %s", $db->escapeString($filename), $tbl));
		}
	}
	$db->queryExec("SET FOREIGN_KEY_CHECKS=1");
} else {
	passthru("clear");
	echo $c->error("\nThis script can dump/restore all tables, compressed or OUTFILE/INFILE, or just collections/binaries/parts.\n\n"
	. "**Single File\n"
	. "php $argv[0] db dump /path/to/save/to              ...: To dump the database.\n"
	. "php $argv[0] db restore /path/to/restore/from      ...: To restore the database.\n\n"
	. "**Individual Table Files\n"
	. "php $argv[0] all dump /path/to/save/to             ...: To dump all tables.\n"
	. "php $argv[0] all restore /path/to/restore/from     ...: To restore all tables.\n\n"
	. "**Three Tables (collections, binaries, parts)\n"
	. "php $argv[0] test dump /path/to/save/to            ...: To dump collections, binaries, parts tables.\n"
	. "php $argv[0] test restore /path/to/restore/from    ...: To restore collections, binaries, parts tables.\n\n"
	. "**Individal Files - OUTFILE/INFILE - No schema\n"
	. "**MySQL MUST have write permissions to this path\n"
	. "php $argv[0] all outfile /path/to/save/to          ...: To dump all tables, using OUTFILE.\n"
	. "php $argv[0] all infile /path/to/restore/from      ...: To restore all tables, using INFILE.\n\n");
}

if(file_exists("mysql-defaults.txt")) {
	@unlink("mysql-defaults.txt");
}
