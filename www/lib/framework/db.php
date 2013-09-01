<?php

/* Class for handling connection to SQL database, querying etc using PDO.
 * Exceptions are caught and displayed to the user. */

class DB
{
	private static $initialized = false;
	private static $pdo = null;

	// Start a connection to the DB.
	function DB()
	{
		if (defined("DB_SYSTEM") && strlen(DB_SYSTEM) > 0)
			$this->dbsystem = strtolower(DB_SYSTEM);
		else
			exit("ERROR: config.php is missing the DB_SYSTEM setting. Add the following in that file:\n define('DB_SYSTEM', 'mysql');\n");
		if (DB::$initialized === false)
		{
			if (defined("DB_PORT"))
				$pdos = $this->dbsystem.':host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME;
			else
				$pdos = $this->dbsystem.':host='.DB_HOST.';dbname='.DB_NAME;

			if ($this->dbsystem == 'mysql')
				$pdos .= ';charset=utf8';

			try {
				DB::$pdo = new PDO($pdos, DB_USER, DB_PASSWORD);
				DB::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				// For backwards compatibility, no need for a patch.
				DB::$pdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
			} catch (PDOException $e) {
				exit("Connection to the SQL server failed, error follows: (".$e->getMessage().")");
			}

			DB::$initialized = true;
		}
		$this->memcached = false;
		if (defined("MEMCACHE_ENABLED"))
			$this->memcached = MEMCACHE_ENABLED;
	}

	// Return string; mysql or pgsql.
	public function dbSystem()
	{
		return $this->dbsystem;
	}

	// Returns a string, escaped with single quotes, false on failure. http://www.php.net/manual/en/pdo.quote.php
	public function escapeString($str)
	{
		if (is_null($str))
			return "NULL";

		return DB::$pdo->quote($str);
	}

	// For inserting a row. Returns last insert ID.
	public function queryInsert($query)
	{
		if ($query=="")
			return false;

		try
		{
			if ($this->dbsystem() == "mysql")
			{
				$ins = DB::$pdo->prepare($query);
				$ins->execute();
				return DB::$pdo->lastInsertId();
			}
			else
			{
				$p = DB::$pdo->prepare($query." RETURNING id");
				$p->execute();
				$r = $p->fetch(PDO::FETCH_ASSOC);
				return $r['id'];
			}
		} catch (PDOException $e) {
			//deadlock, try 10 times
			$i = 1;
			while ( $e->errorInfo[1]==1213 || $e->errorInfo[0]==40001 || $i <= 5)
			{
				sleep($i);
				try {
					$ins = DB::$pdo->prepare($query);
					$ins->execute();
					return DB::$pdo->lastInsertId();
				} catch (PDOException $e) {
					//return false;
				}
				$i++;
			}
			printf($e);
			return false;
		}
	}

	// Used for deleting, updating (and inserting without needing the last insert id). Return the affected row count. http://www.php.net/manual/en/pdo.exec.php
	public function queryExec($query)
	{
		if ($query == "")
			return false;

		try {
			$run = DB::$pdo->prepare($query);
			$run->execute();
			return $run;
		} catch (PDOException $e) {
			//deadlock, try 10 times
			$i = 1;
			while ( $e->errorInfo[1]==1213 || $e->errorInfo[0]==40001 || $i <= 5)
			{
				sleep($i);
				try {
					$run = DB::$pdo->prepare($query);
					$run->execute();
					return $run;
				} catch (PDOException $e) {
					//printf($e);
				}
				$i++;
			}
			printf($e);
			return false;
		}
	}

	// Return an array of rows, an empty array if no results.
	// Optional: Pass true to cache the result with memcache.
	public function query($query, $memcache=false)
	{
		if ($query == "")
			return false;

		if ($this->memcached === true && $memcache === true)
		{
			$memcached = new Mcached();
			if ($memcached !== false)
			{
				$crows = $memcached->get($query);
				if ($crows !== false)
					return $crows;
			}
		}

		try {
			$result = DB::$pdo->query($query);
		} catch (PDOException $e) {
			printf($e);
			$result = false;
		}

		if ($result === false)
			return array();

		$rows = array();
		foreach ($result as $row)
		{
			$rows[] = $row;
		}

		if ($this->memcached === true && $memcache === true)
			$memcached->add($query, $rows);

		return $rows;
	}

