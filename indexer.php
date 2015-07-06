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
require_once 'SPLClassLoader.php';
require_once 'constants.php';

use nzedb\config\Config;

$classLoader = new SplClassLoader('nzedb', [__DIR__ . DIRECTORY_SEPARATOR . 'nzedb']);
$classLoader->register();

$config = new Config('indexer');

define('HAS_WHICH', nzedb\utility\Utility::hasWhich() ? true : false);

// Check if they updated config.php for the openssl changes. Only check 1 to save speed.
if (!defined('nZEDb_SSL_VERIFY_PEER')) {
	define('nZEDb_SSL_CAFILE', '');
	define('nZEDb_SSL_CAPATH', '');
	define('nZEDb_SSL_VERIFY_PEER', '0');
	define('nZEDb_SSL_VERIFY_HOST', '0');
	define('nZEDb_SSL_ALLOW_SELF_SIGNED', '1');
}

?>
