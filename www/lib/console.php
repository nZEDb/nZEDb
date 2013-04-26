<?php
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/amazon.php");
require_once(WWW_DIR."/lib/category.php");
require_once(WWW_DIR."/lib/genres.php");
require_once(WWW_DIR."/lib/site.php");
require_once(WWW_DIR."/lib/util.php");
require_once(WWW_DIR."/lib/releaseimage.php");

class Console
{
	function Console($echooutput=false)
	{
		$this->echooutput = $echooutput;
		$s = new Sites();
		$site = $s->get();
		$this->pubkey = $site->amazonpubkey;
		$this->privkey = $site->amazonprivkey;
		$this->asstag = $site->amazonassociatetag;
		$this->gameqty = (!empty($site->maxgamesprocessed)) ? $site->maxgamesprocessed : 150;
		
		$this->imgSavePath = WWW_DIR.'covers/console/';
	}
	
	public function getConsoleInfo($id)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT consoleinfo.*, genres.title as genres FROM consoleinfo left outer join genres on genres.ID = consoleinfo.genreID where consoleinfo.ID = %d ", $id));
	}

	public function getConsoleInfoByName($title, $platform)
	{
		$db = new DB();
		return $db->queryOneRow(sprintf("SELECT * FROM consoleinfo where title like %s and platform like %s", $db->escapeString("%".$title."%"),  $db->escapeString("%".$platform."%")));
	}

	public function getRange($start, $num)
	{		
		$db = new DB();
		
		if ($start === false)
			$limit = "";
		else
			$limit = " LIMIT ".$start.",".$num;
		
		return $db->query(" SELECT * FROM consoleinfo ORDER BY createddate DESC".$limit);		
	}
	
	public function getCount()
	{			
		$db = new DB();
		$res = $db->queryOneRow("select count(ID) as num from consoleinfo");		
		return $res["num"];
	}
	
	public function getConsoleCount($cat, $maxage=-1, $excludedcats=array())
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
		
		$sql = sprintf("select count(r.ID) as num from releases r inner join consoleinfo c on c.ID = r.consoleinfoID and c.title != '' where r.passwordstatus <= (select value from site where setting='showpasswordedrelease') and %s %s %s %s", $browseby, $catsrch, $maxage, $exccatlist);
		$res = $db->queryOneRow($sql);		
		return $res["num"];	
	}	
	
	public function getConsoleRange($cat, $start, $num, $orderby, $maxage=-1, $excludedcats=array())
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
			
		$order = $this->getConsoleOrder($orderby);
		$sql = sprintf(" SELECT r.*, r.ID as releaseID, con.*, g.title as genre, groups.name as group_name, concat(cp.title, ' > ', c.title) as category_name, concat(cp.ID, ',', c.ID) as category_ids, rn.ID as nfoID from releases r left outer join groups on groups.ID = r.groupID inner join consoleinfo con on con.ID = r.consoleinfoID left outer join releasenfo rn on rn.releaseID = r.ID and rn.nfo is not null left outer join category c on c.ID = r.categoryID left outer join category cp on cp.ID = c.parentID left outer join genres g on g.ID = con.genreID where r.passwordstatus <= (select value from site where setting='showpasswordedrelease') and %s %s %s %s order by %s %s".$limit, $browseby, $catsrch, $maxage, $exccatlist, $order[0], $order[1]);
		return $db->query($sql);		
	}
	
	public function getConsoleOrder($orderby)
	{
		$order = ($orderby == '') ? 'r.postdate' : $orderby;
		$orderArr = explode("_", $order);
		switch($orderArr[0]) {
			case 'title':
				$orderfield = 'con.title';
			break;
			case 'platform':
				$orderfield = 'con.platform';
			break;
			case 'releasedate':
				$orderfield = 'con.releasedate';
			break;
			case 'genre':
				$orderfield = 'con.genreID';
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
			case 'posted': 
			default:
				$orderfield = 'r.postdate';
			break;
		}
		$ordersort = (isset($orderArr[1]) && preg_match('/^asc|desc$/i', $orderArr[1])) ? $orderArr[1] : 'desc';
		return array($orderfield, $ordersort);
	}
	
	public function getConsoleOrdering()
	{
		return array('title_asc', 'title_desc', 'posted_asc', 'posted_desc', 'size_asc', 'size_desc', 'files_asc', 'files_desc', 'stats_asc', 'stats_desc', 'platform_asc', 'platform_desc', 'releasedate_asc', 'releasedate_desc', 'genre_asc', 'genre_desc');
	}
	
	public function getBrowseByOptions()
	{
		return array('platform'=>'platform', 'title'=>'title', 'genre'=>'genreID');
	}
	
	public function getBrowseBy()
	{
		$db = new Db;
		
		$browseby = ' ';
		$browsebyArr = $this->getBrowseByOptions();
		foreach ($browsebyArr as $bbk=>$bbv) {
			if (isset($_REQUEST[$bbk]) && !empty($_REQUEST[$bbk])) {
				$bbs = stripslashes($_REQUEST[$bbk]);
				$browseby .= "con.$bbv LIKE(".$db->escapeString('%'.$bbs.'%').") AND ";
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
			$newArr[] = '<a href="'.WWW_TOP.'/console?'.$field.'='.urlencode($ta).'" title="'.$ta.'">'.$ta.'</a>';
			$i++;
		}
		return implode(', ', $newArr);
	}
	
	public function update($id, $title, $asin, $url, $salesrank, $platform, $publisher, $releasedate, $esrb, $cover, $genreID)
	{			
		$db = new DB();
		
		$db->query(sprintf("UPDATE consoleinfo SET title=%s, asin=%s, url=%s, salesrank=%s, platform=%s, publisher=%s, releasedate='%s', esrb=%s, cover=%d, genreID=%d, updateddate=NOW() WHERE ID = %d", 
		$db->escapeString($title), $db->escapeString($asin), $db->escapeString($url), $salesrank, $db->escapeString($platform), $db->escapeString($publisher), $releasedate, $db->escapeString($esrb), $cover, $genreID, $id));		
	}
	
	public function updateConsoleInfo($gameInfo)
	{
		$db = new DB();
		$gen = new Genres();
		$ri = new ReleaseImage();
		
		$con = array();
		$amaz = $this->fetchAmazonProperties($gameInfo['title'], $gameInfo['node']);
		if (!$amaz) 
			return false;	
		
		//load genres
		$defaultGenres = $gen->getGenres(Genres::CONSOLE_TYPE);
		$genreassoc = array();
		foreach($defaultGenres as $dg) {
			$genreassoc[$dg['ID']] = strtolower($dg['title']);
		}
				
		//
		// get game properties
		//

		$con['coverurl'] = (string) $amaz->Items->Item->LargeImage->URL;
		if ($con['coverurl'] != "")
			$con['cover'] = 1;
		else
			$con['cover'] = 0;

		$con['title'] = (string) $amaz->Items->Item->ItemAttributes->Title;
		if (empty($con['title']))
			$con['title'] = $gameInfo['title'];
					
		$con['platform'] = (string) $amaz->Items->Item->ItemAttributes->Platform;
		if (empty($con['platform']))
			$con['platform'] = $gameInfo['platform'];
		
		//Beginning of Recheck Code
		//This is to verify the result back from amazon was at least somewhat related to what was intended.
		
		//Some of the Platforms don't match Amazon's exactly. This code is needed to facilitate rechecking.
		if (preg_match('/^X360$/i', $gameInfo['platform']))
		{
			$gameInfo['platform'] = str_replace('X360', 'Xbox 360', $gameInfo['platform']);    // baseline single quote
		}		
		if (preg_match('/^XBOX360$/i', $gameInfo['platform']))
		{
			$gameInfo['platform'] = str_replace('XBOX360', 'Xbox 360', $gameInfo['platform']);    // baseline single quote
		}
		if (preg_match('/^NDS$/i', $gameInfo['platform']))
		{
			$gameInfo['platform'] = str_replace('NDS', 'Nintendo DS', $gameInfo['platform']);    // baseline single quote
		}
		if (preg_match('/^PS3$/i', $gameInfo['platform']))
		{
			$gameInfo['platform'] = str_replace('PS3', 'PlayStation 3', $gameInfo['platform']);    // baseline single quote
		}
		if (preg_match('/^PSP$/i', $gameInfo['platform']))
		{
			$gameInfo['platform'] = str_replace('PSP', 'Sony PSP', $gameInfo['platform']);    // baseline single quote
		}
		if (preg_match('/^Wii$/i', $gameInfo['platform']))
		{
			$gameInfo['platform'] = str_replace('Wii', 'Nintendo Wii', $gameInfo['platform']);    // baseline single quote
			$gameInfo['platform'] = str_replace('WII', 'Nintendo Wii', $gameInfo['platform']);    // baseline single quote
		}
		if (preg_match('/^N64$/i', $gameInfo['platform']))
		{
			$gameInfo['platform'] = str_replace('N64', 'Nintendo 64', $gameInfo['platform']);    // baseline single quote
		}
		if (preg_match('/^NES$/i', $gameInfo['platform']))
		{
			$gameInfo['platform'] = str_replace('NES', 'Nintendo NES', $gameInfo['platform']);    // baseline single quote
		}
		if (preg_match('/Super/i', $con['platform']))
		{
			$con['platform'] = str_replace('Super Nintendo', 'SNES', $con['platform']);    // baseline single quote
			$con['platform'] = str_replace('Nintendo Super NES', 'SNES', $con['platform']);    // baseline single quote
		}
		//Remove Online Game Code So Titles Match Properly.
		if (preg_match('/\[Online Game Code\]/i', $con['title']))
		{
			$con['title'] = str_replace(' [Online Game Code]', '', $con['title']);    // baseline single quote
		}
		
		//Basically the XBLA names contain crap, this is to reduce the title down far enough to be usable
		if (preg_match('/xbla/i', $gameInfo['platform']))
		{
			 	$gameInfo['title'] = substr($gameInfo['title'],0,10);
				$con['substr'] = $gameInfo['title'];
		}
		
		//This actual compares the two strings and outputs a percentage value.
		$titlepercent ='';
		$platformpercent ='';
		similar_text(strtolower($gameInfo['title']), strtolower($con['title']), $titlepercent);
		similar_text(strtolower($gameInfo['platform']), strtolower($con['platform']), $platformpercent);
		
		//Since Wii Ware games and XBLA have inconsistent original platforms, as long as title is 50% its ok.
		if (preg_match('/(wiiware|xbla)/i', $gameInfo['platform']))
		{
			 if ($titlepercent >= 50)
			 {
			 	$platformpercent = 100;
			 }
		}
		
		//If the release is DLC matching sucks, so assume anything over 50% is legit.
		if (isset($gameInfo['dlc']) && $gameInfo['dlc'] == 1)
		{
			 if ($titlepercent >= 50)
			 {
			 	$titlepercent = 100;
			 	$platformpercent = 100;
			 }
		}
		
		//Show the Percentages
		//echo("Matched: Title Percentage: $titlepercent%");
		//echo("Matched: Platform Percentage: $platformpercent%");
				
		//If the Title is less than 80% Platform must be 100% unless it is XBLA
		if ($titlepercent < 70)
		{	
			if ($platformpercent != 100)
			{
      return false;
			}
		}
		
		//If title is less than 80% then its most likely not a match
		if ($titlepercent < 70)
		return false;	
		
		//Platform must equal 100%
		if ($platformpercent != 100)
		return false;	
			
		$con['asin'] = (string) $amaz->Items->Item->ASIN;
		
		$con['url'] = (string) $amaz->Items->Item->DetailPageURL;
		$con['url'] = str_replace("%26tag%3Dws", "%26tag%3Dopensourceins%2D21", $con['url']);
		
		$con['salesrank'] = (string) $amaz->Items->Item->SalesRank;
		if ($con['salesrank'] == "")
			$con['salesrank'] = 'null';
		
		$con['publisher'] = (string) $amaz->Items->Item->ItemAttributes->Publisher;
			
		$con['esrb'] = (string) $amaz->Items->Item->ItemAttributes->ESRBAgeRating;
		
		$con['releasedate'] = $db->escapeString((string) $amaz->Items->Item->ItemAttributes->ReleaseDate);
		if ($con['releasedate'] == "''")
			$con['releasedate'] = 'null';
		
		$con['review'] = "";
		if (isset($amaz->Items->Item->EditorialReviews))
			$con['review'] = trim(strip_tags((string) $amaz->Items->Item->EditorialReviews->EditorialReview->Content));
		
		$genreKey = -1;
		$genreName = '';
		if (isset($amaz->Items->Item->BrowseNodes) || isset($amaz->Items->Item->ItemAttributes->Genre))
		{
			if (isset($amaz->Items->Item->BrowseNodes))
			{
				//had issues getting this out of the browsenodes obj
				//workaround is to get the xml and load that into its own obj
				$amazGenresXml = $amaz->Items->Item->BrowseNodes->asXml();
				$amazGenresObj = simplexml_load_string($amazGenresXml);
				$amazGenres = $amazGenresObj->xpath("//Name");
				foreach($amazGenres as $amazGenre)
				{
					$currName = trim($amazGenre[0]);
					if (empty($genreName))
					{
						$genreMatch = $this->matchBrowseNode($currName);
						if ($genreMatch !== false)
						{
							$genreName = $genreMatch;
							break;
						}
					}
				}
			}
						
			if (empty($genreName) && isset($amaz->Items->Item->ItemAttributes->Genre))
			{
				$tmpGenre = (string) $amaz->Items->Item->ItemAttributes->Genre;
				$tmpGenre = str_replace('-', ' ', $tmpGenre);
				$tmpGenre = explode(' ', $tmpGenre);
				foreach($tmpGenre as $tg)
				{
					$genreMatch = $this->matchBrowseNode(ucwords($tg));
					if ($genreMatch !== false)
					{
						$genreName = $genreMatch;
						break;
					}
				}
			}		
		}
		
		if (empty($genreName))
		{
			$genreName = 'Unknown';
		}
		
		if (in_array(strtolower($genreName), $genreassoc)) {
			$genreKey = array_search(strtolower($genreName), $genreassoc);
		} else {
			$genreKey = $db->queryInsert(sprintf("INSERT INTO genres (`title`, `type`) VALUES (%s, %d)", $db->escapeString($genreName), Genres::CONSOLE_TYPE));
		}
		$con['consolegenre'] = $genreName;
		$con['consolegenreID'] = $genreKey;
		
		$query = sprintf("
		INSERT INTO consoleinfo  (`title`, `asin`, `url`, `salesrank`, `platform`, `publisher`, `genreID`, `esrb`, `releasedate`, `review`, `cover`, `createddate`, `updateddate`)
		VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, now(), now())
			ON DUPLICATE KEY UPDATE  `title` = %s,  `asin` = %s,  `url` = %s,  `salesrank` = %s,  `platform` = %s,  `publisher` = %s,  `genreID` = %s,  `esrb` = %s,  `releasedate` = %s,  `review` = %s, `cover` = %d,  createddate = now(),  updateddate = now()", 
		$db->escapeString($con['title']), $db->escapeString($con['asin']), $db->escapeString($con['url']), 
		$con['salesrank'], $db->escapeString($con['platform']), $db->escapeString($con['publisher']), ($con['consolegenreID']==-1?"null":$con['consolegenreID']), $db->escapeString($con['esrb']),
		$con['releasedate'], $db->escapeString($con['review']), $con['cover'], 
		$db->escapeString($con['title']), $db->escapeString($con['asin']), $db->escapeString($con['url']), 
		$con['salesrank'], $db->escapeString($con['platform']), $db->escapeString($con['publisher']), ($con['consolegenreID']==-1?"null":$con['consolegenreID']), $db->escapeString($con['esrb']), 
		$con['releasedate'], $db->escapeString($con['review']), $con['cover'] );

		$consoleId = $db->queryInsert($query);

		if ($consoleId) 
		{
			if ($this->echooutput)
				echo "Added/updated game: ".$con['title']." ".$con['platform'].".\n";

			$con['cover'] = $ri->saveImage($consoleId, $con['coverurl'], $this->imgSavePath, 250, 250);
		} 
		else 
		{
			if ($this->echooutput)
				echo "Nothing to update: ".$con['title']." (".$con['platform'].").\n";
		}

		return $consoleId;
	}
	
	public function fetchAmazonProperties($title, $node)
	{
		$obj = new AmazonProductAPI($this->pubkey, $this->privkey, $this->asstag);
		try
		{
			$result = $obj->searchProducts($title, AmazonProductAPI::GAMES, "NODE", $node);
		}
		catch(Exception $e)
		{
			$result = false;
		}
		return $result;
	}
  
	public function processConsoleReleases()
	{
		$ret = 0;
		$db = new DB();
		
		$res = $db->queryDirect(sprintf("SELECT searchname, ID from releases where consoleinfoID IS NULL and categoryID in ( select ID from category where parentID = %d ) ORDER BY id DESC LIMIT %d", Category::CAT_PARENT_GAME, $this->gameqty));
		if ($db->getNumRows($res) > 0)
		{	
			if ($this->echooutput)
				echo "\nProcessing ".$db->getNumRows($res)." console releases\n";
				
			while ($arr = $db->fetchAssoc($res)) 
			{				
				$gameInfo = $this->parseTitle($arr['searchname']);
				if ($gameInfo !== false)
				{
					
					if ($this->echooutput)
						echo 'Looking up: '.$gameInfo["title"].' ('.$gameInfo["platform"].') ['.$arr['searchname'].']'."\n";
					
					//check for existing console entry
					$gameCheck = $this->getConsoleInfoByName($gameInfo["title"], $gameInfo["platform"]);
					
					if ($gameCheck === false)
					{
						$gameId = $this->updateConsoleInfo($gameInfo);
						if ($gameId === false)
						{
							$gameId = -2;
						}
					}
					else 
					{
						$gameId = $gameCheck["ID"];
					}

					//update release
					$db->query(sprintf("UPDATE releases SET consoleinfoID = %d WHERE ID = %d", $gameId, $arr["ID"]));

				} 
				else {
					//could not parse release title
					$db->query(sprintf("UPDATE releases SET consoleinfoID = %d WHERE ID = %d", -2, $arr["ID"]));
				}
			}
		}
	}
	
	function parseTitle($releasename)
	{
		$result = array();
		
		//get name of the game from name of release
		preg_match('/^(?P<title>.*?)[\.\-_ ](v\.?\d\.\d|PAL|NTSC|EUR|USA|JP|ASIA|JAP|JPN|AUS|MULTI\.?5|MULTI\.?4|MULTI\.?3|PATCHED|FULLDVD|DVD5|DVD9|DVDRIP|PROPER|REPACK|RETAIL|DEMO|DISTRIBUTION|REGIONFREE|READ\.?NFO|NFOFIX|PS2|PS3|PSP|WII|X\-?BOX|XBLA|X360|NDS|N64|NGC)/i', $releasename, $matches);
		if (isset($matches['title'])) 
		{
      $title = $matches['title'];
			//replace dots or underscores with spaces
			$result['title'] = preg_replace('/(\.|_|\%20)/', ' ', $title);
			//Needed to add code to handle DLC Properly
      if (preg_match('/dlc/i', $result['title']))
			{
				$result['dlc'] = '1';
				if (preg_match('/Rock Band Network/i', $result['title']))
				{
        	$result['title'] = 'Rock Band';
				}
				Else if (preg_match('/\-/i', $result['title']))
				{
        	$dlc = explode("-", $result['title']);
        	$result['title'] = $dlc[0];
				}
				Else
				{
        	preg_match('/(.*? .*?) /i', $result['title'], $dlc);
        	$result['title'] = $dlc[0];
				}
			}
		}
		
		//get the platform of the release
		preg_match('/[\.\-_ ](?P<platform>XBLA|WiiWARE|N64|SNES|NES|PS2|PS3|PS 3|PSP|WII|XBOX360|X\-?BOX|X360|NDS|NGC)/i', $releasename, $matches);
		if (isset($matches['platform'])) 
		{
			$platform = $matches['platform'];
			if (preg_match('/^(XBLA)$/i', $platform))
			{
				if (preg_match('/DLC/i', $title))
				{
					$platform = str_replace('XBLA', 'XBOX360', $platform);	   // baseline single quote	
				}
			}
			$browseNode = $this->getBrowseNode($platform);
			$result['platform'] = $platform;
			$result['node'] = $browseNode;
		}
		$result['release'] = $releasename;
		array_map("trim", $result);
		//make sure we got a title and platform otherwise the resulting lookup will probably be shit
		//other option is to pass the $release->categoryID here if we dont find a platform but that would require an extra lookup to determine the name
		//in either case we should have a title at the minimum
		return (isset($result['title']) && !empty($result['title']) && isset($result['platform'])) ? $result : false;
	}
	
	function getBrowseNode($platform)
	{
		switch($platform)
		{
			case 'PS2':
				$nodeId = '301712';
			break;
			case 'PS3':
				$nodeId = '14210751';
			break;
			case 'PSP':
				$nodeId = '11075221';
			break;
			case 'WII':
			case 'Wii':
				$nodeId = '14218901';
			break;
			case 'XBOX360':
			case 'X360':
				$nodeId = '14220161';
			break;
			case 'XBOX':
			case 'X-BOX':
				$nodeId = '537504';
			break;
			case 'NDS':
				$nodeId = '11075831';
			break;
			case 'N64':
				$nodeId = '229763';
			break;
			case 'SNES':
				$nodeId = '294945';
			break;
			case 'NES':
				$nodeId = '566458';
			break;
			case 'NGC':
				$nodeId = '541022';
			break;
			default:
				$nodeId = '468642'; 
			break;
		}
	
		return $nodeId;
	}
	
	public function matchBrowseNode($nodeName)
	{
		$str = '';
		
		//music nodes above mp3 download nodes
		switch($nodeName)
		{
			case 'Action':
			case 'Adventure':
			case 'Arcade':
			case 'Board Games':
			case 'Cards':
			case 'Casino':
			case 'Flying':
			case 'Puzzle':
			case 'Racing':
			case 'Rhythm':
			case 'Role-Playing':
			case 'Simulation':
			case 'Sports':
			case 'Strategy':
			case 'Trivia':
				$str = $nodeName;
				break;
		}
		return ($str != '') ? $str : false;
	}

}


?>