	// Returns the first row of the query.
	public function queryOneRow($query)
	{
		$rows = $this->query($query);

		if (!$rows || count($rows) == 0)
			return false;

		return ($rows) ? $rows[0] : $rows;
	}

	// Optimises/repairs tables on mysql. Vacuum/analyze on postgresql.
	public function optimise($admin=false)
	{
		$tablecnt = 0;
		if ($this->dbsystem == "mysql")
		{
			$alltables = $this->query("SHOW table status WHERE Data_free > 0");
			$tablecnt = count($alltables);
			foreach ($alltables as $table)
			{
				if ($admin === false)
					echo "Optimizing table: ".$table['name'].".\n";
				if (strtolower($table['engine']) == "myisam")
					$this->queryDirect("REPAIR TABLE `".$table['name']."`");
				$this->queryDirect("OPTIMIZE TABLE `".$table['name']."`");
			}
			$this->queryDirect("FLUSH TABLES");
		}
		else if ($this->dbsystem == "pgsql")
		{
			$alltables = $this->query("SELECT table_name as name FROM information_schema.tables WHERE table_schema = 'public'");
			$tablecnt = count($alltables);
			foreach ($alltables as $table)
			{
				if ($admin === false)
					echo "Vacuuming table: ".$table['name'].".\n";
				$this->query("VACUUM (ANALYZE) ".$table['name']);
			}
		}
		return $tablecnt;
	}

	// Query without returning an empty array like our function query(). http://php.net/manual/en/pdo.query.php
	public function queryDirect($query)
	{
		if ($query == "")
			return false;

		try {
			$result = DB::$pdo->query($query);
		} catch (PDOException $e) {
			printf($e);
			$result = false;
		}
		return $result;
	}

	// Prepares a statement, to run use exexute(). http://www.php.net/manual/en/pdo.prepare.php
	public function Prepare($query)
	{
		try {
			$stat = DB::$pdo->prepare($query);
		} catch (PDOException $e) {
			printf($e);
			$stat = false;
		}
		return $stat;
	}

	// Turns off autocommit until commit() is ran. http://www.php.net/manual/en/pdo.begintransaction.php
	public function beginTransaction()
	{
		return DB::$pdo->beginTransaction();
	}

	// Commits a transaction. http://www.php.net/manual/en/pdo.commit.php
	public function Commit()
	{
		return DB::$pdo->commit();
	}

	// Rollback transcations. http://www.php.net/manual/en/pdo.rollback.php
	public function Rollback()
	{
		return DB::$pdo->rollBack();
	}

	// Convert unixtime to sql compatible timestamp : 1969-12-31 07:00:00, also escapes it, pass false as 2nd arg to not escape.
	// (substitute for mysql FROM_UNIXTIME function)
	public function from_unixtime($utime, $escape=true)
	{
		return ($escape) ? $this->escapeString(date('Y-m-d h:i:s', $utime)) : date('Y-m-d h:i:s', $utime);
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

	// Checks whether the connection to the server is working. Optionally start a new connection.
	public function ping($restart = false)
	{
		try {
			return (bool) DB::$pdo->query('SELECT 1+1');
		} catch (PDOException $e) {
			if ($restart = true)
			{
				DB::$initialized = false;
				$this->DB();
			}
			return false;
		}
	}
}

// Class for caching queries into RAM using memcache.
class Mcached
{
	// Make a connection to memcached server.
	function Mcached()
	{
		if (!defined("MEMCACHE_HOST"))
			define('MEMCACHE_HOST', '127.0.0.1');
		if (!defined("MEMCACHE_PORT"))
			define('MEMCACHE_PORT', '11211');
		if (extension_loaded('memcache'))
		{
			$this->m = new Memcache();
			if ($this->m->connect(MEMCACHE_HOST, MEMCACHE_PORT) == false)
				return false;
		}
		else
			return false;

		// Amount of time for the query to expire from memcached server.
		$this->expiry = 900;
		if (defined("MEMCACHE_EXPIRY"))
			$this->expiry = MEMCACHE_EXPIRY;

		// Uses more CPU but less RAM.
		$this->compression = MEMCACHE_COMPRESSED;
		if (defined("MEMCACHE_COMPRESSION"))
			if (MEMCACHE_COMPRESSION === false)
				$this->compression = false;
	}

	// Return a SHA1 hash of the query, used for the key.
	function key($query)
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
