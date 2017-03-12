<?php
namespace nzedb\db;

use nzedb\ColorCLI;
use nzedb\ConsoleTools;
use nzedb\Logger;
use nzedb\LoggerException;
use nzedb\utility\Misc;
use nzedb\utility\Text;
use nzedb\libraries\Cache;
use nzedb\libraries\CacheException;


/**
 * Class for handling connection to MySQL database using PDO.
 *
 * The class extends PDO, thereby exposing all of PDO's functionality directly
 * without the need to wrap each and every method here.
 *
 * Exceptions are caught and displayed to the user.
 * Properties are explicitly created, so IDEs can offer autocompletion for them.
 * @extends \PDO
 */
class DB extends \PDO
{
	const MINIMUM_VERSION_MARIADB = '10.0';

	const MINIMUM_VERSION_MYSQL = '5.6';


	/**
	 * @var bool Is this a Command Line Interface instance.
	 *
	 * This needs to be revisited when moving to li3. Web pages do not need this class so it shouldn't be included by default.
	 */
	public $cli;

	/**
	 * @var object Instance of \nzedb\ConsoleTools class.
	 */
	public $consoleTools;

	/**
	 * @var \nzedb\ColorCLI	Instance variable for logging object. Currently only ColorCLI supported,
	 * but expanding for full logging with agnostic API planned.
	 */
	public $log;

	/**
	 * @note Setting this static causes issues when creating multiple instances of this class with different
	 *       MySQL servers, the next instances re-uses the server of the first instance.
	 * @var \PDO Instance of PDO class.
	 */
	public $pdo = null;

	/**
	 * @var bool
	 */
	protected $_debug;

	/**
	 * @var bool Should we cache the results of the query method?
	 */
	private $cacheEnabled = false;

	/**
	 * @var null|\nzedb\libraries\Cache
	 */
	private $cacheServer = null;

	/**
	 * @var string Lower-cased name of DBMS type (MySQl/Postgres, etc.) in use.
	 */
	private $dbSystem;

	/**
	 * @var string The Db server info.
	 */
	private $dbInfo;

	/**
	 * @var object Class instance debugging.
	 */
	private $debugging;

	/**
	 * @var string	Stored copy of the dsn used to connect.
	 */
	private $dsn;

	/**
	 * @var string server's host to connect to.
	 */
	private $host;

	/**
	 * @var Database name to use.
	 */
	private $name = null;

	/**
	 * @var string password to use for connection to database server.
	 */
	private $password;

	/**
	 * @var boolean Whether the connection to the database server dhould be persistent.
	 */
	private $persist = false;

	/**
	 * @var string Port to use when connecting to the database server.
	 */
	private $port;

	/**
	 * @var string Unix socket to use when connecting to the database server.
	 */
	private $socket;

	/**
	 * @var string MySQL LOW_PRIORITY DELETE option.
	 */
	private $sqlDeleteLowPriority = '';

	/**
	 * @var string MYSQL QUICK DELETE option.
	 */
	private $sqlDeleteQuick = '';

	/**
	 * @var string Username to use when connecting to the database server.
	 */
	private $user;

	/**
	 * @var array List of valid DBMS systems (mysql, postgres, etc.).
	 */
	private $validTypes = ['mysql', 'sphinx'];

	/**
	 * @var string Name of the DBMS provider (MariaDB, MySQl, Percona, etc.)
	 */
	private $vendor = null;

	/**
	 * @var string Version of the Db server.
	 */
	private $version = null;


	/**
	 * Constructor. Sets up all necessary properties. Instantiates a PDO object
	 * if needed, otherwise returns the current one.
	 *
	 * @param array $options
	 */
	public function __construct(array $options = [])
	{
		$this->cli = Misc::isCLI();

		$defaults = [
			'checkVersion'	=> true,
			'createDb'		=> false, // create dbname if it does not exist?
			'ct'			=> new ConsoleTools(),
			'dbhost'		=> defined('DB_HOST') ? DB_HOST : '',
			'dbname'		=> defined('DB_NAME') ? DB_NAME : '', // '' means it is a Sphinx connection
			'dbpass'		=> defined('DB_PASSWORD') ? DB_PASSWORD : '',
			'dbport'		=> defined('DB_PORT') ? DB_PORT : '',
			'dbsock'		=> defined('DB_SOCKET') ? DB_SOCKET : '',
			'dbtype'		=> defined('DB_SYSTEM') ? DB_SYSTEM : '',
			'dbuser'		=> defined('DB_USER') ? DB_USER : '',
			'log'			=> new ColorCLI(),
			'persist'		=> false,
		];
		$options += $defaults;

		if (!$this->cli) {
			$options['log'] = null;
		}

		if (empty($options['dbtype'])) {
			throw new \RuntimeException("No Database system supplied. Currently this must be one of: " .
				implode(',', $this->validTypes), 1);
		} else {
			$this->dbSystem = $options['dbtype'];
		}

		$this->connect($options);

		if ($options['checkVersion']) {
			$this->validateVendorVersion();
		}

		if (!empty($options['dbname'])) {
			if ($options['createDb']) {
			// Note this only ensures the database exists, not the tables.
				$this->initialiseDatabase($options['dbname']);
			}

			$this->pdo->query("USE {$options['dbname']}");
		}

		$this->consoleTools =& $options['ct'];
		$this->log =& $options['log'];

		$this->cacheEnabled = (defined('nZEDb_CACHE_TYPE') && (nZEDb_CACHE_TYPE > 0) ? true : false);

		if ($this->cacheEnabled) {
			try {
				$this->cacheServer = new Cache();
			} catch (CacheException $error) {
				$this->cacheEnabled = false;
				$this->echoError($error->getMessage(), '__construct', 4);
			}
		}

		$this->_debug = (nZEDb_DEBUG || nZEDb_LOGGING);
		if ($this->_debug) {
			try {
				$this->debugging = new Logger(['ColorCLI' => $this->log]);
			} catch (LoggerException $error) {
				$this->_debug = false;
			}
		}

		if (defined('nZEDb_SQL_DELETE_LOW_PRIORITY') && nZEDb_SQL_DELETE_LOW_PRIORITY) {
			$this->sqlDeleteLowPriority = ' LOW_PRIORITY ';
		}

		if (defined('nZEDb_SQL_DELETE_QUICK') && nZEDb_SQL_DELETE_QUICK) {
			$this->sqlDeleteQuick = ' QUICK ';
		}

		return $this->pdo;
	}

