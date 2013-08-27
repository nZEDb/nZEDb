<?php
// This script removes all nzbs not found in the db.

if (isset($argv[1]) && $argv[1] === "true")
{
	define('FS_ROOT', realpath(dirname(__FILE__)));
	require_once(FS_ROOT."/../../../www/config.php");
	require_once(WWW_DIR."lib/site.php");
	require_once(WWW_DIR."lib/nzb.php");

	$s = new Sites();
	$site = $s->get();
	$db = new DB();
	$nzb = new NZB(true);
	$dirItr = new RecursiveDirectoryIterator($site->nzbpath);
	$itr = new RecursiveIteratorIterator($dirItr, RecursiveIteratorIterator::LEAVES_ONLY);
	foreach ($itr as $filePath)
	{
		if (is_file($filePath))
		{
			$file = stristr($filePath->getFilename(), '.nzb.gz', true);
			$res = $db->query(sprintf("SELECT * FROM releases WHERE guid = %s", $db->escapeString($file)));

			if ($res === false)
			{
				echo "$filePath\n";
				echo $filePath->getFilename().PHP_EOL;
			}
		}
	}

	$res = $db->query('SELECT id, guid FROM releases');
	foreach ($res as $row)
	{
		$nzbpath = $nzb->getNZBPath($row["guid"], $site->nzbpath, false, $site->nzbsplitlevel);
		if (!file_exists($nzbpath))
		{
			echo "Deleting ".$row['guid']."\n";
			$db->queryExec(sprintf("DELETE FROM releases WHERE id = %s", $row['id']));
		}

	}
}
else
	exit("This script removes all nzbs not found in the db.\nIf you are sure you want to run it, type php clean_nzbs.php true\n");
?>
