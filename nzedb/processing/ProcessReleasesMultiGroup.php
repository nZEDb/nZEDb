<?php

namespace nzedb\processing;

use nzedb\NZBMultiGroup;
use nzedb\utility\Misc;


class ProcessReleasesMultiGroup extends ProcessReleases
{
	/**
	 * @var
	 */
	protected $mgrFromNames;

	/**
	 * @var NZBMultiGroup
	 */
	protected $mgrnzb;


	/**
	 * ProcessReleasesMultiGroup constructor.
	 *
	 * @param array $options
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);
		$this->mgrnzb = new NZBMultiGroup($this->pdo);
	}

	/**
	 * @param $fromName
	 *
	 * @return bool
	 */
	public function isMultiGroup($fromName)
	{
		$array = array_column($this->getAllPosters(), 'poster');
		return in_array($fromName, $array);
	}


	protected function initiateMgrTableNames()
	{
		$group = [
			'cname' => 'multigroup_collections',
			'bname' => 'multigroup_binaries',
			'pname' => 'multigroup_parts'
		];

		return $group;
	}

	/**
	 * Process incomplete MultiGroup Releases
	 *
	 * @param $groupID
	 */
	public function processIncompleteMgrCollections($groupID)
	{
		$tableNames = $this->initiateMgrTableNames();
		$this->processIncompleteCollectionsMain($groupID, $tableNames);
	}

	/**
	 * Process MultiGroup collection sizes
	 *
	 * @param $groupID
	 */
	public function processMgrCollectionSizes($groupID)
	{
		$tableNames = $this->initiateMgrTableNames();
		$this->processCollectionSizesMain($groupID, $tableNames);
	}

	/**
	 * Delete unwanted MultiGroup collections
	 *
	 * @param $groupID
	 */
	public function deleteUnwantedMgrCollections($groupID)
	{
		$tableNames = $this->initiateMgrTableNames();
		$this->deleteUnwantedCollectionsMain($groupID, $tableNames);
	}

	public function deleteMgrCollections($groupID)
	{
		$tableNames = $this->initiateMgrTableNames();
		$this->deleteCollectionsMain($groupID, $tableNames);
	}

	/**
	 * Create releases from complete MultiGroup collections.
	 *
	 * @param $groupID
	 *
	 * @return array
	 * @access public
	 */
	public function createMGRReleases($groupID)
	{
		$tableNames = $this->initiateMgrTableNames();
		return $this->createReleasesMain($groupID, $tableNames);
	}

	/**
	 * Create NZB files from complete MultiGroup releases.
	 *
	 *
	 * @param $groupID
	 *
	 * @return int
	 * @access public
	 */
	public function createMGRNZBs($groupID)
	{
		$this->mgrFromNames = Misc::convertMultiArray($this->getAllPosters(), "','");

		$releases = $this->pdo->queryDirect(
			sprintf("
				SELECT SQL_NO_CACHE CONCAT(COALESCE(cp.title,'') , CASE WHEN cp.title IS NULL THEN '' ELSE ' > ' END , c.title) AS title,
					r.name, r.id, r.guid
				FROM releases r
				INNER JOIN categories c ON r.categories_id = c.id
				INNER JOIN categories cp ON cp.id = c.parentid
				WHERE %s r.nzbstatus = 0 AND r.fromname IN ('%s')",
				(!empty($groupID) ? ' r.groups_id = ' . $groupID . ' AND ' : ' '),
				$this->mgrFromNames
			)
		);

		$nzbCount = 0;

		if ($releases && $releases->rowCount()) {
			$total = $releases->rowCount();
			// Init vars for writing the NZB's.
			$this->mgrnzb->initiateForMgrWrite();
			foreach ($releases as $release) {

				if ($this->mgrnzb->writeNZBforReleaseId($release['id'], $release['guid'], $release['name'], $release['title']) === true) {
					$nzbCount++;
					if ($this->echoCLI) {
						echo $this->pdo->log->primaryOver("Creating NZBs and deleting MGR Collections:\t" . $nzbCount . '/' . $total . "\r");
					}
				}
			}
		}

		return $nzbCount;
	}

	/**
	 * Add MultiGroup posters to database
	 *
	 * @param $poster
	 */
	public function addPoster($poster)
	{
		$this->pdo->queryInsert(sprintf('INSERT INTO multigroup_posters (poster) VALUE (%s)', $this->pdo->escapeString($poster)));
	}

	/**
	 * Update MultiGroup poster
	 *
	 * @param $id
	 * @param $poster
	 */
	public function updatePoster($id, $poster)
	{
		$this->pdo->queryExec(sprintf('UPDATE multigroup_posters SET poster = %s WHERE id = %d', $this->pdo->escapeString($poster), $id));
	}

	/**
	 * Delete MultiGroup posters from database
	 *
	 * @param $id
	 */
	public function deletePoster($id)
	{
		$this->pdo->queryExec(sprintf('DELETE FROM multigroup_posters WHERE id = %d', $id));
	}

	/**
	 * Fetch all MultiGroup posters from database
	 *
	 * @return array|bool
	 */
	public function getAllPosters()
	{
		$result = $this->pdo->query(sprintf('SELECT poster AS poster FROM multigroup_posters'));
		if (is_array($result) && !empty($result)) {
			return $result;
		}
		return false;
	}
}
