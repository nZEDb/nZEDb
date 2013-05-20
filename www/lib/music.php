<?php
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/amazon.php");
require_once(WWW_DIR."/lib/category.php");
require_once(WWW_DIR."/lib/genres.php");
require_once(WWW_DIR."/lib/site.php");
require_once(WWW_DIR."/lib/util.php");
require_once(WWW_DIR."/lib/releaseimage.php");

class Music
{
	function Music($echooutput=false)
	{
		$this->echooutput = $echooutput;
		$s = new Sites();
		$site = $s->get();
		$this->pubkey = $site->amazonpubkey;
		$this->privkey = $site->amazonprivkey;
		$this->asstag = $site->amazonassociatetag;
		$this->musicqty = (!empty($site->maxmusicprocessed)) ? $site->maxmusicprocessed : 150;
		$this->sleeptime = (!empty($site->amazonsleep)) ? $site->amazonsleep : 1000;
		
		$this->imgSavePath = WWW_DIR.'covers/music/';
	}
	
	public function getMusicInfo($id)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT musicinfo.*, genres.title as genres FROM musicinfo left outer join genres on genres.ID = musicinfo.genreID where musicinfo.ID = %d ", $id));
	}

	public function getMusicInfoByName($artist, $album)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT * FROM musicinfo where title like %s and artist like %s", $db->escapeString("%".$artist."%"),  $db->escapeString("%".$album."%")));
	}

	public function getRange($start, $num)
	{		
		$db = new DB();
		
		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$start.",".$num;
		
		return $db->query(" SELECT * FROM musicinfo ORDER BY createddate DESC".$limit);		
	}
	
	public function getCount()
	{			
		$db = new DB();
		$res = $db->queryOneRow("select count(ID) as num from musicinfo");		
		return $res["num"];
	}
	
	public function getMusicCount($cat, $maxage=-1, $excludedcats=array())
	{
		$db = new DB();
		
		$browseby = $this->getBrowseBy();
		
		$catsrch = "";
		if (count($cat) > 0 && $cat[0] != -1)
		{
			$catsrch = " (";
			foreach ($cat as $category)
			{
				if ($category != -1)
				{
					$categ = new Category();
					if ($categ->isParent($category))
					{
						$children = $categ->getChildren($category);
						$chlist = "-99";
						foreach ($children as $child)
							$chlist.=", ".$child["ID"];

						if ($chlist != "-99")
								$catsrch .= " r.categoryID in (".$chlist.") or ";
					}
					else
					{
						$catsrch .= sprintf(" r.categoryID = %d or ", $category);
					}
				}
			}
			$catsrch.= "1=2 )";
		}			

		if ($maxage > 0)
			$maxage = sprintf(" and r.postdate > now() - interval %d day ", $maxage);
		else
			$maxage = "";		
		
		$exccatlist = "";
		if (count($excludedcats) > 0)
			$exccatlist = " and r.categoryID not in (".implode(",", $excludedcats).")";
		
		$sql = sprintf("select count(r.ID) as num from releases r inner join musicinfo m on m.ID = r.musicinfoID and m.title != '' where r.passwordstatus <= (select value from site where setting='showpasswordedrelease') and %s %s %s %s", $browseby, $catsrch, $maxage, $exccatlist);
		$res = $db->queryOneRow($sql);		
		return $res["num"];	
	}	
	
	public function getMusicRange($cat, $start, $num, $orderby, $maxage=-1, $excludedcats=array())
	{	
		$db = new DB();
		
		$browseby = $this->getBrowseBy();
		
		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$start.",".$num;
		
		$catsrch = "";
		if (count($cat) > 0 && $cat[0] != -1)
		{
			$catsrch = " (";
			foreach ($cat as $category)
			{
				if ($category != -1)
				{
					$categ = new Category();
					if ($categ->isParent($category))
					{
						$children = $categ->getChildren($category);
						$chlist = "-99";
						foreach ($children as $child)
							$chlist.=", ".$child["ID"];

						if ($chlist != "-99")
								$catsrch .= " r.categoryID in (".$chlist.") or ";
					}
					else
					{
						$catsrch .= sprintf(" r.categoryID = %d or ", $category);
					}
				}
			}
			$catsrch.= "1=2 )";
		}	
		
		$maxage = "";
		if ($maxage > 0)
			$maxage = sprintf(" and r.postdate > now() - interval %d day ", $maxage);

		$exccatlist = "";
		if (count($excludedcats) > 0)
			$exccatlist = " and r.categoryID not in (".implode(",", $excludedcats).")";
			
		$order = $this->getMusicOrder($orderby);
		$sql = sprintf(" SELECT r.*, r.ID as releaseID, m.*, g.title as genre, groups.name as group_name, concat(cp.title, ' > ', c.title) as category_name, concat(cp.ID, ',', c.ID) as category_ids, rn.ID as nfoID from releases r left outer join groups on groups.ID = r.groupID inner join musicinfo m on m.ID = r.musicinfoID and m.title != '' left outer join releasenfo rn on rn.releaseID = r.ID and rn.nfo is not null left outer join category c on c.ID = r.categoryID left outer join category cp on cp.ID = c.parentID left outer join genres g on g.ID = m.genreID where r.passwordstatus <= (select value from site where setting='showpasswordedrelease') and %s %s %s %s order by %s %s".$limit, $browseby, $catsrch, $maxage, $exccatlist, $order[0], $order[1]);
		return $db->query($sql);		
	}
	
	public function getMusicOrder($orderby)
	{
		$order = ($orderby == '') ? 'r.postdate' : $orderby;
		$orderArr = explode("_", $order);
		switch($orderArr[0]) {
			case 'artist':
				$orderfield = 'm.artist';
			break;
			case 'size':
				$orderfield = 'r.size';
			break;
			case 'files':
				$orderfield = 'r.totalpart';
			break;
			case 'stats':
				$orderfield = 'r.grabs';
			break;
			case 'year':
				$orderfield = 'm.year';
			break;
			case 'genre':
				$orderfield = 'm.genreID';
			break;
			case 'posted': 
			default:
				$orderfield = 'r.postdate';
			break;
		}
		$ordersort = (isset($orderArr[1]) && preg_match('/^asc|desc$/i', $orderArr[1])) ? $orderArr[1] : 'desc';
		return array($orderfield, $ordersort);
	}
	
	public function getMusicOrdering()
	{
		return array('artist_asc', 'artist_desc', 'posted_asc', 'posted_desc', 'size_asc', 'size_desc', 'files_asc', 'files_desc', 'stats_asc', 'stats_desc', 'year_asc', 'year_desc', 'genre_asc', 'genre_desc');
	}
	
	public function getBrowseByOptions()
	{
		return array('artist'=>'artist', 'title'=>'title', 'genre'=>'genreID', 'year'=>'year');
	}
	
	public function getBrowseBy()
	{
		$db = new Db;
		
		$browseby = ' ';
		$browsebyArr = $this->getBrowseByOptions();
		foreach ($browsebyArr as $bbk=>$bbv) {
			if (isset($_REQUEST[$bbk]) && !empty($_REQUEST[$bbk])) {
				$bbs = stripslashes($_REQUEST[$bbk]);
				if (preg_match('/id/i', $bbv)) {
					$browseby .= "m.{$bbv} = $bbs AND ";
				} else {
					$browseby .= "m.$bbv LIKE(".$db->escapeString('%'.$bbs.'%').") AND ";
				}
			}
		}
		return $browseby;
	}
	
	public function makeFieldLinks($data, $field)
	{
		$tmpArr = explode(', ',$data[$field]);
		$newArr = array();
		$i = 0;
		foreach($tmpArr as $ta) {
			if ($i > 5) { break; } //only use first 6
			$newArr[] = '<a href="'.WWW_TOP.'/music?'.$field.'='.urlencode($ta).'" title="'.$ta.'">'.$ta.'</a>';
			$i++;
		}
		return implode(', ', $newArr);
	}
	
	public function update($id, $title, $asin, $url, $salesrank, $artist, $publisher, $releasedate, $year, $tracks, $cover, $genreID)
	{			
		$db = new DB();
		
		$db->query(sprintf("UPDATE musicinfo SET title=%s, asin=%s, url=%s, salesrank=%s, artist=%s, publisher=%s, releasedate='%s', year=%s, tracks=%s, cover=%d, genreID=%d, updateddate=NOW() WHERE ID = %d", 
		$db->escapeString($title), $db->escapeString($asin), $db->escapeString($url), $salesrank, $db->escapeString($artist), $db->escapeString($publisher), $releasedate, $db->escapeString($year), $db->escapeString($tracks), $cover, $genreID, $id));		
	}
	
	public function updateMusicInfo($title, $year)
	{
		$db = new DB();
		$gen = new Genres();
		$ri = new ReleaseImage();
		
		$mus = array();
		$amaz = $this->fetchAmazonProperties($title);
		if (!$amaz) 
			return false;
		
		// Load genres.
		$defaultGenres = $gen->getGenres(Genres::MUSIC_TYPE);
		$genreassoc = array();
		foreach($defaultGenres as $dg){
			$genreassoc[$dg['ID']] = strtolower($dg['title']);
		}		
		
		//
		// Get album properties.
		//

		$mus['coverurl'] = (string) $amaz->Items->Item->LargeImage->URL;
		if ($mus['coverurl'] != "")
			$mus['cover'] = 1;
		else
			$mus['cover'] = 0;

		$mus['title'] = (string) $amaz->Items->Item->ItemAttributes->Title;
		if (empty($mus['title']))
			return false;
			
		$mus['asin'] = (string) $amaz->Items->Item->ASIN;
		
		$mus['url'] = (string) $amaz->Items->Item->DetailPageURL;
		$mus['url'] = str_replace("%26tag%3Dws", "%26tag%3Dopensourceins%2D21", $mus['url']);
		
		$mus['salesrank'] = (string) $amaz->Items->Item->SalesRank;
		if ($mus['salesrank'] == "")
			$mus['salesrank'] = 'null';
		
		$mus['artist'] = (string) $amaz->Items->Item->ItemAttributes->Artist;
		if (empty($mus['artist']))
			$mus['artist'] = "";
		
		$mus['publisher'] = (string) $amaz->Items->Item->ItemAttributes->Publisher;
		
		$mus['releasedate'] = $db->escapeString((string) $amaz->Items->Item->ItemAttributes->ReleaseDate);
		if ($mus['releasedate'] == "''")
			$mus['releasedate'] = 'null';
		
		$mus['review'] = "";
		if (isset($amaz->Items->Item->EditorialReviews))
			$mus['review'] = trim(strip_tags((string) $amaz->Items->Item->EditorialReviews->EditorialReview->Content));
		
		$mus['year'] = $year;
		if ($mus['year'] == "")
			$mus['year'] = ($mus['releasedate'] != 'null' ? substr($mus['releasedate'], 1, 4) : date("Y"));
		
		$mus['tracks'] = "";
		if (isset($amaz->Items->Item->Tracks))
		{
			$tmpTracks = (array) $amaz->Items->Item->Tracks->Disc;
			$tracks = $tmpTracks['Track'];
			$mus['tracks'] = (is_array($tracks) && !empty($tracks)) ? implode('|', $tracks) : '';
		}
		
		similar_text($mus['artist']." ".$mus['title'], $title, $titlepercent);
		if ($titlepercent < 60)
		{
				return false;
		}
		
		$genreKey = -1;
		$genreName = '';
		if (isset($amaz->Items->Item->BrowseNodes))
		{
			// Had issues getting this out of the browsenodes obj.
			// Workaround is to get the xml and load that into its own obj.
			$amazGenresXml = $amaz->Items->Item->BrowseNodes->asXml();
			$amazGenresObj = simplexml_load_string($amazGenresXml);
			$amazGenres = $amazGenresObj->xpath("//BrowseNodeId");
			
			foreach($amazGenres as $amazGenre)
			{
				$currNode = trim($amazGenre[0]);
				if (empty($genreName))
				{
					$genreMatch = $this->matchBrowseNode($currNode);
					if ($genreMatch !== false)
					{
						$genreName = $genreMatch;
						break;
					}
				}
			}
			
			if (in_array(strtolower($genreName), $genreassoc)) {
				$genreKey = array_search(strtolower($genreName), $genreassoc);
			} else {
				$genreKey = $db->queryInsert(sprintf("INSERT INTO genres (`title`, `type`) VALUES (%s, %d)", $db->escapeString($genreName), Genres::MUSIC_TYPE));
			}		
		}
		$mus['musicgenre'] = $genreName;
		$mus['musicgenreID'] = $genreKey;
				
		$query = sprintf("
		INSERT INTO musicinfo  (`title`, `asin`, `url`, `salesrank`,  `artist`, `publisher`, `releasedate`, `review`, `year`, `genreID`, `tracks`, `cover`, `createddate`, `updateddate`)
		VALUES (%s,		%s,		%s,		%s,		%s,		%s,		%s,		%s,		%s,		%s,		%s,		%d,		now(),		now())
			ON DUPLICATE KEY UPDATE  `title` = %s,  `asin` = %s,  `url` = %s,  `salesrank` = %s,  `artist` = %s,  `publisher` = %s,  `releasedate` = %s,  `review` = %s,  `year` = %s,  `genreID` = %s,  `tracks` = %s,  `cover` = %d,  createddate = now(),  updateddate = now()", 
		$db->escapeString($mus['title']), $db->escapeString($mus['asin']), $db->escapeString($mus['url']), 
		$mus['salesrank'], $db->escapeString($mus['artist']), $db->escapeString($mus['publisher']), 
		$mus['releasedate'], $db->escapeString($mus['review']), $db->escapeString($mus['year']), 
		($mus['musicgenreID']==-1?"null":$mus['musicgenreID']), $db->escapeString($mus['tracks']), $mus['cover'], 
		$db->escapeString($mus['title']), $db->escapeString($mus['asin']), $db->escapeString($mus['url']), 
		$mus['salesrank'], $db->escapeString($mus['artist']), $db->escapeString($mus['publisher']), 
		$mus['releasedate'], $db->escapeString($mus['review']), $db->escapeString($mus['year']), 
		($mus['musicgenreID']==-1?"null":$mus['musicgenreID']), $db->escapeString($mus['tracks']), $mus['cover'] );
		
		$musicId = $db->queryInsert($query);

		if ($musicId) 
		{
			if ($this->echooutput)
			{
				if ($mus["artist"] == "")
					$artist = "";
				else
					$artist = "Artist: ".$mus['artist'].", Album: ";
				echo "added/updated album: ".$artist.$mus['title']." (".$mus['year'].")\n";
			}

			$mus['cover'] = $ri->saveImage($musicId, $mus['coverurl'], $this->imgSavePath, 250, 250);
		} 
		else 
		{
			if ($this->echooutput)
			{
				if ($mus["artist"] == "")
					$artist = "";
				else
					$artist = "Artist: ".$mus['artist'].", Album: ";
				echo "nothing to update: ".$artist.$mus['title']." (".$mus['year'].")\n";
			}
		}
		
		return $musicId;
	}
	
	public function fetchAmazonProperties($title)
	{
		$obj = new AmazonProductAPI($this->pubkey, $this->privkey, $this->asstag);
		try
		{
			$result = $obj->searchProducts($title, AmazonProductAPI::MUSIC, "TITLE");
		}
		catch(Exception $e)
		{
			//if first search failed try the mp3downloads section
			try
			{
				$result = $obj->searchProducts($title, AmazonProductAPI::MP3, "TITLE");
			}
			catch(Exception $e2)
			{
				$result = false;
			}
		}
		return $result;
	}

	public function processMusicReleases($threads=1)
	{
		$threads--;
		$ret = 0;
		$db = new DB();
		$res = $db->queryDirect(sprintf("SELECT searchname, categoryID, ID, relnamestatus from releases where musicinfoID IS NULL and nzbstatus = 1 and relnamestatus > 0 and categoryID in (3010, 3040, 3050) ORDER BY postdate desc LIMIT %d,%d", floor(($this->musicqty) * ($threads * 1.5)), $this->musicqty));
		if ($db->getNumRows($res) > 0)
		{	
			if ($this->echooutput)
				echo "Processing ".$db->getNumRows($res)." music release(s).\n";
						
			while ($arr = $db->fetchAssoc($res)) 
			{				
				$album = $this->parseArtist($arr['searchname'], $arr['categoryID']);
				if ($album !== false)
				{
					if ($album["ext"] !== "")
						$newname = $album["name"].' ('.$album["year"].') '.$album["ext"];
					else
						$newname = $album["name"].' ('.$album["year"].')';
					
					if ($this->echooutput)
						echo 'Looking up: '.$newname."\n";
					
					$albumId = $this->updateMusicInfo($album["name"], $album['year']);
					if ($albumId === false)
					{
						$albumId = -2;
					}

					// Update release.
					if ($arr["relnamestatus"] !== "3")
						$db->query(sprintf("UPDATE releases SET musicinfoID = %d, searchname = %s, relnamestatus = 3 WHERE ID = %d", $albumId, $db->escapeString($newname), $arr["ID"]));
					else
						$db->query(sprintf("UPDATE releases SET musicinfoID = %d WHERE ID = %d", $albumId, $arr["ID"]));

				} 
				else
				{
					// No album found.
					$db->query(sprintf("UPDATE releases SET musicinfoID = %d WHERE ID = %d", -2, $arr["ID"]));
				}
				usleep($this->sleeptime*1000);
			}
		}
	}
	
	public function parseArtist($releasename, $categoryID)
	{
		if (preg_match('/(.+?)(\d{1,2} \d{1,2} )?(19\d{2}|20[0-1][0-9])/', $releasename, $name))
		{
			$ext = "";
			if (preg_match('/ (MP3|FLAC|SAT|WEB) /i', $releasename, $source))
			{
				if ($source[1] == ('FM' || 'MP3' || 'SAT' || 'WEB')){ $ext = "MP3"; }
				else if ($source[1] == "FLAC"){ $ext = "FLAC"; }
			}
			else
			{
				if ($categoryID == "3010"){ $ext = "MP3"; }
				elseif ($categoryID == "3040"){ $ext = "FLAC"; }
			}
			
			$result = array();
			$result["year"] = $name[3];
			$result["ext"] = $ext;
			
			$newname = preg_replace('/ (\d{1,2} \d{1,2} )?(Bootleg|Boxset|Clean.+Version|Compiled by.+|\dCD|Digipak|DIRFIX|DVBS|FLAC|(Ltd )?(Deluxe|Limited|Special).+Edition|Promo|PROOF|Reissue|Remastered|REPACK|RETAIL(.+UK)?|SACD|Sampler|SAT|Summer.+Mag|UK.+Import|Deluxe.+Version|VINYL|WEB)/i', ' ', $name[1]);
			$newname = preg_replace('/ ([a-z]+[0-9]+[a-z]+[0-9]+.+|[a-z]{2,}[0-9]{2,}?.+|3FM|B00[a-z0-9]+|BRC482012|H056|UXM1DW086|(4WCD|ATL|bigFM|CDP|DST|ERE|FIM|MBZZ|MSOne|MVRD|QEDCD|RNB|SBD|SFT|ZYX) \d.+)/i', ' ', $newname);
			$newname = preg_replace('/ (\d{1,2} \d{1,2} )?([A-Z])( ?$)|[0-9]{8,}| (CABLE|FREEWEB|LINE|MAG|MCD|YMRSMILES)/', ' ', $newname);
			$newname = preg_replace('/VA( |-)/', 'Various Artists ', $newname);
			$newname = preg_replace('/ (\d{1,2} \d{1,2} )?(DAB|DE|DVBC|EP|FIX|IT|Jap|NL|PL|(Pure )?FM|SSL|VLS) /i', ' ', $newname);
			$newname = preg_replace('/ (\d{1,2} \d{1,2} )?(CD(A|EP|M|R|S)?|QEDCD|SBD) /i', ' ', $newname);
			$newname = trim(preg_replace('/\s\s+/', ' ', $newname));
			$newname = trim(preg_Replace('/ [a-z]{2}$| [a-z]{3} \d{2,}$|\d{5,} \d{5,}$/i', '', $newname));
			if (!preg_match('/^[a-z0-9]+$/i', $newname) && strlen($newname) > 10)
			{
				$result["name"] = $newname;
				return $result;
			}
			else
				return false;
		}
		else
			return false;
	}

	public function getGenres($activeOnly=false)
	{
		$db = new DB();
		if ($activeOnly)
			return $db->query("SELECT musicgenre.* FROM musicgenre INNER JOIN (SELECT DISTINCT musicgenreID FROM musicinfo) X ON X.musicgenreID = musicgenre.ID ORDER BY title");		
		else
			return $db->query("select * from musicgenre order by title");		
	}
	
	public function matchBrowseNode($nodeId)
	{
		$str = '';
		
		//music nodes above mp3 download nodes
		switch($nodeId)
		{
			case '163420':
				$str = 'Music Video & Concerts';
				break;
			case '30':
			case '624869011':
				$str = 'Alternative Rock';
				break;
			case '31':
			case '624881011':
				$str = 'Blues';
				break;
			case '265640':
			case '624894011':
				$str = 'Broadway & Vocalists';
				break;
			case '173425':
			case '624899011':
				$str = "Children's Music";
				break;
			case '173429': //christian
			case '2231705011': //gospel
			case '624905011': //christian & gospel
				$str = 'Christian & Gospel';
				break;
			case '67204':
			case '624916011':
				$str = 'Classic Rock';
				break;
			case '85':
			case '624926011':
				$str = 'Classical';
				break;
			case '16':
			case '624976011':
				$str = 'Country';
				break;
			case '7': //dance & electronic
			case '624988011': //dance & dj
				$str = 'Dance & Electronic';
				break;
			case '32':
			case '625003011':
				$str = 'Folk';
				break;
			case '67207':
			case '625011011':
				$str = 'Hard Rock & Metal';
				break;
			case '33': //world music
			case '625021011': //international
				$str = 'World Music';
				break;
			case '34':
			case '625036011':
				$str = 'Jazz';
				break;
			case '289122':
			case '625054011':
				$str = 'Latin Music';
				break;
			case '36':
			case '625070011':
				$str = 'New Age';
				break;
			case '625075011':
				$str = 'Opera & Vocal';
				break;
			case '37':
			case '625092011':
				$str = 'Pop';
				break;
			case '39':
			case '625105011':
				$str = 'R&B';
				break;
			case '38':
			case '625117011':
				$str = 'Rap & Hip-Hop';
				break;
			case '40':
			case '625129011':
				$str = 'Rock';
				break;
			case '42':
			case '625144011':
				$str = 'Soundtracks';
				break;
			case '35':
			case '625061011':
				$str = 'Miscellaneous';
				break;			
		}
		return ($str != '') ? $str : false;
	}	

}

?>
