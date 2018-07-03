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
use lithium\storage\Cache;


class Tables extends \app\extensions\data\Model
{
	public $validates = [];

	protected $_meta = [
		//'connection' => 'information_schema',
		'connection' => 'default',
		'key'        => null,
		'source'     => 'TABLES',
	];

	protected $pdo;

	protected static $tpgTables = ['binaries', 'collections', 'missed_parts', 'parts'];


	public static function createTPGTablesForId(int $groupId)
	{
		$source = static::connection();
		$pdo = $source->connection;

		$tpgTables =& static::$tpgTables;
		$result = true;
		/* @var $tpgTables string[][] */
		foreach($tpgTables as $prefix) {
			$result = $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}_{$groupId} LIKE {$prefix}");
			if ($result === false) {
				break;
			}
		}

		return $result !== false;
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

	/**
	 * Get the names of the collections/binaries/parts/missed_part tables.
	 * Try to create new tables for the groups_id if they are missing. If we fail, log the error and
	 * exit.
	 *
	 * @param int|string $groupId
	 *
	 * @return array The table names.
	 * @throws \RuntimeException If a new table could not be created.
	 */
	public static function getTPGNamesFromId($groupId) : array
	{
		// Try cache first.
		$names = Cache::read('default', 'tpgNames_' . $groupId);
		if (!empty($names)) {
			return $names;
		}

		if (nZEDb_ECHOCLI && static::createTPGTablesForId($groupId) === false) {
			throw new \RuntimeException(
				"There was a problem creating new TPG tables for this group ID: '$groupId'"
			);
		}

		$tables = [
			'cname' => 'collections_' . $groupId,
			'bname' => 'binaries_' . $groupId,
			'pname' => 'parts_' . $groupId,
			'prname'=> 'missed_parts_' . $groupId
		];

		Cache::write('default', 'tpgNames_' . $groupId, $tables, '+5 minutes');

		return $tables;
	}

	public static function tpg(string $prefix): array
	{
		if (! \in_array($prefix, static::$tpgTables, false)) {
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
}

Tables::init();
?>
