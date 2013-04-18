<?php

require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/category.php");
require_once(WWW_DIR."/lib/namecleaning.php");

class Namefixer
{
	//
	//	Attempts to fix release names using the release name.
	//
	public function fixNamesWithNames($time, $echo, $cats)
	{
		$db = new DB();
		$type = "Release Name, ";
		$query = "SELECT name as textstring, searchname, categoryID, groupID, ID as releaseID from releases where categoryID != 5070";
		
		//24 hours, other cats
		if ($time == 1 && $cats == 1)
		{
			$relres = $db->queryDirect($query." and adddate > (now() - interval 1 day) and (categoryID like \"2020\" or categoryID like \"3050\" or categoryID like \"6050\" or categoryID like \"5050\" or categoryID like \"7010\" or categoryID like \"8050\")");
		}
		//24 hours, all cats
		if ($time == 1 && $cats == 2)
		{
			$relres = $db->queryDirect($query." and adddate > (now() - interval 1 day)");
		}
		//other cats
		if ($time == 2 && $cats == 1)
		{
			$relres = $db->queryDirect($query." and (categoryID like \"2020\" or categoryID like \"3050\" or categoryID like \"6050\" or categoryID like \"5050\" or categoryID like \"7010\" or categoryID like \"8050\")");
		}
		//all cats
		if ($time == 2 && $cats == 2)
		{
			$relres = $db->queryDirect($query);
		}
		
		while ($relrow = $db->fetchArray($relres))
		{
			$this->checkName($relrow, $echo, $type);
		}
	}
	
	//
	//	Attempts to fix release names using the NFO.
	//
	public function fixNamesWithNfo($time, $echo, $cats)
	{
		$db = new DB();
		$type = "NFO, ";
		$query = "SELECT nfo.releaseID as nfoID, rel.groupID, rel.categoryID, rel.searchname, uncompress(nfo) as textstring, rel.ID as releaseID from releases rel left join releasenfo nfo on (nfo.releaseID = rel.ID) where categoryID != 5070";
		
		//24 hours, other cats
		if ($time == 1 && $cats == 1)
		{
			$relres = $db->queryDirect($query." and rel.adddate > (now() - interval 1 day) and (rel.categoryID like \"2020\" or rel.categoryID like \"3050\" or rel.categoryID like \"6050\" or rel.categoryID like \"5050\" or rel.categoryID like \"7010\" or rel.categoryID like \"8050\") group by rel.ID");
		}
		//24 hours, all cats
		if ($time == 1 && $cats == 2)
		{
			$relres = $db->queryDirect($query." and rel.adddate > (now() - interval 1 day) group by rel.ID");
		}
		//other cats
		if ($time == 2 && $cats == 1)
		{
			$relres = $db->queryDirect($query." and (rel.categoryID like \"2020\" or rel.categoryID like \"3050\" or rel.categoryID like \"6050\" or rel.categoryID like \"5050\" or rel.categoryID like \"7010\" or rel.categoryID like \"8050\") group by rel.ID");
		}
		//all cats
		if ($time == 2 && $cats == 2)
		{
			$relres = $db->queryDirect($query);
		}
		
		while ($relrow = $db->fetchArray($relres))
		{
			$this->checkName($relrow, $echo, $type);
		}
	}
	
	//
	//	Attempts to fix release names using the File name.
	//
	public function fixNamesWithFiles($time, $echo, $cats)
	{
		$db = new DB();
		$type = "Filenames, ";
		$query = "SELECT relfiles.name as textstring, rel.categoryID, rel.searchname, rel.groupID, relfiles.releaseID as fileID, rel.ID as releaseID from releases rel left join releasefiles relfiles on (relfiles.releaseID = rel.ID) where categoryID != 5070";
		
		//24 hours, other cats
		if ($time == 1 && $cats == 1)
		{
			$relres = $db->queryDirect($query." and rel.adddate > (now() - interval 1 day) and (rel.categoryID like \"2020\" or rel.categoryID like \"3050\" or rel.categoryID like \"6050\" or rel.categoryID like \"5050\" or rel.categoryID like \"7010\" or rel.categoryID like \"8050\") group by rel.ID");
		}
		//24 hours, all cats
		if ($time == 1 && $cats == 2)
		{
			$relres = $db->queryDirect($query." and rel.adddate > (now() - interval 1 day) group by rel.ID");
		}
		//other cats
		if ($time == 2 && $cats == 1)
		{
			$relres = $db->queryDirect($query." and (rel.categoryID like \"2020\" or rel.categoryID like \"3050\" or rel.categoryID like \"6050\" or rel.categoryID like \"5050\" or rel.categoryID like \"7010\" or rel.categoryID like \"8050\") group by rel.ID");
		}
		//all cats
		if ($time == 2 && $cats == 2)
		{
			$relres = $db->queryDirect($query);
		}
		
		while ($relrow = $db->fetchArray($relres))
		{
			$this->checkName($relrow, $echo, $type);
		}
	}
	
