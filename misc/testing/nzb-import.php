<?php
require_once dirname(__FILE__) . '/../../www/config.php';
$n = PHP_EOL;

// Print usage.
if (count($argv) !== 6) {
	exit(
		'This will import NZB files(.nzb or .nzb.gz) into your nZEDb site.'. $n . $n .
		'Usage: ' . $n .
		$_SERVER['_'] . ' ' . __FILE__ . ' arg1 arg2 arg3 arg4 arg5' . $n . $n .
		'arg1 : Path to folder where NZB files are stored.                | a folder path' . $n .
		'arg2 : Delete NZB when successfully imported.(recommended)       | true/false' . $n .
		'arg3 : Delete NZB when unsuccessfully imported.(not recommended) | true/false' . $n .
		'arg4 : Use NZB file name as release name.(not recommended)       | true/false' . $n .
		'arg5 : Import this many NZB files. 0 for all                     | a number' . $n . $n .
		'ie: ' . $_SERVER['_'] . ' ' . __FILE__ . ' ' . nZEDb_ROOT . 'nzbToImport' . DS . ' true false false 1000' . $n
	);
}

// Verify arguments.
if (!is_dir($argv[1])) {
	exit('Error: arg1 must be a path (you might not have read access to this path)' . $n);
}
if (!in_array($argv[2], array('true', 'false'))) {
	exit('Error: arg2 must be true or false' . $n);
}
if (!in_array($argv[3], array('true', 'false'))) {
	exit('Error: arg3 must be true or false' . $n);
}
if (!in_array($argv[4], array('true', 'false'))) {
	exit('Error: arg4 must be true or false' . $n);
}
if (!is_numeric($argv[5])) {
	exit('Error: arg5 must be a number' . $n);
}
if ((int)$argv[5] < 0) {
	exit('Error: arg5 must be 0 or higher' . $n);
}

$path = $argv[1];
// Check if path ends with dir separator.
if (substr($path, -1) !== DS) {
	$path .= DS;
}

// Get the files from the user specified path.
$filesToProcess = glob($path . "*.{nzb,nzb.gz}", GLOB_BRACE);
$totalFiles = count($filesToProcess);
if ($totalFiles > 0) {

	// If the user wants to limit the amount of import. do that here.
	if ((int)$argv[5] > 0) {

		// Check if the files found is more than the user's max wanted.
		if ($totalFiles > (int)$argv[5]) {
			// Sort the array by value.
			asort($filesToProcess);

			// Get up to argv5 files.
			$filesToProcess = array_slice($filesToProcess, 1, (int)$argv[5]);
		}
	}

	// Check these user argument values, convert them to bool.
	$deleteNZB = ($argv[2] == 'true') ? true : false;
	$deleteFailedNZB = ($argv[3] == 'true') ? true : false;
	$useNzbName = ($argv[4] == 'true') ? true : false;

	// Create a new instance of NZBImport and send it the file locations.
	$NZBImport = new NZBImport();

	$NZBImport->beginImport($filesToProcess, $useNzbName, $deleteNZB, $deleteFailedNZB);
} else {
	echo 'Nothing found to import!' . $n;
}