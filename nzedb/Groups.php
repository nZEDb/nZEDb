<?php
namespace nzedb;

use nzedb\db\Settings;

class Groups
{
	/**
	 * @var \nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @var ColorCLI
	 */
	public $colorCLI;

	/**
	 * The table names for TPG children
	 *
	 * @var array
	 */
	protected $cbpm;

	/**
	 * Construct.
	 *
	 * @param array $options Class instances.
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Settings' => null,
			'ColorCLI' => null
		];
		$options += $defaults;

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());
		$this->colorCLI = ($options['ColorCLI'] instanceof ColorCLI ? $options['ColorCLI'] : new ColorCLI());
		$this->cbpm = ['collections', 'binaries', 'parts', 'missed_parts'];
	}

	/**
	 * Returns all groups and the count of releases for each group
	 *
	 * @return array
	 */
	public function getAll()
	{
		return $this->pdo->query(
			"SELECT g.*,
				COALESCE(COUNT(r.id), 0) AS num_releases
			FROM groups g
			LEFT OUTER JOIN releases r ON g.id = r.groups_id
			GROUP BY g.id
			ORDER BY g.name ASC",
			true,
			nZEDb_CACHE_EXPIRY_LONG
		);
	}

	/**
	 * Returns an associative array of groups for list selection
	 *
	 * @return array
	 */
	public function getGroupsForSelect()
	{
		$groups = $this->getActive();
		$temp_array = [];

		$temp_array[-1] = "--Please Select--";

		foreach ($groups as $group) {
			$temp_array[$group["name"]] = $group["name"];
		}

		return $temp_array;
	}

	/**
	 * Get all properties of a single group by its ID
	 *
	 * @param $id
	 *
	 * @return array|bool
	 */
	public function getByID($id)
	{
		return $this->pdo->queryOneRow("
			SELECT *
			FROM groups
			WHERE id = {$id}"
		);
	}

	/**
	 * Get all properties of all groups ordered by name ascending
	 *
	 * @return array
	 */
	public function getActive()
	{
		return $this->pdo->query(
			"SELECT * FROM groups WHERE active = 1 ORDER BY name ASC",
			true,
			nZEDb_CACHE_EXPIRY_SHORT
		);
	}

	/**
	 * Get active backfill groups ordered by name ascending
	 *
	 * @return array
	 */
	public function getActiveBackfill()
	{
		return $this->pdo->query(
			"SELECT * FROM groups WHERE backfill = 1 AND last_record != 0 ORDER BY name ASC",
			true,
			nZEDb_CACHE_EXPIRY_SHORT
		);
	}

	/**
	 * Get active backfill groups ordered by the newest backfill postdate
	 *
	 * @return array
	 */
	public function getActiveByDateBackfill()
	{
		return $this->pdo->query(
			"SELECT * FROM groups WHERE backfill = 1 AND last_record != 0 ORDER BY first_record_postdate DESC",
			true,
			nZEDb_CACHE_EXPIRY_SHORT
		);
	}

	/**
	 * Get all active group IDs
	 *
	 * @return array
	 */
	public function getActiveIDs()
	{
		return $this->pdo->query(
			"SELECT id FROM groups WHERE active = 1 ORDER BY name ASC",
			true,
			nZEDb_CACHE_EXPIRY_SHORT
		);
	}

	/**
	 * Get all group columns by Name
	 *
	 * @param $grp
	 *
	 * @return array|bool
	 */
	public function getByName($grp)
	{
		return $this->pdo->queryOneRow("
			SELECT *
			FROM groups
			WHERE name = {$this->pdo->escapeString($grp)}"
		);
	}

	/**
	 * Get a group name using its ID.
	 *
	 * @param int|string $id The group ID.
	 *
	 * @return string Empty string on failure, groupName on success.
	 */
	public function getNameByID($id)
	{
		$res = $this->pdo->queryOneRow("
			SELECT name
			FROM groups
			WHERE id = {$id}"
		);

		return ($res === false ? '' : $res["name"]);
	}

	/**
	 * Get a group ID using its name.
	 *
	 * @param string $name The group name.
	 *
	 * @return string Empty string on failure, groups_id on success.
	 */
	public function getIDByName($name)
	{
		$res = $this->pdo->queryOneRow(
			sprintf("
				SELECT id
				FROM groups
				WHERE name = %s",
				$this->pdo->escapeString($name)
			)
		);

		return ($res === false ? '' : $res["id"]);
	}

	/**
	 * Gets a count of all groups in the table limited by parameters
	 *
	 * @param string $groupname Constrain query to specific group name
	 * @param int    $active Constrain query to active status
	 *
	 * @return mixed
	 */
	public function getCount($groupname = "", $active = -1)
	{
		$res = $this->pdo->queryOneRow(
			sprintf(
				"SELECT COUNT(id) AS num
				 FROM groups
				 WHERE 1=1 %s %s",
				($groupname !== ''
					?
					sprintf(
						"AND groups.name %s",
						$this->pdo->likeString($groupname, true, true)
					)
					: ''
				),
				($active > -1 ? "AND active = {$active}" : '')
			)
		);

		return ($res === false ? 0 : $res["num"]);
	}

