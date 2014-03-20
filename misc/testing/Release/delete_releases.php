<?php
// New line for CLI.
$n = PHP_EOL;

// Time the script started.
$timeStart = TIME();

// Print arguments/usage.
if (count($argv) < 2) {
	exit(
		'This deletes releases based on a list of criteria you pass.' . $n .
		'Usage:' . $n . $n.
		'List of supported criteria:' . $n .
		'fromname   : Look for names of people who posted releases (the poster name). (modifiers: equals, like)' . $n .
		'groupname  : Look in groups. (modifiers: equals, like)' . $n .
		'guid       : Look for a specific guid. (modifiers: equals)' . $n .
		'name       : Look for a name (the usenet name). (modifiers: equals, like)' . $n .
		'searchname : Look for a name (the search name). (modifiers: equals, like)' . $n .
		'size       : Release must be (bigger than |smaller than |exactly) this size.(bytes) (modifiers: equals,bigger,smaller)' . $n .
		'adddate    : Look for releases added to our DB (older than|newer than) x hours. (modifiers: bigger,smaller)' . $n .
		'postdate   : Look for posted to usenet (older than|newer than) x hours. (modifiers: bigger,smaller)' . $n . $n .
		'List of Modifiers:' . $n .
		'equals     : Match must be exactly this. (fromname=equals="john" will only look for john)' . $n .
		'like       : Match can be similar to this. (fromname=like="john" will look for any posters with john in it)' . $n .
		'bigger     : Match must be bigger than this. (postdate=bigger="3" means older than 3 hours ago)' . $n .
		'smaller    : Match must be smaller than this (postdate=smaller="3" means between now and 3 hours ago.' . $n . $n .
		'Examples:' . $n .
		$_SERVER['_'] . ' ' . $argv[0] . ' groupname=equals="alt.binaries.teevee" searchname=like="olympics 2014" postdate=bigger="5"' . $n .
		$_SERVER['_'] . ' ' . $argv[0] . ' guid=equals="8fb5956bae3de4fb94edcc69da44d6883d586fd0"' . $n .
		$_SERVER['_'] . ' ' . $argv[0] . ' size=smaller="104857600" size=bigger="2048" groupname=like="movies"' . $n
	);
}

// Include config.php
require_once dirname(__FILE__) . '/../../../www/config.php';

// Class of DB.
$db = new DB();

// Start forming the query.
$query = 'SELECT id, guid FROM releases WHERE 1=1';

// PgSQL LIKE is not case insensitive.
$like = ($db->dbSystem() === 'mysql' ? 'LIKE ' : 'ILIKE ');

// Go over the arguments, check if they are good, keep forming the query.
foreach($argv as $arg) {
	if ($arg === $argv[0]) {
		continue;
	}
	$query .= formatArgument($arg, $like, $db);
}

// Clean spaces up.
$query = trim(preg_replace('/\s{2,}/', ' ', $query));

// Print the query to the user, ask them if they want to continue using it.
echo
	'This is the query we have formatted using your list of criteria, you can run this in SQL to see if you like the results:' . $n .
	$query . ';' . $n .
	'If you are satisfied, types yes and press enter.' . $n;

// Check the users response.
$userInput = trim(fgets(fopen('php://stdin', 'r')));
if ($userInput !== 'yes') {
	exit('You typed: "' . $userInput . '", the program will exit.' . $n);
}

// Run the query, check if it picked up anything.
$result = $db->query($query);
$totalResults = count($result);
if ($totalResults <= 0) {
	exit('No releases were found to delete, try changing your criteria.' . $n);
}

// Start deleting releases.
$releases = new Releases();
$consoleTools = new ConsoleTools();
$s = new Sites();
$site = $s->get();
$deletedCount = 0;
foreach ($result as $release) {
	$releases->fastDelete($release['id'], $release['guid'], $site);
	$deletedCount++;
	$consoleTools->overWriteHeader(
		"Deleting: " . $consoleTools->percentString($deletedCount, $totalResults) .
		" Time:" . $consoleTools->convertTimer(TIME() - $timeStart)
	);
}
echo $n;
$c = new ColorCLI();
echo $c->headerOver("Deleted " . $deletedCount . " release(s). This script ran for ");
echo $c->header($consoleTools->convertTime(TIME() - $timeStart));
echo $n;

