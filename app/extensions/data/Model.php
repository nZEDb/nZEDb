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
 * @copyright 2017 nZEDb
 */

namespace app\extensions\data;


use app\models\Settings;
use lithium\data\Entity;


class Model extends \lithium\data\Model
{
	protected $_meta = [
		'tpg' => null
	];

	/**
	 * Checks if a newly created `Entity` has been modified
	 *
	 * @param $preEntry
	 *
	 * @throws \InvalidArgumentException
	 * @return bool
	 */
	public static function isModified(Entity $preEntry): bool
	{
		$modified = false;
		foreach ($preEntry->modified() as $field => $value) {
			if ($value) {
				if (nZEDb_DEBUG) {
					echo "Changed: $field\n";
				}
				$modified = true;
			}
		}

		return $modified;
	}

	/**
	 * Checks if Table Per Group mode is enabled.
	 *
	 * @throws \Exception	An exception is thrown by the Settings::value call if the value cannot
	 * 						be retrieved.
	 * @return boolean	Returns true or false indicating whether TPG mode is enabled.
	 */
	protected static function tpg(): boolean
	{
		$tpg = self::meta('tpg');
		if ($tpg === null) {
			$tpg = Settings::value('..tablepergroup') == 1 ? true : false;
			self::meta('tpg', $tpg);
		}

		return $tpg;
	}
}
