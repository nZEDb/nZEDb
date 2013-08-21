<?php

class DB
{
	//
	// the element relstatus of table releases is used to hold the status of the release
	// The variable is a bitwise AND of status
	// List of processed constants - used in releases table. Constants need to be powers of 2: 1, 2, 4, 8, 16 etc...
	const NFO_PROCESSED_NAMEFIXER     = 1;  // We have processed the release against its .nfo file in the namefixer
	const PREDB_PROCESSED_NAMEFIXER   = 2;  // We have processed the release against a predb name

	private static $initialized = false;
	private static $mysqli = null;

	function DB()
	{
		if (DB::$initialized === false)
		{
			// initialize db connection
			if (defined("DB_PORT"))
				DB::$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
			else
				DB::$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

			if (DB::$mysqli->connect_errno) 
			{
				printf("Failed to connect to MySQL: (" . DB::$mysqli->connect_errno . ") " . DB::$mysqli->connect_error);
				exit();
			}

			if (!DB::$mysqli->set_charset('utf8'))
				printf(DB::$mysqli->error);
			else
				DB::$mysqli->character_set_name();

			DB::$initialized = true;
		}
		$this->memcached = false;
		if (defined("MEMCACHE_ENABLED"))
			$this->memcached = MEMCACHE_ENABLED;
	}

	// Returns the MYSQL version.
	public function mysqlversion()
	{
		return substr(DB::$mysqli->client_info, 0, 3);
	}

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

	public function escapeString($str)
	{
		if (is_null($str)){
			return "NULL";
		} else {
			return "'".DB::$mysqli->real_escape_string($str)."'";
		}
	}

	public function makeLookupTable($rows, $keycol)
	{
		$arr = array();
		foreach($rows as $row)
			$arr[$row[$keycol]] = $row;
		return $arr;
	}

	public function queryInsert($query, $returnlastid=true)
	{
		if ($query=="")
			return false;

		$result = DB::$mysqli->query($query);
		return ($returnlastid) ? DB::$mysqli->insert_id : $result;
	}

	public function getInsertID()
	{
		return DB::$mysqli->insert_id;
	}

	public function getAffectedRows()
	{
		return DB::$mysqli->affected_rows;
	}

	public function queryOneRow($query)
	{
		$rows = $this->query($query);

		if (!$rows)
			return false;

		return ($rows) ? $rows[0] : $rows;
	}

	public function query($query, $memcache=false)
	{
		if ($query=="")
			return false;

		if ($this->memcached === true && $memcache === true)
		{
			$memcached = new Memcached();
			if ($memcached !== false)
			{
				$crows = $memcached->get($query);
				if ($crows !== false)
					return $crows;
			}
		}

		$result = DB::$mysqli->query($query);

		if ($result === false || $result === true)
			return array();

		$rows = array();

		while ($row = $this->fetchAssoc($result))
			$rows[] = $row;

		$result->free_result();

		$error = $this->Error();
		if ($error != '')
			echo "MySql error: $error\n";

		if ($this->memcached === true && $memcache === true)
			$memcached->add($query, $rows);

		return $rows;
	}

	public function queryDirect($query)
	{
		return ($query=="") ? false : DB::$mysqli->query($query);
	}

	public function fetchAssoc($result)
	{
		return (is_null($result) ? null : $result->fetch_assoc());
	}

	public function fetchArray($result)
	{
		return (is_null($result) ? null : $result->fetch_array());
	}

	public function optimise()
	{
		$alltables = $this->query("show table status where Data_free > 0");
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

	public function getNumRows($result)
	{
		return (!isset($result->num_rows)) ? 0 : $result->num_rows;
	}

	public function Prepare($query)
	{
		return DB::$mysqli->prepare($query);
	}

	public function Error()
	{
		return DB::$mysqli->error;
	}

	public function setAutoCommit($enabled)
	{
		return DB::$mysqli->autocommit($enabled);
	}

	public function Commit()
	{
		return DB::$mysqli->commit();
	}

	public function Rollback()
	{
		return DB::$mysqli->rollback();
	}
}

// Class for caching queries into RAM using memcache.
class Memcached
{
	// Make a connection to memcached server.
	function Memcached()
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
		$this->compression = "MEMCACHE_COMPRESSED";
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
