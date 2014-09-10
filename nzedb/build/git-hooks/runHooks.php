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
 * @copyright 2014 nZEDb
 */
define('GIT_PRE_COMMIT', true);
require_once realpath(dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'www' .
					  DIRECTORY_SEPARATOR . 'automated.config.php');

echo "Running pre-commit hooks\n";

$error = false;

// TODO Add code here to check permissions on staged files.
//$files = file(nZEDb_ROOT . 'nzedb/build/git-hooks'), FILE_IGNORE_NEW_LINES);
//foreach ($files as $file) {
//	echo "Filename: $file\n";
//}

/**
 * Add all hooks BEFORE the versions are updated so they can be skipped on any errors
 */
if ($error === false) {
	$git = new \nzedb\utility\Git();
	$branch = $git->active_branch();
	if (in_array($branch, $git->mainBranches())) { // Only update versions, patches, etc. on specific branches to lessen conflicts
		try {
			// Run DbUpdates to make sure we're up to date.
			$DbUpdater = new \nzedb\db\DbUpdate(['git' => $git]);
			$DbUpdater->newPatches(['safe' => false]);
		} catch (\Exception $e) {
			$error = 1;
			echo "Error while checking patches!\n";
		}

		if ($error === false) {
			try {
				$vers = new \nzedb\utility\Versions();
				$vers->checkAll();
				$vers->save();
				$git->add(nZEDb_VERSIONS);
			} catch (\Exception $e) {
				$error = 1;
				echo "Error while checking versions!\n";
			}
		}
	} else {
		echo "not 'dev', 'next-master', or 'master' branch, skipping version/patch updates\n";
	}
} else {
	echo "Error in pre-commit hooks!!\n";
}

exit($error);
?>
