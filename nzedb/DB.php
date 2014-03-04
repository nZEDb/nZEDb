<?php

/**
 * Class for handling connection to database (MySQL or PostgreSQL) using PDO.
 *
 * The class extends PDO, thereby exposing all of PDO's functionality directly
 * without the need to wrap each and every method here.
 *
 * Exceptions are caught and displayed to the user.
 * Properties are explicitly created, so IDEs can offer autocompletion for them.
 */
class DB extends PDO
{
	/**
	 * @var object Instance of ColorCLI class.
	 */
	public $c;

	/**
	 * @var object Instance of ConsoleTools class.
	 */
	public $consoletools;

	/**
	 * @var string Lower-cased name of DBMS in use.
	 */
	public $dbsystem;

	/**
	 * @var bool	Whether memcache is enabled.
	 */
	public $memcached;

	/**
	 * @var object Instance of PDO class.
	 */
	private static $pdo = null;

	/**
	 * @var object Class instance debugging.
	 */
	private $debugging;

	/**
	 * Constructor. Sets up all necessary properties. Instantiates a PDO object
	 * if needed, otherwise returns the current one.
	 */
	public function __construct()
	{
		$this->c = new ColorCLI();
		$this->debugging = new Debugging("DB");

		if (defined('DB_SYSTEM') && strlen(DB_SYSTEM) > 0) {
			$this->dbsystem = strtolower(DB_SYSTEM);
		} else if (PHP_SAPI == 'cli') {
			$message = "\nconfig.php is missing the DB_SYSTEM setting. Add the following in that file:\n define('DB_SYSTEM', 'mysql');";
			$this->debugging->start("__construct", $message, 1);
			exit($this->c->error($message));
		} else {
			$this->debugging->start("__construct", "config.php is missing the DB_SYSTEM setting. Add the following in that file: define('DB_SYSTEM', 'mysql');", 1);
			echo "<div class=\"error\">config.php is missing the DB_SYSTEM setting. Add the following in that file:<br/> define('DB_SYSTEM', 'mysql');</div>";
			exit();
		}

		if (!(self::$pdo instanceof PDO)) {
			$this->initialiseDatabase();
		}

		if (defined("MEMCACHE_ENABLED")) {
			$this->memcached = MEMCACHE_ENABLED;
		} else {
			$this->memcached = false;
		}
		$this->consoletools = new ConsoleTools();

		return self::$pdo;
	}

