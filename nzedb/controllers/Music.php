<?php
require_once nZEDb_LIBS . 'AmazonProductAPI.php';
require_once nZEDb_LIB . 'utility' . DS . 'Utility.php';

use nzedb\db\Settings;

/**
 * Class Music
 */
class Music
{
	public $pdo;

	/**
	 * @param bool $echooutput
	 */
	function __construct($echooutput = false)
	{
		$this->echooutput = ($echooutput && nZEDb_ECHOCLI);

		$this->pdo = new Settings();
		$this->pubkey = $this->pdo->getSetting('amazonpubkey');
		$this->privkey = $this->pdo->getSetting('amazonprivkey');
		$this->asstag = $this->pdo->getSetting('amazonassociatetag');
		$this->musicqty = ($this->pdo->getSetting('maxmusicprocessed') != '') ? $this->pdo->getSetting('maxmusicprocessed') : 150;
		$this->sleeptime = ($this->pdo->getSetting('amazonsleep') != '') ? $this->pdo->getSetting('amazonsleep') : 1000;
		$this->imgSavePath = nZEDb_COVERS . 'music' . DS;
		$this->renamed = '';
		if ($this->pdo->getSetting('lookupmusic') == 2) {
			$this->renamed = 'AND isrenamed = 1';
		}
		$this->c = new ColorCLI();
	}

	/**
	 * @param $id
	 *
	 * @return array|bool
	 */
	public function getMusicInfo($id)
	{
		$pdo = $this->pdo;
		return $pdo->queryOneRow(sprintf("SELECT musicinfo.*, genres.title AS genres FROM musicinfo LEFT OUTER JOIN genres ON genres.id = musicinfo.genreid WHERE musicinfo.id = %d ", $id));
	}

	/**
	 * @param $artist
	 * @param $album
	 *
	 * @return array|bool
	 */
	public function getMusicInfoByName($artist, $album)
	{
		$pdo = $this->pdo;
		$like = 'ILIKE';
		if ($pdo->dbSystem() === 'mysql') {
			$like = 'LIKE';
		}
		return $pdo->queryOneRow(sprintf("SELECT * FROM musicinfo WHERE title LIKE %s AND artist %s %s", $pdo->escapeString("%" . $artist . "%"), $like, $pdo->escapeString("%" . $album . "%")));
	}

	/**
	 * @param $start
	 * @param $num
	 *
	 * @return array
	 */
	public function getRange($start, $num)
	{
		$pdo = $this->pdo;

		if ($start === false) {
			$limit = "";
		} else {
			$limit = " LIMIT " . $num . " OFFSET " . $start;
		}

		return $pdo->query(" SELECT * FROM musicinfo ORDER BY createddate DESC" . $limit);
	}

	/**
	 * @return mixed
	 */
	public function getCount()
	{
		$pdo = $this->pdo;
		$res = $pdo->queryOneRow("SELECT COUNT(id) AS num FROM musicinfo");
		return $res["num"];
	}

	/**
	 * @param       $cat
	 * @param       $maxage
	 * @param array $excludedcats
	 *
	 * @return mixed
	 */
	public function getMusicCount($cat, $maxage = -1, $excludedcats = array())
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

