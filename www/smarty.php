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
 * @copyright 2015 - 2019 nZEDb
 */
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'nZEDbBase.php';
require_once nZEDb_ROOT . 'nzedb' . DIRECTORY_SEPARATOR . 'constants.php';
require_once nZEDb_ROOT . 'vendor' . DS . 'autoload.php';

if (!file_exists(nZEDb_CONFIGS . 'install.lock')) {
	header('Location: install');
	exit();
}

if (function_exists('ini_set') && function_exists('ini_get')) {
	ini_set('include_path', nZEDb_WWW . PATH_SEPARATOR . ini_get('include_path'));
}

$www_top = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
if (strlen($www_top) === 1) {
	$www_top = '';
}

// Used everywhere an href is output.
define('WWW_TOP', $www_top);

use nzedb\config\Configure;

try {
	$config = new Configure('smarty');
} catch (\RuntimeException $e) {
	if ($e->getCode() == 1) {
		if (file_exists(nZEDb_WWW . 'config.php')) {
			echo "Move: .../www/config.php to .../configuration/config.php<br />\n Remove any line that says require_once 'automated.config.php';<br />\n";
			if (file_exists(nZEDb_WWW . 'settings.php')) {
				echo "Move: .../www/settings.php to  .../configuration/settings.php<br />\n";
			}
			exit();
		}
	}
}

?>
