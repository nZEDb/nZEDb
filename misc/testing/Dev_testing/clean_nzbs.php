<?php
define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(WWW_DIR."lib/site.php");
require_once(WWW_DIR."lib/nzb.php");
require_once(WWW_DIR."lib/releases.php");
require_once(WWW_DIR."lib/consoletools.php");

$s = new Sites();
$site = $s->get();
$db = new DB();
$nzb = new NZB(true);
$releases = new Releases();
$consoletools = new ConsoleTools();

//
//  This script removes all nzbs not found in the db and all releases with no nzb
//

if (isset($argv[1]) && $argv[1] === "true")
{
	$checked = 0;
	$timestart = TIME();
	echo "Verifying each nzb is present in the db, this could take a long time.\n";
	$dirItr    = new RecursiveDirectoryIterator($site->nzbpath);
	$itr       = new RecursiveIteratorIterator($dirItr, RecursiveIteratorIterator::LEAVES_ONLY);
	foreach ($itr as $filePath)
	{
		if (is_file($filePath))
		{
			$file = stristr($filePath->getFilename(), '.nzb.gz', true);
			$res = $db->query(sprintf("SELECT id, guid FROM `releases` where guid = %s", $db->escapeString($file)));
			if ($res === false)
			{
				$releases->fastDelete($res['id'], $res['guid'], $site);
				echo "Deleted NZB: ".$filePath."\n";
			}
			$time = $consoletools->convertTime(TIME() - $timestart);
			$consoletools->overWrite("\nChecking NZBs: ".$checked++." exists in db,  Running time: ".$time);
		}
	}
	$checked = 0;
	$timestart = TIME();
	echo "\nVerifying each release has an nzb, this could take a long time.\n";
	$res = $db->queryDirect("SELECT id, guid FROM releases");
	while ($row =  $db->fetchAssoc($res))
	{
		$nzbpath = $nzb->getNZBPath($row["guid"], $site->nzbpath, false, $site->nzbsplitlevel);
		if (!file_exists($nzbpath))
		{
			$releases->fastDelete($row['id'], $row['guid'], $site);
			echo "Deleted ReleaseID ".$row['id']."\n";
		}
		$time = $consoletools->convertTime(TIME() - $timestart);
		$consoletools->overWrite("Checking Releases: ".$checked++." have an nzb, Running time: ".$time);
	}
}
else
{
	exit("This script removes all nzbs not found in the db and all releases with no nzb.\nIf you are sure you want to run it, type php clean_nzbs.php true\n");
}
?>
