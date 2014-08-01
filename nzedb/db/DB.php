<?php
namespace nzedb\db;
use \nzedb\utility\Utility;
use \nzedb\libraries\Cache;
use \nzedb\libraries\CacheException;

//use nzedb\controllers\ColorCLI;

/**
 * Class for handling connection to database (MySQL or PostgreSQL) using PDO.
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
	/**
	 * @var bool Is this a Command Line Interface instance.
	 *
	 * This needs to be revisited when moving to li3. Web pages do not need this class so it shouldn't be included by default.
	 */
	public $cli;

	/**
	 * @var object Instance of ConsoleTools class.
	 */
	public $ct;

	/**
	 * @var \ColorCLI	Instance variable for logging object. Currently only ColorCLI supported,
	 * but expanding for full logging with agnostic API planned.
	 */
	public $log;

	/**
	 * @var \PDO Instance of PDO class.
	 */
	public static $pdo = null;

	/**
	 * @var bool
	 */
	protected $_debug;

	/**
	 * @var object Class instance debugging.
	 */
	private $debugging;

	/**
	 * @var string Lower-cased name of DBMS in use.
	 */
	private $dbSystem;

	/**
	 * @var string Version of the Db server.
	 */
	private $dbVersion;

	/**
	 * @var string	Stored copy of the dsn used to connect.
	 */
	private $dsn;

	/**
	 * @var array    Options passed into the constructor or defaulted.
	 */
	private $opts;

	/**
	 * @var null|\nzedb\libraries\Cache
	 */
	private $cacheServer = null;

	/**
	 * @var bool Should we cache the results of the query method?
	 */
	private $cacheEnabled = false;

	/**
	 * Constructor. Sets up all necessary properties. Instantiates a PDO object
	 * if needed, otherwise returns the current one.
	 */
	public function __construct(array $options = array())
	{
		$this->cli = Utility::isCLI();

		$defaults = array(
			'checkVersion'	=> false,
			'createDb'		=> false, // create dbname if it does not exist?
			'ct'			=> new \ConsoleTools(),
			'dbhost'		=> defined('DB_HOST') ? DB_HOST : '',
			'dbname' 		=> defined('DB_NAME') ? DB_NAME : '',
			'dbpass' 		=> defined('DB_PASSWORD') ? DB_PASSWORD : '',
			'dbport'		=> defined('DB_PORT') ? DB_PORT : '',
			'dbsock'		=> defined('DB_SOCKET') ? DB_SOCKET : '',
			'dbtype'		=> defined('DB_SYSTEM') ? DB_SYSTEM : '',
			'dbuser' 		=> defined('DB_USER') ? DB_USER : '',
			'log'			=> new \ColorCLI(),
			'persist'		=> false,
		);
		$options += $defaults;
		if (!$this->cli) {
			$options['log'] = null;
		}
		$this->opts = $options;

		if (!empty($this->opts['dbtype'])) {
			$this->dbSystem = strtolower($this->opts['dbtype']);
		}

		if (!(self::$pdo instanceof \PDO)) {
			$this->initialiseDatabase();
		}

		$this->cacheEnabled = (defined('nZEDb_CACHE_TYPE') && (nZEDb_CACHE_TYPE > 0) ? true : false);

		if ($this->cacheEnabled) {
			try {
				$this->cacheServer = new Cache();
			} catch (CacheException $error) {
				$this->cacheEnabled = false;
				$this->echoError($error->getMessage(), '__construct', 4);
			}
		}

		$this->ct = $this->opts['ct'];
		$this->log = $this->opts['log'];

		$this->_debug = (nZEDb_DEBUG || nZEDb_LOGGING);
		if ($this->_debug) {
			$this->debugging = new \Debugging(['Class' => 'DB', 'ColorCLI' => $this->log]);
		}


		if ($this->opts['checkVersion']) {
			$this->fetchDbVersion();
		}

		return self::$pdo;
	}

	public function checkDbExists ($name = null)
	{
		if (empty($name)) {
			$name = $this->opts['dbname'];
		}

		$found  = false;
		$tables = self::getTableList();
		foreach ($tables as $table) {
			if ($table['Database'] == $name) {
				//var_dump($tables);
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
		$result = self::$pdo->query(
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

	public function checkColumnIndex($table, $column)
	{
		$result = self::$pdo->query(
			sprintf(
				"SHOW INDEXES IN %s WHERE non_unique = 0 AND column_name = '%s'",
				trim($table),
				trim($column)
			)
		);
		if ($result === false) {
			return false;
		}
//var_dump($result);
		return $result->fetchAll(\PDO::FETCH_ASSOC);
	}

	public function getTableList ()
	{
		$query  = ($this->opts['dbtype'] === 'mysql' ? 'SHOW DATABASES' : 'SELECT datname AS Database FROM pg_database');
		$result = self::$pdo->query($query);
		return $result->fetchAll(\PDO::FETCH_ASSOC);
	}

	/**
	 * @return bool Whether the Db is definitely on the local machine.
	 */
	public function isLocalDb ()
	{
		if (!empty($this->opts['dbsock']) || $this->opts['dbhost'] == 'localhost') {
			return true;
		}

		preg_match_all('/inet' . '6?' . ' addr: ?([^ ]+)/', `ifconfig`, $ips);

		// Check for dotted quad - if exists compare against local IP number(s)
		if (preg_match('#^\d+\.\d+\.\d+\.\d+$#', $this->opts['dbhost'])) {
			if (in_array($this->opts['dbhost'], $ips[1])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Init PDO instance.
	 */
	private function initialiseDatabase()
	{
		if ($this->dbSystem === 'mysql') {
			if (!empty($this->opts['dbsock'])) {
				$dsn = $this->dbSystem . ':unix_socket=' . $this->opts['dbsock'];
			} else {
				$dsn = $this->dbSystem . ':host=' . $this->opts['dbhost'];
				if (!empty($this->opts['dbport'])) {
					$dsn .= ';port=' . $this->opts['dbport'];
				}
			}
		} else {
			$dsn = $this->dbSystem . ':host=' . $this->opts['dbhost'] . ';dbname=' . $this->opts['dbname'];
		}
		$dsn .= ';charset=utf8';

		$options = array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_TIMEOUT => 180,
						 \PDO::ATTR_PERSISTENT => $this->opts['persist']);
		if ($this->dbSystem === 'mysql') {
			$options[\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
			$options[\PDO::MYSQL_ATTR_LOCAL_INFILE] = true;
		}

		$this->dsn = $dsn;
		// removed try/catch to let the instantiating code handle the problem (Install for
		// instance can output a message that connecting failed.
		self::$pdo = new \PDO($dsn, $this->opts['dbuser'], $this->opts['dbpass'], $options);

		$found = self::checkDbExists();
		if ($this->opts['dbtype'] === 'pgsql' && !$found) {
			throw new \RuntimeException(
				'Could not find your database: ' . $this->opts['dbname'] .
				', please see Install.txt for instructions on how to create a database.',
				1
			);
		}

		if ($this->opts['createDb']) {
			if ($found) {
				try {
					self::$pdo->query("DROP DATABASE " . $this->opts['dbname']);
				} catch (\Exception $e) {
					throw new \RuntimeException("Error trying to drop your old database: '{$this->opts['dbname']}'", 2);
				}
				$found = self::checkDbExists();
			}

			if ($found) {
				var_dump(self::getTableList());
				throw new \RuntimeException("Could not drop your old database: '{$this->opts['dbname']}'", 2);
			} else {
				self::$pdo->query("CREATE DATABASE `{$this->opts['dbname']}`  DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci");

				if (!self::checkDbExists()) {
					throw new \RuntimeException("Could not create new database: '{$this->opts['dbname']}'", 3);
				}
			}
		}
		self::$pdo->query("USE {$this->opts['dbname']}");
		//		var_dump('made it here');

		// In case PDO is not set to produce exceptions (PHP's default behaviour).
		if (self::$pdo === false) {
			$this->echoError(
				 "Unable to create connection to the Database!",
				 'initialiseDatabase',
				 1,
				 true
			);
		}

		// For backwards compatibility, no need for a patch.
		self::$pdo->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);
		self::$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
	}

	/**
	 * Echo error, optionally exit.
	 *
	 * @param string     $error    The error message.
	 * @param string     $method   The method where the error occured.
	 * @param int        $severity The severity of the error.
	 * @param bool       $exit     Exit or not?
	 */
	protected function echoError($error, $method, $severity, $exit = false)
	{
		if ($this->_debug) {
			$this->debugging->start($method, $error, $severity);

			echo(
				($this->cli ? $this->log->error($error) . PHP_EOL : '<div class="error">' . $error . '</div>')
			);
		}

		if ($exit) {
			exit();
		}
	}

	/**
	 * @return string mysql or pgsql.
	 */
	public function DbSystem()
	{
		return $this->dbSystem;
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

		return self::$pdo->quote($str);
	}

	/**
	 * Formats a 'like' string. ex.(LIKE '%chocolate%')
	 *
	 * @param string $str    The string.
	 * @param bool   $left   Add a % to the left.
	 * @param bool   $right  Add a % to the right.
	 *
	 * @return string
	 */
	public function likeString($str, $left=true, $right=true)
	{
		return (
			'LIKE ' .
			$this->escapeString(
				($left  ? '%' : '') .
				$str .
				($right ? '%' : '')
			)
		);
	}

	/**
	 * Verify if pdo var is instance of PDO class.
	 *
	 * @return bool
	 */
	public function isInitialised()
	{
		return (self::$pdo instanceof \PDO);
	}

	/**
	 * For inserting a row. Returns last insert ID. queryExec is better if you do not need the id.
	 *
	 * @param string $query
	 *
	 * @return bool|int
	 */
	public function queryInsert($query)
	{
		if (empty($query)) {
			return false;
		}

		if (nZEDb_QUERY_STRIP_WHITESPACE) {
			$query = Utility::collapseWhiteSpace($query);
		}

		$i = 2;
		$error = '';
		while($i < 11) {
			$result = $this->queryExecHelper($query, true);
			if (is_array($result) && isset($result['deadlock'])) {
				$error = $result['message'];
				if ($result['deadlock'] === true) {
					$this->echoError("A Deadlock or lock wait timeout has occurred, sleeping.(" . ($i-1) . ")", 'queryInsert', 4);
					$this->ct->showsleep($i * ($i/2));
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
			$this->debugging->start("queryInsert", $query, 6);
		}
		return false;
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
		if (empty($query)) {
			return false;
		}

		if (nZEDb_QUERY_STRIP_WHITESPACE) {
			$query = Utility::collapseWhiteSpace($query);
		}

		$i = 2;
		$error = '';
		while($i < 11) {
			$result = $this->queryExecHelper($query);
			if (is_array($result) && isset($result['deadlock'])) {
				$error = $result['message'];
				if ($result['deadlock'] === true) {
					$this->echoError("A Deadlock or lock wait timeout has occurred, sleeping.(" . ($i-1) . ")", 'queryExec', 4);
					$this->ct->showsleep($i * ($i/2));
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
			$this->debugging->start("queryExec", $query, 6);
		}
		return false;
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
			if ($insert === false ) {
				$run = self::$pdo->prepare($query);
				$run->execute();
				return $run;
			} else {
				if ($this->dbSystem === 'mysql') {
					$ins = self::$pdo->prepare($query);
					$ins->execute();
					return self::$pdo->lastInsertId();
				} else {
					$p = self::$pdo->prepare($query . ' RETURNING id');
					$p->execute();
					$r = $p->fetch(\PDO::FETCH_ASSOC);
					return $r['id'];
				}
			}

		} catch (\PDOException $e) {
			// Deadlock or lock wait timeout, try 10 times.
			if (
				$e->errorInfo[1] == 1213 ||
				$e->errorInfo[0] == 40001 ||
				$e->errorInfo[1] == 1205 ||
				$e->getMessage() == 'SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction'
			) {
				return array('deadlock' => true, 'message' => $e->getMessage());
			}

			// Check if we lost connection to MySQL.
			else if ($this->_checkGoneAway($e->getMessage()) !== false) {

				// Reconnect to MySQL.
				if ($this->_reconnect() === true) {

					// If we reconnected, retry the query.
					return $this->queryExecHelper($query, $insert);

				}
			}

			return array ('deadlock' => false, 'message' => $e->getMessage());
		}
	}

	/**
	 * Direct query. Return the affected row count. http://www.php.net/manual/en/pdo.exec.php
	 *
	 * @note If not "consumed", causes this error:
	 *       'SQLSTATE[HY000]: General error: 2014 Cannot execute queries while other unbuffered queries are active.
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
		if (empty($query)) {
			return false;
		}

		if (nZEDb_QUERY_STRIP_WHITESPACE) {
			$query = Utility::collapseWhiteSpace($query);
		}

		try {
			return self::$pdo->exec($query);

		} catch (\PDOException $e) {

			// Check if we lost connection to MySQL.
			if ($this->_checkGoneAway($e->getMessage()) !== false) {

				// Reconnect to MySQL.
				if ($this->_reconnect() === true) {

					// If we reconnected, retry the query.
					return $this->exec($query, $silent);

				} else {
					// If we are not reconnected, return false.
					return false;
				}

			} else if (!$silent) {
				$this->echoError($e->getMessage(), 'Exec', 4, false);

				if ($this->_debug) {
					$this->debugging->start("Exec", $query, 6);
				}
			}

			return false;
		}
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
		if (empty($query)) {
			return false;
		}

		if (nZEDb_QUERY_STRIP_WHITESPACE) {
			$query = Utility::collapseWhiteSpace($query);
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

		if ($cache === true && $this->cacheEnabled === true) {
			$this->cacheServer->set($this->cacheServer->createKey($query), $result, $cacheExpiry);
		}

		return ($result === false) ? array() : $result;
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
	 *
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
		$mode = self::$pdo->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE);
		if ($mode != \PDO::FETCH_ASSOC) {
			self::$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
		}

		$result = $this->queryArray($query);

		if ($mode != \PDO::FETCH_ASSOC) {
			self::$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
		}
		return $result;
	}

	/**
	 * Query without returning an empty array like our function query(). http://php.net/manual/en/pdo.query.php
	 *
	 * @param string $query The query to run.
	 *
	 * @return bool|\PDOStatement
	 */
	public function queryDirect($query)
	{
		if (empty($query)) {
			return false;
		}

		if (nZEDb_QUERY_STRIP_WHITESPACE) {
			$query = Utility::collapseWhiteSpace($query);
		}

		try {
			$result = self::$pdo->query($query);
		} catch (\PDOException $e) {

			// Check if we lost connection to MySQL.
			if ($this->_checkGoneAway($e->getMessage()) !== false) {

				// Reconnect to MySQL.
				if ($this->_reconnect() === true) {

					// If we reconnected, retry the query.
					$result = $this->queryDirect($query);

				} else {
					// If we are not reconnected, return false.
					$result = false;
				}

			} else {
				$this->echoError($e->getMessage(), 'queryDirect', 4, false);
				if ($this->_debug) {
					$this->debugging->start("queryDirect", $query, 6);
				}
				$result = false;
			}
		}
		return $result;
	}

	/**
	 * Reconnect to MySQL when the connection has been lost.
	 *
	 * @see ping(), _checkGoneAway() for checking the connection.
	 *
	 * @return bool
	 */
	protected function _reconnect()
	{
		$this->initialiseDatabase();

		// Check if we are really connected to MySQL.
		if ($this->ping() === false) {
			// If we are not reconnected, return false.
			return false;
		}
		return true;
	}

	/**
	 * Verify that we've lost a connection to MySQL.
	 *
	 * @param string $errorMessage
	 *
	 * @return bool
	 */
	protected function _checkGoneAway($errorMessage)
	{
		if (stripos($errorMessage, 'MySQL server has gone away') !== false) {
			return true;
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
		if (preg_match('#\s+LIMIT\s+(?P<lower>\d+)(,\s+(?P<upper>\d+))?(;)?$#i', $query, $matches)) {
			If (!isset($matches['upper']) && isset($matches['lower']) && $matches['lower'] == 1) {
				// good it's already correctly set.
			} else { // We have a limit, but it's not for a single row
				return false;
			}

		} else if ($appendLimit) {
			$query .= ' LIMIT 1';
		}

		$rows = $this->query($query);
		if (!$rows || count($rows) == 0) {
			$rows = false;
		}

		return is_array($rows) ? $rows[0] : $rows;
	}

	/**
	 * Optimises/repairs tables on mysql. Vacuum/analyze on postgresql.
	 *
	 * @param bool   $admin
	 * @param string $type  'true'    Force optimize of all tables.
	 *                      'full'    Force optimize of all tables.
	 *                      'all'     Optimise tables with 5% or more free space.
	 *                      'run'     Optimise tables with 5% or more free space.
	 *                      'analyze' Analyze tables to rebuild statistics.
	 *
	 * @return int Quantity optimized/analyzed
	 */
	public function optimise($admin = false, $type = '')
	{
		$tableArray = $myIsamTables = false;

		if ($type === 'true' || $type === 'full' || $type === 'analyze') {
			$tableArray = $this->queryDirect('SHOW TABLE STATUS ');
			$myIsamTables = $this->queryDirect("SHOW TABLE STATUS WHERE ENGINE LIKE 'myisam'");
		} else if ($type === 'all' || $type === 'run') {
			$tableArray = $this->queryDirect('SHOW TABLE STATUS WHERE Data_free / Data_length > 0.005');
			$myIsamTables = $this->queryDirect("SHOW TABLE STATUS WHERE ENGINE LIKE 'myisam' AND Data_free / Data_length > 0.005");
		}

		$optimised = 0;
		if ($tableArray !== false && $tableArray->rowCount() > 0) {

			$tableNames = '';
			foreach ($tableArray as $table) {
				$tableNames .= $table['name'] . ',';
			}
			$tableNames = rtrim($tableNames, ',');

			if ($type === 'analyze') {

				$this->queryExec('ANALYZE LOCAL TABLE ' . $tableNames);
				$this->logOptimize($admin, 'ANALYZE', $tableNames);

			} else {

				$this->queryExec('OPTIMIZE LOCAL TABLE ' . $tableNames);
				$this->logOptimize($admin, 'OPTIMIZE', $tableNames);

				if ($myIsamTables !== false && $myIsamTables->rowCount() > 0) {
					$tableNames = '';
					foreach ($myIsamTables as $table) {
						$tableNames .= $table['name'] . ',';
					}
					$this->queryExec('REPAIR LOCAL TABLE ' . rtrim($tableNames, ','));
					$this->logOptimize($admin, 'REPAIR', $tableNames);
				}

				$this->queryExec('FLUSH TABLES');
			}
			$optimised = $tableArray->rowCount();
		}

		return $optimised;
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
		$message = $type . ' table: ' . $tables;
		if ($web === false) {
			echo $this->log->primary($message);

		}
		if ($this->_debug) {
			$this->debugging->start("optimise", $message, 5);
		}
	}

	/**
	 * Turns off autocommit until commit() is ran. http://www.php.net/manual/en/pdo.begintransaction.php
	 *
	 * @return bool
	 */
	public function beginTransaction()
	{
		if (nZEDb_USE_SQL_TRANSACTIONS) {
			return self::$pdo->beginTransaction();
		}
		return true;
	}

	/**
	 * Commits a transaction. http://www.php.net/manual/en/pdo.commit.php
	 *
	 * @return bool
	 */
	public function Commit()
	{
		if (nZEDb_USE_SQL_TRANSACTIONS) {
			return self::$pdo->commit();
		}
		return true;
	}

	/**
	 * Rollback transcations. http://www.php.net/manual/en/pdo.rollback.php
	 *
	 * @return bool
	 */
	public function Rollback()
	{
		if (nZEDb_USE_SQL_TRANSACTIONS) {
			return self::$pdo->rollBack();
		}
		return true;
	}

	/**
	 * PHP interpretation of MySQL's from_unixtime method.
	 * @param int  $utime UnixTime
	 *
	 * @return bool|string
	 */
	public function from_unixtime($utime)
	{
		return 'FROM_UNIXTIME(' . $utime . ')';
	}

	/**
	 * PHP interpretation of mysql's unix_timestamp method.
	 * @param string $date
	 *
	 * @return int
	 */
	public function unix_timestamp($date)
	{
		return strtotime($date);
	}

	/**
	 * Get a string for MySQL or PgSql with a column name in between
	 * MySQL: UNIX_TIMESTAMP(column_name) AS outputName
	 * PgSQL: EXTRACT('EPOCH' FROM column_name)::INT AS outputName;
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
			return (bool) self::$pdo->query('SELECT 1+1');
		} catch (\PDOException $e) {
			if ($restart == true) {
				$this->initialiseDatabase();
			}
			return false;
		}
	}

	/**
	 * Prepares a statement to be run by the Db engine.
	 * To run the statement use the returned $statement with ->execute();
	 *
	 * Ideally the signature would have array before $options but that causes a strict warning.
	 *
	 * @param string $query SQL query to run, with optional place holders.
	 * @param array $options Driver options.
	 *
	 * @return false|\PDOstatement on success false on failure.
	 *
	 * @link http://www.php.net/pdo.prepare.php
	 */
	public function Prepare($query, $options = array())
	{
		try {
			$PDOstatement = self::$pdo->prepare($query, $options);
		} catch (\PDOException $e) {
			if ($this->_debug) {
				$this->debugging->start("Prepare", $e->getMessage(), 5);
			}
			echo $this->log->error("\n" . $e->getMessage());
			$PDOstatement = false;
		}
		return $PDOstatement;
	}

	/**
	 * Retrieve db attributes http://us3.php.net/manual/en/pdo.getattribute.php
	 *
	 * @param int $attribute
	 *
	 * @return bool|mixed
	 */
	public function getAttribute($attribute)
	{
		$result = false;
		if ($attribute != '') {
			try {
				$result = self::$pdo->getAttribute($attribute);
			} catch (\PDOException $e) {
				if ($this->_debug) {
					$this->debugging->start("getAttribute", $e->getMessage(), 5);
				}
				echo $this->log->error("\n" . $e->getMessage());
				$result = false;
			}

		}
		return $result;
	}

	/**
	 * Returns the stored Db version string.
	 *
	 * @return string
	 */
	public function getDbVersion ()
	{
		return $this->dbVersion;
	}

	/**
	 * @param string $requiredVersion The minimum version to compare against
	 *
	 * @return bool|null       TRUE if Db version is greater than or eaqual to $requiredVersion,
	 * false if not, and null if the version isn't available to check against.
	 */
	public function isDbVersionAtLeast ($requiredVersion)
	{
		if (empty($this->dbVersion)) {
			return null;
		}
		return version_compare($requiredVersion, $this->dbVersion, '<=');
	}

	/**
	 * Performs the fetch from the Db server and stores the resulting Major.Minor.Version number.
	 */
	private function fetchDbVersion ()
	{
		$result = $this->queryOneRow("SELECT VERSION() AS version");
		if (!empty($result)) {
			$dummy = explode('-', $result['version'], 2);
			$this->dbVersion = $dummy[0];
		}
	}

}
