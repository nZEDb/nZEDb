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

namespace app\extensions\util;


class Yenc extends \lithium\core\Adaptable
{
	/**
	 * `Libraries::locate()`-compatible path to adapter for this class.
	 *
	 * @see lithium\core\Libraries::locate()
	 * @var string Dot-delimited path.
	 */
	protected static $_adapters = 'adapter.extensions.util.yenc';

	/**
	 * Contains adapter configurations for `yEnc` adapter.
	 *
	 * @var array
	 */
	protected static $_configurations = [];

	public static function decode($string, $ignore = false, array $options = [])
	{
		$options += ['name' => 'default'];
		return static::adapter($options['name'])->decode($string);
	}

	public static function encode($data, $filename, $lineLength = 128, $crc32 = true, array $options = [])
	{
		$options += ['name' => 'default'];

		return static::adapter($options['name'])->encode($data, $filename, $lineLength, $crc32);
	}
}
