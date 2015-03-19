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
	 * @var array Prepared Statement objects
	 */
	protected $ps = [
		'AddGroups'		=> null,
		'DeleteShort' 	=> null,
		'Export'		=> null,
		'Import' 		=> null,
		'Insert'		=> null,
		'LoadData' 		=> null,
		'Truncate'		=> null,
		'UpdateGroupID'	=> null,
	];

	public function __construct(array $options = [])
	{
		$defaults = [];
		$options += $defaults;
		parent::__construct($options);

		$this->tableMain = 'predb';
		$this->tableTemp = 'predb_imports';
	}

	public function executeAddGroups()
	{
		if (!isset($this->ps['AddGroups'])) {
			$this->prepareSQLAddGroups();
		}

		return $this->ps['AddGroups']->execute();
	}

	public function executeDeleteShort()
	{
		if (!isset($this->ps['DeleteShort'])) {
			$this->prepareSQLDeleteShort();
		}

		return $this->ps['DeleteShort']->execute();
	}

	public function executeExport(array $options = null)
	{
		$defaults = [
			'enclosed'	=> '',
			'fields'	=> '\\t',
			'lines'		=> '\\n',
			'path'		=> null,
		];
		$options += $defaults;

		if (empty($options['path'])) {
			return null;
		}

		if (!isset($this->ps['Export'])) {
			$this->prepareSQLExport();
		}

		return $this->ps['Export']->execute([
				':enclosed'	=> $options['enclosed'],
				':fields'	=> $options['fields'],
				':lines'	=> $options['lines'],
				':path'		=> $options['path'],
			]);
	}

	public function executeInsert()
	{
		if (!isset($this->ps['Insert'])) {
			$this->prepareSQLInsert();
		}

		return $this->ps['Insert']->execute();
	}

	public function executeLoadData(array $options = null)
	{
		$defaults = [
			'fields'	=> '\\t',
			'lines'		=> '\\n',
			'local'		=> false,
			'path'		=> null,
		];
		$options += $defaults;

		if (empty($options['path'])) {
			return null;
		}

		if (!isset($this->ps['LoadData'])) {
			// TODO detect LOCAL here and pass parameter as appropriate
			$this->prepareSQLLoadData($options['local']);
		}

		return $this->ps['LoadData']->execute([':path' => $options['path']]);
	}

	public function executeTruncate()
	{
		if (!isset($this->ps['Truncate'])) {
			$this->prepareSQLTruncate();
		}
		return $this->ps['Truncate']->execute();
	}

	public function executeUpdateGroupID()
	{
		if (!isset($this->ps['UpdateGroupID'])) {
			$this->prepareSQLUpdateGroupIDs();
		}

		return $this->ps['UpdateGroupID']->execute();
	}

	public function import(\String $filespec, $localDB = false)
	{
		if (!($this->ps['AddGroups'] instanceof \PDOStatement)) {
			$this->prepareImportSQL($localDB);
		}

		$this->ps['Truncate']->execute();

		$this->ps['LoadData']->execute([':path' => $filespec]);

		$this->ps['DeleteShort']->execute();

		$this->ps['AddGroups']->execute();

		$this->ps['UpdateGroupID']->execute();

		$this->ps['Insert']->execute();
	}

	public function progress($settings = null, array $options = [])
	{
		$defaults = [
			'path'	=> nZEDb_ROOT . 'cli' . DS . 'data' . DS . 'predb_progress.txt',
			'read'	=> true,
		];
		$options += $defaults;

		if (!$options['read'] || !is_file($options['path'])) {
			file_put_contents($options['path'], base64_encode(serialize($settings)));
		} else {
			$settings = unserialize(base64_decode(file_get_contents($options['path'])));
		}

		return $settings;
	}

	protected function prepareImportSQL($localDB = false)
	{
		$this->prepareSQLTruncate();

		$this->prepareSQLLoadData($localDB);

		$this->prepareSQLDeleteShort();

		$this->prepareSQLAddGroups();

		$this->prepareSQLUpdateGroupIDs();

		$this->prepareSQLInsert();
	}

	protected function prepareSQLStatement($sql, $index)
	{
		$this->ps[$index] = $this->prepare($sql);
	}

	/**
	 * Add any groups that are not in our current groups table
	 */
	protected function prepareSQLAddGroups()
	{
		$sql = <<<SQL_ADD_GROUPS
INSERT IGNORE INTO groups (name, description)
	SELECT groupname, 'Added by predb import script'
	FROM predb_imports AS pi LEFT JOIN groups AS g ON pi.groupname = g.name
	WHERE pi.groupname IS NOT NULL AND g.name IS NULL
	GROUP BY groupname;
SQL_ADD_GROUPS;

		$this->prepareSQLStatement($sql, 'AddGroups');
	}

	protected function prepareSQLDeleteShort()
	{
		$this->prepareSQLStatement('DELETE FROM predb_imports WHERE LENGTH(title) <= 8', 'DeleteShort');
	}

	protected function prepareSQLExport()
	{
		$sql = <<<SQL_EXPORT
SELECT title, nfo, size, files, filename, nuked, nukereason, category, predate, source, requestid, g.name
	FROM {$this->tableMain} p LEFT OUTER JOIN groups g ON p.group_id = g.id
	INTO OUTFILE :path
	FIELDS TERMINATED BY :field
	:enclosed
	LINES TERMINATED BY :lines;
SQL_EXPORT;

		$this->prepareSQLStatement($sql, 'Export');
	}

	protected function prepareSQLInsert()
	{
		$sql = <<<SQL_INSERT
INSERT INTO {$this->tableMain} (title, nfo, size, files, filename, nuked, nukereason, category, predate, SOURCE, requestid, group_id)
  SELECT pi.title, pi.nfo, pi.size, pi.files, pi.filename, pi.nuked, pi.nukereason, pi.category, pi.predate, pi.source, pi.requestid, group_id
    FROM predb_imports AS pi
  ON DUPLICATE KEY UPDATE predb.nfo = IF(predb.nfo IS NULL, pi.nfo, predb.nfo),
	  predb.size = IF(predb.size IS NULL, pi.size, predb.size),
	  predb.files = IF(predb.files IS NULL, pi.files, predb.files),
	  predb.filename = IF(predb.filename = '', pi.filename, predb.filename),
	  predb.nuked = IF(pi.nuked > 0, pi.nuked, predb.nuked),
	  predb.nukereason = IF(pi.nuked > 0, pi.nukereason, predb.nukereason),
	  predb.category = IF(predb.category IS NULL, pi.category, predb.category),
	  predb.requestid = IF(predb.requestid = 0, pi.requestid, predb.requestid),
	  predb.group_id = IF(predb.group_id = 0, pi.group_id, predb.group_id);
SQL_INSERT;

		$this->prepareSQLStatement($sql, 'Insert');
	}

	protected function prepareSQLLoadData($local = true)
	{
		$sql = sprintf(
			"LOAD DATA %s INFILE :path IGNORE INTO TABLE predb_imports FIELDS TERMINATED BY '\\t\\t' ENCLOSED BY \"'\" LINES TERMINATED BY '\\r\\n' (title, nfo, size, files, filename, nuked, nukereason, category, predate, source, requestid, groupname);",
			($local === false ? 'LOCAL' : '')
		);
		$this->prepareSQLStatement($sql, 'LoadData');
	}

	protected function prepareSQLTruncate()
	{
		$this->prepareSQLStatement('TRUNCATE TABLE predb_imports', 'Truncate');
	}

	protected function prepareSQLUpdateGroupIDs()
	{
		$sql = "UPDATE predb_imports AS pi SET group_id = (SELECT id FROM groups WHERE name = pi.groupname) WHERE groupname IS NOT NULL";
		$this->prepareSQLStatement($sql, 'UpdateGroupID');
	}
}
