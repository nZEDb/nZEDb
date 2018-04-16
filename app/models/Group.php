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
			'require' =>
				[ 'required' => true ],
				[
					'notEmpty',
					'message' => 'The group\'s name is required to create a new entry.'
				],
		],
		'backfill_target' => [
			'number' => [ 'numeric' ],
		],
		'first_record' => [
			'number' => [ 'numeric' ],
		],
		'last_record' => [
			'number' => [ 'numeric' ],
		],
		'minfilestoformrelease' => [
			'number' => [ 'numeric' ],
		],
		'minsizetoformrelease' => [
			'number' => [' numeric' ],
		],
		'active' => [
			'bool' => [ 'boolean' ]
		],
		'backfill' => [
			'bool' => ['boolean' ]
		],
		'description' => [
			'require' =>
				['required' => true],
				[
					'notEmpty',
					'message' => "The group's name is required to create a new entry."
				],
		],
		'id' => [
			'index' => ['numeric']
		]
	];

	/**
	 * Create a new Group entry.
	 *
	 * @param array $data	Column names/value pairs. Valid columns are:
	 * 						id
	 * 						name
	 * 						backfill_target
	 * 						first_record
	 * 						first_record_postdate
	 * 						last_record
	 * 						last_record_postdate
	 * 						last_updated
	 * 						minfilestoformrelease
	 * 						minsizetoformrelease
	 * 						active
	 * 						backfill
	 * 						description
	 *                        Warning: setting 'id' is allowed, but not recommended unless you can
	 *                      be certain that the id is available.
	 * @param array $options
	 *
	 * @return object
	 */
	public static function create(array $data = [], array $options = [])
	{
		$defaults = [
			'active'          => false,
			'backfill'        => false,
			'backfill_target' => 1,
			'description'     => 'Auto-created by Group::' . __METHOD__,
		];
		$data += $defaults;

		if (!isset($data['name'])) {
			throw new \InvalidArgumentException("The group's name is required to create a new entry.");
		}

		return parent::create($data, $options);
	}

	/**
	 * Finds a groups' entry using its name. Optionally creates the group entry if it does not
	 * exist.
	 *
	 * @param       $name
	 * @param array $options
	 *
	 * @return int|null
	 */
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
			$group = static::create(
				[
					'name' => $name,
					'description' => $options['description']
				]
			);
			$group->save();
		}

		return $group === null ? null : $group->id;
	}
}

?>
