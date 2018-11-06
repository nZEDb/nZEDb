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


use lithium\data\Entity;


/**
 * @method static array all(array $options = []) Returns all rows from the model, that match the $options.
 * @method static array count(array $optionss = []) Counts the rows, that match the provided options.
 * @method static array first(array $optionss = []) Returns the first match for the provided options.
 */
class Model extends \lithium\data\Model
{
	/**
	 * The number of rows found by the last query.
	 *
	 * @return int
	 */
	public static function foundRows()
	{
		$result = static::Find('first', ['fields' => 'FOUND_ROWS() AS found']);

		return $result->data()['found'];
	}

	public static function isModified($preEntry) : bool
	{
		if (!($preEntry instanceof Entity)) {
			$test = get_class($preEntry);
			$test = $test ?: 'non-object';
			throw new \InvalidArgumentException('$preEntry must be an object derived from the Lithium Entity class, a "' . $test . '" was passed instead.');
		}

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
}
