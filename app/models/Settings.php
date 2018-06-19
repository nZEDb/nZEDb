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

use nzedb\utility\Text;


/**
 * Settings - model for settings table.
 *
 * li3 app completely ignore the 'setting' column and only uses 'section', 'subsection', and 'name'
 * for finding values/hints.
 *
*@package app\models
 */
class Settings extends \app\extensions\data\Model
{
	const REGISTER_STATUS_OPEN = 0;

	const REGISTER_STATUS_INVITE = 1;

	const REGISTER_STATUS_CLOSED = 2;

	const REGISTER_STATUS_API_ONLY = 3;

	const ERR_BADUNRARPATH = -1;

	const ERR_BADFFMPEGPATH = -2;

	const ERR_BADMEDIAINFOPATH = -3;

	const ERR_BADNZBPATH = -4;

	const ERR_DEEPNOUNRAR = -5;

	const ERR_BADTMPUNRARPATH = -6;

	const ERR_BADNZBPATH_UNREADABLE = -7;

	const ERR_BADNZBPATH_UNSET = -8;

	const ERR_BAD_COVERS_PATH = -9;

	const ERR_BAD_YYDECODER_PATH = -10;

	public $validates = [
		'section' => [
			[
				'required'	=> false
			]
		],
		'subsection' => [
			[
				'required' => false
			]
		],
		'name' => [
			[
				'required' => true
			],
			[
				'notEmpty',
				'message' => 'You must supply a name for this setting.'
			]
		],
		'value' => [
			[
				'required' => true
			]
		],
		'hint' => [
			[
				'required' => true
			],
			[
				'notEmpty',
				'message' => 'You must supply a hint/description for this setting.'
			]
		],
		'setting' => [
			[
				'required' => true
			],
			[
				'notEmpty',
				'message' => 'You must supply a name for this setting.'
			]
		],
	];

	protected $_meta = [
		'key' => ['section', 'subsection', 'name']
	];

	public static function hasAllEntries($console = null)
	{
		$filepath = Text::pathCombine(['db', 'schema', 'data', '10-settings.tsv'], nZEDb_RES);
		if (!file_exists($filepath)) {
			throw new \InvalidArgumentException("Unable to find {$filepath}");
		}
		$settings = file($filepath);

		if (!\is_array($settings)) {
			throw new \InvalidArgumentException("Settings is not an array!");
		}

		$setting = [];
		$dummy = array_shift($settings);
		$result = false;
		if ($dummy !== null) {
			if ($console !== null) {
				$console->primary("Verifying settings table...");
				$console->info("(section, subsection, name):");
			}
			$result = true;
			foreach ($settings as $line) {
				$message = '';
				list($setting['section'], $setting['subsection'], $setting['name']) =
					explode("\t", $line);

				$value = Settings::value(
					[
						'section'    => $setting['section'],
						'subsection' => $setting['subsection'],
						'name'       => $setting['name']
					],
					true);
				if ($value === null) {
					$result = false;
					$message = "error";
				}

				if ($message != '' && $console !== null) {
					$console->out(" {$setting['section']}, {$setting['subsection']}, {$setting['name']}: "
						. "MISSING!");
				}
			}
		}

		return $result;
	}

	public static function init()
	{
		static::finder('setting',
			function ($params, $next) {

				$params['options']['conditions'] = self::settingToArray($params['options']['conditions']);
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
	 * Return a tree-like array of all or selected settings.
	 *
	 *	@param array $options	Options array for Settings::find() i.e. ['conditions' => ...].
	 * @param bool $excludeUnsectioned If rows with empty 'section' field should be excluded.
	 *		Note this doesn't prevent empty 'subsection' fields.
	 * @return array
	 * @throws \RuntimeException
	 */
	public static function toTree(array $options = [], $excludeUnsectioned = true)
	{
		$results = empty($options) ?
			Settings::find('all') :
			Settings::find('all', $options);
		$results = $results->data();

		$tree = [];
		if (is_array($results)) {
			foreach ($results as $result) {
				if (!empty($result['section']) || !$excludeUnsectioned) {
					$tree[$result['section']][$result['subsection']][$result['name']] =
						['value' => $result['value'], 'hint' => $result['hint']];
				}
			}
		} else {
			throw new \RuntimeException(
				"NO results from Settings table! Check your table has been created and populated."
			);
		}

		return $tree;
	}

	/**
	 * Checks the supplied parameter is either a string or an array with single element. If
	 * either the value is passed to Settings::dottedToArray() for conversion. Otherwise the
	 * value is returned unchanged.
	 *
	 * @param string|array $setting    Setting array/string to check.
	 *
	 * @return array|boolean
	 */
	public static function settingToArray($setting)
	{
		if (!is_array($setting)) {
			$setting = self::dottedToArray($setting);
		} elseif (count($setting) == 1) {
			$setting = self::dottedToArray($setting[0]);
		}

		return $setting;
	}

	/**
	 * Return the value of supplied setting.
	 * The setting can be either a normal condition array for the custom 'setting' finder or a
	 * dotted string notation setting. Note that dotted notation will be converted to an array by
	 * the custom finder, so it will be slower: Explicitly use the array format if speed it paramount.
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

		if ($result !== false && $result->count() > 0) {
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

		return $result;

	}
}

Settings::init();
?>