	//
	//	Update the release with the new information.
	//
	public function updateRelease($release, $name, $method, $echo, $type)
	{
		$n = "\n";
		$namecleaning = new nameCleaning();
		$newname = $namecleaning->fixerCleaner($name);
		
		if ($newname !== $release["searchname"])
		{ 
			$category = new Category();
			$determinedcat = $category->determineCategory($newname, $release["groupID"]);
			if ($echo == 1)
			{
				echo	"New name: ".$newname.$n.
						"Old name: ".$release["searchname"].$n.
						"New cat:  ".$determinedcat.$n.
						"Old cat:  ".$release["categoryID"].$n.
						"Method:   ".$type.$method.$n.$n;
				$db = new DB();
				$db->queryDirect(sprintf("UPDATE releases set searchname = %s where ID = %d", $db->escapeString($newname), $release["releaseID"]));
			}
			if ($echo == 2)
			{
				echo	"New name: ".$newname.$n.
						"Old name: ".$release["searchname"].$n.
						"New cat:  ".$determinedcat.$n.
						"Old cat:  ".$release["categoryID"].$n.
						"Method:   ".$type.$method.$n.$n;
			}
		}
	}
	
	//
	//	Check the array using regex for a clean name.
	//
	public function checkName($release, $echo, $type)
	{                       
		$this->tvCheck($release, $echo, $type);
		$this->movieCheck($release, $echo, $type);
		$this->gameCheck($release, $echo, $type);
		$this->appCheck($release, $echo, $type);
	}
	
