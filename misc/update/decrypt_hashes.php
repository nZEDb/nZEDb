<?php
require_once realpath(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'bootstrap.php');

use nzedb\Category;
use nzedb\ConsoleTools;
use nzedb\NameFixer;
use nzedb\db\DB;

$pdo = new DB();

if (!isset($argv[1]) || ($argv[1] != "all" && $argv[1] != "full" && !is_numeric($argv[1]))) {
	exit($pdo->log->error(
		"\nThis script tries to match hashes of the releases.name or releases.searchname to predb hashes.\n"
		. "To display the changes, use 'show' as the second argument.\n\n"
		. "php decrypt_hashes.php 1000		...: to limit to 1000 sorted by newest postdate.\n"
		. "php decrypt_hashes.php full ...: to run on full database.\n"
		. "php decrypt_hashes.php all ...: to run on all hashed releases(including previously renamed).\n"
	));
}

echo $pdo->log->header("\nDecrypt Hashes (${argv[1]}) Started at " . date('g:i:s'));
echo $pdo->log->primary("Matching predb hashes to hash(releases.name or releases.searchname)");

getPreName($argv);

function getPreName($argv)
{
	global $pdo;
	$timestart = time();
	$consoletools = new ConsoleTools(['ColorCLI' => $pdo->log]);
	$namefixer = new NameFixer(['Settings' => $pdo, 'ConsoleTools' => $consoletools]);

	$res = false;
	if (isset($argv[1]) && $argv[1] === "all") {
		$res = $pdo->queryDirect('SELECT id AS releases_id, name, searchname, groups_id, categories_id, dehashstatus FROM releases WHERE predb_id = 0 AND ishashed = 1');
	} else if (isset($argv[1]) && $argv[1] === "full") {
		$res = $pdo->queryDirect('SELECT id AS releases_id, name, searchname, groups_id, categories_id, dehashstatus FROM releases WHERE categories_id = ' .
				Category::OTHER_HASHED . ' AND ishashed = 1 AND dehashstatus BETWEEN -6 AND 0');
	} else if (isset($argv[1]) && is_numeric($argv[1])) {
		$res = $pdo->queryDirect('SELECT id AS releases_id, name, searchname, groups_id, categories_id, dehashstatus FROM releases WHERE categories_id = ' .
				Category::OTHER_HASHED . ' AND ishashed = 1 AND dehashstatus BETWEEN -6 AND 0 ORDER BY postdate DESC LIMIT ' . $argv[1]);
	}

	$counter = $counted = $total = 0;
	if ($res !== false) {
		$total = $res->rowCount();
	}
	$show = (!isset($argv[2]) || $argv[2] !== 'show') ? 0 : 1;
	if ($total > 0) {
		echo $pdo->log->header("\n" . number_format($total) . ' releases to process.');
		sleep(2);

		foreach ($res as $row) {
			$success = 0;
			if (preg_match('/[a-fA-F0-9]{32,40}/i', $row['name'], $matches)) {
				$success = $namefixer->matchPredbHash($matches[0], $row, 1, 1, true, $show);
			} else if (preg_match('/[a-fA-F0-9]{32,40}/i', $row['searchname'], $matches)) {
				$success = $namefixer->matchPredbHash($matches[0], $row, 1, 1, true, $show);
			}

			if ($success === 0) {
				$pdo->queryDirect(sprintf('UPDATE releases SET dehashstatus = dehashstatus - 1 WHERE id = %d', $row['releases_id']));
			} else {
				$counted++;
			}
			if ($show === 0) {
				$consoletools->overWritePrimary("Renamed Releases: [" . number_format($counted) . "] " . $consoletools->percentString(++$counter, $total));
			}
		}
	}
	if ($total > 0) {
		echo $pdo->log->header("\nRenamed " . $counted . " releases in " . $consoletools->convertTime(TIME() - $timestart) . ".");
	} else {
		echo $pdo->log->info("\nNothing to do.");
	}
}
