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
 * @link <http://www.gnu.org/licenses/>.
 * @author niel
 * @copyright 2015 nZEDb
 */

require_once realpath(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\db\PreDb;
use nzedb\utility\Misc;

if (!Misc::isWin()) {
	$canExeRead = Misc::canExecuteRead(nZEDb_RES);
	if (is_string($canExeRead)) {
		exit($canExeRead);
	}
	unset($canExeRead);
}

$db = ['local', 'remote'];

if (!(isset($argv[1]) && in_array(strtolower($argv[1]), $db) && isset($argv[2]) && is_file($argv[2]))) {
	$message = <<<HELP
This script can import a predb dump file. You may use the full path, or a relative path.
For importing, the script insert new rows and update existing matched rows. For databases not on the local system, use remote, else use local.

php {$argv[0]} [remote | local] /path/to/filename

HELP;
	exit($message);
} else {
	$path = !preg_match('#^/#', $argv[2]) ? getcwd() . '/' . $argv[2] : $argv[2];

}
$argv[1] = strtolower($argv[1]);

Misc::clearScreen();

$table = isset($argv[3]) ? $argv[3] : 'predb';

$predb = new PreDb();
$local = ($argv[1] === 'local');

echo $predb->log->info("Clearing import table");

// Truncate to clear any old data
$predb->executeTruncate();

// Import file into predb_imports
$predb->executeLoadData([
		'fields'	=> '\\t\\t',
		'lines'		=> '\\r\\n',
		'local'		=> $local,
		'path'		=> $path,
	]);
//$predb->executeLoadData($path, $local);

// Remove any titles where length <=8
echo $predb->log->info("Deleting any records where title <=8 from Temporary Table");

$predb->executeDeleteShort();

// Add any groups that do not currently exist
$predb->executeAddGroups();

// Fill the group_id
$predb->executeUpdateGroupID();

echo $predb->log->info("Inserting records from temporary table into predb table");
$predb->executeInsert();

?>
