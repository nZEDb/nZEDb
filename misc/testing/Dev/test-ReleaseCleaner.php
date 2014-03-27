<?php
$message =
	'Shows old searchname vs new searchname for releases in a group using the releaseCleaning class. (Good for testing new regex)' .
	PHP_EOL .
	'Argument 1 is the group name.' . PHP_EOL .
	'Argument 2 is how many releases to limit this to, must be a number.' . PHP_EOL .
	'Argument 3 (true|false) true renames the releases, false only displays what could be changed.' . PHP_EOL .
	'php ' . $argv[0] . ' alt.binaries.comics.dcp 1000 false' . PHP_EOL;

if ($argc < 4) {
	exit($message);
}
if (!is_numeric($argv[2])) {
	exit($message);
}
if (!in_array($argv[3], array('true', 'false'))) {
	exit($message);
}

$rename = false;
if ($argv[3] === 'true') {
	$rename = true;
}

require_once dirname(__FILE__) . '/../../../www/config.php';

$db = new DB();

$group = $db->queryOneRow(sprintf('SELECT id FROM groups WHERE name = %s', $db->escapeString($argv[1])));

if ($group === false) {
	exit('No group with name ' . $argv[1] . ' found in the database.');
}

$releases = $db->query(sprintf('SELECT name, searchname, id FROM releases WHERE groupid = %d ORDER BY postdate LIMIT %d', $group['id'], $argv[2]));

if (count($releases) === 0) {
	exit('No releases found in your database for group ' . $argv[1] . PHP_EOL);
}

$RC = new ReleaseCleaning();

foreach($releases as $release) {
	$newName = $RC->releaseCleaner($release['name'], $argv[1]);
	if (is_array($newName)) {
		$newName = $newName['cleansubject'];
	}
	if ($newName !== $release['searchname']) {
		echo 'Old name: ' . $release['searchname'] . PHP_EOL;
		echo 'New name: ' . $newName . PHP_EOL . PHP_EOL;

		if ($rename === true) {
			$db->queryExec(sprintf('UPDATE releases SET searchname = %s WHERE id = %d', $db->escapeString($newName), $release['id']));
		}
	}
}