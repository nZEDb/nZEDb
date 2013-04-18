<?php

require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/category.php");
require_once(WWW_DIR."/lib/namecleaning.php");

class Namefixer
{
	private $tmpName = '';
	private $newName = '';
	private $relsID = '';
	private $functionUsed = '';
	//
	//Attempts to fix release names using the release name.
	//
	public function fixNamesWithNames($time, $echo, $cats)
	{
		$db = new DB();
		$query = "SELECT name, searchname, ID as releaseID from releases";
		
		//24 hours, other cats
		if ($time == 1 && $cats == 1)
		{
			$relres = $db->queryDirect($query." where adddate > (now() - interval 1 day) and (categoryID like \"2020\" or categoryID like \"3050\" or categoryID like \"6050\" or categoryID like \"5050\" or categoryID like \"7010\" or categoryID like \"8050\")");
		}
		//24 hours, all cats
		if ($time == 1 && $cats == 2)
		{
			$relres = $db->queryDirect($query." where adddate > (now() - interval 1 day)");
		}
		//other cats
		if ($time == 2 && $cats == 1)
		{
			$relres = $db->queryDirect($query." where (categoryID like \"2020\" or categoryID like \"3050\" or categoryID like \"6050\" or categoryID like \"5050\" or categoryID like \"7010\" or categoryID like \"8050\")");
		}
		//all cats
		if ($time == 2 && $cats == 2)
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
		$query = "SELECT nfo.releaseID as nfoID, uncompress(nfo) as NFO, rel.ID as releaselID from releases rel left join releasenfo nfo on (nfo.releaseID = rel.ID)";
		
		//24 hours, other cats
		if ($time == 1 && $cats == 1)
		{
			$relres = $db->queryDirect($query." where rel.adddate > (now() - interval 1 day) and (rel.categoryID like \"2020\" or rel.categoryID like \"3050\" or rel.categoryID like \"6050\" or rel.categoryID like \"5050\" or rel.categoryID like \"7010\" or rel.categoryID like \"8050\") group by rel.ID");
		}
		//24 hours, all cats
		if ($time == 1 && $cats == 2)
		{
			$relres = $db->queryDirect($query." where rel.adddate > (now() - interval 1 day) group by rel.ID");
		}
		//other cats
		if ($time == 2 && $cats == 1)
		{
			$relres = $db->queryDirect($query." where (rel.categoryID like \"2020\" or rel.categoryID like \"3050\" or rel.categoryID like \"6050\" or rel.categoryID like \"5050\" or rel.categoryID like \"7010\" or rel.categoryID like \"8050\") group by rel.ID");
		}
		//all cats
		if ($time == 2 && $cats == 2)
		{
			$relres = $db->queryDirect($query);
		}
		
		while ($relrow = $db->fetchArray($relres))
		{
			$this->checkName($relrow, $echo);
		}
	}
	
	//
	//Attempts to fix release names using the File name.
	//
	public function fixNamesWithFiles($time, $echo)
	{
		$db = new DB();
		//$query = "SELECT relfiles.name as filename, relfiles.releaseID as fileID, rel.ID as relID from releases rel left join releasefiles relfiles on (relfiles.releaseID = rel.ID) where rel.categoryID = 7010 and rel.categoryID = 2020 and categoryID = 5050";
		$query = "SELECT relfiles.name as filename, relfiles.releaseID as fileID, rel.ID as releaseID from releases rel left join releasefiles relfiles on (relfiles.releaseID = rel.ID)";
		
		//24 hours, other cats
		if ($time == 1 && $cats == 1)
		{
			$relres = $db->queryDirect($query." where rel.adddate > (now() - interval 1 day) and (rel.categoryID like \"2020\" or rel.categoryID like \"3050\" or rel.categoryID like \"6050\" or rel.categoryID like \"5050\" or rel.categoryID like \"7010\" or rel.categoryID like \"8050\") group by rel.ID");
		}
		//24 hours, all cats
		if ($time == 1 && $cats == 2)
		{
			$relres = $db->queryDirect($query." where rel.adddate > (now() - interval 1 day) group by rel.ID");
		}
		//other cats
		if ($time == 2 && $cats == 1)
		{
			$relres = $db->queryDirect($query." where (rel.categoryID like \"2020\" or rel.categoryID like \"3050\" or rel.categoryID like \"6050\" or rel.categoryID like \"5050\" or rel.categoryID like \"7010\" or rel.categoryID like \"8050\") group by rel.ID");
		}
		//all cats
		if ($time == 2 && $cats == 2)
		{
			$relres = $db->queryDirect($query);
		}
		
		while ($filerow = $db->fetchArray($fileres))
		{
			$this->checkName($filerow, $echo);
		}
	}
	
	//
	//Update the release with the new information.
	//
	public function updateRelease($release, $name, $method, $echo)
	{
		$n = "\n";
		$namecleaning = new nameCleaning();
		$newname = $namecleaning->fixerCleaner($name);
		
		if ($echo == 1)
		{
			echo	"New name: ".$newname.$n.
					"Old name: ".$release["searchname"].$n.
					"Method:   ".$method.$n.$n;
			$db = new DB();
			$db->queryDirect(sprintf("UPDATE releases set searchname = %s where ID = %d", $release["releaseID"], $newname));
		}
		if ($echo == 2)
		{
			echo	"New name: ".$newname.$n.
					"Old name: ".$release["searchname"].$n.
					"Method:   ".$method.$n.$n;
		}
	}
	
	//
	//Check the array using regex for a clean name.
	//
	public function checkName($release, $echo)
	{                       
		$this->tvCheck($release, $echo);
		//$this->movieCheck($array, $echo);
	}
	
	//
	//Look for a TV name.
	//
	public function tvCheck($release, $echo)
	{
		if (preg_match('/\w[\w.\-\',;& ]+((s\d{1,2}(\.|_|\-| )?(b|d|e)\d{1,2})|\d{1,2}x\d{2}|ep(\.|_|\-| )?\d{2})[\w.\-\',;& ]+(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["name"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="tvCheck: Title.SxxEx.EpTitle.source.vcodec.group", $echo);
		}
	}
	
	//
	//Look for a movie name.
	//
	public function movieCheck($array)
	{
		
	}
}
