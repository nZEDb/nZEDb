<?php

use nzedb\db\Settings;

class Groups
{
	/**
	 * @var nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * Construct.
	 *
	 * @param array $options Class instances.
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Settings' => null
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
	}

	/**
	 * @return array
	 */
	public function getAll()
	{
		return $this->pdo->query(
			"SELECT groups.*,
			COALESCE(rel.num, 0) AS num_releases
			FROM groups
			LEFT OUTER JOIN
				(SELECT group_id, COUNT(id) AS num FROM releases GROUP BY group_id) rel
			ON rel.group_id = groups.id
			ORDER BY groups.name"
		);
	}

	/**
	 * @return array
	 */
	public function getGroupsForSelect()
	{
		$categories = $this->pdo->query("SELECT * FROM groups WHERE active = 1 ORDER BY name");
		$temp_array = array();

		$temp_array[-1] = "--Please Select--";

		foreach($categories as $category) {
			$temp_array[$category["name"]] = $category["name"];
		}

		return $temp_array;
	}

	/**
	 * @param $id
	 *
	 * @return array|bool
	 */
	public function getByID($id)
	{
		return $this->pdo->queryOneRow(sprintf("SELECT * FROM groups WHERE id = %d ", $id));
	}

	/**
	 * @return array
	 */
	public function getActive()
	{
		return $this->pdo->query("SELECT * FROM groups WHERE active = 1 ORDER BY name");
	}

	/**
	 * @return array
	 */
	public function getActiveBackfill()
	{
		return $this->pdo->query("SELECT * FROM groups WHERE backfill = 1 AND last_record != 0 ORDER BY name");
	}

	/**
	 * @return array
	 */
	public function getActiveByDateBackfill()
	{
		return $this->pdo->query("SELECT * FROM groups WHERE backfill = 1 AND last_record != 0 ORDER BY first_record_postdate DESC");
	}

	/**
	 * @return array
	 */
	public function getActiveIDs()
	{
		return $this->pdo->query("SELECT id FROM groups WHERE active = 1 ORDER BY name");
	}

	/**
	 * @param $grp
	 *
	 * @return array|bool
	 */
	public function getByName($grp)
	{
		return $this->pdo->queryOneRow(sprintf("SELECT * FROM groups WHERE name = %s", $this->pdo->escapeString($grp)));
	}

	/**
	 * Get a group name using its ID.
	 *
	 * @param int|string $id The group ID.
	 *
	 * @return string Empty string on failure, groupName on success.
	 */
	public function getByNameByID($id)
	{
		$res = $this->pdo->queryOneRow(sprintf("SELECT name FROM groups WHERE id = %d ", $id));
		return ($res === false ? '' : $res["name"]);
	}

	/**
	 * Get a group name using its name.
	 *
	 * @param string $name The group name.
	 *
	 * @return string Empty string on failure, group_id on success.
	 */
	public function getIDByName($name)
	{
		$res = $this->pdo->queryOneRow(sprintf("SELECT id FROM groups WHERE name = %s", $this->pdo->escapeString($name)));
		return ($res === false ? '' : $res["id"]);
	}

	/**
	 * Set the backfill to 0 when the group is backfilled to max.
	 *
	 * @param $name
	 */
	public function disableForPost($name)
	{
		$this->pdo->queryExec(sprintf("UPDATE groups SET backfill = 0 WHERE name = %s", $this->pdo->escapeString($name)));
	}

	/**
	 * @param string $groupname
	 *
	 * @return mixed
	 */
	public function getCount($groupname="")
	{
		$res = $this->pdo->queryOneRow(
			sprintf(
				"SELECT COUNT(id) AS num
				 FROM groups
				 WHERE 1 = 1 %s",
				($groupname !== ''
					?
					sprintf(
						"AND groups.name LIKE %s ",
						$this->pdo->escapeString("%".$groupname."%")
					)
					: ''
				)
			)
		);
		return $res["num"];
	}

	/**
	 * @param string $groupname
	 *
	 * @return mixed
	 */
	public function getCountActive($groupname="")
	{
		$res = $this->pdo->queryOneRow(
			sprintf("
				SELECT COUNT(id) AS num
				FROM groups
				WHERE 1 = 1 %s
				AND active = 1",
				($groupname !== ''
					?
					sprintf(
						"AND groups.name LIKE %s ",
							$this->pdo->escapeString("%".$groupname."%")
					)
					: ''
				)
			)
		);
		return $res["num"];
	}

	/**
	 * @param string $groupname
	 *
	 * @return mixed
	 */
	public function getCountInactive($groupname="")
	{
		$res = $this->pdo->queryOneRow(
			sprintf("
				SELECT COUNT(id) AS num
				FROM groups
				WHERE 1 = 1 %s
				AND active = 0",
				($groupname !== ''
					?
					sprintf(
						"AND groups.name LIKE %s ",
						$this->pdo->escapeString("%".$groupname."%")
					)
					: ''
				)
			)
		);
		return $res["num"];
	}

