<?php

require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/groups.php");
require_once(WWW_DIR."/lib/site.php");

class Category
{
	const CAT_GAME_NDS = 1010;
	const CAT_GAME_PSP = 1020;
	const CAT_GAME_WII = 1030;
	const CAT_GAME_XBOX = 1040;
	const CAT_GAME_XBOX360 = 1050;
	const CAT_GAME_WIIWARE = 1060;
	const CAT_GAME_XBOX360DLC = 1070;
	const CAT_GAME_PS3 = 1080;
	const CAT_GAME_OTHER = 1090;
	const CAT_MOVIE_FOREIGN = 2010;
	const CAT_MOVIE_OTHER = 2020;
	const CAT_MOVIE_SD = 2030;
	const CAT_MOVIE_HD = 2040;
	const CAT_MOVIE_3D = 2050;
	const CAT_MOVIE_BLURAY = 2060;
	const CAT_MOVIE_DVD = 2070;
	const CAT_MUSIC_MP3 = 3010;
	const CAT_MUSIC_VIDEO = 3020;
	const CAT_MUSIC_AUDIOBOOK = 3030;
	const CAT_MUSIC_LOSSLESS = 3040;
	const CAT_MUSIC_OTHER = 3050;
	const CAT_PC_0DAY = 4010;
	const CAT_PC_ISO = 4020;
	const CAT_PC_MAC = 4030;
	const CAT_PC_PHONE_OTHER = 4040;
	const CAT_PC_GAMES = 4050;
	const CAT_PC_PHONE_IOS = 4060;
	const CAT_PC_PHONE_ANDROID = 4070;
	const CAT_TV_WEBDL = 5010;
	const CAT_TV_FOREIGN = 5020;
	const CAT_TV_SD = 5030;
	const CAT_TV_HD = 5040;
	const CAT_TV_OTHER = 5050;
	const CAT_TV_SPORT = 5060;
	const CAT_TV_ANIME = 5070;
	const CAT_TV_DOCUMENTARY = 5080;
	const CAT_XXX_DVD = 6010;
	const CAT_XXX_WMV = 6020;
	const CAT_XXX_XVID = 6030;
	const CAT_XXX_X264 = 6040;
	const CAT_XXX_OTHER = 6050;
	const CAT_XXX_IMAGESET = 6060;
	const CAT_XXX_PACKS = 6070;
	const CAT_MISC = 7010;
	const CAT_BOOKS_EBOOK = 8010;
	const CAT_BOOKS_COMICS = 8020;
	const CAT_BOOKS_MAGAZINES = 8030;
	const CAT_BOOKS_TECHNICAL = 8040;
	const CAT_BOOKS_OTHER = 8050;
	
	const CAT_PARENT_GAME = 1000;
	const CAT_PARENT_MOVIE = 2000;
	const CAT_PARENT_MUSIC = 3000;
	const CAT_PARENT_PC = 4000;
	const CAT_PARENT_TV = 5000;
	const CAT_PARENT_XXX = 6000;
	const CAT_PARENT_MISC = 7000;
	const CAT_PARENT_BOOKS = 8000;

	const STATUS_INACTIVE = 0;
	const STATUS_ACTIVE = 1;
	const STATUS_DISABLED = 2;

	private $tmpCat = 0;

	public function category()
	{
		$s = new Sites();
		$site = $s->get();
		$this->categorizeforeign = ($site->categorizeforeign == "0") ? false : true;
	}
	
	public function get($activeonly=false, $excludedcats=array())
	{
		$db = new DB();

		$exccatlist = "";
		if (count($excludedcats) > 0)
			$exccatlist = " and c.ID not in (".implode(",", $excludedcats).")";

		$act = "";
		if ($activeonly)
			$act = sprintf(" where c.status = %d %s ", Category::STATUS_ACTIVE, $exccatlist ) ;

		return $db->query("select c.ID, concat(cp.title, ' > ',c.title) as title, cp.ID as parentID, c.status from category c inner join category cp on cp.ID = c.parentID ".$act." ORDER BY c.ID");
	}

	public function isParent($cid)
	{
		$db = new DB();
		$ret = $db->queryOneRow(sprintf("select * from category where ID = %d and parentID is null", $cid));
		if ($ret)
			return true;
		else
			return false;
	}

	public function getFlat($activeonly=false)
	{
		$db = new DB();
		$act = "";
		if ($activeonly)
			$act = sprintf(" where c.status = %d ", Category::STATUS_ACTIVE ) ;
		return $db->query("select c.*, (SELECT title FROM category WHERE ID=c.parentID) AS parentName from category c ".$act." ORDER BY c.ID");
	}

	public function getChildren($cid)
	{
		$db = new DB();
		return $db->query(sprintf("select c.* from category c where parentID = %d", $cid));
	}
	
