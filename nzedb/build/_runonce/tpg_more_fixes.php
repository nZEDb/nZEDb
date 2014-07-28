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
	// Drop this index, as we will recreate it as a unique.
	$queries[] = 'ALTER TABLE parts_%d DROP INDEX ix_binary_binaryhash';
	// Recreate the index as unique so we can use on duplicate key update, saving select / update query.
	$queries[] = 'ALTER TABLE parts_%d ADD UNIQUE INDEX ix_binary_binaryhash(binaryhash)';

	$groupCount = $groups->rowCount();
	foreach ($groups as $group) {
		echo 'Fixing group ' . $group['id'] . PHP_EOL;
		foreach ($queries as $query) {
			$pdo->queryExec(sprintf($query, $group['id']), true);
		}
		echo 'Finished fixing group ' . $group['id'] . ', ' . (--$groupCount) . ' to go!' .PHP_EOL;
	}
	echo 'All done!' . PHP_EOL;
}