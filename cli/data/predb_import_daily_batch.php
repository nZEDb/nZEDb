<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 *
 * @link      <http://www.gnu.org/licenses/>.
 * @author    niel
 * @copyright 2015 nZEDb
 */

/* TODO better tune the queries for performance, including pre-fetching groups_id and other data for
	faster inclusion in the main query.
*/
require_once realpath(dirname(__DIR__, 2) . '/app/config/bootstrap.php');

use nzedb\db\PreDb;
use nzedb\utility\Misc;

if (!Misc::isWin()) {
	if (nZEDb_DEBUG) {
		echo "Checking resource path\n";
	}
	$canExeRead = Misc::canExecuteRead(nZEDb_RES);
	if (is_string($canExeRead)) {
		exit($canExeRead);
	}
	unset($canExeRead);
}

if (nZEDb_DEBUG) {
	echo "Checking directory is writable\n";
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
		 'Argument 3: Show output of queries or not, true | false' . PHP_EOL
	);
}
if (nZEDb_DEBUG) {
	echo "Parameter check completed\n";
}

$url         = 'https://api.github.com/repos/nZEDb/nZEDbPre_Dumps/contents/dumps/';
$filePattern = '(?P<filename>(?P<stamp>\d+)_predb_dump\.csv\.gz)';

if (nZEDb_DEBUG) {
	echo "Fetching predb_dump directory list from GitHub\n";
}

$result = getDirListing($url);

$dirs = json_decode($result, true);

if (is_null($dirs) ||
	(isset($dirs['message']) && substr($dirs['message'], 0, 27) == 'API rate limit exceeded for')) {
	exit("Error: $result");
}


if (nZEDb_DEBUG) {
	echo "Fetching predb_dump lists from GitHub\n";
}

foreach ($dirs as $dir) {
	if ($dir['name'] == '0README.txt') {
		continue;
	}

	$result = getDirListing($url . $dir['name'] . '/');

	if (nZEDb_DEBUG) {
		echo "Extracting filenames from list.\n";
	}

	$temp = json_decode($result, true);
	if (is_null($temp)) {
		exit("Error: $result");
	}

	$data[$dir['name']] = $temp;
}

$total = 0;
foreach ($data as $dir => $files) {
	$total += count($files);
}
$total -= 1;

$predb = new PreDb();

$progress = $predb->progress(settings_array());
if (is_numeric($argv[1])) {
	$progress['last'] = $argv[1];
}

foreach ($data as $dir => $files) {
	foreach ($files as $file) {
		//var_dump($file);
		if (preg_match("#^https://raw\.githubusercontent\.com/nZEDb/nZEDbPre_Dumps/master/dumps/$dir/$filePattern$#",
			$file['download_url'])) {
			if (preg_match("#^$filePattern$#", $file['name'], $match)) {
				$timematch = $progress['last'];

				// Skip patches the user does not want.
				if ($match['stamp'] < $timematch) {
					echo 'Skipping dump ' . $match['stamp'] .
						', as your minimum unix time argument is ' .
						$timematch . PHP_EOL;
					--$total;
					continue;
				}

				// Download the dump.
				$dump = Misc::getUrl(['url' => $file['download_url']]);
				echo "Downloading: {$file['download_url']}\n";

				if (!$dump) {
					echo "Error downloading dump {$match['stamp']} you can try manually importing it." .
						PHP_EOL;
					continue;
				} else {
					if (nZEDb_DEBUG) {
						echo sprintf("Dump %s downloaded\n", date('Y-m-d', $match['stamp']));
					}
				}

				// Make sure we didn't get an HTML page.
				if (strpos($dump, '<!DOCTYPE html>') !== false) {
					echo "The dump file {$match['stamp']} might be missing from GitHub." . PHP_EOL;
					continue;
				}

				// Decompress.
				$dump = gzdecode($dump);

				if (!$dump) {
					echo "Error decompressing dump {$match['stamp']}." . PHP_EOL;
					continue;
				}

				// Store the dump.
				$dumpFile = nZEDb_TMP . $match['stamp'] . '_predb_dump.csv';
				$fetched = file_put_contents($dumpFile, $dump);
				if (!$fetched) {
					echo "Error storing dump file {$match['stamp']} in (" . nZEDb_RES . ').' .
						PHP_EOL;
					continue;
				}

				// Make sure it's readable by all.
				chmod($dumpFile, 0777);
				$local = strtolower($argv[2]) === 'local' ? true : false;
				$verbose = $argv[3] == true ? true : false;

				if ($verbose) {
					echo $predb->log->info('Clearing import table');
				}

				// Truncate to clear any old data
				$predb->executeTruncate();

				// Import file into predb_imports
				$predb->executeLoadData(
					[
						'fields' => '\\t\\t',
						'lines'  => '\\r\\n',
						'local'  => $local,
						'path'   => $dumpFile,
					]);

				// Remove any titles where length <=8
				if ($verbose === true) {
					echo $predb->log->info('Deleting any records where title <=8 from Temporary Table');
				}
				$predb->executeDeleteShort();

				// Add any groups that do not currently exist
				$predb->executeAddGroups();

				// Fill the groups_id
				$predb->executeUpdateGroupID();

				echo $predb->log->info('Inserting records from temporary table into predb table');
				$predb->executeInsert();

				// Delete the dump.
				unlink($dumpFile);

				$progress = $predb->progress(settings_array($match['stamp'] + 1, $progress),
					['read' => false]);
				echo sprintf("Successfully imported PreDB dump %d (%s), %d dumps remaining\n",
					$match['stamp'],
					 date('Y-m-d', $match['stamp']),
					$total--
					);
			} else {
				echo "Ignoring: {$file['download_url']}\n";
			}
		} else {
			if (nZEDb_DEBUG) {
				echo "^https://raw.githubusercontent.com/nZEDb/nZEDbPre_Dumps/master/dumps/$dir/$filePattern$\n {$file['download_url']}\n";
			}
		}
	}
}

////////////////////////////////////////////////////////////////////////////////////////////////////

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

function getDirListing($url)
{
	$result = Misc::getUrl(
		[
			'url'            => $url,
			'requestheaders' => [
				'Content-Type: application/json',
				'User-Agent: nZEDb'
			]
		]);

	if ($result === false) {
		exit('Error connecting to GitHub, try again later?' . PHP_EOL);
	}

	return $result;
}

?>
