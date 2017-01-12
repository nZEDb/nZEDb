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

	/**
	 * @var
	 */
	protected $fromNamesQuery;

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

	/**
	 * This method exists to prevent the parent one from over-writing the $this->tables property.
	 *
	 * @param $groupID Unused
	 *
	 * @return void
	 */
	protected function initiateTableNames($groupID)
	{
		$this->tables = [
			'cname' => 'multigroup_collections',
			'bname' => 'multigroup_binaries',
			'pname' => 'multigroup_parts'
		];
	}

	/**
	 * Form fromNamesQuery for creating NZBs
	 *
	 * @void
	 */
	protected function formFromNamesQuery()
	{
		$posters = Misc::convertMultiArray($this->getAllPosters(), "','");
		$this->fromNamesQuery = sprintf("AND r.fromname IN('%s')", $posters);
	}
}
