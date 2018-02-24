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
	protected $_meta = [
		'connection' => 'information_schema',
		'key'        => null,
		'source'     => 'TABLES',
	];

	public $validates = [];

	public static function cpb(string $base): array
	{
		if (!in_array($base, ['binaries', 'collections', 'missed_parts', 'parts'])) {
			throw new \InvalidArgumentException("Argument must be one of: 'binaries', 'collections', 'missed_parts', or 'parts'");
		};

		$tables = self::find('tpg', ['base' => $base]);
		$list = [];
		foreach ($tables as $table) {
			$list[] = $table->data()['table_name'];
		}
		natsort($list);

		return $list;
	}

	public static function init()
	{
		static::finder('tpg',
			function ($params, $next) {
				$params = [
					'type'    => 'all',
					'options' => [
						'conditions' => [
							'table_name'   => [
								'LIKE' => "{$params['options']['base']}_%"
							],
							'table_schema' => Connections::get('default',
								['config' => true])['database'],
						],
						'fields'     => [
							'table_name'
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
