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

/* TODO better tune the queries for performance, including pre-fetching group_id and other data for
	faster inclusion in the main query.
*/
require_once realpath(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'indexer.php');

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
	echo "Fetching predb_dump list from GitHub\n";
}

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

if (nZEDb_DEBUG) {
	echo "Extracting filenames from list.\n";
}

$data = json_decode($result, true);
if (is_null($data)) {
	exit("Error: $result");
}

$total = count($data) - 1;
$predb = new PreDb();

$progress = $predb->progress(settings_array());

foreach ($data as $file) {
	if (preg_match("#^https://raw\.githubusercontent\.com/nZEDb/nZEDbPre_Dumps/master/dumps/$filePattern$#",
				   $file['download_url'])) {
		if (preg_match("#^$filePattern$#", $file['name'], $match)) {
			$timematch = $progress['last'];

			// Skip patches the user does not want.
			if ($match[1] < $timematch) {
				echo 'Skipping dump ' . $match[2] .
					 ', as your minimum unix time argument is ' .
					 $timematch . PHP_EOL;
				--$total;
				continue;
			}

			// Download the dump.
			$dump = Misc::getUrl(['url' => $file['download_url']]);
			echo "Downloading: {$file['download_url']}\n";

			if (!$dump) {
				echo "Error downloading dump {$match[2]} you can try manually importing it." .
					 PHP_EOL;
				continue;
			} else {
				if (nZEDb_DEBUG) {
					echo "Dump {$match[2]} downloaded\n";
				}
			}

			// Make sure we didn't get an HTML page.
			if (strpos($dump, '<!DOCTYPE html>') !== false) {
				echo "The dump file {$match[2]} might be missing from GitHub." . PHP_EOL;
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
				echo "Error storing dump file {$match[2]} in (" . nZEDb_RES . ').' .
					 PHP_EOL;
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
			$predb->executeLoadData(
					[
						'fields' => '\\t\\t',
						'lines'  => '\\r\\n',
						'local'  => $local,
						'path'   => $dumpFile,
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

			$progress = $predb->progress(settings_array($match[2] + 1, $progress),
										 ['read' => false]);
			echo "Successfully imported PreDB dump {$match[2]}, " . (--$total) .
				 ' dumps remaining.' . PHP_EOL;
		} else {
			echo "Ignoring: {$file['download_url']}\n";
		}
	} else if (nZEDb_DEBUG) {
			echo "^https://raw.githubusercontent.com/nZEDb/nZEDbPre_Dumps/master/dumps/$filePattern$\n {$file['download_url']}\n";
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

?>
