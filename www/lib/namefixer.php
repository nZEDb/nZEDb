<?php

require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/category.php");
require_once(WWW_DIR."/lib/groups.php");
require_once(WWW_DIR."/lib/namecleaning.php");

class Namefixer
{
	
	function Namefixer()
	{
		$this->relid = 0;
		$this->fixed = 0;
		$this->checked = 0;
	}
	
	//
	// Attempts to fix release names using the release name.
	//
	public function fixNamesWithNames($time, $echo, $cats, $namestatus)
	{
		$db = new DB();
		$type = "Release Name, ";
		$query = "SELECT name as textstring, searchname, categoryID, groupID, ID as releaseID from releases where categoryID != 5070 and relnamestatus = 1";
		
		//24 hours, other cats
		if ($time == 1 && $cats == 1)
		{
			$relres = $db->queryDirect($query." and adddate > (now() - interval 6 hour) and (categoryID like \"2020\" or categoryID like \"3050\" or categoryID like \"6050\" or categoryID like \"5050\" or categoryID like \"7010\" or categoryID like \"8050\")"." order by releaseID asc");
		}
		//24 hours, all cats
		if ($time == 1 && $cats == 2)
		{
			$relres = $db->queryDirect($query." and adddate > (now() - interval 6 hour)"." order by releaseID asc");
		}
		//other cats
		if ($time == 2 && $cats == 1)
		{
			$relres = $db->queryDirect($query." and (categoryID like \"2020\" or categoryID like \"3050\" or categoryID like \"6050\" or categoryID like \"5050\" or categoryID like \"7010\" or categoryID like \"8050\")"." order by releaseID asc");
		}
		//all cats
		if ($time == 2 && $cats == 2)
		{
			$relres = $db->queryDirect($query." order by releaseID asc");
		}
		
		$rowcount = $db->getAffectedRows();
		
		if ($rowcount > 0)
		{
			while ($relrow = $db->fetchArray($relres))
			{
				$this->checkName($relrow, $echo, $type, $namestatus);
				$this->checked++;
				if ($this->checked % 500 == 0)
					echo $this->checked." names processed.\n\n";
			}
			if ($echo == 1)
				echo $this->fixed." releases have had their names changed out of: ".$this->checked." names.\n";
			else
				echo $this->fixed." releases could have their names changed. ".$this->checked." release names were checked.\n";
		}
		else
			echo "Nothing to fix.\n";
	}
	
	//
	//	Attempts to fix release names using the NFO.
	//
	public function fixNamesWithNfo($time, $echo, $cats, $namestatus)
	{
		$db = new DB();
		$type = "NFO, ";
		$query = "SELECT nfo.releaseID as nfoID, rel.groupID, rel.categoryID, rel.searchname, uncompress(nfo) as textstring, rel.ID as releaseID from releases rel left join releasenfo nfo on (nfo.releaseID = rel.ID) where categoryID != 5070 and relnamestatus = 1";
		
		//24 hours, other cats
		if ($time == 1 && $cats == 1)
		{
			$relres = $db->queryDirect($query." and rel.adddate > (now() - interval 6 hour) and (rel.categoryID like \"2020\" or rel.categoryID like \"3050\" or rel.categoryID like \"6050\" or rel.categoryID like \"5050\" or rel.categoryID like \"7010\" or rel.categoryID like \"8050\") group by rel.ID"." order by releaseID asc");
		}
		//24 hours, all cats
		if ($time == 1 && $cats == 2)
		{
			$relres = $db->queryDirect($query." and rel.adddate > (now() - interval 6 hour) group by rel.ID"." order by releaseID asc");
		}
		//other cats
		if ($time == 2 && $cats == 1)
		{
			$relres = $db->queryDirect($query." and (rel.categoryID like \"2020\" or rel.categoryID like \"3050\" or rel.categoryID like \"6050\" or rel.categoryID like \"5050\" or rel.categoryID like \"7010\" or rel.categoryID like \"8050\") group by rel.ID"." order by releaseID asc");
		}
		//all cats
		if ($time == 2 && $cats == 2)
		{
			$relres = $db->queryDirect($query." order by releaseID asc");
		}
		
		$rowcount = $db->getAffectedRows();
		
		if ($rowcount > 0)
		{
			while ($relrow = $db->fetchArray($relres))
			{
				$this->checkName($relrow, $echo, $type, $namestatus);
				$this->checked++;
				if ($this->checked % 500 == 0)
					echo $this->checked." NFOs processed.\n\n";
			}
			if($echo ==1)
				echo $this->fixed." releases have had their names changed out of: ".$this->checked." NFO's.\n";
			else
				echo $this->fixed." releases could have their names changed. ".$this->checked." NFO's were checked.\n";
		}
		else
			echo "Nothing to fix.\n";
	}
	
