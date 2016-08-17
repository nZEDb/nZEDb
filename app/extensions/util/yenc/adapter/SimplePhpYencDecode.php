<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 *
 * @link      <http://www.gnu.org/licenses/>.
 * @author    niel
 * @copyright 2016 nZEDb
 */

namespace app\extensions\util\yenc\adapter;

use app\extensions\util\Yenc;


class SimplePhpYencDecode extends \lithium\core\Object
{
	public static function decode(&$text, $ignore = false)
	{
		return \simple_yenc_decode($text);
	}

	public static function decodeIgnore(&$text)
	{
		self::decode($text, true);
	}

	/**
	 * Determines if this adapter is enabled by checking if the `nzedb_yenc` extension is loaded.
	 *
	 * @return boolean Returns `true` if enabled, otherwise `false`.
	 */
	public static function enabled()
	{
		return extension_loaded('simple_php_yenc_decode');
	}

	public static function encode($data, $filename, $lineLength, $crc32)
	{
		return Yenc::encode($data, $filename, $lineLength, $crc32, ['name' => 'php']);
	}

	protected function _init()
	{
		parent::_init();
	}
}

?>
