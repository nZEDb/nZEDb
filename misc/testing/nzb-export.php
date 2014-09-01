<?php
require_once dirname(__FILE__) . '/../../www/config.php';
$n = PHP_EOL;

// Print usage.
if (count($argv) !== 6) {
	exit(
		'This will export NZB files(to .nzb or .nzb.gz) into sub folders (using group name) of the specified folder.'. $n . $n .
		'Usage: ' . $n .
		$_SERVER['_'] . ' ' . __FILE__ . ' arg1 arg2 arg3 arg4 arg5' . $n . $n .
		'arg1 : Path to folder where NZB files are to be stored.          | a folder path' . $n .
		'arg2 : The start date in this format: 01/01/2008 or false        | date/false' . $n .
		'arg3 : The end date in this format: 01/01/2008 or false          | date/false' . $n .
		'arg4 : Group ID for the group or false                           | number/false' . $n .
		'arg5 : Gzip the NZB files (recommended, faster/takes less space) | true/false' . $n . $n .
		'Examples: ' . $n .
		$_SERVER['_'] . ' ' .$argv[0] . ' ' . nZEDb_ROOT . 'exportFolder' . DS . ' 01/01/2012 01/01/2014 false true' . $n .
		$_SERVER['_'] . ' ' .$argv[0] . ' ' . nZEDb_ROOT . 'exportFolder' . DS . ' false 01/01/2014 12 false' . $n
	);
}

$NE = new \NZBExport();
$NE->beginExport(
	array(
		// Path.
		$argv[1],
		// Start time.
		(strtolower($argv[2]) === 'false' ? '' : $argv[2]),
		// End time.
		(strtolower($argv[3]) === 'false' ? '' : $argv[3]),
		// Group ID.
		(strtolower($argv[4]) === 'false' ? 0 : (int)$argv[4]),
		// Gzip.
		(strtolower($argv[5]) === 'true' ? true : false)
	)
);
