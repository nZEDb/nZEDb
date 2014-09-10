<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

if (!isset($argv[1]) || !isset($argv[2]))
	exit("ERROR: You must supply the level you want to reorganize it to, and the source directory  (You would use: 3 .../nZEDb/resources/nzb/ to move it to 3 levels deep)\n");

$pdo = new Settings();
$nzb = new \NZB($pdo);
$consoleTools = new \ConsoleTools();

$newLevel = $argv[1];
$sourcePath = $argv[2];
$objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($sourcePath));

$filestoprocess = Array();
$iFilesProcessed = $iFilesCounted = 0;
$time = TIME();

echo "\nReorganizing files to Level $newLevel from: $sourcePath This could take a while...\n";
//$consoleTools = new \ConsoleTools();
foreach ($objects as $filestoprocess => $nzbFile) {
	if ($nzbFile->getExtension() != "gz") {
		continue;
	}

	$newFileName = $nzb->getNZBPath(str_replace(".nzb.gz", "", $nzbFile->getBasename()),
									$newLevel,
									true);
	if ($newFileName != $nzbFile) {
		rename($nzbFile, $newFileName);
		chmod($newFileName, 0777);
	}
	$iFilesProcessed++;
	if ($iFilesProcessed % 100 == 0) {
		$consoleTools->overWrite("Reorganized $iFilesProcessed");
	}
}

$pdo->ping(true);
$pdo->queryExec(sprintf("UPDATE settings SET value = %s WHERE setting = 'nzbsplitlevel'", $argv[1]));
$consoleTools->overWrite("Processed $iFilesProcessed nzbs in " . relativeTime($time) . "\n");

function relativeTime ($_time)
{
	$d = array();
	$d[0] = array(1, "sec");
	$d[1] = array(60, "min");
	$d[2] = array(3600, "hr");
	$d[3] = array(86400, "day");
	$d[4] = array(31104000, "yr");

	$w = array();

	$return      = "";
	$now         = TIME();
	$diff        = ($now - $_time);
	$secondsLeft = $diff;

	for ($i = 4; $i > -1; $i--) {
		$w[$i] = intval($secondsLeft / $d[$i][0]);
		$secondsLeft -= ($w[$i] * $d[$i][0]);
		if ($w[$i] != 0)
			$return .= $w[$i] . " " . $d[$i][1] . (($w[$i] > 1) ? 's' : '') . " ";
	}
	return $return;
}

?>
