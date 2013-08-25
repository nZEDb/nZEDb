<?php

/* Testing PDO, which will allow us to use other databases than Mysql */

class DB
{
/* Need to see if we can replace this with relnamestatus since they seem to do the same thing */
	// the element relstatus of table releases is used to hold the status of the release
	// The variable is a bitwise AND of status
	// List of processed constants - used in releases table. Constants need to be powers of 2: 1, 2, 4, 8, 16 etc...
	const NFO_PROCESSED_NAMEFIXER     = 1;  // We have processed the release against its .nfo file in the namefixer
	const PREDB_PROCESSED_NAMEFIXER   = 2;  // We have processed the release against a predb name

	private static $initialized = false;
	private static $pdo = null;

	function DB()
	{
		if (defined("DB_SYSTEM") && strlen(DB_SYSTEM) > 0)
			$this->dbsystem = strtolower(DB_SYSTEM);
		else
		{
			printf("ERROR: config.php is missing DB_SYSTEM\n");
			exit();
		}
		if (DB::$initialized === false)
		{
			$charset = '';
			if ($this->dbsystem == 'mysql')
				$charset = ';charset=utf8';
			if (defined("DB_PORT"))
				$pdos = $this->dbsystem.':host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.$charset;
			else
				$pdos = $this->dbsystem.':host='.DB_HOST.';dbname='.DB_NAME.$charset;

			// Initialize DB connection.
			try
			{
				DB::$pdo = new PDO($pdos, DB_USER, DB_PASSWORD);
				DB::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				if ($this->dbsystem == "pgsql")
					DB::$pdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
			}
			catch (PDOException $e)
			{
				printf("Connection failed: (".$e->getMessage().")");
				exit();
			}

			DB::$initialized = true;
		}
		$this->memcached = false;
		if (defined("MEMCACHE_ENABLED"))
			$this->memcached = MEMCACHE_ENABLED;
	}


	public function dbSystem()
	{
		return $this->dbsystem;
	}

/* Tested. Works the same. ** */
	// Returns a string, escaped with single quotes.
	public function escapeString($str)
	{
		if (is_null($str))
			return "NULL";
		else 
			return DB::$pdo->quote($str);
	}

/* Tested. Works the same. ** */
	// For inserting a row.
	public function queryInsert($query, $returnlastid=true)
	{
		if ($query=="")
			return false;

		return DB::$pdo->exec($query);
	}

/* Tested. In PDO you must use exec to delete, it returns the affected row count. ** */
	// For deleting rows.
	public function queryDelete($query)
	{
		if ($query == "")
			return false;

		return DB::$pdo->exec($query);
	}

/* Tested. In PDO you must use exec or prepared statement to update. */
	// For updating rows.
	public function queryUpdate($query)
	{
		if ($query == "")
			return false;

		return DB::$pdo->exec($query);
	}

/* Tested. Return 2 keys; numeric value and name value, vs just name on mysqli, there is no free_result on pdo, not sure if that will impact anything */
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

		$result = DB::$pdo->query($query);
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

/* Tested. Works the same. */
	// Returns the first row of the query.
	public function queryOneRow($query)
	{
		$rows = $this->query($query);

		if (!$rows)
			return false;

		return ($rows) ? $rows[0] : $rows;
	}

/* Tested. Works the same. Untested in anything other than mysql. */
	// Optimises/repairs tables.
	public function optimise()
	{
		$alltables = $this->query("show table status where Data_free > 0");
		$tablecnt = count($alltables);

		foreach ($alltables as $tablename)
		{
			$ret[] = $tablename['Name'];
			echo "Optimizing table: ".$tablename['Name'].".\n";
			if (strtolower($tablename['Engine']) == "myisam")
				$this->queryDirect("REPAIR TABLE `".$tablename['Name']."`");
			$this->queryDirect("OPTIMIZE TABLE `".$tablename['Name']."`");
		}
		$this->queryDirect("FLUSH TABLES");
		return $tablecnt;
	}

	public function queryDirect($query)
	{
		return ($query=="") ? false : DB::$pdo->query($query);
	}

/* Tested ,works. */ 
	public function Prepare($query)
	{
		return DB::$pdo->prepare($query);
	}

/* Untested ;Anything using this might need to be modified.
 * mysqli error returns a string, while this is an array, so we convert it to string
 * Retrieves only errors on the database handle : http://www.php.net/manual/en/pdo.errorinfo.php*/
	public function Error()
	{
		$e = DB::$pdo->errorInfo();
		return "SQL Error: ".$e[0]." ".$e[2];
	}

/* Untested ;Could cause issues with myisam, see : http://php.net/manual/en/pdo.transactions.php
 * If so we might have to put an option to turn on / off transactions */
	public function beginTransaction()
	{
		return DB::$pdo->beginTransaction();
	}

/* Untested */
	public function Commit()
	{
		return DB::$pdo->commit();
	}

/* Untested */
	public function Rollback()
	{
		return DB::$pdo->rollBack();
	}

	// Convert unixtime to sql compatible timestamp : 1969-12-31 07:00:00, also escapes it, pass false as 2nd arg to not escape.
	// (substitute for mysql from_unixtime function).
	public function from_unixtime($utime, $escape=true)
	{
		return ($escape) ? $this->escapeString(date('Y-m-d h:i:s', $utime)) : date('Y-m-d h:i:s', $utime);
	}

	// Convert unixtime to a date, no time then back to unix time.
	// (substitute for mysql's DATE() function).
	public function unixtime_date($utime)
	{
		return strtotime(date('Y-m-d', $utime));
	}

/*No replacements in PDO. Used in tmux monitor.php, possible solution here? http://terenceyim.wordpress.com/2009/01/09/adding-ping-function-to-pdo/
	// Checks whether the connection to the server is working. Optionally kills connection.
	public function ping($kill=false)
	{
		if (DB::$mysqli->ping() === false)
		{
			printf ("Error: %s\n", DB::$mysqli->error());
			DB::$mysqli->close();
			return false;
		}
		if ($kill === true)
			$this->kill();
		return true;
	}

	//This function is used to ask the server to kill a MySQL thread specified by the processid parameter. This value must be retrieved by calling the mysqli_thread_id() function. 
	public function kill()
	{
		DB::$mysqli->kill(DB::$mysqli->thread_id);
		DB::$mysqli->close();
	}
*/


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