		$sql = sprintf("SELECT COUNT(DISTINCT r.musicinfoid) AS num FROM releases r INNER JOIN musicinfo m ON m.id = r.musicinfoid AND m.title != '' AND m.cover = 1 WHERE nzbstatus = 1 AND r.passwordstatus <= (SELECT value FROM settings WHERE setting='showpasswordedrelease') AND %s %s %s %s", $browseby, $catsrch, $maxage, $exccatlist);
		$res = $pdo->queryOneRow($sql);
		return $res["num"];
	}

	/**
	 * @param       $cat
	 * @param       $start
	 * @param       $num
	 * @param       $orderby
	 * @param       $maxage
	 * @param array $excludedcats
	 *
	 * @return array
	 */
	public function getMusicRange($cat, $start, $num, $orderby, $maxage = -1, $excludedcats = array())
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

		$order = $this->getMusicOrder($orderby);
		return $pdo->query(sprintf("SELECT GROUP_CONCAT(r.id ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_id, "
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
					. "m.*, r.musicinfoid, groups.name AS group_name, rn.id as nfoid FROM releases r "
					. "LEFT OUTER JOIN groups ON groups.id = r.group_id "
					. "LEFT OUTER JOIN releasenfo rn ON rn.releaseid = r.id "
					. "INNER JOIN musicinfo m ON m.id = r.musicinfoid "
					. "WHERE r.nzbstatus = 1 AND m.cover = 1 AND m.title != '' AND "
					. "r.passwordstatus <= (SELECT value FROM settings WHERE setting='showpasswordedrelease') AND %s %s %s %s "
					. "GROUP BY m.id ORDER BY %s %s" . $limit, $browseby, $catsrch, $maxage, $exccatlist, $order[0], $order[1]));
	}

	/**
	 * @param $orderby
	 *
	 * @return array
	 */
	public function getMusicOrder($orderby)
	{
		$order = ($orderby == '') ? 'r.postdate' : $orderby;
		$orderArr = explode("_", $order);
		switch ($orderArr[0]) {
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
				$orderfield = 'm.genreid';
				break;
			case 'posted':
			default:
				$orderfield = 'r.postdate';
				break;
		}
		$ordersort = (isset($orderArr[1]) && preg_match('/^asc|desc$/i', $orderArr[1])) ? $orderArr[1] : 'desc';
		return array($orderfield, $ordersort);
	}

	/**
	 * @return array
	 */
	public function getMusicOrdering()
	{
		return array('artist_asc', 'artist_desc', 'posted_asc', 'posted_desc', 'size_asc', 'size_desc', 'files_asc', 'files_desc', 'stats_asc', 'stats_desc', 'year_asc', 'year_desc', 'genre_asc', 'genre_desc');
	}

	/**
	 * @return array
	 */
	public function getBrowseByOptions()
	{
		return array('artist' => 'artist', 'title' => 'title', 'genre' => 'genreid', 'year' => 'year');
	}

	/**
	 * @return string
	 */
	public function getBrowseBy()
	{
		$pdo = new Settings();

		$like = ' ILIKE(';
		if ($pdo->dbSystem() === 'mysql') {
			$like = ' LIKE(';
		}

		$browseby = ' ';
		$browsebyArr = $this->getBrowseByOptions();
		foreach ($browsebyArr as $bbk => $bbv) {
			if (isset($_REQUEST[$bbk]) && !empty($_REQUEST[$bbk])) {
				$bbs = stripslashes($_REQUEST[$bbk]);
				if (preg_match('/id/i', $bbv)) {
					$browseby .= 'm.' . $bbv . ' = ' . $bbs . ' AND ';
				} else {
					$browseby .= 'm.' . $bbv . $like . $pdo->escapeString('%' . $bbs . '%') . ') AND ';
				}
			}
		}
		return $browseby;
	}

	/**
	 * @param $data
	 * @param $field
	 *
	 * @return string
	 */
	public function makeFieldLinks($data, $field)
	{
		$tmpArr = explode(', ', $data[$field]);
		$newArr = array();
		$i = 0;
		foreach ($tmpArr as $ta) {
			if ($i > 5) {
				break;
			} //only use first 6
			$newArr[] = '<a href="' . WWW_TOP . '/music?' . $field . '=' . urlencode($ta) . '" title="' . $ta . '">' . $ta . '</a>';
			$i++;
		}
		return implode(', ', $newArr);
	}

	/**
	 * @param $id
	 * @param $title
	 * @param $asin
	 * @param $url
	 * @param $salesrank
	 * @param $artist
	 * @param $publisher
	 * @param $releasedate
	 * @param $year
	 * @param $tracks
	 * @param $cover
	 * @param $genreID
	 */
	public function update($id, $title, $asin, $url, $salesrank, $artist, $publisher, $releasedate, $year, $tracks, $cover, $genreID)
	{
		$pdo = $this->pdo;
		$pdo->queryExec(sprintf("UPDATE musicinfo SET title = %s, asin = %s, url = %s, salesrank = %s, artist = %s, publisher = %s, releasedate = %s, year = %s, tracks = %s, cover = %d, genreid = %d, updateddate = NOW() WHERE id = %d", $pdo->escapeString($title), $pdo->escapeString($asin), $pdo->escapeString($url), $salesrank, $pdo->escapeString($artist), $pdo->escapeString($publisher), $pdo->escapeString($releasedate), $pdo->escapeString($year), $pdo->escapeString($tracks), $cover, $genreID, $id));
	}

	/**
	 * @param      $title
	 * @param      $year
	 * @param null $amazdata
	 *
	 * @return bool
	 */
	public function updateMusicInfo($title, $year, $amazdata = null)
	{
		$pdo = $this->pdo;
		$gen = new Genres();
		$ri = new ReleaseImage();
		$titlepercent = 0;

		$mus = array();
		if ($title != '') {
			$amaz = $this->fetchAmazonProperties($title);
		} else if ($amazdata != null) {
			$amaz = $amazdata;
		}
		if (!$amaz) {
			return false;
		}

		if (isset($amaz->Items->Item->ItemAttributes->Title)) {
			$mus['title'] = (string) $amaz->Items->Item->ItemAttributes->Title;
			if (empty($mus['title'])) {
				return false;
			}
		} else {
			return false;
		}

		// Load genres.
		$defaultGenres = $gen->getGenres(Genres::MUSIC_TYPE);
		$genreassoc = array();
		foreach ($defaultGenres as $dg) {
			$genreassoc[$dg['id']] = strtolower($dg['title']);
		}

		// Get album properties.
		$mus['coverurl'] = (string) $amaz->Items->Item->LargeImage->URL;
		if ($mus['coverurl'] != "") {
			$mus['cover'] = 1;
		} else {
			$mus['cover'] = 0;
		}

		$mus['asin'] = (string) $amaz->Items->Item->ASIN;

		$mus['url'] = (string) $amaz->Items->Item->DetailPageURL;
		$mus['url'] = str_replace("%26tag%3Dws", "%26tag%3Dopensourceins%2D21", $mus['url']);

		$mus['salesrank'] = (string) $amaz->Items->Item->SalesRank;
		if ($mus['salesrank'] == "") {
			$mus['salesrank'] = 'null';
		}

		$mus['artist'] = (string) $amaz->Items->Item->ItemAttributes->Artist;
		if (empty($mus['artist'])) {
			$mus['artist'] = (string) $amaz->Items->Item->ItemAttributes->Creator;
			if (empty($mus['artist'])) {
				$mus['artist'] = "";
			}
		}

		$mus['publisher'] = (string) $amaz->Items->Item->ItemAttributes->Publisher;

		$mus['releasedate'] = $pdo->escapeString((string) $amaz->Items->Item->ItemAttributes->ReleaseDate);
		if ($mus['releasedate'] == "''") {
			$mus['releasedate'] = 'null';
		}

		$mus['review'] = "";
		if (isset($amaz->Items->Item->EditorialReviews)) {
			$mus['review'] = trim(strip_tags((string) $amaz->Items->Item->EditorialReviews->EditorialReview->Content));
		}

		$mus['year'] = $year;
		if ($mus['year'] == "") {
			$mus['year'] = ($mus['releasedate'] != 'null' ? substr($mus['releasedate'], 1, 4) : date("Y"));
		}

		$mus['tracks'] = "";
		if (isset($amaz->Items->Item->Tracks)) {
			$tmpTracks = (array) $amaz->Items->Item->Tracks->Disc;
			$tracks = $tmpTracks['Track'];
			$mus['tracks'] = (is_array($tracks) && !empty($tracks)) ? implode('|', $tracks) : '';
		}

		similar_text($mus['artist'] . " " . $mus['title'], $title, $titlepercent);
		if ($titlepercent < 60) {
			return false;
		}

		$genreKey = -1;
		$genreName = '';
		if (isset($amaz->Items->Item->BrowseNodes)) {
			// Had issues getting this out of the browsenodes obj.
			// Workaround is to get the xml and load that into its own obj.
			$amazGenresXml = $amaz->Items->Item->BrowseNodes->asXml();
			$amazGenresObj = simplexml_load_string($amazGenresXml);
			$amazGenres = $amazGenresObj->xpath("//BrowseNodeId");

			foreach ($amazGenres as $amazGenre) {
				$currNode = trim($amazGenre[0]);
				if (empty($genreName)) {
					$genreMatch = $this->matchBrowseNode($currNode);
					if ($genreMatch !== false) {
						$genreName = $genreMatch;
						break;
					}
				}
			}

			if (in_array(strtolower($genreName), $genreassoc)) {
				$genreKey = array_search(strtolower($genreName), $genreassoc);
			} else {
				$genreKey = $pdo->queryInsert(sprintf("INSERT INTO genres (title, type) VALUES (%s, %d)", $pdo->escapeString($genreName), Genres::MUSIC_TYPE));
			}
		}
		$mus['musicgenre'] = $genreName;
		$mus['musicgenreid'] = $genreKey;

		$check = $pdo->queryOneRow(sprintf('SELECT id FROM musicinfo WHERE asin = %s', $pdo->escapeString($mus['asin'])));
		if ($check === false) {
			$musicId = $pdo->queryInsert(sprintf("INSERT INTO musicinfo (title, asin, url, salesrank, artist, publisher, "
					. "releasedate, review, year, genreid, tracks, cover, createddate, updateddate) VALUES "
					. "(%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %d, now(), now())", $pdo->escapeString($mus['title']), $pdo->escapeString($mus['asin']), $pdo->escapeString($mus['url']), $mus['salesrank'], $pdo->escapeString($mus['artist']), $pdo->escapeString($mus['publisher']), $mus['releasedate'], $pdo->escapeString($mus['review']), $pdo->escapeString($mus['year']), ($mus['musicgenreid'] == -1 ? "null" : $mus['musicgenreid']), $pdo->escapeString($mus['tracks']), $mus['cover']));
		} else {
			$musicId = $check['id'];
			$pdo->queryExec(sprintf('UPDATE musicinfo SET title = %s, asin = %s, url = %s, salesrank = %s, artist = %s, '
					. 'publisher = %s, releasedate = %s, review = %s, year = %s, genreid = %s, tracks = %s, cover = %s, '
					. 'updateddate = NOW() WHERE id = %d', $pdo->escapeString($mus['title']), $pdo->escapeString($mus['asin']), $pdo->escapeString($mus['url']), $mus['salesrank'], $pdo->escapeString($mus['artist']), $pdo->escapeString($mus['publisher']), $mus['releasedate'], $pdo->escapeString($mus['review']), $pdo->escapeString($mus['year']), ($mus['musicgenreid'] == -1 ? "null" : $mus['musicgenreid']), $pdo->escapeString($mus['tracks']), $mus['cover'], $musicId));
		}

		if ($musicId) {
			if ($this->echooutput) {
				if ($mus["artist"] == "") {
					$artist = "";
				} else {
					$artist = "Artist: " . $mus['artist'] . ", Album: ";
				}
				$this->c->doEcho(
					$this->c->header("\nAdded/updated album: ") .
					$this->c->alternateOver("   Artist: ") .
					$this->c->primary($mus['artist']) .
					$this->c->alternateOver("   Title:  ") .
					$this->c->primary($mus['title']) .
					$this->c->alternateOver("   Year:   ") .
					$this->c->primary($mus['year'])
				);
			}
			$mus['cover'] = $ri->saveImage($musicId, $mus['coverurl'], $this->imgSavePath, 250, 250);
		} else {
			if ($this->echooutput) {
				if ($mus["artist"] == "") {
					$artist = "";
				} else {
					$artist = "Artist: " . $mus['artist'] . ", Album: ";
				}
				$this->c->doEcho(
					$this->c->headerOver("Nothing to update: ") .
					$this->c->primaryOver(
						$artist .
						$mus['title'] .
						" (" .
						$mus['year'] .
						")"
					)
				);
			}
		}

		return $musicId;
	}

	/**
	 * @param $title
	 *
	 * @return bool|mixed
	 */
	public function fetchAmazonProperties($title)
	{
		$result = false;
		$obj = new AmazonProductAPI($this->pubkey, $this->privkey, $this->asstag);
		// Try Music category.
		try {
			$result = $obj->searchProducts($title, AmazonProductAPI::MUSIC, "TITLE");
		} catch (Exception $e) {
		}

		// Try MP3 category.
		if ($result === false) {
			usleep(700000);
			try {
				$result = $obj->searchProducts($title, AmazonProductAPI::MP3, "TITLE");
			} catch (Exception $e) {
			}
		}

		// Try Digital Music category.
		if ($result === false) {
			usleep(700000);
			try {
				$result = $obj->searchProducts($title, AmazonProductAPI::DIGITALMUS, "TITLE");
			} catch (Exception $e) {
			}
		}

		// Try Music Tracks category.
		if ($result === false) {
			usleep(700000);
			try {
				$result = $obj->searchProducts($title, AmazonProductAPI::MUSICTRACKS, "TITLE");
			} catch (Exception $e) {
			}
		}

		return $result;
	}

	/**
	 *
	 */
	public function processMusicReleases()
	{
		$pdo = $this->pdo;
		$res = $pdo->queryDirect(sprintf('SELECT searchname, id FROM releases '
				. 'WHERE musicinfoid IS NULL AND nzbstatus = 1 %s AND categoryid IN (3010, 3040, 3050) '
				. 'ORDER BY postdate DESC LIMIT %d', $this->renamed, $this->musicqty));
		if ($res->rowCount() > 0) {
			if ($this->echooutput) {
				$this->c->doEcho(
					$this->c->header("Processing " . $res->rowCount() .' music release(s).'
					)
				);
			}

			foreach ($res as $arr) {
				$startTime = microtime(true);
				$usedAmazon = false;
				$album = $this->parseArtist($arr['searchname']);
				if ($album !== false) {
					$newname = $album["name"] . ' (' . $album["year"] . ')';

					if ($this->echooutput) {
						$this->c->doEcho($this->c->headerOver('Looking up: ') . $this->c->primary($newname));
					}

					// Do a local lookup first
					$musicCheck = $this->getMusicInfoByName('', $album["name"]);

					if ($musicCheck === false) {
						$albumId = $this->updateMusicInfo($album["name"], $album['year']);
						$usedAmazon = true;
						if ($albumId === false) {
							$albumId = -2;
						}
					} else {
						$albumId = $musicCheck['id'];
					}

					// Update release.
					$pdo->queryExec(sprintf("UPDATE releases SET musicinfoid = %d WHERE id = %d", $albumId, $arr["id"]));
				} // No album found.
				else {
					$pdo->queryExec(sprintf("UPDATE releases SET musicinfoid = %d WHERE id = %d", -2, $arr["id"]));
					echo '.';
				}

				// Sleep to not flood amazon.
				$diff = floor((microtime(true) - $startTime) * 1000000);
				if ($this->sleeptime * 1000 - $diff > 0 && $usedAmazon === true) {
					usleep($this->sleeptime * 1000 - $diff);
				}
			}

			if ($this->echooutput) {
				echo "\n";
			}

		} else {
			if ($this->echooutput) {
				$this->c->doEcho($this->c->header('No music releases to process.'));
			}
		}
	}

	/**
	 * @param $releasename
	 *
	 * @return array|bool
	 */
	public function parseArtist($releasename)
	{
		$name = '';
		if (preg_match('/(.+?)(\d{1,2} \d{1,2} )?\(?(19\d{2}|20[0-1][0-9])\b/', $releasename, $name)) {
			$result = array();
			$result["year"] = $name[3];

			$a = preg_replace('/ (\d{1,2} \d{1,2} )?(Bootleg|Boxset|Clean.+Version|Compiled by.+|\dCD|Digipak|DIRFIX|DVBS|FLAC|(Ltd )?(Deluxe|Limited|Special).+Edition|Promo|PROOF|Reissue|Remastered|REPACK|RETAIL(.+UK)?|SACD|Sampler|SAT|Summer.+Mag|UK.+Import|Deluxe.+Version|VINYL|WEB)/i', ' ', $name[1]);
			$b = preg_replace('/ ([a-z]+[0-9]+[a-z]+[0-9]+.+|[a-z]{2,}[0-9]{2,}?.+|3FM|B00[a-z0-9]+|BRC482012|H056|UXM1DW086|(4WCD|ATL|bigFM|CDP|DST|ERE|FIM|MBZZ|MSOne|MVRD|QEDCD|RNB|SBD|SFT|ZYX) \d.+)/i', ' ', $a);
			$c = preg_replace('/ (\d{1,2} \d{1,2} )?([A-Z])( ?$)|\(?[0-9]{8,}\)?| (CABLE|FREEWEB|LINE|MAG|MCD|YMRSMILES)|\(([a-z]{2,}[0-9]{2,}|ost)\)|-web-/', ' ', $b);
			$d = preg_replace('/VA( |-)/', 'Various Artists ', $c);
			$e = preg_replace('/ (\d{1,2} \d{1,2} )?(DAB|DE|DVBC|EP|FIX|IT|Jap|NL|PL|(Pure )?FM|SSL|VLS) /i', ' ', $d);
			$f = preg_replace('/ (\d{1,2} \d{1,2} )?(CD(A|EP|M|R|S)?|QEDCD|SBD) /i', ' ', $e);
			$g = str_replace(array('_', '-'), ' ', $f);
			$h = trim(preg_replace('/\s\s+/', ' ', $g));
			$newname = trim(preg_Replace('/ [a-z]{2}$| [a-z]{3} \d{2,}$|\d{5,} \d{5,}$|-WEB$/i', '', $h));

			/*var_dump($releasename);
			var_dump($name[1]);
			var_dump($a);
			var_dump($b);
			var_dump($c);
			var_dump($d);
			var_dump($e);
			var_dump($f);
			var_dump($g);
			var_dump($h);
			var_dump($newname);
			//exit();*/

			if (!preg_match('/^[a-z0-9]+$/i', $newname) && strlen($newname) > 10) {
				$result["name"] = $newname;
				return $result;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * @param bool $activeOnly
	 *
	 * @return array
	 */
	public function getGenres($activeOnly = false)
	{
		$pdo = $this->pdo;
		if ($activeOnly) {
			return $pdo->query("SELECT musicgenre.* FROM musicgenre INNER JOIN (SELECT DISTINCT musicgenreid FROM musicinfo) x ON x.musicgenreid = musicgenre.id ORDER BY title");
		} else {
			return $pdo->query("SELECT * FROM musicgenre ORDER BY title");
		}
	}

	/**
	 * @param $nodeId
	 *
	 * @return bool|string
	 */
	public function matchBrowseNode($nodeId)
	{
		$str = '';

		//music nodes above mp3 download nodes
		switch ($nodeId) {
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
