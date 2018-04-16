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


use lithium\data\Connections;


class Tables extends \app\extensions\data\Model
{
	public $validates = [];

	protected $_meta = [
		'connection' => 'information_schema',
		'key'        => null,
		'source'     => 'TABLES',
	];

	private static $tpgTables = ['binaries', 'collections', 'missed_parts', 'parts'];


	public static function tpg(string $prefix) : array
	{
		if (! \in_array($prefix, self::tpgTables, false)) {
			throw new \InvalidArgumentException("Argument must be one of: 'binaries', 'collections', 'missed_parts', or 'parts'");
		};

		$tables = self::find('tpg', ['prefix' => $prefix])->data();
		$list = [];
		/* @var $tables string[][] */
		foreach ($tables as $table) {
			$list[] = $table['TABLE_NAME'];
		}
		usort($list, 'strnatcmp');

		return $list;
	}


	public static function init()
	{
		static::finder('tpg',
			function ($params, $next) {
				if (! isset($params['options']['prefix'])) {
					throw new \InvalidArgumentException('The option "prefix" is required for the TPG finder');
				}
				$params = [
					'type'    => 'all',
					'options' => [
						'conditions' => [
							'TABLE_NAME'   => [
								'LIKE' => "{$params['options']['prefix']}_%"
							],
							'TABLE_SCHEMA' => Connections::get('default',
								['config' => true])['database'],
						],
						'fields'     => [
							'TABLE_NAME'
						],
					],
				];

				$array = array_diff_key(
					$params['options'],
					array_fill_keys(['conditions', 'fields', 'order', 'limit', 'page'], 0)
				);
				$params['options'] = array_diff_key($params['options'], $array);

				$result = $next($params);

				return $result;
			}
		);
	}
}

Tables::init();
?>