	public function __destruct()
	{
		$this->pdo = null;
	}

	public function __get($name)
	{
		$result = $this->queryOneRow("SELECT value FROM settings WHERE setting = '$name' LIMIT 1");

		return is_array($result) ? $result['value'] : $result;
	}

	/**
	 * Turns off autocommit until commit() is ran. http://www.php.net/manual/en/pdo.begintransaction.php
	 *
	 * @return bool
	 */
	public function beginTransaction()
	{
		if (nZEDb_USE_SQL_TRANSACTIONS) {
			return $this->pdo->beginTransaction();
		}

		return true;
	}

	/**
	 * Fetch a list of indexes on a specified column of a table.
	 *
	 * @param $table
	 * @param $column
	 *
	 * @return array|boolean The array of indexes if found, or false if not.
	 */
	public function checkColumnIndex($table, $column)
	{
		$result = $this->pdo->query(
			sprintf(
				"SHOW INDEXES IN %s WHERE non_unique = 0 AND column_name = '%s'",
				trim($table),
				trim($column)
			)
		);
		if ($result === false) {
			return false;
		}

		return $result->fetchAll(\PDO::FETCH_ASSOC);
	}

	/**
	 * Compare a name against a list of databases found on the server.
	 *
	 * @param string $name
	 *
	 * @return boolean True if the name exists on the server, otherwise false.
	 */
	public function checkDbExists($name = null)
	{
		if (empty($name)) {
			$name = $this->name;
		}

		$found  = false;
		$tables = self::getDatabasesList();
		foreach ($tables as $table) {
			if ($table['database'] == $name) {
				$found = true;
				break;
			}
		}
		return $found;
	}

	/**
	 * Looks up info for index on table.
	 *
	 * @param $table string Table to look at.
	 * @param $index string Index to check.
	 *
	 * @return bool|array False on failure, associative array of SHOW data.
	 */
	public function checkIndex($table, $index)
	{
		$result = $this->pdo->query(
			sprintf(
				"SHOW INDEX FROM %s WHERE key_name = '%s'",
				trim($table),
				trim($index)
			)
		);
		if ($result === false) {
			return false;
		}

		return $result->fetch(\PDO::FETCH_ASSOC);
	}

	/**
	 * Commits a transaction. http://www.php.net/manual/en/pdo.commit.php
	 *
	 * @return bool
	 */
	public function commit()
	{
		if (nZEDb_USE_SQL_TRANSACTIONS) {
			return $this->pdo->commit();
		}

		return true;
	}

	/**
	 * @return string mysql.
	 */
	public function DbSystem()
	{
		return $this->dbSystem;
	}

	/**
	 * Disable debugging info to the shell.
	 */
	public function debugDisable()
	{
		unset($this->debugging);
		$this->_debug = false;
	}

	/**
	 * Enable debugging info to the shell.
	 */
	public function debugEnable()
	{
		$this->_debug = true;
		try {
			$this->debugging = new Logger(['ColorCLI' => $this->log]);
		} catch (LoggerException $error) {
			$this->_debug = false;
		}
	}

	/**
	 * Returns a string, escaped with single quotes, false on failure. http://www.php.net/manual/en/pdo.quote.php
	 *
	 * @param string $str
	 *
	 * @return string
	 */
	public function escapeString($str)
	{
		if (is_null($str)) {
			return 'NULL';
		}

		return $this->pdo->quote($str);
	}

	/**
	 * Direct query. Return the affected row count. http://www.php.net/manual/en/pdo.exec.php
	 *
	 * @note  If not "consumed", causes this error:
	 *        'SQLSTATE[HY000]: General error: 2014 Cannot execute queries while other unbuffered queries are active.
	 *        Consider using PDOStatement::fetchAll(). Alternatively, if your code is only ever going to run against mysql,
	 *        you may enable query buffering by setting the PDO::MYSQL_ATTR_USE_BUFFERED_QUERY attribute.'
	 *
	 * @param string $query
	 * @param bool   $silent Whether to skip echoing errors to the console.
	 *
	 * @return bool|int|\PDOStatement
	 */
	public function exec($query, $silent = false)
	{
		if (!$this->parseQuery($query)) {
			return false;
		}

		try {
			return $this->pdo->exec($query);
		} catch (\PDOException $e) {

			// Check if we lost connection to MySQL.
			if ($this->checkGoneAway($e->getMessage()) !== false) {

				// Reconnect to MySQL.
				if ($this->reconnect() === true) {

					// If we reconnected, retry the query.
					return $this->exec($query, $silent);
				} else {
					// If we are not reconnected, return false.
					return false;
				}
			} else {
				if (!$silent) {
					$this->echoError($e->getMessage(), 'Exec', 4, false);

					if ($this->_debug) {
						$this->debugging->log(get_class(), __FUNCTION__, $query, Logger::LOG_SQL);
					}
				}
			}

			return false;
		}
	}

