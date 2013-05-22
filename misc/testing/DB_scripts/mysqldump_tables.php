<?php
require(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."/lib/framework/db.php");

//
//	This script can dump all table or just collections/binaries/parts/partrepair.
//

function newname( $filename )
{
    $getdate = gmDate("Ymd");
	$path = dirname($filename);
	$file = basename($filename,".sql");
	$stamp = date ("YmdHis", filemtime($filename));
	rename($filename,$path."/".$file."_".$stamp.".sql");
}

if((isset($argv[1]) && $argv[1] == "all") && (isset($argv[2]) && $argv[2] == "backup") && (isset($argv[3]) && file_exists($argv[3])))
{
	$sql = "SHOW tables";
	$db = new DB();
	$tables = $db->query($sql);
	foreach($tables as $row)
	{
		$tbl = $row['Tables_in_'.DB_NAME];
		$filename = $argv[3]."/".$tbl.".sql";
		printf("Dumping $tbl\n");
		if (file_exists($filename))
			newname($filename);
		$db->query(sprintf("SELECT * INTO OUTFILE '%s' FROM `%s`", $filename, $tbl));
	}
}
elseif((isset($argv[1]) && $argv[1] == "all") && (isset($argv[2]) && $argv[2] == "restore") && (isset($argv[3]) && file_exists($argv[3])))
{
	$sql = "SHOW tables";
	$db = new DB();
	$tables = $db->query($sql);
	foreach($tables as $row)
	{
		$tbl = $row['Tables_in_'.DB_NAME];
		$filename = $argv[3]."/".$tbl.".sql";
		if (file_exists($filename))
		{
			printf("Restoring $tbl\n");
			$db->query(sprintf("LOAD DATA INFILE '%s' INTO TABLE `%s`", $filename, $tbl));
		}
	}
}
elseif((isset($argv[1]) && $argv[1] == "test") && (isset($argv[2]) && $argv[2] == "backup") && (isset($argv[3]) && file_exists($argv[3])))
{
	$db = new DB;
	$arr = array("parts", "binaries", "collections", "partrepair", "groups");
	foreach ($arr as &$tbl)
	{
		$filename = $argv[3]."/".$tbl.".sql";
		printf("Dumping $tbl.\n");
		if (file_exists($filename))
			newname($filename);
		$db->query(sprintf("SELECT * INTO OUTFILE '%s' FROM `%s`", $filename, $tbl));
	}
}
elseif((isset($argv[1]) && $argv[1] == "test") && (isset($argv[2]) && $argv[2] == "restore") && (isset($argv[3]) && file_exists($argv[3])))
{
	$db = new DB;
	$arr = array("parts", "binaries", "collections", "partrepair", "groups"");
	foreach ($arr as &$tbl)
	{
		$filename = $argv[3]."/".$tbl.".sql";
		if (file_exists($filename))
		{
			printf("Restoring $tbl\n");
			$db->query(sprintf("LOAD DATA INFILE '%s' INTO TABLE `%s`", $filename, $tbl));
		}
	}
}
else
{
	passthru("clear");
	echo "\033[1;33mThis script can dump/restore all tables or just collections/binaries/parts.\n";
	echo "To backup all tables run: php mysqldump_tables.php all backup /path/to/save/to\n";
	echo "To restore all tables run: php mysqldump_tables.php all restore /path/where/saved\n";
	echo "To backup collections, binaries, parts tables run: php mysqldump_tables.php test backup /path/to/save/to\n";
	echo "To restore collections, binaries, parts tables run: php mysqldump_tables.php test restore /path/where/saved\n\n\033[0m";
	exit(1);
}
?>
