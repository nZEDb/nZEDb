<?php
// TODO: Move RemoveCrapReleases and possible others into here.

/**
 * Handles removing of various unwanted releases.
 *
 * Class ReleaseRemover
 */
class ReleaseRemover
{
	/**
	 * @var DB
	 */
	protected $db;

	/**
	 * @var ColorCLI
	 */
	protected $color;

	/**
	 * @var ConsoleTools
	 */
	protected $consoleTools;

	/**
	 * @var Releases
	 */
	protected $releases;

	/**
	 * The query we will use to select unwanted releases.
	 * @var string
	 */
	protected $query;

	/**
	 * LIKE is case sensitive in PgSQL, get the insensitive one for it.
	 * @var string
	 */
	protected $like;

	/**
	 * If an error occurred, store it here.
	 * @var string
	 */
	protected $error;

	/**
	 * Time we started.
	 * @var int
	 */
	protected $timeStart;

	/**
	 * Result of the select query.
	 *
	 * @var array
	 */
	protected $result;

	/**
	 * Ignore user check?
	 * @var bool
	 */
	protected $ignoreUserCheck;

	/**
	 * Is is run from the browser?
	 * @var bool
	 */
	protected $browser;

	/**
	 * @const New line.
	 */
	const N = PHP_EOL;

	/**
	 * Construct.
	 *
	 * @param bool $browser Is is run from the browser?
	 */
	public function __construct($browser = false)
	{
		$this->db = new DB();
		$this->color = new ColorCLI();
		$this->consoleTools = new ConsoleTools();
		$this->releases = new Releases();

		$this->like = ($this->db->dbSystem() === 'mysql' ? 'LIKE' : 'ILIKE');
		$this->query = '';
		$this->error = '';
		$this->ignoreUserCheck = false;
		$this->browser = $browser;
	}

	/**
	 * Remove releases using user criteria.
	 *
	 * @param array $arguments Array of criteria used to delete unwanted releases.
	 *                         Criteria muse look like this : columnName=modifier="content"
	 *                         columnName is a column name from the releases table.
	 *                         modifiers are : equals,like,bigger,smaller
	 *                         content is what to change the column content to
	 *
	 * @return bool
	 */
	public function removeByCriteria($arguments)
	{
		$this->ignoreUserCheck = false;
		// Time we started.
		$this->timeStart = TIME();

		// Start forming the query.
		$this->query = 'SELECT id, guid FROM releases WHERE 1=1';

		// Keep forming the query based on the user's criteria, return if any errors.
		foreach($arguments as $arg) {
			$this->error = '';
			$string = $this->formatCriteriaQuery($arg);
			if ($string === false) {
				return $this->returnError();
			}
			$this->query .= $string;
		}
		$this->query = $this->cleanSpaces($this->query);

		// Check if the user wants to run the query.
		if ($this->checkUserResponse() === false) {
			return false;
		}

		// Check if the query returns results.
		if ($this->checkSelectQuery()=== false) {
			return $this->returnError();
		}

		// Delete the releases.
		$string = $this->deleteReleases();

		return ($this->browser ? $string : true);
	}

	/**
	 * Delete releases from the database.
	 */
	protected function deleteReleases()
	{
		$deletedCount = 0;
		foreach ($this->result['result'] as $release) {
			$this->releases->fastDelete($release['id'], $release['guid']);
			$deletedCount++;
			if (!$this->browser) {
				$this->consoleTools->overWriteHeader(
					"Deleting: " . $this->consoleTools->percentString($deletedCount, $this->result['total']) .
					" Time:" . $this->consoleTools->convertTimer(TIME() - $this->timeStart)
				);
			}
		}

		if ($this->browser) {
			return 'Success! Deleted ' . $deletedCount . ' release(s) in ' . $this->consoleTools->convertTime(TIME() - $this->timeStart);
		} else {
			echo self::N;
			echo $this->color->headerOver("Deleted " . $deletedCount . " release(s). This script ran for ");
			echo $this->color->header($this->consoleTools->convertTime(TIME() - $this->timeStart));
		}
	}

	/**
	 * Verify if the query has any results.
	 *
	 * @return bool|int False on failure, count of found releases.
	 */
	protected function checkSelectQuery()
	{
		// Run the query, check if it picked up anything.
		$result = $this->db->query($this->query);
		$totalResults = count($result);
		if ($totalResults <= 0) {
			$this->error = 'No releases were found to delete, try changing your criteria.';
			return false;
		}
		$this->result = array('total' => $totalResults, 'result' => $result);
		return true;
	}