	/**
	 * PHP interpretation of MySQL's from_unixtime method.
	 *
	 * @param int $utime UnixTime
	 *
	 * @return string
	 */
	public function from_unixtime($utime)
	{
		return 'FROM_UNIXTIME(' . $utime . ')';
	}

	/**
	 * Retrieve db attributes http://us3.php.net/manual/en/pdo.getattribute.php
	 *
	 * @param int $attribute
	 *
	 * @return false|mixed
	 */
	public function getAttribute($attribute)
	{
		$result = false;
		if ($attribute != '') {
			try {
				$result = $this->pdo->getAttribute($attribute);
			} catch (\PDOException $e) {
				if ($this->_debug) {
					$this->debugging->log(get_class(),
						__FUNCTION__,
						$e->getMessage(),
						Logger::LOG_INFO);
				}
				echo $this->log->error("\n" . $e->getMessage());
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * Fetches a list (array) of databases on the server. NOTE it only lists those the user can see.
	 *
	 * @return array
	 */
	public function getDatabasesList()
	{
		$query = ($this->dbSystem === 'mysql' ? 'SHOW DATABASES' :
			'SELECT datname AS database FROM pg_database');
		$result = $this->pdo->query($query);

		return $result->fetchAll(\PDO::FETCH_ASSOC);
	}

	/**
	 * Returns the stored Db version string.
	 *
	 * @return string
	 */
	public function getDbInfo()
	{
		return $this->dbInfo;
	}

	/**
	 * Fetches the value for a specified setting from the settings table.
	 *
	 * @param $name
	 *
	 * @return string|boolean	String of settings' value, or false.
	 */
	public function getSetting($name)
	{
		$result = $this->queryOneRow("SELECT value FROM settings WHERE setting = '$name' LIMIT 1");
		return is_array($result) ? $result['value'] : $result;
	}

	/**
	 * Return a tree-like array of all or selected settings.
	 *
	 * @param array $options            Options array for Settings::find() i.e. ['conditions' => ...].
	 * @param bool  $excludeUnsectioned If rows with empty 'section' field should be excluded.
	 *                                  Note this doesn't prevent empty 'subsection' fields.
	 *
	 * @return array
	 * @throws \RuntimeException
	 */
	public function getSettingsAsTree($excludeUnsectioned = true)
	{
		$where = $excludeUnsectioned ? "WHERE section != ''" : '';

		$sql = sprintf("SELECT section, subsection, name, value, hint FROM settings %s ORDER BY section, subsection, name",
			$where);
		$results = $this->queryArray($sql);

		$tree = [];
		if (is_array($results)) {
			foreach ($results as $result) {
				if (!empty($result['section']) || !$excludeUnsectioned) {
					$tree[$result['section']][$result['subsection']][$result['name']] =
						['value' => $result['value'], 'hint' => $result['hint']];
				}
			}
		} else {
			echo "NO results!!\n";
		}

		return $tree;
	}

	/**
	 * Accessor for DB::$vendor field.
	 *
	 * @return string The vendor.
	 */
	public function getVendor()
	{
		return $this->vendor;
	}

	/**
	 * Accessor for DB::$version field.
	 *
	 * @return string The version.
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * Verify if pdo var is instance of PDO class.
	 *
	 * @return bool
	 */
	public function isInitialised()
	{
		return ($this->pdo instanceof \PDO);
	}

	/**
	 * Attempts to determine if the Db is on the local machine.
	 *
	 * If the method returns true, then the Db is definitely on the local machine. However,
	 * returning false only indicates that it could not positively be determined to be local - so
	 * assume remote.
	 *
	 * @return bool Whether the Db is definitely on the local machine.
	 */
	public function isLocalDb()
	{
		$local = false;
		if (!empty($this->socket) || $this->host == 'localhost') {
			$local = true;
		} else {
			preg_match_all('/inet' . '6?' . ' addr: ?([^ ]+)/', `ifconfig`, $ips);

			// Check for dotted quad - if exists compare against local IP number(s)
			if (preg_match('#^\d+\.\d+\.\d+\.\d+$#', $this->host)) {
				if (in_array($this->host, $ips[1])) {
					$local = true;
				}
			}
		}
		return $local;
	}

	/**
	 * @return boolean true if the version is valid for server's vendor, false otherwise.
	 * @throws \RuntimeException if the vendor is not valid.
	 */
	public function isVendorVersionValid()
	{
		if (empty($this->vendor) || empty($this->version)) {
			$this->setServerInfo();
		}

		switch (strtolower($this->vendor)) {
			case 'mariadb':
				return version_compare(SELF::MINIMUM_VERSION_MARIADB, $this->version, '<=');
				break;
			case 'percona':
			case 'mysql':
				return version_compare(SELF::MINIMUM_VERSION_MYSQL, $this->version, '<=');
				break;
		}

		throw new \RuntimeException("No valid DB vendor set!\n'{$this->vendor}'", 4);
	}

	/**
	 * Formats a 'like' string. ex.(LIKE '%chocolate%')
	 *
	 * @param string $str   The string.
	 * @param bool   $left  Add a % to the left.
	 * @param bool   $right Add a % to the right.
	 *
	 * @return string
	 */
	public function likeString($str, $left = true, $right = true)
	{
		return ('LIKE ' . $this->escapeString(($left ? '%' : '') . $str . ($right ? '%' : '')));
	}

	/**
	 * Optimises/repairs tables on mysql.
	 *
	 * @param bool   $admin     If we are on web, don't echo.
	 * @param string $type      'full' | '' Force optimize of all tables.
	 *                          'space'     Optimise tables with 5% or more free space.
	 *                          'analyze'   Analyze tables to rebuild statistics.
	 * @param bool   $local     Only analyze local tables. Good if running replication.
	 * @param array  $tableList (optional) Names of tables to analyze.
	 *
	 * @return int Quantity optimized/analyzed
	 */
	public function optimise($admin = false, $type = '', $local = false, $tableList = [])
	{
		$tableAnd = '';
		if (count($tableList)) {
			foreach ($tableList as $tableName) {
				$tableAnd .= ($this->escapeString($tableName) . ',');
			}
			$tableAnd = (' AND Name IN (' . rtrim($tableAnd, ',') . ')');
		}

		switch ($type) {
			case 'space':
				$tableArray = $this->queryDirect('SHOW TABLE STATUS WHERE Data_free / Data_length > 0.005' .
					$tableAnd);
				$myIsamTables = $this->queryDirect("SHOW TABLE STATUS WHERE ENGINE LIKE 'myisam' AND Data_free / Data_length > 0.005" .
					$tableAnd);
				break;
			case 'analyze':
			case '':
			case 'full':
			default:
				$tableArray = $this->queryDirect('SHOW TABLE STATUS WHERE 1=1' . $tableAnd);
				$myIsamTables = $this->queryDirect("SHOW TABLE STATUS WHERE ENGINE LIKE 'myisam'" .
					$tableAnd);
				break;
		}

		$optimised = 0;
		if ($tableArray instanceof \Traversable && $tableArray->rowCount()) {

			$tableNames = '';
			foreach ($tableArray as $table) {
				$tableNames .= $table['name'] . ',';
			}
			$tableNames = rtrim($tableNames, ',');

			$local = ($local ? 'LOCAL' : '');
			if ($type === 'analyze') {
				$this->queryExec(sprintf('ANALYZE %s TABLE %s', $local, $tableNames));
				$this->logOptimize($admin, 'ANALYZE', $tableNames);
			} else {

				$this->queryExec(sprintf('OPTIMIZE %s TABLE %s', $local, $tableNames));
				$this->logOptimize($admin, 'OPTIMIZE', $tableNames);

				if ($myIsamTables instanceof \Traversable && $myIsamTables->rowCount()) {
					$tableNames = '';
					foreach ($myIsamTables as $table) {
						$tableNames .= $table['name'] . ',';
					}
					$tableNames = rtrim($tableNames, ',');
					$this->queryExec(sprintf('REPAIR %s TABLE %s', $local, $tableNames));
					$this->logOptimize($admin, 'REPAIR', $tableNames);
				}
				$this->queryExec(sprintf('FLUSH %s TABLES', $local));
			}
			$optimised = $tableArray->rowCount();
		}

		return $optimised;
	}

	/**
	 * Checks whether the connection to the server is working. Optionally restart a new connection.
	 * NOTE: Restart does not happen if PDO is not using exceptions (PHP's default configuration).
	 * In this case check the return value === false.
	 *
	 * @param boolean $restart Whether an attempt should be made to reinitialise the Db object on failure.
	 *
	 * @return boolean
	 */
	public function ping($restart = false)
	{
		try {
			return (bool)$this->pdo->query('SELECT 1+1');
		} catch (\PDOException $e) {
			if ($restart == true) {
				$this->connect([
					'checkVersion' => false,
					'createDb'     => false,
					'dbhost'       => $this->host,
					'dbname'       => $this->name, // '' means it is a Sphinx connection
					'dbpass'       => $this->password,
					'dbport'       => $this->port,
					'dbsock'       => $this->socket,
					'dbtype'       => $this->dbSystem,
					'dbuser'       => $this->user,
					'persist'      => $this->persist,
				]);
			}

			return false;
		}
	}

	/**
	 * Prepares a statement to be run by the Db engine.
	 * To run the statement use the returned $statement with ->execute();
	 * Ideally the signature would have array before $options but that causes a strict warning.
	 *
	 * @param string $query SQL query to run, with optional place holders.
	 * @param array  $options Driver options.
	 *
	 * @return false|\PDOstatement on success false on failure.
	 * @link http://www.php.net/pdo.prepare.php
	 */
	public function Prepare($query, $options = [])
	{
		try {
			$PDOstatement = $this->pdo->prepare($query, $options);
		} catch (\PDOException $e) {
			if ($this->_debug) {
				$this->debugging->log(get_class(),
					__FUNCTION__,
					$e->getMessage(),
					Logger::LOG_INFO);
			}
			echo $this->log->error("\n" . $e->getMessage());
			$PDOstatement = false;
		}

		return $PDOstatement;
	}

	/**
	 * Returns an array of result (empty array if no results or an error occurs)
	 * Optional: Pass true to cache the result with a cache server.
	 *
	 * @param string $query       SQL to execute.
	 * @param bool   $cache       Indicates if the query result should be cached.
	 * @param int    $cacheExpiry The time in seconds before deleting the query result from the cache server.
	 *
	 * @return array Array of results (possibly empty) on success, empty array on failure.
	 */
	public function query($query, $cache = false, $cacheExpiry = 600)
	{
		if (!$this->parseQuery($query)) {
			return false;
		}

		if ($cache === true && $this->cacheEnabled === true) {
			try {
				$data = $this->cacheServer->get($this->cacheServer->createKey($query));
				if ($data !== false) {
					return $data;
				}
			} catch (CacheException $error) {
				$this->echoError($error->getMessage(), 'query', 4);
			}
		}

		$result = $this->queryArray($query);

		if ($result !== false && $cache === true && $this->cacheEnabled === true) {
			$this->cacheServer->set($this->cacheServer->createKey($query), $result, $cacheExpiry);
		}

		return ($result === false) ? [] : $result;
	}

	/**
	 * Main method for creating results as an array.
	 *
	 * @param string $query SQL to execute.
	 *
	 * @return array|boolean Array of results on success or false on failure.
	 */
	public function queryArray($query)
	{
		$result = false;
		if (!empty($query)) {
			$result = $this->queryDirect($query);

			if (!empty($result)) {
				$result = $result->fetchAll();
			}
		}

		return $result;
	}

	/**
	 * Returns all results as an associative array.
	 * Do not use this function for large dat-asets, as it can cripple the Db server and use huge
	 * amounts of RAM. Instead iterate through the data.
	 *
	 * @param string $query The query to execute.
	 *
	 * @return array|boolean Array of results on success, false otherwise.
	 */
	public function queryAssoc($query)
	{
		if ($query == '') {
			return false;
		}
		$mode = $this->pdo->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE);
		if ($mode != \PDO::FETCH_ASSOC) {
			$this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
		}

		$result = $this->queryArray($query);

		if ($mode != \PDO::FETCH_ASSOC) {
			$this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, $mode); // Restore old mode
		}

		return $result;
	}

	/**
	 * Returns a multidimensional array of result of the query function return and the count of found rows
	 * Note: Query passed to this function SHOULD include SQL_CALC_FOUND_ROWS
	 * Optional: Pass true to cache the result with a cache server.
	 *
	 * @param string $query       SQL to execute.
	 * @param bool   $cache       Indicates if the query result should be cached.
	 * @param int    $cacheExpiry The time in seconds before deleting the query result from the cache server.
	 *
	 * @return array Array of results (possibly empty) on success, empty array on failure.
	 */
	public function queryCalc($query, $cache = false, $cacheExpiry = 600)
	{
		$data = $this->query($query, $cache, $cacheExpiry);

		if (strpos($query, 'SQL_CALC_FOUND_ROWS') === false) {
			return $data;
		}

		// Remove LIMIT and OFFSET from query to allow queryCalc usage with browse
		$query = preg_replace('#(\s+LIMIT\s+\d+)?\s+OFFSET\s+\d+\s*$#i', '', $query);

		if ($cache === true && $this->cacheEnabled === true) {
			try {
				$count = $this->cacheServer->get($this->cacheServer->createKey($query . 'count'));
				if ($count !== false) {
					return ['total' => $count, 'result' => $data];
				}
			} catch (CacheException $error) {
				$this->echoError($error->getMessage(), 'queryCalc', 4);
			}
		}

		$result = $this->queryOneRow('SELECT FOUND_ROWS() AS total');

		if ($result !== false && $cache === true && $this->cacheEnabled === true) {
			$this->cacheServer->set($this->cacheServer->createKey($query . 'count'),
				$result['total'],
				$cacheExpiry);
		}

		return
			[
				'total'  => ($result === false ? 0 : $result['total']),
				'result' => $data
			];
	}

	/**
	 * Delete rows from MySQL.
	 *
	 * @param string $query
	 * @param bool   $silent Echo or log errors?
	 *
	 * @return bool|\PDOStatement
	 */
	public function queryDelete($query, $silent = false)
	{
		// Accommodate for chained queries (SELECT 1;DELETE x FROM y)
		if (preg_match('#(.*?[^a-z0-9]|^)DELETE\s+(.+?)$#is', $query, $matches)) {
			$query = $matches[1] .
				'DELETE ' .
				$this->sqlDeleteLowPriority .
				$this->sqlDeleteQuick .
				$matches[2];
		}

		return $this->queryExec($query, $silent);
	}

	/**
	 * Query without returning an empty array like our function query(). http://php.net/manual/en/pdo.query.php
	 *
	 * Uses the parentquery
	 *
	 * @param string $query  The query to run.
	 * @param bool   $ignore Ignore errors, do not log them?
	 *
	 * @return bool|\PDOStatement
	 */
	public function queryDirect($query, $ignore = false)
	{
		if (!$this->parseQuery($query)) {
			return false;
		}

		try {
			$result = $this->pdo->query($query);
		} catch (\PDOException $e) {

			// Check if we lost connection to MySQL.
			if ($this->checkGoneAway($e->getMessage()) !== false) {

				// Reconnect to MySQL.
				if ($this->reconnect() === true) {

					// If we reconnected, retry the query.
					$result = $this->queryDirect($query);
				} else {
					// If we are not reconnected, return false.
					$result = false;
				}
			} else {
				if ($ignore === false) {
					$this->echoError($e->getMessage(), 'queryDirect', 4, false);
					if ($this->_debug) {
						$this->debugging->log(get_class(), __FUNCTION__, $query, Logger::LOG_SQL);
					}
				}
				$result = false;
			}
		}

		return $result;
	}

	/**
	 * Used for deleting, updating (and inserting without needing the last insert id).
	 *
	 * @param string $query
	 * @param bool   $silent Echo or log errors?
	 *
	 * @return bool|\PDOStatement
	 */
	public function queryExec($query, $silent = false)
	{
		if (!$this->parseQuery($query)) {
			return false;
		}

		$i = 2;
		$error = '';
		while ($i < 11) {
			$result = $this->queryExecHelper($query);
			if (is_array($result) && isset($result['deadlock'])) {
				$error = $result['message'];
				if ($result['deadlock'] === true) {
					$this->echoError("A Deadlock or lock wait timeout has occurred, sleeping. (" .
						($i - 1) .
						")",
						'queryExec',
						4);
					$this->consoleTools->showsleep($i * ($i / 2));
					$i++;
				} else {
					break;
				}
			} elseif ($result === false) {
				$error = 'Unspecified error.';
				break;
			} else {
				return $result;
			}
		}
		if ($silent === false && $this->_debug) {
			$this->echoError($error, 'queryExec', 4);
			$this->debugging->log(get_class(), __FUNCTION__, $query, Logger::LOG_SQL);
		}

		return false;
	}

	/**
	 * For inserting a row. Returns last insert ID. queryExec is better if you do not need the id.
	 *
	 * @param string $query
	 *
	 * @return integer|false|string
	 */
	public function queryInsert($query)
	{
		if (!$this->parseQuery($query)) {
			return false;
		}

		$i = 2;
		$error = '';
		while ($i < 11) {
			$result = $this->queryExecHelper($query, true);
			if (is_array($result) && isset($result['deadlock'])) {
				$error = $result['message'];
				if ($result['deadlock'] === true) {
					$this->echoError("A Deadlock or lock wait timeout has occurred, sleeping. (" .
						($i - 1) . ")",
						'queryInsert',
						4);
					$this->consoleTools->showsleep($i * ($i / 2));
					$i++;
				} else {
					break;
				}
			} elseif ($result === false) {
				$error = 'Unspecified error.';
				break;
			} else {
				return $result;
			}
		}
		if ($this->_debug) {
			$this->echoError($error, 'queryInsert', 4);
			$this->debugging->log(get_class(), __FUNCTION__, $query, Logger::LOG_SQL);
		}

		return false;
	}

	/**
	 * Returns the first row of the query.
	 *
	 * @param string $query
	 * @param bool   $appendLimit
	 *
	 * @return array|bool
	 */
	public function queryOneRow($query, $appendLimit = true)
	{
		// Force the query to only return 1 row, so queryArray doesn't potentially run out of memory on a large data set.
		// First check if query already contains a LIMIT clause.
		if (preg_match('#\s+LIMIT\s+(?P<lower>\d+)(,\s+(?P<upper>\d+))?(;)?$#i',
			$query,
			$matches)) {
			if (!isset($matches['upper']) && isset($matches['lower']) && $matches['lower'] == 1) {
				// good it's already correctly set.
			} else {
				// We have a limit, but it's not for a single row
				return false;
			}
		} else {
			if ($appendLimit) {
				$query .= ' LIMIT 1';
			}
		}

		$rows = $this->query($query);
		if (!$rows || count($rows) == 0) {
			$rows = false;
		}

		return is_array($rows) ? $rows[0] : $rows;
	}

	/**
	 * Rollback transcations. http://www.php.net/manual/en/pdo.rollback.php
	 *
	 * @return bool
	 */
	public function Rollback()
	{
		if (nZEDb_USE_SQL_TRANSACTIONS) {
			return $this->pdo->rollBack();
		}

		return true;
	}

	/**
	 * Converts the result from a query of the settings table into an array.
	 *
	 * @param array $rows
	 *
	 * @return array|bool|mixed
	 */
	public function rowsToArray(array $rows)
	{
		foreach ($rows as $row) {
			if (is_array($row)) {
				$this->rowToArray($row);
			}
		}

		return $this->settings;
	}

	/**
	 * Take the provided row and adds it to the settings array.
	 *
	 * @param array $row
	 */
	public function rowToArray(array $row)
	{
		$this->settings[$row['setting']] = $row['value'];
	}

	/**
	 * Fetch the cavers patch setting from the database and assign it to a constant.
	 *
	 * @return void
	 */
	public function setCovers()
	{
		$path = app\models\Settings::value([
			'section'    => 'site',
			'subsection' => 'main',
			'name'       => 'coverspath',
			'setting'    => 'coverspath',
		]);
		Misc::setCoversConstant($path);
	}

	/**
	 * Update the Settings table using provided form data, which is first validated.
	 *
	 * @param $form
	 *
	 * @return int|null
	 */
	public function settingsUpdate($form)
	{
		$error = $this->settingsValidate($form);

		if ($error === null) {
			$sql = $sqlKeys = [];
			foreach ($form as $settingK => $settingV) {
				$sql[] = sprintf("WHEN %s THEN %s",
					$this->escapeString($settingK),
					$this->escapeString($settingV));
				$sqlKeys[] = $this->escapeString($settingK);
			}

			$this->queryExec(
				sprintf("UPDATE settings SET value = CASE setting %s END WHERE setting IN (%s)",
					implode(' ', $sql),
					implode(', ', $sqlKeys)
				)
			);
		} else {
			$form = $error;
		}

		return $form;
	}

	/**
	 * PHP interpretation of mysql's unix_timestamp method.
	 *
	 * @param string $date
	 *
	 * @return int
	 */
	public function unix_timestamp($date)
	{
		return strtotime($date);
	}

	/**
	 * Get a string for MySQL with a column name in between
	 * ie: UNIX_TIMESTAMP(column_name) AS outputName
	 *
	 * @param string $column     The datetime column.
	 * @param string $outputName The name to store the SQL data into. (the word after AS)
	 *
	 * @return string
	 */
	public function unix_timestamp_column($column, $outputName = 'unix_time')
	{
		return ('UNIX_TIMESTAMP(' . $column . ') AS ' . $outputName);
	}

	/**
	 * Interpretation of mysql's UUID method.
	 * Return uuid v4 string. http://www.php.net/manual/en/function.uniqid.php#94959
	 *
	 * @return string
	 */
	public function uuid()
	{
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0xffff)
		);
	}

	/**
	 * Use the 'vendor' and 'version' fields to check if the server meets the minimum required
	 * version for the DBMS's vendor.
	 *
	 * @return boolean
	 * @throws \RuntimeException if the version is not supported for the vendor.
	 */
	public function validateVendorVersion()
	{
		// 'name' == '' means it is a Sphinx connection.
		if ($this->name != '' && !$this->isVendorVersionValid()) {
			switch (strtolower($this->vendor)) {
				case 'mariadb':
					$minVersion = self::MINIMUM_VERSION_MARIADB;
					break;
				case 'percona':
				default:
					$minVersion = self::MINIMUM_VERSION_MYSQL;
			}
			throw new \RuntimeException("Minimum version for vendor '{$this->vendor}' is {$minVersion}, current version is: '{$this->version}''",
				1);
		}
	}



	/**
	 * Verify that we've lost a connection to MySQL.
	 *
	 * @param string $errorMessage
	 *
	 * @return boolean
	 */
	protected function checkGoneAway($errorMessage)
	{
		return (stripos($errorMessage, 'MySQL server has gone away') !== false);
	}

	/**
	 * Connect to database
	 *
	 * @param array $options
	 *
	 * @return bool
	 * @throws \ErrorException
	 */
	protected function connect(array $options)
	{
		if (!empty($options['dbsock'])) {
			$dsn = $this->dbSystem . ':unix_socket=' . $options['dbsock'];
		} else {
			$dsn = $this->dbSystem . ':host=' . $options['dbhost'];
			if (!empty($options['dbport'])) {
				$dsn .= ';port=' . $options['dbport'];
			}
		}
		$dsn .= ';charset=utf8';

		$this->dsn = $dsn;

		$connectionOptions = [
			\PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_TIMEOUT            => 180,
			\PDO::ATTR_PERSISTENT         => $options['persist'],
			\PDO::MYSQL_ATTR_LOCAL_INFILE => true
		];

		// removed try/catch to let the instantiating code handle the problem (Install for
		// instance can output a message that connecting failed.
		$this->pdo = new \PDO($dsn, $options['dbuser'], $options['dbpass'], $connectionOptions);

		// For backwards compatibility, no need for a patch.
		// This forces field names to always be lower-cased and returned rows to be associative
		// arrays not numerical
		$this->pdo->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);
		$this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

		// In case PDO is not set to produce exceptions (PHP's default behaviour).
		if ($this->pdo === false) {
			$message = "Unable to create connection to the Database!";
			$this->echoError(
				$message,
				'connect',
				1,
				true
			);
			throw new \ErrorException($message, 1);
		}

		$this->host		= $options['dbhost'];
		$this->name		= $options['dbname'];
		$this->password	= $options['dbpass'];
		$this->persist	= $options['persist'];
		$this->port		= $options['dbport'];
		$this->socket	= $options['dbsock'];
		$this->user		= $options['dbuser'];

		return true;
	}

	/**
	 * Echo error, optionally exit.
	 *
	 * @param string $error    The error message.
	 * @param string $method   The method where the error occured.
	 * @param int    $severity The severity of the error.
	 * @param bool   $exit     Exit or not?
	 */
	protected function echoError($error, $method, $severity, $exit = false)
	{
		if ($this->_debug) {
			$this->debugging->log(get_class(), $method, $error, $severity);

			echo(
			($this->cli ? $this->log->error($error) . PHP_EOL :
				'<div class="error">' . $error . '</div>')
			);
		}

		if ($exit) {
			exit();
		}
	}

	/**
	 * Helper method for queryInsert and queryExec, checks for deadlocks.
	 *
	 * @param string $query
	 * @param bool   $insert
	 *
	 * @return array|\PDOStatement
	 */
	protected function queryExecHelper($query, $insert = false)
	{
		try {
			if ($insert === false) {
				$run = $this->pdo->prepare($query);
				$run->execute();

				return $run;
			} else {
				$ins = $this->pdo->prepare($query);
				$ins->execute();

				return $this->pdo->lastInsertId();
			}
		} catch (\PDOException $e) {
			// Deadlock or lock wait timeout, try 10 times.
			if (
				$e->errorInfo[1] == 1213 ||
				$e->errorInfo[0] == 40001 ||
				$e->errorInfo[1] == 1205 ||
				$e->getMessage() ==
				'SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction'
			) {
				return ['deadlock' => true, 'message' => $e->getMessage()];
			} // Check if we lost connection to MySQL.
			else {
				if ($this->checkGoneAway($e->getMessage()) !== false) {

					// Reconnect to MySQL.
					if ($this->reconnect() === true) {

						// If we reconnected, retry the query.
						return $this->queryExecHelper($query, $insert);
					}
				}
			}

			return ['deadlock' => false, 'message' => $e->getMessage()];
		}
	}

	/**
	 * Reconnect to MySQL when the connection has been lost.
	 *
	 * @see ping(), checkGoneAway() for checking the connection.
	 * @return bool
	 */
	protected function reconnect()
	{
		$this->connect([
			'checkVersion' => false,
			'createDb'     => false,
			'dbhost'       => $this->host,
			'dbname'       => $this->name, // '' means it is a Sphinx connection
			'dbpass'       => $this->password,
			'dbport'       => $this->port,
			'dbsock'       => $this->socket,
			'dbtype'       => $this->dbSystem,
			'dbuser'       => $this->user,
			'persist'      => $this->persist,
		]);

		return $this->ping();
	}

	/**
	 * Validate the provided array, which should be from the settings edit page.
	 *
	 * @param array $fields
	 *
	 * @return int|null
	 */
	protected function settingsValidate(array $fields)
	{
		$defaults = [
			'checkpasswordedrar' => false,
			'ffmpegpath'         => '',
			'mediainfopath'      => '',
			'nzbpath'            => '',
			'tmpunrarpath'       => '',
			'unrarpath'          => '',
			'yydecoderpath'      => '',
		];
		$fields += $defaults;    // Make sure keys exist to avoid error notices.
		ksort($fields);
		// Validate settings
		$fields['nzbpath'] = Text::trailingSlash($fields['nzbpath']);
		$error = null;
		switch (true) {
			case ($fields['mediainfopath'] != '' && !is_file($fields['mediainfopath'])):
				$error = Settings::ERR_BADMEDIAINFOPATH;
				break;
			case ($fields['ffmpegpath'] != '' && !is_file($fields['ffmpegpath'])):
				$error = Settings::ERR_BADFFMPEGPATH;
				break;
			case ($fields['unrarpath'] != '' && !is_file($fields['unrarpath'])):
				$error = Settings::ERR_BADUNRARPATH;
				break;
			case (empty($fields['nzbpath'])):
				$error = Settings::ERR_BADNZBPATH_UNSET;
				break;
			case (!file_exists($fields['nzbpath']) || !is_dir($fields['nzbpath'])):
				$error = Settings::ERR_BADNZBPATH;
				break;
			case (!is_readable($fields['nzbpath'])):
				$error = Settings::ERR_BADNZBPATH_UNREADABLE;
				break;
			case ($fields['checkpasswordedrar'] == 1 && !is_file($fields['unrarpath'])):
				$error = Settings::ERR_DEEPNOUNRAR;
				break;
			case ($fields['tmpunrarpath'] != '' && !file_exists($fields['tmpunrarpath'])):
				$error = Settings::ERR_BADTMPUNRARPATH;
				break;
			case ($fields['yydecoderpath'] != '' &&
				$fields['yydecoderpath'] !== 'simple_php_yenc_decode' &&
				!file_exists($fields['yydecoderpath'])):
				$error = Settings::ERR_BAD_YYDECODER_PATH;
		}

		return $error;
	}

	/**
	 * Fetch information from the server returned by the SQL VERSION() function. This is parsed
	 * into an array for returning.
	 *
	 * @return array
	 */
	private function fetchServerInfo()
	{
		$info = [];
		$result = $this->queryOneRow("SELECT VERSION() as version");

		if ($result === null) {
			throw new \RuntimeException("Could not fetch database server info!", 5);
		} else {
			$this->dbInfo = $result['version'];
			$result = explode('-', $result['version']);
			$info['vendor'] = count($result) > 1 ? strtolower($result[1]) : 'mysql';
			$info['version'] = $result[0];

			switch ($info['vendor']) {
				case 'mariadb':
				case 'mysql':
				case 'percona':
					break;
				default:
					$info['vendor'] = 'mysql';
			}
		}

		return $info;
	}

	/**
	 * Initialise the database. NOTE this does not include the tables it just creates the database.
	 *
	 * @param string $name	The name of the database.
	 *
	 * @throws \RuntimeException
	 */
	private function initialiseDatabase($name)
	{
		if (empty($name)) {
			throw new \RuntimeException("No database name passed to " . __METHOD__, 1);
		}

		$found = self::checkDbExists($name);
		if ($found) {
			try {
				$this->pdo->query("DROP DATABASE $name");
			} catch (\Exception $e) {
				throw new \RuntimeException("Error trying to drop your old database: '{$name}'\n" .
					$e->getMessage(),
					2);
			}
			$found = self::checkDbExists($name);
		}

		if ($found) {
			//var_dump(self::getTableList());
			throw new \RuntimeException("Could not drop your old database: '{$name}'" ,
				2);
		} else {
			$this->pdo->query("CREATE DATABASE `{$name}` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci");

			if (!self::checkDbExists($name)) {
				throw new \RuntimeException("Could not create new database: '{$name}'",
					3);
			}
		}
	}

	/**
	 * Log/echo repaired/optimized/analyzed tables.
	 *
	 * @param bool   $web    If we are on web, don't echo.
	 * @param string $type   ANALYZE|OPTIMIZE|REPAIR
	 * @param string $tables Table names.
	 *
	 * @access private
	 * @void
	 */
	private function logOptimize($web, $type, $tables)
	{
		$message = $type . ' (' . $tables . ')';
		if ($web === false) {
			echo $this->log->primary($message);

		}
		if ($this->_debug) {
			$this->debugging->log(get_class(), __FUNCTION__, $message, Logger::LOG_INFO);
		}
	}

	/**
	 * Checks if the query is empty. Cleans the query of whitespace if needed.
	 *
	 * @param string $query
	 *
	 * @return boolean
	 */
	private function parseQuery(&$query)
	{
		if (empty($query)) {
			return false;
		}

		if (nZEDb_QUERY_STRIP_WHITESPACE) {
			$query = Text::collapseWhiteSpace($query);
		}
		return true;
	}

	/**
	 * Populate 'vendor' and 'version' fields.
	 *
	 * @access private
	 * @void
	 */
	private function setServerInfo()
	{
		$dummy = $this->fetchServerInfo();

		$this->vendor = $dummy['vendor'];
		$this->version = $dummy['version'];
	}
}
