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

require_once nZEDb_CORE . 'autoloader.php';
require_once nZEDb_LIBS . 'autoloader.php';

require_once nZEDb_WWW . '/SPLClassLoader.php';

$paths = array(nZEDb_ROOT, nZEDb_WWW . 'pages', SMARTY_DIR, SMARTY_DIR . 'plugins', SMARTY_DIR . 'sysplugins');
$classLoader = new \SplClassLoader(null, $paths);
$classLoader->register();

?>
