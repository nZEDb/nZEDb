<?php

use nzedb\db\Settings;

$message =
	'Shows old searchname vs new searchname for releases in a group using the releaseCleaning class. (Good for testing new regex)' .
	PHP_EOL .
	'Argument 1 is the group name.' . PHP_EOL .
	'Argument 2 is how many releases to limit this to, must be a number.' . PHP_EOL .
	'Argument 3 (true|false) true renames the releases, false only displays what could be changed.' . PHP_EOL .
	'Argument 4 is a categoryID, pass 0 to do all, 7010 to do misc, etc.' . PHP_EOL .
	'php ' . $argv[0] . ' alt.binaries.comics.dcp 1000 false' . PHP_EOL;

if ($argc < 5) {
	exit($message);
}
if (!is_numeric($argv[2]) || !is_numeric($argv[4])) {
	exit($message);
}
if (!in_array($argv[3], array('true', 'false'))) {
	exit($message);
}

$category = '';
if ($argv[4] !== '0' && strlen($argv[4]) === 4) {
	$category = 'AND categoryid = ' . $argv[4];
}

$rename = false;
if ($argv[3] === 'true') {
	$rename = true;
}

require_once dirname(__FILE__) . '/../../../www/config.php';

$pdo = new Settings();

$group = $pdo->queryOneRow(sprintf('SELECT id FROM groups WHERE name = %s', $pdo->escapeString($argv[1])));

if ($group === false) {
	exit('No group with name ' . $argv[1] . ' found in the database.');
}

$releases = $pdo->query(sprintf('SELECT name, searchname, fromname, size, id FROM releases WHERE group_id = %d %s ORDER BY postdate LIMIT %d', $group['id'], $category, $argv[2]));

if (count($releases) === 0) {
	exit('No releases found in your database for group ' . $argv[1] . PHP_EOL);
}

$RC = new \ReleaseCleaning($pdo);
$sphinx = new \SphinxSearch();

foreach($releases as $release) {
	$newName = $RC->releaseCleaner($release['name'], $release['fromname'], $release['size'], $argv[1]);
	if (is_array($newName)) {
		$newName = $newName['cleansubject'];
	}
	if ($newName !== $release['searchname']) {
		echo 'Old name: ' . $release['searchname'] . PHP_EOL;
		echo 'New name: ' . $newName . PHP_EOL . PHP_EOL;

		if ($rename === true) {
			$newName = $pdo->escapeString($newName);
			$pdo->queryExec(sprintf('UPDATE releases SET searchname = %s WHERE id = %d', $newName, $release['id']));
			$sphinx->updateReleaseSearchName($release['id'], $newName);
		}
	}
}
