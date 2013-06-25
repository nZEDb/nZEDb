<?php
define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(WWW_DIR."lib/site.php");
require_once(WWW_DIR."lib/nzb.php");

$s = new Sites();
$site = $s->get();
$db = new DB();
$nzb = new NZB(true);

$dirItr    = new RecursiveDirectoryIterator($site->nzbpath);
//$filterItr = new MyRecursiveFilterIterator($dirItr);
$itr       = new RecursiveIteratorIterator($dirItr, RecursiveIteratorIterator::LEAVES_ONLY);
foreach ($itr as $filePath) {
	if (is_file($filePath))
	{
		$file = stristr($filePath->getFilename(), '.nzb.gz', true);

		$res = $db->query(sprintf("SELECT * FROM `releases` where guid = %s", $db->escapeString($file)));

		if ($res === false)
		{
			echo "$filePath\n";

			echo $filePath->getFilename() . PHP_EOL;
		}
    }
}


$res = $db->queryDirect('SELECT ID, guid FROM `releases`');
while ($row =  $db->fetchAssoc($res))
{
	$nzbpath = $nzb->getNZBPath($row["guid"], $site->nzbpath, false, $site->nzbsplitlevel);
	if (!file_exists($nzbpath))
	{
		echo "deleting ".$row['guid']."\n";
		$db->query(sprintf("DELETE FROM `nZEDb`.`releases` WHERE `releases`.`ID` = %s", $row['ID']));
	}

}
?>
