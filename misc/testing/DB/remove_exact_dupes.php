<?php

require_once dirname(__FILE__) . '/../../../www/config.php';

$c = new ColorCLI();
if ($argc < 3 || !isset($argv[1]) || (isset($argv[1]) && !is_numeric($argv[1]))) {
	exit($c->error("\nIncorrect argument suppplied. This script will delete all duplicate releases matching on name, fromname, groupid and size.\n"
		. "Unfortunately, I can not guarantee which copy will be deleted.\n\n"
		. "php $argv[0] 10 exact             ...: To delete all duplicates added within the last 10 hours.\n"
		. "php $argv[0] 10 near              ...: To delete all duplicates with size variation of 1% and added within the last 10 hours.\n"
		. "php $argv[0] 0 exact              ...: To delete all duplicates.\n"
		. "php $argv[0] 0 near               ...: To delete all duplicates with size variation of 1%.\n"
		. "php $argv[0] 10 exact dupes/      ...: To delete all duplicates added within the last 10 hours and save a copy of the nzb to dupes folder.\n"));
}

$crosspostt = $argv[1];
$db = new DB();
$c = new ColorCLI();
$releases = new Releases();
$count = $total = $all = 0;
$nzb = new NZB();
$ri = new ReleaseImage();
$s = new Sites();
$site = $s->get();
$consoleTools = new ConsoleTools();
$size = ' size ';
if ($argv[2] === 'near') {
	$size = ' size between (size *.99) AND (size * 1.01) ';
}

if ($crosspostt != 0) {
	if ($db->dbSystem() == 'mysql') {
		$query = sprintf('SELECT max(id) AS id, guid FROM releases WHERE adddate > (NOW() - INTERVAL %d HOUR) GROUP BY name, fromname, groupid,' . $size . 'HAVING COUNT(*) > 1', $crosspostt);
	} else {
		$query = sprintf("SELECT max(id) AS id, guid FROM releases WHERE adddate > (NOW() - INTERVAL '%d HOURS') GROUP BY name, fromname, groupid," . $size . "HAVING COUNT(name) > 1", $crosspostt);
	}
} else {
	$query = sprintf('SELECT max(id) AS id, guid FROM releases GROUP BY name, fromname, groupid,' . $size . 'HAVING COUNT(*) > 1');
}

do {
	$resrel = $db->queryDirect($query);
	$total = $resrel->rowCount();
	echo $c->header(number_format($total) . " Releases have Duplicates");
	if (count($resrel) > 0) {
		foreach ($resrel as $rowrel) {
			$nzbpath = $nzb->getNZBPath($rowrel['guid'], $site->nzbpath, false, $site->nzbsplitlevel);
			if (isset($argv[3]) && is_dir($argv[3])) {
				$path = $argv[3];
				if (substr($path, strlen($path) - 1) != '/') {
					$path = $path . "/";
				}
				if (!file_exists($path . $rowrel['guid'] . ".nzb.gz") && file_exists($nzbpath)) {
					if (@copy($nzbpath, $path . $rowrel['guid'] . ".nzb.gz") !== true) {
						exit("\n" . $c->error("\nUnable to write " . $path . $rowrel['guid'] . ".nzb.gz"));
					}
				}
			}
			if ($releases->fastDelete($rowrel['id'], $rowrel['guid'], $site) !== false) {
				$consoleTools->overWritePrimary('Deleted: ' . number_format(++$count) . " Duplicate Releases");
			}
		}
	}
	$all += $count;
	$count = 0;
	echo "\n\n";
	$consoleTools = new ConsoleTools();
} while ($total > 0);
echo $c->header("\nDeleted ". number_format($all) . " Duplicate Releases");
