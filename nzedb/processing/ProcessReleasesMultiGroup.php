<?php

namespace nzedb\processing;

use nzedb\NZBMultiGroup;
use nzedb\utility\Misc;


class ProcessReleasesMultiGroup extends ProcessReleases
{
	/**
	 * @var
	 */
	protected $fromNames;

	protected $tables = [
			'cname' => 'multigroup_collections',
			'bname' => 'multigroup_binaries',
			'pname' => 'multigroup_parts'
		];


	/**
	 * ProcessReleasesMultiGroup constructor.
	 *
	 * @param array $options
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);
		$this->nzb = new NZBMultiGroup($this->pdo);
	}

	/**
	 * Add MultiGroup posters to database
	 *
	 * @param $poster
	 */
	public function addPoster($poster)
	{
		$this->pdo->queryInsert(sprintf('INSERT INTO multigroup_posters (poster) VALUE (%s)',
			$this->pdo->escapeString($poster)));
	}

	/**
	 * Create NZB files from complete MultiGroup releases.
	 *
	 * @param $groupID
	 *
	 * @return int
	 * @access public
	 */
	public function createMGRNZBs($groupID)
	{
		$this->fromNames = Misc::convertMultiArray($this->getAllPosters(), "','");

		$releases = $this->pdo->queryDirect(
			sprintf("
				SELECT SQL_NO_CACHE CONCAT(COALESCE(cp.title,'') , CASE WHEN cp.title IS NULL THEN '' ELSE ' > ' END , c.title) AS title,
					r.name, r.id, r.guid
				FROM releases r
				INNER JOIN categories c ON r.categories_id = c.id
				INNER JOIN categories cp ON cp.id = c.parentid
				WHERE %s r.nzbstatus = 0 AND r.fromname IN ('%s')",
				(!empty($groupID) ? ' r.groups_id = ' . $groupID . ' AND ' : ' '),
				$this->fromNames
			)
		);

		$nzbCount = 0;

		if ($releases && $releases->rowCount()) {
			$total = $releases->rowCount();
			// Init vars for writing the NZB's.
			$this->nzb->initiateForMgrWrite();
			foreach ($releases as $release) {

				if ($this->nzb->writeNZBforReleaseId($release['id'],
						$release['guid'],
						$release['name'],
						$release['title']) === true
				) {
					$nzbCount++;
					if ($this->echoCLI) {
						echo $this->pdo->log->primaryOver("Creating NZBs and deleting MGR Collections:\t" .
							$nzbCount .
							'/' .
							$total .
							"\r");
					}
				}
			}
		}

		return $nzbCount;
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

	/**
	 * This method exists to prevent the parent one from over-writing the $this->tables property.
	 *
	 * @param $groupID Unused
	 *
	 * @return void
	 */
	protected function initiateTableNames($groupID)
	{
		return;
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
}
