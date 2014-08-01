<?php
require_once nZEDb_LIBS . 'GiantBombAPI.php';
require_once nZEDb_LIB . 'utility' . DS . 'Utility.php';

use nzedb\db\Settings;

class Games
{
	const REQID_NONE = -3; // The Request ID was not found locally or via web lookup.
	const REQID_ZERO = -2; // The Request ID was 0.
	const REQID_NOLL = -1; // Request ID was not found via local lookup.
	const CONS_UPROC = 0; // Release has not been processed.
	const REQID_FOUND = 1; // Request ID found and release was updated.

	/**
	 * @var nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @var bool
	 */
	public $echooutput;

	/**
	 * @var array|bool|string
	 */
	public $pubkey;

	/**
	 * @var array|bool|int|string
	 */
	public $gameqty;

	/**
	 * @var array|bool|int|string
	 */
	public $sleeptime;

	/**
	 * @var string
	 */
	public $imgSavePath;

	/**
	 * @var string
	 */
	public $renamed;

	/**
	 * @var int
	 */
	public $matchpercent;

	/**
	 * @var bool
	 */
	public $maxhitrequest;

	/**
	 * @param array $options Class instances / Echo to cli.
	 */
	public function __construct(array $options = array())
	{
		$defaults = [
			'Echo'     => false,
			'ColorCLI' => null,
			'Settings' => null,
		];
		$options += $defaults;

		$this->echooutput = ($options['Echo'] && nZEDb_ECHOCLI);

		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());

