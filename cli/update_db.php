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
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'www' . DIRECTORY_SEPARATOR . 'config.php';

use nzedb\db\DbUpdate;
use nzedb\utility\Utility;

if (!Utility::isCLI()) {
	exit;
}

if (isset($argc) && $argc > 1 && isset($argv[1]) && $argv[1] == true) {
	$backup  = (isset($argv[2]) && $argv[2] == 'safe') ? true : false;
	$updater = new DbUpdate(['backup' => $backup]);
	echo $updater->log->header("Db updater starting ...");
	$patched = $updater->processPatches(['safe' => $backup]);

	if ($patched > 0) {
		echo $updater->log->info("$patched patch(es) applied.");

		$smarty  = new Smarty();
		$cleared = $smarty->clearCompiledTemplate();
		if ($cleared) {
			$msg = "The smarty template cache has been cleaned for you\n";
		} else {
			$msg = "You should clear your smarty template cache at: " . SMARTY_DIR . "templates_c\n";
		}
		$updater->log->info($msg);
	}
} else {
	echo "Usage: php update_db.php true";
}


?>
