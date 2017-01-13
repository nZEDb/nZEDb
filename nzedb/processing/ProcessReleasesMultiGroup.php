<?php

namespace nzedb\processing;

use app\models\MultigroupPosters;
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
		$posters = MultigroupPosters::commaSeparatedList();
		$this->fromNamesQuery = sprintf("AND r.fromname IN('%s')", $posters);
	}
}
