<?php

namespace nzedb\processing;

use app\models\MultigroupPosters;
use nzedb\NZBMultiGroup;

class ProcessReleasesMultiGroup extends ProcessReleases
{
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
	 * Form fromNamesQuery for creating NZBs
	 *
	 * @void
	 */
	protected function formFromNamesQuery()
	{
		$this->fromNamesQuery = '';
	}

	/**
	 * @param $fromName
	 *
	 * @return bool
	 */
	public static function isMultiGroup($fromName)
	{
		$poster = MultigroupPosters::find('first', ['conditions' => ['poster' => $fromName]]);
		return (empty($poster) ? false : true);
	}

	/**
	 * This method exists to prevent the parent one from over-writing the $this->tables property.
	 *
	 * @param int $groupID Unused with mgr
	 *
	 * @return void
	 */
	protected function initiateTableNames($groupID)
	{
		$this->tables = self::tableNames();
	}

	/**
	 * Returns MGR table names
	 *
	 * @return array
	 */
	public static function tableNames()
	{
		return [
			'cname' => 'multigroup_collections',
			'bname' => 'multigroup_binaries',
			'pname' => 'multigroup_parts',
			'prname' => 'multigroup_missed_parts',
		];
	}
}
