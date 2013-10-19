<?php
require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");

//	This script can dump all tables or just collections/binaries/parts/partrepair/groups.

$db = new DB();
if ($db->dbSystem() == "pgsql")
	exit("This script is only for mysql.\n");

$exportopts = "";
$mysqlplatform = "";

//determine mysql platform (oracle, percona, mariadb etc)
if($db->dbSystem() == "mysql")
{
	$mysqlplatform = mysql_get_client_info();
	if(strpos($mysqlplatform, "Percona"))
	{
		//Percona only has --innodb-optimize-keys
		$exportopts = "--opt --innodb-optimize-keys --complete-insert --skip-quick";
	}
	else 
	{
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
				."[mysql]"
				."\n"
				."user = " . DB_USER
				."\n"
				."password = " . DB_PASSWORD;
	
	$filehandle = fopen("mysql-defaults.txt", "w+");
	if(!$filehandle)
		exit("Unable to write mysql defaults file! Exiting");
	else 
	{
		fwrite($filehandle, $filetext);
		fclose($filehandle);
		chmod("mysql-defaults.txt", 0600);
	}
}

$dbhost = DB_HOST;
$dbport = DB_PORT;
$dbuser = DB_USER;
$dbpass = DB_PASSWORD;
$dbname = DB_NAME;

if($db->dbSystem() == "mysql")
	//generate defaults file used to store database login information so it is not in cleartext in ps command for mysqldump
	builddefaultsfile();

if((isset($argv[1]) && $argv[1] == "db") && (isset($argv[2]) && $argv[2] == "dump") && (isset($argv[3]) && file_exists($argv[3])))
{
	$filename = $argv[3]."/".$dbname.".gz";
	printf("Dumping $dbname\n");
	if (file_exists($filename))
		newname($filename);
	$command = "mysqldump --defaults-file=mysql-defaults.txt $exportopts -h$dbhost -P$dbport "."$dbname | gzip -9 > $filename";
	system($command);
}
elseif((isset($argv[1]) && $argv[1] == "db") && (isset($argv[2]) && $argv[2] == "restore") && (isset($argv[3]) && file_exists($argv[3])))
{
	$filename = $argv[3]."/".$dbname.".gz";
	if (file_exists($filename))
	{
		printf("Restoring $dbname\n");
		$command = "gunzip < $filename | mysql --defaults-file=mysql-defaults.txt -h$dbhost -P$dbport $dbname";
		system($command);
    }
}
elseif((isset($argv[1]) && $argv[1] == "all") && (isset($argv[2]) && $argv[2] == "dump") && (isset($argv[3]) && file_exists($argv[3])))
{
	$sql = "SHOW tables";
	$tables = $db->query($sql);
	foreach($tables as $row)
	{
		$tbl = $row['tables_in_'.DB_NAME];
		$filename = $argv[3]."/".$tbl.".gz";
		printf("Dumping $tbl\n");
		if (file_exists($filename))
			newname($filename);
		$command = "mysqldump --defaults-file=mysql-defaults.txt $exportopts -h$dbhost -P$dbport "."$dbname $tbl | gzip -9 > $filename";
		system($command);
	}
}
elseif((isset($argv[1]) && $argv[1] == "all") && (isset($argv[2]) && $argv[2] == "restore") && (isset($argv[3]) && file_exists($argv[3])))
{
	$sql = "SHOW tables";
	$tables = $db->query($sql);
	foreach($tables as $row)
	{
		$tbl = $row['tables_in_'.DB_NAME];
		$filename = $argv[3]."/".$tbl.".gz";
		if (file_exists($filename))
		{
			printf("Restoring $tbl\n");
			$command = "gunzip < $filename | mysql --defaults-file=mysql-defaults.txt -h$dbhost -P$dbport $dbname";
			system($command);
		}
	}
}
elseif((isset($argv[1]) && $argv[1] == "test") && (isset($argv[2]) && $argv[2] == "dump") && (isset($argv[3]) && file_exists($argv[3])))
{
	$arr = array("parts", "binaries", "collections", "partrepair", "groups");
	foreach ($arr as &$tbl)
	{
		$filename = $argv[3]."/".$tbl.".gz";
		printf("Dumping $tbl.\n");
		if (file_exists($filename))
			newname($filename);
		$command = "mysqldump --defaults-file=mysql-defaults.txt $exportopts -h$dbhost -P$dbport "."$dbname $tbl | gzip -9 > $filename";
		system($command);
	}
}
elseif((isset($argv[1]) && $argv[1] == "test") && (isset($argv[2]) && $argv[2] == "restore") && (isset($argv[3]) && file_exists($argv[3])))
{
	$arr = array("parts", "binaries", "collections", "partrepair", "groups");
	foreach ($arr as &$tbl)
	{
		$filename = $argv[3]."/".$tbl.".gz";
		if (file_exists($filename))
		{
			printf("Restoring $tbl\n");
			$command = "gunzip < $filename | mysql --defaults-file=mysql-defaults.txt -h$dbhost -P$dbport $dbname";
			system($command);
		}
	}
}
elseif((isset($argv[1]) && $argv[1] == "all") && (isset($argv[2]) && $argv[2] == "outfile") && (isset($argv[3]) && file_exists($argv[3])))
{
	$sql = "SHOW tables";
	$tables = $db->query($sql);
	foreach($tables as $row)
	{
		$tbl = $row['tables_in_'.DB_NAME];
		$filename = $argv[3].$tbl.".csv";
		printf("Dumping $tbl\n");
		if (file_exists($filename))
			newname($filename);
		$db->queryDirect(sprintf("SELECT * INTO OUTFILE %s FROM %s", $db->escapeString($filename), $tbl));
	}
}
elseif((isset($argv[1]) && $argv[1] == "all") && (isset($argv[2]) && $argv[2] == "infile") && (isset($argv[3]) && is_dir($argv[3])))
{
	$sql = "SHOW tables";
	$tables = $db->query($sql);
	foreach($tables as $row)
	{
		$tbl = $row['tables_in_'.DB_NAME];
		$filename = $argv[3].$tbl.".csv";
		if (file_exists($filename))
		{
			printf("Restoring $tbl\n");
			$db->queryExec(sprintf("LOAD DATA INFILE %s INTO TABLE %s", $db->escapeString($filename), $tbl));
		}
	}
}
elseif((isset($argv[1]) && $argv[1] == "predb") && (isset($argv[2]) && $argv[2] == "outfile") && (isset($argv[3]) && file_exists($argv[3])))
{
	$tables = array('predb');
	foreach($tables as $row)
	{
		$tbl = $row['tables_in_'.DB_NAME];
		$filename = $argv[3]."/".$tbl."_clean.sql";
		printf("Dumping $tbl\n");
		if (file_exists($filename))
			newname($filename);
		$db->query(sprintf("SELECT title, nfo, size, category, predate, adddate, source, md5 INTO OUTFILE %s FROM %s", $db->escapeString($filename), $tbl));
    }
}
else
{
	passthru("clear");
	echo "\033[1;33mThis script can dump/restore all tables, compressed or OUTFILE/INFILE, or just collections/binaries/parts.\n\n"
	."**Single File\n"
	."To dump the database run: php mysqldump_tables.php db dump /path/to/save/to\n"
	."To restore the database run: php mysqldump_tables.php db restore /path/where/saved\n\n"
	."**Individual Table Files\n"
	."To dump all tables run: php mysqldump_tables.php all dump /path/to/save/to\n"
	."To restore all tables run: php mysqldump_tables.php all restore /path/where/saved\n\n"
	."**Three Tables (collections, binaries, parts)\n"
	."To dump collections, binaries, parts tables run: php mysqldump_tables.php test dump /path/to/save/to\n"
	."To restore collections, binaries, parts tables run: php mysqldump_tables.php test restore /path/where/saved\n\n"
	."**Individal Files - OUTFILE/INFILE - No schema\n"
	."To dump all tables, using OUTFILE run: php mysqldump_tables.php all outfile /path/to/save/to\n"
	."To restore all tables, using INFILE run: php mysqldump_tables.php all infile /path/where/saved\n\n\033[0m"
	."To dump the predb table, clean, using OUTFILE run: php mysqldump_tables.php predb outfile /path/to/save/to\n";
}

if(file_exists("mysql-defaults.txt"))
	unlink("mysql-defaults.txt");

?>
