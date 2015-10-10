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

spl_autoload_register(
	function ($class) {
		// Only continue if the class is in our namespace.
		if (strpos($class, 'nzedb\\') === 0) {
			// Replace namespace separators with directory separators in the class name, append
			// with .php
			$file = nZEDb_ROOT . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';

			// if the file exists, require it
			if (file_exists($file)) {
				require_once $file;
			}
		}
	}
);

?>
