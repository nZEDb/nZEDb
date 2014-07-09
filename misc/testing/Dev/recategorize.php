<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$c = new ColorCLI();
if (!(isset($argv[1]) && ($argv[1] == "all" || $argv[1] == "misc" || preg_match('/\([\d, ]+\)/', $argv[1]) || is_numeric($argv[1])))) {
	exit($c->error(
		"\nThis script will attempt to re-categorize releases and is useful if changes have been made to Category.php.\n"
		. "No updates will be done unless the category changes\n"
		. "An optional last argument, test, will display the number of category changes that would be made\n"
		. "but will not update the database.\n\n"
		. "php $argv[0] all                     ...: To process all releases.\n"
		. "php $argv[0] misc                    ...: To process all releases in misc categories.\n"
		. "php $argv[0] 155                     ...: To process all releases in group_id 155.\n"
		. "php $argv[0] '(155, 140)'            ...: To process all releases in group_ids 155 and 140.\n"
	));
}

reCategorize($argv);

function reCategorize($argv)
{
	$c = new ColorCLI();
	$where = '';
	$update = true;
	if (isset($argv[1]) && is_numeric($argv[1])) {
		$where = ' AND group_id = ' . $argv[1];
	} else if (isset($argv[1]) && preg_match('/\([\d, ]+\)/', $argv[1])) {
		$where = ' AND group_id IN ' . $argv[1];
	} else if (isset($argv[1]) && $argv[1] === 'misc') {
		$where = ' AND categoryid IN (1090, 2020, 3050, 4040, 5050, 6050, 7010, 7020, 8050)';
	}
	if (isset($argv[2]) && $argv[2] === 'test') {
		$update = false;
	}

	if (isset($argv[1]) && (is_numeric($argv[1]) || preg_match('/\([\d, ]+\)/', $argv[1]))) {
		echo $c->header("Categorizing all releases in ${argv[1]} using searchname. This can take a while, be patient.");
	} else if (isset($argv[1]) && $argv[1] == "misc") {
		echo $c->header("Categorizing all releases in misc categories using searchname. This can take a while, be patient.");
	} else {
		echo $c->header("Categorizing all releases using searchname. This can take a while, be patient.");
	}
	$timestart = TIME();
	if (isset($argv[1]) && (is_numeric($argv[1]) || preg_match('/\([\d, ]+\)/', $argv[1])) || $argv[1] === 'misc') {
		$chgcount = categorizeRelease($update, str_replace(" AND", "WHERE", $where), true);
	} else {
		$chgcount = categorizeRelease($update, "", true);
	}
	$consoletools = new ConsoleTools();
	$time = $consoletools->convertTime(TIME() - $timestart);
	if ($update === true) {
		echo $c->header("Finished re-categorizing " . number_format($chgcount) . " releases in " . $time . " , 	using the searchname.\n");
	} else {
		echo $c->header("Finished re-categorizing in " . $time . " , using the searchname.\n"
		. "This would have changed " . number_format($chgcount) . " releases but no updates were done.\n");
	}
}

// Categorizes releases.
// Returns the quantity of categorized releases.
function categorizeRelease($update = true, $where, $echooutput = false)
{
	$pdo = new Settings();
	$cat = new Categorize();
	$consoletools = new consoleTools();
	$relcount = $chgcount = 0;
	$c = new ColorCLI();
	echo $c->primary("SELECT id, searchname, group_id, categoryid FROM releases " . $where);
	$resrel = $pdo->queryDirect("SELECT id, searchname, group_id, categoryid FROM releases " . $where);
	$total = $resrel->rowCount();
	if ($total > 0) {
		foreach ($resrel as $rowrel) {
			$catId = $cat->determineCategory($rowrel['searchname'], $rowrel['group_id']);
			if ($rowrel['categoryid'] != $catId) {
				if ($update === true) {
					$pdo->queryExec(
						sprintf("
							UPDATE releases
							SET iscategorized = 1,
								rageid = -1,
								seriesfull = NULL,
								season = NULL,
								episode = NULL,
								tvtitle = NULL,
								tvairdate = NULL,
								imdbid = NULL,
								musicinfoid = NULL,
								consoleinfoid = NULL,
								gamesinfo_id = NULL,
								bookinfoid = NULL,
								anidbid = NULL,
								categoryid = %d
							WHERE id = %d",
							$catId,
							$rowrel['id']
						)
					);
				}
				$chgcount++;
			}
			$relcount++;
			if ($echooutput) {
				$consoletools->overWritePrimary("Re-Categorized: [" . number_format($chgcount) . "] " . $consoletools->percentString($relcount, $total));
			}
		}
	}
	if ($echooutput !== false && $relcount > 0) {
		echo "\n";
	}
	return $chgcount;
}