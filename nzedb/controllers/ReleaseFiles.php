<?php

/**
 * Adds/fetches rar/zip/etc files for a release.
 *
 * Class ReleaseFiles
 */
class ReleaseFiles
{
	/**
	 * @var nzedb\db\DB
	 */
	protected $db;

	/**
	 *
	 */
	public function __construct()
	{
		$this->db = new nzedb\db\DB();
	}

	/**
	 * Get all release files for a release ID.
	 *
	 * @param int $id The release ID.
	 *
	 * @return array
	 */
	public function get($id)
	{
		return $this->db->query(sprintf("SELECT * FROM releasefiles WHERE releaseid = %d ORDER BY releasefiles.name", $id));
	}

	/**
	 * Get all release files for a release GUID.
	 *
	 * @param string $guid The release GUID.
	 *
	 * @return array
	 */
	public function getByGuid($guid)
	{
		return $this->db->query(
			sprintf("
				SELECT releasefiles.*
				FROM releasefiles
				INNER JOIN releases r ON r.id = releasefiles.releaseid
				WHERE r.guid = %s
				ORDER BY releasefiles.name ",
				$this->db->escapeString($guid)
			)
		);
	}

	/**
	 * Delete release files for a release ID.
	 *
	 * @param int $id The release ID.
	 *
	 * @return mixed
	 */
	public function delete($id)
	{
		return $this->db->queryExec(sprintf("DELETE FROM releasefiles WHERE releaseid = %d", $id));
	}

	/**
	 * Add new files for a release ID.
	 *
	 * @param int    $id          The ID of the release.
	 * @param string $name        Name of the file.
	 * @param int    $size        Size of the file.
	 * @param int    $createdTime Unix time the file was created.
	 * @param int    $hasPassword Does it have a password (see Releases class constants)?
	 *
	 * @return mixed
	 */
	public function add($id, $name, $size, $createdTime, $hasPassword)
	{
		$duplicateCheck = $this->db->queryOneRow(
			sprintf('
				SELECT id
				FROM releasefiles
				WHERE name = %s',
				$this->db->escapeString(utf8_encode($name))
			)
		);

		if ($duplicateCheck === false) {
			return $this->db->queryInsert(
				sprintf("
					INSERT INTO releasefiles
					(releaseid, name, size, createddate, passworded)
					VALUES
					(%d, %s, %s, %s, %d)",
					$id,
					$this->db->escapeString(utf8_encode($name)),
					$this->db->escapeString($size),
					$this->db->from_unixtime($createdTime),
					$hasPassword ));
		}
		return 0;
	}
}
