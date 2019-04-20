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
 * @copyright 2019 nZEDb
 */

namespace zed\db;

use Cake\ORM\TableRegistry;


class Settings
{
	public const REGISTER_STATUS_OPEN = 0;

	public const REGISTER_STATUS_INVITE = 1;

	public const REGISTER_STATUS_CLOSED = 2;

	public const REGISTER_STATUS_API_ONLY = 3;

	public const ERR_BADUNRARPATH = -1;

	public const ERR_BADFFMPEGPATH = -2;

	public const ERR_BADMEDIAINFOPATH = -3;

	public const ERR_BADNZBPATH = -4;

	public const ERR_DEEPNOUNRAR = -5;

	public const ERR_BADTMPUNRARPATH = -6;

	public const ERR_BADNZBPATH_UNREADABLE = -7;

	public const ERR_BADNZBPATH_UNSET = -8;

	public const ERR_BAD_COVERS_PATH = -9;

	public const ERR_BAD_YYDECODER_PATH = -10;


	/**
	 * @param $setting
	 *
	 * @return array|bool
	 */
	protected static function dottedToArray($setting)
	{
		$result = [];
		if (is_string($setting)) {
			$array = explode('.', $setting);
			$count = count($array);
			if ($count > 3) {
				return false;
			}

			while (3 - $count > 0) {
				array_unshift($array, '');
				$count++;
			}
			list(
				$result['section'],
				$result['subsection'],
				$result['name'],
				) = $array;
		} else {
			return false;
		}

		return $result;
	}

	/**
	 * @param array $setting
	 *
	 * @return string
	 */
	protected static function find(array $setting): string
	{
		$settings = TableRegistry::getTableLocator()->get('Settings');
		$query = $settings->find('all',
			[
				'fields'     => 'value',
				'conditions' => $setting
			])
			->first();

		return $query->value;
	}

	/**
	 * @param null $setting
	 *
	 * @return string
	 */
	public static function value($setting = null): string
	{
		switch (true) {
			case \is_string($setting):
				$setting = self::dottedToArray($setting);

				if ($setting === false) {
					throw new \RuntimeException('Error converting dotted string to an array!');
				}
				break;

			case \is_array($setting):
				if (!\array_key_exists('section') ||
					!\array_key_exists('subsection') ||
					!\array_key_exists('name'))
					throw new \InvalidArgumentException('This method requires an array or a dotted string of `section`, `subsection`, and `name` field values.');
				break;

			case $setting === null:
			default:
				throw new \InvalidArgumentException('This method requires an array or a dotted string of `section`, `subsection`, and `name` field values.');
				break;
		}

		return self::find($setting);
	}
}
