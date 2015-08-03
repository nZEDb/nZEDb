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
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'indexer.php');

use nzedb\utility\Misc;
use nzedb\utility\Versions;

if (!Misc::isCLI()) {
	exit;
}

$vers = new Versions();
if (isset($argc) && $argc > 1 && isset($argv[1]) && $argv[1] == true) {
	echo $vers->out->header("Checking versions...");

	if ($vers->checkAll()) {
		$vers->save();
	} else {
		echo "No changes detected.\n";
		output($vers);
	}
} else {
	$vers->checkAll(false);
	echo "Version info in file:\n";
	output($vers);
}

function output($vers)
{
	echo "  Commit: " . $vers->out->primary($vers->getCommit());
	echo "SQL   DB: " . $vers->out->primary($vers->getSQLPatchFromDb());
	echo "SQL File: " . $vers->out->primary($vers->getSQLPatchFromFiles());
	echo "     Tag: " . $vers->out->primary($vers->getTagVersion());
}

?>
