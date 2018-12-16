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

use lithium\data\collection\RecordSet;

class Groups extends \app\extensions\data\Model
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
			[
				'required' => true,
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
	 * @return \lithium\data\Entity	This *should* return a lithium\data\entity\Record object, but
	 *                              it actually returns a RecordSet for some reason.
	 * @throws \InvalidArgumentException if the new group's name is omitted.
	 */
	public static function create(array $data = [], array $options = [])
	{
		// TODO reinstate validation of name field once all lithium's edge cases are resolved.
		/*
		if ((!empty($data) && !isset($data['name']) || )) {
			throw new \InvalidArgumentException("To create a new group entry, you *must* supply the new group's name!\n" . print_r($data, true));
		}
		*/

		if (!empty($data) && \count($data) > 1) {
			$defaults = [
				'active'          => false,
				'backfill'        => false,
				'backfill_target' => 1,
				'description'     => 'Auto-created by ' . __CLASS__ . '::' . __METHOD__,
				'first_record'    => 0,
				'last_record'     => 0,
				//'minfilestoformrelease' => 0,
				//'minsizetoformrelease'  => 0,
			];
			$data += $defaults;
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
	 *
	 * @throws \InvalidArgumentException if a new entry must be created but the 'name' is not set.
	 */
	public static function findIdFromName($name, array $options = [])
	{
		$defaults = [
			'create'      => false,
			'description' => 'Auto-created by ' . __CLASS__ . '::' . __METHOD__,
		];
		$options += $defaults;

		$group = self::find('first',
			[
				'fields'     => ['id'],
				'conditions' => ['name' => $name],
			]
		);

		if ($group === null && $options['create'] === true) {
			try {
				$group = static::create(
					[
						'name'        => $name,
						'description' => $options['description']
					]
				);
			} catch (\InvalidArgumentException $e) {
				throw new \InvalidArgumentException($e->getMessage() . PHP_EOL . 'Thrown in \app\models\Groups.php');
			}
			$group->save();
		}

		return $group === null ? null : $group->id;
	}

	/**
	 * Returns an array of active groups and their associated data.
	 *
	 * @return array
	 */
	public static function getActive() : array
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
				'order'      => 'name',
			]
		)->data();
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

	public static function getAllByID($id)
	{
		$active = static::find('first',
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
				'conditions' => ['id' => $id],
			]
		);

		return $active->data();
	}

	public static function getAllByName(string $name)
	{
		$active = static::find('first',
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
				'conditions' => ['name' => $name],
			]
		);

		return $active->data();
	}

	/**
	 * @param string $order
	 * @param bool   $active
	 *
	 * @return array
	 */
	public static function getBackfilling(string $order, bool $active = true) : array
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

		$results = static::find('all',
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
				'conditions' => ['active' => $active, 'backfill' => true],
				'order'      => $order
			]
		);

		return ($results instanceof RecordSet) ? $results->data() : [];
	}

	/**
	 * Get a group ID using its name.
	 *
	 * @param string $name
	 *
	 * @return string Empty string on failure, groups_id on success.
	 */
	public static function getIDByName(string $name) : string
	{
		$entry = static::find('first',
			[
				'fields'     => ['id'],
				'conditions' => ['name' => $name],
			]
		);

		return $entry !== null ? $entry->data()['id'] : '';
	}

	/**
	 * Get a group name using its ID.
	 *
	 * @param int|string $id The group ID.
	 *
	 * @return string Empty string on failure, group name on success.
	 */
	public static function getNameByID($id) : string
	{
		$entry = static::find('first',
			[
				'fields'     => ['name'],
				'conditions' => ['id' => $id],
			]
		);

		return $entry !== null ? $entry->data()['name'] : '';
	}

	/**
	 * Fetch a range of groups, taking maximum items per page and pageno into account. Optionally
	 * limiting by %groupname% matching.
	 *
	 * @param int       $pageno
	 * @param int       $number
	 * @param string    $groupname
	 * @param bool|null $active Whether result should be filtered by active/inactive, or
	 *                          neither. true/false or null.
	 *
	 * @return mixed
	 */
	public static function getRange(int $pageno = 1, int $number = ITEMS_PER_PAGE, $groupname = '', $active = null)
	{
		$conditions = empty($groupname) ? [] : ['name' => ['LIKE' => "%$groupname%"]];
		$conditions += \is_null($active) ? [] : ['active' => $active];

		$results = static::find('all',
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
				'conditions' => $conditions,
				'limit' => $number,
				'order' => 'name ASC',
				'page' => $pageno,
			]
		);

		return $results->data();
	}

	/**
	 * Checks group name is standard and replaces the shorthand prefix if it exists.
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
	 * @param int|string $id     id of group to update.
	 * @param string     $column 'active' or 'backfill'
	 * @param int        $status
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
