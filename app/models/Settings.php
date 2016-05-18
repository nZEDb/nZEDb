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

namespace app\models;

/**
 * Settings - model for settings table.
 *
 * li3 app completely ignore the 'setting' column and only uses 'section', 'subsection', and 'name'
 * for finding values/hints.
 *
 * @package app\models
 */
class Settings extends \lithium\data\Model
{

	public $validates = [];

	protected $_meta = [
		'key' => ['section', 'subsection', 'name']
	];

	public static function init()
	{
		static::finder('setting',
			function ($params, $next) {

				if (!is_array($params['options']['conditions'])) {
					$params['options']['conditions'] = self::dottedToArray($params['options']['conditions']);
				} elseif (count($params) == 1) {
					$params['options']['conditions'] = self::dottedToArray($params['options']['conditions'][0]);
				}
				$params['type'] = 'first';

				$array = array_diff_key(
					$params['options'],
					array_fill_keys(['conditions', 'fields', 'order', 'limit', 'page'], 0)
				);
				$params['options'] = array_diff_key($params['options'], $array);
				$params['options']['fields'] = ['value', 'hint'];


				$result = $next($params);

				return $result;
			}
		);
	}

	/**
	 * Return the value of supplied setting.
	 * The setting can be either a a normal condition array for the custom 'setting' finder or a
	 * dotted string notation setting.
	 * Be aware that this method only returns the first of any values found, so make sure your
	 * $setting produces a unique result.
	 *
	 * @param      $setting
	 * @param bool $returnAlways Indicates if the method should throw an exception (false) or return
	 *                           null on failure. Defaults to throwing an exception.
	 *
	 * @return string|null		 The setting's value, or null on failure IF 'returnAlways' is true.
	 * @throws \Exception
	 */
	public static function value($setting, $returnAlways = false)
	{
		$result = Settings::find('setting', ['conditions' => $setting, 'fields' => ['value']]);

		if ($result->count()) {
			$value = $result->data()[0]['value'];
		} else if ($returnAlways === false) {
			throw new \Exception("Unable to fetch setting from Db!");
		} else {
			$value = null;
		}

		return $value;
	}

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
		//var_dump($result);

		return $result;

	}
}

Settings::init();
