<?php

/* Testing PDO, which will allow us to use other databases than Mysql */

class DB1
{
/* Need to see if we can replace this with relnamestatus since they seem to do the same thing */
	// the element relstatus of table releases is used to hold the status of the release
	// The variable is a bitwise AND of status
	// List of processed constants - used in releases table. Constants need to be powers of 2: 1, 2, 4, 8, 16 etc...
	const NFO_PROCESSED_NAMEFIXER     = 1;  // We have processed the release against its .nfo file in the namefixer
	const PREDB_PROCESSED_NAMEFIXER   = 2;  // We have processed the release against a predb name

	private static $initialized = false;
	private static $pdo = null;

	function DB1()
	{
		// Type can be added later on in config.php
		$this->dbtype = 'mysql';
		// Not sure if pdo is case sensitive, just in case.
		$this->dbtype = strtolower($this->dbtype);
		if (DB1::$initialized === false)
		{
			$charset = '';
			if ($this->dbtype == 'mysql')
				$charset = ';charset=utf8';
			if (defined("DB_PORT"))
				$pdos = $this->dbtype.':host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.$charset;
			else
				$pdos = $this->dbtype.':host='.DB_HOST.';dbname='.DB_NAME.$charset;

			// Initialize DB connection.
			try
			{
				DB1::$pdo = new PDO($pdos, DB_USER, DB_PASSWORD);
				DB1::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
			catch (PDOException $e)
			{
				printf("Connection failed: (".$e->getMessage().")");
				exit();
			}

			DB1::$initialized = true;
		}
		$this->memcached = false;
		if (defined("MEMCACHE_ENABLED"))
			$this->memcached = MEMCACHE_ENABLED;
	}



/*No replacements in PDO, but not used anywhere.
	// Returns the MYSQL version.
	public function mysqlversion()
	{
		return substr(DB1::$mysqli->client_info, 0, 3);
	}
*/



/*No replacements in PDO. Used in tmux monitor.php, possible solution here? http://terenceyim.wordpress.com/2009/01/09/adding-ping-function-to-pdo/
	// Checks whether the connection to the server is working. Optionally kills connection.
	public function ping($kill=false)
	{
		if (DB1::$mysqli->ping() === false)
		{
			printf ("Error: %s\n", DB1::$mysqli->error());
			DB1::$mysqli->close();
			return false;
		}
		if ($kill === true)
			$this->kill();
		return true;
	}

	//This function is used to ask the server to kill a MySQL thread specified by the processid parameter. This value must be retrieved by calling the mysqli_thread_id() function. 
	public function kill()
	{
		DB1::$mysqli->kill(DB1::$mysqli->thread_id);
		DB1::$mysqli->close();
	}
*/


/* Works the same. */
	public function escapeString($str)
	{
		if (is_null($str))
			return "NULL";
		else 
			return DB1::$pdo->quote($str);
	}


/* Not used in any scripts.
	public function makeLookupTable($rows, $keycol)
	{
		$arr = array();
		foreach($rows as $row)
			$arr[$row[$keycol]] = $row;
		return $arr;
	}
*/


/* Untested */
	public function queryInsert($query, $returnlastid=true)
	{
		if ($query=="")
			return false;

		$result = DB1::$pdo->query($query);
		return ($returnlastid) ? DB1::$pdo->lastInsertId : $result;
	}


/* Untested ;should work the same. */
	public function getInsertID()
	{
		return DB1::$pdo->lastInsertId;
	}


/* This works on a delete query, but not on a select,
 * Example 2 : http://php.net/manual/en/pdostatement.rowcount.php
 * It says to do a select count(*) for the same query, which is a waste.
 * Not sure about this, maybe count($result) if it is an array ?.
	public function getAffectedRows()
	{
		return DB::$mysqli->affected_rows;
	}*/


/* Works the same. */
	public function queryOneRow($query)
	{
		$rows = $this->query($query);

		if (!$rows)
			return false;

		return ($rows) ? $rows[0] : $rows;
	}

/* Return 2 keys; numeric value and name value, vs just name on mysqli, there is no free_result on pdo, not sure if that will impact anything */
	public function query($query, $memcache=false)
	{
		if ($query == "")
			return false;

		if ($this->memcached === true && $memcache === true)
		{
			$memcached = new Mcached1();
			if ($memcached !== false)
			{
				$crows = $memcached->get($query);
				if ($crows !== false)
					return $crows;
			}
		}

		$result = DB1::$pdo->query($query);
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





/* Need to change everything using queryDirect like this, or use query instead:

$db1 = new DB1();
$a = $db1->queryDirect("select * from predb limit 1");
foreach ($db1->fetchAssoc($a) as $b)
{
	 print_r($b);
}
*/
	public function queryDirect($query)
	{
		return ($query=="") ? false : DB1::$pdo->query($query);
	}

	public function fetchAssoc($result)
	{
		return (is_null($result) ? null : $result->fetchAll(PDO::FETCH_CLASS));
	}



/* Namefixer uses this, will have to look into changing namefixer to query or fetchassoc instead. */

	public function fetchArray($result)
	{
		return (is_null($result) ? null : $result->fetchAll(PDO::FETCH_CLASS));
	}


/* Seems to work fine. */
	public function optimise()
	{
		$alltables = $this->query("show table status where Data_free > 0");
		print_r($alltables);
		$tablecnt = sizeof($alltables);

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

/* Same problem as getAffectedRows
	public function getNumRows($result)
	{
		return (!isset($result->num_rows)) ? 0 : $result->num_rows;
	}*/


/* Untested */ 
	public function Prepare($query)
	{
		return DB1::$pdo->prepare($query);
	}

/* Untested ;Anything using this might need to be modified.
 * mysqli error returns a string, while this is an array, so we convert it to string
 * Retrieves only errors on the database handle : http://www.php.net/manual/en/pdo.errorinfo.php*/
	public function Error()
	{
		$e = DB1::$pdo->errorInfo();
		return "SQL Error: ".$e[0]." ".$e[2];
	}

/* Untested ;Could cause issues with myisam, see : http://php.net/manual/en/pdo.transactions.php
 * If so we might have to put an option to turn on / off transactions */
	public function setAutoCommit($enabled)
	{
		return DB1::$pdo->beginTransaction();
	}

/* Untested */
	public function Commit()
	{
		return DB1::$pdo->commit();
	}

/* Untested */
	public function Rollback()
	{
		return DB1::$pdo->rollBack();
	}
}

// Class for caching queries into RAM using memcache.
class Mcached1
{
	// Make a connection to memcached server.
	function Mcached1()
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
