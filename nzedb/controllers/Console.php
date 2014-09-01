<?php
require_once nZEDb_LIBS . 'AmazonProductAPI.php';
require_once nZEDb_LIB . 'utility' . DS . 'Utility.php';

use nzedb\db\Settings;

class Console
{
	const CONS_UPROC = 0; // Release has not been processed.
	const CONS_NTFND = -2;

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
	 * @var array|bool|string
	 */
	public $privkey;

	/**
	 * @var array|bool|string
	 */
	public $asstag;

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
	 * @param array $options Class instances / Echo to cli.
	 */
	public function __construct(array $options = array())
	{
		$defaults = [
			'Echo'     => false,
			'Settings' => null,
		];
		$options += $defaults;

		$this->echooutput = ($options['Echo'] && nZEDb_ECHOCLI);
		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());

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
	}

	public function getConsoleInfo($id)
	{
		return $this->pdo->queryOneRow(
			sprintf(
				"SELECT consoleinfo.*, genres.title AS genres FROM consoleinfo LEFT OUTER JOIN genres ON genres.id = consoleinfo.genre_id WHERE consoleinfo.id = %d ",
				$id
			)
		);
	}

	public function getConsoleInfoByName($title, $platform)
	{
		return $this->pdo->queryOneRow(
			sprintf(
				"SELECT * FROM consoleinfo WHERE title %s AND platform %s",
				$this->pdo->likeString($title, true, true),
				$this->pdo->likeString($platform, true, true)
			)
		);
	}

	public function getRange($start, $num)
	{
		return $this->pdo->query(
			sprintf(
				"SELECT * FROM consoleinfo ORDER BY createddate DESC %s",
				($start === false ? '' : ('LIMIT ' . $num . ' OFFSET ' . $start))
			)
		);
	}

	public function getCount()
	{
		$res = $this->pdo->queryOneRow("SELECT COUNT(id) AS num FROM consoleinfo");
		return ($res === false ? 0 : $res['num']);
	}

	public function getConsoleCount($cat, $maxage = -1, $excludedcats = array())
	{
		$catsrch = '';
		if (count($cat) > 0 && $cat[0] != -1) {
			$catsrch = (new Category(['Settings' => $this->pdo]))->getCategorySearch($cat);
		}

		$res = $this->pdo->queryOneRow(
			sprintf("
				SELECT COUNT(DISTINCT r.consoleinfoid) AS num
				FROM releases r
				INNER JOIN consoleinfo con ON con.id = r.consoleinfoid AND con.title != '' AND con.cover = 1
				WHERE r.nzbstatus = 1
				AND r.passwordstatus <= (SELECT value FROM settings WHERE setting='showpasswordedrelease')
				AND %s %s %s %s",
				$this->getBrowseBy(),
				$catsrch,
				($maxage > 0 ? sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxage) : ''),
				(count($excludedcats) > 0 ? (' AND r.categoryid NOT IN (' . implode(',', $excludedcats) . ')') : '')
			)
		);
		return ($res === false ? 0 : $res["num"]);
	}

	public function getConsoleRange($cat, $start, $num, $orderby, $excludedcats = array())
	{

		$browseby = $this->getBrowseBy();

		if ($start === false) {
			$limit = "";
		} else {
			$limit = " LIMIT " . $num . " OFFSET " . $start;
		}

		$catsrch = '';
		if (count($cat) > 0 && $cat[0] != -1) {
			$catsrch = (new Category(['Settings' => $this->pdo]))->getCategorySearch($cat);
		}

		$exccatlist = "";
		if (count($excludedcats) > 0) {
			$exccatlist = " AND r.categoryid NOT IN (" . implode(",", $excludedcats) . ")";
		}

		$order = $this->getConsoleOrder($orderby);
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
				. "con.*, r.consoleinfoid, groups.name AS group_name, rn.id as nfoid FROM releases r "
				. "LEFT OUTER JOIN groups ON groups.id = r.group_id "
				. "LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id "
				. "INNER JOIN consoleinfo con ON con.id = r.consoleinfoid "
				. "WHERE r.nzbstatus = 1 AND con.title != '' AND "
				. "r.passwordstatus <= (SELECT value FROM settings WHERE setting='showpasswordedrelease') AND %s %s
				%s "
				. "GROUP BY con.id ORDER BY %s %s" . $limit, $browseby, $catsrch, $exccatlist, $order[0], $order[1]
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
				$orderfield = 'con.genre_id';
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
		return array('platform' => 'platform', 'title' => 'title', 'genre' => 'genre_id');
	}

	public function getBrowseBy()
	{
		$browseby = ' ';
		$browsebyArr = $this->getBrowseByOptions();
		$like = 'LIKE';
		foreach ($browsebyArr as $bbk => $bbv) {
			if (isset($_REQUEST[$bbk]) && !empty($_REQUEST[$bbk])) {
				$bbs = stripslashes($_REQUEST[$bbk]);
				$browseby .= 'con.' . $bbv . ' ' . $like . ' (' . $this->pdo->escapeString('%' . $bbs . '%') . ') AND ';
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

	public function update($id, $title, $asin, $url, $salesrank, $platform, $publisher, $releasedate, $esrb, $cover, $genreID, $review = 'review')
	{
		$this->pdo->queryExec(
			sprintf("
				UPDATE consoleinfo
				SET
					title = %s, asin = %s, url = %s, salesrank = %s, platform = %s, publisher = %s,
					releasedate= %s, esrb = %s, cover = %d, genre_id = %d, review = %s, updateddate = NOW()
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
				($review == 'review' ? $review : $this->pdo->escapeString(substr($review, 0, 3000))),
				$id
			)
		);
	}

	public function updateConsoleInfo($gameInfo)
	{
		$consoleId = self::CONS_NTFND;

		$amaz = $this->fetchAmazonProperties($gameInfo['title'], $gameInfo['node']);

		if ($amaz) {

			$gameInfo['platform'] = $this->_replacePlatform($gameInfo['platform']);

			$con = $this->_setConBeforeMatch($amaz, $gameInfo);

			// Basically the XBLA names contain crap, this is to reduce the title down far enough to be usable.
			if (stripos('xbla', $gameInfo['platform']) !== false) {
				$gameInfo['title'] = substr($gameInfo['title'], 0, 10);
				$con['substr'] = $gameInfo['title'];
			}

			if ($this->_matchConToGameInfo($gameInfo, $con) === true) {

				$con += $this->_setConAfterMatch($amaz);
				$con += $this->_matchGenre($amaz);

				// Set covers properties
				$con['coverurl'] = (string)$amaz->Items->Item->LargeImage->URL;

				if ($con['coverurl'] != "") {
					$con['cover'] = 1;
				} else {
					$con['cover'] = 0;
				}

				$consoleId = $this->_updateConsoleTable($con);

				if ($this->echooutput) {
					if ($consoleId !== -2) {
						$this->pdo->log->doEcho(
							$this->pdo->log->header("Added/updated game: ") .
							$this->pdo->log->alternateOver("   Title:    ") .
							$this->pdo->log->primary($con['title']) .
							$this->pdo->log->alternateOver("   Platform: ") .
							$this->pdo->log->primary($con['platform']) .
							$this->pdo->log->alternateOver("   Genre: ") .
							$this->pdo->log->primary($con['consolegenre'])
						);
					}
				}
			}
		}
		return $consoleId;
	}

	protected function _matchConToGameInfo($gameInfo = array(), $con = array())
	{
		$matched = false;

		// This actual compares the two strings and outputs a percentage value.
		$titlepercent = $platformpercent = '';

		//Remove import tags from console title for match
		$con['title'] = trim(preg_replace('/(\[|\().{2,} import(\]|\))$/i', '', $con['title']));

		similar_text(strtolower($gameInfo['title']), strtolower($con['title']), $titlepercent);
		similar_text(strtolower($gameInfo['platform']), strtolower($con['platform']), $platformpercent);

		if (nZEDb_DEBUG) {
			echo(PHP_EOL ."Matched: Title Percentage 1: $titlepercent% between " . $gameInfo['title'] . " and " . $con['title'] . PHP_EOL);
		}

		// Since Wii Ware games and XBLA have inconsistent original platforms, as long as title is 50% its ok.
		if (preg_match('/wiiware|xbla/i', trim($gameInfo['platform'])) && $titlepercent >= 50) {
			$titlepercent = 100;
			$platformpercent = 100;
		}

		// If the release is DLC matching will be difficult, so assume anything over 50% is legit.
		if (isset($gameInfo['dlc']) && $gameInfo['dlc'] == 1 && $titlepercent >= 50) {
			$titlepercent = 100;
			$platformpercent = 100;
		}

		if ($titlepercent < 70) {
			$gameInfo['title'] .= ' - ' . $gameInfo['platform'];
			similar_text(strtolower($gameInfo['title']), strtolower($con['title']), $titlepercent);
		}

		if (nZEDb_DEBUG) {
			echo("Matched: Title Percentage 2: $titlepercent% between " . $gameInfo['title'] . " and " . $con['title'] . PHP_EOL);
			echo("Matched: Platform Percentage: $platformpercent% between " . $gameInfo['platform'] . " and " . $con['platform'] . PHP_EOL);
		}

		// Platform must equal 100%.
		if ($platformpercent == 100 && $titlepercent >= 70) {
			$matched = true;
		}

		return $matched;
	}

	protected function _setConBeforeMatch($amaz, $gameInfo)
	{
		$con['platform'] = (string)$amaz->Items->Item->ItemAttributes->Platform;
		if (empty($con['platform'])) {
			$con['platform'] = $gameInfo['platform'];
		}

		if (stripos('Super', $con['platform']) !== false) {
			$con['platform'] = 'SNES';
		}

		$con['title'] = (string)$amaz->Items->Item->ItemAttributes->Title;
		if (empty($con['title'])) {
			$con['title'] = $gameInfo['title'];
		}

		// Remove Download strings
		$dlStrings = array(' [Online Game Code]', ' [Download]', ' [Digital Code]', ' [Digital Download]');
		$con['title'] = str_ireplace($dlStrings, '', $con['title']);
		return $con;
	}

	protected function _setConAfterMatch($amaz = array())
	{
		$con['asin'] = (string)$amaz->Items->Item->ASIN;

		$con['url'] = (string)$amaz->Items->Item->DetailPageURL;
		$con['url'] = str_replace("%26tag%3Dws", "%26tag%3Dopensourceins%2D21", $con['url']);

		$con['salesrank'] = (string)$amaz->Items->Item->SalesRank;
		if ($con['salesrank'] == "") {
			$con['salesrank'] = "null";
		}

		$con['publisher'] = (string)$amaz->Items->Item->ItemAttributes->Publisher;
		$con['esrb'] = (string)$amaz->Items->Item->ItemAttributes->ESRBAgeRating;
		$con['releasedate'] = (string)$amaz->Items->Item->ItemAttributes->ReleaseDate;

		if(!isset($con['releasedate'])){
			$con['releasedate'] = "";
		}

		if ($con['releasedate'] == "''") {
			$con['releasedate'] = "";
		}

		$con['review'] = "";
		if (isset($amaz->Items->Item->EditorialReviews)) {
			$con['review'] = trim(strip_tags((string)$amaz->Items->Item->EditorialReviews->EditorialReview->Content));
		}
		return $con;
	}

	protected function _matchGenre($amaz = array())
	{

		$genreName = '';

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

		if ($genreName == '' && isset($amaz->Items->Item->ItemAttributes->Genre)) {
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

		if (empty($genreName)) {
			$genreName = 'Unknown';
		}

		$genreKey = $this->_getGenreKey($genreName);

		return array('consolegenre' => $genreName, 'consolegenreID' => $genreKey);
	}

	protected function _getGenreKey($genreName)
	{
		$genreassoc = $this->_loadGenres();

		if (in_array(strtolower($genreName), $genreassoc)) {
			$genreKey = array_search(strtolower($genreName), $genreassoc);
		} else {
			$genreKey = $this->pdo->queryInsert(
								sprintf("
									INSERT INTO genres (title, type)
									VALUES (%s, %d)",
									$this->pdo->escapeString($genreName),
									Genres::CONSOLE_TYPE
								)
			);
		}
		return $genreKey;
	}

	protected function _loadGenres()
	{
		$gen = new Genres(['Settings' => $this->pdo]);

		$defaultGenres = $gen->getGenres(Genres::CONSOLE_TYPE);
		$genreassoc = array();
		foreach ($defaultGenres as $dg) {
			$genreassoc[$dg['id']] = strtolower($dg['title']);
		}
		return $genreassoc;
	}

	/** This function sets the platform retrieved
	 *  from the release to the Amazon equivalent
	 *
	 * @param string $platform
	 *
	**/
	protected function _replacePlatform($platform)
	{
		switch (strtoupper($platform)) {

			case 'X360':
			case 'XBOX360':
				$platform = 'Xbox 360';
				break;
			case 'XBOXONE':
				$platform = 'Xbox One';
				break;
			case 'DSi':
			case 'NDS':
				$platform = 'Nintendo DS';
				break;
			case '3DS':
				$platform = 'Nintendo 3DS';
				break;
			case 'PS2':
				$platform = 'PlayStation2';
				break;
			case 'PS3':
				$platform = 'PlayStation 3';
				break;
			case 'PS4':
				$platform = 'PlayStation 4';
				break;
			case 'PSP':
				$platform = 'Sony PSP';
				break;
			case 'PSVITA':
				$platform = 'PlayStation Vita';
				break;
			case 'PSX':
			case 'PSX2PSP':
				$platform = 'PlayStation';
				break;
			case 'WIIU':
				$platform = 'Nintendo Wii U';
				break;
			case 'WII':
				$platform = 'Nintendo Wii';
				break;
			case 'NGC':
				$platform = 'GameCube';
				break;
			case 'N64':
				$platform = 'Nintendo 64';
				break;
			case 'NES':
				$platform = 'Nintendo NES';
				break;
			case 'SUPER NINTENDO':
			case 'NINTENDO SUPER NES':
			case 'SNES':
				$platform = 'SNES';
				break;
		}
		return $platform;
	}

	protected function _updateConsoleTable($con = array())
	{
		$ri = new ReleaseImage($this->pdo);

		$check = $this->pdo->queryOneRow(
						sprintf('
							SELECT id
							FROM consoleinfo
							WHERE asin = %s',
							$this->pdo->escapeString($con['asin'])
						)
		);

		if ($check === false) {
			$consoleId = $this->pdo->queryInsert(
				sprintf(
					"INSERT INTO consoleinfo (title, asin, url, salesrank, platform, publisher, genre_id, esrb, releasedate, review, cover, createddate, updateddate)
					VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, NOW(), NOW())",
					$this->pdo->escapeString($con['title']),
					$this->pdo->escapeString($con['asin']),
					$this->pdo->escapeString($con['url']),
					$con['salesrank'],
					$this->pdo->escapeString($con['platform']),
					$this->pdo->escapeString($con['publisher']),
					($con['consolegenreID'] == -1 ? "null" : $con['consolegenreID']),
					$this->pdo->escapeString($con['esrb']),
					($con['releasedate'] != "" ? $this->pdo->escapeString($con['releasedate']) : "null"),
					$this->pdo->escapeString(substr($con['review'], 0, 3000)),
					$con['cover']
				)
			);
			if($con['cover'] === 1){
			$con['cover'] = $ri->saveImage($consoleId, $con['coverurl'], $this->imgSavePath, 250, 250);
			}
		} else {
			$consoleId = $check['id'];

			if($con['cover'] === 1){
				$con['cover'] = $ri->saveImage($consoleId, $con['coverurl'], $this->imgSavePath, 250, 250);
			}

			$this->update(
						$consoleId, $con['title'], $con['asin'], $con['url'], $con['salesrank'],
						$con['platform'], $con['publisher'], $con['releasedate'], $con['esrb'],
						$con['cover'], $con['consolegenreID'], (isset($con['review']) ? $con['review'] : null)
			);
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
		$res = $this->pdo->queryDirect(
						sprintf('
							SELECT searchname, id
							FROM releases
							WHERE nzbstatus = %d %s
							AND consoleinfoid IS NULL
							AND categoryid BETWEEN 1000 AND 1999
							ORDER BY postdate DESC
							LIMIT %d',
							NZB::NZB_ADDED,
							$this->renamed,
							$this->gameqty
						)
		);

		if ($res instanceof Traversable && $res->rowCount() > 0) {

			if ($this->echooutput) {
				$this->pdo->log->doEcho($this->pdo->log->header("Processing " . $res->rowCount() . ' console release(s).'));
			}

			foreach ($res as $arr) {
				$startTime = microtime(true);
				$usedAmazon = false;
				$gameId = self::CONS_NTFND;
				$gameInfo = $this->parseTitle($arr['searchname']);

				if ($gameInfo !== false) {
						if ($this->echooutput) {
						$this->pdo->log->doEcho(
							$this->pdo->log->headerOver('Looking up: ') .
							$this->pdo->log->primary(
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
					} else {
						if ($this->echooutput) {
							$this->pdo->log->doEcho(
									$this->pdo->log->headerOver("Found Local: ") .
									$this->pdo->log->primary("{$gameCheck['title']} - {$gameCheck['platform']}") .
									PHP_EOL
							);
						}
						$gameId = $gameCheck['id'];
					}

				} elseif ($this->echooutput) {
					echo '.';
				}

				// Update release.
				$this->pdo->queryExec(
							sprintf('
								UPDATE releases
								SET consoleinfoid = %d
								WHERE id = %d',
								$gameId,
								$arr['id']
							)
				);

				// Sleep to not flood amazon.
				$diff = floor((microtime(true) - $startTime) * 1000000);
				if ($this->sleeptime * 1000 - $diff > 0 && $usedAmazon === true) {
					usleep($this->sleeptime * 1000 - $diff);
				}
			}

		} else if ($this->echooutput) {
			$this->pdo->log->doEcho($this->pdo->log->header('No console releases to process.'));
		}
	}

	function parseTitle($releasename)
	{
		$releasename = preg_replace('/\sMulti\d?\s/i', '', $releasename);
		$result = array();

		// Get name of the game from name of release.
		if (preg_match('/^(.+((abgx360EFNet|EFNet\sFULL|FULL\sabgxEFNet|abgx\sFULL|abgxbox360EFNet)\s|illuminatenboard\sorg|Place2(hom|us)e.net|united-forums? co uk|\(\d+\)))?(?P<title>.*?)[\.\-_ ](v\.?\d\.\d|PAL|NTSC|EUR|USA|JP|ASIA|JAP|JPN|AUS|MULTI(\.?\d{1,2})?|PATCHED|FULLDVD|DVD5|DVD9|DVDRIP|PROPER|REPACK|RETAIL|DEMO|DISTRIBUTION|REGIONFREE|[\. ]RF[\. ]?|READ\.?NFO|NFOFIX|PSX(2PSP)?|PS[2-4]|PSP|PSVITA|WIIU|WII|X\-?BOX|XBLA|X360|3DS|NDS|N64|NGC)/i', $releasename, $matches)) {
			$title = $matches['title'];

			// Replace dots, underscores, or brackets with spaces.
			$result['title'] = str_replace(['.','_','%20', '[', ']'], ' ', $title);
			$result['title'] = str_replace([' RF ', '.RF.', '-RF-', '_RF_'], ' ', $result['title']);
			//Remove format tags from release title for match
			$result['title'] = trim(preg_replace('/PAL|MULTI(\d)?|NTSC-?J?|\(JAPAN\)/i', '', $result['title']));
			//Remove disc tags from release title for match
			$result['title'] = trim(preg_replace('/Dis[ck] \d.*$/i', '', $result['title']));

			// Needed to add code to handle DLC Properly.
			if (stripos('dlc', $result['title']) !== false) {
				$result['dlc'] = '1';
				if (stripos('Rock Band Network', $result['title']) !== false) {
					$result['title'] = 'Rock Band';
				} else if (strpos('-', $result['title']) !== false) {
					$dlc = explode("-", $result['title']);
					$result['title'] = $dlc[0];
				} else if (preg_match('/(.*? .*?) /i', $result['title'], $dlc)) {
					$result['title'] = $dlc[0];
				}
			}

		} else {
			$title = '';
		}

		// Get the platform of the release.
		if (preg_match('/[\.\-_ ](?P<platform>XBLA|WiiWARE|N64|SNES|NES|PS[2-4]|PS 3|PSX(2PSP)?|PSP|WIIU|WII|XBOX360|XBOXONE|X\-?BOX|X360|3DS|NDS|N?GC)/i', $releasename, $matches)) {
			$platform = $matches['platform'];

			if (preg_match('/^N?GC$/i', $platform)) {
				$platform = 'NGC';
			}

			if (stripos('PSX2PSP', $platform) === 0) {
				$platform = 'PSX';
			}

			if (!empty($title) && stripos('XBLA', $platform) === 0) {
				if (stripos('dlc', $title) !== false) {
					$platform = 'XBOX360';
				}
			}

			$browseNode = $this->getBrowseNode($platform);
			$result['platform'] = $platform;
			$result['node'] = $browseNode;
		}
		$result['release'] = $releasename;
		array_map("trim", $result);

		/* Make sure we got a title and platform otherwise the resulting lookup will probably be shit.
		   Other option is to pass the $release->categoryID here if we don't find a platform but that
		   would require an extra lookup to determine the name. In either case we should have a title at the minimum. */

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
			case 'Action_shooter':
			case 'Action_Games':
			case 'Action_games':
				$str = 'Action';
				break;
			case 'Action/Adventure':
			case 'Action\Adventure':
			case 'Adventure_games':
				$str = 'Adventure';
				break;
			case 'Boxing_games':
			case 'Sports_games':
				$str = 'Sports';
				break;
			case 'Fantasy_action_games':
				$str = 'Fantasy';
				break;
			case 'Fighting_action_games':
				$str = 'Fighting';
				break;
			case 'Flying_simulation_games':
				$str = 'Flying';
				break;
			case 'Horror_action_games':
				$str = 'Horror';
				break;
			case 'Kids & Family':
				$str = 'Family';
				break;
			case 'Role_playing_games':
				$str = 'Role-Playing';
				break;
			case 'Shooter_action_games':
				$str = 'Shooter';
				break;
			case 'Singing_games':
				$str = 'Music';
				break;
			case 'Action':
			case 'Adventure':
			case 'Arcade':
			case 'Board Games':
			case 'Cards':
			case 'Casino':
			case 'Collections':
			case 'Family':
			case 'Fantasy':
			case 'Fighting':
			case 'Flying':
			case 'Horror':
			case 'Music':
			case 'Puzzle':
			case 'Racing':
			case 'Rhythm':
			case 'Role-Playing':
			case 'Simulation':
			case 'Shooter':
			case 'Shooting':
			case 'Sports':
			case 'Strategy':
			case 'Trivia':
				$str = $nodeName;
				break;
		}

		return ($str != '') ? $str : false;
	}


}