	/**
	 * Go through user arguments and format part of the query.
	 *
	 * @param string $argument User argument.
	 *
	 * @return bool|string
	 */
	protected function formatCriteriaQuery($argument)
	{
		// Check if the user wants to ignore the check.
		if ($argument === 'ignore') {
			$this->ignoreUserCheck = true;
			return '';
		}

		$this->error = 'Invalid argument supplied: ' . $argument . self::N;
		$args = explode('=', $argument);
		if (count($args) === 3) {

			$args[0] = $this->cleanSpaces($args[0]);
			$args[1] = $this->cleanSpaces($args[1]);
			$args[2] = $this->cleanSpaces($args[2]);
			switch($args[0]) {
				case 'fromname':
					switch ($args[1]) {
						case 'equals':
							return ' AND fromname = ' . $this->db->escapeString($args[2]);
						case 'like':
							return ' AND fromname ' . $this->formatLike($args[2], 'fromname');
					}
					break;
				case 'groupname':
					switch ($args[1]) {
						case 'equals':
							$group = $this->db->queryOneRow('SELECT id FROM groups WHERE name = ' . $this->db->escapeString($args[2]));
							if ($group === false) {
								$this->error = 'This group was not found in your database: ' . $args[2] . PHP_EOL;
								break;
							}
							return ' AND groupid = ' . $group['id'];
						case 'like':
							$groups = $this->db->query('SELECT id FROM groups WHERE name ' . $this->formatLike($args[2], 'name'));
							if (count($groups) === 0) {
								$this->error = 'No groups were found with this pattern in your database: ' . $args[2] . PHP_EOL;
								break;
							}
							$gQuery = ' AND groupid IN (';
							foreach ($groups as $group) {
								$gQuery .= $group['id'] . ',';
							}
							$gQuery = substr($gQuery, 0, strlen($gQuery) - 1) . ')';
							return $gQuery;
						default:
							break;
					}
					break;
				case 'guid':
					switch ($args[1]) {
						case 'equals':
							return ' AND guid = ' . $this->db->escapeString($args[2]);
						default:
							break;
					}
					break;
				case 'name':
					switch ($args[1]) {
						case 'equals':
							return ' AND name = ' . $this->db->escapeString($args[2]);
						case 'like':
							return ' AND name ' . $this->formatLike($args[2], 'name');
						default:
							break;
					}
					break;
				case 'searchname':
					switch ($args[1]) {
						case 'equals':
							return ' AND searchname = ' . $this->db->escapeString($args[2]);
						case 'like':
							return ' AND searchname ' . $this->formatLike($args[2], 'searchname');
						default:
							break;
					}
					break;
				case 'size':
					if (!is_numeric($args[2])) {
						break;
					}
					switch ($args[1]) {
						case 'equals':
							return ' AND size = ' . $args[2];
						case 'bigger':
							return ' AND size > ' . $args[2];
						case 'smaller':
							return ' AND size < ' . $args[2];
						default:
							break;
					}
					break;
				case 'adddate':
					if (!is_numeric($args[2])) {
						break;
					}
					switch ($args[1]) {
						case 'bigger':
							return ' AND adddate <  NOW() - INTERVAL ' . $args[2] . ' HOUR';
						case 'smaller':
							return ' AND adddate >  NOW() - INTERVAL ' . $args[2] . ' HOUR';
						default:
							break;
					}
					break;
				case 'postdate':
					if (!is_numeric($args[2])) {
						break;
					}
					switch ($args[1]) {
						case 'bigger':
							return ' AND postdate <  NOW() - INTERVAL ' . $args[2] . ' HOUR';
						case 'smaller':
							return ' AND postdate >  NOW() - INTERVAL ' . $args[2] . ' HOUR';
						default:
							break;
					}
			}
		}
		return false;
	}

	/**
	 * Check if the user wants to run the current query.
	 *
	 * @return bool
	 */
	protected function checkUserResponse()
	{
		if ($this->ignoreUserCheck || $this->browser) {
			return true;
		}

		// Print the query to the user, ask them if they want to continue using it.
		echo $this->color->primary(
			'This is the query we have formatted using your criteria, you can run it in SQL to see if you like the results:' .
			self::N . $this->query . ';' . self::N .
			'If you are satisfied, type yes and press enter. Anything else will exit.'
		);

		// Check the users response.
		$userInput = trim(fgets(fopen('php://stdin', 'r')));
		if ($userInput !== 'yes') {
			echo $this->color->primary('You typed: "' . $userInput . '", the program will exit.');
			return false;
		}
		return true;
	}

	/**
	 * Remove multiple spaces and trim leading spaces.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	protected function cleanSpaces($string)
	{
		return trim(preg_replace('/\s{2,}/', ' ', $string));
	}

	/**
	 * Format a "like" string. ie: "name LIKE '%test%' AND name LIKE '%123%'
	 *
	 * @param string $string The string to format.
	 * @param string $type   The column name.
	 *
	 * @return string
	 */
	protected function formatLike($string, $type)
	{
		$newString = explode(' ', $string);
		if (count($newString) > 1) {
			$string = implode("%' AND {$type} {$this->like} '%", array_unique($newString));
		}
		return " {$this->like} '%" . $string . "%' ";
	}

	/**
	 * Echo the error and return false if on CLI.
	 * Return the error if on browser.
	 *
	 * @return bool/string
	 */
	protected function returnError()
	{
		if ($this->browser) {
			return $this->error . '<br />';
		} else {
			echo $this->color->error($this->error);
			return false;
		}

	}
}