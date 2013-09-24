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
	$timestart = TIME();
	$checked = $deleted = 0;
	echo "Getting List of nzbs to check against db.\n";
	$dirItr = new RecursiveDirectoryIterator($site->nzbpath);
	$itr = new RecursiveIteratorIterator($dirItr, RecursiveIteratorIterator::LEAVES_ONLY);
	foreach ($itr as $filePath)
	{
		if (is_file($filePath))
		{
			if (preg_match('/([a-f0-9]+)\.nzb/', $filePath, $guid))
			{
				$res = $db->queryOneRow(sprintf("SELECT id, guid FROM releases WHERE guid = %s", $db->escapeString(stristr($filePath->getFilename(), '.nzb.gz', true))));
				if ($res === false)
				{
					$releases->fastDelete("NULL", $guid[1], $site);
					//echo "\nDeleted NZB: ".$filePath."\n";
					$deleted++;
				}
				$time = $consoletools->convertTime(TIME() - $timestart);
				$consoletools->overWrite("Checking NZBs: ".$deleted." of ".++$checked." deleted from disk,  Running time: ".$time);
			}
		}
	}
	echo number_format(++$checked)." nzbs checked, ".number_format($deleted)." nzbs deleted.\n";

	$timestart = TIME();
	$checked = $deleted = 0;
	echo "\nGetting List of releases to check against nzbs.\n";
	$res = $db->query('SELECT id, guid FROM releases');
	if (count($res) > 0)
	{
		$consoletools = new ConsoleTools();
		foreach ($res as $row)
		{
			$nzbpath = $nzb->getNZBPath($row["guid"], $site->nzbpath, false, $site->nzbsplitlevel);
			if (!file_exists($nzbpath))
			{
				//echo "Deleting ".$row['guid']."\n";
				$releases->fastDelete($row['id'], $row['guid'], $site);
				$deleted++;
			}
			$time = $consoletools->convertTime(TIME() - $timestart);
			$consoletools->overWrite("Checking Releases: ".$deleted." of ".++$checked." deleted from db,  Running time: ".$time);
		}
	}
	echo number_format($checked)." releases checked, ".number_format($deleted)." releases deleted.\n";
}
else
	exit("This script removes all nzbs not found in the db.\nIf you are sure you want to run it, type php clean_nzbs.php true\n");
?>
