<?php

require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/category.php");

class Namefixer
{
	private $tmpName = '';
	private $newName = '';
	private $relsID = '';
	private $functionUsed = '';
	//
	//Attempts to fix release names using the release name.
	//
	public function fixNamesWithNames($time, $echo)
	{
		$db = new DB();
		$query = "SELECT name, ID as releaseID from releases";
		
		//Do 24 hours or full DB.
		if ($time == 1)
		{
			$relres = $db->queryDirect(sprintf($query,"where adddate > (now() - interval 1 day)"));
		}
		
		if ($time == 2)
		{
			$relres = $db->queryDirect($query);
		}
		
		while ($relrow = $db->fetchArray($relres))
		{
			$this->checkName($relrow, $echo);
		}
	}
	
	//
	//Attempts to fix release names using the NFO.
	//
	public function fixNamesWithNfo($time, $echo)
	{
		$db = new DB();
		//For testing do all cats//$query = "SELECT uncompress(nfo) as NFO, nfo.releaseID as nfoID, rel.ID as relID from releases rel left join releasenfo nfo on (nfo.releaseID = rel.ID) where rel.categoryID = 7010 and rel.categoryID = 2020 and categoryID = 5050";
		$query = "SELECT uncompress(nfo) as NFO, nfo.releaseID as nfoID, rel.ID as relID from releases rel left join releasenfo nfo on (nfo.releaseID = rel.ID)";
		
		//Do 24 hours or full DB.
		if ($time == 1)
		{
			$nfores = $db->queryDirect(sprintf($query,"and adddate > (now() - interval 1 day group by rel.ID"));
		}
		
		if ($time == 2)
		{
			$nfores = $db->queryDirect(sprintf($query,"group by rel.ID"));
		}
		
		while ($nforow = $db->fetchArray($nfores))
		{
			$this->checkName($nforow, $echo);
		}
	}
	
	//
	//Attempts to fix release names using the File name.
	//
	public function fixNamesWithFiles($time, $echo)
	{
		$db = new DB();
		//$query = "SELECT relfiles.name as filename, relfiles.releaseID as fileID, rel.ID as relID from releases rel left join releasefiles relfiles on (relfiles.releaseID = rel.ID) where rel.categoryID = 7010 and rel.categoryID = 2020 and categoryID = 5050";
		$query = "SELECT relfiles.name as filename, relfiles.releaseID as fileID, rel.ID as relID from releases rel left join releasefiles relfiles on (relfiles.releaseID = rel.ID)";
		
		//Do 24 hours or full DB.
		if ($time == 1)
		{
			$fileres = $db->queryDirect(sprintf($query,"and adddate > (now() - interval 1 day group by rel.ID"));
		}
		
		if ($time == 2)
		{
			$fileres = $db->queryDirect(sprintf($query,"group by rel.ID"));
		}
		
		while ($filerow = $db->fetchArray($fileres))
		{
			$this->checkName($filerow, $echo);
		}
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
	public function checkName($array, $echo)
	{                          
		if($this->tvCheck($array))
		{ 
			if ($echo == 1)
			{
			
			}
			else if ($echo == 2)
			{
				//$this->updateRelease($ID, $name);
			}
		}
		if($this->movieCheck($array))
		{ 
			if ($echo == 1)
			{
			
			}
			else if ($echo == 2)
			{
				//$this->updateRelease($ID, $name);
			}
		}
	}
	
	//
	//Look for a TV name.
	//
	public function tvCheck($array)
	{
		foreach ($array as $searchname)
		{
			/*if (preg_match('/\w.+hdtv.+\w/i', $searchname, $result))
			{
				echo $result['0']."\n";
			}*/
			if (preg_match('/[a-z0-9.]+s\d{1,2}e\d{1,2}\.hdtv\.x264+[a-z0-9.-]+/i', $searchname, $result))
			{
				echo $result['0']."\n";
			}
			
		}
	}
	
	//
	//Look for a movie name.
	//
	public function movieCheck($array)
	{
		
	}
}
