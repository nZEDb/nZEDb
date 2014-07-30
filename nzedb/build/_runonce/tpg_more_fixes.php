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
 * @author niel / kevin
 * @copyright 2014 nZEDb
 */

if (!isset($argv[1]) || !in_array($argv[1], ['1', '2'])) {
	exit(
	'Options: (enter a number, it\'s not recommended to rerun the same fix)' . PHP_EOL .
	'1: 2014-07-28: Add unique key to binaryhash to be able to do multiple updates in 1 statement.' . PHP_EOL
	);
}

require_once dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'config.php';

use nzedb\db\Settings;

$pdo = new Settings();

if (!$pdo->getSetting('tablepergroup')) {
	exit("Tables per groups is not enabled, quitting!");
}

$groups = $pdo->queryDirect('SELECT id FROM groups WHERE active = 1 OR backfill = 1');

if ($groups === false) {
	echo "No active groups. Fix not needed.\n";
} else {

	$queries = array();

	switch ($argv[1]) {
		case 1:
			// Drop this index, as we will recreate it as a unique.
			$queries['single'] = 'ALTER TABLE binaries_%d DROP INDEX ix_binary_binaryhash';
			// Recreate the index as unique so we can use on duplicate key update, saving select / update query.
			$queries['single'] = 'ALTER IGNORE TABLE binaries_%d ADD UNIQUE INDEX ix_binary_binaryhash(binaryhash)';
			break;
		default:
			exit();
	}

	$groupCount = $groups->rowCount();
	if (count($queries) && $groupCount) {
		foreach ($groups as $group) {
			echo 'Fixing group ' . $group['id'] . PHP_EOL;
			foreach ($queries as $type => $query) {
				switch ($type) {
					case 'single':
						$pdo->queryExec(sprintf($query, $group['id']), true);
						break;
					case 'double':
						$pdo->queryExec(sprintf($query, $group['id'], $group['id']), true);
						break;
				}
			}
			echo 'Finished fixing group ' . $group['id'] . ', ' . (--$groupCount) . ' to go!' .PHP_EOL;
		}
	}
	echo 'All done!' . PHP_EOL;
}