		$this->pubkey = $this->pdo->getSetting('giantbombkey');
		$this->gameqty = ($this->pdo->getSetting('maxgamesprocessed') != '') ? $this->pdo->getSetting('maxgamesprocessed') : 150;
		$this->sleeptime = ($this->pdo->getSetting('amazonsleep') != '') ? $this->pdo->getSetting('amazonsleep') : 1000;
		$this->imgSavePath = nZEDb_COVERS . 'games' . DS;
		$this->renamed = '';
		$this->matchpercent = 60;
		$this->maxhitrequest = false;
		if ($this->pdo->getSetting('lookupgames') == 2) {
			$this->renamed = 'AND isrenamed = 1';
		}
		//$this->cleangames = ($this->pdo->getSetting('lookupgames') == 2) ? 'AND isrenamed = 1' : '';
	}

	public function getgamesinfo($id)
	{
		return $this->pdo->queryOneRow(
			sprintf("
				SELECT gamesinfo.*, genres.title AS genres
				FROM gamesinfo
				LEFT OUTER JOIN genres ON genres.id = gamesinfo.genreid
				WHERE gamesinfo.id = %d",
				$id
			)
		);
	}

	public function getgamesinfoByName($title, $platform)
	{
		return $this->pdo->queryOneRow(
			sprintf("
				SELECT *
				FROM gamesinfo
				WHERE title LIKE %s
				AND platform LIKE %s",
				$this->pdo->escapeString("%" . $title . "%"),
				$this->pdo->escapeString("%" . $platform . "%")
			)
		);
	}

	public function getRange($start, $num)
	{
		return $this->pdo->query(
			sprintf(
				"SELECT * FROM gamesinfo ORDER BY createddate DESC %s",
				($start === false ? '' : 'LIMIT ' . $num . ' OFFSET ' . $start)
			)
		);
	}

	public function getCount()
	{
		$res = $this->pdo->queryOneRow("SELECT COUNT(id) AS num FROM gamesinfo");
		return ($res === false ? 0 : $res["num"]);
	}

	public function getgamesCount($cat, $maxage = -1, $excludedcats = array())
	{
		$catsrch = "";
		if (count($cat) > 0 && $cat[0] != -1) {
			$catsrch = " (";
			$categ = new Category(['Settings' => $this->pdo]);
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

		$res = $this->pdo->queryOneRow(
			sprintf("
				SELECT COUNT(DISTINCT r.gamesinfo_id) AS num
				FROM releases r
				INNER JOIN gamesinfo con ON con.id = r.gamesinfo_id
				WHERE r.nzbstatus = 1
				AND con.title != ''
				AND con.cover = 1
				AND r.passwordstatus <= (SELECT value FROM settings WHERE setting='showpasswordedrelease')
				AND %s %s %s %s",
				$this->getBrowseBy(),
				$catsrch,
				($maxage > 0 ? sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxage) : ''),
				(count($excludedcats) > 0 ? " AND r.categoryid NOT IN (" . implode(",", $excludedcats) . ")" : '')
			)
		);

		return ($res === false ? 0 : $res["num"]);
	}

	public function getgamesRange($cat, $start, $num, $orderby, $maxage = -1, $excludedcats = array())
	{
		$browseby = $this->getBrowseBy();

		if ($start === false) {
			$limit = "";
		} else {
			$limit = " LIMIT " . $num . " OFFSET " . $start;
		}
		$catsrch = "";
		if (count($cat) > 0 && $cat[0] != -1) {
			$catsrch = " (";
			$categ = new Category(['Settings' => $this->pdo]);
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
			$maxage = sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxage);
		} else {
			$maxage = '';
		}

		$exccatlist = "";
		if (count($excludedcats) > 0) {
			$exccatlist = " AND r.categoryid NOT IN (" . implode(",", $excludedcats) . ")";
		}

		$order = $this->getgamesOrder($orderby);

		return $this->pdo->query(
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
				. "con.*, YEAR (con.releasedate) as year, r.gamesinfo_id, groups.name AS group_name,
				rn.id as nfoid FROM releases r "
				. "LEFT OUTER JOIN groups ON groups.id = r.group_id "
				. "LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id "
				. "INNER JOIN gamesinfo con ON con.id = r.gamesinfo_id "
				. "WHERE r.nzbstatus = 1 AND con.cover = 1 AND con.title != '' AND "
				. "r.passwordstatus <= (SELECT value FROM settings WHERE setting='showpasswordedrelease') AND %s %s %s %s "
				. "GROUP BY con.id ORDER BY %s %s" . $limit,
				$browseby,
				$catsrch,
				$maxage,
				$exccatlist,
				$order[0],
				$order[1]
			)
		);
	}

	public function getgamesOrder($orderby)
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

	public function getgamesOrdering()
	{
		return array(
			'title_asc', 'title_desc', 'posted_asc', 'posted_desc', 'size_asc', 'size_desc',
			'files_asc', 'files_desc', 'stats_asc', 'stats_desc', 'platform_asc', 'platform_desc',
			'releasedate_asc', 'releasedate_desc', 'genre_asc', 'genre_desc'
		);
	}

	public function getBrowseByOptions()
	{
		return array('platform' => 'platform', 'title' => 'title', 'genre' => 'genreID', 'year' => 'year');
	}

	public function getBrowseBy()
	{
		$browseby = ' ';
		$browsebyArr = $this->getBrowseByOptions();
		$like = 'LIKE';

		foreach ($browsebyArr as $bbk => $bbv) {
			if (isset($_REQUEST[$bbk]) && !empty($_REQUEST[$bbk])) {
				$bbs = stripslashes($_REQUEST[$bbk]);
				if ($bbk === 'year') {
					$browseby .= 'YEAR (con.releasedate) ' . $like . ' (' . $this->pdo->escapeString('%' . $bbs . '%') . ') AND ';
				} else {
					$browseby .= 'con.' . $bbv . ' ' . $like . ' (' . $this->pdo->escapeString('%' . $bbs . '%') . ') AND ';
				}
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
			$newArr[] =
				'<a href="' . WWW_TOP . '/games?' . $field . '=' . urlencode($ta) . '" title="' .
				$ta . '">' . $ta . '</a>';
			$i++;
		}

		return implode(', ', $newArr);
	}

	public function update($id, $title, $asin, $url, $salesrank, $platform, $publisher, $releasedate, $esrb, $cover, $genreID)
	{

		$this->pdo->queryExec(
			sprintf("
				UPDATE gamesinfo
				SET
					title = %s, asin = %s, url = %s, salesrank = %s, platform = %s, publisher = %s,
					releasedate= %s,esrb = %s, cover = %d, genreid = %d, updateddate = NOW()
				WHERE id = %d",
				$this->pdo->escapeString($title),
				$this->pdo->escapeString($asin),
				$this->pdo->escapeString($url),
				$salesrank,
				$this->pdo->escapeString($platform),
				$this->pdo->escapeString($publisher),
				$this->pdo->escapeString($releasedate),
				$this->pdo->escapeString($esrb),
				$cover,
				$genreID,
				$id
			)
		);
	}

	/**
	 * Process each game, updating game information from Giantbomb
	 *
	 * @param $gameInfo
	 *
	 * @return bool
	 */
	public function updategamesinfo($gameInfo)
	{
		$gen = new Genres(['Settings' => $this->pdo]);
		$ri = new ReleaseImage($this->pdo);

		$con = array();
		$ggameid = $this->fetchgiantbombgameid($gameInfo['title']);
		if($this->maxhitrequest === true){
		return false;
		}
		$gb = $this->fetchGiantBombArray($ggameid);
		$gb = $gb['results'];
		if (!is_array($gb)) {
			return false;
		}

		// Load genres.
		$defaultGenres = $gen->getGenres(Genres::GAME_TYPE);
		$genreassoc = array();
		foreach ($defaultGenres as $dg) {
			$genreassoc[$dg['id']] = strtolower($dg['title']);
		}

		// Get game properties.
		$con['coverurl'] = (string)$gb['image']['super_url'];
		if ($con['coverurl'] != "") {
			$con['cover'] = 1;
		} else {
			$con['cover'] = 0;
		}

		$con['title'] = (string)$gb['name'];
		if (empty($con['title'])) {
			$con['title'] = $gameInfo['title'];
		}
		if (is_array($gb['platforms'])) {
			while (list($key) = each($gb['platforms'])) {
				if ($gb['platforms'][$key]['id'] == $gameInfo['node']) {
					$con['platform'] = (string)$gb['platforms'][$key]['name'];
				}
			}
		}

		if (empty($con['platform'])) {
			$con['platform'] = $gameInfo['platform'];
		}

		// Beginning of Recheck Code.
		// This is to verify the result back from amazon was at least somewhat related to what was intended.
		// Some of the platforms don't match Amazon's exactly. This code is needed to facilitate rechecking.
		if (preg_match('/^Pc$/i', $gameInfo['platform'])) {
			$gameInfo['platform'] = str_replace('Pc', 'PC', $gameInfo['platform']);
		} // baseline single quote
		if (preg_match('/^Mac$/i', $gameInfo['platform'])) {
			$gameInfo['platform'] = str_replace('Mac', 'MAC', $gameInfo['platform']);
		} // baseline single quote

		// Remove Online Game Code So Titles Match Properly.
		if (preg_match('/\[Online Game Code\]/i', $con['title'])) {
			$con['title'] = str_replace(' [Online Game Code]', '', $con['title']);
		} // baseline single quote
		// Basically the XBLA names contain crap, this is to reduce the title down far enough to be usable.

		// This actual compares the two strings and outputs a percentage value.
		$titlepercent = $platformpercent = '';
		similar_text(strtolower($gameInfo['title']), strtolower($con['title']), $titlepercent);
		similar_text(strtolower($gameInfo['platform']), strtolower($con['platform']), $platformpercent);

		// If the release is DLC matching sucks, so assume anything over 50% is legit.
		if (isset($gameInfo['dlc']) && $gameInfo['dlc'] == 1) {
			if ($titlepercent >= 50) {
				$titlepercent = 100;
				$platformpercent = 100;
			}
		}

		/*
		echo("Matched: Title Percentage: $titlepercent% between " . strtolower($gameInfo['title']) . " and " . strtolower($con['title']) . ".\n");
		echo("Matched: Platform Percentage: $platformpercent% \n");
		*/

		// If the Title is less than 60% Platform must be 100% unless it is XBLA.
		if ($titlepercent < $this->matchpercent) {
			if ($platformpercent != 100) {
				return false;
			}
		}

		// If title is less than 70% then its most likely not a match.
		if ($titlepercent < $this->matchpercent) {
			similar_text(strtolower($gameInfo['title'] . ' - ' . $gameInfo['platform']), strtolower($con['title']), $titlewithplatpercent);
			if ($titlewithplatpercent < 70) {
				return false;
			}
		}

		// Platform must equal 100%.
		if ($platformpercent != 100) {
			return false;
		}

		$con['asin'] = $ggameid;

		$con['url'] = (string)$gb['site_detail_url'];
		$con['url'] = str_replace("%26tag%3Dws", "%26tag%3Dopensourceins%2D21", $con['url']);

		if (is_array($gb['publishers'])) {
			while (list($key) = each($gb['publishers'])) {
				if ($key == 0) {
					$con['publisher'] = (string)$gb['publishers'][$key]['name'];
				}
			}
		} else {
			$con['publisher'] = "Unknown";
		}

		if (is_array($gb['original_game_rating'])) {
			$con['esrb'] = (string)$gb['original_game_rating'][0]['name'];
		} else {
			$con['esrb'] = (string)$gb['original_game_rating']['name'];
		}
		$con['releasedate'] = $this->pdo->escapeString((string)$gb['original_release_date']);
		if ($con['releasedate'] == "''") {
			$con['releasedate'] = 'null';
		}

		$con['review'] = "";
		if (isset($gb['description'])) {
			$con['review'] = trim(strip_tags((string)$gb['description']));
		}

		$genreName = '';

		if (empty($genreName) && isset($gb['genres'][0]['name'])) {
			$a = (string)$gb['genres'][0]['name'];
			$b = str_replace('-', ' ', $a);
			$tmpGenre = explode(',', $b);
			foreach ($tmpGenre as $tg) {
				$genreMatch = $this->matchBrowseNode(ucwords($tg));
				if ($genreMatch !== false) {
					$genreName = (string)$genreMatch;
					break;
				}
			}
		}

		if (empty($genreName)) {
			$genreName = 'Unknown';
		}
		if (in_array(strtolower($genreName), $genreassoc)) {
			$genreKey = array_search(strtolower($genreName), $genreassoc);
		} else {
			$genreKey = $this->pdo->queryInsert(
				sprintf("
					INSERT INTO genres (title, type)
					VALUES (%s, %d)",
					$this->pdo->escapeString($genreName),
					Genres::GAME_TYPE
				)
			);
		}

		$con['gamesgenre'] = $genreName;
		$con['gamesgenreID'] = $genreKey;

		$check = $this->pdo->queryOneRow(
			sprintf('
				SELECT id
				FROM gamesinfo
				WHERE title = %s
				AND asin = %s',
				$this->pdo->escapeString($con['title']),
				$this->pdo->escapeString($con['asin'])
			)
		);
		if ($check === false) {
			$gamesId = $this->pdo->queryInsert(
				sprintf("
					INSERT INTO gamesinfo
						(title, asin, url, platform, publisher, genreid, esrb, releasedate, review, cover, createddate, updateddate)
					VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %d, NOW(), NOW())",
					$this->pdo->escapeString($con['title']),
					$this->pdo->escapeString($con['asin']),
					$this->pdo->escapeString($con['url']),
					$this->pdo->escapeString($con['platform']),
					$this->pdo->escapeString($con['publisher']),
					($con['gamesgenreID'] == -1 ? "null" : $con['gamesgenreID']),
					$this->pdo->escapeString($con['esrb']),
					$con['releasedate'],
					$this->pdo->escapeString(substr($con['review'], 0, 3000)),
					$con['cover']
				)
			);
		} else {
			$gamesId = $check['id'];
			$this->pdo->queryExec(
				sprintf('
					UPDATE gamesinfo
					SET
						title = %s, asin = %s, url = %s, platform = %s, publisher = %s, genreid = %s,
						esrb = %s, releasedate = %s, review = %s, cover = %s, updateddate = NOW()
					WHERE id = %d',
					$this->pdo->escapeString($con['title']),
					$this->pdo->escapeString($con['asin']),
					$this->pdo->escapeString($con['url']),
					$this->pdo->escapeString($con['platform']),
					$this->pdo->escapeString($con['publisher']),
					($con['gamesgenreID'] == -1 ? "null" : $con['gamesgenreID']),
					$this->pdo->escapeString($con['esrb']),
					$con['releasedate'],
					$this->pdo->escapeString(substr($con['review'], 0, 3000)),
					$con['cover'],
					$gamesId
				)
			);
		}

		if ($gamesId) {
			if ($this->echooutput) {
				$this->pdo->log->doEcho(
					$this->pdo->log->header("Added/updated game: ") .
					$this->pdo->log->alternateOver("   Title:    ") .
					$this->pdo->log->primary($con['title']) .
					$this->pdo->log->alternateOver("   Platform: ") .
					$this->pdo->log->primary($con['platform'])
				);
			}
			$con['cover'] = $ri->saveImage($gamesId, $con['coverurl'], $this->imgSavePath, 250, 250);
		} else {
			if ($this->echooutput) {
				$this->pdo->log->doEcho(
					$this->pdo->log->headerOver("Nothing to update: ") .
					$this->pdo->log->primary($con['title'] . " (" . $con['platform'] . ')' )
				);
			}
		}

		return $gamesId;
	}

	/**
	 * Get Giantbomb search results
	 *
	 * @param $gameid
	 *
	 * @return bool|mixed Json Array if no result False
	 */

	public function fetchGiantBombArray($gameid)
	{
		$obj = new GiantBomb($this->pubkey);
		try {
			$fields = array(
				"deck", "description", "original_game_rating", "api_detail_url", "image", "genres",
				"name", "platforms", "publishers", "original_release_date", "reviews",
				"site_detail_url"
			);
			$result = json_decode(json_encode($obj->game($gameid, $fields)), true);
		} catch (Exception $e) {
			$result = false;
		}

		return $result;
	}

	/**
	 * Retrieve Giantbomb game ID for api requests
	 *
	 * @param $title
	 *
	 * @return bool|mixed - Json Array if game was found false if nothing
	 */
	public function fetchgiantbombgameid($title)
	{
		$obj = new GiantBomb($this->pubkey);
		try {
			$result = json_decode(json_encode($obj->search($title, '', 1)), true);
			// We hit the maximum request.
			if(empty($result)){
			$this->maxhitrequest = true;
			$result = false;
			}
			if (!is_array($result['results']) || (int) $result['number_of_total_results'] === 0) {
				$result = false;
			} else {
				$result = $result['results'][0]['id'];
			}
		} catch (Exception $e) {
			$result = false;
		}

		return $result;
	}

	public function processGamesReleases()
	{
		$res = $this->pdo->queryDirect(
			sprintf('
				SELECT searchname, id
				FROM releases
				WHERE nzbstatus = 1 %s
				AND gamesinfo_id = 0
				AND categoryid = 4050
				ORDER BY postdate DESC
				LIMIT %d',
				$this->renamed,
				$this->gameqty
			)
		);

		if ($res instanceof Traversable && $res->rowCount() > 0) {
			if ($this->echooutput) {
				$this->pdo->log->doEcho($this->pdo->log->header("Processing " . $res->rowCount() . ' games release(s).'));
			}

			foreach ($res as $arr) {

				// Reset maxhitrequest
				$this->maxhitrequest = false;
				$startTime = microtime(true);
				$usedgb = false;
				$gameInfo = $this->parseTitle($arr['searchname']);
				if ($gameInfo !== false) {

					if ($this->echooutput) {
						$this->pdo->log->doEcho(
							$this->pdo->log->headerOver('Looking up: ') .
							$this->pdo->log->primary($gameInfo['title'] . ' (' . $gameInfo['platform'] . ')' )
						);
					}

					// Check for existing games entry.
					$gameCheck = $this->getgamesinfoByName($gameInfo['title'], $gameInfo['platform']);

					if ($gameCheck === false) {
						$gameId = $this->updategamesinfo($gameInfo);
						$usedgb = true;
						if ($gameId === false) {
							$gameId = -2;

						// Leave gamesinfo_id 0 to parse again
						if($this->maxhitrequest === true){
							$gameId = 0;
						}
						}

					} else {
						$gameId = $gameCheck['id'];
					}
					//$gameId = null;
					// Update release.
					$this->pdo->queryExec(sprintf('UPDATE releases SET gamesinfo_id = %d WHERE id = %d', $gameId, $arr['id']));
				} else {
					// Could not parse release title.
					$this->pdo->queryExec(sprintf('UPDATE releases SET gamesinfo_id = %d WHERE id = %d', -2, $arr['id']));

					if ($this->echooutput) {
						echo '.';
					}
				}

				// Sleep to not flood amazon.
				$diff = floor((microtime(true) - $startTime) * 1000000);
				if ($this->sleeptime * 1000 - $diff > 0 && $usedgb === true) {
					usleep($this->sleeptime * 1000 - $diff);
				}
			}
		} else {
			if ($this->echooutput) {
				$this->pdo->log->doEcho($this->pdo->log->header('No games releases to process.'));
			}
		}
	}

	/**
	 * Parse the game release title
	 *
	 * @param string $releasename
	 *
	 * @return array|bool
	 */
	public function parseTitle($releasename)
	{
		// Get name of the game from name of release.
		if (preg_match(
			'/^(.+((EFNet|EFNet\sFULL|FULL\sabgxEFNet|abgx\sFULL|abgxbox360EFNet)\s|illuminatenboard\sorg|' .
			'Place2(hom|us)e.net|united-forums? co uk|\(\d+\)))?(?P<title>.*?)[\.\-_ \:](v\.?\d\.\d|RIP|ADDON|' .
			'EUR|USA|JP|ASIA|JAP|JPN|AUS|MULTI(\.?\d{1,2})?|PATCHED|FULLDVD|DVD5|DVD9|DVDRIP|\(GAMES\)\s*\(C\)|PROPER|REPACK|RETAIL|' .
			'DEMO|DISTRIBUTION|BETA|REGIONFREE|READ\.?NFO|NFOFIX|Update|' .
			// Group names, like Reloaded, CPY, Razor1911, etc
			'[a-z0-9]{2,}$)/i',
			preg_replace('/\sMulti\d?\s/i', '', $releasename),
			$matches)
		) {
			// Replace dots, underscores, or brackets with spaces.
			$result = array();
			$result['title'] = str_replace(' RF ', ' ', preg_replace('/(\.|_|\%20|\[|\])/', ' ', $matches['title']));
			// Needed to add code to handle DLC Properly.
			if (stripos($result['title'], 'dlc') !== false) {
				$result['dlc'] = '1';
				if (stripos($result['title'], 'Rock Band Network') !== false) {
					$result['title'] = 'Rock Band';
				} else if (stripos($result['title'], '-') !== false) {
					$dlc = explode("-", $result['title']);
					$result['title'] = $dlc[0];
				} else if (preg_match('/(.*? .*?) /i', $result['title'], $dlc)) {
					$result['title'] = $dlc[0];
				}
			}

			//get the platform of the release
			if (preg_match('/[\.\-_ ](?P<platform>MAC|MACOSX)/i', $releasename, $matches)) {
				$platform = $matches['platform'];
				if (preg_match('/^MAC$/i', $platform)) {
					$platform = 'MAC';
				} else {
					$platform = 'MACOSX';
				}
			} else {
				$platform = "PC";
			}

			$browseNode = $this->getBrowseNode($platform);
			$result['platform'] = $platform;
			$result['node'] = $browseNode;
			$result['release'] = $releasename;

			// Other option is to pass the $release->categoryID here if we don't find a platform but that would require an
			// extra lookup to determine the name. In either case we should have a title at the minimum.
			return array_map("trim", $result);
		}

		return false;
	}

	/**
	 * Set the Giantbomb Category ID #
	 *
	 * @param $platform
	 *
	 * @return string
	 */
	protected function getBrowseNode($platform)
	{
		switch ($platform) {
			case 'PC':
				$nodeId = '94';
				break;
			case 'MAC':
				$nodeId = '17';
				break;
			default:
				$nodeId = '94';
				break;
		}

		return $nodeId;
	}

	/**
	 * See if genre name exists
	 *
	 * @param $nodeName
	 *
	 * @return bool|string
	 */
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

		return ($str !== '') ? $str : false;
	}
}