	//
	//	Attempts to fix release names using the File name.
	//
	public function fixNamesWithFiles($time, $echo, $cats, $namestatus)
	{
		$db = new DB();
		$type = "Filenames, ";
		$query = "SELECT relfiles.name as textstring, rel.categoryID, rel.searchname, rel.groupID, relfiles.releaseID as fileID, rel.ID as releaseID from releases rel left join releasefiles relfiles on (relfiles.releaseID = rel.ID) where categoryID != 5070 and relnamestatus = 1";
		
		//24 hours, other cats
		if ($time == 1 && $cats == 1)
		{
			$relres = $db->queryDirect($query." and rel.adddate > (now() - interval 6 hour) and (rel.categoryID like \"2020\" or rel.categoryID like \"3050\" or rel.categoryID like \"6050\" or rel.categoryID like \"5050\" or rel.categoryID like \"7010\" or rel.categoryID like \"8050\") group by rel.ID"." order by releaseID asc");
		}
		//24 hours, all cats
		if ($time == 1 && $cats == 2)
		{
			$relres = $db->queryDirect($query." and rel.adddate > (now() - interval 6 hour) group by rel.ID"." order by releaseID asc");
		}
		//other cats
		if ($time == 2 && $cats == 1)
		{
			$relres = $db->queryDirect($query." and (rel.categoryID like \"2020\" or rel.categoryID like \"3050\" or rel.categoryID like \"6050\" or rel.categoryID like \"5050\" or rel.categoryID like \"7010\" or rel.categoryID like \"8050\") group by rel.ID"." order by releaseID asc");
		}
		//all cats
		if ($time == 2 && $cats == 2)
		{
			$relres = $db->queryDirect($query." order by releaseID asc");
		}
		
		$rowcount = $db->getAffectedRows();
		
		if ($rowcount > 0)
		{
			while ($relrow = $db->fetchArray($relres))
			{
				$this->checkName($relrow, $echo, $type, $namestatus);
				$this->checked++;
				if ($this->checked % 500 == 0)
					echo $this->checked." files processed.\n\n";
			}
			if($echo == 1)
				echo $this->fixed." releases have had their names changed out of: ".$this->checked." files.\n";
			else
				echo $this->fixed." releases could have their names changed. ".$this->checked." files were checked.\n";
		}
		else
			echo "Nothing to fix.\n";
	}
	
	//
	//	Update the release with the new information.
	//
	public function updateRelease($release, $name, $method, $echo, $type, $namestatus)
	{
		$n = "\n";
		$namecleaning = new nameCleaning();
		$newname = $namecleaning->fixerCleaner($name);
		
		if ($this->relid !== $release["releaseID"])
		{
			if ($newname !== $release["searchname"])
			{
				$this->relid = $release["releaseID"];
				
				$category = new Category();
				$determinedcat = $category->determineCategory($newname, $release["groupID"]);
				$oldcatname = $category->getNameByID($release["categoryID"]);
				$newcatname = $category->getNameByID($determinedcat);
				
				$groups = new Groups();
				$groupname = $groups->getByNameByID($release["groupID"]);
				
				$this->fixed ++;
				if ($echo == 1)
				{
					echo	"New name: ".$newname.$n.
							"Old name: ".$release["searchname"].$n.
							"New cat:  ".$newcatname.$n.
							"Old cat:  ".$oldcatname.$n.
							"Group:    ".$groupname.$n.
							"Method:   ".$type.$method.$n.$n;
				
					if ($namestatus == 1)
					{
						$db = new DB();
						$db->queryDirect(sprintf("UPDATE releases set searchname = %s, relnamestatus = 2, categoryID = %d where ID = %d", $db->escapeString($newname), $determinedcat, $release["releaseID"]));
					}
					else
					{
						$db = new DB();
						$db->queryDirect(sprintf("UPDATE releases set searchname = %s, categoryID = %d where ID = %d", $db->escapeString($newname), $determinedcat, $release["releaseID"]));
					}
				}
				if ($echo == 2)
				{
					echo	"New name: ".$newname.$n.
							"Old name: ".$release["searchname"].$n.
							"New cat:  ".$newcatname.$n.
							"Old cat:  ".$oldcatname.$n.
							"Group:    ".$groupname.$n.
							"Method:   ".$type.$method.$n.$n;
				}
			}
		}
	}
	
