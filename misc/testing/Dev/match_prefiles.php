<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;
use nzedb\utility\Utility;

$c = new ColorCLI();

$array = array("full", "expanded", "console", "misc", "xxx");

if (!in_array($argv[1], $array) || (isset($argv[2]) && $argv[2] !== 'show') || (isset($argv[3]) && !is_numeric($argv[3]))) {

	exit($c->error(
		"\nThis script tries to match release filenames to PreDB filenames.\n"
		. "To display the changes, use 'show' as the second argument. The optional third argument will limit the amount of filenames to attempt to match.\n\n"
		. "php match_prefiles.php full show	...: to run on full database and show renames.\n"
		. "php match_prefiles.php expanded		...: to run on console, xxx, and misc releases.\n"
		. "php match_prefiles.php console		...: to run on all console releases.\n"
		. "php match_prefiles.php misc		...: to run on all misc releases.\n"
		. "php match_prefiles.php xxx show 2000	...: to run on all xxx releases show renaming and limit to 2000 release filenames.\n"
		. "php match_prefiles.php all		...: to run on all releases with filenames (including previously renamed).\n"
		. "\nCurrently the only available methods are: console, misc, and xxx.  You can also use the expanded keyword to run against all of these choices or select full to run against everything.\n"
	));
}

echo $c->header("\nMatch PreFiles (${argv[1]}) Started at " . date('g:i:s'));
echo $c->primary("Matching predb filename to SUBSTRING_INDEX releasefiles.name.\n");

preFileName($argv);

function preFileName($argv)
{
	$pdo = new Settings();
	$timestart = TIME();
	$namefixer = new NameFixer();
	$c = new ColorCLI();
	$utility = new Utility();

	$qrycat = $renamed = $regfilter = $orderby = $limit = '';
	$regfilter = "AND rf.name REGEXP BINARY '^[a-z0-9]{1,20}-[a-z0-9]{1,20}\..{3}$' ";
	$rfname = "SUBSTRING_INDEX(rf.name, '.', 1)";
	$orderby = "ORDER BY postdate ASC";

	if (isset($argv[1]) && $argv[1] === "full") {
		$rfname = "rf.name";
		$regfilter = "";
		$orderby = '';
	} else if (isset($argv[1]) && $argv[1] === "expanded") {
		$qrycat = "AND (r.categoryid BETWEEN 1000 AND 1999 OR r.categoryid BETWEEN 6000 AND 6999 OR r.categoryid BETWEEN 7000 AND 7999) ";
	} else if (isset($argv[1]) && $argv[1] === "console") {
		$qrycat = "AND r.categoryid BETWEEN 1000 AND 1999 ";
	} else if (isset($argv[1]) && $argv[1] === "xxx") {
		$qrycat = "AND r.categoryid BETWEEN 6000 AND 6999 ";
	} else if (isset($argv[1]) && $argv[1] === "misc") {
		$qrycat = "AND r.categoryid BETWEEN 7000 AND 7999 ";
	}

	if (isset($argv[3]) && is_numeric($argv[3])) {
		$limit = "LIMIT " . $argv[3];
	}

	$qry =	sprintf(
			"SELECT r.id AS releaseid, r.name, r.searchname, r.group_id, r.categoryid, %s AS filename " .
			"FROM releases r INNER JOIN releasefiles rf ON r.id = rf.releaseid " .
			"WHERE r.preid = 0 %s %s %s " .
			"%s %s",
			$rfname,
			$qrycat,
			$regfilter,
			$renamed,
			$orderby,
			$limit
	);

	echo $c->headerOver($qry . PHP_EOL);
	$query = $pdo->queryDirect($qry);

	$total = $query->rowCount();
	$counter = $counted = 0;
	$show = (!isset($argv[2]) || $argv[2] !== 'show') ? 0 : 1;

	if ($total > 0) {

		echo $c->header("\n" . number_format($total) . ' releases to process.');
		sleep(2);
		$consoletools = new ConsoleTools();

		foreach ($query as $row) {
			$success = 0;
			if ($argv[1] === 'full') {
				$fileName = $row['filename'];
				//this function cuts the file extension off
				$fileName = $utility->cutStringUsingLast('.', $fileName, "left", false);
				//if filename has a .part001, send it back to the function to cut the next period
				if (preg_match('/\.part\d+$/', $fileName)) {
					$fileName = $utility->cutStringUsingLast('.', $fileName, "left", false);
				}
				//if filename has a .vol001, send it back to the function to cut the next period
				if (preg_match('/\.vol\d+(\+\d+)?$/', $fileName)) {
					$fileName = $utility->cutStringUsingLast('.', $fileName, "left", false);
				}
				//if filename contains a slash, cut the string and keep string to the right of the last slash
				if (strpos($fileName, '\\') !== false) {
					$fileName = $utility->cutStringUsingLast('\\', $fileName, "right", false);
				}
				$row['filename'] = trim($fileName);
			}
			if (isset($row['filename']) && $row['filename'] !== '' && strpos($row['filename'], '.') != 0 && strlen($row['filename']) > 0) {
				$success = $namefixer->matchPredbFiles($row, 1, 1, true, $show, $argv[1]);
				// A lot of obscured releases have one NFO file properly named with a track number (Audio) at the front of it
				// This will strip out the track and match it to its pre title
				if ($success === 0 && preg_match('/^\d{2}-[a-z0-9]/', $row['filename'])) {
					$row['filename'] = preg_replace('/^\d{2}-/', '', $row['filename']);
					$success = $namefixer->matchPredbFiles($row, 1, 1, true, $show, $argv[1]);
				}
			}
			if ($success === 1) {
				$counted++;
			}
			if ($show === 0) {
				$consoletools->overWritePrimary("Renamed Releases: [" . number_format($counted) . "] " . $consoletools->percentString(++$counter, $total));
			}
		}
	}
	if ($total > 0) {
		echo $c->header("\nRenamed " . number_format($counted) . " releases in " . $consoletools->convertTime(TIME() - $timestart) . ".");
	} else {
		echo $c->info("\nNothing to do.");
	}
}
