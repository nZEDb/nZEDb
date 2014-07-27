<?php

use nzedb\db\Settings;

abstract class RequestID
{
	// Request ID.
	const REQID_OLD    = -4; // We rechecked the web a second time and didn't find a title so don't process it again.
	const REQID_NONE   = -3; // The Request ID was not found locally or via web lookup.
	const REQID_ZERO   = -2; // The Request ID was 0.
	const REQID_NOLL   = -1; // Request ID was not found via local lookup.
	const REQID_UPROC  =  0; // Release has not been processed.
	const REQID_FOUND  =  1; // Request ID found and release was updated.

	/**
	 * @param array $options Class instances / Echo to cli?
	 */
	public function __construct(array $options = array())
	{
		$defaults = [
			'Echo'         => true,
			'Categorize'   => null,
			'ColorCLI'     => null,
			'ConsoleTools' => null,
			'Groups'       => null,
			'Settings'     => null,
		];
		$defaults = array_replace($defaults, $options);

		$this->echoOutput = ($defaults['Echo'] && nZEDb_ECHOCLI);
		$this->pdo = ($defaults['Settings'] instanceof Settings ? $defaults['Settings'] : new Settings());
		$this->category = ($defaults['Categorize'] instanceof Categorize ? $defaults['Categorize'] : new Categorize(['Settings' => $this->pdo]));
		$this->groups = ($defaults['Groups'] instanceof Groups ? $defaults['Groups'] : new Groups(['Settings' => $this->pdo]));
		$this->colorCLI = ($defaults['ColorCLI'] instanceof ColorCLI ? $defaults['ColorCLI'] : new ColorCLI());
		$this->consoleTools = ($defaults['ConsoleTools'] instanceof ConsoleTools ? $defaults['ConsoleTools'] : new ConsoleTools(['ColorCLI' => $this->colorCLI]));
	}

	/**
	 * Look up request ID's for releases.
	 *
	 * @param array $options
	 *
	 * @return int Quantity of releases matched to a request ID.
	 */
	public function lookupRequestIDs(array $options = array())
	{
		$curOptions = [
			'charGUID'      => '',
			'GroupID'       => '',
			'limit'         => '',
			'show'          => 1,
			'time'          => 0,
		];
		$curOptions = array_replace($curOptions, $options);

		$startTime = time();
		$renamed = 0;

		$this->_charGUID = $curOptions['charGUID'];
		$this->_groupID = $curOptions['GroupID'];
		$this->_show = $curOptions['show'];
		$this->_maxTime = $curOptions['time'];
		$this->_limit = $curOptions['limit'];

		$this->_getReleases();

		if ($this->_releases !== false && $this->_releases->rowCount() > 0) {
			$this->_totalReleases = $this->_releases->rowCount();
			$this->colorCLI->doEcho($this->colorCLI->primary('Processing ' . $this->_totalReleases . " releases for RequestID's."));
			$renamed = $this->_processReleases();
			if ($this->echoOutput) {
				echo $this->colorCLI->header(
					"\nRenamed " . number_format($renamed) . " releases in " . $this->consoleTools->convertTime(time() - $startTime) . "."
				);
			}
		} elseif ($this->echoOutput) {
			$this->colorCLI->doEcho($this->colorCLI->primary("No RequestID's to process."));
		}

		return $renamed;
	}

	/**
	 * Fetch releases with requestID's from MySQL.
	 */
	protected function _getReleases() { }

	/**
	 * Process releases for requestID's.
	 *
	 * @return int How many did we rename?
	 */
	protected function _processReleases() { }

	/**
	 * No request ID was found, update the release.
	 *
	 * @param int $releaseID
	 * @param int $status
	 */
	protected function _requestIdNotFound($releaseID, $status)
	{
		if ($releaseID == 0) {
			return;
		}

		$this->pdo->queryExec(
			sprintf('
				UPDATE releases SET reqidstatus = %d WHERE id = %d',
				$status, $releaseID
			)
		);
	}

	/**
	 * Get a new title / pre ID for a release.
	 *
	 * @return array|bool
	 */
	protected function _getNewTitle() { }

	/**
	 * Find a RequestID in a usenet subject.
	 *
	 * @return int
	 */
	protected function _siftReqId()
	{
		switch (true) {
			case preg_match('/\[ ?#?scnzb@?efnet ?\]\[(\d+)\]/', $this->_release['name'], $requestID):
			case preg_match('/\[\s*(\d+)\s*\]/', $this->_release['name'], $requestID):
			case preg_match('/^REQ\s*(\d{4,6})/i', $this->_release['name'], $requestID):
			case preg_match('/^(\d{4,6})-\d{1}\[/', $this->_release['name'], $requestID):
			case preg_match('/(\d{4,6}) -/',$this->_release['name'], $requestID):
				if ((int) $requestID[1] > 0) {
					return (int) $requestID[1];
				}
		}
		return self::REQID_ZERO;
	}

	/**
	 * @var bool Echo to CLI?
	 */
	protected $echoOutput;

	/**
	 * @var Categorize
	 */
	protected $category;

	/**
	 * @var nzedb\db\Settings
	 */
	protected $pdo;

	/**
	 * @var ConsoleTools
	 */
	protected $consoleTools;

	/**
	 * @var ColorCLI
	 */
	protected $colorCLI;

	/**
	 * The found request ID for the release.
	 * @var int
	 */
	protected $_requestID = self::REQID_ZERO;

	/**
	 * The title found from a request ID lookup.
	 * @var bool|string|array
	 */
	protected $_newTitle = false;

	/**
	 * Releases with potential Request ID's we can work on.
	 * @var \PDOStatement
	 */
	protected $_releases;

	/**
	 * Total amount of releases we will be working on.
	 * @var int
	 */
	protected $_totalReleases;

	/**
	 * Release we are currently working on.
	 * @var array
	 */
	protected $_release;

	/**
	 * @var int To show the result or not.
	 */
	protected $_show = 0;

	/**
	 * GroupID, which is optional, to limit query results.
	 * @var string
	 */
	protected $_groupID;

	/**
	 * First character of a release GUID, which is optional, to limit query results.
	 * @var string
	 */
	protected $_charGUID;

	protected $_limit;

	protected $_maxTime;
}