	//
	//	Check the array using regex for a clean name.
	//
	public function checkName($release, $echo, $type, $namestatus)
	{
		// Just for filenames.
		if ($type == "Filenames, ")
		{
			$this->fileCheck($release, $echo, $type, $namestatus);
		}
		$this->tvCheck($release, $echo, $type, $namestatus);
		$this->movieCheck($release, $echo, $type, $namestatus);
		$this->gameCheck($release, $echo, $type, $namestatus);
		$this->appCheck($release, $echo, $type, $namestatus);
		// Just for NFOs.
		if ($type == "NFO, ")
		{
			$this->nfoCheckTV($release, $echo, $type, $namestatus);
			$this->nfoCheckM($release, $echo, $type, $namestatus);
			$this->nfoCheckTY($release, $echo, $type, $namestatus);
			$this->nfoCheckG($release, $echo, $type, $namestatus);
		}
	}
	
	//
	//	Look for a TV name.
	//
	public function tvCheck($release, $echo, $type, $namestatus)
	{
		if (preg_match('/\w[\w.\-\',;& ]+((s\d{1,2}(\.|_|\-| )?(b|d|e)\d{1,2})|\d{1,2}x\d{2}|ep(\.|_|\-| )?\d{2})[\w.\-\',;.\(\)]+(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="tvCheck: Title.SxxExx.Text.source.group", $echo, $type, $namestatus);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((s\d{1,2}(\.|_|\-| )?(b|d|e)\d{1,2})|\d{1,2}x\d{2}|ep(\.|_|\-| )?\d{2})[\w.\-\',;& ]+((19|20)\d\d)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="tvCheck: Title.SxxExx.Text.year.group", $echo, $type, $namestatus);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((s\d{1,2}(\.|_|\-| )?(b|d|e)\d{1,2})|\d{1,2}x\d{2}|ep(\.|_|\-| )?\d{2})[\w.\-\',;& ]+(480|720|1080)(i|p)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="tvCheck: Title.SxxExx.Text.resolution.source.vcodec.group", $echo, $type, $namestatus);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((s\d{1,2}(\.|_|\-| )?(b|d|e)\d{1,2})|\d{1,2}x\d{2}|ep(\.|_|\-| )?\d{2})(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="tvCheck: Title.SxxExx.source.vcodec.group", $echo, $type, $namestatus);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((s\d{1,2}(\.|_|\-| )?(b|d|e)\d{1,2})|\d{1,2}x\d{2}|ep(\.|_|\-| )?\d{2})(\.|_|\-| )(AAC( LC)?|AC-?3|DD5((\.|_|\-| )1)?|(A_)?DTS(-)?(HD)?|Dolby(( )?TrueHD)?|MP3|TrueHD)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(480|720|1080)(i|p)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="tvCheck: Title.SxxExx.acodec.source.res.vcodec.group", $echo, $type, $namestatus);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((s\d{1,2}(\.|_|\-| )?(b|d|e)\d{1,2})|\d{1,2}x\d{2}|ep(\.|_|\-| )?\d{2})[\w.\-\',;& ]+((19|20)\d\d)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="tvCheck: Title.SxxExx.resolution.source.vcodec.group", $echo, $type, $namestatus);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((19|20)\d\d)(\.|_|\-| )((s\d{1,2}(\.|_|\-| )?(b|d|e)\d{1,2})|\d{1,2}x\d{2}|ep(\.|_|\-| )?\d{2})(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="tvCheck: Title.year.###(season/episode).source.group", $echo, $type, $namestatus);
		}
		if (preg_match('/\w(19|20)\d\d(\.|_|\-| )\d{2}(\.|_|\-| )\d{2}(\.|_|\-| )(IndyCar|NBA|NCW(T|Y)S|NNS|NSCS?)((\.|_|\-| )(19|20)\d\d)?[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="tvCheck: Sports", $echo, $type, $namestatus);
		}
	}
	
	//
	//	Look for a movie name.
	//
	public function movieCheck($release, $echo, $type, $namestatus)
	{
		if (preg_match('/\w[\w.\-\',;& ]+((19|20)\d\d)[\w.\-\',;& ]+(480|720|1080)(i|p)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.year.Text.res.vcod.group", $echo, $type, $namestatus);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((19|20)\d\d)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)(\.|_|\-| )(480|720|1080)(i|p)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.year.source.vcodec.res.group", $echo, $type, $namestatus);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((19|20)\d\d)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)(\.|_|\-| )(AAC( LC)?|AC-?3|DD5((\.|_|\-| )1)?|(A_)?DTS(-)?(HD)?|Dolby(( )?TrueHD)?|MP3|TrueHD)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.year.source.vcodec.acodec.group", $echo, $type, $namestatus);
		}
		if (preg_match('/\w[\w.\-\',;& ]+(Brazilian|Chinese|Croatian|Danish|Deutsch|Dutch|Estonian|English|Finnish|Flemish|Francais|French|German|Greek|Hebrew|Icelandic|Italian|Japenese|Japan|Japanese|Korean|Latin|Nordic|Norwegian|Polish|Portuguese|Russian|Serbian|Slovenian|Swedish|Spanisch|Spanish|Thai|Turkish)(\.|_|\-| )(AAC( LC)?|AC-?3|DD5((\.|_|\-| )1)?|(A_)?DTS(-)?(HD)?|Dolby(( )?TrueHD)?|MP3|TrueHD)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.year.language.acodec.source.vcodec.group", $echo, $type, $namestatus);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((19|20)\d\d)(\.|_|\-| )(480|720|1080)(i|p)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(AAC( LC)?|AC-?3|DD5((\.|_|\-| )1)?|(A_)?DTS(-)?(HD)?|Dolby(( )?TrueHD)?|MP3|TrueHD)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.year.resolution.source.acodec.vcodec.group", $echo, $type, $namestatus);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((19|20)\d\d)(\.|_|\-| )(480|720|1080)(i|p)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.year.resolution.source.vcodec.group", $echo, $type, $namestatus);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((19|20)\d\d)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(480|720|1080)(i|p)(\.|_|\-| )(AAC( LC)?|AC-?3|DD5((\.|_|\-| )1)?|(A_)?DTS(-)?(HD)?|Dolby(( )?TrueHD)?|MP3|TrueHD)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.year.source.resolution.acodec.vcodec.group", $echo, $type, $namestatus);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((19|20)\d\d)(\.|_|\-| )(480|720|1080)(i|p)(\.|_|\-| )(AAC( LC)?|AC-?3|DD5((\.|_|\-| )1)?|(A_)?DTS(-)?(HD)?|Dolby(( )?TrueHD)?|MP3|TrueHD)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.year.resolution.acodec.vcodec.group", $echo, $type, $namestatus);
		}
		if (preg_match('/[\w.\-\',;& ]+((19|20)\d\d)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BR(RIP)?|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(480|720|1080)(i|p)(\.|_|\-| )[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.year.source.res.group", $echo, $type, $namestatus);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((19|20)\d\d)(\.|_|\-| )[\w.\-\',;& ]+(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BR(RIP)?|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.year.eptitle.source.vcodec.group", $echo, $type, $namestatus);
		}
		if (preg_match('/\w[\w.\-\',;& ]+(480|720|1080)(i|p)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(AAC( LC)?|AC-?3|DD5((\.|_|\-| )1)?|(A_)?DTS(-)?(HD)?|Dolby(( )?TrueHD)?|MP3|TrueHD)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.resolution.source.acodec.vcodec.group", $echo, $type, $namestatus);
		}
		if (preg_match('/\w[\w.\-\',;& ]+(480|720|1080)(i|p)(\.|_|\-| )(AAC( LC)?|AC-?3|DD5((\.|_|\-| )1)?|(A_)?DTS(-)?(HD)?|Dolby(( )?TrueHD)?|MP3|TrueHD)[\w.\-\',;& ]+(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )((19|20)\d\d)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.resolution.acodec.eptitle.source.year.group", $echo, $type, $namestatus);
		}
		if (preg_match('/\w[\w.\-\',;& ]+(Brazilian|Chinese|Croatian|Danish|Deutsch|Dutch|Estonian|English|Finnish|Flemish|Francais|French|German|Greek|Hebrew|Icelandic|Italian|Japenese|Japan|Japanese|Korean|Latin|Nordic|Norwegian|Polish|Portuguese|Russian|Serbian|Slovenian|Swedish|Spanisch|Spanish|Thai|Turkish)(\.|_|\-| )((19|20)\d\d)(\.|_|\-| )(AAC( LC)?|AC-?3|DD5((\.|_|\-| )1)?|(A_)?DTS(-)?(HD)?|Dolby(( )?TrueHD)?|MP3|TrueHD)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.language.year.acodec.src", $echo, $type, $namestatus);
		}
	}
	
	//
	//	Look for a game name.
	//
	public function gameCheck($release, $echo, $type, $namestatus)
	{
		if (preg_match('/\w[\w.\-\',;& ]+(ASIA|DLC|EUR|GOTY|JPN|KOR|MULTI\d{1}|NTSCU?|PAL|RF|Region(\.|_|-|( ))?Free|USA|XBLA)(\.|_|\-| )(DLC(\.|_|\-| )Complete|FRENCH|GERMAN|MULTI\d{1}|PROPER|PSN|READ(\.|_|-|( ))?NFO|UMD)?(\.|_|\-| )?(GC|NDS|NGC|PS3|PSP|WII|XBOX(360)?)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
			$this->updateRelease($release, $result["0"], $methdod="gameCheck: Videogames 1", $echo, $type, $namestatus);
		if (preg_match('/\w[\w.\-\',;& ]+(GC|NDS|NGC|PS3|WII|XBOX(360)?)(\.|_|\-| )(DUPLEX|iNSOMNi|OneUp|STRANGE|SWAG|SKY)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
			$this->updateRelease($release, $result["0"], $methdod="gameCheck: Videogames 2", $echo, $type, $namestatus);
		if (preg_match('/\w[A-Za-z0-9._\-\',;].+-OUTLAWS/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$result = str_replace("OUTLAWS","PC GAME OUTLAWS",$result['0']);
			$this->updateRelease($release, $result["0"], $methdod="gameCheck: PC Games -OUTLAWS", $echo, $type, $namestatus);
		}
		if (preg_match('/\w[A-Za-z0-9._\-\',;].+-ALiAS/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$result = str_replace("ALiAS","PC GAME ALiAS",$result['0']);
			$this->updateRelease($release, $result["0"], $methdod="gameCheck: PC Games -ALiAS", $echo, $type, $namestatus);
		}
	}
	
	//
	//	Look for a app name.
	//
	public function appCheck($release, $echo, $type, $namestatus)
	{
		if (preg_match('/\w[\w.\-\',;& ]+(\d{1,10}|Linux|UNIX)(\.|_|\-| )(RPM)?(\.|_|\-| )?(X64)?(\.|_|\-| )?(Incl)(\.|_|\-| )(Keygen)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
			$this->updateRelease($release, $result["0"], $methdod="appCheck: Apps 1", $echo, $type, $namestatus);
		if (preg_match('/\w[\w.\-\',;& ]+(\d){1,8}(\.|_|\-| )(winall-freeware)[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
			$this->updateRelease($release, $result["0"], $methdod="appCheck: Apps 2", $echo, $type, $namestatus);
	}
	
	/*
	 * 
	 * Just for NFOS.
	 * 
	 */
	
	//
	//	TV.
	//
	public function nfoCheckTV($release, $echo, $type, $namestatus)
	{
		if(preg_match('/(?:(\:\s{1,}))(.+?S\d{1,3}[.-_ ]?(E|D)\d{1,3}.+?)(\s{2,}|\r|\n)/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
			$this->updateRelease($release, $result["2"], $methdod="nfoCheck: Generic TV", $echo, $type, $namestatus);
	}
	
	//
	//	Movies.
	//
	public function nfoCheckM($release, $echo, $type, $namestatus)
	{
		if(preg_match('/(?:(\:\s{1,}))(.+?[\.\-_ ]\d{4}[\.\-_ ].+?(BDRip|bluray|DVDRip|XVID).+?)(\s{2,}|\r|\n)/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
			$this->updateRelease($release, $result["2"], $methdod="nfoCheck: Generic Movies", $echo, $type, $namestatus);
	}
	
	//
	//	Title (year)
	//
	public function nfoCheckTY($release, $echo, $type, $namestatus)
	{
		//Title(year)
		if(preg_match('/(\w[\w`~!@#$%^&*()_+\-={}|"<>?\[\]\\;\',.\/ ]+\s?\((19|20)\d\d\))/i', $release["textstring"], $result) && !preg_match('/\.pdf|Audio\s?Book/i', $release["textstring"]) && $this->relid !== $release["releaseID"])
		{
			$releasename = $result[0];			
			if(preg_match('/(idiomas|lang|language|langue|sprache).*?\b(Brazilian|Chinese|Croatian|Danish|DE|Deutsch|Dutch|Estonian|ES|English|Englisch|Finnish|Flemish|Francais|French|FR|German|Greek|Hebrew|Icelandic|Italian|Japenese|Japan|Japanese|Korean|Latin|Nordic|Norwegian|Polish|Portuguese|Russian|Serbian|Slovenian|Swedish|Spanisch|Spanish|Thai|Turkish)\b/i', $release["textstring"], $result))
			{
				if($result[2] == 'DE') 		{$result[2] = 'DUTCH';}
				if($result[2] == 'Englisch'){$result[2] = 'English';}
				if($result[2] == 'FR') 		{$result[2] = 'FRENCH';}
				if($result[2] == 'ES') 		{$result[2] = 'SPANISH';}
				$releasename = $releasename.".".$result[2];
			}					
			if(preg_match('/(frame size|res|resolution|video|video res).*?(272|336|480|494|528|608|640|\(640|688|704|720x480|816|820|1080|1 080|1280 @|1280|1920|1 920|1920x1080)/i', $release["textstring"], $result))
			{
				if($result[2] == '272')		{$result[2] = '272p';}
				if($result[2] == '336') 	{$result[2] = '480p';}
				if($result[2] == '480') 	{$result[2] = '480p';}
				if($result[2] == '494') 	{$result[2] = '480p';}
				if($result[2] == '608') 	{$result[2] = '480p';}
				if($result[2] == '640') 	{$result[2] = '480p';}
				if($result[2] == '\(640')	{$result[2] = '480p';}
				if($result[2] == '688')		{$result[2] = '480p';}
				if($result[2] == '704')		{$result[2] = '480p';}
				if($result[2] == '720x480') {$result[2] = '480p';}
				if($result[2] == '816')		{$result[2] = '1080p';}
				if($result[2] == '820')		{$result[2] = '1080p';}	
				if($result[2] == '1080')	{$result[2] = '1080p';}
				if($result[2] == '1280x720'){$result[2] = '720p';}
				if($result[2] == '1280 @')	{$result[2] = '720p';}
				if($result[2] == '1280')	{$result[2] = '720p';}
				if($result[2] == '1920')	{$result[2] = '1080p';}	
				if($result[2] == '1 920')	{$result[2] = '1080p';}	
				if($result[2] == '1 080')	{$result[2] = '1080p';}
				if($result[2] == '1920x1080'){$result[2] = '1080p';}
				$releasename = $releasename.".".$result[2];
			}
			if(preg_match('/(largeur|width).*?(640|\(640|688|704|720|1280 @|1280|1920|1 920)/i', $release["textstring"], $result))
			{
				if($result[2] == '640')		{$result[2] = '480p';}
				if($result[2] == '\(640')	{$result[2] = '480p';}
				if($result[2] == '688')		{$result[2] = '480p';}
				if($result[2] == '704')		{$result[2] = '480p';}
				if($result[2] == '1280 @')	{$result[2] = '720p';}
				if($result[2] == '1280')	{$result[2] = '720p';}
				if($result[2] == '1920')	{$result[2] = '1080p';}	
				if($result[2] == '1 920')	{$result[2] = '1080p';}		
				if($result[2] == '720')		{$result[2] = '480p';}
				$releasename = $releasename.".".$result[2];
			}
			if(preg_match('/source.*?\b(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)\b/i', $release["textstring"], $result))
			{	
				if($result[1] == 'BD')		{$result[1] = 'Bluray.x264';}
				if($result[1] == 'CAMRIP')	{$result[1] = 'CAM';}
				if($result[1] == 'DBrip')	{$result[1] = 'BDRIP';}
				if($result[1] == 'DVD R1')	{$result[1] = 'DVD';}
				if($result[1] == 'HD')		{$result[1] = 'HDTV';}
				if($result[1] == 'NTSC')	{$result[1] = 'DVD';}
				if($result[1] == 'PAL')		{$result[1] = 'DVD';}
				if($result[1] == 'Ripped ')	{$result[1] = 'DVDRIP';}
				if($result[1] == 'VOD')		{$result[1] = 'DVD';}
				$releasename = $releasename.".".$result[1];
			}
			if(preg_match('/(codec|codec name|codec code|format|MPEG-4 Visual|original format|res|resolution|video|video codec|video format|video res|tv system|type|writing library).*?\b(AVC|AVI|DBrip|DIVX|\(Divx|DVD|(H|X)(\.|_|\-| )?264|NTSC|PAL|WMV|XVID)\b/i', $release["textstring"], $result))
			{
				if($result[2] == 'AVI')				{$result[2] = 'DVDRIP';}
				if($result[2] == 'DBrip')			{$result[2] = 'BDRIP';}
				if($result[2] == '(Divx')			{$result[2] = 'DIVX';}
				if($result[2] == 'h.264')			{$result[2] = 'H264';}
				if($result[2] == 'MPEG-4 Visual')	{$result[2] = 'x264';}
				if($result[1] == 'NTSC')			{$result[1] = 'DVD';}
				if($result[1] == 'PAL')				{$result[1] = 'DVD';}
				if($result[2] == 'x.264')			{$result[2] = 'x264';}
				$releasename = $releasename.".".$result[2];
			}
			if(preg_match('/(audio|audio format|codec|codec name|format).*?\b(0x0055 MPEG-1 Layer 3|AAC( LC)?|AC-?3|\(AC3|DD5(.1)?|(A_)?DTS(-)?(HD)?|Dolby(\s?TrueHD)?|TrueHD|FLAC|MP3)\b/i', $release["textstring"], $result))
			{
				if($result[2] == '0x0055 MPEG-1 Layer 3'){$result[2] = 'MP3';}
				if($result[2] == 'AC-3')	{$result[2] = 'AC3';}
				if($result[2] == '(AC3')	{$result[2] = 'AC3';}
				if($result[2] == 'AAC LC')	{$result[2] = 'AAC';}
				if($result[2] == 'A_DTS')	{$result[2] = 'DTS';}
				if($result[2] == 'DTS-HD')	{$result[2] = 'DTS';}
				if($result[2] == 'DTSHD')	{$result[2] = 'DTS';}
				$releasename = $releasename.".".$result[2];
			}
			$releasename = $releasename."-NoGroup";
			$this->updateRelease($release, $releasename, $methdod="nfoCheck: Title (Year)", $echo, $type, $namestatus);
		}
	}
	
	//
	//	Games.
	//
	public function nfoCheckG($release, $echo, $type, $namestatus)
	{
		if(preg_match('/ALiAS|BAT-TEAM|\FAiRLiGHT|Game Type|HI2U|iTWINS|JAGUAR|LARGEISO|MAZE|MEDIUMISO|nERv|PROPHET|PROFiT|PROCYON|RELOADED|REVOLVER|ROGUE|ViTALiTY/i', $release["textstring"]) && $this->relid !== $release["releaseID"])
		{
			if(preg_match('/\w[\w.\+\&\*\/\-\(\)\',;: ]+\(c\)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
			{
				$releasename = str_replace("(c)","(PC GAMES) (c)", $result['0']);
				$this->updateRelease($release, $releasename, $methdod="nfoCheck: PC Games (c)", $echo, $type, $namestatus);
			}
			if(preg_match('/\w[\w.\+\&\*\/\-\(\)\',;: ]+\*ISO\*/i', $release["textstring"], $result))
			{
				$releasename = str_replace("*ISO*","*ISO* (PC GAMES)", $result['0']);
				$this->updateRelease($release, $releasename, $methdod="nfoCheck: PC Games *ISO*", $echo, $type, $namestatus);
			}
		}
	}
	
	//
	//	Misc.
	//
	public function nfoCheckMisc($release, $echo, $type, $namestatus)
	{
		if(preg_match('/Supplier.+?IGUANA/i', $release["textstring"]) && $this->relid !== $release["releaseID"])
		{
			if(preg_match('/\w[\w`~!@#$%^&*()_+\-={}|:"<>?\[\]\\;\',.\/ ]+\s\((19|20)\d\d\)/i', $release["textstring"], $result))
				$releasename = $result[0];
			if(preg_match('/\s\[\*\] (English|Dutch|French|German|Spanish)\b/i', $release["textstring"], $result))
				$releasename = $releasename.".".$result[1];
			if(preg_match('/\s\[\*\] (DTS 6(\.|_|\-| )1|DS 5(\.|_|\-| )1|DS 2(\.|_|\-| )0|DS 2(\.|_|\-| )0 MONO)\b/i', $release["textstring"], $result))
				$releasename = $releasename.".".$result[2];
			if(preg_match('/Format.+(DVD(5|9|R)?|(h|x)(\.|_|\-| )?264)\b/i', $release["textstring"], $result))
				$releasename = $releasename.".".$result[1];
			if(preg_match('/\[(640x.+|1280x.+|1920x.+)\] Resolution\b/i', $release["textstring"], $result))
			{
				if($result[1] == '640x.+') {$result[1] = '480p';}
				if($result[1] == '1280x.+'){$result[1] = '720p';}
				if($result[1] == '1920x.+'){$result[1] = '1080p';}
				$releasename = $releasename.".".$result[1];
			}
			$releasename = $releasename.".IGUANA";
			$this->updateRelease($release, $result, $methdod="nfoCheck: IGUANA", $echo, $type, $namestatus);
		}
	}
	
	//
	//	Just for filenames.
	//
	public function fileCheck($release, $echo, $type, $namestatus)
	{
		if (preg_match('/^(\\\\|\/)?(.+(\\\\|\/))*(.+?S\d{1,3}[.-_ ]?(E|D)\d{1,3}.+)\.(.+)$/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
			$this->updateRelease($release, $result["4"], $methdod="fileCheck: Generic TV", $echo, $type, $namestatus);
		if (preg_match('/^(\\\\|\/)?(.+(\\\\|\/))*(.+?([\.\-_ ]\d{4}[\.\-_ ].+?(BDRip|bluray|DVDRip|XVID)).+)\.(.+)$/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
			$this->updateRelease($release, $result["4"], $methdod="fileCheck: Generic movie", $echo, $type, $namestatus);
		if (preg_match('/(.+?([\.\-_ ]\dCD|CDR|FLAC|SAT|WEB).+?(19|20)\d\d.+?)\\\\.+/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
			$this->updateRelease($release, $result["1"], $methdod="fileCheck: Generic music", $echo, $type, $namestatus);
		if (preg_match('/^(.+?(19|20)\d\d\-([a-z0-9]{3})|[a-z]{2})\\\\/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
			$this->updateRelease($release, $result["1"], $methdod="fileCheck: music groups", $echo, $type, $namestatus);
		if (preg_match('/.+\\\\(.+\((19|20)\d\d\)\.avi)/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$newname = str_replace('.avi', ' DVDRip XVID', $result["1"]);
			$this->updateRelease($release, $newname, $methdod="fileCheck: Movie (year) avi", $echo, $type, $namestatus);
		}
		if (preg_match('/^(.+?IMAGESET.+?)\\\\.+/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
			$this->updateRelease($release, $result["1"], $methdod="fileCheck: XXX Imagesets", $echo, $type, $namestatus);
		if (preg_match('/\w[\w.\-\',;& ]+1080i(\.|_|\-| )DD5(\.|_|\-| )1(\.|_|\-| )MPEG2-R&C(?=\.ts)/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$result = str_replace("MPEG2","MPEG2.HDTV",$result["0"]);
			$this->updateRelease($release, $result, $methdod="fileCheck: R&C", $echo, $type, $namestatus);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((s\d{1,2}(\.|_|\-| )?(b|d|e)\d{1,2})|\d{1,2}x\d{2}|ep(\.|_|\-| )?\d{2})(\.|_|\-| )(480|720|1080)(i|p)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )nSD(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)(\.|_|\-| )NhaNC3[\w.\-\',;& ]+\w/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
			$this->updateRelease($release, $result["0"], $methdod="fileCheck: NhaNc3", $echo, $type, $namestatus);
		if (preg_match('/\wtvp-[\w.\-\',;]+((s\d{1,2}(\.|_|\-| )?(b|d|e)\d{1,2})|\d{1,2}x\d{2}|ep(\.|_|\-| )?\d{2})(\.|_|\-| )(720p|1080p|xvid)(?=\.(avi|mkv))/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
		{
			$result = str_replace("720p","720p.HDTV.X264",$result['0']);
			$result = str_replace("1080p","1080p.Bluray.X264",$result['0']);
			$result = str_replace("xvid","XVID.DVDrip",$result['0']);
			$this->updateRelease($release, $result, $methdod="fileCheck: tvp", $echo, $type, $namestatus);
		}
		if (preg_match('/\w[\w.\-\',;& ]+\d{3,4}\.hdtv-lol\.(avi|mp4|mkv|ts|nfo|nzb)/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
			$this->updateRelease($release, $result["0"], $methdod="fileCheck: Title.211.hdtv-lol.extension", $echo, $type, $namestatus);
		if (preg_match('/\w[\w.\-\',;& ]+-S\d{1,2}E\d{1,2}-XVID-DL.avi/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
			$this->updateRelease($release, $result["0"], $methdod="fileCheck: Title-SxxExx-XVID-DL.avi", $echo, $type, $namestatus);
		if (preg_match('/\S.*[\w.\-\',;]+\s\-\ss\d{2}e\d{2}\s\-\s[\w.\-\',;].+\./i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
			$this->updateRelease($release, $result["0"], $methdod="fileCheck: Title - SxxExx - Eptitle", $echo, $type, $namestatus);
		if (preg_match('/\w.+?\)\.nds/i', $release["textstring"], $result) && $this->relid !== $release["releaseID"])
			$this->updateRelease($release, $result["0"], $methdod="fileCheck: ).nds Nintendo DS", $echo, $type, $namestatus);
	}
}
?>
