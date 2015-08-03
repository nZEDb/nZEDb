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

require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'indexer.php';

use nzedb\db\DbUpdate;
use nzedb\utility\Git;
use nzedb\utility\Misc;

if (!Misc::isCLI()) {
	exit;
}

$error = false;
$git = new Git();
$branch = $git->active_branch();

if (in_array($branch, $git->mainBranches())) {
	// Only update patches, etc. on specific branches to lessen conflicts
	try {
		// Run DbUpdates to make sure we're up to date.
		$DbUpdater = new DbUpdate(['git' => $git]);
		$DbUpdater->newPatches(['safe' => false]);
	} catch (\Exception $e) {
		$error = 1;
		echo "Error while checking patches!\n";
		echo $e->getMessage() . "\n";
	}
}

exit($error);

?>
