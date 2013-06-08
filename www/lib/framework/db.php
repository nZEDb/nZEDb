<?php

class DB
{
	private static $initialized = false;
	private static $db = null;

	function DB()
	{
		if (DB::$initialized === false)
		{
			// initialize db connection
			if (defined("DB_PORT"))
			{
				DB::$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
			}
			else
			{
				DB::$db = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
			}

			if (DB::$db->connect_errno) {
				printf("Failed to connect to MySQL: (" . DB::$db->connect_errno . ") " . DB::$db->connect_error);
				exit();
			}

			DB::$db->set_charset('utf8');
			DB::$initialized = true;
		}
	}

	public function escapeString($str)
	{
		if (is_null($str))
		{
			return "NULL";
		} else {
		$str = preg_replace("/[\x01-\x20\x80-\xff]/", " ", $str);
		return "'".DB::$db->real_escape_string($str)."'";
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

		$result = DB::$db->query($query);
		return ($returnlastid) ? DB::$db->insert_id : $result;
	}

	public function getInsertID()
	{
		return DB::$db->insert_id;
	}

	public function getAffectedRows()
	{
		return DB::$db->affected_rows;
	}

	public function queryOneRow($query)
	{
		$rows = $this->query($query);

		if (!$rows)
			return false;

		return ($rows) ? $rows[0] : $rows;
	}

	public function query($query)
	{
		if ($query=="")
			return false;

		$result = DB::$db->query($query);

		if ($result === false || $result === true)
			return array();

		$rows = array();

		while ($row = $this->fetchAssoc($result))
			$rows[] = $row;

		$result->free_result();

		$error = $this->Error();
		if ($error != '')
			echo "MySql error: $error\n";

		return $rows;
	}

	public function queryDirect($query)
	{
		return ($query=="") ? false : DB::$db->query($query);
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
				$this->queryDirect("FLUSH TABLES");
			$this->queryDirect("OPTIMIZE TABLE `".$tablename['Name']."`");
		}
		return $tablecnt;
	}

    public function getNumRows($result)
    {
        return (!isset($result->num_rows)) ? 0 : $result->num_rows;
    }

	public function Prepare($query)
	{
		return DB::$db->prepare($query);
	}

	public function Error()
	{
		return DB::$db->error;
	}

	public function setAutoCommit($enabled)
	{
		return DB::$db->autocommit($enabled);
	}

	public function Commit()
	{
		return DB::$db->commit();
	}

	public function Rollback()
	{
		return DB::$db->rollback();
	}
}
?>
