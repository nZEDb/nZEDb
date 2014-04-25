<?php
require_once dirname(__FILE__) . '/config.php';

use nzedb\db\DB;

$c = new ColorCLI();
if (!isset($argv[1]) || ( $argv[1] != "all" && $argv[1] != "full" && !is_numeric($argv[1]))) {
	exit($c->error("\nThis script tries to match an MD5 or SHA1 of the releases.name or releases.searchname to predb.md5 or sha1 columns.\n"
			. "To display the changes, use 'show' as the second argument.\n\n"
			. "php decrypt_hashes.php 1000		...: to limit to 1000 sorted by newest postdate.\n"
			. "php decrypt_hashes.php full 		...: to run on full database.\n"
			. "php decrypt_hashes.php all 		...: to run on all hashed releases(including previously renamed).\n"));
}

echo $c->header("\nDecrypt Hashes (${argv[1]}) Started at " . date('g:i:s'));
echo $c->primary("Matching predb MD5 to md5(releases.name or releases.searchname)");

preName($argv);

function preName($argv)
{
	$db = new DB();
	$timestart = TIME();
	$namefixer = new NameFixer();

	if (isset($argv[1]) && $argv[1] === "all") {
		$res = $db->queryDirect('SELECT id AS releaseid, name, searchname, groupid, categoryid FROM releases WHERE ishashed = 1');
	} else if (isset($argv[1]) && $argv[1] === "full") {
		$res = $db->queryDirect('SELECT id AS releaseid, name, searchname, groupid, categoryid FROM releases WHERE ishashed = 1 AND dehashstatus BETWEEN -6 AND 0');
	} else if (isset($argv[1]) && is_numeric($argv[1])) {
		$res = $db->queryDirect('SELECT id AS releaseid, name, searchname, groupid, categoryid FROM releases WHERE ishashed = 1 AND dehashstatus BETWEEN -6 AND 0 ORDER BY postdate DESC LIMIT ' . $argv[1]);
	}
	$c = new ColorCLI();

	$total = $res->rowCount();
	$counter = $counted = 0;
	if ($total > 0) {
		echo $c->header("\n" . number_format($total) . ' releases to process.');
		sleep(2);
		$consoletools = new ConsoleTools();
		$category = new Category();
		$reset = 0;
		$loops = 1;
		foreach ($res as $row) {
			$success = false;
			if (preg_match('/([0-9a-fA-F]{32,40})/', $row['searchname'], $match) || preg_match('/([0-9a-fA-F]{32,40})/', $row['name'], $match)) {
				$pre = $db->queryOneRow(sprintf('SELECT id, title, source FROM predb WHERE md5 = %s OR sha1 = %s', $db->escapeString($match[1]), $db->escapeString($match[1])));
				if ($pre !== false) {
					$determinedcat = $category->determineCategory($pre['title'], $row['groupid']);
					$result = $db->queryDirect(sprintf("UPDATE releases SET rageid = -1, seriesfull = NULL, season = NULL, episode = NULL, tvtitle = NULL, tvairdate = NULL, imdbid = NULL, musicinfoid = NULL, consoleinfoid = NULL, bookinfoid = NULL, anidbid = NULL, "
							. "preid = %d, dehashstatus = 1, isrenamed = 1, iscategorized = 1, searchname = %s, categoryid = %d WHERE id = %d", $pre['id'], $db->escapeString($pre['title']), $determinedcat, $row['releaseid']));
					if ($result->rowCount() > 0) {
						if (isset($argv[2]) && $argv[2] === 'show') {
							$namefixer->updateRelease($row, $pre["title"], $method = "predb md5 release name: " . $pre["source"], 1, "MD5, ", 1, 1);
						}
						$counted++;
						$success = true;
					}
				}
			}
			if ($success === false) {
				$db->queryDirect(sprintf('UPDATE releases SET dehashstatus = dehashstatus - 1 WHERE id = %d', $row['releaseid']));
			}
			if (!isset($argv[2]) || $argv[2] !== 'show') {
				$consoletools->overWritePrimary("Renamed Releases: [" . number_format($counted) . "] " . $consoletools->percentString( ++$counter, $total));
			}
		}
	}
	if ($total > 0) {
		echo $c->header("\nRenamed " . $counted . " releases in " . $consoletools->convertTime(TIME() - $timestart) . ".");
	} else {
		echo $c->info("\nNothing to do.");
	}
}