	/**
	 * @param        $start
	 * @param        $num
	 * @param string $groupname
	 *
	 * @return mixed
	 */
	public function getRange($start, $num, $groupname="")
	{
		return $this->pdo->query(
			sprintf("
				SELECT groups.*,
				COALESCE(rel.num, 0) AS num_releases
				FROM groups
				LEFT OUTER JOIN
					(SELECT group_id, COUNT(id) AS num
						FROM releases GROUP BY group_id
					) rel
				ON rel.group_id = groups.id
				WHERE 1 = 1 %s
				ORDER BY groups.name " . ($start === false ? '' : " LIMIT " . $num . " OFFSET " . $start),
				($groupname !== ''
					?
					sprintf(
						"AND groups.name LIKE %s ",
						$this->pdo->escapeString("%".$groupname."%")
					)
					: ''
				)
			)
		);
	}

	/**
	 * @param        $start
	 * @param        $num
	 * @param string $groupname
	 *
	 * @return mixed
	 */
	public function getRangeActive($start, $num, $groupname="")
	{
		return $this->pdo->query(
			sprintf("
				SELECT groups.*, COALESCE(rel.num, 0) AS num_releases
				FROM groups
				LEFT OUTER JOIN
					(SELECT group_id, COUNT(id) AS num
						FROM releases
						GROUP BY group_id
					) rel
				ON rel.group_id = groups.id
				WHERE 1 = 1 %s
				AND active = 1
				ORDER BY groups.name " . ($start === false ? '' : " LIMIT " . $num . " OFFSET " .$start),
				($groupname !== ''
					?
					sprintf(
						"AND groups.name LIKE %s ",
						$this->pdo->escapeString("%".$groupname."%")
					)
					: ''
				)
			)
		);
	}

	/**
	 * @param        $start
	 * @param        $num
	 * @param string $groupname
	 *
	 * @return mixed
	 */
	public function getRangeInactive($start, $num, $groupname="")
	{
		return $this->pdo->query(
			sprintf("
				SELECT groups.*, COALESCE(rel.num, 0) AS num_releases
				FROM groups
				LEFT OUTER JOIN
					(SELECT group_id, COUNT(id) AS num
						FROM releases
						GROUP BY group_id
					) rel
				ON rel.group_id = groups.id
				WHERE 1 = 1 %s
				AND active = 0
				ORDER BY groups.name " . ($start === false ? '' : " LIMIT ".$num." OFFSET ".$start),
				($groupname !== ''
					? sprintf(
						"AND groups.name LIKE %s ",
						$this->pdo->escapeString("%".$groupname."%")
					)
					: ''
				)
			)
		);
	}

	/**
	 * Update an existing group.
	 *
	 * @param Array $group
	 *
	 * @return bool
	 */
	public function update($group)
	{

		$minFileString =
			($group["minfilestoformrelease"] == '' ?
				"minfilestoformrelease = NULL," :
				sprintf(" minfilestoformrelease = %d,", $this->formatNumberString($group["minfilestoformrelease"], false))
			);

		$minSizeString =
			($group["minsizetoformrelease"] == '' ?
				"minsizetoformrelease = NULL" :
				sprintf(" minsizetoformrelease = %d", $this->formatNumberString($group["minsizetoformrelease"], false))
			);

		return $this->pdo->queryExec(
			sprintf(
				"UPDATE groups
				SET name = %s, description = %s, backfill_target = %s, first_record = %s, last_record = %s,
				last_updated = NOW(), active = %s, backfill = %s, %s %s
				WHERE id = %d",
				$this->pdo->escapeString(trim($group["name"])),
				$this->pdo->escapeString(trim($group["description"])),
				$this->formatNumberString($group["backfill_target"]),
				$this->formatNumberString($group["first_record"]),
				$this->formatNumberString($group["last_record"]),
				$this->formatNumberString($group["active"]),
				$this->formatNumberString($group["backfill"]),
				$minFileString,
				$minSizeString,
				$group["id"]
			)
		);
	}

	/**
	 * Add a new group.
	 *
	 * @param array $group
	 *
	 * @return bool
	 */
	public function add($group)
	{
		$minFileString =
			($group["minfilestoformrelease"] == '' ?
				"NULL" :
				sprintf("%d", $this->formatNumberString($group["minfilestoformrelease"], false))
			);

		$minSizeString =
			($group["minsizetoformrelease"] == '' ?
				"NULL" :
				sprintf("%d", $this->formatNumberString($group["minsizetoformrelease"], false))
			);

		return $this->pdo->queryInsert(
			sprintf("
				INSERT INTO groups
					(name, description, backfill_target, first_record, last_record, last_updated,
					active, backfill, minfilestoformrelease, minsizetoformrelease)
				VALUES (%s, %s, %s, %s, %s, NOW(), %s, %s, %s, %s)",
				$this->pdo->escapeString(trim($group["name"])),
				$this->pdo->escapeString(trim($group["description"])),
				$this->formatNumberString($group["backfill_target"]),
				$this->formatNumberString($group["first_record"]),
				$this->formatNumberString($group["last_record"]),
				$this->formatNumberString($group["active"]),
				$this->formatNumberString($group["backfill"]),
				$minFileString,
				$minSizeString
			)
		);
	}

