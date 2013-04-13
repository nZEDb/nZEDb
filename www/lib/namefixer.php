<?php

require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/categorizer.php");

class Namefixer
{
	private $tmpName = array();
	//
	//Attempts to fix release names using the release name.
	//
	public function fixNamesWithNames($time)
	{
		//Fix releases which have never been fixed using the release name.
		
		$db = new DB();
		$query = "SELECT name, ID from releases";
		
		//Do 24 hours or full DB.
		if ($time == 1)
		{
			$relres = $db->queryDirect(sprintf($query,"where adddate > (now() - interval 1 day)"));
		}
		
		if ($time == 2)
		{
			$relres = $db->queryDirect($query);
		}
		
		while ($relrow = mysql_fetch_array($relres))
		{
			$this->checkName($relrow);
		}
	}
	
	//
	//Attempts to fix release names using the NFO.
	//
	public function fixNamesWithNfo($time)
	{
		//Fix releases in misc -> other using the NFO.
		
		$db = new DB();
		$query = "SELECT ID from releases where categoryID = 7010 and categoryID = 2020 and categoryID = 5050";
		
		//Do 24 hours or full DB.
		if ($time == 1)
		{
			$nameres = $db->queryDirect(sprintf($query,"and adddate > (now() - interval 1 day)"));
		}
		
		if ($time == 2)
		{
			$nameres = $db->queryDirect($query);
		}
		
		$nfo = $db->queryOneRow(sprintf("SELECT uncompress(nfo) as NFO from releasenfo where releaseID = %d", $nameres['ID']));
		$this->checkName($nfo);
	}
	
	//
	//Attempts to fix release names using the File name.
	//
	public function fixNamesWithFiles($time)
	{
		//Fix releases in misc -> other using the file names.
		
		$db = new DB();
		$query = "SELECT ID from releases where categoryID = 7010 and categoryID = 2020 and categoryID = 5050";
		
		//Do 24 hours or full DB.
		if ($time == 1)
		{
			$nameres = $db->queryDirect(sprintf($query,"and adddate > (now() - interval 1 day)"));
		}
		
		if ($time == 2)
		{
			$nameres = $db->queryDirect($query);
		}
		
		$filename = $db->queryOneRow(sprintf("SELECT name as filename from releasefiles where releaseID = %d", $nameres['ID']));
		$this->checkName($filename);
	}
	
	//
	//Update the release with the new information.
	//
	public function updateRelease($ID, $name)
	{
		$db = new DB();
		$db->queryDirect(sprintf("UPDATE releases set searchname = %s where ID = %d", $ID, $name));
	}
	
	//
	//Check the array using regex for a clean name.
	//
	public function checkName($array)
	{                          
		if($this->tvCheck($array))
		{ 
			$this->updateRelease($ID, $name);
		}
		if($this->movieCheck($array))
		{ 
			$this->updateRelease($ID, $name);
		}
	}
	
	//
	//Look for a TV name.
	//
	public function tvCheck($array)
	{
		print_r($array);
	}
	
	//
	//Look for a movie name.
	//
	public function movieCheck($array)
	{
		
	}
}
