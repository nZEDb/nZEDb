<?php

/* TODO better tune the queries for performance, including pre-fetching group_id and other data for
	faster inclusion in the main query.
*/
require_once dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'config.php';

use nzedb\db\PreDb;
use nzedb\utility\Utility;

if (!Utility::isWin()) {
	$canExeRead = Utility::canExecuteRead(nZEDb_RES);
	if (is_string($canExeRead)) {
		exit($canExeRead);
	}
	unset($canExeRead);
}

if (!is_writable(nZEDb_RES)) {
	exit('The (' . nZEDb_RES . ') folder must be writable.' . PHP_EOL);
}

if (!isset($argv[1]) || !is_numeric($argv[1]) && $argv[1] != 'progress' || !isset($argv[2]) ||
	!in_array($argv[2], ['local', 'remote']) || !isset($argv[3]) ||
	!in_array($argv[3], ['true', 'false'])
) {
	exit('This script quickly imports the daily PreDB dumps.' . PHP_EOL .
		 'Argument 1: Enter the unix time of the patch to start at.' . PHP_EOL .
		 'You can find the unix time in the file name of the patch, it\'s the long number.' .
		 PHP_EOL .
		 'You can put in 0 to import all the daily PreDB dumps.' . PHP_EOL .
		 'You can put in progress to track progress of the imports and only import newer ones.' .
		 PHP_EOL .
		 'Argument 2: If your MySQL server is local, type local else type remote.' . PHP_EOL .
		 'Argument 3: Show output of dump_predb.php or not, true | false' . PHP_EOL
	);
}

$fileName = '_predb_dump.csv.gz';
$innerUrl = 'fb2pffwwriruyco';
$baseUrl  = 'https://www.dropbox.com/sh/' . $innerUrl;

$result = Utility::getUrl(['url' => $baseUrl . '/AACy9Egno_v2kcziVHuvWbbxa']);

if (!$result) {
	exit('Error connecting to dropbox.com, try again later?' . PHP_EOL);
}

Utility::clearScreen();

$result = preg_match_all(
	'/<a href="https:\/\/www.dropbox.com\/sh\/' . $innerUrl . '\/(\S+\/\d+' . $fileName . '\?dl=0)"/',
	$result,
	$links);

if ($result) {
	$links      = array_unique($links[1]);
	$total      = count($links);
	$predb      = new PreDb();

	$progress = $predb->progress(settings_array());

	if ($argv[1] != 'progress') {
		$progress['last'] = !is_numeric($argv[1]) ? time() : $argv[1];
	}

	$predb->executeTruncate();

	foreach ($links as $link) {
		if (preg_match('#^(.+)/(\d+)_#', $link, $match)) {
			$timematch = -1 + $progress['last'];

			// Skip patches the user does not want.
			if ($match[2] < $timematch) {
				echo 'Skipping dump ' . $match[2] . ', as your minimum unix time argument is ' .
					 $timematch . PHP_EOL;
				--$total;
				continue;
			}

			// Download the dump.
			$dump = Utility::getUrl(['url' => "$baseUrl/{$match[1]}/{$match[2]}$fileName?dl=1"]);

			if (!$dump) {
				echo "Error downloading dump {$match[2]} you can try manually importing it." . PHP_EOL;
				continue;
			}

			// Make sure we didn't get an HTML page.
			if (strlen($dump) < 5000 && strpos($dump, '<!DOCTYPE html>') !== false) {
				echo "The dump file {$match[2]} might be missing from dropbox." . PHP_EOL;
				continue;
			}

			// Decompress.
			$dump = gzdecode($dump);

			if (!$dump) {
				echo "Error decompressing dump {$match[2]}." . PHP_EOL;
				continue;
			}

			// Store the dump.
			$dumpFile = nZEDb_RES . $match[2] . '_predb_dump.csv';
			$fetched  = file_put_contents($dumpFile, $dump);
			if (!$fetched) {
				echo "Error storing dump file {$match[2]} in (" . nZEDb_RES . ').' . PHP_EOL;
				continue;
			}

			// Make sure it's readable by all.
			chmod($dumpFile, 0777);
			$local   = strtolower($argv[2]) == 'local' ? true : false;
			$verbose = $argv[3] == true ? true : false;

			if ($verbose) {
				echo $predb->log->info("Clearing import table");
			}

			// Truncate to clear any old data
			$predb->executeTruncate();

			// Import file into predb_imports
			$predb->executeLoadData([
										'fields' => '\\t\\t',
										'lines'  => '\\r\\n',
										'local' => $local,
										'path' => $dumpFile,
									]);

			// Remove any titles where length <=8
			if ($verbose === true) {
				echo $predb->log->info("Deleting any records where title <=8 from Temporary Table");
			}
			$predb->executeDeleteShort();

			// Add any groups that do not currently exist
			$predb->executeAddGroups();

			// Fill the group_id
			$predb->executeUpdateGroupID();

			echo $predb->log->info("Inserting records from temporary table into predb table");
			$predb->executeInsert();

			// Delete the dump.
			unlink($dumpFile);

			$progress = $predb->progress(settings_array($match[2] + 1, $progress), ['read' => false]);
			echo "Successfully imported PreDB dump {$match[2]} " . (--$total) .
				 ' dumps remaining to import.' . PHP_EOL;
		}
	}
}


function settings_array($last = null, $settings = null)
{
	if (is_null($settings)) {
		$settings['last'] = 0;
	}

	if (!is_null($last)) {
		$settings['last'] = $last;
	}

	return $settings;
}
