<?php

use nzedb\db\DB;

class Groups
{
	/**
	 * @var DB
	 */
	protected $db;

	/**
	 * Construct.
	 *
	 * @param null $db
	 */
	public function __construct($db=null)
	{
		if (!is_null($db)) {
			$this->db = $db;
		} else {
			$this->db = new DB();
		}
	}

	/**
	 * @return array
	 */
	public function getAll()
	{
		return $this->db->query(
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
		$categories = $this->db->query("SELECT * FROM groups WHERE active = 1 ORDER BY name");
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
		return $this->db->queryOneRow(sprintf("SELECT * FROM groups WHERE id = %d ", $id));
	}

	/**
	 * @return array
	 */
	public function getActive()
	{
		return $this->db->query("SELECT * FROM groups WHERE active = 1 ORDER BY name");
	}

	/**
	 * @return array
	 */
	public function getActiveBackfill()
	{
		return $this->db->query("SELECT * FROM groups WHERE backfill = 1 AND last_record != 0 ORDER BY name");
	}

	/**
	 * @return array
	 */
	public function getActiveByDateBackfill()
	{
		return $this->db->query("SELECT * FROM groups WHERE backfill = 1 AND last_record != 0 ORDER BY first_record_postdate DESC");
	}

	/**
	 * @return array
	 */
	public function getActiveIDs()
	{
		return $this->db->query("SELECT id FROM groups WHERE active = 1 ORDER BY name");
	}

	/**
	 * @param $grp
	 *
	 * @return array|bool
	 */
	public function getByName($grp)
	{
		return $this->db->queryOneRow(sprintf("SELECT * FROM groups WHERE name = %s", $this->db->escapeString($grp)));
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
		$res = $this->db->queryOneRow(sprintf("SELECT name FROM groups WHERE id = %d ", $id));
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
		$res = $this->db->queryOneRow(sprintf("SELECT id FROM groups WHERE name = %s", $this->db->escapeString($name)));
		return ($res === false ? '' : $res["id"]);
	}

	/**
	 * Set the backfill to 0 when the group is backfilled to max.
	 *
	 * @param $name
	 */
	public function disableForPost($name)
	{
		$this->db->queryExec(sprintf("UPDATE groups SET backfill = 0 WHERE name = %s", $this->db->escapeString($name)));
	}

	/**
	 * @param string $groupname
	 *
	 * @return mixed
	 */
	public function getCount($groupname="")
	{
		$res = $this->db->queryOneRow(
			sprintf(
				"SELECT COUNT(id) AS num
				 FROM groups
				 WHERE 1 = 1 %s",
				($groupname !== ''
					?
					sprintf(
						"AND groups.name %s %s ",
						($this->db->dbSystem() === 'mysql' ? 'LIKE' : 'ILIKE'),
						$this->db->escapeString("%".$groupname."%")
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
		$res = $this->db->queryOneRow(
			sprintf("
				SELECT COUNT(id) AS num
				FROM groups
				WHERE 1 = 1 %s
				AND active = 1",
				($groupname !== ''
					?
					sprintf(
						"AND groups.name %s %s ",
							($this->db->dbSystem() === 'mysql' ? 'LIKE' : 'ILIKE'),
							$this->db->escapeString("%".$groupname."%")
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
		$res = $this->db->queryOneRow(
			sprintf("
				SELECT COUNT(id) AS num
				FROM groups
				WHERE 1 = 1 %s
				AND active = 0",
				($groupname !== ''
					?
					sprintf(
						"AND groups.name %s %s ",
							($this->db->dbSystem() === 'mysql' ? 'LIKE' : 'ILIKE'),
							$this->db->escapeString("%".$groupname."%")
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
		return $this->db->query(
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
						"AND groups.name %s %s ",
							($this->db->dbSystem() === 'mysql' ? 'LIKE' : 'ILIKE'),
							$this->db->escapeString("%".$groupname."%")
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
		return $this->db->query(
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
						"AND groups.name %s %s ",
							($this->db->dbSystem() === 'mysql' ? 'LIKE' : 'ILIKE'),
							$this->db->escapeString("%".$groupname."%")
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
		return $this->db->query(
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
						"AND groups.name %s %s ",
							($this->db->dbSystem() === 'mysql' ? 'LIKE' : 'ILIKE'),
							$this->db->escapeString("%".$groupname."%")
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

		return $this->db->queryExec(
			sprintf(
				"UPDATE groups
				SET name = %s, description = %s, backfill_target = %s, first_record = %s, last_record = %s,
				last_updated = NOW(), active = %s, backfill = %s, %s %s
				WHERE id = %d",
				$this->db->escapeString(trim($group["name"])),
				$this->db->escapeString(trim($group["description"])),
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

		return $this->db->queryInsert(
			sprintf("
				INSERT INTO groups
					(name, description, backfill_target, first_record, last_record, last_updated,
					active, backfill, minfilestoformrelease, minsizetoformrelease)
				VALUES (%s, %s, %s, %s, %s, NOW(), %s, %s, %s, %s)",
				$this->db->escapeString(trim($group["name"])),
				$this->db->escapeString(trim($group["description"])),
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

		return ($escape ? $this->db->escapeString($setting) : (int)$setting);
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
		return $this->db->queryExec(sprintf("DELETE FROM groups WHERE id = %d", $id));
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
		(new Binaries())->purgeGroup($id);

		// Remove rows from part repair.
		$this->db->queryExec(sprintf("DELETE FROM partrepair WHERE group_id = %d", $id));

		$this->db->queryExec(sprintf('DROP TABLE IF EXISTS collections_', $id));
		$this->db->queryExec(sprintf('DROP TABLE IF EXISTS binaries_', $id));
		$this->db->queryExec(sprintf('DROP TABLE IF EXISTS parts_', $id));
		$this->db->queryExec(sprintf('DROP TABLE IF EXISTS partrepair_', $id));

		// Reset the group stats.
		return $this->db->queryExec(
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
		$this->db->queryExec("TRUNCATE TABLE collections");
		$this->db->queryExec("TRUNCATE TABLE binaries");
		$this->db->queryExec("TRUNCATE TABLE parts");
		$this->db->queryExec("TRUNCATE TABLE partrepair");
		$groups = $this->db->query("SELECT id FROM groups");
		foreach ($groups as $group) {
			$this->db->queryExec('DROP TABLE IF EXISTS collections_' . $group['id']);
			$this->db->queryExec('DROP TABLE IF EXISTS binaries_' . $group['id']);
			$this->db->queryExec('DROP TABLE IF EXISTS parts_' . $group['id']);
			$this->db->queryExec('DROP TABLE IF EXISTS partrepair_' . $group['id']);
		}

		// Reset the group stats.
		return $this->db->queryExec("
			UPDATE groups
			SET backfill_target = 0, first_record = 0, first_record_postdate = NULL, last_record = 0,
				last_record_postdate = NULL, last_updated = NULL, active = 0"
		);
	}

	/**
	 * Purge a group.
	 *
	 * @param int|string $id The group ID.
	 */
	public function purge($id)
	{
		$this->reset($id);

		$releases = new Releases();
		$rels = $this->db->query(sprintf("SELECT id FROM releases WHERE group_id = %d", $id));
		foreach ($rels as $rel) {
			$releases->delete($rel["id"]);
		}
	}

	/**
	 * Purge all groups.
	 */
	public function purgeall()
	{
		$this->resetall();

		$releases = new Releases();
		$rels = $this->db->query("SELECT id FROM releases");
		foreach ($rels as $rel) {
			$releases->delete($rel["id"]);
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
			$nntp = new NNTP(false);
			if ($nntp->doConnect() !== true) {
				return 'Problem connecting to usenet.';
			}
			$groups = $nntp->getGroups();
			$nntp->doQuit();

			if ($nntp->isError($groups)) {
				return 'Problem fetching groups from usenet.';
			}

			$regFilter = '/' . $groupList . '/i';

			$ret = array();

			foreach($groups AS $group) {
				if (preg_match($regFilter, $group['group']) > 0) {
					$res = $this->db->queryOneRow(
						sprintf('SELECT id FROM groups WHERE name = %s', $this->db->escapeString($group['group']))
					);
					if ($res === false) {
						$this->db->queryInsert(
							sprintf(
								'INSERT INTO groups (name, active, backfill) VALUES (%s, %d, %d)',
								$this->db->escapeString($group['group']), $active, $backfill
							)
						);
						$ret[] = array ('group' => $group['group'], 'msg' => 'Created');
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
		$this->db->queryExec(sprintf("UPDATE groups SET active = %d WHERE id = %d", $status, $id));
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
		$this->db->queryExec(sprintf("UPDATE groups SET backfill = %d WHERE id = %d", $status, $id));
		return "Group $id has been " . (($status == 0) ? 'deactivated' : 'activated') . '.';
	}
}