	//
	// Returns ID's for site disabled categories. 
	//
	public function getDisabledIDs()
	{
		$db = new DB();
		return $db->queryDirect("SELECT ID from category where status = 2");
	}
	public function getById($id)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT c.disablepreview, c.ID, CONCAT(COALESCE(cp.title,'') , CASE WHEN cp.title IS NULL THEN '' ELSE ' > ' END , c.title) as title, c.status, c.parentID from category c left outer join category cp on cp.ID = c.parentID where c.ID = %d", $id));
	}

	public function getByIds($ids)
	{
		$db = new DB();
		return $db->query(sprintf("SELECT concat(cp.title, ' > ',c.title) as title from category c inner join category cp on cp.ID = c.parentID where c.ID in (%s)", implode(',', $ids)));
	}

	public function update($id, $status, $desc, $disablepreview)
	{
		$db = new DB();
		return $db->query(sprintf("update category set disablepreview = %d, status = %d, description = %s where ID = %d", $disablepreview, $status, $db->escapeString($desc), $id));
	}

	public function getForMenu($excludedcats=array())
	{
		$db = new DB();
		$ret = array();

		$exccatlist = "";
		if (count($excludedcats) > 0)
			$exccatlist = " and ID not in (".implode(",", $excludedcats).")";

		$arr = $db->query(sprintf("select * from category where status = %d %s", Category::STATUS_ACTIVE, $exccatlist));
		foreach ($arr as $a)
			if ($a["parentID"] == "")
				$ret[] = $a;

		foreach ($ret as $key => $parent)
		{
			$subcatlist = array();
			$subcatnames = array();
			foreach ($arr as $a)
			{
				if ($a["parentID"] == $parent["ID"])
				{
					$subcatlist[] = $a;
					$subcatnames[] = $a["title"];
				}
			}

			if (count($subcatlist) > 0)
			{
				array_multisort($subcatnames, SORT_ASC, $subcatlist);
				$ret[$key]["subcatlist"] = $subcatlist;
			}
			else
			{
				unset($ret[$key]);
			}
		}
		return $ret;
	}

	public function getForSelect($blnIncludeNoneSelected = true)
	{
		$categories = $this->get();
		$temp_array = array();

		if ($blnIncludeNoneSelected)
		{
			$temp_array[-1] = "--Please Select--";
		}

		foreach($categories as $category)
			$temp_array[$category["ID"]] = $category["title"];

		return $temp_array;
	}
	
	//
	// Return the category name from the supplied categoryID.
	//
	public function getNameByID($ID)
	{
		$db = new DB();
		$parent = array_shift($db->queryOneRow(sprintf("SELECT title from category where ID = %d", substr($ID, 0, 1)."000")));
		$cat = array_shift($db->queryOneRow(sprintf("SELECT title from category where ID = %d", $ID)));
		return $parent." ".$cat;
	}

	//
	// Work out which category is applicable for either a group or a binary.
	// returns -1 if no category is appropriate from the group name.
	//
	public function determineCategory($releasename = "", $groupID)
	{	 
		//					   
		//Try against all functions, if still nothing, return Cat Misc.
		//
		
		if($this->byGroup($releasename, $groupID)){ return $this->tmpCat; }
		if($this->isPC($releasename)){ return $this->tmpCat; }
		if($this->isTV($releasename)){ return $this->tmpCat; }
		if($this->isMovie($releasename)){ return $this->tmpCat; }
		if($this->isXXX($releasename)){ return $this->tmpCat; }
		if($this->isConsole($releasename)){ return $this->tmpCat; }
		if($this->isMusic($releasename)){ return $this->tmpCat; }
		if($this->isBook($releasename)){ return $this->tmpCat; }
		if($this->isHashed($releasename)){ return $this->tmpCat; }
		
		return Category::CAT_MISC;
	}
	
	//
	// Beginning of functions to determine category by release name
	//
	
	//
	//	Groups
	//
	
	public function byGroup($releasename, $groupID)
	{
		$groups = new Groups();
		
		$groupRes = $groups->getByID($groupID);
		
		if (is_array($groupRes))
		{
			foreach ($groupRes as $groupRows)
			{
				if (preg_match('/alt\.binaries\.0day\.stuffz/', $groupRes["name"]))
				{
					if($this->isEBook($releasename)){ return $this->tmpCat; }
					if($this->isPC($releasename)){ return $this->tmpCat; }
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat = Category::CAT_PC_0DAY;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.audio\.warez/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat = Category::CAT_PC_0DAY;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.(multimedia\.)?anime(\.(highspeed|repost))?/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat = Category::CAT_TV_ANIME;
					return true;
				}
				
				if($this->categorizeforeign)
				{
					if (preg_match('/alt\.binaries\.cartoons\.french/', $groupRes["name"]))
					{
						if($this->isHashed($releasename)){ return $this->tmpCat; }
						$this->tmpCat =  Category::CAT_TV_FOREIGN;
						return true;
					}
				}
				
				if (preg_match('/alt\.binaries\.cd\.image\.linux/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat =  Category::CAT_PC_0DAY;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.cd\.lossless/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat =  Category::CAT_MUSIC_LOSSLESS;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.classic\.tv\.shows/i', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat =  Category::CAT_TV_SD;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.(comics\.dcp|pictures\.comics\.(complete|dcp|repost))/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat =  Category::CAT_BOOKS_COMICS;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.console\.ps3/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat =  Category::CAT_GAME_PS3;
					return true;
				}
				if (preg_match('/alt\.binaries\.cores/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					if($this->isXxx($releasename)){ return $this->tmpCat; }
					return false;
				}
				
				if (preg_match('/alt\.binaries(\.(19\d0s|country|sounds?(\.country|\.19\d0s)?))?\.mp3(\.[a-z]+)?/i', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					if($this->isMusicLossless($releasename)){ return $this->tmpCat; }
					$this->tmpCat =  Category::CAT_MUSIC_MP3;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.dvd(\-?r)?(\.(movies|))?$/i', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat =  Category::CAT_MOVIE_DVD;
					return true;
				}
				
				if($this->categorizeforeign)
				{
					if (preg_match('/alt\.binaries\.(dvdnordic\.org|nordic\.(dvdr?|xvid))|dk\.(binaer|binaries)\.film(\.divx)?/', $groupRes["name"]))
					{
						if($this->isHashed($releasename)){ return $this->tmpCat; }
						$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
						return true;
					}
				}
				
				if (preg_match('/alt\.binaries\.documentaries/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat =  Category::CAT_TV_DOCUMENTARY;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.e\-?books?((\.|\-)(technical|textbooks))/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat =  Category::CAT_BOOKS_TECHNICAL;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.e\-?book(\.[a-z]+)?/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					if($this->isComic($releasename)){ return $this->tmpCat; }
					$this->tmpCat =  Category::CAT_BOOKS_EBOOK;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.((movies|multimedia)\.)?(erotica(\.(amateur|divx))?|ijsklontje)/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					if($this->isXxx($releasename)){ return $this->tmpCat; }
					$this->tmpCat =  Category::CAT_XXX_OTHER;
					return true;
				}
				
				if (preg_match('/alt\.binaries(\.games)?\.nintendo(\.)?ds/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat =  Category::CAT_GAME_NDS;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.games\.wii/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					if($this->isGameWiiWare($releasename)){ return $this->tmpCat; }
					$this->tmpCat =  Category::CAT_GAME_WII;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.games\.xbox$/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat =  Category::CAT_GAME_XBOX;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.games\.xbox360/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					if($this->isGameXBOX360DLC($releasename)){ return $this->tmpCat; }
					$this->tmpCat = Category::CAT_GAME_XBOX360;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.ipod\.videos\.tvshows/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat = Category::CAT_TV_OTHER;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.mac$/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat = Category::CAT_PC_MAC;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.mma$/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					if($this->is0day($releasename)){ return $this->tmpCat; }
					$this->tmpCat = Category::CAT_TV_SPORT;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.moovee/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat = Category::CAT_MOVIE_SD;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.mpeg\.video\.music/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat =  Category::CAT_MUSIC_VIDEO;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.multimedia\.documentaries/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat =  Category::CAT_TV_DOCUMENTARY;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.multimedia\.sports(\.boxing)?/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat =  Category::CAT_TV_SPORT;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.(mp3|sounds?)(\.mp3)?\.audiobook(s|\.repost)?/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat =  Category::CAT_MUSIC_AUDIOBOOK;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.pro\-wrestling/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat = Category::CAT_TV_SPORT;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.sounds\.(flac(\.jazz)|jpop|lossless(\.[a-z0-9]+)?)|alt\.binaries\.(cd\.lossless|music\.flac)/i', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat =  Category::CAT_MUSIC_LOSSLESS;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.sony\.psp/', $groupRes["name"]))
				{
					if($this->isHashed($releasename)){ return $this->tmpCat; }
					$this->tmpCat = Category::CAT_GAME_PSP;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.warez$/', $groupRes["name"]))
				{
					$this->tmpCat = Category::CAT_PC_0DAY;
					return true;
				}
				
				if (preg_match('/alt\.binaries\.warez\.smartphone/', $groupRes["name"]))
				{
					if($this->isPhone($releasename)){ return $this->tmpCat; }
					$this->tmpCat = Category::CAT_PC_PHONE_OTHER;
					return true;
				}
				
				if($this->categorizeforeign)
				{
					if (preg_match('/dk\.binaer\.tv/', $groupRes["name"]))
					{
						if($this->isHashed($releasename)){ return $this->tmpCat; }
						$this->tmpCat = Category::CAT_TV_FOREIGN;
						return true;
					}
				}
				
				return false;
			}
		}
	}
	
	//
	//	TV
	//
	
	public function isTV($releasename, $assumeTV=TRUE)
	{
		$looksLikeTV = preg_match('/[\.\-_ ](\dx\d\d|s\d{1,3}[.-_ ]?(e|d)\d{1,3}|C4TV|Complete[\.\-_ ]Season|DSR|(D|H|P)DTV|SUBPACK|Season[\.\-_ ]\d{1,2}|WEB\-DL|WEBRip)[\.\-_ ]|TV[\.\-_ ](19|20)\d\d|TrollHD/i', $releasename);
		$looksLikeSportTV = preg_match('/[\.\-_ ]((19|20)\d\d[\.\-_ ]\d{1,2}[\.\-_ ]\d{1,2}[\.\-_ ]VHSRip|Indy[\.\-_ ]?Car|(iMPACT|Smoky[\.\-_ ]Mountain|Texas)[\.\-_ ]Wrestling|Moto[\.\-_ ]?GP|NSCS[\.\-_ ]ROUND|NECW[\.\-_ ]TV|(Per|Post)\-Show|PPV|WrestleMania|WCW|WEB[\.\-_ ]HD|WWE[\.\-_ ](Monday|NXT|RAW|Smackdown|Superstars|WrestleMania))[\.\-_ ]/i', $releasename);
		
		if ($looksLikeTV && !preg_match('/[\.\-_ ](flac|imageset|mp3|xxx)[\.\-_ ]/i', $releasename))
		{
			if($this->isOtherTV($releasename)){ return true; }
			if($this->categorizeforeign)
			{
				if($this->isForeignTV($releasename)){ return true; }
			}
			if($this->isSportTV($releasename)){ return true; }
			if($this->isDocumentaryTV($releasename)){ return true; }
			if($this->isWEBDL($releasename)){ return true; }
			if($this->isHDTV($releasename)){ return true; }
			if($this->isSDTV($releasename)){ return true; }
			if($this->isAnimeTV($releasename)){ return true; }
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
		
		if ($looksLikeSportTV)
		{
			if($this->isSportTV($releasename)){ return true; }
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}

		return false;
	}
	
	public function isOtherTV($releasename)
	{
		if(preg_match('/[\.\-_ ]SUBPACK[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
	}
	
	public function isForeignTV($releasename)
	{
		if (!preg_match('/[\.\-_ ]NHL[\.\-_ ]/', $releasename))
		{
			if(preg_match('/[\.\-_ ](chinese|dk|fin|french|ger|heb|ita|jap|kor|nor|nordic|nl|pl|swe)[\.\-_ ]?(sub|dub)(ed|bed|s)?[\.\-_ ]|<German>/i', $releasename))
			{
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}
		
			if(preg_match('/[\.\-_ ](brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish).+(Divx|DOKU|DUB(BED)?|DLMUX|NOVARIP|RealCo|Sub(bed|s)?|Web[\.\-_ ]?Rip|WS|Xvid)[\.\-_ ]/i', $releasename))
			{
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}
		
			if(preg_match('/[\.\-_ ](Divx|DOKU|DUB(BED)?|DLMUX|NOVARIP|RealCo|Sub(bed|s)?|Web[\.\-_ ]?Rip|WS|Xvid).+(brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish)[\.\-_ ]/i', $releasename))
			{
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}
		
			if(preg_match('/(S\d\dE\d\d|DOCU(MENTAIRE)?|TV)?[\.\-_ ](FRENCH|German|Dutch)[\.\-_ ](720p|1080p|dv(b|d)r(ip)?|LD|HD\-?TV|TV[\.\-_ ]?RIP|x264)[\.\-_ ]/i', $releasename))
			{
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}
		
			if(preg_match('/[\.\-_ ]FastSUB|NL|nlvlaams|patrfa|Seizoen|slosinh|Videomann|Vostfr|xslidian[\.\-_ ]|x264\-iZU/i', $releasename))
			{
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}
		}
		return false;
	}

	public function isSportTV($releasename)
	{
		if(preg_match('/s\d{1,2}[.-_ ]?e\d{1,2}/i', $releasename))
		{
			return false;
		}
		
		if(preg_match('/[\.\-_ ]?(Bellator|bundesliga|EPL|ESPN|FIA|la[\.\-_ ]liga|MMA|motogp|NFL|NCAA|PGA|red[\.\-_ ]bull.+race|Sengoku|Strikeforce|supercup|uefa|UFC|wtcc|WWE)[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_TV_SPORT;
			return true;
		}
		
		if(preg_match('/[\.\-_ ]?(DTM|FIFA|formula[\.\-_ ]1|indycar|Rugby|NASCAR|NBA|NHL|NRL|netball[\.\-_ ]anz|ROH|SBK|Superleague|The[\.\-_ ]Ultimate[\.\-_ ]Fighter|TNA|V8[\.\-_ ]Supercars|WBA|WrestleMania)[\.\-_ ]/i', $releasename))
		{	
			$this->tmpCat = Category::CAT_TV_SPORT;
			return true;
		}
		
		if(preg_match('/[\.\-_ ]?(AFL|Grand Prix|Indy[\.\-_ ]Car|(iMPACT|Smoky[\.\-_ ]Mountain|Texas)[\.\-_ ]Wrestling|Moto[\.\-_ ]?GP|NSCS[\.\-_ ]ROUND|NECW|Poker|PWX|Rugby|WCW)[\.\-_ ]/i', $releasename))
		{	
			$this->tmpCat = Category::CAT_TV_SPORT;
			return true;
		}
		
		if(preg_match('/[\.\-_ ]?(Horse)[\.\-_ ]Racing[\.\-_ ]/i', $releasename))
		{	
			$this->tmpCat = Category::CAT_TV_SPORT;
			return true;
		}
		
		return false;
	}
	
	public function isDocumentaryTV($releasename)
	{
		if (preg_match('/[\.\-_ ](Docu|Documentary)[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
			return true;
		}
		
		return false;
	}
	
	public function isWEBDL($releasename)
	{
		if (preg_match('/web[\.\-_ ]dl/i', $releasename))
		{
			$this->tmpCat = Category::CAT_TV_WEBDL;
			return true;
		}
		
		return false;
	}
	
	public function isHDTV($releasename)
	{
		if (preg_match('/1080(i|p)|720p/i', $releasename))
		{
			$this->tmpCat = Category::CAT_TV_HD;
			return true;
		}
		
		return false;
	}

	public function isSDTV($releasename)
	{
		if (preg_match('/(360|480|576)p|Complete[\.\-_ ]Season|dvdr|dvd5|dvd9|SD[\.\-_ ]TV|TVRip|xvid/i', $releasename))
		{
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}
		
		if (preg_match('/((H|P)D[\.\-_ ]?TV|DSR|WebRip)[\.\-_ ]x264/i', $releasename))
		{
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}
		
		if (preg_match('/s\d{1,2}[.-_ ]?e\d{1,2}|\s\d{3,4}\s/i', $releasename))
		{
			if (preg_match('/(H|P)D[\.\-_ ]?TV|BDRip[\.\-_ ]x264/i', $releasename))
			{
				$this->tmpCat = Category::CAT_TV_SD;
				return true;
			}
		}
		
		return false;
	}
	
	public function isAnimeTV($releasename)
	{
		if (preg_match('/[\.\-_ ]Anime[\.\-_ ]|^\(\[AST\]\s|\[HorribleSubs\]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_TV_ANIME;
			return true;
		}
		
		return false;
	}

	//
	//  Movie
	//
	
	public function isMovie($releasename)
	{
		if(preg_match('/(B|H)(D|R)RIP|Bluray|BD[\.\-_ ]?(25|50)?|BR|Camrip|DIVX|[\.\-_ ]DVD[\.\-_ ]|DVD-?(5|9|R|Rip)?|Untouched|VHSRip|XVID|[\.\-_ ](DTS|TVrip)[\.\-_ ]/i', $releasename) && !preg_match('/[\.\-_ ]XXX[\.\-_ ]|\wXXX(1080p|720p|DVD)/i', $releasename))
		{
			if($this->categorizeforeign)
			{
				if($this->isMovieForeign($releasename)){ return true; }
			}
			if($this->isMovieDVD($releasename)){ return true; }
			if($this->isMovieSD($releasename)){ return true; }
			if($this->isMovie3D($releasename)){ return true; }
			if($this->isMovieBluRay($releasename)){ return true; }
			if($this->isMovieHD($releasename)){ return true; }
		}
		
		return false;
	}

	public function isMovieForeign($releasename)
	{
		if(preg_match('/(danish|flemish|Deutsch|dutch|french|german|nl\.?subbed|nl\.?sub|\.NL|norwegian|swedish|swesub|spanish|Staffel)[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}
		
		if(preg_match('/Castellano/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}
		
		if(preg_match('/(AC3|DIVX|DVD(5|9|RIP|R)|XVID)[\.\-_ ](Dutch|French|German|ITA)|(Dutch|French|German|ITA)[\.\-_ ](AC3|DIVX|DVD(5|9|RIP|R)|XVID)/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}
		
		return false;
	}
	
	public function isMovieDVD($releasename)
	{
		if(preg_match('/(dvd\-?r|[\.\-_ ]dvd|dvd9|dvd5|[\.\-_ ]r5)[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MOVIE_SD;
			return true;
		}
		
		return false;
	}
	
	public function isMovieSD($releasename)
	{
		if(preg_match('/(dvdr|dvd9|dvd5|divx|dvdscr|extrascene|dvdrip|r5|\.CAM|vhsrip|xvid)[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MOVIE_SD;
			return true;
		}
		
		return false;
	}
	
	public function isMovie3D($releasename)
	{
		if(preg_match('/[\.\-_ ]3D\s?[\.\-_\[ ](1080p|(19|20)\d\d|AVC|BD(25|50)|Blu[\.\-_ ]?ray|CEE|Complete|GER|MVC|MULTi|SBS)[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MOVIE_3D;
			return true;
		}
		
		return false;
	}
	
	public function isMovieBluRay($releasename)
	{
		if(preg_match('/bluray\-|bd?[\.\-_ ]?(25|50)|blu-ray|Bluray\s\-\sUntouched|[\.\-_ ]untouched[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MOVIE_BLURAY;
			return true;
		}
		
		return false;
	}
	
	public function isMovieHD($releasename)
	{
		if(preg_match('/720p|1080p|AVC|VC1|VC\-1|web\-dl|wmvhd|x264|XvidHD/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MOVIE_HD;
			return true;
		}
		
		return false;
	}

	//
	//  PC
	//
	
	public function isPC($releasename)
	{
		if(!preg_match('/PDTV|x264|[\.\-_ ]XXX[\.\-_ ]|Imageset/i', $releasename))
		{
			if($this->isPhone($releasename)){ return true; }
			if($this->isMac($releasename)){ return true; }
			if($this->is0day($releasename)){ return true; }
			if($this->isPCGame($releasename)){ return true; }
		}
		
		return false;
	}

	public function isPhone($releasename)
	{
		if (preg_match('/[\.\-_ ]?(IPHONE|ITOUCH|IPAD)[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_PC_PHONE_IOS;
			return true;
		}
		
		if (preg_match('/[\.\-_ ]?(ANDROID)[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_PC_PHONE_ANDROID;
			return true;
		}
		
		if (preg_match('/[\.\-_ ]?(symbian|xscale|wm5|wm6)[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_PC_PHONE_OTHER;
			return true;
		}
		
		return false;
	}

	public function is0day($releasename)
	{
		if (preg_match('/[\.\-_ ](utorrent|Virtualbox)[\.\-_ ]|incl.+crack/i', $releasename))
		{
			$this->tmpCat = Category::CAT_PC_0DAY;
			return true;
		}
		
		if(preg_match('/[\.\-_ ](32bit|64bit|x32|x64|x86|i\d86|win64|winnt|win9x|win2k|winxp|winnt2k2003serv|win9xnt|win9xme|winnt2kxp|win2kxp|win2kxp2k3|keygen|regged|keymaker|winall|win32|template|Patch|GAMEGUiDE|unix|irix|solaris|freebsd|hpux|linux|windows|multilingual|software|Pro v\d{1,3})[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_PC_0DAY;
			return true;
		}
		
		if (preg_match('/Adobe|\-BEAN|Cracked|CYGNUS|\.deb|DIGERATI|FOSI|Keyfilemaker|Keymaker|Keygen|Lynda\.com|lz0|MULTiLANGUAGE|MultiOS|\-iNViSiBLE|\-SPYRAL|\-SUNiSO|\-UNION|\-TE|v\d{1,3}.*?Pro|v\d{1,3}.*?|WinAll|\(x(64|86)\)/i', $releasename))
		{
			$this->tmpCat = Category::CAT_PC_0DAY;
			return true;
		}
		return false;
	}

	public function isMac($releasename)
	{
		if(preg_match('/mac(\.|\s)?osx/i', $releasename))
		{
			$this->tmpCat = Category::CAT_PC_MAC;
			return true;
		}
		return false;
	}

	public function isPCGame($releasename)
	{
		if (preg_match('/FASDOX|games|PC GAME|RIP\-unleashed|Razor1911/i', $releasename) && !preg_match('/[\.\-_ ]PSP|WII|XBOX/i', $releasename))
		{
			$this->tmpCat = Category::CAT_PC_GAMES;
			return true;
		}
		
		if (preg_match('/[\.\-_ ](0x0007|ALiAS|BACKLASH|BAT|CPY|FASiSO|FLT([\.\-_ ]|COGENT)|GENESIS|HI2U|JAGUAR|MAZE|MONEY|OUTLAWS|PPTCLASSiCS|PROPHET|RAiN|RELOADED|RiTUELYPOGEiOS|SKIDROW|TiNYiSO)/i', $releasename))
		{
			$this->tmpCat = Category::CAT_PC_GAMES;
			return true;
		}
		return false;
	}

	//
	//   XXX
	//
	
	public function isXxx($releasename)
	{
		if(preg_match('/[\.\-_ ](XXX|PORNOLATiON)/', $releasename))
		{
			if($this->isXxx264($releasename)){ return true; }
			if($this->isXxxXvid($releasename)){ return true; }
			if($this->isXxxImageset($releasename)){ return true; }
			if($this->isXxxWMV($releasename)){ return true; }
			if($this->isXxxDVD($releasename)){ return true; }
			if($this->isXxxOther($releasename)){ return true; }
			$this->tmpCat = Category::CAT_XXX_OTHER;
			return true;
		}
		
		else if(preg_match('/a\.b\.erotica|Imageset|Lesbian|Squirt|Transsexual/i', $releasename))
		{
			if($this->isXxx264($releasename)){ return true; }
			if($this->isXxxXvid($releasename)){ return true; }
			if($this->isXxxImageset($releasename)){ return true; }
			if($this->isXxxWMV($releasename)){ return true; }
			if($this->isXxxDVD($releasename)){ return true; }
			if($this->isXxxOther($releasename)){ return true; }
			$this->tmpCat = Category::CAT_XXX_OTHER;
			return true;
		}
		return false;
	}
	
	public function isXxx264($releasename)
	{
		if (preg_match('/720p|1080p|x264/i', $releasename) && !preg_match('/wmv/i', $releasename))
		{
			$this->tmpCat = Category::CAT_XXX_X264;
			return true;
		}
		return false;
	}
	
	public function isXxxWMV($releasename)
	{
		if (preg_match('/(\d{2}\.\d{2}\.\d{2})|(e\d{2,})|f4v|flv|isom|(issue\.\d{2,})|mov|mp4|mpeg|multiformat|pack\-|realmedia|uhq|wmv/i', $releasename))
		{
			$this->tmpCat = Category::CAT_XXX_WMV;
			return true;
		}
		
		return false;
	}

	public function isXxxXvid($releasename)
	{
		if (preg_match('/dvdrip|bdrip|brrip|detoxication|divx|nympho|pornolation|swe6|tesoro|xvid/i', $releasename))
		{
			$this->tmpCat = Category::CAT_XXX_XVID;
			return true;
		}
		
		return false;
	}

	public function isXxxDVD($releasename)
	{
		if (preg_match('/dvdr[^ip]|dvd5|dvd9/i', $releasename))
		{
			$this->tmpCat = Category::CAT_XXX_DVD;
			return true;
		}
		
		return false;
	}
	public function isXxxImageset($releasename)
	{
		if (preg_match('/IMAGESET/i', $releasename))
		{
			$this->tmpCat = Category::CAT_XXX_IMAGESET;
			return true;
		}
		
		return false;
	}
	public function isXxxOther($releasename)
	{
		// If nothing else matches, then try these words.
		if (preg_match('/[\.\-_ ]Brazzers|North\.Pole|She[\.\-_ ]?Male|Transsexual/i', $releasename))
		{
			$this->tmpCat = Category::CAT_XXX_OTHER;
			return true;
		}
		
		return false;
	}

	//
	//  Console
	//
	
	public function isConsole($releasename)
	{
		if($this->isGameNDS($releasename)){return true;}
		if($this->isGamePS3($releasename)){ return true; }
		if($this->isGamePSP($releasename)){ return true; }
		if($this->isGameWiiWare($releasename)){ return true; }
		if($this->isGameWii($releasename)){ return true; }
		if($this->isGameXBOX360DLC($releasename)){ return true; }
		if($this->isGameXBOX360($releasename)){ return true; }
		if($this->isGameXBOX($releasename)){ return true; }
		
		return false;
	}

	public function isGameNDS($releasename)
	{
		if (preg_match('/NDS|[\. ]nds|nintendo.+3ds/', $releasename))
		{
			if(preg_match('/\((DE|DSi(\sEnhanched)?|EUR?|FR|GAME|HOL|JP|NL|NTSC|PAL|KS|USA?)\)/i', $releasename))
			{
				$this->tmpCat = Category::CAT_GAME_NDS;
				return true;
			}
		}
		return false;
	}

	public function isGamePS3($releasename)
	{
		if (preg_match('/PS3/i', $releasename))
		{
			if (preg_match('/ANTiDOTE|DLC|DUPLEX|EUR?|Googlecus|GOTY|\-HR|iNSOMNi|JPN|KONDIOS|\[PS3\]|PSN/i', $releasename))
			{
				$this->tmpCat = Category::CAT_GAME_PS3;
				return true;
			}
			if (preg_match('/AGENCY|APATHY|Caravan|MULTi|NRP|NTSC|PAL|SPLiT|STRiKE|USA?|ZRY/i', $releasename))
			{
				$this->tmpCat = Category::CAT_GAME_PS3;
				return true;
			}
		}
		return false;
	}

	public function isGamePSP($releasename)
	{
		if (preg_match('/PSP/i', $releasename))
		{
			if (preg_match('/[\.\-_ ](BAHAMUT|Caravan|EBOOT|EMiNENT|EUR?|EvoX|GAME|GHS|Googlecus|HandHeld|\-HR|JAP|JPN|KLOTEKLAPPERS|KOR|NTSC|PAL)/i', $releasename))
			{
				$this->tmpCat = Category::CAT_GAME_PSP;
				return true;
			}
			if (preg_match('/[\.\-_ ](Dynarox|HAZARD|ITALIAN|KLB|KuDoS|LIGHTFORCE|MiRiBS|POPSTATiON|(PLAY)?ASiA|PSN|SPANiSH|SUXXORS|UMD(RIP)?|USA?|YARR)/i', $releasename))
			{
				$this->tmpCat = Category::CAT_GAME_PSP;
				return true;
			}
		}
	}

	public function isGameWiiWare($releasename)
	{
		if (preg_match('/(Console|DLC|VC).+[\.\-_ ]WII|(Console|DLC|VC)[\.\-_ ]WII|WII[\.\-_ ].+(Console|DLC|VC)|WII[\.\-_ ](Console|DLC|VC)|WIIWARE/i', $releasename))
		{
			$this->tmpCat = Category::CAT_GAME_WIIWARE;
			return true;
		}
		return false;
	}

	public function isGameWii($releasename)
	{
		if (preg_match('/WII/i', $releasename))
		{
			if (preg_match('/[\.\-_ ](Allstars|BiOSHOCK|dumpTruck|DNi|iCON|JAP|NTSC|PAL|ProCiSiON|PROPER|RANT|REV0|SUNSHiNE|SUSHi|TMD|USA?)/i', $releasename))
			{
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}
			if (preg_match('/[\.\-_ ](APATHY|BAHAMUT|DMZ|ERD|GAME|JPN|LoCAL|MULTi|NAGGERS|OneUp|PLAYME|PONS|Scrubbed|VORTEX|ZARD|ZER0)/i', $releasename))
			{
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}
			if (preg_match('/[\.\-_ ](ALMoST|AMBITION|Caravan|CLiiCHE|DRYB|HaZMaT|KOR|LOADER|MARVEL|PROMiNENT|LaKiTu|LOCAL|QwiiF|RANT)/i', $releasename))
			{
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}
		}
		return false;
	}

	public function isGameXBOX360DLC($releasename)
	{
		if (preg_match('/(DLC.*?xbox360|xbox360.*?DLC|XBLA.*?xbox360|xbox360.*?XBLA)/i', $releasename))
		{
			$this->tmpCat = Category::CAT_GAME_XBOX360DLC;
			return true;
		}
		return false;
	}

	public function isGameXBOX360($releasename)
	{
		if (preg_match('/XBOX360/i', $releasename))
		{
			$this->tmpCat = Category::CAT_GAME_XBOX360;
			return true;
		}
		if (preg_match('/x360/i', $releasename))
		{
			if (preg_match('/Allstars|ASiA|CCCLX|COMPLEX|DAGGER|GLoBAL|iMARS|JAP|JPN|MULTi|NTSC|PAL|REPACK|RRoD|RF|SWAG|USA?/i', $releasename))
			{
				$this->tmpCat = Category::CAT_GAME_XBOX360;
				return true;
			}
			if (preg_match('/DAMNATION|GERMAN|GOTY|iNT|iTA|JTAG|KINECT|MARVEL|MUX360|RANT|SPARE|SPANISH|VATOS|XGD/i', $releasename))
			{
				$this->tmpCat = Category::CAT_GAME_XBOX360;
				return true;
			}
		}
		return false;
	}

	public function isGameXBOX($releasename)
	{
		if (preg_match('/XBOX/i', $releasename))
		{
			$this->tmpCat = Category::CAT_GAME_XBOX;
			return true;
		}
		return false;
	}

	//
	// Music
	//
	
	public function isMusic($releasename)
	{
		if($this->isMusicVideo($releasename)){ return true; }
		if($this->isMusicLossless($releasename)){ return true; }
		if($this->isMusicMP3($releasename)){ return true; }
		if($this->isMusicOther($releasename)){ return true; }
		
		return false;
	}

	public function isMusicVideo($releasename)
	{
		if (preg_match('/(720P|x264)\-(19|20)\d\d\-[a-z0-9]{1,12}/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MUSIC_VIDEO;
			return true;
		}
		if (preg_match('/[a-z0-9]{1,12}\-(19|20)\d\d\-(720P|x264)/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MUSIC_VIDEO;
			return true;
		}
		
		return false;
	}
	
	public function isMusicLossless($releasename)
	{
		if (preg_match('/\[(19|20)\d\d\][\.\-_ ]\[FLAC\]|(\(|\[)flac(\)|\])|FLAC\-(19|20)\d\d\-[a-z0-9]{1,12}|\.flac"|(19|20)\d\d\sFLAC/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
			return true;
		}
		
		return false;
	}
	
	public function isMusicMP3($releasename)
	{
		if (preg_match('/[a-z0-9]{1,12}\-(19|20)\d\d\-[a-z0-9]{1,12}|[\.\-\(\[_ ]\d{2,3}k[\.\-\)\]_ ]|\((192|256|320)\)|(320|cd|eac|vbr).+mp3|(cd|eac|mp3|vbr).+320|FIH\_INT|\s\dCDs|[\.\-_ ]MP3[\.\-_ ]|MP3\-\d{3}kbps|\.(m3u|mp3)"|NMR\s\d{2,3}\skbps|\(320\)\.|\-\((Bootleg|Promo)\)|\.mp3$|\-\sMP3\s(19|20)\d\d|\(vbr\)|rip(192|256|320)|[\.\-_ ]WEB.+(19|20)\d\d/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MUSIC_MP3;
			return true;
		}
		if (preg_match('/\-(19|20)\d\d\-(C4|MTD)(\s|\.)|\-web\-(19|20)\d\d(\.|\s)/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MUSIC_MP3;
			return true;
		}
		return false;
	}
	
	public function isMusicOther($releasename)
	{
		if (preg_match('/\(\d\-?CD\)|\-\dcd\-|\d[\.\-_ ]Albums|Albums.+(EP)|Bonus.+Tracks|Box.+?CD.+SET|Discography|D\.O\.M|Greatest\sSongs|Live.+(Bootleg|Remastered)|Music.+Vol|(\(|\[|\s)NMR(\)|\]|\s)|Promo.+CD|Reggaeton|Tiesto.+Club|Vinyl\s2496|^\(VA\s|\WV\.A\./i', $releasename))
		{
			$this->tmpCat = Category::CAT_MUSIC_OTHER;
			return true;
		}
		return false;
	}
	
	//
	// Books
	//
	
	public function isBook($releasename)
	{
		if($this->isEBook($releasename)){ return true; }
		if($this->isComic($releasename)){ return true; }
		if($this->isMagazine($releasename)){ return true; }
		
		return false;
	}
	
	public function isEBook($releasename)
	{
		if (preg_match('/[\.\-_ ](Ebook|E?\-book|\) WW|Publishing|\[Springer\]|Service\s?Manual)|(\(|\[)(epub|html|mobi|pdf|rtf|tif|txt)(\)|\])|\.(doc|epub|mobi|pdf)(?![\w .])/i', $releasename))
		{
			$this->tmpCat = Category::CAT_BOOKS_EBOOK;
			return true;
		}

		return false;
	}
	
	public function isComic($releasename)
	{
		if (preg_match('/\.(cbr|cbz)|\(c2c\)|comix|comic.+book/i', $releasename))
		{
			$this->tmpCat = Category::CAT_BOOKS_COMICS;
			return true;
		}

		return false;
	}
	
	public function isMagazine($releasename)
	{
		if (preg_match('/[\.\-_ ](Magazine)[\.\-_ ]/i', $releasename))
		{
			$this->tmpCat = Category::CAT_BOOKS_MAGAZINES;
			return true;
		}

		return false;
	}
	
	//
	// Hashed - all hashed go in other misc.
	//
	
	public function isHashed($releasename)
	{
		if (preg_match('/[a-z0-9]{25,}/i', $releasename))
		{
			$this->tmpCat = Category::CAT_MISC;
			return true;
		}
		
		if (preg_match('/[A-Z0-9]{20,}/', $releasename))
		{
			$this->tmpCat = Category::CAT_MISC;
			return true;
		}

		return false;
	}
}
?>