	private function initialiseDatabase()
	{
		if ($this->dbsystem == 'mysql') {
			if (defined('DB_SOCKET') && DB_SOCKET != '') {
				$dsn = $this->dbsystem . ':unix_socket=' . DB_SOCKET . ';dbname=' . DB_NAME;
			} else {
				$dsn = $this->dbsystem . ':host=' . DB_HOST . ';dbname=' . DB_NAME;
				if (defined('DB_PORT')) {
					$dsn .= ';port=' . DB_PORT;
				}
			}
			$dsn .= ';charset=utf8';
		} else {
			$dsn = $this->dbsystem . ':host=' . DB_HOST . ';dbname=' . DB_NAME;
		}

		try {
			$options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 180, PDO::MYSQL_ATTR_LOCAL_INFILE => true);
			if ($this->dbsystem == 'mysql') {
				$options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
			}

			self::$pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
			if (self::$pdo === false) { // In case PDO is not set to produce exceptions (PHP's default behaviour).
				$message = "Unable to create connection to the Database!\n";
				$this->debugging->start("initialiseDatabase", $message, 1);
				exit($message);
			}
			// For backwards compatibility, no need for a patch.
			self::$pdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
			self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		} catch (PDOException $e) {
			if (PHP_SAPI == 'cli') {
				$message = "\nConnection to the SQL server failed, error was: (" . $e->getMessage() . ")";
				$this->debugging->start("initialiseDatabase", $message, 1);
				exit($message);
			} else {
				$this->debugging->start("initialiseDatabase", "Connection to the SQL server failed, error was: (" . $e->getMessage() . ")", 1);
				echo "<div class=\"error\">Connection to the SQL server failed, error was: ({$e->getMessage()});</div>";
				exit();
			}
		}
	}

	/**
	 * @return string; mysql or pgsql.
	 */
	public function dbSystem()
	{
		return $this->dbsystem;
	}

	// Returns a string, escaped with single quotes, false on failure. http://www.php.net/manual/en/pdo.quote.php
	public function escapeString($str)
	{
		if (is_null($str)) {
			return 'NULL';
		}

		return self::$pdo->quote($str);
	}

	public function isInitialised()
	{
		return (self::$pdo instanceof PDO);
	}

	// For inserting a row. Returns last insert ID. queryExec is better if you do not need the id.
	public function queryInsert($query, $i = 1)
	{
		if ($query == '') {
			return false;
		}

		try {
			if ($this->dbsystem() == 'mysql') {
				$ins = self::$pdo->prepare($query);
				$ins->execute();
				return self::$pdo->lastInsertId();
			} else {
				$p = self::$pdo->prepare($query . ' RETURNING id');
				$p->execute();
				$r = $p->fetch(PDO::FETCH_ASSOC);
				return $r['id'];
			}
		} catch (PDOException $e) {
			// Deadlock or lock wait timeout, try 10 times.
			while (($e->errorInfo[1] == 1213 || $e->errorInfo[0] == 40001 || $e->errorInfo[1] == 1205 || $e->getMessage() == 'SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction') && $i <= 10) {
				$message = "\nA Deadlock or lock wait timeout has occurred, sleeping.\n";
				$this->debugging->start("queryInsert", $message, 4);
				echo $this->c->error($message);
				$this->consoletools->showsleep($i * $i);
				$this->queryInsert($query, $i++);
			}
			if ($e->errorInfo[1] == 1213 || $e->errorInfo[0] == 40001 || $e->errorInfo[1] == 1205) {
				$this->debugging->start("queryInsert", "Deadlock or lock wait timeout, try increasing innodb_lock_wait_timeout.", 4);
			} else if ($e->errorInfo[1] == 1062 || $e->errorInfo[0] == 23000) {
				$this->debugging->start("queryInsert", "Insert would create duplicate row, skipping.", 4);
			} else if ($e->errorInfo[1] == 1406 || $e->errorInfo[0] == 22001) {
				$this->debugging->start("queryInsert", "Too large to fit column length.", 4);
			} else {
				$this->debugging->start("queryInsert", $e->getMessage(), 4);
				echo $this->c->error("\n" . $e->getMessage());
			}
			$this->debugging->start("queryInsert", $query, 6);
			return false;
		}
	}

	// Used for deleting, updating (and inserting without needing the last insert id).
	public function queryExec($query, $i = 1)
	{
		if ($query == '') {
			return false;
		}

		try {
			$run = self::$pdo->prepare($query);
			$run->execute();
			return $run;
		} catch (PDOException $e) {
			// Deadlock or lock wait timeout, try 10 times.
			while (($e->errorInfo[1] == 1213 || $e->errorInfo[0] == 40001 || $e->errorInfo[1] == 1205 || $e->getMessage() == 'SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction') && $i <= 10) {
				$message = "\nA Deadlock or lock wait timeout has occurred, sleeping.\n";
				$this->debugging->start("queryExec", $message, 4);
				echo $this->c->error($message);
				$this->consoletools->showsleep($i * $i);
				$this->queryInsert($query, $i++);
			}
			if ($e->errorInfo[1] == 1213 || $e->errorInfo[0] == 40001 || $e->errorInfo[1] == 1205) {
				$this->debugging->start("queryExec", "Deadlock or lock wait timeout.", 4);
			} else if ($e->errorInfo[1] == 1062 || $e->errorInfo[0] == 23000) {
				$this->debugging->start("queryExec", "Update would create duplicate row, skipping.", 4);
			} else if ($e->errorInfo[1] == 1406 || $e->errorInfo[0] == 22001) {
				$this->debugging->start("queryExec", "Too large to fit column length.", 4);
			} else {
				$this->debugging->start("queryExec", $e->getMessage(), 4);
				echo $this->c->error("\n" . $e->getMessage());
			}
			$this->debugging->start("queryExec", $query, 6);
			return false;
		}
	}

	// Direct query. Return the affected row count. http://www.php.net/manual/en/pdo.exec.php
	public function Exec($query)
	{
		if ($query == '') {
			return false;
		}

		try {
			return self::$pdo->exec($query);
		} catch (PDOException $e) {
			echo $this->c->error("\n" . $e->getMessage());
			$this->debugging->start("Exec", $e->getMessage(), 4);
			$this->debugging->start("Exec", $query, 6);
			return false;
		}
	}

	// Return an array of rows, an empty array if no results.
	// Optional: Pass true to cache the result with memcache.
	/**
	 * Returns an array of result (empty array if no results or an error occurs)
	 *
	 * @param type $query	 SQL to execute.
	 * @param type $memcache Indicates if memcache should you be used if available.
	 * @return array	Array of results (possibly empty) on success, empty array on failure.
	 */
	public function query($query, $memcache = false)
	{
		if ($query == '') {
			return false;
		}

		if ($memcache === true && $this->memcached === true) {
			try {
				$memcached = new Mcached();
				if ($memcached !== false) {
					$crows = $memcached->get($query);
					if ($crows !== false) {
						return $crows;
					}
				}
			} catch (Exception $er) {
				echo $this->c->error("\n" . $er->getMessage());
				$this->debugging->start("query", $er->getMessage(), 4);
				$this->debugging->start("query", $query, 6);
			}
		}

		$result = $this->queryArray($query);

		if ($memcache === true && $this->memcached === true) {
			$memcached->add($query, $result);
		}

		return ($result === false) ? array() : $result;
	}

	/**
	 * Main method for creating results as an array.
	 *
	 * @param string $query		SQL to execute.
	 * @return array|boolean	Array of results on success or false on failure.
	 */
	public function queryArray($query)
	{
		if ($query == '') {
			return false;
		}

		$result = $this->queryDirect($query);
		if ($result === false) {
			return false;
		}
		$rows = array();
		foreach ($result as $row) {
			$rows[] = $row;
		}

		return (!isset($rows)) ? false : $rows;
	}

	// Query without returning an empty array like our function query(). http://php.net/manual/en/pdo.query.php
	public function queryDirect($query)
	{
		if ($query == '') {
			return false;
		}

		try {
			$result = self::$pdo->query($query);
		} catch (PDOException $e) {
			//echo $query."\n";
			$error = "\nqueryDirect: " . $e->getMessage() . "\n";
			echo $this->c->error($error);
			$this->debugging->start("queryDirect", $error, 4);
			$this->debugging->start("queryDirect", $query, 6);
			$result = false;
		}
		return $result;
	}

	// Returns the first row of the query.
	public function queryOneRow($query)
	{
		$rows = $this->query($query);

		if (!$rows || count($rows) == 0) {
			return false;
		}

		return is_array($rows) ? $rows[0] : $rows;
	}

	/**
	 * Returns results as an array but without an empty array like our query() function.
	 *
	 * @param string $query		The query to execute.
	 * @return array|boolean	Array of results on success, false otherwise.
	 */
	public function queryAssoc($query)
	{
		if ($query == '') {
			return false;
		}
		$mode = self::$pdo->getAttribute(PDO::ATTR_DEFAULT_FETCH_MODE);
		if ($mode != PDO::FETCH_ASSOC) {
			self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		}

		$result = $this->queryArray($query);

		if ($mode != PDO::FETCH_ASSOC) {
			self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		}
		return $result;
	}

	// Optimises/repairs tables on mysql. Vacuum/analyze on postgresql.
	public function optimise($admin = false, $type = '')
	{
		$tablecnt = 0;
		if ($this->dbsystem == 'mysql') {
			if ($type === 'true' || $type === 'full' || $type === 'analyze') {
				$alltables = $this->query('SHOW TABLE STATUS');
			} else {
				$alltables = $this->query('SHOW TABLE STATUS WHERE Data_free / Data_length > 0.005');
			}
			$tablecnt = count($alltables);
			if ($type === 'all' || $type === 'full') {
				$tbls = '';
				foreach ($alltables as $table) {
					$tbls .= $table['name'] . ', ';
				}
				$tbls = rtrim(trim($tbls),',');
				if ($admin === false) {
					$message = 'Optimizing tables: ' . $tbls;
					echo $this->c->primary($message);
					$this->debugging->start("optimise", $message, 5);
				}
				$this->queryExec("OPTIMIZE LOCAL TABLE ${tbls}");
			} else {
				foreach ($alltables as $table) {
					if ($type === 'analyze') {
						if ($admin === false) {
							$message = 'Analyzing table: ' . $table['name'];
							echo $this->c->primary($message);
							$this->debugging->start("optimise", $message, 5);
						}
						$this->queryExec('ANALYZE LOCAL TABLE `' . $table['name'] . '`');
					} else {
						if ($admin === false) {
							$message = 'Optimizing table: ' . $table['name'];
							echo $this->c->primary($message);
							$this->debugging->start("optimise", $message, 5);
						}
						if (strtolower($table['engine']) == 'myisam') {
							$this->queryExec('REPAIR TABLE `' . $table['name'] . '`');
						}
						$this->queryExec('OPTIMIZE LOCAL TABLE `' . $table['name'] . '`');
					}
				}
			}
			if ($type !== 'analyze') {
				$this->queryExec('FLUSH TABLES');
			}
		} else if ($this->dbsystem == 'pgsql') {
			$alltables = $this->query("SELECT table_name as name FROM information_schema.tables WHERE table_schema = 'public'");
			$tablecnt = count($alltables);
			foreach ($alltables as $table) {
				if ($admin === false) {
					$message = 'Vacuuming table: ' . $table['name'] . ".\n";
					echo $message;
					$this->debugging->start("optimise", $message, 5);
				}
				$this->query('VACUUM (ANALYZE) ' . $table['name']);
			}
		}
		return $tablecnt;
	}

	// Check if the tables exists for the groupid, make new tables and set status to 1 in groups table for the id.
	public function newtables($grpid)
	{
		$s = new Sites();
		$site = $s->get();
		$DoPartRepair = ($site->partrepair == '0') ? false : true;

		if (!is_null($grpid) && is_numeric($grpid)) {
			$binaries = $parts = $collections = $partrepair = false;
			if ($this->dbsystem == 'pgsql') {
				$like = ' (LIKE collections INCLUDING ALL)';
			} else {
				$like = ' LIKE collections';
			}
			try {
				self::$pdo->query('SELECT * FROM ' . $grpid . '_collections LIMIT 1');
				$old_tables = true;
			} catch (PDOException $e) {
				$old_tables = false;
			}

			if ($old_tables === true) {
				$sql = 'SHOW TABLE STATUS';
				$tables = self::$pdo->query($sql);
				if (count($tables) > 0) {
					foreach ($tables as $row) {
						$tbl = $row['name'];
						$tblnew = '';
						if (strpos($tbl, '_collections') !== false) {
							$tblnew = 'collections_' . str_replace('_collections', '', $tbl);
						} else if (strpos($tbl, '_binaries') !== false) {
							$tblnew = 'binaries_' . str_replace('_binaries', '', $tbl);
						} else if (strpos($tbl, '_parts') !== false) {
							$tblnew = 'parts_' . str_replace('_parts', '', $tbl);
						} else if (strpos($tbl, '_partrepair') !== false) {
							$tblnew = 'partrepair_' . str_replace('_partrepair', '', $tbl);
						}
						if ($tblnew != '') {
							try {
								self::$pdo->query('ALTER TABLE ' . $tbl . ' RENAME TO ' . $tblnew);
							} catch (PDOException $e) {
								// table already exists
							}
						}
					}
				}
			}

			try {
				self::$pdo->query('SELECT * FROM collections_' . $grpid . ' LIMIT 1');
				$collections = true;
			} catch (PDOException $e) {
				try {
					if ($this->queryExec('CREATE TABLE collections_' . $grpid . $like) !== false) {
						$collections = true;
						$this->newtables($grpid);
					}
				} catch (PDOException $e) {
					return false;
				}
			}

			if ($collections === true) {
				if ($this->dbsystem == 'pgsql') {
					$like = ' (LIKE binaries INCLUDING ALL)';
				} else {
					$like = ' LIKE binaries';
				}
				try {
					self::$pdo->query('SELECT * FROM binaries_' . $grpid . ' LIMIT 1');
					$binaries = true;
				} catch (PDOException $e) {
					if ($this->queryExec('CREATE TABLE binaries_' . $grpid . $like) !== false) {
						$binaries = true;
						$this->newtables($grpid);
					}
				}
			}

			if ($binaries === true) {
				if ($this->dbsystem == 'pgsql') {
					$like = ' (LIKE parts INCLUDING ALL)';
				} else {
					$like = ' LIKE parts';
				}
				try {
					self::$pdo->query('SELECT * FROM parts_' . $grpid . ' LIMIT 1');
					$parts = true;
				} catch (PDOException $e) {
					if ($this->queryExec('CREATE TABLE parts_' . $grpid . $like) !== false) {
						$parts = true;
						$this->newtables($grpid);
					}
				}
			}

			if ($DoPartRepair === true && $parts === true) {
				if ($this->dbsystem == 'pgsql') {
					$like = ' (LIKE partrepair INCLUDING ALL)';
				} else {
					$like = ' LIKE partrepair';
				}
				try {
					DB::$pdo->query('SELECT * FROM partrepair_' . $grpid . ' LIMIT 1');
					$partrepair = true;
				} catch (PDOException $e) {
					if ($this->queryExec('CREATE TABLE partrepair_' . $grpid . $like) !== false) {
						$partrepair = true;
						$this->newtables($grpid);
					}
				}
			} else {
				$partrepair = true;
			}

			if ($parts === true && $binaries === true && $collections === true && $partrepair === true) {
				return true;
			} else {
				return false;
			}
		}
	}

	// Turns off autocommit until commit() is ran. http://www.php.net/manual/en/pdo.begintransaction.php
	public function beginTransaction()
	{
		return self::$pdo->beginTransaction();
	}

	// Commits a transaction. http://www.php.net/manual/en/pdo.commit.php
	public function Commit()
	{
		return self::$pdo->commit();
	}

	// Rollback transcations. http://www.php.net/manual/en/pdo.rollback.php
	public function Rollback()
	{
		return self::$pdo->rollBack();
	}

	public function from_unixtime($utime, $escape = true)
	{
		if ($escape === true) {
			if ($this->dbsystem == 'mysql') {
				return 'FROM_UNIXTIME(' . $utime . ')';
			} else if ($this->dbsystem == 'pgsql') {
				return 'TO_TIMESTAMP(' . $utime . ')::TIMESTAMP';
			}
		} else {
			return date('Y-m-d h:i:s', $utime);
		}
	}

	// Date to unix time.
	// (substitute for mysql's UNIX_TIMESTAMP() function)
	public function unix_timestamp($date)
	{
		return strtotime($date);
	}

	// Return uuid v4 string. http://www.php.net/manual/en/function.uniqid.php#94959
	// (substitute for mysql's UUID() function)
	public function uuid()
	{
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
	}

	/**
	 * Checks whether the connection to the server is working. Optionally start
	 * a new connection.
	 * NOTE: Restart does not happen if PDO is not using exceptions (PHP's
	 * default configuration). In this case check the return value === false.
	 *
	 * @param boolean $restart Whether an attempt should be made to reinitialise the Db object on failure.
	 * @return boolean
	 */
	public function ping($restart = false)
	{
		try {
			return (bool) self::$pdo->query('SELECT 1+1');
		} catch (PDOException $e) {
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
	 * @param string	$query		SQL query to run, with optional place holders.
	 * @param array		$options	Driver options.
	 * @return Pobject|false PDOstatement on success false on failure.
	 * @link http://www.php.net/pdo.prepare.php
	 */
	public function Prepare($query, $options = array())
	{
		try {
			$PDOstatement = self::$pdo->prepare($query, $options);
		} catch (PDOException $e) {
			$this->debugging->start("Prepare", $e->getMessage(), 5);
			echo $this->c->error("\n" . $e->getMessage());
			$PDOstatement = false;
		}
		return $PDOstatement;
	}

	// Retrieve db attributes http://us3.php.net/manual/en/pdo.getattribute.php
	public function getAttribute($attribute)
	{
		if ($attribute != '') {
			try {
				$result = self::$pdo->getAttribute($attribute);
			} catch (PDOException $e) {
				$this->debugging->start("getAttribute", $e->getMessage(), 5);
				echo $this->c->error("\n" . $e->getMessage());
				$result = false;
			}
			return $result;
		}
	}
}

// Class for caching queries into RAM using memcache.
class Mcached
{
	// Make a connection to memcached server.
	public function Mcached()
	{
		$this->c = new ColorCLI();
		if (extension_loaded('memcache')) {
			$this->m = new Memcache();
			if ($this->m->connect(MEMCACHE_HOST, MEMCACHE_PORT) == false) {
				throw new Exception($this->c->error("\nUnable to connect to the memcached server."));
			}
		} else {
			throw new Exception($this->c->error("nExtension 'memcache' not loaded."));
		}

		$this->expiry = MEMCACHE_EXPIRY;

		$this->compression = MEMCACHE_COMPRESSED;
		if (defined('MEMCACHE_COMPRESSION')) {
			if (MEMCACHE_COMPRESSION === false) {
				$this->compression = false;
			}
		}
	}

	// Return a SHA1 hash of the query, used for the key.
	public function key($query)
	{
		return sha1($query);
	}

	// Return some stats on the server.
	public function Server_Stats()
	{
		return $this->m->getExtendedStats();
	}

	// Flush all the data on the server.
	public function Flush()
	{
		return $this->m->flush();
	}

	// Add a query to memcached server.
	public function add($query, $result)
	{
		return $this->m->add($this->key($query), $result, $this->compression, $this->expiry);
	}

	// Delete a query on the memcached server.
	public function delete($query)
	{
		return $this->m->delete($this->key($query));
	}

	// Retrieve a query from the memcached server. Stores the query if not found.
	public function get($query)
	{
		return $this->m->get($this->key($query));
	}
}
