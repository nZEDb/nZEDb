<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\DB;

$c = new ColorCLI();
if (!isset($argv[1]) || ($argv[1] != "console")) {
	exit($c->error(
		"\nThis script tries to match release filenames to PreDB filenames.\n"
		. "To display the changes, use 'show' as the second argument.\n\n"
		. "php match_prefiles.php 1000		...: to limit to 1000 releases in all categories not previously renamed sorted by newest postdate.\n"
		. "php match_prefiles.php full 		...: to run on full database.\n"
		. "php match_prefiles.php console		...: to run on all console releases (recommended).\n"
		. "php match_prefiles.php all 		...: to run on all releases with filenames (including previously renamed).\n"
		. "\nCurrently the only available method is console.\n"
	));
}

echo $c->header("\nMatch PreFiles (${argv[1]}) Started at " . date('g:i:s'));
echo $c->primary("Matching predb filename to SUBSTRING_INDEX releasefiles.name.\n");

preName($argv);

function preName($argv)
{
	$db = new DB();
	$timestart = TIME();
	$namefixer = new NameFixer();
	$c = new ColorCLI();

	$catrange = $renamed = $orderby = $limit = '';

	if (isset($argv[1]) && $argv[1] === "full") {
		$renamed = "AND isrenamed = 0";
	} else if (isset($argv[1]) && $argv[1] === "console") {
		$catrange = "AND categoryid BETWEEN 1000 AND 1999";
	} else if (isset($argv[1]) && is_numeric($argv[1])) {
		$renamed = "AND isrenamed = 0";
		$limit = "LIMIT " . $argv[1];
		$orderby = "ORDER BY postdate DESC";
	}

	$query = $db->queryDirect(sprintf("SELECT DISTINCT r.id AS releaseid, r.searchname, r.groupid, r.categoryid, SUBSTRING_INDEX(rf.name, '.', 1) AS filename
					FROM releases r INNER JOIN releasefiles rf ON r.id = rf.releaseid
					WHERE r.preid = 0 %s AND rf.name REGEXP BINARY '[a-z0-9]{1,20}-[a-z0-9]{1,20}\..{3}'
					%s %s GROUP BY r.id %s", $catrange, $renamed, $orderby, $limit));

	$total = $query->rowCount();
	$counter = $counted = 0;
	$show = (!isset($argv[2]) || $argv[2] !== 'show') ? 0 : 1;
	if ($total > 0) {
		echo $c->header("\n" . number_format($total) . ' releases to process.');
		sleep(2);
		$consoletools = new ConsoleTools();

		foreach ($query as $row) {
			$success = 0;
			$success = $namefixer->matchPredbFiles($row, 1, 1, true, $show);

			if ($success === 1) {
				$counted++;
			}
			if ($show === 0) {
				$consoletools->overWritePrimary("Renamed Releases: [" . number_format($counted) . "] " . $consoletools->percentString(++$counter, $total));
			}
		}
	}
	if ($total > 0) {
		echo $c->header("\nRenamed " . $counted . " releases in " . $consoletools->convertTime(TIME() - $timestart) . ".");
	} else {
		echo $c->info("\nNothing to do.");
	}
}
