<?php

require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/categorizer.php");

class Namefixer
{
	//
	//Attempts to fix release names using the release name.
	//
	public function fixNamesWithNames($time)
	{
		//Fix releases which have never been fixed using the release name.
		
		$query = "SELECT name, ID from releases"
		
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
	//Attempts to fix release names using the NFO.
	//
	public function fixNamesWithNfo($time)
	{
		//Fix releases in misc -> other using the NFO.
		
		$query = "SELECT name, ID from releases where categoryID = 7010 and categoryID = 2020 and categoryID = 5050"
		
		//Do 24 hours or full DB.
		if ($time == 1)
		{
			$nameres = $db->queryDirect(sprintf($query,"and adddate > (now() - interval 1 day)"));
		}
		
		if ($time == 2)
		{
			$nameres = $db->queryDirect($query);
		}
	}
	
	//
	//Attempts to fix release names using the File name.
	//
	public function fixNamesWithFiles($time)
	{
		//Fix releases in misc -> other using the file names.
		
		$query = "SELECT name, ID from releases where categoryID = 7010 and categoryID = 2020 and categoryID = 5050"
		
		//Do 24 hours or full DB.
		if ($time == 1)
		{
			$nameres = $db->queryDirect(sprintf($query,"and adddate > (now() - interval 1 day)"));
		}
		
		if ($time == 2)
		{
			$nameres = $db->queryDirect($query);
		}
	}
}