	/**
	 * @param        $start
	 * @param        $num
	 * @param string $groupname
	 *
	 * @param int    $active
	 *
	 * @return mixed
	 */
	public function getRange($start, $num, $groupname = "", $active = -1)
	{
		return $this->pdo->query(
			sprintf("
				SELECT groups.*,
				COALESCE(COUNT(r.id), 0) AS num_releases
				FROM groups
				LEFT OUTER JOIN releases r ON r.groups_id = groups.id
				WHERE 1=1 %s %s
				GROUP BY groups.id
				ORDER BY groups.name ASC
				%s",
				($groupname !== ''
					?
					sprintf(
						"AND groups.name %s",
						$this->pdo->likeString($groupname, true, true)
					)
					: ''
				),
				($active > -1 ? "AND active = {$active}" : ''),
				($start === false ? '' : " LIMIT " . $num . " OFFSET " . $start)
			), true, nZEDb_CACHE_EXPIRY_SHORT
		);
	}

	/**
	 * Update an existing group.
	 *
	 * @param array $group
	 *
	 * @return bool
	 */
	public function update($group)
	{

		$minFileString =
			(
				$group["minfilestoformrelease"] == ''
					? "minfilestoformrelease = NULL,"
					: sprintf(
						" minfilestoformrelease = %d,",
						$this->formatNumberString($group["minfilestoformrelease"], false)
					)
			);

		$minSizeString =
			(
				$group["minsizetoformrelease"] == ''
					? "minsizetoformrelease = NULL"
					: sprintf(
						" minsizetoformrelease = %d",
						$this->formatNumberString($group["minsizetoformrelease"], false)
					)
			);

		return $this->pdo->queryExec(
			sprintf(
				"UPDATE groups
				SET name = %s, description = %s, backfill_target = %s, first_record = %s, last_record = %s,
				last_updated = NOW(), active = %s, backfill = %s, %s %s
				WHERE id = %d",
				$this->pdo->escapeString(trim($group["name"])),
				$this->formatNumberString($group["backfill_target"]),
				$this->pdo->escapeString(trim($group["description"])),
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
	 * Checks group name is standard and replaces any shorthand prefixes
	 *
	 * @param string $groupName The full name of the usenet group being evaluated
	 *
	 * @return string The name of the group after replacing any shorthand prefix
	 */
	public function isValidGroup($groupName)
	{
		if (preg_match('/(\w\.)+\w/i', $groupName)) {

			return preg_replace('/^a\.b\./i', 'alt.binaries.', $groupName, 1);
		}

		return false;
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
			(
				$group["minfilestoformrelease"] == ''
					? "NULL"
					: sprintf("%d", $this->formatNumberString($group["minfilestoformrelease"], false))
			);

		$minSizeString =
			(
				$group["minsizetoformrelease"] == ''
					? "NULL"
					: sprintf("%d", $this->formatNumberString($group["minsizetoformrelease"], false))
			);

		return $this->pdo->queryInsert(
			sprintf("
				INSERT INTO groups
					(name, description, backfill_target, first_record, last_record, last_updated,
					active, backfill, minfilestoformrelease, minsizetoformrelease)
				VALUES (%s, %s, %s, %s, %s, NOW(), %s, %s, %s, %s)",
				$this->pdo->escapeString(trim($group["name"])),
				(isset($group["description"]) ? $this->pdo->escapeString(trim($group["description"])) : "''"),
				(isset($group["backfill_target"]) ? $this->formatNumberString($group["backfill_target"]) : "1"),
				(isset($group["first_record"]) ? $this->formatNumberString($group["first_record"]) : "0"),
				(isset($group["last_record"]) ? $this->formatNumberString($group["last_record"]) : "0"),
				(isset($group["active"]) ? $this->formatNumberString($group["active"]) : "0"),
				(isset($group["backfill"]) ? $this->formatNumberString($group["backfill"]) : "0"),
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
	protected function formatNumberString($setting, $escape = true)
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

		return $this->pdo->queryExec("
			DELETE FROM groups
			WHERE id = {$id}"
		);
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
		(new Binaries(['Groups' => $this, 'Settings' => $this->pdo]))->purgeGroup($id);

		// Remove rows from part repair.
		$this->pdo->queryExec("DELETE FROM missed_parts WHERE group_id = {$id}");

		foreach ($this->cbpm AS $tablePrefix) {
			$this->pdo->queryExec("DROP TABLE IF EXISTS {$tablePrefix}_{$id}"
			);
		}

		// Reset the group stats.
		return $this->pdo->queryExec(
			sprintf("
				UPDATE groups
				SET backfill_target = 1, first_record = 0, first_record_postdate = NULL, last_record = 0,
					last_record_postdate = NULL, last_updated = NULL
				WHERE id = %d",
				$id
			)
		);
	}

	/**
	 * Reset all groups.
	 *
	 * @return bool
	 */
	public function resetall()
	{
		foreach ($this->cbpm AS $tablePrefix) {
			$this->pdo->queryExec("TRUNCATE TABLE {$tablePrefix}");
		}
		$groups = $this->pdo->query("SELECT id FROM groups");
		foreach ($groups as $group) {
			foreach ($this->cbpm AS $tablePrefix) {
				$this->pdo->queryExec("DROP TABLE IF EXISTS {$tablePrefix}_{$group['id']}");
			}
		}

		// Reset the group stats.
		return $this->pdo->queryExec("
			UPDATE groups
			SET backfill_target = 1, first_record = 0, first_record_postdate = NULL,
				last_record = 0, last_record_postdate = NULL, last_updated = NULL, active = 0"
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
			sprintf("
				SELECT id, guid
				FROM releases %s",
				($id === false ? '' : 'WHERE groups_id = ' . $id)
			)
		);

		if ($releaseArray instanceof \Traversable) {
			$releases     = new Releases(['Settings' => $this->pdo, 'Groups' => $this]);
			$nzb          = new NZB($this->pdo);
			$releaseImage = new ReleaseImage($this->pdo);
			foreach ($releaseArray as $release) {
				$releases->deleteSingle(
					[
						'g' => $release['guid'],
						'i' => $release['id']
					],
					$nzb,
					$releaseImage
				);
			}
		}
	}

	/**
	 * Adds new newsgroups based on a regular expression match against USP available
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
			$nntp = new NNTP(['Echo' => false]);
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

			foreach ($groups as $group) {
				if (preg_match($regFilter, $group['group']) > 0) {
					$res = $this->getIDByName($group['group']);
					if ($res === '') {
						$this->add(
							[
								'name'        => $group['group'],
								'active'      => $active,
								'backfill'    => $backfill,
								'description' => 'Added by bulkAdd',
							]
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
	 * Updates the group active/backfill status
	 *
	 * @param int $id Which group ID
	 * @param string $column Which column active/backfill
	 * @param int $status Which status we are setting
	 *
	 * @return string
	 */
	public function updateGroupStatus($id, $column, $status = 0)
	{
		$this->pdo->queryExec("
			UPDATE groups
			SET {$column} = {$status}
			WHERE id = {$id}"
		);

		return "Group {$id}: {$column} has been " . (($status == 0) ? 'deactivated' : 'activated') . '.';
	}

	/**
	 * @var array
	 */
	private $cbppTableNames;

	/**
	 * Get the names of the collections/binaries/parts/part repair tables.
	 * If TPG is on, try to create new tables for the groups_id, if we fail, log the error and exit.
	 *
	 * @param bool $tpgSetting false, tpg is off in site setting, true tpg is on in site setting.
	 * @param int  $groupID    ID of the group.
	 *
	 * @return array The table names.
	 */
	public function getCBPTableNames($tpgSetting, $groupID)
	{
		$groupKey = ($groupID . '_' . (int)$tpgSetting);

		// Check if buffered and return. Prevents re-querying MySQL when TPG is on.
		if (isset($this->cbppTableNames[$groupKey])) {
			return $this->cbppTableNames[$groupKey];
		}

		$tables           = [];
		$tables['cname']  = 'collections';
		$tables['bname']  = 'binaries';
		$tables['pname']  = 'parts';
		$tables['prname'] = 'missed_parts';

		if ($tpgSetting === true) {
			if ($groupID == '') {
				exit('Error: You must use .../misc/update/nix/multiprocessing/releases.php since you have enabled TPG!');
			}

			if ($this->createNewTPGTables($groupID) === false && nZEDb_ECHOCLI) {
				exit('There is a problem creating new TPG tables for this group ID: ' . $groupID .
					 PHP_EOL);
			}

			$groupEnding = '_' . $groupID;
			$tables['cname'] .= $groupEnding;
			$tables['bname'] .= $groupEnding;
			$tables['pname'] .= $groupEnding;
			$tables['prname'] .= $groupEnding;
		}

		// Buffer.
		$this->cbppTableNames[$groupKey] = $tables;

		return $tables;
	}

	/**
	 * Check if the tables exist for the groups_id, make new tables for table per group.
	 *
	 * @param int $groupID
	 *
	 * @return bool
	 */
	public function createNewTPGTables($groupID)
	{
		foreach ($this->cbpm as $tablePrefix) {
			if ($this->pdo->queryExec(
					"CREATE TABLE IF NOT EXISTS {$tablePrefix}_{$groupID} LIKE {$tablePrefix}",
					true
				) === false
			) {

				return false;
			}
		}

		return true;
	}

	/**
	 * @note Disable group that does not exist on USP server
	 * @param $id
	 *
	 */
	public function disableIfNotExist($id)
	{
		$this->updateGroupStatus($id, 'active', 0);
		$this->colorCLI->doEcho(
			$this->colorCLI->error(
				'Group does not exist on server, disabling'
			)
		);
	}
}
