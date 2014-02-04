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
define('GIT_PRE_COMMIT', true);
require_once realpath(dirname(__FILE__) . '/../Versions.php');

$error = false;
echo "Running pre-commit hooks\n";


/**
 * Add all hooks BEFORE the versions are updated so they can be skipped on any errors
 */
if ($error === false) {
	exec("git branch -a | grep \*", $output);
	if ($output[0] == '* dev') { // Only update versions on the dev branch to lessen conflicts
		$vers = new Versions();
		$vers->checkAll();
		$vers->save();
		passthru('git add ' . nZEDb_VERSIONS);
	} else {
		echo "not dev branch, skipping version updates\n";
	}
}
exit($error);
?>