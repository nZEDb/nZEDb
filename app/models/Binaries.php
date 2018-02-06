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
 * @copyright 2018 nZEDb
 */

namespace app\models;

use app\models\Settings;


class Binaries extends \app\extensions\data\Model
{
	public static function create(array $data = [], array $options = [])
	{
		$default = [
			'group' => null	// Id of group or null if TPG not enabled.
		];
		$options += $default;

		if (self::tpg()) {
			if ($options['group'] === null) {
				throw new \ErrorException('Table Per Group is enabled, but no group id was provided');
			}
			// TODO handle table translation here.
//		} else {
		}

	}
}
