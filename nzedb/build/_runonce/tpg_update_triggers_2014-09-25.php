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
 * @copyright 2014 nZEDb
 */
require_once dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'indexer.php';

use nzedb\db\Settings;

$pdo = new Settings();

if (!$pdo->getSetting('tablepergroup')) {
	exit("Tables per groups is not enabled, quitting!");
}

// Doing it this way in case there are tables existing not related to the active/backfill list (i.e. I don't have a clue when these tables get deleted so I'm doing any that are there).
$tables = $pdo->queryDirect("SELECT SUBSTR(TABLE_NAME, 9) AS suffix FROM information_schema.TABLES WHERE TABLE_SCHEMA = (SELECT DATABASE()) AND TABLE_NAME LIKE 'binaries%' ORDER BY TABLE_NAME");

$query1 = "ALTER TABLE binaries%s DROP INDEX ix_binary_collection";
$query2 = "DROP TRIGGER IF EXISTS delete_collections%s";
$query3 = "CREATE TRIGGER delete_collections%s BEFORE DELETE ON collections%s FOR EACH ROW BEGIN DELETE FROM binaries%s WHERE collection_id = OLD.id; DELETE FROM parts%s WHERE collection_id = OLD.id; END";
$query4 = "ALTER TABLE binaries%s ADD INDEX ix_parts_collection_id(collection_id)";

if ($tables instanceof \Traversable) {
	foreach ($tables as $table) {
		echo "Updating table binaries{$table['suffix']}" . PHP_EOL;
		$pdo->queryExec(sprintf($query1, $table['suffix']), true);
		$pdo->queryExec(sprintf($query2, $table['suffix']), true);
		$pdo->queryExec(sprintf($query3,
								$table['suffix'],
								$table['suffix'],
								$table['suffix'],
								$table['suffix']),
						true);
		$pdo->queryExec(sprintf($query4, $table['suffix']), true);
	}
	echo 'All done!' . PHP_EOL;
}

?>
