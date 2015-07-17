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
require_once realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'initialise.php');
require_once 'autoloader.php';

use nzedb\config\Configure;

try {
	$config = new Configure('smarty');
} catch (\RuntimeException $e) {
	if ($e->getMessage() ==
		"Unable to load configuration file 'config.php'. Make sure it has been created and contains correct settings.") {
		if (is_dir("install")) {
			header("location: install");
			exit();
		}
	}
}

if (function_exists('ini_set') && function_exists('ini_get')) {
	ini_set('include_path', nZEDb_WWW . PATH_SEPARATOR . ini_get('include_path'));
}

// Path to smarty files. (not prefixed with nZEDb as the name is needed in smarty files).
define('SMARTY_DIR', nZEDb_LIBS . 'smarty' . DS);

$www_top = str_replace("\\", "/", dirname($_SERVER['PHP_SELF']));
if (strlen($www_top) == 1) {
	$www_top = "";
}

// Used everywhere an href is output, includes the full path to the nZEDb install.
define('WWW_TOP', $www_top);

?>
