<?php
require(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."/lib/framework/db.php");

//
//	This script can dump all tables or just collections/binaries/parts/partrepair/groups.
//

function newname( $filename )
{
    $getdate = gmDate("Ymd");
	$path = dirname($filename);
	$file = basename($filename,".gz");
	$stamp = date ("Y_m_d_His", filemtime($filename));
	rename($filename,$path."/".$file."_".$stamp.".gz");
}

$dbhost = DB_HOST;
$dbport = DB_PORT;
$dbuser = DB_USER;
$dbpass = DB_PASSWORD;
$dbname = DB_NAME;

if((isset($argv[1]) && $argv[1] == "db") && (isset($argv[2]) && $argv[2] == "dump") && (isset($argv[3]) && file_exists($argv[3])))
{
	$filename = $argv[3]."/".$dbname.".gz";
	printf("Dumping $dbname\n");
	if (file_exists($filename))
		newname($filename);
	$command = "mysqldump --opt --complete-insert --skip-quick -h$dbhost -P$dbport -u$dbuser -p$dbpass "."$dbname | gzip -9 > $filename";
	system($command);
}
elseif((isset($argv[1]) && $argv[1] == "db") && (isset($argv[2]) && $argv[2] == "restore") && (isset($argv[3]) && file_exists($argv[3])))
{
	$filename = $argv[3]."/".$dbname.".gz";
	if (file_exists($filename))
	{
		printf("Restoring $dbname\n");
		$command = "gunzip < $filename | mysql -h$dbhost -P$dbport -u$dbuser -p$dbpass $dbname";
		system($command);
    }
}
elseif((isset($argv[1]) && $argv[1] == "all") && (isset($argv[2]) && $argv[2] == "dump") && (isset($argv[3]) && file_exists($argv[3])))
{
	$sql = "SHOW tables";
	$db = new DB();
	$tables = $db->query($sql);
	foreach($tables as $row)
	{
		$tbl = $row['Tables_in_'.DB_NAME];
		$filename = $argv[3]."/".$tbl.".gz";
		printf("Dumping $tbl\n");
		if (file_exists($filename))
			newname($filename);
		$command = "mysqldump --opt --complete-insert --skip-quick -h$dbhost -P$dbport -u$dbuser -p$dbpass "."$dbname $tbl | gzip -9 > $filename";
		system($command);
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
		$filename = $argv[3]."/".$tbl.".gz";
		if (file_exists($filename))
		{
			printf("Restoring $tbl\n");
			$command = "gunzip < $filename | mysql -h$dbhost -P$dbport -u$dbuser -p$dbpass $dbname";
			system($command);
		}
	}
}
elseif((isset($argv[1]) && $argv[1] == "test") && (isset($argv[2]) && $argv[2] == "dump") && (isset($argv[3]) && file_exists($argv[3])))
{
	$db = new DB;
	$arr = array("parts", "binaries", "collections", "partrepair", "groups");
	foreach ($arr as &$tbl)
	{
		$filename = $argv[3]."/".$tbl.".gz";
		printf("Dumping $tbl.\n");
		if (file_exists($filename))
			newname($filename);
		$command = "mysqldump --opt --complete-insert --skip-quick -h$dbhost -P$dbport -u$dbuser -p$dbpass "."$dbname $tbl | gzip -9 > $filename";
		system($command);
	}
}
elseif((isset($argv[1]) && $argv[1] == "test") && (isset($argv[2]) && $argv[2] == "restore") && (isset($argv[3]) && file_exists($argv[3])))
{
	$db = new DB;
	$arr = array("parts", "binaries", "collections", "partrepair", "groups");
	foreach ($arr as &$tbl)
	{
		$filename = $argv[3]."/".$tbl.".gz";
		if (file_exists($filename))
		{
			printf("Restoring $tbl\n");
			$command = "gunzip < $filename | mysql -h$dbhost -P$dbport -u$dbuser -p$dbpass $dbname";
			system($command);
		}
	}
}
else
{
	passthru("clear");
	echo "\033[1;33mThis script can dump/restore all tables or just collections/binaries/parts.\n";
	echo "To dump the database run: php mysqldump_tables.php db dump /path/to/save/to\n";
	echo "To restore the database run: php mysqldump_tables.php db restore /path/where/saved\n";
	echo "To dump all tables run: php mysqldump_tables.php all dump /path/to/save/to\n";
	echo "To restore all tables run: php mysqldump_tables.php all restore /path/where/saved\n";
	echo "To dump collections, binaries, parts tables run: php mysqldump_tables.php test dump /path/to/save/to\n";
	echo "To restore collections, binaries, parts tables run: php mysqldump_tables.php test restore /path/where/saved\n\n\033[0m";
	exit(1);
}
?>
