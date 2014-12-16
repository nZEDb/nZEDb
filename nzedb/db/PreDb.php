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
 *
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 *
 * @link <http://www.gnu.org/licenses/>.
 * @author niel
 * @copyright 2014 nZEDb
 */
namespace nzedb\db;


class PreDb extends DB
{
	/**
	 * @var array Prepared Statement objects for importing files
	 */
	protected $importPS = [
		'AddGroups'		=> null,
		'DeleteShort' 	=> null,
		'Import' 		=> null,
		'LoadData' 		=> null,
		'Truncate'		=> null,
		'UpdateGroupID'	=> null,
	];

	protected $table;

	public function __construct(array $options = [])
	{
		$defaults = ['table' => 'predb'];
		$options += $defaults;
		parent::__construct($options);

		$this->table = $options['table'];
	}

	public function import($localDB = false)
	{
		if (!($this->preparedAddGroups instanceof \PDOStatement))
		{
			$this->prepareImportSQL($localDB);
		}
	}


	protected function prepareImportSQL($localDB = false)
	{
		$this->importPS['Truncate'] = $this->pdo->prepare("TRUNCATE TABLE tmp_pre");
		$sql                    = sprintf(
			"LOAD DATA %s INFILE :path IGNORE INTO TABLE tmp_pre FIELDS TERMINATED BY '\\t\\t' ENCLOSED BY \"'\" LINES TERMINATED BY '\\r\\n' (title, nfo, size, files, filename, nuked, nukereason, category, predate, source, requestid, groupname);",
			($localDB === false ? 'LOCAL' : '')
		);
		$this->importPS['LoadData'] = $this->pdo->prepare($sql);

		$this->importPS['DeleteShort'] = $this->pdo->prepare("DELETE FROM tmp_pre WHERE LENGTH(title) <= 8");

		// Add any groups that do not currently exist
		$sql = <<<SQL_ADD_GROUPS
INSERT IGNORE INTO groups (name, description)
	SELECT groupname, 'Added by predb import script'
	FROM tmp_pre AS t LEFT JOIN groups AS g ON t.groupname = g.name
	WHERE t.groupname IS NOT NULL AND g.name IS NULL
	GROUP BY groupname;
SQL_ADD_GROUPS;

		$this->importPS['AddGroups'] = $this->pdo->prepare($sql);

		// Fill the group_id
		$this->importPS['UpdateGroupID'] = $this->pdo->prepare("UPDATE tmp_pre AS t SET group_id = (SELECT id FROM groups WHERE name = t.groupname) WHERE groupname IS NOT NULL");

		$sql = <<<SQL_INSERT
INSERT INTO {$this->table} (title, nfo, size, files, filename, nuked, nukereason, category, predate, SOURCE, requestid, group_id)
  SELECT t.title, t.nfo, t.size, t.files, t.filename, t.nuked, t.nukereason, t.category, t.predate, t.source, t.requestid, group_id
    FROM tmp_pre AS t
  ON DUPLICATE KEY UPDATE predb.nfo = IF(predb.nfo IS NULL, t.nfo, predb.nfo),
	  predb.size = IF(predb.size IS NULL, t.size, predb.size),
	  predb.files = IF(predb.files IS NULL, t.files, predb.files),
	  predb.filename = IF(predb.filename = '', t.filename, predb.filename),
	  predb.nuked = IF(t.nuked > 0, t.nuked, predb.nuked),
	  predb.nukereason = IF(t.nuked > 0, t.nukereason, predb.nukereason),
	  predb.category = IF(predb.category IS NULL, t.category, predb.category),
	  predb.requestid = IF(predb.requestid = 0, t.requestid, predb.requestid),
	  predb.group_id = IF(predb.group_id == 0, t.group_id, predb.group_id);
SQL_INSERT;

		$this->importPS['Import'] = $this->pdo->prepare($sql);
	}
}
