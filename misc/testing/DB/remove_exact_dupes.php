<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();

if ($argc < 3 || !isset($argv[1]) || (isset($argv[1]) && !is_numeric($argv[1]))) {
	exit($pdo->log->error("\nIncorrect argument suppplied. This script will delete all duplicate releases matching on name, fromname, group_id and size.\n"
		. "Unfortunately, I can not guarantee which copy will be deleted.\n\n"
		. "php remove_exact_dupes.php 10 exact             ...: To delete all duplicates added within the last 10 hours.\n"
		. "php remove_exact_dupes.php 10 near              ...: To delete all duplicates with size variation of 1% and added within the last 10 hours.\n"
		. "php remove_exact_dupes.php 0 exact              ...: To delete all duplicates.\n"
		. "php remove_exact_dupes.php 0 near               ...: To delete all duplicates with size variation of 1%.\n"
		. "php remove_exact_dupes.php 10 exact dupes/      ...: To delete all duplicates added within the last 10 hours and save a copy of the nzb to dupes folder.\n"));
}

$crosspostt = $argv[1];
$releases = new \Releases(['Settings' => $pdo]);
$count = $total = $all = 0;
$nzb = new \NZB($pdo);
$ri = new \ReleaseImage($pdo);
$consoleTools = new \ConsoleTools(['ColorCLI' => $pdo->log]);
$size = ' size ';
if ($argv[2] === 'near') {
	$size = ' size between (size *.99) AND (size * 1.01) ';
}

if ($crosspostt != 0) {
	$query = sprintf('SELECT max(id) AS id, id AS idx, guid FROM releases WHERE adddate > (NOW() - INTERVAL %d HOUR) GROUP BY name, fromname, group_id,' . $size . 'HAVING COUNT(*) > 1', $crosspostt);
} else {
	$query = sprintf('SELECT max(id) AS id, id AS idx, guid FROM releases GROUP BY name, fromname, group_id,' . $size . 'HAVING COUNT(*) > 1');
}

do {
	$resrel = $pdo->queryDirect($query);
	if ($resrel instanceof \Traversable) {
		$total = $resrel->rowCount();
		echo $pdo->log->header(number_format($total) . " Releases have Duplicates");
		foreach ($resrel as $rowrel) {
			$nzbpath = $nzb->getNZBPath($rowrel['guid']);
			if (isset($argv[3]) && is_dir($argv[3])) {
				$path = $argv[3];
				if (substr($path, strlen($path) - 1) != '/') {
					$path = $path . "/";
				}
				if (!file_exists($path . $rowrel['guid'] . ".nzb.gz") && file_exists($nzbpath)) {
					if (@copy($nzbpath, $path . $rowrel['guid'] . ".nzb.gz") !== true) {
						exit("\n" . $pdo->log->error("\nUnable to write " . $path . $rowrel['guid'] . ".nzb.gz"));
					}
				}
			}
			if ($releases->deleteSingle(['g' => $rowrel['guid'], 'i' => $rowrel['idx']], $nzb, $ri) !== false) {
				$consoleTools->overWritePrimary('Deleted: ' . number_format(++$count) . " Duplicate Releases");
			}
		}
	}
	$all += $count;
	$count = 0;
	echo "\n\n";
} while ($total > 0);
echo $pdo->log->header("\nDeleted ". number_format($all) . " Duplicate Releases");
