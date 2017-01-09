<?php
namespace nzedb;

/**
 * Class for reading and writing NZB files on the hard disk,
 * building folder paths to store the NZB files.
 */
class NZBMultiGroup extends NZB
{
	/**
	 * Default constructor.
	 *
	 * @access public
	 *
	 * @param $pdo
	 */
	public function __construct(&$pdo = null)
	{
		parent::__construct($pdo);
	}

	/**
	 * Initiate class vars when writing NZB's.
	 *
	 * @access public
	 */
	public function initiateForMgrWrite()
	{
		$this->_tableNames = [
			'cName' => 'mgr_collections',
			'bName' => 'mgr_binaries',
			'pName' => 'mgr_parts',
		];

		$this->setQueries();
	}
}
