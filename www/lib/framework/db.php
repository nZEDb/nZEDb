<?php

/*
* Class for handling connection to MySQL and PostgreSQL database using PDO.
* Exceptions are caught and displayed to the user.
*/

class DB
{
	private static $initialized = false;
	private static $pdo = null;

	// Start a connection to the DB.
	public function DB()
	{
		if (defined('DB_SYSTEM') && strlen(DB_SYSTEM) > 0)
			$this->dbsystem = strtolower(DB_SYSTEM);
		else
			exit("ERROR: config.php is missing the DB_SYSTEM setting. Add the following in that file:\n define('DB_SYSTEM', 'mysql');\n");
		if (DB::$initialized === false)
		{
			$pdos = $this->dbsystem.':host='.DB_HOST.';dbname='.DB_NAME;
			if (defined('DB_PORT'))
				$pdos .= ';port='.DB_PORT;

			if ($this->dbsystem == 'mysql')
				$pdos .= ';charset=utf8';

			try {
				if ($this->dbsystem == 'mysql')
					$options = array( PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 180, PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8');
				else
					$options = array( PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 180);

				DB::$pdo = new PDO($pdos, DB_USER, DB_PASSWORD, $options);
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
			return 'NULL';
		
		return DB::$pdo->quote($str);
	}

	// For inserting a row. Returns last insert ID. queryExec is better if you do not need the id.
	public function queryInsert($query)
	{
		if ($query == '')
			return false;

		try
		{
			if ($this->dbsystem() == 'mysql')
			{
				$ins = DB::$pdo->prepare($query);
				$ins->execute();
				return DB::$pdo->lastInsertId();
			}
			else
			{
				$p = DB::$pdo->prepare($query.' RETURNING id');
				$p->execute();
				$r = $p->fetch(PDO::FETCH_ASSOC);
				return $r['id'];
			}
		} catch (PDOException $e) {
			// Deadlock or lock wait timeout, try 10 times.
			$i = 1;
			while (($e->errorInfo[1] == 1213 || $e->errorInfo[0] == 40001 || $e->errorInfo[0] == 1205) && $i <= 10)
			{
				sleep($i * $i);
				$ins = DB::$pdo->prepare($query);
				$ins->execute();
				return DB::$pdo->lastInsertId();
				$i++;
			}
			printf($e);
			if ($e->errorInfo[1]==1062 || $e->errorInfo[1]==23000)
				return $e;
				//echo "\nError: Insert would create duplicate row, skipping\n";
			return false;
		}
	}

	// Used for deleting, updating (and inserting without needing the last insert id).
	public function queryExec($query)
	{
		if ($query == '')
			return false;

		try {
			$run = DB::$pdo->prepare($query);
			$run->execute();
			return $run;
		} catch (PDOException $e) {
			// Deadlock or lock wait timeout, try 10 times.
			$i = 1;
			while (($e->errorInfo[1] == 1213 || $e->errorInfo[0] == 40001 || $e->errorInfo[0] == 1205) && $i <= 10)
			{
				sleep($i * $i);
				$run = DB::$pdo->prepare($query);
				$run->execute();
				return $run;
				$i++;
			}
			printf($e);
			//if ($e->errorInfo[1]==1062 || $e->errorInfo[1]==23000)
				//echo "\nError: Update would create duplicate row, skipping\n";
			return false;
		}
	}

	// Direct query. Return the affected row count. http://www.php.net/manual/en/pdo.exec.php
	public function Exec($query)
	{
		if ($query == '')
			return false;

		try {
			return DB::$pdo->exec($query);
		} catch (PDOException $e) {
			printf($e);
			return false;
		}
	}


	// Return an array of rows, an empty array if no results.
	// Optional: Pass true to cache the result with memcache.
	public function query($query, $memcache=false)
	{
		if ($query == '')
			return false;

		if ($memcache === true && $this->memcached === true)
		{
			try {
				$memcached = new Mcached();
				if ($memcached !== false)
				{
					$crows = $memcached->get($query);
					if ($crows !== false)
						return $crows;
				}
			} catch (Exception $er) {
					printf ($er);
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

		if ($memcache === true && $this->memcached === true)
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

	// Query without returning an empty array like our function query(). http://php.net/manual/en/pdo.query.php
	public function queryDirect($query)
	{
		if ($query == '')
			return false;

		try {
			$result = DB::$pdo->query($query);
		} catch (PDOException $e) {
			printf($e);
			$result = false;
		}
		return $result;
	}

	// Optimises/repairs tables on mysql. Vacuum/analyze on postgresql.
	public function optimise($admin=false)
	{
		$tablecnt = 0;
		if ($this->dbsystem == 'mysql')
		{
			$alltables = $this->query('SHOW table status WHERE Data_free > 0');
			$tablecnt = count($alltables);
			foreach ($alltables as $table)
			{
				if ($admin === false)
					echo 'Optimizing table: '.$table['name'].".\n";
				if (strtolower($table['engine']) == 'myisam')
					$this->queryDirect('REPAIR TABLE `'.$table['name'].'`');
				$this->queryDirect('OPTIMIZE TABLE `'.$table['name'].'`');
			}
			$this->queryDirect('FLUSH TABLES');
		}
		else if ($this->dbsystem == 'pgsql')
		{
			$alltables = $this->query("SELECT table_name as name FROM information_schema.tables WHERE table_schema = 'public'");
			$tablecnt = count($alltables);
			foreach ($alltables as $table)
			{
				if ($admin === false)
					echo 'Vacuuming table: '.$table['name'].".\n";
				$this->query('VACUUM (ANALYZE) '.$table['name']);
			}
		}
		return $tablecnt;
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

	public function from_unixtime($utime, $escape=true)
	{
		if ($escape === true)
		{
			if ($this->dbsystem == 'mysql')
				return 'FROM_UNIXTIME('.$utime.')';
			else if ($this->dbsystem == 'pgsql')
				return 'TO_TIMESTAMP('.$utime.')::TIMESTAMP';
		}
		else
			return date('Y-m-d h:i:s', $utime);
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
	public function Mcached()
	{
		if (extension_loaded('memcache'))
		{
			$this->m = new Memcache();
			if ($this->m->connect(MEMCACHE_HOST, MEMCACHE_PORT) == false)
				throw new Exception('Unable to connect to the memcached server.');
		}
		else
			throw new Exception('Extension "memcache" not loaded.');

		$this->expiry = MEMCACHE_EXPIRY;

		$this->compression = MEMCACHE_COMPRESSED;
		if (defined('MEMCACHE_COMPRESSION'))
		{
			if (MEMCACHE_COMPRESSION === false)
				$this->compression = false;
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
