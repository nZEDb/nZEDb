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

if (!isset($argv[1])) {
	$message = <<<HELP
This script can export a predb dump file. You may use the full path, or a relative path.
For exporting, the path must be writeable by mysql, any existing file [predb_dump.csv] will be overwritten.

php {$argv[0]} /path/to/write/to                     ...: To export.

HELP;
	exit($message);
} else {
	$path = !preg_match('#^/#', $argv[1]) ? getcwd() . '/' . $argv[1] : $argv[1];
}

if (file_exists($path) && is_file($path)) {
	unlink($path);
}

Misc::clearScreen();

$table = isset($argv[2]) ? $argv[2] : 'predb';

$predb = new PreDb();
if (nZEDb_ECHOCLI) {
	echo "Exporting table: $table to '$path'\n";
}
$result = $predb->executeExport([
		'enclosed'	=> '',
		'fields'	=> '\t\t',
		'limit'		=> 0,
		'lines'		=> '\r\n',
		'path'		=> $path,
	]);

if ($result == false) {
	echo "ERROR: Failed to export file!\n";
}

?>