/**
 * Go over the usere's argument list, format part of the query.
 *
 * @param string $argument An argument passed to CLI.
 * @param string $like     LIKE for MySQL, ILIKE for PgSQL.
 * @param DB     $db       Class DB.
 *
 * @return string
 */
function formatArgument($argument, $like, $db) {
	$args = explode('=', $argument);
	if (count($args) !== 3) {
		argumentError($argument);
	}

	switch($args[0]) {
		case 'fromname':
			switch ($args[1]) {
				case 'equals':
					return ' AND fromname = ' . $db->escapeString($args[2]);
				case 'like':
					return ' AND fromname ' . formatLike($args[2], 'fromname', $like);
				default:
					argumentError($argument);
			}
			break;
		case 'groupname':
			switch ($args[1]) {
				case 'equals':
					$group = $db->queryOneRow('SELECT id FROM groups WHERE name = ' . $db->escapeString($args[2]));
					if ($group === false) {
						exit('This group was not found in your database: ' . $args[2] . PHP_EOL);
					}
					return ' AND groupid = ' . $group['id'];
				case 'like':
					$group = $db->queryOneRow('SELECT id FROM groups WHERE name ' . formatLike($args[2], 'name', $like));
					if ($group === false) {
						exit('No groups were found with this pattern in your database: ' . $args[2] . PHP_EOL);
					}
					return ' AND groupid ' . $like . $group['id'];
				default:
					argumentError($argument);
			}
			break;
		case 'guid':
			switch ($args[1]) {
				case 'equals':
					return ' AND guid = ' . $db->escapeString($args[2]);
				default:
					argumentError($argument);
			}
			break;
		case 'name':
			switch ($args[1]) {
				case 'equals':
					return ' AND name = ' . $db->escapeString($args[2]);
				case 'like':
					return ' AND name ' . formatLike($args[2], 'name', $like);
				default:
					argumentError($argument);
			}
			break;
		case 'searchname':
			switch ($args[1]) {
				case 'equals':
					return ' AND searchname = ' . $db->escapeString($args[2]);
				case 'like':
					return ' AND searchname ' . formatLike($args[2], 'searchname', $like);
				default:
					argumentError($argument);
			}
			break;
		case 'size':
			if (!is_numeric($args[2])) {
				argumentError($argument);
			}
			switch ($args[1]) {
				case 'equals':
					return ' AND size = ' . $args[2];
				case 'bigger':
					return ' AND size > ' . $args[2];
				case 'smaller':
					return ' AND size < ' . $args[2];
				default:
					argumentError($argument);
			}
			break;
		case 'adddate':
			if (!is_numeric($args[2])) {
				argumentError($argument);
			}
			switch ($args[1]) {
				case 'bigger':
					return ' AND adddate <  NOW() - INTERVAL ' . $args[2] . ' HOUR';
				case 'smaller':
					return ' AND adddate >  NOW() - INTERVAL ' . $args[2] . ' HOUR';
				default:
					argumentError($argument);
			}
			break;
		case 'postdate':
			if (!is_numeric($args[2])) {
				argumentError($argument);
			}
			switch ($args[1]) {
				case 'bigger':
					return ' AND postdate <  NOW() - INTERVAL ' . $args[2] . ' HOUR';
				case 'smaller':
					return ' AND postdate >  NOW() - INTERVAL ' . $args[2] . ' HOUR';
				default:
					argumentError($argument);
			}
			break;
		default:
			argumentError($argument);
	}
	return '';
}

/**
 * Format a "like" string. ie: "name LIKE '%test%' AND name LIKE '%123%'
 *
 * @param string $string The string to format.
 * @param string $type   The column name.
 * @param string $like   LIKE/ILIKE
 *
 * @return string
 */
function formatLike($string, $type, $like) {
	$newString = explode(' ', $string);
	if (count($newString) > 1) {
		$string = implode("%' AND {$type} {$like} '%", $newString);
	}
	return " {$like} '%" . $string . "%' ";
}

/**
 * Exit if wrong argument is passed.
 *
 * @param string $argument The wrong argument.
 */
function argumentError($argument) {
	exit('Invalid argument supplied: ' . $argument . PHP_EOL);
}