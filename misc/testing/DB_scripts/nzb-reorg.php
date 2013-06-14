<?php

require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/site.php");
require_once(WWW_DIR."lib/nzb.php");
require_once(WWW_DIR."lib/consoletools.php");

$n = "\n";
$db = new DB();

if (!isset($argv[1]) || !isset($argv[2]))
	exit("ERROR: You must supply the level you want to reorganize it to and the source directory  (You would use: 3 /var/www/nZEDb/nzbfiles/ to move it to 3 levels deep)".$n);

$s = new Sites();
$nzb = new NZB();

$site = $s->get();
$sitenzbpath = $site->nzbpath;
$newLevel = $argv[1];
$sourcePath = $argv[2];

$filestoprocess = Array();
$iFilesProcessed = 0;
$iFilesCounted = 0;
$time = TIME();

$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourcePath));
$consoleTools = new ConsoleTools();
foreach($objects as $filestoprocess => $nzbFile)
{
    if($nzbFile->getExtension() != "gz")
        continue;
	$consoleTools->overWrite("Getting filecount: ".$iFilesCounted++." nzbs  ");
}
$time1 = TIME();

echo $n."Reorganizing files to Level " . $newLevel . " from: " . $sourcePath. " This could take a while... ".$n;
$consoleTools = new ConsoleTools();
foreach($objects as $filestoprocess => $nzbFile)
{
	if($nzbFile->getExtension() != "gz")
		continue;

	$fileGuid = str_replace(".nzb.gz", "", $nzbFile->getBasename());

	$newFileName = $nzb->getNZBPath($fileGuid, $sitenzbpath, true, $newLevel);
	if ($newFileName != $nzbFile)
	{
		//echo $newFileName . $n;
		rename($nzbFile, $newFileName);
		chmod($newFileName, 0777); // change the chmod to fix issues some users have with file permissions
	}
	$iFilesProcessed++;
	$est = (int)((TIME() -$time1)/$iFilesProcessed*($iFilesCounted-$iFilesProcessed)/60);
	$perc = (int)($iFilesProcessed/$iFilesCounted*100);
	$consoleTools->overWrite("Reorganized ".$iFilesProcessed."/".$iFilesCounted."(".$perc."%) - est. ".$est." minutes  ");
}

$db->query(sprintf("update site set value = %s where setting = 'nzbsplitlevel'", $argv[1]));
echo "Processed ".$iFilesProcessed." nzbs in ".relativeTime($time).$n;
die();


function relativeTime($_time) {
	$d[0] = array(1,"sec");
	$d[1] = array(60,"min");
	$d[2] = array(3600,"hr");
	$d[3] = array(86400,"day");
	$d[4] = array(31104000,"yr");

	$w = array();

	$return = "";
	$now = TIME();
	$diff = ($now-$_time);
	$secondsLeft = $diff;

	for($i=4;$i>-1;$i--)
	{
		$w[$i] = intval($secondsLeft/$d[$i][0]);
		$secondsLeft -= ($w[$i]*$d[$i][0]);
		if($w[$i]!=0)
		{
			//$return.= abs($w[$i]). " " . $d[$i][1] . (($w[$i]>1)?'s':'') ." ";
			$return.= $w[$i]. " " . $d[$i][1] . (($w[$i]>1)?'s':'') ." ";
		}
	}

	//$return .= ($diff>0)?"ago":"left";
	return $return;
}

?>
