<?php
// This script removes all NZB's not found in the DB and all releases with no NZB.

if (isset($argv[1]) && $argv[1] === "true")
{
	define('FS_ROOT', realpath(dirname(__FILE__)));
	require_once(FS_ROOT."/../../../www/config.php");
	require_once(WWW_DIR."lib/site.php");
	require_once(WWW_DIR."lib/nzb.php");
	require_once(WWW_DIR."lib/releases.php");
	require_once(WWW_DIR."lib/consoletools.php");

	$s = new Sites();
	$site = $s->get();
	$db = new DB();
	$releases = new Releases();
	$consoletools = new ConsoleTools();
	$nzb = new NZB(true);
	$dirItr = new RecursiveDirectoryIterator($site->nzbpath);
	$itr = new RecursiveIteratorIterator($dirItr, RecursiveIteratorIterator::LEAVES_ONLY);

	$timestart = TIME();
	$checked = 0;
	foreach ($itr as $filePath)
	{
		if (substr($filePath, -2) == "gz")
		{
			if (is_file($filePath))
			{
				$res = $db->queryOneRow(sprintf("SELECT id, guid FROM releases WHERE guid = %s", $db->escapeString(stristr($filePath->getFilename(), '.nzb.gz', true))));
				if ($res === false)
				{
					$releases->fastDelete($res['id'], $res['guid'], $site);
					echo "Deleted NZB: ".$filePath."\n";
				}
			}
			$time = $consoletools->convertTime(TIME() - $timestart);
			$consoletools->overWrite("Checking NZBs: ".$checked++." exists in db,  Running time: ".$time);
		}
	}

	if ($checked > 0)
		echo "\n";

	$timestart = TIME();
	$checked = 0;
	$res = $db->query('SELECT id, guid FROM releases');
	if (count($res) > 0)
	{
		foreach ($res as $row)
		{
			$nzbpath = $nzb->getNZBPath($row["guid"], $site->nzbpath, false, $site->nzbsplitlevel);
			if (!file_exists($nzbpath))
			{
				echo "Deleting ".$row['guid']."\n";
				$releases->fastDelete($row['id'], $row['guid'], $site); 
			}
			$time = $consoletools->convertTime(TIME() - $timestart);
			$consoletools->overWrite("Checking Releases: ".$checked++." have an nzb, Running time: ".$time);
		}
	}
	echo "\n";
}
else
	exit("This script removes all nzbs not found in the db.\nIf you are sure you want to run it, type php clean_nzbs.php true\n");
?>
