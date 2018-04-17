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

class Groups extends \lithium\data\Model
{
	public $belongsTo = ['Releases', 'ReleasesGroups'];

	public $validates = [
		'active'   => [
			'bool' => ['boolean']
		],
		'backfill' => [
			'bool' => ['boolean']
		],
		'backfill_target' => [
			'number' => [ 'numeric' ],
		],
		'description' => [
			'require' =>
				['required' => true],
			[
				'notEmpty',
				'message' => "The group's name is required to create a new entry."
			],
		],
		'first_record' => [
			'number' => [ 'numeric' ],
		],
		//'id' => [
		//	'index' => ['numeric']
		//]
		'last_record' => [
			'number' => [ 'numeric' ],
		],
		//'minfilestoformrelease' => [
		//	'number' => [ 'numeric' ],
		//],
		//'minsizetoformrelease' => [
		//	'number' => [' numeric' ],
		//],
		'name' => [
			'require' =>
				['required' => true],
			[
				'notEmpty',
				'message' => 'The group\'s name is required to create a new entry.'
			],
		],
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
	 * @return \lithium\data\entity\Entity
	 * @throws \InvalidArgumentException if the groups' name is omitted.
	 */
	public static function create(array $data = [], array $options = [])
	{
		$defaults = [
			'active'          => false,
			'backfill'        => false,
			'backfill_target' => 1,
			'description'     => 'Auto-created by Groups::' . __METHOD__,
			'first_record'    => 0,
			'last_record'     => 0,
			//'minfilestoformrelease' => 0,
			//'minsizetoformrelease'  => 0,
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

	public static function getActive()
	{
		return static::find('all',
			[
				'fields' => [
					'id',
					'name',
					'backfill_target',
					'first_record',
					'first_record_postdate',
					'last_record',
					'last_record_postdate',
					'last_updated',
					'minfilestoformrelease',
					'minsizetoformrelease',
					'active',
					'backfill',
					'description',
				],
				'conditions' => ['active' => true],
			]
		);

		return $active;
	}

	public static function getActiveIDs()
	{
		return static::find('all',
			[
				'fields'     => ['id'],
				'conditions' => ['active' => true],
			]
		);
	}

	public static function getBackfilling(string $order)
	{
		switch (\strtolower($order)) {
			case '':
			case 'normal':
				$order = 'name ASC';
				break;
			case 'date':
				$order = 'first_record_postdate DESC';
				break;
			default:
				throw new \InvalidArgumentException("Order must be 'normal' or 'date'");
		}

		return static::find('all',
			[
				'fields'     => [
					'id',
					'name',
					'backfill_target',
					'first_record',
					'first_record_postdate',
					'last_record',
					'last_record_postdate',
					'last_updated',
					'minfilestoformrelease',
					'minsizetoformrelease',
					'active',
					'backfill',
					'description',
				],
				'conditions' => ['active' => true],
				'order'      => $order
			]
		);
	}

	/**
	 * Checks group name is standard and replaces the shorthand prefix if is exists.
	 *
	 * @param string $name The full name of the usenet group being evaluated
	 *
	 * @return string|bool The name of the group replacing shorthand prefix or false if groupname was malformed
	 */
	public static function isValidName(string $name)
	{
		if (preg_match('/^([\w-]+\.)+[\w-]+$/i', $name)) {

			return preg_replace('/^a\.b\./i', 'alt.binaries.', $name, 1);
		}

		return false;
	}

	/**
	 * @param        $id     id of group to update.
	 * @param string $column 'active' or 'backfill'
	 * @param int    $status
	 *
	 * @return string
	 *
	 * @throws \InvalidArgumentException if column is not one of the two permitted.
	 */
	public static function updateStatus($id, string $column, int $status = 0) : string
	{
		if (! \in_array($column, ['active', 'backfill'])) {
			throw new \InvalidArgumentException("Only 'active' and 'backfill' status can be updated.");
		}

		$group = static::find('first',
			[
				'conditions' => ['id' => $id],
				'fields'	=> ['id', $column]
			]
		);

		$group->$column = $status;
		$group->save();

		$text = $status == 0 ? 'deactivated' : 'activated';
		return "Group $id: $column has been $text";
	}
}

?>
