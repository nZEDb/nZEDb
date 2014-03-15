<?php

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
	const CAT_MUSIC_FOREIGN = 3060;
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
	const CAT_BOOKS_FOREIGN = 8060;
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

	public function __construct()
	{
		$s = new Sites();
		$site = $s->get();
		$this->categorizeforeign = ($site->categorizeforeign == "0") ? false : true;
		$this->catlanguage = (!empty($site->catlanguage)) ? $site->catlanguage : "0";
		$this->catwebdl = ($site->catwebdl == "0") ? false : true;
		$this->db = new DB();
	}

	public function get($activeonly = false, $excludedcats = array())
	{
		$db = $this->db;

		$exccatlist = "";
		if (count($excludedcats) > 0) {
			$exccatlist = " AND c.id NOT IN (" . implode(",", $excludedcats) . ")";
		}

		$act = "";
		if ($activeonly) {
			$act = sprintf(" WHERE c.status = %d %s ", Category::STATUS_ACTIVE, $exccatlist);
		}

		return $db->query("SELECT c.id, CONCAT(cp.title, ' > ',c.title) AS title, cp.id AS parentid, c.status, c.minsize FROM category c INNER JOIN category cp ON cp.id = c.parentid " . $act . " ORDER BY c.id");
	}

	public function isParent($cid)
	{
		$db = $this->db;
		$ret = $db->queryOneRow(sprintf("SELECT * FROM category WHERE id = %d AND parentid IS NULL", $cid));
		if ($ret) {
			return true;
		} else {
			return false;
		}
	}

	public function getFlat($activeonly = false)
	{
		$db = $this->db;
		$act = "";
		if ($activeonly) {
			$act = sprintf(" WHERE c.status = %d ", Category::STATUS_ACTIVE);
		}
		return $db->query("SELECT c.*, (SELECT title FROM category WHERE id=c.parentid) AS parentName FROM category c " . $act . " ORDER BY c.id");
	}

	public function getChildren($cid)
	{
		$db = $this->db;
		return $db->query(sprintf("SELECT c.* FROM category c WHERE parentid = %d", $cid));
	}

	/**
	 * Get names of enabled parent categories.
	 * @return array
	 */
	public function getEnabledParentNames()
	{
		return $this->db->query("SELECT title FROM category WHERE parentid IS NULL AND status = 1");
	}

	// Returns ID's for site disabled categories.
	public function getDisabledIDs()
	{
		$db = $this->db;
		return $db->query("SELECT id FROM category WHERE status = 2");
	}

	public function getById($id)
	{
		$db = $this->db;
		return $db->queryOneRow(sprintf("SELECT c.disablepreview, c.id, CONCAT(COALESCE(cp.title,'') , CASE WHEN cp.title IS NULL THEN '' ELSE ' > ' END , c.title) as title, c.status, c.parentID, c.minsize FROM category c LEFT OUTER JOIN category cp ON cp.id = c.parentid WHERE c.id = %d", $id));
	}

	public function getByIds($ids)
	{
		$db = $this->db;
		if (count($ids) > 0) {
			return $db->query(sprintf("SELECT CONCAT(cp.title, ' > ',c.title) AS title FROM category c INNER JOIN category cp ON cp.id = c.parentid WHERE c.id IN (%s)", implode(',', $ids)));
		} else {
			return false;
		}
	}

	public function update($id, $status, $desc, $disablepreview, $minsize)
	{
		$db = $this->db;
		return $db->queryExec(sprintf("UPDATE category SET disablepreview = %d, status = %d, description = %s, minsize = %d  WHERE id = %d", $disablepreview, $status, $db->escapeString($desc), $minsize, $id));
	}

	public function getForMenu($excludedcats = array())
	{
		$db = $this->db;
		$ret = array();

		$exccatlist = '';
		if (count($excludedcats) > 0) {
			$exccatlist = ' AND id NOT IN (' . implode(',', $excludedcats) . ')';
		}

		$arr = $db->query(sprintf('SELECT * FROM category WHERE status = %d %s', Category::STATUS_ACTIVE, $exccatlist));
		foreach ($arr as $a) {
			if ($a['parentid'] == '') {
				$ret[] = $a;
			}
		}

		foreach ($ret as $key => $parent) {
			$subcatlist = array();
			$subcatnames = array();
			foreach ($arr as $a) {
				if ($a['parentid'] == $parent['id']) {
					$subcatlist[] = $a;
					$subcatnames[] = $a['title'];
				}
			}

			if (count($subcatlist) > 0) {
				array_multisort($subcatnames, SORT_ASC, $subcatlist);
				$ret[$key]['subcatlist'] = $subcatlist;
			} else {
				unset($ret[$key]);
			}
		}
		return $ret;
	}

	public function getForSelect($blnIncludeNoneSelected = true)
	{
		$categories = $this->get();
		$temp_array = array();

		if ($blnIncludeNoneSelected) {
			$temp_array[-1] = "--Please Select--";
		}

		foreach ($categories as $category) {
			$temp_array[$category["id"]] = $category["title"];
		}

		return $temp_array;
	}

	// Return the category name from the supplied categoryID.
	public function getNameByID($ID)
	{
		$db = $this->db;
		$parent = $db->queryOneRow(sprintf("SELECT title FROM category WHERE id = %d", substr($ID, 0, 1) . "000"));
		$cat = $db->queryOneRow(sprintf("SELECT title FROM category WHERE id = %d", $ID));
		return $parent["title"] . " " . $cat["title"];
	}

	// Looks up the site to see which language of categorizer to use.
	public function determineCategory($releasename = "", $groupID)
	{
		/*
		 * 0 = English
		 * 2 = Danish
		 * 3 = French
		 * 1 = German
		 */

		if ($this->catlanguage == "0") {
			if ($this->determineCategoryNormal($releasename, $groupID)) {
				return $this->tmpCat;
			}
			return Category::CAT_MISC;
		} else if ($this->catlanguage == "1") {
			$cg = new CategoryGerman();
			if ($newcat = $cg->determineCategory($releasename, $groupID)) {
				return $newcat;
			} else {
				return Category::CAT_MISC;
			}
		} else if ($this->catlanguage == "2") {
			$cd = new CategoryDanish();
			if ($newcat = $cd->determineCategory($releasename, $groupID)) {
				return $newcat;
			} else {
				return Category::CAT_MISC;
			}
		} else if ($this->catlanguage == "3") {
			$cf = new CategoryFrench();
			if ($newcat = $cf->determineCategory($releasename, $groupID)) {
				return $newcat;
			} else {
				return Category::CAT_MISC;
			}
		}
	}

	// Work out which category is applicable for either a group or a binary.
	// returns -1 if no category is appropriate from the group name.
	public function determineCategoryNormal($releasename = "", $groupID)
	{
		// Note that in byGroup() some overrides occur...
		if ($this->byGroup($releasename, $groupID)) {
			return $this->tmpCat;
		}
		if ($this->isPC($releasename)) {
			return $this->tmpCat;
		}
		if ($this->isXXX($releasename)) {
			return $this->tmpCat;
		}
		if ($this->isTV($releasename)) {
			return $this->tmpCat;
		}
		if ($this->isMusic($releasename)) {
			return $this->tmpCat;
		}
		if ($this->isMovie($releasename)) {
			return $this->tmpCat;
		}
		if ($this->isConsole($releasename)) {
			return $this->tmpCat;
		}
		if ($this->isBook($releasename)) {
			return $this->tmpCat;
		}
		//Try against all functions, if still nothing, return Cat Misc.
		if ($this->isMisc($releasename)) {
			return $this->tmpCat;
		}
	}

	//	Groups.
	public function byGroup($releasename, $groupID)
	{
		$groups = new Groups();
		$groupRes = $groups->getByID($groupID);
		if (is_array($groupRes)) {
			if (preg_match('/alt\.binaries\.0day\.stuffz/', $groupRes["name"])) {
				if ($this->isBook($releasename)) {
					return $this->tmpCat;
				}
				if ($this->isPC($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.audio\.warez/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.(multimedia\.erotica\.|cartoons\.french\.|dvd\.|multimedia\.)?anime(\.highspeed|\.repost|s-fansub|\.german)?/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_ANIME;
				return true;
			}

			if ($this->categorizeforeign) {
				if (preg_match('/alt\.binaries\.cartoons\.french/', $groupRes["name"])) {
					$this->tmpCat = Category::CAT_TV_FOREIGN;
					return true;
				}
			}

			if (preg_match('/alt\.binaries\.cd\.image\.linux/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.cd\.lossless/', $groupRes["name"])) {
				if ($this->categorizeforeign) {
					if ($this->isMusicForeign($releasename)) {
						return $this->tmpCat;
					}
					$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}

			if (preg_match('/alt\.binaries\.classic\.tv\.shows/i', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_SD;
				return true;
			}

			if (preg_match('/alt\.binaries\.(comics\.dcp|pictures\.comics\.(complete|dcp|reposts?))/', $groupRes["name"])) {
				if ($this->categorizeforeign) {
					if ($this->isBookForeign($releasename)) {
						return $this->tmpCat;
					}
					$this->tmpCat = Category::CAT_BOOKS_COMICS;
					return true;
				}
				$this->tmpCat = Category::CAT_BOOKS_COMICS;
				return true;
			}

			if (preg_match('/alt\.binaries\.console\.ps3/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_GAME_PS3;
				return true;
			}
			if (preg_match('/alt\.binaries\.cores/', $groupRes["name"])) {
				if ($this->isXxx($releasename)) {
					return $this->tmpCat;
				}
				return false;
			}

			if (preg_match('/alt\.binaries(\.(19\d0s|country|sounds?(\.country|\.19\d0s)?))?\.mp3(\.[a-z]+)?/i', $groupRes["name"])) {

				if ($this->isMusic($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/alt\.binaries\.dvd(\-?r)?(\.(movies|))?$/i', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_MOVIE_DVD;
				return true;
			}

			if ($this->categorizeforeign) {
				if (preg_match('/alt\.binaries\.(dvdnordic\.org|nordic\.(dvdr?|xvid))|dk\.(binaer|binaries)\.film(\.divx)?/', $groupRes["name"])) {
					$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
					return true;
				}
			}

			if (preg_match('/alt\.binaries\.documentaries/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
				return true;
			}

			if (preg_match('/alt\.binaries\.e\-?books?((\.|\-)(technical|textbooks))/', $groupRes["name"])) {
				if ($this->categorizeforeign) {
					if ($this->isBookForeign($releasename)) {
						return $this->tmpCat;
					}
					$this->tmpCat = Category::CAT_BOOKS_TECHNICAL;
					return true;
				}
				$this->tmpCat = Category::CAT_BOOKS_TECHNICAL;
				return true;
			}

			if (preg_match('/alt\.binaries\.e\-?book(\.[a-z]+)?/', $groupRes["name"])) {
				if (!preg_match('/(pdf|html|epub|mobi|azw)/', $releasename)) {
					if ($this->isPC($releasename)) {
						return $this->tmpCat;
					}
					return false;
				}
				if ($this->isBook($releasename)) {
					return $this->tmpCat;
				}
				if ($this->categorizeforeign) {
					if ($this->isBookForeign($releasename)) {
						return $this->tmpCat;
					}
					$this->tmpCat = Category::CAT_BOOKS_EBOOK;
					return true;
				}
				$this->tmpCat = Category::CAT_BOOKS_EBOOK;
				return true;
			}

			if (preg_match('/alt\.binaries\..*(erotica|ijsklontje|xxx)/', $groupRes["name"])) {
				if ($this->isXxx($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_XXX_OTHER;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.dox/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_PC_GAMES;
				return true;
			}

			if (preg_match('/alt\.binaries(\.games)?\.nintendo(\.)?ds/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_GAME_NDS;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.wii/', $groupRes["name"])) {
				if ($this->isGameWiiWare($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.xbox$/', $groupRes["name"])) {
				if ($this->isGameXBOX360DLC($releasename)) {
					return $this->tmpCat;
				}
				if ($this->isGameXBOX360($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_XBOX;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.xbox360/', $groupRes["name"])) {
				if ($this->isGameXBOX360DLC($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_XBOX360;
				return true;
			}

			if (preg_match('/alt\.binaries\.ipod\.videos\.tvshows/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_OTHER;
				return true;
			}

			if (preg_match('/alt\.binaries\.mac$/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_PC_MAC;
				return true;
			}

			if (preg_match('/alt\.binaries\.mma$/', $groupRes["name"])) {
				if ($this->is0day($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.moovee/', $groupRes["name"])) {
				// Check the movie isn't an HD release before blindly assigning SD
				if ($this->isMovieHD($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_MOVIE_SD;
				return true;
			}

			if (preg_match('/alt\.binaries\.mpeg\.video\.music/', $groupRes["name"])) {
				if ($this->categorizeforeign) {
					if ($this->isMusicForeign($releasename)) {
						return $this->tmpCat;
					}
					$this->tmpCat = Category::CAT_MUSIC_VIDEO;
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_VIDEO;
				return true;
			}

			if (preg_match('/alt\.binaries\.multimedia\.documentaries/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
				return true;
			}

			if (preg_match('/alt\.binaries\.multimedia\.sports(\.boxing)?/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.music/', $groupRes["name"])) {
				if ($this->categorizeforeign) {
					if ($this->isMusicForeign($releasename)) {
						return $this->tmpCat;
					}
					if ($this->isMusic($releasename)) {
						return $this->tmpCat;
					}
					$this->tmpCat = Category::CAT_MUSIC_MP3;
					return true;
				}
				if ($this->isMusic($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/alt\.binaries\.music\.opera/', $groupRes["name"])) {
				if ($this->categorizeforeign) {
					if ($this->isMusicForeign($releasename)) {
						return $this->tmpCat;
					}
					if (preg_match('/720p|[-._ ]mkv/i', $releasename)) {
						$this->tmpCat = Category::CAT_MUSIC_VIDEO;
						return true;
					}
					$this->tmpCat = Category::CAT_MUSIC_MP3;
					return true;
				}

				if (preg_match('/720p|[-._ ]mkv/i', $releasename)) {
					$this->tmpCat = Category::CAT_MUSIC_VIDEO;
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/audiobook/', $groupRes["name"])) {
				if ($this->categorizeforeign) {
					if ($this->isMusicForeign($releasename)) {
						return $this->tmpCat;
					}
					$this->tmpCat = Category::CAT_MUSIC_AUDIOBOOK;
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_AUDIOBOOK;
				return true;
			}

			if (preg_match('/alt\.binaries\.pro\-wrestling/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.sounds\.(flac(\.jazz)|jpop|lossless(\.[a-z0-9]+)?)|alt\.binaries\.(cd\.lossless|music\.flac)/i', $groupRes["name"])) {
				if ($this->categorizeforeign) {
					if ($this->isMusicForeign($releasename)) {
						return $this->tmpCat;
					}
					$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}

			if (preg_match('/alt\.binaries\.sounds\.whitburn\.pop/i', $groupRes["name"])) {
				if ($this->categorizeforeign) {
					if ($this->isMusicForeign($releasename)) {
						return $this->tmpCat;
					}
					if (!preg_match('/[-._ ]scans[-._ ]/i', $releasename)) {
						$this->tmpCat = Category::CAT_MUSIC_MP3;
						return true;
					}
				}

				if (!preg_match('/[-._ ]scans[-._ ]/i', $releasename)) {
					$this->tmpCat = Category::CAT_MUSIC_MP3;
					return true;
				}
			}

			if (preg_match('/alt\.binaries\.sony\.psp/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_GAME_PSP;
				return true;
			}

			if (preg_match('/alt\.binaries\.warez$/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.warez\.smartphone/', $groupRes["name"])) {
				if ($this->isPhone($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_PC_PHONE_OTHER;
				return true;
			}

			if ($this->categorizeforeign) {
				if (preg_match('/dk\.binaer\.tv/', $groupRes["name"])) {
					$this->tmpCat = Category::CAT_TV_FOREIGN;
					return true;
				}
			}

			return false;
		}
	}

	//
	// Beginning of functions to determine category by release name.
	//

	//	TV.
	public function isTV($releasename, $assumeTV = true)
	{
		$looksLikeTV = preg_match('/Daily[-_\.]Show|Nightly News|\d\d-\d\d-[12][90]\d\d|[12][90]\d{2}\.\d{2}\.\d{2}|\d+x\d+|s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|[-._ ](\dx\d\d|C4TV|Complete[-._ ]Season|DSR|(D|H|P)DTV|EP[-._ ]?\d{1,3}|S\d{1,3}.+Extras|SUBPACK|Season[-._ ]\d{1,2}|WEB\-DL|WEBRip)([-._ ]|$)|TV[-._ ](19|20)\d\d|TrollHD/i', $releasename);
		$looksLikeSportTV = preg_match('/[-._ ]((19|20)\d\d[-._ ]\d{1,2}[-._ ]\d{1,2}[-._ ]VHSRip|Indy[-._ ]?Car|(iMPACT|Smoky[-._ ]Mountain|Texas)[-._ ]Wrestling|Moto[-._ ]?GP|NSCS[-._ ]ROUND|NECW[-._ ]TV|(Per|Post)\-Show|PPV|WrestleMania|WCW|WEB[-._ ]HD|WWE[-._ ](Monday|NXT|RAW|Smackdown|Superstars|WrestleMania))[-._ ]/i', $releasename);
		if (!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}|season|episode/i', $releasename) && preg_match('/part[-._ ]?\d/i', $releasename)) {
			return false;
		}
		if ($looksLikeTV && !preg_match('/[-._ ](flac|imageset|mp3|xxx)[-._ ]|[ .]exe$/i', $releasename)) {
			if ($this->isOtherTV($releasename)) {
				return true;
			}
			if ($this->categorizeforeign) {
				if ($this->isForeignTV($releasename)) {
					return true;
				}
			}
			if ($this->isSportTV($releasename)) {
				return true;
			}
			if ($this->isDocumentaryTV($releasename)) {
				return true;
			}
			if ($this->catwebdl) {
				if ($this->isWEBDL($releasename)) {
					return true;
				}
			}
			if ($this->isHDTV($releasename)) {
				return true;
			}
			if ($this->isSDTV($releasename)) {
				return true;
			}
			if ($this->isAnimeTV($releasename)) {
				return true;
			}
			if ($this->isOtherTV2($releasename)) {
				return true;
			}
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}

		if ($looksLikeSportTV) {
			if ($this->isSportTV($releasename)) {
				return true;
			}
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
		return false;
	}

	public function isOtherTV($releasename)
	{
		if (preg_match('/[-._ ](S\d{1,3}.+Extras|SUBPACK)[-._ ]|News/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
		return false;
	}

	public function isForeignTV($releasename)
	{
		if (!preg_match('/[-._ ](NHL|stanley.+cup)[-._ ]/', $releasename)) {
			if (preg_match('/[-._ ](chinese|dk|fin|french|ger|heb|ita|jap|kor|nor|nordic|nl|pl|swe)[-._ ]?(sub|dub)(ed|bed|s)?|<German>/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ](brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish).+(720p|1080p|Divx|DOKU|DUB(BED)?|DLMUX|NOVARIP|RealCo|Sub(bed|s)?|Web[-._ ]?Rip|WS|Xvid|x264)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ](720p|1080p|Divx|DOKU|DUB(BED)?|DLMUX|NOVARIP|RealCo|Sub(bed|s)?|Web[-._ ]?Rip|WS|Xvid).+(brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/(S\d\d[EX]\d\d|DOCU(MENTAIRE)?|TV)?[-._ ](FRENCH|German|Dutch)[-._ ](720p|1080p|dv(b|d)r(ip)?|LD|HD\-?TV|TV[-._ ]?RIP|x264)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ]FastSUB|NL|nlvlaams|patrfa|RealCO|Seizoen|slosinh|Videomann|Vostfr|xslidian[-._ ]|x264\-iZU/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}
		}
		return false;
	}

	public function isSportTV($releasename)
	{
		if (!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])/i', $releasename)) {
			if (preg_match('/[-._ ]?(Bellator|bundesliga|EPL|ESPN|FIA|la[-._ ]liga|MMA|motogp|NFL|NCAA|PGA|red[-._ ]bull.+race|Sengoku|Strikeforce|supercup|uefa|UFC|wtcc|WWE)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(DTM|FIFA|formula[-._ ]1|indycar|Rugby|NASCAR|NBA|NHL|NRL|netball[-._ ]anz|ROH|SBK|Superleague|The[-._ ]Ultimate[-._ ]Fighter|TNA|V8[-._ ]Supercars|WBA|WrestleMania)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(AFL|Grand Prix|Indy[-._ ]Car|(iMPACT|Smoky[-._ ]Mountain|Texas)[-._ ]Wrestling|Moto[-._ ]?GP|NSCS[-._ ]ROUND|NECW|Poker|PWX|Rugby|WCW)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(Horse)[-._ ]Racing[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}
		}
		return false;
	}

	public function isDocumentaryTV($releasename)
	{
		if (preg_match('/[-._ ](Docu|Documentary)[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
			return true;
		}
		return false;
	}

	public function isWEBDL($releasename)
	{
		if (preg_match('/web[-._ ]dl/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_WEBDL;
			return true;
		}
		return false;
	}

	public function isHDTV($releasename)
	{
		if (preg_match('/1080(i|p)|720p/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_HD;
			return true;
		}
		if ($this->catwebdl == false) {
			if (preg_match('/web[-._ ]dl/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_HD;
				return true;
			}
		}
		return false;
	}

	public function isSDTV($releasename)
	{
		if (preg_match('/(360|480|576)p|Complete[-._ ]Season|dvdr(ip)?|dvd5|dvd9|SD[-._ ]TV|TVRip|NTSC|BDRip|hdtv|xvid/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}

		if (preg_match('/((H|P)D[-._ ]?TV|DSR|WebRip)[-._ ]x264/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}

		if (preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|\s\d{3,4}\s/i', $releasename)) {
			if (preg_match('/(H|P)D[-._ ]?TV|BDRip[-._ ]x264/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_SD;
				return true;
			}
		}
		return false;
	}

	public function isAnimeTV($releasename)
	{
		if (preg_match('/[-._ ]Anime[-._ ]|^\(\[AST\]\s|\[HorribleSubs\]/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_ANIME;
			return true;
		}
		return false;
	}

	public function isOtherTV2($releasename)
	{
		if (preg_match('/[-._ ]s\d{1,3}[-._ ]?(e|d)\d{1,3}([-._ ]|$)/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
		return false;
	}

	//  Movies.
	public function isMovie($releasename)
	{
		if (preg_match('/[-._ ]AVC|[-._ ]|(B|H)(D|R)RIP|Bluray|BD[-._ ]?(25|50)?|BR|Camrip|[-._ ]\d{4}[-._ ].+(720p|1080p|Cam)|DIVX|[-._ ]DVD[-._ ]|DVD-?(5|9|R|Rip)|Untouched|VHSRip|XVID|[-._ ](DTS|TVrip)[-._ ]/i', $releasename) && !preg_match('/auto(cad|desk)|divx[-._ ]plus|[-._ ]exe$|[-._ ](jav|XXX)[-._ ]|SWE6RUS|\wXXX(1080p|720p|DVD)|Xilisoft/i', $releasename)) {
			if ($this->categorizeforeign) {
				if ($this->isMovieForeign($releasename)) {
					return true;
				}
			}
			if ($this->isMovieDVD($releasename)) {
				return true;
			}
			if ($this->isMovieSD($releasename)) {
				return true;
			}
			if ($this->isMovie3D($releasename)) {
				return true;
			}
			if ($this->isMovieBluRay($releasename)) {
				return true;
			}
			if ($this->isMovieHD($releasename)) {
				return true;
			}
			if ($this->isMovieOther($releasename)) {
				return true;
			}
		}

		return false;
	}

	public function isMovieForeign($releasename)
	{
		if (preg_match('/(danish|flemish|Deutsch|dutch|french|german|nl[-._ ]?sub(bed|s)?|\.NL|norwegian|swedish|swesub|spanish|Staffel)[-._ ]|\(german\)|Multisub/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}

		if (preg_match('/Castellano/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}

		if (preg_match('/(720p|1080p|AC3|AVC|DIVX|DVD(5|9|RIP|R)|XVID)[-._ ](Dutch|French|German|ITA)|\(?(Dutch|French|German|ITA)\)?[-._ ](720P|1080p|AC3|AVC|DIVX|DVD(5|9|RIP|R)|HD[-._ ]|XVID)/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}
		return false;
	}

	public function isMovieDVD($releasename)
	{
		if (preg_match('/(dvd\-?r|[-._ ]dvd|dvd9|dvd5|[-._ ]r5)[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_DVD;
			return true;
		}
		return false;
	}

	public function isMovieSD($releasename)
	{
		if (preg_match('/(divx|dvdscr|extrascene|dvdrip|\.CAM|vhsrip|xvid)[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_SD;
			return true;
		}
		return false;
	}

	public function isMovie3D($releasename)
	{
		if (preg_match('/[-._ ]3D\s?[\.\-_\[ ](1080p|(19|20)\d\d|AVC|BD(25|50)|Blu[-._ ]?ray|CEE|Complete|GER|MVC|MULTi|SBS|H(-)?SBS)[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_3D;
			return true;
		}
		return false;
	}

	public function isMovieBluRay($releasename)
	{
		if (preg_match('/bluray\-|[-._ ]bd?[-._ ]?(25|50)|blu-ray|Bluray\s\-\sUntouched|[-._ ]untouched[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_BLURAY;
			return true;
		}
		return false;
	}

	public function isMovieHD($releasename)
	{
		if (preg_match('/720p|1080p|AVC|VC1|VC\-1|web\-dl|wmvhd|x264|XvidHD|bdrip/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_HD;
			return true;
		}
		return false;
	}

	public function isMovieOther($releasename)
	{
		if (preg_match('/[-._ ]cam[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_OTHER;
			return true;
		}
		return false;
	}

	//  PC.
	public function isPC($releasename)
	{
		if (!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|[-._ ](PDTV|PSP|UMD(RIP)?)[-._ ]|SWE6RUS|x264|[-._ ]XXX[-._ ]|Imageset/i', $releasename)) {
			if ($this->isPhone($releasename)) {
				return true;
			}
			if ($this->isMac($releasename)) {
				return true;
			}
			if ($this->isISO($releasename)) {
				return true;
			}
			if ($this->is0day($releasename)) {
				return true;
			}
			if ($this->isPCGame($releasename)) {
				return true;
			}
		}
		return false;
	}

	public function isPhone($releasename)
	{
		if (preg_match('/[-._ ]?(IPHONE|ITOUCH|IPAD)[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_PC_PHONE_IOS;
			return true;
		}

		if (preg_match('/[-._ ]?(ANDROID)[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_PC_PHONE_ANDROID;
			return true;
		}

		if (preg_match('/[-._ ]?(symbian|xscale|wm5|wm6)[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_PC_PHONE_OTHER;
			return true;
		}
		return false;
	}

	public function isISO($releasename)
	{
		if (preg_match('/\biso\b/i', $releasename)) {
			$this->tmpCat = Category::CAT_PC_ISO;
			return true;
		}
		return false;
	}

	public function is0day($releasename)
	{
		if (preg_match('/[-._ ]exe$|[-._ ](utorrent|Virtualbox)[-._ ]|incl.+crack| DRM$|>DRM</i', $releasename)) {
			$this->tmpCat = Category::CAT_PC_0DAY;
			return true;
		}

		if (preg_match('/[-._ ](32bit|64bit|converter|i\d86|keygen|keymaker|freebsd|GAMEGUiDE|hpux|irix|linux|multilingual|Patch|Pro v\d{1,3}|portable|regged|software|solaris|template|unix|win2kxp2k3|win64|winnt|win9x|win2k|winxp|winnt2k2003serv|win9xnt|win9xme|winnt2kxp|win2kxp|win32|winall|Windows|x32|x64|x86)[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_PC_0DAY;
			return true;
		}

		if (preg_match('/Adobe|auto(cad|desk)|\-BEAN|Cracked|Cucusoft|CYGNUS|Divx[-._ ]Plus|\.(deb|exe)|DIGERATI|FOSI|Keyfilemaker|Keymaker|Keygen|Lynda\.com|lz0|MULTiLANGUAGE|MultiOS|\-iNViSiBLE|\-SPYRAL|\-SUNiSO|\-UNION|\-TE|v\d{1,3}.*?Pro|[-._ ]v\d{1,3}[-._ ]|WinAll|\(x(64|86)\)|Xilisoft/i', $releasename)) {
			$this->tmpCat = Category::CAT_PC_0DAY;
			return true;
		}
		return false;
	}

	public function isMac($releasename)
	{
		if (preg_match('/mac(\.|\s)?osx/i', $releasename)) {
			$this->tmpCat = Category::CAT_PC_MAC;
			return true;
		}
		return false;
	}

	public function isPCGame($releasename)
	{
		if (preg_match('/FASDOX|games|PC GAME|RIP\-unleashed|Razor1911/i', $releasename) && !preg_match('/[-._ ]PSP|WII|XBOX|MP3|FLAC/i', $releasename)) {
			$this->tmpCat = Category::CAT_PC_GAMES;
			return true;
		}

		if (preg_match('/[-._ ](0x0007|ALiAS|BACKLASH|BAT|CPY|FASiSO|FLT([-._ ]|COGENT)|GENESIS|HI2U|JAGUAR|MAZE|MONEY|OUTLAWS|PPTCLASSiCS|PROPHET|RAiN|RELOADED|RiTUELYPOGEiOS|SKIDROW|TiNYiSO|FLTDOX|INLAWS)/i', $releasename) && !preg_match('/[-._ ]PSP|WII|XBOX|MP3|FLAC/i', $releasename)) {
			$this->tmpCat = Category::CAT_PC_GAMES;
			return true;
		}
		return false;
	}

	//	XXX.
	public function isXxx($releasename)
	{
		if (preg_match('/(XXX|Porn|PORNOLATiON|SWE6RUS|masturbation|masturebate|lesbian|Imageset|Squirt|Transsexual|a\.b\.erotica|pictures\.erotica\.anime|cumming|ClubSeventeen|Errotica|Erotica|EroticaX|nymph|sexontv|My_Stepfather_Made_Me|slut|\bwhore\b)/i', $releasename)) {
			if ($this->isXxxPack($releasename)) {
				return true;
			}
			if ($this->isXxx264($releasename)) {
				return true;
			}
			if ($this->isXxxXvid($releasename)) {
				return true;
			}
			if ($this->isXxxImageset($releasename)) {
				return true;
			}
			if ($this->isXxxWMV($releasename)) {
				return true;
			}
			if ($this->isXxxDVD($releasename)) {
				return true;
			}
			if ($this->isXxxOther($releasename)) {
				return true;
			}
			$this->tmpCat = Category::CAT_XXX_OTHER;
			return true;
		}
		return false;
	}

	public function isXxx264($releasename)
	{
		if (preg_match('/720p|1080(hd|p)|x264/i', $releasename) && !preg_match('/wmv/i', $releasename)) {
			$this->tmpCat = Category::CAT_XXX_X264;
			return true;
		}
		return false;
	}

	public function isXxxWMV($releasename)
	{
		if (preg_match('/(\d{2}\.\d{2}\.\d{2})|([ex]\d{2,})|f4v|flv|isom|(issue\.\d{2,})|mov|mp4|mpeg|multiformat|pack\-|realmedia|uhq|wmv/i', $releasename)) {
			$this->tmpCat = Category::CAT_XXX_WMV;
			return true;
		}
		return false;
	}

	public function isXxxXvid($releasename)
	{
		if (preg_match('/dvdrip|bdrip|brrip|detoxication|divx|nympho|pornolation|swe6|tesoro|xvid/i', $releasename)) {
			$this->tmpCat = Category::CAT_XXX_XVID;
			return true;
		}
		return false;
	}

	public function isXxxDVD($releasename)
	{
		if (preg_match('/dvdr[^ip]|dvd5|dvd9/i', $releasename)) {
			$this->tmpCat = Category::CAT_XXX_DVD;
			return true;
		}
		return false;
	}

	public function isXxxImageset($releasename)
	{
		if (preg_match('/IMAGESET|ABPEA/i', $releasename)) {
			$this->tmpCat = Category::CAT_XXX_IMAGESET;
			return true;
		}
		return false;
	}

	public function isXxxPack($releasename)
	{
		if (preg_match('/[ \.]PACK[ \.]/i', $releasename)) {
			$this->tmpCat = Category::CAT_XXX_PACKS;
			return true;
		}
		return false;
	}

	public function isXxxOther($releasename)
	{
		// If nothing else matches, then try these words.
		if (preg_match('/[-._ ]Brazzers|Creampie|[-._ ]JAV[-._ ]|North\.Pole|She[-._ ]?Male|Transsexual/i', $releasename)) {
			$this->tmpCat = Category::CAT_XXX_OTHER;
			return true;
		}
		return false;
	}

	//	Console.
	public function isConsole($releasename)
	{
		if ($this->isGameNDS($releasename)) {
			return true;
		}
		if ($this->isGamePS3($releasename)) {
			return true;
		}
		if ($this->isGamePSP($releasename)) {
			return true;
		}
		if ($this->isGameWiiWare($releasename)) {
			return true;
		}
		if ($this->isGameWii($releasename)) {
			return true;
		}
		if ($this->isGameXBOX360DLC($releasename)) {
			return true;
		}
		if ($this->isGameXBOX360($releasename)) {
			return true;
		}
		if ($this->isGameXBOX($releasename)) {
			return true;
		}

		return false;
	}

	public function isGameNDS($releasename)
	{
		if (preg_match('/NDS|[\. ]nds|nintendo.+3ds/', $releasename)) {
			if (preg_match('/\((DE|DSi(\sEnhanched)?|EUR?|FR|GAME|HOL|JP|JPN|NL|NTSC|PAL|KS|USA?)\)/i', $releasename)) {
				$this->tmpCat = Category::CAT_GAME_NDS;
				return true;
			}
			if (preg_match('/(EUR|FR|GAME|HOL|JP|JPN|NL|NTSC|PAL|KS|USA)/i', $releasename)) {
				$this->tmpCat = Category::CAT_GAME_NDS;
				return true;
			}
		}
		return false;
	}

	public function isGamePS3($releasename)
	{
		if (preg_match('/PS3/i', $releasename)) {
			if (preg_match('/ANTiDOTE|DLC|DUPLEX|EUR?|Googlecus|GOTY|\-HR|iNSOMNi|JAP|JPN|KONDIOS|\[PS3\]|PSN/i', $releasename)) {
				$this->tmpCat = Category::CAT_GAME_PS3;
				return true;
			}
			if (preg_match('/AGENCY|APATHY|Caravan|MULTi|NRP|NTSC|PAL|SPLiT|STRiKE|USA?|ZRY/i', $releasename)) {
				$this->tmpCat = Category::CAT_GAME_PS3;
				return true;
			}
		}
		return false;
	}

	public function isGamePSP($releasename)
	{
		if (preg_match('/PSP/i', $releasename)) {
			if (preg_match('/[-._ ](BAHAMUT|Caravan|EBOOT|EMiNENT|EUR?|EvoX|GAME|GHS|Googlecus|HandHeld|\-HR|JAP|JPN|KLOTEKLAPPERS|KOR|NTSC|PAL)/i', $releasename)) {
				$this->tmpCat = Category::CAT_GAME_PSP;
				return true;
			}
			if (preg_match('/[-._ ](Dynarox|HAZARD|ITALIAN|KLB|KuDoS|LIGHTFORCE|MiRiBS|POPSTATiON|(PLAY)?ASiA|PSN|SPANiSH|SUXXORS|UMD(RIP)?|USA?|YARR)/i', $releasename)) {
				$this->tmpCat = Category::CAT_GAME_PSP;
				return true;
			}
		}
	}

	public function isGameWiiWare($releasename)
	{
		if (preg_match('/(Console|DLC|VC).+[-._ ]WII|(Console|DLC|VC)[-._ ]WII|WII[-._ ].+(Console|DLC|VC)|WII[-._ ](Console|DLC|VC)|WIIWARE/i', $releasename)) {
			$this->tmpCat = Category::CAT_GAME_WIIWARE;
			return true;
		}
		return false;
	}

	public function isGameWii($releasename)
	{
		if (preg_match('/WII/i', $releasename)) {
			if (preg_match('/[-._ ](Allstars|BiOSHOCK|dumpTruck|DNi|iCON|JAP|NTSC|PAL|ProCiSiON|PROPER|RANT|REV0|SUNSHiNE|SUSHi|TMD|USA?)/i', $releasename)) {
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}
			if (preg_match('/[-._ ](APATHY|BAHAMUT|DMZ|ERD|GAME|JPN|LoCAL|MULTi|NAGGERS|OneUp|PLAYME|PONS|Scrubbed|VORTEX|ZARD|ZER0)/i', $releasename)) {
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}
			if (preg_match('/[-._ ](ALMoST|AMBITION|Caravan|CLiiCHE|DRYB|HaZMaT|KOR|LOADER|MARVEL|PROMiNENT|LaKiTu|LOCAL|QwiiF|RANT)/i', $releasename)) {
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}
		}
		return false;
	}

	public function isGameXBOX360DLC($releasename)
	{
		if (preg_match('/DLC.+xbox360|xbox360.+DLC|XBLA.+xbox360|xbox360.+XBLA/i', $releasename)) {
			$this->tmpCat = Category::CAT_GAME_XBOX360DLC;
			return true;
		}
		return false;
	}

	public function isGameXBOX360($releasename)
	{
		if (preg_match('/XBOX360/i', $releasename)) {
			$this->tmpCat = Category::CAT_GAME_XBOX360;
			return true;
		}
		if (preg_match('/x360/i', $releasename)) {
			if (preg_match('/Allstars|ASiA|CCCLX|COMPLEX|DAGGER|GLoBAL|iMARS|JAP|JPN|MULTi|NTSC|PAL|REPACK|RRoD|RF|SWAG|USA?/i', $releasename)) {
				$this->tmpCat = Category::CAT_GAME_XBOX360;
				return true;
			}
			if (preg_match('/DAMNATION|GERMAN|GOTY|iNT|iTA|JTAG|KINECT|MARVEL|MUX360|RANT|SPARE|SPANISH|VATOS|XGD/i', $releasename)) {
				$this->tmpCat = Category::CAT_GAME_XBOX360;
				return true;
			}
		}
		return false;
	}

	public function isGameXBOX($releasename)
	{
		if (preg_match('/XBOX/i', $releasename)) {
			$this->tmpCat = Category::CAT_GAME_XBOX;
			return true;
		}
		return false;
	}

	//	Music.
	public function isMusic($releasename)
	{
		if ($this->isMusicVideo($releasename)) {
			return true;
		}
		if ($this->isAudiobook($releasename)) {
			return true;
		}
		if ($this->isMusicLossless($releasename)) {
			return true;
		}
		if ($this->isMusicMP3($releasename)) {
			return true;
		}
		if ($this->isMusicOther($releasename)) {
			return true;
		}

		return false;
	}

	public function isMusicForeign($releasename)
	{
		if ($this->categorizeforeign) {
			if (preg_match('/[ \-\._](brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish|bl|cz|de|es|fr|ger|heb|hu|hun|it(a| 19|20\d\d)|jap|ko|kor|nl|pl|se)[ \-\._]/i', $releasename)) {
				$this->tmpCat = Category::CAT_MUSIC_FOREIGN;
				return true;
			}
		}

		return false;
	}

	public function isAudiobook($releasename)
	{
		if ($this->categorizeforeign) {
			if (preg_match('/Audiobook/i', $releasename)) {
				$this->tmpCat = Category::CAT_MUSIC_FOREIGN;
				return true;
			}
		}

		return false;
	}

	public function isMusicVideo($releasename)
	{
		if (preg_match('/(720P|x264)\-(19|20)\d\d\-[a-z0-9]{1,12}/i', $releasename)) {
			if ($this->isMusicForeign($releasename)) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_MUSIC_VIDEO;
				return true;
			}
		}
		if (preg_match('/[a-z0-9]{1,12}\-(19|20)\d\d\-(720P|x264)/i', $releasename)) {
			if ($this->isMusicForeign($releasename)) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_MUSIC_VIDEO;
				return true;
			}
		}
		return false;
	}

	public function isMusicLossless($releasename)
	{
		if (preg_match('/\[(19|20)\d\d\][-._ ]\[FLAC\]|(\(|\[)flac(\)|\])|FLAC\-(19|20)\d\d\-[a-z0-9]{1,12}|\.flac"|(19|20)\d\d\sFLAC|[-._ ]FLAC.+(19|20)\d\d[-._ ]| FLAC$/i', $releasename)) {
			if ($this->isMusicForeign($releasename)) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}
		}
		return false;
	}

	public function isMusicMP3($releasename)
	{
		if (preg_match('/[a-z0-9]{1,12}\-(19|20)\d\d\-[a-z0-9]{1,12}|[\.\-\(\[_ ]\d{2,3}k[\.\-\)\]_ ]|\((192|256|320)\)|(320|cd|eac|vbr).+mp3|(cd|eac|mp3|vbr).+320|FIH\_INT|\s\dCDs|[-._ ]MP3[-._ ]|MP3\-\d{3}kbps|\.(m3u|mp3)"|NMR\s\d{2,3}\skbps|\(320\)\.|\-\((Bootleg|Promo)\)|\.mp3$|\-\sMP3\s(19|20)\d\d|\(vbr\)|rip(192|256|320)|[-._ ](CDR|WEB).+(19|20)\d\d/i', $releasename)) {
			if ($this->isMusicForeign($releasename)) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}
		}
		if (preg_match('/\s(19|20)\d\d\s([a-z0-9]{3}|[a-z]{2,})$|\-(19|20)\d\d\-(C4|MTD)(\s|\.)|[-._ ]FM.+MP3[-._ ]|\-web\-(19|20)\d\d(\.|\s)|[-._ ](SAT|WEB).+(19|20)\d\d([-._ ]|$)|[-._ ](19|20)\d\d.+(SAT|WEB)([-._ ]|$)| MP3$/i', $releasename)) {
			if ($this->isMusicForeign($releasename)) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}
		}
		return false;
	}

	public function isMusicOther($releasename)
	{
		if (preg_match('/(19|20)\d\d\-(C4)$|[-._ ]\d?CD[-._ ](19|20)\d\d|\(\d\-?CD\)|\-\dcd\-|\d[-._ ]Albums|Albums.+(EP)|Bonus.+Tracks|Box.+?CD.+SET|Discography|D\.O\.M|Greatest\sSongs|Live.+(Bootleg|Remastered)|Music.+Vol|(\(|\[|\s)NMR(\)|\]|\s)|Promo.+CD|Reggaeton|Tiesto.+Club|Vinyl\s2496|\WV\.A\.|^\(VA\s|^VA[-._ ]/i', $releasename)) {
			if ($this->isMusicForeign($releasename)) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_MUSIC_OTHER;
				return true;
			}
		}
		return false;
	}

	//	Books.
	public function isBook($releasename)
	{
		if (!preg_match('/AVI[-._ ]PDF|\.exe|Full[-._ ]Video/i', $releasename)) {
			if ($this->isComic($releasename)) {
				return true;
			}
			if ($this->isTechnicalBook($releasename)) {
				return true;
			}
			if ($this->isMagazine($releasename)) {
				return true;
			}
			if ($this->isBookOther($releasename)) {
				return true;
			}
			if ($this->isEBook($releasename)) {
				return true;
			}
		}
		return false;
	}

	public function isBookForeign($releasename)
	{
		if ($this->categorizeforeign) {
			if (preg_match('/[ \-\._](brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_BOOKS_FOREIGN;
				return true;
			}
		}

		return false;
	}

	public function isComic($releasename)
	{
		if (preg_match('/[\. ](cbr|cbz)|[\( ]c2c|cbr|cbz[\) ]|comix|^\(comic|[\.\-_\(\[ ]comics?[-._ ]|comic.+book|covers.+digital|DC.+(Adventures|Universe)|digital.+(son|zone)|Graphic.+Novel|[\.\-_h ]manga|Total[-._ ]Marvel/i', $releasename)) {
			if ($this->isBookForeign($releasename)) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_BOOKS_COMICS;
				return true;
			}
		}
		return false;
	}

	public function isTechnicalBook($releasename)
	{
		if (preg_match('/^\(?(atz|bb|css|c ?t|Drawing|Gabler|IOS|Iphone|Lynda|Manning|Medic(al|ine)|MIT|No[-._ ]Starch|Packt|Peachpit|Pragmatic|Revista|Servo|SmartBooks|Spektrum|Strata|Sybex|Syngress|Vieweg|Wiley|Woods|Wrox)[-._ ]|[-._ ](Ajax|CSS|DIY|Javascript|(My|Postgre)?SQL|XNA)[-._ ]|3DS\.\-_ ]Max|Academic|Adobe|Algebra|Analysis|Appleworks|Archaeology|Bitdefender|Birkhauser|Britannica|[-._ ]C\+\+|C[-._ ](\+\+|Sharp|Plus)|Chemistry|Circuits|Cook(book|ing)|(Beginners?|Complete|Communications|Definitive|Essential|Hackers?|Practical|Professionals?)[-._ ]Guide|Developer|Diagnostic|Disassembl(er|ing|y)|Debugg(er|ing)|Dreamweaver|Economics|Education|Electronics|Enc(i|y)clopedia|Engineer(ing|s)|Essays|Exercizes|For.+Beginners|Focal[-._ ]Press|For[-._ ]Dummies|FreeBSD|Fundamentals[-._ ]of[-._ ]|(Galileo|Island)[-._ ]Press|Geography|Grammar|Guide[-._ ](For|To)|Hacking|Google|Handboo?k|How[-._ ](It|To)|Intoduction[-._ ]to|Iphone|jQuery|Lessons[-._ ]In|Learning|LibreOffice|Linux|Manual|Marketing|Masonry|Mathematic(al|s)?|Medical|Microsoft|National[-._ ]Academies|Nero[-._ ]\d+|OReilly|OS[-._ ]X[-._ ]|Official[-._ ]Guide|Open(GL|Office)|Pediatric|Periodic.+Table|Photoshop|Physics|Power(PC|Point|Shell)|Programm(ers?|ier||ing)|Raspberry.+Pi|Remedies|Service\s?Manual|SitePoint|Sketching|Statistics|Stock.+Market|Students|Theory|Training|Tutsplus|Ubuntu|Understanding[-._ ](and|Of|The)|Visual[-._ ]Studio|Textbook|VMWare|wii?max|Windows[-._ ](8|7|Vista|XP)|^Wood[-._ ]|Woodwork|WordPress|Work(book|shop)|Youtube/i', $releasename)) {
			if ($this->isBookForeign($releasename)) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_BOOKS_TECHNICAL;
				return true;
			}
		}
		return false;
	}

	public function isMagazine($releasename)
	{
		if (preg_match('/[a-z\-\._ ][-._ ](January|February|March|April|May|June|July|August|September|October|November|December)[-._ ](\d{1,2},)?20\d\d[-._ ]|^\(.+[ .]\d{1,2}[ .]20\d\d[ .].+\.scr|[-._ ](Catalogue|FHM|NUTS|Pictorial|Tatler|XXX)[-._ ]|^\(?(Allehanda|Club|Computer([a-z0-9]+)?|Connect \d+|Corriere|ct|Diario|Digit(al)?|Esquire|FHM|Gadgets|Galileo|Glam|GQ|Infosat|Inked|Instyle|io|Kicker|Liberation|New Scientist|NGV|Nuts|Popular|Professional|Reise|Sette(tv)?|Springer|Stuff|Studentlitteratur|Vegetarian|Vegetable|Videomarkt|Wired)[-._ ]|Brady(.+)?Games|Catalog|Columbus.+Dispatch|Correspondenten|Corriere[-._ ]Della[-._ ]Sera|Cosmopolitan|Dagbladet|Digital[-._ ]Guide|Economist|Eload ?24|ExtraTime|Fatto[-._ ]Quotidiano|Flight[-._ ](International|Journal)|Finanzwoche|France.+Football|Foto.+Video|Games?(Master|Markt|tar|TM)|Gardening|Gazzetta|Globe[-._ ]And[-._ ]Mail|Guitar|Heimkino|Hustler|La.+(Lettura|Rblica|Stampa)|Le[-._ ](Monde|Temps)|Les[-._ ]Echos|e?Magazin(es?)?|Mac(life|welt)|Marie.+Claire|Maxim|Men.+(Health|Fitness)|Motocross|Motorcycle|Mountain[-._ ]Bike|MusikWoche|National[-._ ]Geographic|New[-._ ]Yorker|PC([-._ ](Gamer|Welt|World)|Games|Go|Tip)|Penthouse|Photograph(er|ic)|Playboy|Posten|Quotidiano|(Golf|Readers?).+Digest|SFX[-._ ]UK|Recipe(.+Guide|s)|SkyNews|Sport[-._ ]?Week|Strategy.+Guide|TabletPC|Tattoo[-._ ]Life|The[-._ ]Guardian|Tageszeitung|Tid(bits|ning)|Top[-._ ]Gear[-._ ]|Total[-._ ]Guitar|Travel[-._ ]Guides?|Tribune[-._ ]De[-._ ]|US[-._ ]Weekly|USA[-._ ]Today|Vogue|Verlag|Warcraft|Web.+Designer|What[-._ ]Car|Zeitung/i', $releasename)) {
			if ($this->isBookForeign($releasename)) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_BOOKS_MAGAZINES;
				return true;
			}
		}
		return false;
	}

	public function isBookOther($releasename)
	{
		if (preg_match('/"\d\d-\d\d-20\d\d\./i', $releasename)) {
			$this->tmpCat = Category::CAT_BOOKS_OTHER;
			return true;
		}
		return false;
	}

	public function isEBook($releasename)
	{
		if (preg_match('/^ePub|[-._ ](Ebook|E?\-book|\) WW|Publishing)|[\.\-_\(\[ ](epub|html|mobi|pdf|rtf|tif|txt)[\.\-_\)\] ]|[\. ](doc|epub|mobi|pdf)(?![\w .])/i', $releasename)) {
			if ($this->isBookForeign($releasename)) {
				return true;
			} else {
				$this->tmpCat = Category::CAT_BOOKS_EBOOK;
				return true;
			}
		}
		return false;
	}

	//	Misc, all hash/misc go in other misc.
	public function isMisc($releasename)
	{
		if (!preg_match('/[-._ ](720p|1080p|s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ]))[-._ ]/i', $releasename)) {
			if (preg_match('/[a-z0-9]{21,}/i', $releasename)) {
				$this->tmpCat = Category::CAT_MISC;
				return true;
			}

			if (preg_match('/[A-Z0-9]{20,}/', $releasename)) {
				$this->tmpCat = Category::CAT_MISC;
				return true;
			}

			if (preg_match('/^[A-Z0-9]{1,}$/', $releasename)) {
				$this->tmpCat = Category::CAT_MISC;
				return true;
			}

			if (preg_match('/^[a-z0-9]{1,}$/', $releasename)) {
				$this->tmpCat = Category::CAT_MISC;
				return true;
			}
		}
		return false;
	}
}

class CategoryDanish extends Category
{
	private $tmpCat = 0;

	public function determineCategory($releasename = "", $groupID)
	{
		//Try against all functions, if still nothing, return Cat Misc.
		if ($this->byGroup($releasename, $groupID)) {
			return $this->tmpCat;
		}
		if (Category::isPC($releasename)) {
			return $this->tmpCat;
		}
		if (Category::isXXX($releasename)) {
			return $this->tmpCat;
		}
		if ($this->isTV($releasename)) {
			return $this->tmpCat;
		}
		if ($this->isMovie($releasename)) {
			return $this->tmpCat;
		}
		if (Category::isConsole($releasename)) {
			return $this->tmpCat;
		}
		if (Category::isMusic($releasename)) {
			return $this->tmpCat;
		}
		if (Category::isBook($releasename)) {
			return $this->tmpCat;
		}
		if (Category::isMisc($releasename)) {
			return $this->tmpCat;
		}
	}

	// Groups.
	public function byGroup($releasename, $groupID)
	{
		$groups = new Groups();
		$groupRes = $groups->getByID($groupID);
		if (is_array($groupRes)) {
			if (preg_match('/alt\.binaries\.0day\.stuffz/', $groupRes["name"])) {
				if ($this->isBook($releasename)) {
					return $this->tmpCat;
				}
				if ($this->isPC($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.(multimedia\.erotica\.|cartoons\.french\.|dvd\.|multimedia\.)?anime(\.highspeed|\.repost|s-fansub|\.german)?/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_ANIME;
				return true;
			}

			if (preg_match('/alt\.binaries\.audio\.warez/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.(multimedia\.)?anime(\.(highspeed|repost))?/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_ANIME;
				return true;
			}

			if (preg_match('/alt\.binaries\.cartoons\.french/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/alt\.binaries\.cd\.image\.linux/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.cd\.lossless/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}

			if (preg_match('/alt\.binaries\.classic\.tv\.shows/i', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_SD;
				return true;
			}

			if (preg_match('/alt\.binaries\.(comics\.dcp|pictures\.comics\.(complete|dcp|reposts?))/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_BOOKS_COMICS;
				return true;
			}

			if (preg_match('/alt\.binaries\.console\.ps3/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_GAME_PS3;
				return true;
			}

			if (preg_match('/alt\.binaries\.cores/', $groupRes["name"])) {
				if ($this->isXxx($releasename)) {
					return $this->tmpCat;
				}
				return false;
			}

			if (preg_match('/alt\.binaries(\.(19\d0s|country|sounds?(\.country|\.19\d0s)?))?\.mp3(\.[a-z]+)?/i', $groupRes["name"])) {

				if ($this->isMusic($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/alt\.binaries\.dvd(\-?r)?(\.(movies|))?$/i', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_MOVIE_DVD;
				return true;
			}

			if (preg_match('/alt\.binaries\.documentaries/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
				return true;
			}

			if (preg_match('/alt\.binaries\.e\-?books?((\.|\-)(technical|textbooks))/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_BOOKS_TECHNICAL;
				return true;
			}

			if (preg_match('/alt\.binaries\.e\-?book(\.[a-z]+)?/', $groupRes["name"])) {
				if ($this->isBook($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_BOOKS_EBOOK;
				return true;
			}

			if (preg_match('/alt\.binaries\.((movies|multimedia)\.)?(erotica(\.(amateur|divx))?|ijsklontje)/', $groupRes["name"])) {
				if ($this->isXxx($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_XXX_OTHER;
				return true;
			}

			if (preg_match('/alt\.binaries(\.games)?\.nintendo(\.)?ds/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_GAME_NDS;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.wii/', $groupRes["name"])) {
				if ($this->isGameWiiWare($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.xbox$/', $groupRes["name"])) {
				if ($this->isGameXBOX360DLC($releasename)) {
					return $this->tmpCat;
				}
				if ($this->isGameXBOX360($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_XBOX;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.xbox360/', $groupRes["name"])) {
				if ($this->isGameXBOX360DLC($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_XBOX360;
				return true;
			}

			if (preg_match('/alt\.binaries\.ipod\.videos\.tvshows/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_OTHER;
				return true;
			}

			if (preg_match('/alt\.binaries\.mac$/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_PC_MAC;
				return true;
			}

			if (preg_match('/alt\.binaries\.mma$/', $groupRes["name"])) {
				if ($this->is0day($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.moovee/', $groupRes["name"])) {
				// Check the movie isn't an HD release before blindly assigning SD
				if ($this->isMovieHD($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_MOVIE_SD;
				return true;
			}

			if (preg_match('/alt\.binaries\.mpeg\.video\.music/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_MUSIC_VIDEO;
				return true;
			}

			if (preg_match('/alt\.binaries\.multimedia\.documentaries/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
				return true;
			}

			if (preg_match('/alt\.binaries\.multimedia\.sports(\.boxing)?/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.music\.opera/', $groupRes["name"])) {
				if (preg_match('/720p|[-._ ]mkv/i', $releasename)) {
					$this->tmpCat = Category::CAT_MUSIC_VIDEO;
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/alt\.binaries\.(mp3|sounds?)(\.mp3)?\.audiobook(s|\.repost)?/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_MUSIC_AUDIOBOOK;
				return true;
			}

			if (preg_match('/alt\.binaries\.pro\-wrestling/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.sounds\.(flac(\.jazz)|jpop|lossless(\.[a-z0-9]+)?)|alt\.binaries\.(cd\.lossless|music\.flac)/i', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}

			if (preg_match('/alt\.binaries\.sounds\.whitburn\.pop/i', $groupRes["name"])) {
				if (!preg_match('/[-._ ]scans[-._ ]/i', $releasename)) {
					$this->tmpCat = Category::CAT_MUSIC_MP3;
					return true;
				}
			}

			if (preg_match('/alt\.binaries\.sony\.psp/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_GAME_PSP;
				return true;
			}

			if (preg_match('/alt\.binaries\.warez$/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.warez\.smartphone/', $groupRes["name"])) {
				if ($this->isPhone($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_PC_PHONE_OTHER;
				return true;
			}

			return false;
		}
	}

	//	TV.
	public function isTV($releasename, $assumeTV = true)
	{
		$looksLikeTV = preg_match('/Daily[-_\.]Show|Nightly News|\d\d-\d\d-[12][90]\d\d|[12][90]\d{2}\.\d{2}\.\d{2}|\d+x\d+|s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|[-._ ](\dx\d\d|C4TV|Complete[-._ ]Season|DSR|(D|H|P)DTV|EP[-._ ]?\d{1,3}|S\d{1,3}.+Extras|SUBPACK|Season[-._ ]\d{1,2}|WEB\-DL|WEBRip)([-._ ]|$)|TV[-._ ](19|20)\d\d|TrollHD/i', $releasename);
		$looksLikeSportTV = preg_match('/[-._ ]((19|20)\d\d[-._ ]\d{1,2}[-._ ]\d{1,2}[-._ ]VHSRip|Indy[-._ ]?Car|(iMPACT|Smoky[-._ ]Mountain|Texas)[-._ ]Wrestling|Moto[-._ ]?GP|NSCS[-._ ]ROUND|NECW[-._ ]TV|(Per|Post)\-Show|PPV|WrestleMania|WCW|WEB[-._ ]HD|WWE[-._ ](Monday|NXT|RAW|Smackdown|Superstars|WrestleMania))[-._ ]/i', $releasename);
		if (!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}|season|episode/i', $releasename) && preg_match('/part[-._ ]?\d/i', $releasename)) {
			return false;
		}
		if ($looksLikeTV && !preg_match('/[-._ ](flac|imageset|mp3|xxx)[-._ ]/i', $releasename)) {
			if ($this->isOtherTV($releasename)) {
				return true;
			}
			if ($this->isForeignTV($releasename)) {
				return true;
			}
			if ($this->isSportTV($releasename)) {
				return true;
			}
			if ($this->isDocumentaryTV($releasename)) {
				return true;
			}
			if ($this->isWEBDL($releasename)) {
				return true;
			}
			if ($this->isHDTV($releasename)) {
				return true;
			}
			if ($this->isSDTV($releasename)) {
				return true;
			}
			if ($this->isAnimeTV($releasename)) {
				return true;
			}
			if ($this->isOtherTV2($releasename)) {
				return true;
			}
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}

		if ($looksLikeSportTV) {
			if ($this->isSportTV($releasename)) {
				return true;
			}
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
		return false;
	}

	public function isOtherTV($releasename)
	{
		if (preg_match('/[-._ ](S\d{1,3}.+Extras|SUBPACK)[-._ ]|News/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
	}

	public function isForeignTV($releasename)
	{
		if (!preg_match('/[-._ ](NHL|stanley.+cup)[-._ ]/', $releasename)) {
			if (preg_match('/[-._ ](chinese|fin|french|ger|heb|ita|jap|kor|nor|nordic|nl|pl|swe)[-._ ]?(sub|dub)(ed|bed|s)?|<German>/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ](brazilian|chinese|croatian|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish).+(720p|1080p|Divx|DOKU|DUB(BED)?|DLMUX|NOVARIP|RealCo|Sub(bed|s)?|Web[-._ ]?Rip|WS|Xvid)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ](720p|1080p|Divx|DOKU|DUB(BED)?|DLMUX|NOVARIP|RealCo|Sub(bed|s)?|Web[-._ ]?Rip|WS|Xvid).+(brazilian|chinese|croatian|deutsch|dutch|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/(S\d\d[EX]\d\d|DOCU(MENTAIRE)?|TV)?[-._ ](FRENCH|German|Dutch)[-._ ](720p|1080p|dv(b|d)r(ip)?|LD|HD\-?TV|TV[-._ ]?RIP|x264)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ]FastSUB|NL|nlvlaams|patrfa|RealCO|Seizoen|slosinh|Videomann|Vostfr|xslidian[-._ ]|x264\-iZU/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}
		}
		return false;
	}

	public function isSportTV($releasename)
	{
		if (!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])/i', $releasename)) {
			if (preg_match('/[-._ ]?(Bellator|bundesliga|EPL|ESPN|FIA|la[-._ ]liga|MMA|motogp|NFL|NCAA|PGA|red[-._ ]bull.+race|Sengoku|Strikeforce|supercup|uefa|UFC|wtcc|WWE)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(DTM|FIFA|formula[-._ ]1|indycar|Rugby|NASCAR|NBA|NHL|NRL|netball[-._ ]anz|ROH|SBK|Superleague|The[-._ ]Ultimate[-._ ]Fighter|TNA|V8[-._ ]Supercars|WBA|WrestleMania)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(AFL|Grand Prix|Indy[-._ ]Car|(iMPACT|Smoky[-._ ]Mountain|Texas)[-._ ]Wrestling|Moto[-._ ]?GP|NSCS[-._ ]ROUND|NECW|Poker|PWX|Rugby|WCW)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(Horse)[-._ ]Racing[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}
		}
		return false;
	}

	public function isDocumentaryTV($releasename)
	{
		if (preg_match('/[-._ ](Docu|Documentary)[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
			return true;
		}
		return false;
	}

	public function isWEBDL($releasename)
	{
		if (preg_match('/web[-._ ]dl/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_WEBDL;
			return true;
		}
		return false;
	}

	public function isHDTV($releasename)
	{
		if (preg_match('/1080(i|p)|720p/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_HD;
			return true;
		}
		return false;
	}

	public function isSDTV($releasename)
	{
		if (preg_match('/(360|480|576)p|Complete[-._ ]Season|dvdr|dvd5|dvd9|SD[-._ ]TV|TVRip|xvid/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}

		if (preg_match('/((H|P)D[-._ ]?TV|DSR|WebRip)[-._ ]x264/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}

		if (preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|\s\d{3,4}\s/i', $releasename)) {
			if (preg_match('/(H|P)D[-._ ]?TV|BDRip[-._ ]x264/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_SD;
				return true;
			}
		}
		return false;
	}

	public function isAnimeTV($releasename)
	{
		if (preg_match('/[-._ ]Anime[-._ ]|^\(\[AST\]\s|\[HorribleSubs\]/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_ANIME;
			return true;
		}

		return false;
	}

	public function isOtherTV2($releasename)
	{
		if (preg_match('/[-._ ]s\d{1,3}[-._ ]?(e|d)\d{1,3}[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
	}

	//  Movie.
	public function isMovie($releasename)
	{
		if (preg_match('/[-._ ]AVC|[-._ ]|(B|H)(D|R)RIP|Bluray|BD[-._ ]?(25|50)?|BR|Camrip|[-._ ]\d{4}[-._ ].+(720p|1080p|Cam)|DIVX|[-._ ]DVD[-._ ]|DVD-?(5|9|R|Rip)|Untouched|VHSRip|XVID|[-._ ](DTS|TVrip)[-._ ]/i', $releasename) && !preg_match('/[-._ ]exe$|[-._ ](jav|XXX)[-._ ]|\wXXX(1080p|720p|DVD)|Xilisoft/i', $releasename)) {
			if ($this->isMovieForeign($releasename)) {
				return true;
			}
			if ($this->isMovieDVD($releasename)) {
				return true;
			}
			if ($this->isMovieSD($releasename)) {
				return true;
			}
			if ($this->isMovie3D($releasename)) {
				return true;
			}
			if ($this->isMovieBluRay($releasename)) {
				return true;
			}
			if ($this->isMovieHD($releasename)) {
				return true;
			}
			if ($this->isMovieOther($releasename)) {
				return true;
			}
		}
		return false;
	}

	public function isMovieForeign($releasename)
	{
		if (preg_match('/(danish|flemish|Deutsch|dutch|french|german|nl[-._ ]?sub(bed|s)?|\.NL|norwegian|swedish|swesub|spanish|Staffel)[-._ ]|\(german\)/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}

		if (preg_match('/Castellano/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}

		if (preg_match('/(720p|1080p|AC3|AVC|DIVX|DVD(5|9|RIP|R)|XVID)[-._ ](Dutch|French|German|ITA)|\(?(Dutch|French|German|ITA)\)?[-._ ](720P|1080p|AC3|AVC|DIVX|DVD(5|9|RIP|R)|HD[-._ ]|XVID)/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}
		return false;
	}

	public function isMovieDVD($releasename)
	{
		if (preg_match('/(dvd\-?r|[-._ ]dvd|dvd9|dvd5|[-._ ]r5)[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_DVD;
			return true;
		}
		return false;
	}

	public function isMovieSD($releasename)
	{
		if (preg_match('/(divx|dvdscr|extrascene|dvdrip|\.CAM|vhsrip|xvid)[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_SD;
			return true;
		}
		return false;
	}

	public function isMovie3D($releasename)
	{
		if (preg_match('/[-._ ]3D\s?[\.\-_\[ ](1080p|(19|20)\d\d|AVC|BD(25|50)|Blu[-._ ]?ray|CEE|Complete|GER|MVC|MULTi|SBS)[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_3D;
			return true;
		}
		return false;
	}

	public function isMovieBluRay($releasename)
	{
		if (preg_match('/bluray\-|[-._ ]bd?[-._ ]?(25|50)|blu-ray|Bluray\s\-\sUntouched|[-._ ]untouched[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_BLURAY;
			return true;
		}
		return false;
	}

	public function isMovieHD($releasename)
	{
		if (preg_match('/720p|1080p|AVC|VC1|VC\-1|web\-dl|wmvhd|x264|XvidHD|bdrip/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_HD;
			return true;
		}
		return false;
	}

	public function isMovieOther($releasename)
	{
		if (preg_match('/[-._ ]cam[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_OTHER;
			return true;
		}
		return false;
	}
}

class CategoryFrench extends Category
{
	private $tmpCat = 0;

	public function determineCategory($releasename = "", $groupID)
	{
		//Try against all functions, if still nothing, return Cat Misc.
		if ($this->byGroup($releasename, $groupID)) {
			return $this->tmpCat;
		}
		if (Category::isPC($releasename)) {
			return $this->tmpCat;
		}
		if (Category::isXXX($releasename)) {
			return $this->tmpCat;
		}
		if ($this->isTV($releasename)) {
			return $this->tmpCat;
		}
		if ($this->isMovie($releasename)) {
			return $this->tmpCat;
		}
		if (Category::isConsole($releasename)) {
			return $this->tmpCat;
		}
		if (Category::isMusic($releasename)) {
			return $this->tmpCat;
		}
		if (Category::isBook($releasename)) {
			return $this->tmpCat;
		}
		if (Category::isMisc($releasename)) {
			return $this->tmpCat;
		}
	}

	// Groups.
	public function byGroup($releasename, $groupID)
	{
		$groups = new Groups();
		$groupRes = $groups->getByID($groupID);
		if (is_array($groupRes)) {
			if (preg_match('/alt\.binaries\.0day\.stuffz/', $groupRes["name"])) {
				if ($this->isEBook($releasename)) {
					return $this->tmpCat;
				}
				if ($this->isPC($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.audio\.warez/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.(multimedia\.erotica\.|cartoons\.french\.|dvd\.|multimedia\.)?anime(\.highspeed|\.repost|s-fansub|\.german)?/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_ANIME;
				return true;
			}

			if (preg_match('/alt\.binaries\.cd\.image\.linux/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.cd\.lossless/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}

			if (preg_match('/alt\.binaries\.classic\.tv\.shows/i', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_SD;
				return true;
			}

			if (preg_match('/alt\.binaries\.(comics\.dcp|pictures\.comics\.(complete|dcp|reposts?))/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_BOOKS_COMICS;
				return true;
			}

			if (preg_match('/alt\.binaries\.console\.ps3/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_GAME_PS3;
				return true;
			}
			if (preg_match('/alt\.binaries\.cores/', $groupRes["name"])) {
				if ($this->isXxx($releasename)) {
					return $this->tmpCat;
				}
				return false;
			}

			if (preg_match('/alt\.binaries(\.(19\d0s|country|sounds?(\.country|\.19\d0s)?))?\.mp3(\.[a-z]+)?/i', $groupRes["name"])) {
				if ($this->isMusicLossless($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/alt\.binaries\.dvd(\-?r)?(\.(movies|))?$/i', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_MOVIE_DVD;
				return true;
			}

			if (preg_match('/alt\.binaries\.(dvdnordic\.org|nordic\.(dvdr?|xvid))|dk\.(binaer|binaries)\.film(\.divx)?/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
				return true;
			}

			if (preg_match('/alt\.binaries\.documentaries/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
				return true;
			}

			if (preg_match('/alt\.binaries\.e\-?books?((\.|\-)(technical|textbooks))/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_BOOKS_TECHNICAL;
				return true;
			}

			if (preg_match('/alt\.binaries\.e\-?book(\.[a-z]+)?/', $groupRes["name"])) {
				if ($this->isBook($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_BOOKS_EBOOK;
				return true;
			}

			if (preg_match('/alt\.binaries\.((movies|multimedia)\.)?(erotica(\.(amateur|divx))?|ijsklontje)/', $groupRes["name"])) {
				if ($this->isXxx($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_XXX_OTHER;
				return true;
			}

			if (preg_match('/alt\.binaries(\.games)?\.nintendo(\.)?ds/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_GAME_NDS;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.wii/', $groupRes["name"])) {
				if ($this->isGameWiiWare($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.xbox$/', $groupRes["name"])) {
				if ($this->isGameXBOX360DLC($releasename)) {
					return $this->tmpCat;
				}
				if ($this->isGameXBOX360($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_XBOX;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.xbox360/', $groupRes["name"])) {
				if ($this->isGameXBOX360DLC($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_XBOX360;
				return true;
			}

			if (preg_match('/alt\.binaries\.ipod\.videos\.tvshows/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_OTHER;
				return true;
			}

			if (preg_match('/alt\.binaries\.mac$/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_PC_MAC;
				return true;
			}

			if (preg_match('/alt\.binaries\.mma$/', $groupRes["name"])) {
				if ($this->is0day($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.moovee/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_MOVIE_SD;
				return true;
			}

			if (preg_match('/alt\.binaries\.mpeg\.video\.music/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_MUSIC_VIDEO;
				return true;
			}

			if (preg_match('/alt\.binaries\.multimedia\.documentaries/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
				return true;
			}

			if (preg_match('/alt\.binaries\.multimedia\.sports(\.boxing)?/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.music\.opera/', $groupRes["name"])) {
				if (preg_match('/720p|[-._ ]mkv/i', $releasename)) {
					$this->tmpCat = Category::CAT_MUSIC_VIDEO;
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/alt\.binaries\.(mp3|sounds?)(\.mp3)?\.audiobook(s|\.repost)?/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_MUSIC_AUDIOBOOK;
				return true;
			}

			if (preg_match('/alt\.binaries\.pro\-wrestling/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.sounds\.(flac(\.jazz)|jpop|lossless(\.[a-z0-9]+)?)|alt\.binaries\.(cd\.lossless|music\.flac)/i', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}

			if (preg_match('/alt\.binaries\.sounds\.whitburn\.pop/i', $groupRes["name"])) {
				if (!preg_match('/[-._ ]scans[-._ ]/i', $releasename)) {
					$this->tmpCat = Category::CAT_MUSIC_MP3;
					return true;
				}
			}

			if (preg_match('/alt\.binaries\.sony\.psp/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_GAME_PSP;
				return true;
			}

			if (preg_match('/alt\.binaries\.warez$/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.warez\.smartphone/', $groupRes["name"])) {
				if ($this->isPhone($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_PC_PHONE_OTHER;
				return true;
			}

			if (preg_match('/dk\.binaer\.tv/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			return false;
		}
	}

	//
	//	TV
	//

	public function isTV($releasename, $assumeTV = true)
	{
		$looksLikeTV = preg_match('/Daily[-_\.]Show|Nightly News|\d\d-\d\d-[12][90]\d\d|[12][90]\d{2}\.\d{2}\.\d{2}|\d+x\d+|s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|[-._ ](\dx\d\d|C4TV|Complete[-._ ]Season|DSR|(D|H|P)DTV|EP[-._ ]?\d{1,3}|S\d{1,3}.+Extras|SUBPACK|Season[-._ ]\d{1,2}|WEB\-DL|WEBRip)([-._ ]|$)|TV[-._ ](19|20)\d\d|TrollHD/i', $releasename);
		$looksLikeSportTV = preg_match('/[-._ ]((19|20)\d\d[-._ ]\d{1,2}[-._ ]\d{1,2}[-._ ]VHSRip|Indy[-._ ]?Car|(iMPACT|Smoky[-._ ]Mountain|Texas)[-._ ]Wrestling|Moto[-._ ]?GP|NSCS[-._ ]ROUND|NECW[-._ ]TV|(Per|Post)\-Show|PPV|WrestleMania|WCW|WEB[-._ ]HD|WWE[-._ ](Monday|NXT|RAW|Smackdown|Superstars|WrestleMania))[-._ ]/i', $releasename);
		if (!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}|season|episode/i', $releasename) && preg_match('/part[-._ ]?\d/i', $releasename)) {
			return false;
		}
		if ($looksLikeTV && !preg_match('/[-._ ](flac|imageset|mp3|xxx)[-._ ]/i', $releasename)) {
			if ($this->isOtherTV($releasename)) {
				return true;
			}
			if ($this->isForeignTV($releasename)) {
				return true;
			}
			if ($this->isSportTV($releasename)) {
				return true;
			}
			if ($this->isDocumentaryTV($releasename)) {
				return true;
			}
			if ($this->isWEBDL($releasename)) {
				return true;
			}
			if ($this->isHDTV($releasename)) {
				return true;
			}
			if ($this->isSDTV($releasename)) {
				return true;
			}
			if ($this->isAnimeTV($releasename)) {
				return true;
			}
			if ($this->isOtherTV2($releasename)) {
				return true;
			}
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}

		if ($looksLikeSportTV) {
			if ($this->isSportTV($releasename)) {
				return true;
			}
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
		return false;
	}

	public function isOtherTV($releasename)
	{
		if (preg_match('/[-._ ](S\d{1,3}.+Extras|SUBPACK)[-._ ]|News/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
	}

	public function isForeignTV($releasename)
	{
		if (!preg_match('/[-._ ](NHL|stanley.+cup)[-._ ]/', $releasename)) {
			if (preg_match('/[-._ ](chinese|dk|fin|ger|heb|ita|jap|kor|nor|nordic|nl|pl|swe)[-._ ]?(sub|dub)(ed|bed|s)?|<German>/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ](brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish).+(720p|1080p|Divx|DOKU|DUB(BED)?|DLMUX|NOVARIP|RealCo|Sub(bed|s)?|Web[-._ ]?Rip|WS|Xvid)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ](720p|1080p|Divx|DOKU|DUB(BED)?|DLMUX|NOVARIP|RealCo|Sub(bed|s)?|Web[-._ ]?Rip|WS|Xvid).+(brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|german|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/(S\d\d[EX]\d\d|DOCU|TV)?[-._ ](German|Dutch)[-._ ](720p|1080p|dv(b|d)r(ip)?|LD|HD\-?TV|TV[-._ ]?RIP|x264)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ]FastSUB|NL|nlvlaams|patrfa|RealCO|Seizoen|slosinh|Videomann|xslidian[-._ ]|x264\-iZU/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}
		}
		return false;
	}

	public function isSportTV($releasename)
	{
		if (!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])/i', $releasename)) {
			if (preg_match('/[-._ ]?(Bellator|bundesliga|EPL|ESPN|FIA|la[-._ ]liga|MMA|motogp|NFL|NCAA|PGA|red[-._ ]bull.+race|Sengoku|Strikeforce|supercup|uefa|UFC|wtcc|WWE)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(DTM|FIFA|formula[-._ ]1|indycar|Rugby|NASCAR|NBA|NHL|NRL|netball[-._ ]anz|ROH|SBK|Superleague|The[-._ ]Ultimate[-._ ]Fighter|TNA|V8[-._ ]Supercars|WBA|WrestleMania)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(AFL|Grand Prix|Indy[-._ ]Car|(iMPACT|Smoky[-._ ]Mountain|Texas)[-._ ]Wrestling|Moto[-._ ]?GP|NSCS[-._ ]ROUND|NECW|Poker|PWX|Rugby|WCW)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(Horse)[-._ ]Racing[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}
		}
		return false;
	}

	public function isDocumentaryTV($releasename)
	{
		if (preg_match('/[-._ ](Docu|Documentary)[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
			return true;
		}
		return false;
	}

	public function isWEBDL($releasename)
	{
		if (preg_match('/web[-._ ]dl/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_WEBDL;
			return true;
		}
		return false;
	}

	public function isHDTV($releasename)
	{
		if (preg_match('/1080(i|p)|720p/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_HD;
			return true;
		}
		return false;
	}

	public function isSDTV($releasename)
	{
		if (preg_match('/(360|480|576)p|Complete[-._ ]Season|dvdr|dvd5|dvd9|SD[-._ ]TV|TVRip|xvid/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}

		if (preg_match('/((H|P)D[-._ ]?TV|DSR|WebRip)[-._ ]x264/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}

		if (preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|\s\d{3,4}\s/i', $releasename)) {
			if (preg_match('/(H|P)D[-._ ]?TV|BDRip[-._ ]x264/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_SD;
				return true;
			}
		}
		return false;
	}

	public function isAnimeTV($releasename)
	{
		if (preg_match('/[-._ ]Anime[-._ ]|^\(\[AST\]\s|\[HorribleSubs\]/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_ANIME;
			return true;
		}

		return false;
	}

	public function isOtherTV2($releasename)
	{
		if (preg_match('/[-._ ]s\d{1,3}[-._ ]?(e|d)\d{1,3}[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
	}

	//  Movies.
	public function isMovie($releasename)
	{
		if (preg_match('/[-._ ]AVC|[-._ ]|(B|H)(D|R)RIP|Bluray|BD[-._ ]?(25|50)?|BR|Camrip|[-._ ]\d{4}[-._ ].+(720p|1080p|Cam)|DIVX|[-._ ]DVD[-._ ]|DVD-?(5|9|R|Rip)|Untouched|VHSRip|XVID|[-._ ](DTS|TVrip)[-._ ]/i', $releasename) && !preg_match('/[-._ ]exe$|[-._ ](jav|XXX)[-._ ]|\wXXX(1080p|720p|DVD)|Xilisoft/i', $releasename)) {
			if ($this->isMovieForeign($releasename)) {
				return true;
			}
			if ($this->isMovieDVD($releasename)) {
				return true;
			}
			if ($this->isMovieSD($releasename)) {
				return true;
			}
			if ($this->isMovie3D($releasename)) {
				return true;
			}
			if ($this->isMovieBluRay($releasename)) {
				return true;
			}
			if ($this->isMovieHD($releasename)) {
				return true;
			}
			if ($this->isMovieOther($releasename)) {
				return true;
			}
		}
		return false;
	}

	public function isMovieForeign($releasename)
	{
		if (preg_match('/(danish|flemish|Deutsch|dutch|german|nl[-._ ]?sub(bed|s)?|\.NL|norwegian|swedish|swesub|spanish|Staffel)[-._ ]|\(german\)/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}

		if (preg_match('/Castellano/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}

		if (preg_match('/(720p|1080p|AC3|AVC|DIVX|DVD(5|9|RIP|R)|XVID)[-._ ](Dutch|French|German|ITA)|\(?(Dutch|French|German|ITA)\)?[-._ ](720P|1080p|AC3|AVC|DIVX|DVD(5|9|RIP|R)|HD[-._ ]|XVID)/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}
		return false;
	}

	public function isMovieDVD($releasename)
	{
		if (preg_match('/(dvd\-?r|[-._ ]dvd|dvd9|dvd5|[-._ ]r5)[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_DVD;
			return true;
		}
		return false;
	}

	public function isMovieSD($releasename)
	{
		if (preg_match('/(divx|dvdscr|extrascene|dvdrip|\.CAM|vhsrip|xvid)[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_SD;
			return true;
		}
		return false;
	}

	public function isMovie3D($releasename)
	{
		if (preg_match('/[-._ ]3D\s?[\.\-_\[ ](1080p|(19|20)\d\d|AVC|BD(25|50)|Blu[-._ ]?ray|CEE|Complete|GER|MVC|MULTi|SBS)[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_3D;
			return true;
		}
		return false;
	}

	public function isMovieBluRay($releasename)
	{
		if (preg_match('/bluray\-|[-._ ]bd?[-._ ]?(25|50)|blu-ray|Bluray\s\-\sUntouched|[-._ ]untouched[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_BLURAY;
			return true;
		}
		return false;
	}

	public function isMovieHD($releasename)
	{
		if (preg_match('/720p|1080p|AVC|VC1|VC\-1|web\-dl|wmvhd|x264|XvidHD|bdrip/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_HD;
			return true;
		}
		return false;
	}

	public function isMovieOther($releasename)
	{
		if (preg_match('/[-._ ]cam[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_OTHER;
			return true;
		}
		return false;
	}
}

class CategoryGerman extends Category
{
	private $tmpCat = 0;

	public function determineCategory($releasename = "", $groupID)
	{
		//Try against all functions, if still nothing, return Cat Misc.
		if ($this->byGroup($releasename, $groupID)) {
			return $this->tmpCat;
		}
		if (Category::isPC($releasename)) {
			return $this->tmpCat;
		}
		if (Category::isXXX($releasename)) {
			return $this->tmpCat;
		}
		if ($this->isTV($releasename)) {
			return $this->tmpCat;
		}
		if ($this->isMovie($releasename)) {
			return $this->tmpCat;
		}
		if (Category::isConsole($releasename)) {
			return $this->tmpCat;
		}
		if (Category::isMusic($releasename)) {
			return $this->tmpCat;
		}
		if (Category::isBook($releasename)) {
			return $this->tmpCat;
		}
		if (Category::isMisc($releasename)) {
			return $this->tmpCat;
		}
	}

	public function byGroup($releasename, $groupID)
	{
		$groups = new Groups();
		$groupRes = $groups->getByID($groupID);
		if (is_array($groupRes)) {
			if (preg_match('/alt\.binaries\.0day\.stuffz/', $groupRes["name"])) {
				if ($this->isBook($releasename)) {
					return $this->tmpCat;
				}
				if ($this->isPC($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.(multimedia\.erotica\.|cartoons\.french\.|dvd\.|multimedia\.)?anime(\.highspeed|\.repost|s-fansub|\.german)?/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_ANIME;
				return true;
			}

			if (preg_match('/alt\.binaries\.audio\.warez/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.(multimedia\.)?anime(\.(highspeed|repost))?/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_ANIME;
				return true;
			}

			if (preg_match('/alt\.binaries\.cartoons\.french/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/alt\.binaries\.cd\.image\.linux/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.cd\.lossless/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}

			if (preg_match('/alt\.binaries\.classic\.tv\.shows/i', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_SD;
				return true;
			}

			if (preg_match('/alt\.binaries\.(comics\.dcp|pictures\.comics\.(complete|dcp|reposts?))/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_BOOKS_COMICS;
				return true;
			}

			if (preg_match('/alt\.binaries\.console\.ps3/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_GAME_PS3;
				return true;
			}
			if (preg_match('/alt\.binaries\.cores/', $groupRes["name"])) {
				if ($this->isXxx($releasename)) {
					return $this->tmpCat;
				}
				return false;
			}

			if (preg_match('/alt\.binaries(\.(19\d0s|country|sounds?(\.country|\.19\d0s)?))?\.mp3(\.[a-z]+)?/i', $groupRes["name"])) {

				if ($this->isMusic($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/alt\.binaries\.dvd(\-?r)?(\.(movies|))?$/i', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_MOVIE_DVD;
				return true;
			}

			if (preg_match('/alt\.binaries\.(dvdnordic\.org|nordic\.(dvdr?|xvid))|dk\.(binaer|binaries)\.film(\.divx)?/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
				return true;
			}

			if (preg_match('/alt\.binaries\.documentaries/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
				return true;
			}

			if (preg_match('/alt\.binaries\.e\-?books?((\.|\-)(technical|textbooks))/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_BOOKS_TECHNICAL;
				return true;
			}

			if (preg_match('/alt\.binaries\.e\-?book(\.[a-z]+)?/', $groupRes["name"])) {
				if ($this->isBook($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_BOOKS_EBOOK;
				return true;
			}

			if (preg_match('/alt\.binaries\.((movies|multimedia)\.)?(erotica(\.(amateur|divx))?|ijsklontje)/', $groupRes["name"])) {
				if ($this->isXxx($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_XXX_OTHER;
				return true;
			}

			if (preg_match('/alt\.binaries(\.games)?\.nintendo(\.)?ds/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_GAME_NDS;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.wii/', $groupRes["name"])) {
				if ($this->isGameWiiWare($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_WII;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.xbox$/', $groupRes["name"])) {
				if ($this->isGameXBOX360DLC($releasename)) {
					return $this->tmpCat;
				}
				if ($this->isGameXBOX360($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_XBOX;
				return true;
			}

			if (preg_match('/alt\.binaries\.games\.xbox360/', $groupRes["name"])) {
				if ($this->isGameXBOX360DLC($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_GAME_XBOX360;
				return true;
			}

			if (preg_match('/alt\.binaries\.ipod\.videos\.tvshows/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_OTHER;
				return true;
			}

			if (preg_match('/alt\.binaries\.mac$/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_PC_MAC;
				return true;
			}

			if (preg_match('/alt\.binaries\.mma$/', $groupRes["name"])) {
				if ($this->is0day($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.moovee/', $groupRes["name"])) {
				// Check the movie isn't an HD release before blindly assigning SD
				if ($this->isMovieHD($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_MOVIE_SD;
				return true;
			}

			if (preg_match('/alt\.binaries\.mpeg\.video\.music/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_MUSIC_VIDEO;
				return true;
			}

			if (preg_match('/alt\.binaries\.multimedia\.documentaries/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
				return true;
			}

			if (preg_match('/alt\.binaries\.multimedia\.sports(\.boxing)?/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.music\.opera/', $groupRes["name"])) {
				if (preg_match('/720p|[-._ ]mkv/i', $releasename)) {
					$this->tmpCat = Category::CAT_MUSIC_VIDEO;
					return true;
				}
				$this->tmpCat = Category::CAT_MUSIC_MP3;
				return true;
			}

			if (preg_match('/alt\.binaries\.(mp3|sounds?)(\.mp3)?\.audiobook(s|\.repost)?/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_MUSIC_AUDIOBOOK;
				return true;
			}

			if (preg_match('/alt\.binaries\.pro\-wrestling/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/alt\.binaries\.sounds\.(flac(\.jazz)|jpop|lossless(\.[a-z0-9]+)?)|alt\.binaries\.(cd\.lossless|music\.flac)/i', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_MUSIC_LOSSLESS;
				return true;
			}

			if (preg_match('/alt\.binaries\.sounds\.whitburn\.pop/i', $groupRes["name"])) {
				if (!preg_match('/[-._ ]scans[-._ ]/i', $releasename)) {
					$this->tmpCat = Category::CAT_MUSIC_MP3;
					return true;
				}
			}

			if (preg_match('/alt\.binaries\.sony\.psp/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_GAME_PSP;
				return true;
			}

			if (preg_match('/alt\.binaries\.warez$/', $groupRes["name"])) {
				$this->tmpCat = Category::CAT_PC_0DAY;
				return true;
			}

			if (preg_match('/alt\.binaries\.warez\.smartphone/', $groupRes["name"])) {
				if ($this->isPhone($releasename)) {
					return $this->tmpCat;
				}
				$this->tmpCat = Category::CAT_PC_PHONE_OTHER;
				return true;
			}

			if ($this->categorizeforeign) {
				if (preg_match('/dk\.binaer\.tv/', $groupRes["name"])) {
					$this->tmpCat = Category::CAT_TV_FOREIGN;
					return true;
				}
			}

			return false;
		}
	}

	//	TV.
	public function isTV($releasename, $assumeTV = true)
	{
		$looksLikeTV = preg_match('/Daily[-_\.]Show|Nightly News|\d\d-\d\d-[12][90]\d\d|[12][90]\d{2}\.\d{2}\.\d{2}|\d+x\d+|s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|[-._ ](\dx\d\d|C4TV|Complete[-._ ]Season|DSR|(D|H|P)DTV|EP[-._ ]?\d{1,3}|S\d{1,3}.+Extras|SUBPACK|Season[-._ ]\d{1,2}|WEB\-DL|WEBRip)([-._ ]|$)|TV[-._ ](19|20)\d\d|TrollHD/i', $releasename);
		$looksLikeSportTV = preg_match('/[-._ ]((19|20)\d\d[-._ ]\d{1,2}[-._ ]\d{1,2}[-._ ]VHSRip|Indy[-._ ]?Car|(iMPACT|Smoky[-._ ]Mountain|Texas)[-._ ]Wrestling|Moto[-._ ]?GP|NSCS[-._ ]ROUND|NECW[-._ ]TV|(Per|Post)\-Show|PPV|WrestleMania|WCW|WEB[-._ ]HD|WWE[-._ ](Monday|NXT|RAW|Smackdown|Superstars|WrestleMania))[-._ ]/i', $releasename);
		if (!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}|season|episode/i', $releasename) && preg_match('/part[-._ ]?\d/i', $releasename)) {
			return false;
		}
		if ($looksLikeTV && !preg_match('/[-._ ](flac|imageset|mp3|xxx)[-._ ]/i', $releasename)) {
			if ($this->isOtherTV($releasename)) {
				return true;
			}
			if ($this->isForeignTV($releasename)) {
				return true;
			}
			if ($this->isSportTV($releasename)) {
				return true;
			}
			if ($this->isDocumentaryTV($releasename)) {
				return true;
			}
			if ($this->isWEBDL($releasename)) {
				return true;
			}
			if ($this->isHDTV($releasename)) {
				return true;
			}
			if ($this->isSDTV($releasename)) {
				return true;
			}
			if ($this->isAnimeTV($releasename)) {
				return true;
			}
			if ($this->isOtherTV2($releasename)) {
				return true;
			}
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}

		if ($looksLikeSportTV) {
			if ($this->isSportTV($releasename)) {
				return true;
			}
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
		return false;
	}

	public function isOtherTV($releasename)
	{
		if (preg_match('/[-._ ](S\d{1,3}.+Extras|SUBPACK)[-._ ]|News/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
	}

	public function isForeignTV($releasename)
	{
		if (!preg_match('/[-._ ](NHL|stanley.+cup)[-._ ]/', $releasename)) {
			if (preg_match('/[-._ ](chinese|dk|fin|french|heb|ita|jap|kor|nor|nordic|nl|pl|swe)[-._ ]?(sub|dub)(ed|bed|s)?/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ](brazilian|chinese|croatian|danish|estonian|flemish|finnish|french|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish).+(720p|1080p|Divx|DOKU|DUB(BED)?|DLMUX|NOVARIP|RealCo|Sub(bed|s)?|Web[-._ ]?Rip|WS|Xvid)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ](720p|1080p|Divx|DUB(BED)?|DLMUX|NOVARIP|RealCo|Sub(bed|s)?|Web[-._ ]?Rip|WS|Xvid).+(brazilian|chinese|croatian|danish|deutsch|dutch|estonian|flemish|finnish|french|greek|hebrew|icelandic|italian|ita|latin|mandarin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/(S\d\d[EX]\d\d|DOCU(MENTAIRE)?|TV)?[-._ ](FRENCH|Dutch)[-._ ](720p|1080p|dv(b|d)r(ip)?|LD|HD\-?TV|TV[-._ ]?RIP|x264)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}

			if (preg_match('/[-._ ]FastSUB|NL|nlvlaams|patrfa|RealCO|Seizoen|slosinh|Videomann|Vostfr|xslidian[-._ ]|x264\-iZU/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_FOREIGN;
				return true;
			}
		}
		return false;
	}

	public function isSportTV($releasename)
	{
		if (!preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])/i', $releasename)) {
			if (preg_match('/[-._ ]?(Bellator|bundesliga|EPL|ESPN|FIA|la[-._ ]liga|MMA|motogp|NFL|NCAA|PGA|red[-._ ]bull.+race|Sengoku|Strikeforce|supercup|uefa|UFC|wtcc|WWE)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(DTM|FIFA|formula[-._ ]1|indycar|Rugby|NASCAR|NBA|NHL|NRL|netball[-._ ]anz|ROH|SBK|Superleague|The[-._ ]Ultimate[-._ ]Fighter|TNA|V8[-._ ]Supercars|WBA|WrestleMania)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(AFL|Grand Prix|Indy[-._ ]Car|(iMPACT|Smoky[-._ ]Mountain|Texas)[-._ ]Wrestling|Moto[-._ ]?GP|NSCS[-._ ]ROUND|NECW|Poker|PWX|Rugby|WCW)[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}

			if (preg_match('/[-._ ]?(Horse)[-._ ]Racing[-._ ]/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_SPORT;
				return true;
			}
		}
		return false;
	}

	public function isDocumentaryTV($releasename)
	{
		if (preg_match('/[-._ ](Docu|Documentary)[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_DOCUMENTARY;
			return true;
		}
		return false;
	}

	public function isWEBDL($releasename)
	{
		if (preg_match('/web[-._ ]dl/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_WEBDL;
			return true;
		}
		return false;
	}

	public function isHDTV($releasename)
	{
		if (preg_match('/1080(i|p)|720p/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_HD;
			return true;
		}
		return false;
	}

	public function isSDTV($releasename)
	{
		if (preg_match('/(360|480|576)p|Complete[-._ ]Season|dvdr|dvd5|dvd9|SD[-._ ]TV|TVRip|xvid/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}

		if (preg_match('/((H|P)D[-._ ]?TV|DSR|WebRip)[-._ ]x264/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_SD;
			return true;
		}

		if (preg_match('/s\d{1,3}[-._ ]?[ed]\d{1,3}([ex]\d{1,3}|[-.\w ])|\s\d{3,4}\s/i', $releasename)) {
			if (preg_match('/(H|P)D[-._ ]?TV|BDRip[-._ ]x264/i', $releasename)) {
				$this->tmpCat = Category::CAT_TV_SD;
				return true;
			}
		}
		return false;
	}

	public function isAnimeTV($releasename)
	{
		if (preg_match('/[-._ ]Anime[-._ ]|^\(\[AST\]\s|\[HorribleSubs\]/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_ANIME;
			return true;
		}
		return false;
	}

	public function isOtherTV2($releasename)
	{
		if (preg_match('/[-._ ]s\d{1,3}[-._ ]?(e|d)\d{1,3}[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_TV_OTHER;
			return true;
		}
	}

	//
	//  Movie
	//

	public function isMovie($releasename)
	{
		if (preg_match('/[-._ ]AVC|[-._ ]|(B|H)(D|R)RIP|Bluray|BD[-._ ]?(25|50)?|BR|Camrip|[-._ ]\d{4}[-._ ].+(720p|1080p|Cam)|DIVX|[-._ ]DVD[-._ ]|DVD-?(5|9|R|Rip)|Untouched|VHSRip|XVID|[-._ ](DTS|TVrip)[-._ ]/i', $releasename) && !preg_match('/[-._ ]exe$|[-._ ](jav|XXX)[-._ ]|\wXXX(1080p|720p|DVD)|Xilisoft/i', $releasename)) {
			if ($this->isMovieForeign($releasename)) {
				return true;
			}
			if ($this->isMovieDVD($releasename)) {
				return true;
			}
			if ($this->isMovieSD($releasename)) {
				return true;
			}
			if ($this->isMovie3D($releasename)) {
				return true;
			}
			if ($this->isMovieBluRay($releasename)) {
				return true;
			}
			if ($this->isMovieHD($releasename)) {
				return true;
			}
			if ($this->isMovieOther($releasename)) {
				return true;
			}
		}
		return false;
	}

	public function isMovieForeign($releasename)
	{
		if (preg_match('/(danish|flemish|french|nl[-._ ]?sub(bed|s)?|\.NL|norwegian|swedish|swesub|spanish|Staffel)[-._ ]|\(german\)/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}

		if (preg_match('/Castellano/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}

		if (preg_match('/(720p|1080p|AC3|AVC|DIVX|DVD(5|9|RIP|R)|XVID)[-._ ](French|ITA)|\(?(French|ITA)\)?[-._ ](720P|1080p|AC3|AVC|DIVX|DVD(5|9|RIP|R)|HD[-._ ]|XVID)/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_FOREIGN;
			return true;
		}
		return false;
	}

	public function isMovieDVD($releasename)
	{
		if (preg_match('/(dvd\-?r|[-._ ]dvd|dvd9|dvd5|[-._ ]r5)[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_DVD;
			return true;
		}
		return false;
	}

	public function isMovieSD($releasename)
	{
		if (preg_match('/(divx|dvdscr|extrascene|dvdrip|\.CAM|vhsrip|xvid)[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_SD;
			return true;
		}
		return false;
	}

	public function isMovie3D($releasename)
	{
		if (preg_match('/[-._ ]3D\s?[\.\-_\[ ](1080p|(19|20)\d\d|AVC|BD(25|50)|Blu[-._ ]?ray|CEE|Complete|GER|MVC|MULTi|SBS)[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_3D;
			return true;
		}
		return false;
	}

	public function isMovieBluRay($releasename)
	{
		if (preg_match('/bluray\-|[-._ ]bd?[-._ ]?(25|50)|blu-ray|Bluray\s\-\sUntouched|[-._ ]untouched[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_BLURAY;
			return true;
		}
		return false;
	}

	public function isMovieHD($releasename)
	{
		if (preg_match('/720p|1080p|AVC|VC1|VC\-1|web\-dl|wmvhd|x264|XvidHD|bdrip/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_HD;
			return true;
		}
		return false;
	}

	public function isMovieOther($releasename)
	{
		if (preg_match('/[-._ ]cam[-._ ]/i', $releasename)) {
			$this->tmpCat = Category::CAT_MOVIE_OTHER;
			return true;
		}
		return false;
	}
}