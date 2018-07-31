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

use yenc\yenc;


class NzedbYenc extends \lithium\core\BaseObject
{
	public static function decode(&$text)
	{
		return (new yenc())->decode($text);
	}

	public static function decodeIgnore(&$text)
	{
		return (new yenc())->decode($text, true);
	}

	/**
	 * Determines if this adapter is enabled by checking if the `yenc` extension is loaded.
	 *
	 * @return boolean Returns `true` if enabled, otherwise `false`.
	 */
	public static function enabled()
	{
		return extension_loaded('yenc');
	}

	public static function encode($data, $filename, $lineLength = 128)
	{
		return (new yenc())->encode($data, $filename, $lineLength);
	}

	protected function _init()
	{
		parent::_init();
	}
}

?>