	//
	//	Look for a TV name.
	//
	public function tvCheck($release, $echo, $type)
	{
		if (preg_match('/\w[\w.\-\',;& ]+((s\d{1,2}(\.|_|\-| )?(b|d|e)\d{1,2})|\d{1,2}x\d{2}|ep(\.|_|\-| )?\d{2})[\w.\-\',;.\(\)]+(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="tvCheck: Title.SxxExx.Text.source.group", $echo, $type);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((s\d{1,2}(\.|_|\-| )?(b|d|e)\d{1,2})|\d{1,2}x\d{2}|ep(\.|_|\-| )?\d{2})[\w.\-\',;& ]+((19|20)\d\d)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="tvCheck: Title.SxxExx.Text.year.group", $echo, $type);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((s\d{1,2}(\.|_|\-| )?(b|d|e)\d{1,2})|\d{1,2}x\d{2}|ep(\.|_|\-| )?\d{2})[\w.\-\',;& ]+(480|720|1080)(i|p)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="tvCheck: Title.SxxExx.Text.resolution.source.vcodec.group", $echo, $type);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((s\d{1,2}(\.|_|\-| )?(b|d|e)\d{1,2})|\d{1,2}x\d{2}|ep(\.|_|\-| )?\d{2})(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="tvCheck: Title.SxxExx.source.vcodec.group", $echo, $type);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((s\d{1,2}(\.|_|\-| )?(b|d|e)\d{1,2})|\d{1,2}x\d{2}|ep(\.|_|\-| )?\d{2})(\.|_|\-| )(AAC( LC)?|AC-?3|DD5((\.|_|\-| )1)?|(A_)?DTS(-)?(HD)?|Dolby(( )?TrueHD)?|MP3|TrueHD)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(480|720|1080)(i|p)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="tvCheck: Title.SxxExx.acodec.source.res.vcodec.group", $echo, $type);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((s\d{1,2}(\.|_|\-| )?(b|d|e)\d{1,2})|\d{1,2}x\d{2}|ep(\.|_|\-| )?\d{2})[\w.\-\',;& ]+((19|20)\d\d)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="tvCheck: Title.SxxExx.resolution.source.vcodec.group", $echo, $type);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((19|20)\d\d)(\.|_|\-| )((s\d{1,2}(\.|_|\-| )?(b|d|e)\d{1,2})|\d{1,2}x\d{2}|ep(\.|_|\-| )?\d{2})(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="tvCheck: Title.year.###(season/episode).source.group", $echo, $type);
		}
		if (preg_match('/\w(19|20)\d\d(\.|_|\-| )\d{2}(\.|_|\-| )\d{2}(\.|_|\-| )(IndyCar|NBA|NCW(T|Y)S|NNS|NSCS?)((\.|_|\-| )(19|20)\d\d)?[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="tvCheck: Sports", $echo, $type);
		}
	}
	
	//
	//	Look for a movie name.
	//
	public function movieCheck($release, $echo, $type)
	{
		if (preg_match('/\w[\w.\-\',;& ]+((19|20)\d\d)[\w.\-\',;& ]+(480|720|1080)(i|p)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.year.Text.res.vcod.group", $echo, $type);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((19|20)\d\d)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)(\.|_|\-| )(480|720|1080)(i|p)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.year.source.vcodec.res.group", $echo, $type);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((19|20)\d\d)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)(\.|_|\-| )(AAC( LC)?|AC-?3|DD5((\.|_|\-| )1)?|(A_)?DTS(-)?(HD)?|Dolby(( )?TrueHD)?|MP3|TrueHD)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.year.source.vcodec.acodec.group", $echo, $type);
		}
		if (preg_match('/\w[\w.\-\',;& ]+(Brazilian|Chinese|Croatian|Danish|Deutsch|Dutch|Estonian|English|Finnish|Flemish|Francais|French|German|Greek|Hebrew|Icelandic|Italian|Japenese|Japan|Japanese|Korean|Latin|Nordic|Norwegian|Polish|Portuguese|Russian|Serbian|Slovenian|Swedish|Spanisch|Spanish|Thai|Turkish)(\.|_|\-| )(AAC( LC)?|AC-?3|DD5((\.|_|\-| )1)?|(A_)?DTS(-)?(HD)?|Dolby(( )?TrueHD)?|MP3|TrueHD)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.year.language.acodec.source.vcodec.group", $echo, $type);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((19|20)\d\d)(\.|_|\-| )(480|720|1080)(i|p)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(AAC( LC)?|AC-?3|DD5((\.|_|\-| )1)?|(A_)?DTS(-)?(HD)?|Dolby(( )?TrueHD)?|MP3|TrueHD)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.year.resolution.source.acodec.vcodec.group", $echo, $type);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((19|20)\d\d)(\.|_|\-| )(480|720|1080)(i|p)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.year.resolution.source.vcodec.group", $echo, $type);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((19|20)\d\d)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(480|720|1080)(i|p)(\.|_|\-| )(AAC( LC)?|AC-?3|DD5((\.|_|\-| )1)?|(A_)?DTS(-)?(HD)?|Dolby(( )?TrueHD)?|MP3|TrueHD)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.year.source.resolution.acodec.vcodec.group", $echo, $type);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((19|20)\d\d)(\.|_|\-| )(480|720|1080)(i|p)(\.|_|\-| )(AAC( LC)?|AC-?3|DD5((\.|_|\-| )1)?|(A_)?DTS(-)?(HD)?|Dolby(( )?TrueHD)?|MP3|TrueHD)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.year.resolution.acodec.vcodec.group", $echo, $type);
		}
		if (preg_match('/[\w.\-\',;& ]+((19|20)\d\d)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BR(RIP)?|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(480|720|1080)(i|p)(\.|_|\-| )[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.year.source.res.group", $echo, $type);
		}
		if (preg_match('/\w[\w.\-\',;& ]+((19|20)\d\d)(\.|_|\-| )[\w.\-\',;& ]+(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BR(RIP)?|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.year.eptitle.source.vcodec.group", $echo, $type);
		}
		if (preg_match('/\w[\w.\-\',;& ]+(480|720|1080)(i|p)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )(AAC( LC)?|AC-?3|DD5((\.|_|\-| )1)?|(A_)?DTS(-)?(HD)?|Dolby(( )?TrueHD)?|MP3|TrueHD)(\.|_|\-| )(DivX|(H|X)(\.|_|\-| )?264|MPEG2|XviD(HD)?|WMV)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.resolution.source.acodec.vcodec.group", $echo, $type);
		}
		if (preg_match('/\w[\w.\-\',;& ]+(480|720|1080)(i|p)(\.|_|\-| )(AAC( LC)?|AC-?3|DD5((\.|_|\-| )1)?|(A_)?DTS(-)?(HD)?|Dolby(( )?TrueHD)?|MP3|TrueHD)[\w.\-\',;& ]+(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)(\.|_|\-| )((19|20)\d\d)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.resolution.acodec.eptitle.source.year.group", $echo, $type);
		}
		if (preg_match('/\w[\w.\-\',;& ]+(Brazilian|Chinese|Croatian|Danish|Deutsch|Dutch|Estonian|English|Finnish|Flemish|Francais|French|German|Greek|Hebrew|Icelandic|Italian|Japenese|Japan|Japanese|Korean|Latin|Nordic|Norwegian|Polish|Portuguese|Russian|Serbian|Slovenian|Swedish|Spanisch|Spanish|Thai|Turkish)(\.|_|\-| )((19|20)\d\d)(\.|_|\-| )(AAC( LC)?|AC-?3|DD5((\.|_|\-| )1)?|(A_)?DTS(-)?(HD)?|Dolby(( )?TrueHD)?|MP3|TrueHD)(\.|_|\-| )(BD(-?(25|50|RIP))?|Blu(-)?Ray( )?(3D)?|BRRIP|CAM(RIP)?|DBrip|DTV|DVD\-?(5|9|(R(IP)?|scr(eener)?))?|(H|P|S)D?(RIP|TV(RIP)?)?|NTSC|PAL|R5|Ripped |(S)?VCD|scr(eener)?|SAT(RIP)?|TS|VHS(RIP)?|VOD|WEB-DL)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="movieCheck: Title.language.year.acodec.src", $echo, $type);
		}
	}
	
	//
	//	Look for a game name.
	//
	public function gameCheck($release, $echo, $type)
	{
		if (preg_match('/\w[\w.\-\',;& ]+(ASIA|DLC|EUR|GOTY|JPN|KOR|MULTI\d{1}|NTSCU?|PAL|RF|Region(\.|_|-|( ))?Free|USA|XBLA)(\.|_|\-| )(DLC(\.|_|\-| )Complete|FRENCH|GERMAN|MULTI\d{1}|PROPER|PSN|READ(\.|_|-|( ))?NFO|UMD)?(\.|_|\-| )?(GC|NDS|NGC|PS3|PSP|WII|XBOX(360)?)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="gameCheck: Videogames 1", $echo, $type);
		}
		if (preg_match('/\w[\w.\-\',;& ]+(GC|NDS|NGC|PS3|WII|XBOX(360)?)(\.|_|\-| )(DUPLEX|iNSOMNi|OneUp|STRANGE|SWAG|SKY)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="gameCheck: Videogames 2", $echo, $type);
		}
	}
	
	//
	//	Look for a app name.
	//
	public function appCheck($release, $echo, $type)
	{
		if (preg_match('/\w[\w.\-\',;& ]+(\d{1,10}|Linux|UNIX)(\.|_|\-| )(RPM)?(\.|_|\-| )?(X64)?(\.|_|\-| )?(Incl)(\.|_|\-| )(Keygen)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="appCheck: Apps 1", $echo, $type);
		}
		if (preg_match('/\w[\w.\-\',;& ]+(\d){1,8}(\.|_|\-| )(winall-freeware)[\w.\-\',;& ]+\w/i', $release["textstring"], $result))
		{
			$this->updateRelease($release, $result["0"], $methdod="appCheck: Apps 2", $echo, $type);
		}
	}
}
