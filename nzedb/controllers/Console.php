<?php
require_once nZEDb_LIBS . 'AmazonProductAPI.php';
require_once nZEDb_LIB . 'utility' . DS . 'Utility.php';

use nzedb\db\Settings;

class Console
{
	const REQID_NONE   = -3; // The Request ID was not found locally or via web lookup.
	const REQID_ZERO   = -2; // The Request ID was 0.
	const REQID_NOLL   = -1; // Request ID was not found via local lookup.
	const CONS_UPROC  =   0; // Release has not been processed.
	const REQID_FOUND  =  1; // Request ID found and release was updated.

	public $pdo;

	function __construct($echooutput = false)
	{
		$this->echooutput = ($echooutput && nZEDb_ECHOCLI);

		$this->pdo = new Settings();
		$this->pubkey = $this->pdo->getSetting('amazonpubkey');
		$this->privkey = $this->pdo->getSetting('amazonprivkey');
		$this->asstag = $this->pdo->getSetting('amazonassociatetag');
		$this->gameqty = ($this->pdo->getSetting('maxgamesprocessed') != '') ? $this->pdo->getSetting('maxgamesprocessed') : 150;
		$this->sleeptime = ($this->pdo->getSetting('amazonsleep') != '') ? $this->pdo->getSetting('amazonsleep') : 1000;
		$this->imgSavePath = nZEDb_COVERS . 'console' . DS;
		$this->renamed = '';
		if ($this->pdo->getSetting('lookupgames') == 2) {
			$this->renamed = 'AND isrenamed = 1';
		}
		//$this->cleanconsole = ($this->pdo->getSetting('lookupgames') == 2) ? 'AND isrenamed = 1' : '';
		$this->c = new ColorCLI();
	}

	public function getConsoleInfo($id)
	{
		$pdo = $this->pdo;
		return $pdo->queryOneRow(sprintf("SELECT consoleinfo.*, genres.title AS genres FROM consoleinfo LEFT OUTER JOIN genres ON genres.id = consoleinfo.genreid WHERE consoleinfo.id = %d ", $id));
	}

	public function getConsoleInfoByName($title, $platform)
	{
		$pdo = $this->pdo;
		$like = 'ILIKE';
		if ($pdo->dbSystem() === 'mysql') {
			$like = 'LIKE';
		}
		return $pdo->queryOneRow(sprintf("SELECT * FROM consoleinfo WHERE title LIKE %s AND platform %s %s", $pdo->escapeString("%" . $title . "%"), $like, $pdo->escapeString("%" . $platform . "%")));
	}

	public function getRange($start, $num)
	{
		$pdo = $this->pdo;

		if ($start === false) {
			$limit = "";
		} else {
			$limit = " LIMIT " . $num . " OFFSET " . $start;
		}

		return $pdo->query("SELECT * FROM consoleinfo ORDER BY createddate DESC" . $limit);
	}

	public function getCount()
	{
		$pdo = $this->pdo;
		$res = $pdo->queryOneRow("SELECT COUNT(id) AS num FROM consoleinfo");
		return $res["num"];
	}

