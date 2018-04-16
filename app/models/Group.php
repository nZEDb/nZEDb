<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
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

class Group extends \lithium\data\Model
{
	public $validates = [
		'name' => [
			[
				'required' => true
			],
			[
				'notEmpty',
				'message' => 'The group\'s name is required to create a new entry.'
			]
		],
	];

	public static function findIdFromName($name, array $options = [])
	{
		$defaults = [
			'create'      => false,
			'description' => 'Auto-created by Group::' . __METHOD__ . ' model',
		];
		$options += $defaults;

		$group = self::find('first',
			[
				'fields'     => ['id'],
				'conditions' => ['name' => $name],
			]
		);

		if ($group === null && $options['create'] === true) {
			$group = self::createMissing($name,
				[
					'description'	=> $options['description']
				]
			);
		}

		return $group;
	}

	protected static function createMissing(array $data)
	{
		$defaults = [
			'active'   => false,
			'backfill' => false,
			'description' => 'Auto-created by Group::' . __METHOD__,
		];
		$data += $defaults;

		if (!isset($data['name'])) {
			throw new \InvalidArgumentException("");
		}

		$group = self::create($data);

		$group->save();

		return $group;
	}
}

?>
