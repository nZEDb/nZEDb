<?php
require(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."/lib/consoletools.php");
require_once(WWW_DIR."/lib/predb.php");
require_once(WWW_DIR."/lib/framework/db.php");

/*
 * Downloads predb titles from github and stores them in the predb table.
 */

if (isset($argv[1]) && is_numeric($argv[1]))
{
	$db = new DB;
	$predb = new Predb;
	$consoletools = new ConsoleTools;
	$predbv = $db->queryOneRow("SELECT value as v from site where setting = 'predbversion'");
	if ($predbv["v"] == 142)
		exit("You are at the maximum backfill.\n");
	else if ($argv[1] == 0 || $argv[1] > 142)
		exit("Wrong argument. It must be a number between 1 and 142.\n");
	else if ($argv[1] < $predbv["v"])
		$filenums = $predbv["v"]+$argv[1];
	else if ($argv[1] + $predbv["v"] > 142)
		$filenums = 142-$predbv["v"];
	else if ($argv[1] >= $predbv["v"])
		$filenums = $argv[1];
	
	echo "Going to download and insert preDB backfills, you are currently at backfill # ".$predbv["v"].".\n";

	$done = $total = 0;
	foreach (range($predbv["v"], $filenums) as $filenumber)
	{
		$filenump = str_pad($filenumber, 3, '0', STR_PAD_LEFT);
		$gitfile = fopen("https://github.com/nZEDb/pre-info/raw/master/datafiles/data".$filenump.".gz", "rb");
		if($gitfile)
		{
			$zippath = WWW_DIR."data".$filenump.".gz";
			$zipfile = fopen($zippath, "wb");
			if($zipfile)
			{
				while(!feof($gitfile))
				{
					fwrite($zipfile, fread($gitfile, 1024*8), 1024*8);
				}
				
				if ($gitfile)
					fclose($gitfile);
				if ($zipfile);
					fclose($zipfile);

				if (file_exists($zippath))
				{
					if($handle = gzopen($zippath, "rb"))
					{
						$file = WWW_DIR."data".$filenump.".txt";
						$txthandle = fopen($file, "w");
				
						while($string = gzread($handle, 4096))
						{
							fwrite($txthandle, $string, strlen($string));
						}
						gzclose($handle);
						fclose($txthandle);
						unlink($zippath);
						if (file_exists($file))
						{
							chmod($file, 0777);
							$insert = $db->queryDirect(sprintf("LOAD DATA INFILE %s INTO TABLE predb FIELDS TERMINATED BY ',' ENCLOSED BY '~' LINES TERMINATED BY '\n' (@adddate, title, category, size, predate) set adddate = FROM_UNIXTIME(@adddate), title = title, category = category, size = round(size), predate = predate, source = 'backfill', md5 = md5(title)", $db->escapeString($file)));
							if(!$insert)
							{
								unlink($file);
								exit("MySQL Error: ".$db->Error()."\n");
							}
							unlink($file);
							$db->query(sprintf("UPDATE site SET value = %d WHERE setting = %s", $filenumber+1, $db->escapeString("predbversion")));
							$db->query("UPDATE predb SET adddate = (now() - interval 1 day) WHERE (adddate > (now() - interval 2 hour) or adddate < (now() - interval 6 year))");
							$predb->parseTitles(2, 1, 2, 1, 1);
							$predb->matchPredb();
							$done++;
							$total = $total + 10000;
							$consoletools->overWrite("You are currently at backfill ".$filenumber.", you have done ".$done." backfills this run, for a total of ".$total." rows.");
						}
						else
							exit("ERROR: TXT file missing.\n");
					}
					else
						exit("ERROR: Unable to gzopen the gzip file.\n");
				}
				else
					exit("ERROR: gzip file is missing.\n");
			}
			else
				exit("ERROR: Unable to open the gzip file.\n");
		}
		else
			exit("ERROR: Problem contacting github.com\n");
	}
	exit("\n");
}
else
	exit("This script inserts pre info into the preDB mysql table from a dump made 5/29/2013.\nSupply an argument ex\:(php backfill_predb.php 3), 3 will backfill 30000, you can backfill up to 1.42 million (142 as an argument).\nIf you have already ran this script in the past, your status is saved, so you can go further.\nIt is a good idea to do a little at a time if you are uncertain, instead of doing all 142 in 1 go.\n\nMake sure there are no data###.zip or data###.txt in the www folder before starting.\n");
?>
