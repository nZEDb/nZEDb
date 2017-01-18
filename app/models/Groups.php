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
namespace app\models;


/**
 * Groups - class for groups table.
 *
 * @package app\models
 */
class Groups extends \lithium\data\Model
{
	/**
	 * Convenience method to return the 'id' of supplied group name.
	 *
	 * @param      $group			Name of group to find 'id' of.
	 * @param bool $returnAlways	Whether the method should return (null) regardless. Default
	 *								is to throw an exception.
	 *
	 * @return integer|null         The group's id number, or null if not found and return is
	 *								required.
	 * @throws \Exception
	 */
	public static function findID($group, $returnAlways = false)
	{
		$result = Groups::find('first', ['conditions' => ['name' => $group]]);

		if ($result !== false && $result->count() > 0) {
			$primary = $result->data()[0]['id'];
		} else {
			if ($returnAlways === false) {
				throw new \Exception("No group entry!");
			} else {
				$primary = null;
			}
		}

		return $primary;
	}
}
