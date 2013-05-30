<?php
require(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."/lib/framework/db.php");

/*
 * Downloads predb titles from github and stores them in the predb table.
 */

if (!isset($argv[1]) && !is_numeric($argv[1]))
	exit("This script inserts pre info into the preDB mysql table from a dump made 5/29/2013.\nSupply an argument ex:(php backfill_predb.php 3), 3 will backfill 30000, you can backfill up to 1.42 million (142).\nIf you have already ran this script in the past, your status is saved, so you can go further.\n\nMake sure there are no data###.zip or data###.txt in the www folder before starting.\n");

$db = new DB;
$predbv = $db->queryOneRow("SELECT value as v from site where setting = 'predbversion'");
if ($argv[1] < $predbv["v"])
	exit("You have already reached file ".$predbv["v"]." please select a higher file.\n");
else if ($argv[1] >= $predbv["v"])
	$filenums = $argv[1];
else if ($argv[1] == 0 || $argv[1] > 142)
	exit("Wrong argument. It must be a number between 1 and 142.\n");

$done = 0;
foreach (range($filenums, 142) as $filenumber)
{
	$filenump = str_pad($filenumber, 3, '0', STR_PAD_LEFT);
	$zipfile = WWW_DIR."data".$filenump.".gz";
	if (!file_exists($zipfile))
	{	$ziphandle = fopen("https://github.com/nZEDb/pre-info/raw/master/data".$filenump.".gz", 'r');
		@file_put_contents($zipfile, $ziphandle);
		fclose($ziphandle);
	}

	if (file_exists($zipfile))
	{
		if($handle = gzopen($zipfile, "r"))
		{
			$contents = gzread($handle, filesize($zipfile));
			$file = WWW_DIR."data".$filenump.".txt";
			if (!file_exists($file))
			{
				$txthandle = fopen($zipfile, 'r');
				@file_put_contents($file, $contents);
				fclose($txthandle);
				gzclose($file);
				unlink($zipfile);
			}

			if (file_exists($file))
			{
				$db->query("LOAD DATA INFILE ".'"'.$file.'"'." INTO TABLE predb FIELDS TERMINATED BY ',' ENCLOSED BY '".'"'."' LINES TERMINATED BY '\n' (@adddate, title, category, size, predate) set adddate = FROM_UNIXTIME(@adddate), title = title, category = category, size = round(size), predate = predate, source = 'backfill', md5 = md5(title)");
				unlink($file);
				$db->query(sprintf("UPDATE site SET value = %d WHERE setting = predbversion", $filenumber));
			}
		}
		$done++;
		echo "We are currently at backfill ".$filenumber.", we have done ".$done." backfills so far.\n";
	}
}

?>
