<?php

require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/categorizer.php");

class Namefixer
{
	//
	//Attempts to fix release names using the release name.
	//
	public function fixNamesWithNames($type, $time)
	{
		//Fix releases which have never been fixed using the release name.
		if ($type == 1)
		{
			$query = "SELECT name, ID from releases where relnamestatus = 1"
		}
		
		//Fix all releases using the release name ignoring relnamestatus.
		if ($type == 2)
		{
			$query = "SELECT name, ID from releases"
		}
		
		//Do 24 hours or full DB.
		if ($time == 1)
		{
			$nameres = $db->queryDirect(sprintf($query,"where adddate > (now() - interval 1 day)"));
		}
		
		if ($time == 2)
		{
			$nameres = $db->queryDirect($query);
		}
	}
	
	//
	//Attempts to fix release names using the NFO. - Placeholder.
	//
	public function fixNamesWithNfo($type)
	{
		//fix releases using the NFO, added in the past 24 hours.
		if ($type == 1)
		{
			$query = "SELECT name, ID from releases where relnamestatus = 1"
		}
		//Fix all releases using the NFO.
		if ($type == 2)
		{
			$query = "SELECT name, ID from releases"
		}
		
		//Do 24 hours or full DB.
		if ($time == 1)
		{
			$nameres = $db->queryDirect(sprintf($query,"where adddate > (now() - interval 1 day)"));
		}
		
		if ($time == 2)
		{
			$nameres = $db->queryDirect($query);
		}
	}
	
	//
	//Attempts to fix release names using the File name. - Placeholder.
	//
	public function fixNamesWithFiles($type)
	{
		//fix releases using the release file, added in the past 24 hours.
		if ($type == 1)
		{
			$query = "SELECT name, ID from releases where relnamestatus = 1"
		}
		//Fix all releases using the file name.
		if ($type == 2)
		if ($type == 2)
		{
			$query = "SELECT name, ID from releases"
		}
		
		//Do 24 hours or full DB.
		if ($time == 1)
		{
			$nameres = $db->queryDirect(sprintf($query,"where adddate > (now() - interval 1 day)"));
		}
		
		if ($time == 2)
		{
			$nameres = $db->queryDirect($query);
		}
	}
	
	//
	//Reset the relnamestatus to 1.
	//
	public function resetRelname($type)
	{
		//Reset relnamestatus to 1 for releases added in the past 24 hours.
		if ($type == 1)
		{
			$db = new DB();
			$db->queryDirect("UPDATE releases set relnamestatus = 1 where adddate > (now() - interval 1 day)")
		}
		//Reset relnamestatus to 1 for all releases.
		if ($type == 2)
		{
			$db = new DB();
			$db->queryDirect("UPDATE releases set relnamestatus = 1")
		}
		
		if ($type > 2 || $type == 0 || !is_numeric($type))
		{
			exit("ERROR: Wrong argument.");
		}
	}
}
