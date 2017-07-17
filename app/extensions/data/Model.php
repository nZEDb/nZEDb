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
use lithium\data\Source;

class Model extends \lithium\data\Model
{
	public static function import(array $options = [])
	{
		$source = static::connection();
		$options['table'] = static::meta('source');

		try {
			return $source->import($options);
		} catch (\BadMethodCallException $e) {
			throw new \BadMethodCallException(
				'Table Imports can only be applied to MySql adapters!',
				$e
			);
		}
	}

	public static function isModified($preEntry)
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