	public function getConsoleCount($cat, $maxage = -1, $excludedcats = array())
	{
		$pdo = $this->pdo;

		$browseby = $this->getBrowseBy();

		$catsrch = "";
		if (count($cat) > 0 && $cat[0] != -1) {
			$catsrch = " (";
			$categ = new Category();
			foreach ($cat as $category) {
				if ($category != -1) {
					if ($categ->isParent($category)) {
						$children = $categ->getChildren($category);
						$chlist = "-99";
						foreach ($children as $child) {
							$chlist .= ", " . $child["id"];
						}

						if ($chlist != "-99") {
							$catsrch .= " r.categoryid IN (" . $chlist . ") OR ";
						}
					} else {
						$catsrch .= sprintf(" r.categoryid = %d OR ", $category);
					}
				}
			}
			$catsrch .= "1=2 )";
		}

		if ($maxage > 0) {
			if ($pdo->dbSystem() === 'mysql') {
				$maxage = sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else if ($pdo->dbSystem() === 'pgsql') {
				$maxage = sprintf(" AND r.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		} else {
			$maxage = '';
		}

		$exccatlist = "";
		if (count($excludedcats) > 0) {
			$exccatlist = " AND r.categoryid NOT IN (" . implode(",", $excludedcats) . ")";
		}

		$res = $pdo->queryOneRow(sprintf("SELECT COUNT(DISTINCT r.consoleinfoid) AS num FROM releases r INNER JOIN consoleinfo con ON con.id = r.consoleinfoid AND con.title != '' AND con.cover = 1 WHERE r.nzbstatus = 1 AND r.passwordstatus <= (SELECT value FROM settings WHERE setting='showpasswordedrelease') AND %s %s %s %s", $browseby, $catsrch, $maxage, $exccatlist));
		return $res["num"];
	}

	public function getConsoleRange($cat, $start, $num, $orderby, $maxage = -1, $excludedcats = array())
	{
		$pdo = $this->pdo;

		$browseby = $this->getBrowseBy();

		if ($start === false) {
			$limit = "";
		} else {
			$limit = " LIMIT " . $num . " OFFSET " . $start;
		}

		$catsrch = "";
		if (count($cat) > 0 && $cat[0] != -1) {
			$catsrch = " (";
			$categ = new Category();
			foreach ($cat as $category) {
				if ($category != -1) {
					if ($categ->isParent($category)) {
						$children = $categ->getChildren($category);
						$chlist = "-99";
						foreach ($children as $child) {
							$chlist .= ", " . $child["id"];
						}

						if ($chlist != "-99") {
							$catsrch .= " r.categoryid IN (" . $chlist . ") OR ";
						}
					} else {
						$catsrch .= sprintf(" r.categoryid = %d OR ", $category);
					}
				}
			}
			$catsrch .= "1=2 )";
		}

		$maxage = '';
		if ($maxage > 0) {
			if ($pdo->dbSystem() === 'mysql') {
				$maxage = sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxage);
			} else if ($pdo->dbSystem() === 'pgsql') {
				$maxage = sprintf(" AND r.postdate > NOW() - INTERVAL '%d DAYS' ", $maxage);
			}
		}

		$exccatlist = "";
		if (count($excludedcats) > 0) {
			$exccatlist = " AND r.categoryid NOT IN (" . implode(",", $excludedcats) . ")";
		}

		$order = $this->getConsoleOrder($orderby);
		return $pdo->query(
			sprintf(
				"SELECT GROUP_CONCAT(r.id ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_id, "
				. "GROUP_CONCAT(r.rarinnerfilecount ORDER BY r.postdate DESC SEPARATOR ',') as grp_rarinnerfilecount, "
				. "GROUP_CONCAT(r.haspreview ORDER BY r.postdate DESC SEPARATOR ',') AS grp_haspreview, "
				. "GROUP_CONCAT(r.passwordstatus ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_password, "
				. "GROUP_CONCAT(r.guid ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_guid, "
				. "GROUP_CONCAT(rn.id ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_nfoid, "
				. "GROUP_CONCAT(groups.name ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_grpname, "
				. "GROUP_CONCAT(r.searchname ORDER BY r.postdate DESC SEPARATOR '#') AS grp_release_name, "
				. "GROUP_CONCAT(r.postdate ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_postdate, "
				. "GROUP_CONCAT(r.size ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_size, "
				. "GROUP_CONCAT(r.totalpart ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_totalparts, "
				. "GROUP_CONCAT(r.comments ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_comments, "
				. "GROUP_CONCAT(r.grabs ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_grabs, "
				. "con.*, r.consoleinfoid, groups.name AS group_name, rn.id as nfoid FROM releases r "
				. "LEFT OUTER JOIN groups ON groups.id = r.group_id "
				. "LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id "
				. "INNER JOIN consoleinfo con ON con.id = r.consoleinfoid "
				. "WHERE r.nzbstatus = 1 AND con.cover = 1 AND con.title != '' AND "
				. "r.passwordstatus <= (SELECT value FROM settings WHERE setting='showpasswordedrelease') AND %s %s %s
				%s "
				. "GROUP BY con.id ORDER BY %s %s" . $limit, $browseby, $catsrch, $maxage, $exccatlist, $order[0], $order[1]
			)
		);
	}

	public function getConsoleOrder($orderby)
	{
		$order = ($orderby == '') ? 'r.postdate' : $orderby;
		$orderArr = explode("_", $order);
		switch ($orderArr[0]) {
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
		return array('platform' => 'platform', 'title' => 'title', 'genre' => 'genreID');
	}

	public function getBrowseBy()
	{
		$pdo = $this->pdo;

		$browseby = ' ';
		$browsebyArr = $this->getBrowseByOptions();
		$like = 'ILIKE';
		if ($pdo->dbSystem() === 'mysql') {
			$like = 'LIKE';
		}
		foreach ($browsebyArr as $bbk => $bbv) {
			if (isset($_REQUEST[$bbk]) && !empty($_REQUEST[$bbk])) {
				$bbs = stripslashes($_REQUEST[$bbk]);
				$browseby .= 'con.' . $bbv . ' ' . $like . ' (' . $pdo->escapeString('%' . $bbs . '%') . ') AND ';
			}
		}
		return $browseby;
	}

	public function makeFieldLinks($data, $field)
	{
		$tmpArr = explode(', ', $data[$field]);
		$newArr = array();
		$i = 0;
		foreach ($tmpArr as $ta) {
			// Only use first 6.
			if ($i > 5) {
				break;
			}
			$newArr[] = '<a href="' . WWW_TOP . '/console?' . $field . '=' . urlencode($ta) . '" title="' . $ta . '">' . $ta . '</a>';
			$i++;
		}
		return implode(', ', $newArr);
	}

	public function update($id, $title, $asin, $url, $salesrank, $platform, $publisher, $releasedate, $esrb, $cover, $genreID)
	{
		$pdo = $this->pdo;

		$pdo->queryExec(sprintf("UPDATE consoleinfo SET title = %s, asin = %s, url = %s, salesrank = %s, platform = %s, publisher = %s, releasedate= %s, esrb = %s, cover = %d, genreid = %d, updateddate = NOW() WHERE id = %d", $pdo->escapeString($title), $pdo->escapeString($asin), $pdo->escapeString($url), $salesrank, $pdo->escapeString($platform), $pdo->escapeString($publisher), $pdo->escapeString($releasedate), $pdo->escapeString($esrb), $cover, $genreID, $id));
	}

	public function updateConsoleInfo($gameInfo)
	{
		$pdo = $this->pdo;
		$gen = new Genres();
		$ri = new ReleaseImage();

		$con = array();
		$amaz = $this->fetchAmazonProperties($gameInfo['title'], $gameInfo['node']);
		if (!$amaz) {
			return false;
		}

		// Load genres.
		$defaultGenres = $gen->getGenres(Genres::CONSOLE_TYPE);
		$genreassoc = array();
		foreach ($defaultGenres as $dg) {
			$genreassoc[$dg['id']] = strtolower($dg['title']);
		}

		// Get game properties.
		$con['coverurl'] = (string)$amaz->Items->Item->LargeImage->URL;
		if ($con['coverurl'] != "") {
			$con['cover'] = 1;
		} else {
			$con['cover'] = 0;
		}

		$con['title'] = (string)$amaz->Items->Item->ItemAttributes->Title;
		if (empty($con['title'])) {
			$con['title'] = $gameInfo['title'];
		}

		$con['platform'] = (string)$amaz->Items->Item->ItemAttributes->Platform;
		if (empty($con['platform'])) {
			$con['platform'] = $gameInfo['platform'];
		}

		// Beginning of Recheck Code.
		// This is to verify the result back from amazon was at least somewhat related to what was intended.
		// Some of the platforms don't match Amazon's exactly. This code is needed to facilitate rechecking.
		if (preg_match('/^X360$/i', $gameInfo['platform'])) {
			$gameInfo['platform'] = str_replace('X360', 'Xbox 360', $gameInfo['platform']);
		} // baseline single quote
		if (preg_match('/^XBOX360$/i', $gameInfo['platform'])) {
			$gameInfo['platform'] = str_replace('XBOX360', 'Xbox 360', $gameInfo['platform']);
		} // baseline single quote
		if (preg_match('/^XBOXONE$/i', $gameInfo['platform'])) {
			$gameInfo['platform'] = str_replace('XBOXONE', 'Xbox One', $gameInfo['platform']);
		} // baseline single quote
		if (preg_match('/^NDS$/i', $gameInfo['platform'])) {
			$gameInfo['platform'] = str_replace('NDS', 'Nintendo DS', $gameInfo['platform']);
		} // baseline single quote
		if (preg_match('/^3DS$/i', $gameInfo['platform'])) {
			$gameInfo['platform'] = str_replace('3DS', 'Nintendo 3DS', $gameInfo['platform']);
		} // baseline single quote
		if (preg_match('/^PS2$/i', $gameInfo['platform'])) {
			$gameInfo['platform'] = str_replace('PS2', 'PlayStation2', $gameInfo['platform']);
		}
		if (preg_match('/^PS3$/i', $gameInfo['platform'])) {
			$gameInfo['platform'] = str_replace('PS3', 'PlayStation 3', $gameInfo['platform']);
		} // baseline single quote
		if (preg_match('/^PS4$/i', $gameInfo['platform'])) {
			$gameInfo['platform'] = str_replace('PS4', 'PlayStation 4', $gameInfo['platform']);
		} // baseline single quote
		if (preg_match('/^PSP$/i', $gameInfo['platform'])) {
			$gameInfo['platform'] = str_replace('PSP', 'Sony PSP', $gameInfo['platform']);
		} // baseline single quote
		if (preg_match('/^PSVITA$/i', $gameInfo['platform'])) {
			$gameInfo['platform'] = str_replace('PSVITA', 'PlayStation Vita', $gameInfo['platform']);
		} // baseline single quote
		if (preg_match('/^PSX$/i', $gameInfo['platform'])) {
			$gameInfo['platform'] = str_replace('PSX', 'PlayStation', $gameInfo['platform']);
		} // baseline single quote
		if (preg_match('/^WiiU$/i', $gameInfo['platform'])) {
			$gameInfo['platform'] = str_replace('WiiU', 'Nintendo Wii U', $gameInfo['platform']); // baseline single quote
			$gameInfo['platform'] = str_replace('WIIU', 'Nintendo Wii U', $gameInfo['platform']); // baseline single quote
		}
		if (preg_match('/^Wii$/i', $gameInfo['platform'])) {
			$gameInfo['platform'] = str_replace('Wii', 'Nintendo Wii', $gameInfo['platform']); // baseline single quote
			$gameInfo['platform'] = str_replace('WII', 'Nintendo Wii', $gameInfo['platform']); // baseline single quote
		}
		if (preg_match('/^NGC$/i', $gameInfo['platform'])) {
			$gameInfo['platform'] = str_replace('NGC', 'GameCube', $gameInfo['platform']); // baseline single quote
		}
		if (preg_match('/^N64$/i', $gameInfo['platform'])) {
			$gameInfo['platform'] = str_replace('N64', 'Nintendo 64', $gameInfo['platform']);
		} // baseline single quote
		if (preg_match('/^NES$/i', $gameInfo['platform'])) {
			$gameInfo['platform'] = str_replace('NES', 'Nintendo NES', $gameInfo['platform']);
		} // baseline single quote
		if (preg_match('/Super/i', $con['platform'])) {
			$con['platform'] = str_replace('Super Nintendo', 'SNES', $con['platform']); // baseline single quote
			$con['platform'] = str_replace('Nintendo Super NES', 'SNES', $con['platform']); // baseline single quote
		}
		// Remove Online Game Code So Titles Match Properly.
		if (preg_match('/\[Online Game Code\]/i', $con['title'])) {
			$con['title'] = str_replace(' [Online Game Code]', '', $con['title']);
		} // baseline single quote
// Basically the XBLA names contain crap, this is to reduce the title down far enough to be usable.
		if (preg_match('/xbla/i', $gameInfo['platform'])) {
			$gameInfo['title'] = substr($gameInfo['title'], 0, 10);
			$con['substr'] = $gameInfo['title'];
		}

		// This actual compares the two strings and outputs a percentage value.
		$titlepercent = $platformpercent = '';
		similar_text(strtolower($gameInfo['title']), strtolower($con['title']), $titlepercent);
		similar_text(strtolower($gameInfo['platform']), strtolower($con['platform']), $platformpercent);

		// Since Wii Ware games and XBLA have inconsistent original platforms, as long as title is 50% its ok.
		if (preg_match('/(wiiware|xbla)/i', $gameInfo['platform'])) {
			if ($titlepercent >= 50) {
				$platformpercent = 100;
			}
		}

		// If the release is DLC matching sucks, so assume anything over 50% is legit.
		if (isset($gameInfo['dlc']) && $gameInfo['dlc'] == 1) {
			if ($titlepercent >= 50) {
				$titlepercent = 100;
				$platformpercent = 100;
			}
		}

		/**
		echo("Matched: Title Percentage: $titlepercent% between " . strtolower($gameInfo['title']) . " and " . strtolower($con['title']) . ".\n");
		echo("Matched: Platform Percentage: $platformpercent% \n");
		**/

		// If the Title is less than 80% Platform must be 100% unless it is XBLA.
		if ($titlepercent < 70) {
			if ($platformpercent != 100) {
				return false;
			}
		}

		// If title is less than 80% then its most likely not a match.
		if ($titlepercent < 70) {
			similar_text(strtolower($gameInfo['title'] . ' - ' . $gameInfo['platform']), strtolower($con['title']), $titlewithplatpercent);
			if ($titlewithplatpercent < 70) {
				return false;
			}
		}

		// Platform must equal 100%.
		if ($platformpercent != 100) {
			return false;
		}

		$con['asin'] = (string)$amaz->Items->Item->ASIN;

		$con['url'] = (string)$amaz->Items->Item->DetailPageURL;
		$con['url'] = str_replace("%26tag%3Dws", "%26tag%3Dopensourceins%2D21", $con['url']);

		$con['salesrank'] = (string)$amaz->Items->Item->SalesRank;
		if ($con['salesrank'] == "") {
			$con['salesrank'] = 'null';
		}

		$con['publisher'] = (string)$amaz->Items->Item->ItemAttributes->Publisher;

		$con['esrb'] = (string)$amaz->Items->Item->ItemAttributes->ESRBAgeRating;

		$con['releasedate'] = $pdo->escapeString((string)$amaz->Items->Item->ItemAttributes->ReleaseDate);
		if ($con['releasedate'] == "''") {
			$con['releasedate'] = 'null';
		}

		$con['review'] = "";
		if (isset($amaz->Items->Item->EditorialReviews)) {
			$con['review'] = trim(strip_tags((string)$amaz->Items->Item->EditorialReviews->EditorialReview->Content));
		}

		$genreKey = -1;
		$genreName = '';
		if (isset($amaz->Items->Item->BrowseNodes) || isset($amaz->Items->Item->ItemAttributes->Genre)) {
			if (isset($amaz->Items->Item->BrowseNodes)) {
				//had issues getting this out of the browsenodes obj
				//workaround is to get the xml and load that into its own obj
				$amazGenresXml = $amaz->Items->Item->BrowseNodes->asXml();
				$amazGenresObj = simplexml_load_string($amazGenresXml);
				$amazGenres = $amazGenresObj->xpath("//Name");
				foreach ($amazGenres as $amazGenre) {
					$currName = trim($amazGenre[0]);
					if (empty($genreName)) {
						$genreMatch = $this->matchBrowseNode($currName);
						if ($genreMatch !== false) {
							$genreName = $genreMatch;
							break;
						}
					}
				}
			}

			if (empty($genreName) && isset($amaz->Items->Item->ItemAttributes->Genre)) {
				$a = (string)$amaz->Items->Item->ItemAttributes->Genre;
				$b = str_replace('-', ' ', $a);
				$tmpGenre = explode(' ', $b);
				foreach ($tmpGenre as $tg) {
					$genreMatch = $this->matchBrowseNode(ucwords($tg));
					if ($genreMatch !== false) {
						$genreName = $genreMatch;
						break;
					}
				}
			}
		}

		if (empty($genreName)) {
			$genreName = 'Unknown';
		}

		if (in_array(strtolower($genreName), $genreassoc)) {
			$genreKey = array_search(strtolower($genreName), $genreassoc);
		} else {
			$genreKey = $pdo->queryInsert(sprintf("INSERT INTO genres (`title`, `type`) VALUES (%s, %d)", $pdo->escapeString($genreName), Genres::CONSOLE_TYPE));
		}

		$con['consolegenre'] = $genreName;
		$con['consolegenreID'] = $genreKey;

		$check = $pdo->queryOneRow(sprintf('SELECT id FROM consoleinfo WHERE title = %s AND asin = %s', $pdo->escapeString($con['title']), $pdo->escapeString($con['asin'])));
		if ($check === false) {
			$consoleId = $pdo->queryInsert(
				sprintf(
					"INSERT INTO consoleinfo (title, asin, url, salesrank, platform, publisher, genreid, esrb, releasedate, review, cover, createddate, updateddate)
					VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, NOW(), NOW())",
					$pdo->escapeString($con['title']),
					$pdo->escapeString($con['asin']),
					$pdo->escapeString($con['url']),
					$con['salesrank'],
					$pdo->escapeString($con['platform']),
					$pdo->escapeString($con['publisher']),
					($con['consolegenreID'] == -1 ? "null" : $con['consolegenreID']),
					$pdo->escapeString($con['esrb']),
					$con['releasedate'],
					$pdo->escapeString(substr($con['review'], 0, 3000)),
					$con['cover']
				)
			);
		} else {
			$consoleId = $check['id'];
			$pdo->queryExec(
				sprintf(
					'UPDATE consoleinfo SET title = %s, asin = %s, url = %s, salesrank = %s, platform = %s, publisher = %s, genreid = %s, esrb = %s, releasedate = %s,
					review = %s, cover = %s, updateddate = NOW() WHERE id = %d',
					$pdo->escapeString($con['title']),
					$pdo->escapeString($con['asin']),
					$pdo->escapeString($con['url']),
					$con['salesrank'],
					$pdo->escapeString($con['platform']),
					$pdo->escapeString($con['publisher']),
					($con['consolegenreID'] == -1 ? "null" : $con['consolegenreID']),
					$pdo->escapeString($con['esrb']), $con['releasedate'],
					$pdo->escapeString(substr($con['review'], 0, 3000)),
					$con['cover'],
					$consoleId
				)
			);
		}

		if ($consoleId) {
			if ($this->echooutput) {
				$this->c->doEcho(
					$this->c->header("Added/updated game: ") .
					$this->c->alternateOver("   Title:    ") .
					$this->c->primary($con['title']) .
					$this->c->alternateOver("   Platform: ") .
					$this->c->primary($con['platform'])
				);
			}

			$con['cover'] = $ri->saveImage($consoleId, $con['coverurl'], $this->imgSavePath, 250, 250);
		} else {
			if ($this->echooutput) {
				$this->c->doEcho(
					$this->c->headerOver("Nothing to update: ") .
					$this->c->primary(
						$con['title'] .
						" (" .
						$con['platform'] .
						')'
					)
				);
			}
		}
		return $consoleId;
	}

	public function fetchAmazonProperties($title, $node)
	{
		$obj = new AmazonProductAPI($this->pubkey, $this->privkey, $this->asstag);
		try {
			$result = $obj->searchProducts($title, AmazonProductAPI::GAMES, "NODE", $node);
		} catch (Exception $e) {
			$result = false;
		}
		return $result;
	}

	public function processConsoleReleases()
	{
		$pdo = $this->pdo;
		$res = $pdo->queryDirect(sprintf('SELECT searchname, id FROM releases WHERE nzbstatus = 1 %s AND consoleinfoid IS NULL AND categoryid BETWEEN 1000 AND 1999 ORDER BY postdate DESC LIMIT %d', $this->renamed, $this->gameqty));

		if ($res->rowCount() > 0) {
			if ($this->echooutput) {
				$this->c->doEcho($this->c->header("Processing " . $res->rowCount() . ' console release(s).'));
			}

			foreach ($res as $arr) {
				$startTime = microtime(true);
				$usedAmazon = false;
				$gameInfo = $this->parseTitle($arr['searchname']);
				if ($gameInfo !== false) {

					if ($this->echooutput) {
						$this->c->doEcho(
							$this->c->headerOver('Looking up: ') .
							$this->c->primary(
								$gameInfo['title'] .
								' (' .
								$gameInfo['platform'] . ')'
							)
						);
					}

					// Check for existing console entry.
					$gameCheck = $this->getConsoleInfoByName($gameInfo['title'], $gameInfo['platform']);

					if ($gameCheck === false) {
						$gameId = $this->updateConsoleInfo($gameInfo);
						$usedAmazon = true;
						if ($gameId === false) {
							$gameId = -2;
						}
					} else {
						$gameId = $gameCheck['id'];
					}

					// Update release.
					$pdo->queryExec(sprintf('UPDATE releases SET consoleinfoid = %d WHERE id = %d', $gameId, $arr['id']));
				} else {
					// Could not parse release title.
					$pdo->queryExec(sprintf('UPDATE releases SET consoleinfoid = %d WHERE id = %d', -2, $arr['id']));

					if ($this->echooutput) {
						echo '.';
					}
				}

				// Sleep to not flood amazon.
				$diff = floor((microtime(true) - $startTime) * 1000000);
				if ($this->sleeptime * 1000 - $diff > 0 && $usedAmazon === true) {
					usleep($this->sleeptime * 1000 - $diff);
				}
			}
		} else if ($this->echooutput) {
			$this->c->doEcho($this->c->header('No console releases to process.'));
		}
	}

	function parseTitle($releasename)
	{
		$matches = '';
		$releasename = preg_replace('/\sMulti\d?\s/i', '', $releasename);
		$result = array();

		// Get name of the game from name of release.
		preg_match('/^(.+((abgx360EFNet|EFNet\sFULL|FULL\sabgxEFNet|abgx\sFULL|abgxbox360EFNet)\s|illuminatenboard\sorg|Place2(hom|us)e.net|united-forums? co uk|\(\d+\)))?(?P<title>.*?)[\.\-_ \:](v\.?\d\.\d|PAL|NTSC|EUR|USA|JP|ASIA|JAP|JPN|AUS|MULTI(\.?\d{1,2})?|PATCHED|FULLDVD|DVD5|DVD9|DVDRIP|PROPER|REPACK|RETAIL|DEMO|DISTRIBUTION|REGIONFREE|[\. ]RF[\. ]?|READ\.?NFO|NFOFIX|PSX(2PSP)?|PS[2-4]|PSP|PSVITA|WIIU|WII|X\-?BOX|XBLA|X360|3DS|NDS|N64|NGC)/i', $releasename, $matches);
		if (isset($matches['title'])) {
			$title = $matches['title'];
			// Replace dots, underscores, or brackets with spaces.
			$result['title'] = preg_replace('/(\.|_|\%20|\[|\])/', ' ', $title);
			$result['title'] = str_replace(' RF ', ' ', $result['title']);
			// Needed to add code to handle DLC Properly.
			if (preg_match('/dlc/i', $result['title'])) {
				$result['dlc'] = '1';
				if (preg_match('/Rock Band Network/i', $result['title'])) {
					$result['title'] = 'Rock Band';
				} else if (preg_match('/\-/i', $result['title'])) {
					$dlc = explode("-", $result['title']);
					$result['title'] = $dlc[0];
				} else if (preg_match('/(.*? .*?) /i', $result['title'], $dlc)) {
					$result['title'] = $dlc[0];
				}
			}
		}

		//get the platform of the release
		preg_match('/[\.\-_ ](?P<platform>XBLA|WiiWARE|N64|SNES|NES|PS[2-4]|PS 3|PSX(2PSP)?|PSP|WIIU|WII|XBOX360|XBOXONE|X\-?BOX|X360|3DS|NDS|N?GC)/i', $releasename, $matches);
		if (isset($matches['platform'])) {
			$platform = $matches['platform'];
			if (preg_match('/^N?GC$/i', $platform)) {
				$platform = 'NGC';
			}
			if (preg_match('/^PSX2PSP$/i', $platform)) {
				$platform = 'PSX';
			}
			if (preg_match('/^(XBLA)$/i', $platform)) {
				if (preg_match('/DLC/i', $title)) {
					$platform = str_replace('XBLA', 'XBOX360', $platform); // baseline single quote
				}
			}
			$browseNode = $this->getBrowseNode($platform);
			$result['platform'] = $platform;
			$result['node'] = $browseNode;
		}
		$result['release'] = $releasename;
		array_map("trim", $result);
		// Make sure we got a title and platform otherwise the resulting lookup will probably be shit. Other option is to pass the $release->categoryID here if we don't find a platform but that would require an extra lookup to determine the name. In either case we should have a title at the minimum.
		return (isset($result['title']) && !empty($result['title']) && isset($result['platform'])) ? $result : false;
	}

	function getBrowseNode($platform)
	{
		switch ($platform) {
			case 'PS2':
				$nodeId = '301712';
				break;
			case 'PS3':
				$nodeId = '14210751';
				break;
			case 'PS4':
				$nodeId = '6427814011';
				break;
			case 'PSP':
				$nodeId = '11075221';
				break;
			case 'PSVITA':
				$nodeId = '3010556011';
				break;
			case 'PSX':
				$nodeId = '294940';
				break;
			case 'WII':
			case 'Wii':
				$nodeId = '14218901';
				break;
			case 'WIIU':
			case 'WiiU':
				$nodeId = '3075112011';
				break;
			case 'XBOX360':
			case 'X360':
				$nodeId = '14220161';
				break;
			case 'XBOXONE':
				$nodeId = '6469269011';
				break;
			case 'XBOX':
			case 'X-BOX':
				$nodeId = '537504';
				break;
			case 'NDS':
				$nodeId = '11075831';
				break;
			case '3DS':
				$nodeId = '2622269011';
				break;
			case 'GC':
			case 'NGC':
				$nodeId = '541022';
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
		switch ($nodeName) {
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