	/**
	 * Format numeric string when adding/updating groups.
	 *
	 * @param string $setting
	 * @param bool   $escape
	 *
	 * @return string|int
	 */
	protected function formatNumberString($setting, $escape=true)
	{
		$setting = trim($setting);
		if ($setting === "0" || !is_numeric($setting)) {
			$setting = '0';
		}

		return ($escape ? $this->pdo->escapeString($setting) : (int)$setting);
	}

	/**
	 * Delete a group.
	 *
	 * @param int|string $id ID of the group.
	 *
	 * @return bool
	 */
	public function delete($id)
	{
		$this->purge($id);
		return $this->pdo->queryExec(sprintf("DELETE FROM groups WHERE id = %d", $id));
	}

	/**
	 * Reset a group.
	 *
	 * @param string|int $id The group ID.
	 *
	 * @return bool
	 */
	public function reset($id)
	{
		// Remove rows from collections / binaries / parts.
		(new \Binaries(['Groups' => $this, 'Settings' => $this->pdo]))->purgeGroup($id);

		// Remove rows from part repair.
		$this->pdo->queryExec(sprintf("DELETE FROM partrepair WHERE group_id = %d", $id));

		$this->pdo->queryExec(sprintf('DROP TABLE IF EXISTS collections_%d', $id));
		$this->pdo->queryExec(sprintf('DROP TABLE IF EXISTS binaries_%d', $id));
		$this->pdo->queryExec(sprintf('DROP TABLE IF EXISTS parts_%d', $id));
		$this->pdo->queryExec(sprintf('DROP TABLE IF EXISTS partrepair_%d', $id));

		// Reset the group stats.
		return $this->pdo->queryExec(
			sprintf("
				UPDATE groups
				SET backfill_target = 0, first_record = 0, first_record_postdate = NULL, last_record = 0,
					last_record_postdate = NULL, last_updated = NULL
				WHERE id = %d", $id)
		);
	}

	/**
	 * Reset all groups.
	 *
	 * @return bool
	 */
	public function resetall()
	{
		$this->pdo->queryExec("TRUNCATE TABLE collections");
		$this->pdo->queryExec("TRUNCATE TABLE binaries");
		$this->pdo->queryExec("TRUNCATE TABLE parts");
		$this->pdo->queryExec("TRUNCATE TABLE partrepair");
		$groups = $this->pdo->query("SELECT id FROM groups");
		foreach ($groups as $group) {
			$this->pdo->queryExec('DROP TABLE IF EXISTS collections_' . $group['id']);
			$this->pdo->queryExec('DROP TABLE IF EXISTS binaries_' . $group['id']);
			$this->pdo->queryExec('DROP TABLE IF EXISTS parts_' . $group['id']);
			$this->pdo->queryExec('DROP TABLE IF EXISTS partrepair_' . $group['id']);
		}

		// Reset the group stats.
		return $this->pdo->queryExec("
			UPDATE groups
			SET backfill_target = 0, first_record = 0, first_record_postdate = NULL, last_record = 0,
				last_record_postdate = NULL, last_updated = NULL, active = 0"
		);
	}

	/**
	 * Purge a single group or all groups.
	 *
	 * @param int|string|bool $id The group ID. If false, purge all groups.
	 */
	public function purge($id = false)
	{
		if ($id === false) {
			$this->resetall();
		} else {
			$this->reset($id);
		}

		$releaseArray = $this->pdo->queryDirect(
			sprintf("SELECT id, guid FROM releases %s", ($id === false ? '' : 'WHERE group_id = ' . $id))
		);

		if ($releaseArray instanceof \Traversable) {
			$releases = new \Releases(['Settings' => $this->pdo, 'Groups' => $this]);
			$nzb = new \NZB($this->pdo);
			$releaseImage = new \ReleaseImage($this->pdo);
			foreach ($releaseArray as $release) {
				$releases->deleteSingle(['g' => $release['guid'], 'i' => $release['id']], $nzb, $releaseImage);
			}
		}
	}

	/**
	 * Update the list of newsgroups and return an array of messages.
	 *
	 * @param string $groupList
	 * @param int    $active
	 * @param int    $backfill
	 *
	 * @return array
	 */
	public function addBulk($groupList, $active = 1, $backfill = 1)
	{
		if (preg_match('/^\s*$/m', $groupList)) {
			$ret = "No group list provided.";
		} else {
			$nntp = new \NNTP(['Echo' => false]);
			if ($nntp->doConnect() !== true) {
				return 'Problem connecting to usenet.';
			}
			$groups = $nntp->getGroups();
			$nntp->doQuit();

			if ($nntp->isError($groups)) {
				return 'Problem fetching groups from usenet.';
			}

			$regFilter = '/' . $groupList . '/i';

			$ret = [];

			foreach($groups AS $group) {
				if (preg_match($regFilter, $group['group']) > 0) {
					$res = $this->pdo->queryOneRow(
						sprintf('SELECT id FROM groups WHERE name = %s', $this->pdo->escapeString($group['group']))
					);
					if ($res === false) {
						$this->pdo->queryInsert(
							sprintf(
								'INSERT INTO groups (name, active, backfill) VALUES (%s, %d, %d)',
								$this->pdo->escapeString($group['group']), $active, $backfill
							)
						);
						$ret[] = ['group' => $group['group'], 'msg' => 'Created'];
					}
				}
			}

			if (count($ret) === 0) {
				$ret = 'No groups found with your regex, try again!';
			}
		}
		return $ret;
	}

	/**
	 * @param     $id
	 * @param int $status
	 *
	 * @return string
	 */
	public function updateGroupStatus($id, $status = 0)
	{
		$this->pdo->queryExec(sprintf("UPDATE groups SET active = %d WHERE id = %d", $status, $id));
		return "Group $id has been " . (($status == 0) ? 'deactivated' : 'activated') . '.';
	}

	/**
	 * @param     $id
	 * @param int $status
	 *
	 * @return string
	 */
	public function updateBackfillStatus($id, $status = 0)
	{
		$this->pdo->queryExec(sprintf("UPDATE groups SET backfill = %d WHERE id = %d", $status, $id));
		return "Group $id has been " . (($status == 0) ? 'deactivated' : 'activated') . '.';
	}

	/**
	 * @var array
	 */
	private $cbppTableNames;

	/**
	 * Get the names of the collections/binaries/parts/part repair tables.
	 * If TPG is on, try to create new tables for the group_id, if we fail, log the error and exit.
	 *
	 * @param bool $tpgSetting false, tpg is off in site setting, true tpg is on in site setting.
	 * @param int  $groupID    ID of the group.
	 *
	 * @return array The table names.
	 */
	public function getCBPTableNames($tpgSetting, $groupID)
	{
		$groupKey = ($groupID . '_' . (int) $tpgSetting);

		// Check if buffered and return. Prevents re-querying MySQL when TPG is on.
		if (isset($this->cbppTableNames[$groupKey])) {
			return $this->cbppTableNames[$groupKey];
		}

		$tables = [];
		$tables['cname']  = 'collections';
		$tables['bname']  = 'binaries';
		$tables['pname']  = 'parts';
		$tables['prname'] = 'partrepair';

		if ($tpgSetting === true) {
			if ($groupID == '') {
				exit('Error: You must use releases_threaded.py since you have enabled TPG!');
			}

			if ($this->createNewTPGTables($groupID) === false && nZEDb_ECHOCLI) {
				exit('There is a problem creating new TPG tables for this group ID: ' . $groupID . PHP_EOL);
			}

			$groupEnding = '_' . $groupID;
			$tables['cname']  .= $groupEnding;
			$tables['bname']  .= $groupEnding;
			$tables['pname']  .= $groupEnding;
			$tables['prname'] .= $groupEnding;
		}

		// Buffer.
		$this->cbppTableNames[$groupKey] = $tables;

		return $tables;
	}

	/**
	 * Check if the tables exists for the group_id, make new tables for table per group.
	 *
	 * @param int $groupID
	 *
	 * @return bool
	 */
	public function createNewTPGTables($groupID)
	{
		foreach (['collections', 'binaries', 'parts', 'partrepair'] as $tableName) {
			if ($this->pdo->queryExec(sprintf('SELECT * FROM %s_%s LIMIT 1', $tableName, $groupID), true) === false) {
				if ($this->pdo->queryExec(sprintf('CREATE TABLE %s_%s LIKE %s', $tableName, $groupID, $tableName), true) === false) {
					return false;
				} else {
					if ($tableName === 'collections') {
						$this->pdo->queryExec(
							sprintf(
								'CREATE TRIGGER delete_collections_%s BEFORE DELETE ON collections_%s FOR EACH ROW BEGIN' .
								' DELETE FROM binaries_%s WHERE collectionid = OLD.id; DELETE FROM parts_%s WHERE collection_id = OLD.id; END',
								$groupID, $groupID, $groupID, $groupID
							)
						);
					}
				}
			}
		}
		return true;
	}
}
