<?php
namespace nzedb;

use app\models\Settings;
use DBorsatto\GiantBomb\Config;
use DBorsatto\GiantBomb\Client;
use nzedb\db\DB;

class Games
{
	const GAME_MATCH_PERCENTAGE = 85;

	const GAMES_TITLE_PARSE_REGEX =
		'#(?P<title>[\w\s\.]+)(-(?P<relgrp>FLT|RELOADED|SKIDROW|PROPHET|RAZOR1911|CORE|REFLEX))?\s?(\s*(\(?(' .
		'(?P<reltype>PROPER|MULTI\d|RETAIL|CRACK(FIX)?|ISO|(RE)?(RIP|PACK))|(?P<year>(19|20)\d{2})|V\s?' .
		'(?P<version>(\d+\.)+\d+)|(-\s)?(?P=relgrp))\)?)\s?)*\s?(\.\w{2,4})?#i';

	/**
	 * @var string
	 */
	public $cookie;

	/**
	 * @var bool
	 */
	public $echoOutput;

	/**
	 * @var array|bool|int|string
	 */
	public $gameQty;

	/**
	 * @var string
	 */
	public $imgSavePath;

	/**
	 * @var int
	 */
	public $matchPercentage;

	/**
	 * @var bool
	 */
	public $maxHitRequest;

	/**
	 * @var \nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @var array|bool|string
	 */
	public $publicKey;

	/**
	 * @var string
	 */
	public $renamed;

	/**
	 * @var array|bool|int|string
	 */
	public $sleepTime;

	/**
	 * @var string
	 */
	protected $_classUsed;

	/**
	 * @var string
	 */
	protected $_gameID;

	/**
	 * @var array|bool
	 */
	protected $_gameResults;

	/**
	 * @var object
	 */
	protected $_getGame;

	/**
	 * @var int
	 */
	protected $_resultsFound = 0;

	/**
	 * @param array $options Class instances / Echo to cli.
	 *
	 * @throws \Exception
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Echo'     => false,
			'ColorCLI' => null,
			'Settings' => null,
		];
		$options += $defaults;

		$this->echoOutput = ($options['Echo'] && nZEDb_ECHOCLI);

		$this->pdo = ($options['Settings'] instanceof DB ? $options['Settings'] : new DB());

		$this->publicKey = Settings::value('APIs..giantbombkey');
		$result = Settings::value('..maxgamesprocessed');
		$this->gameQty = ($result != '') ? $result : 150;
		$result = Settings::value('..amazonsleep');
		$this->sleepTime = ($result != '') ? $result : 1000;
		$this->imgSavePath = nZEDb_COVERS . 'games' . DS;
		$this->renamed = '';
		$this->matchPercentage = 60;
		$this->maxHitRequest = false;
		$this->cookie = nZEDb_TMP . 'xxx.cookie';
		if (Settings::value('..lookupgames') == 2) {
			$this->renamed = 'AND isrenamed = 1';
		}
		$this->catWhere = 'AND categories_id = ' . Category::PC_GAMES;
		//$this->cleangames = (Settings::value('..lookupgames') == 2) ? 'AND isrenamed = 1' : '';
		if($this->publicKey !== '') {
			$this->config = new Config($this->publicKey);
			$this->giantbomb = new Client($this->config);
		}
	}

	/**
	 * @param $id
	 *
	 * @return array|bool
	 */
	public function getGamesInfo($id)
	{
		return $this->pdo->queryOneRow(
			sprintf("
				SELECT gamesinfo.*, genres.title AS genres
				FROM gamesinfo
				LEFT OUTER JOIN genres ON genres.id = gamesinfo.genre_id
				WHERE gamesinfo.id = %d",
				$id
			)
		);
	}

	/**
	 * @param string $title
	 *
	 * @return array|bool
	 */
	public function getGamesInfoByName($title)
	{
		$bestMatch = false;

		if (empty($title)) {
			return $bestMatch;
		}

		$results = $this->pdo->queryDirect("
			SELECT *
			FROM gamesinfo
			WHERE MATCH(title) AGAINST({$this->pdo->escapeString($title)})
			LIMIT 20"
		);

		if ($results instanceof \Traversable) {
			$bestMatchPct = 0;
			foreach ($results as $result) {
				// If we have an exact string match set best match and break out
				if ($result['title'] === $title) {
					$bestMatch = $result;
					break;
				}
				similar_text(strtolower($result['title']), strtolower($title), $percent);
				// If similar_text reports an exact match set best match and break out
				if ($percent === 100) {
					$bestMatch = $result;
					break;
				}
				if ($percent >= self::GAME_MATCH_PERCENTAGE && $percent > $bestMatchPct) {
					$bestMatch = $result;
					$bestMatchPct = $percent;
				}
			}
		}

		return $bestMatch;
	}

	/**
	 * @param $start
	 * @param $num
	 *
	 * @return array
	 */
	public function getRange($start, $num)
	{
		return $this->pdo->query(
			sprintf(
				"SELECT gi.*, g.title AS genretitle FROM gamesinfo gi INNER JOIN genres g ON gi.genre_id = g.id ORDER BY createddate DESC %s",
				($start === false ? '' : 'LIMIT ' . $num . ' OFFSET ' . $start)
			)
		);
	}

	/**
	 * @return int
	 */
	public function getCount()
	{
		$res = $this->pdo->queryOneRow("SELECT COUNT(id) AS num FROM gamesinfo");
		return ($res === false ? 0 : $res["num"]);
	}

	/**
	 * @param       $cat
	 * @param       $start
	 * @param       $num
	 * @param       $orderby
	 * @param int   $maxage
	 * @param array $excludedcats
	 *
	 * @return array
	 */
	public function getGamesRange($cat, $start, $num, $orderby, $maxage = -1, $excludedcats = [])
	{
		$browseby = $this->getBrowseBy();

		$catsrch = '';
		if (count($cat) > 0 && $cat[0] != -1) {
			$catsrch = (new Category(['Settings' => $this->pdo]))->getCategorySearch($cat);
		}

		if ($maxage > 0) {
			$maxage = sprintf(' AND r.postdate > NOW() - INTERVAL %d DAY ', $maxage);
		} else {
			$maxage = '';
		}

		$exccatlist = "";
		if (count($excludedcats) > 0) {
			$exccatlist = " AND r.categories_id NOT IN (" . implode(",", $excludedcats) . ")";
		}

		$order = $this->getGamesOrder($orderby);

		$games = $this->pdo->queryCalc(
			sprintf("
				SELECT SQL_CALC_FOUND_ROWS gi.id,
					GROUP_CONCAT(r.id ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_id
				FROM gamesinfo gi
				LEFT JOIN releases r ON gi.id = r.gamesinfo_id
				WHERE r.nzbstatus = 1
				AND gi.title != ''
				AND gi.cover = 1
				AND r.passwordstatus %s
				%s %s %s %s
				GROUP BY gi.id
				ORDER BY %s %s %s",
				Releases::showPasswords(),
				$browseby,
				$catsrch,
				$maxage,
				$exccatlist,
				$order[0],
				$order[1],
				($start === false ? '' : ' LIMIT ' . $num . ' OFFSET ' . $start)
			), true, nZEDb_CACHE_EXPIRY_MEDIUM
		);

		$gameIDs = $releaseIDs = false;

		if (is_array($games['result'])) {
			foreach ($games['result'] as $game => $id) {
				$gameIDs[] = $id['id'];
				$releaseIDs[] = $id['grp_release_id'];
			}
		}

		$return = $this->pdo->query(
			sprintf("
				SELECT
					GROUP_CONCAT(r.id ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_id,
					GROUP_CONCAT(r.rarinnerfilecount ORDER BY r.postdate DESC SEPARATOR ',') as grp_rarinnerfilecount,
					GROUP_CONCAT(r.haspreview ORDER BY r.postdate DESC SEPARATOR ',') AS grp_haspreview,
					GROUP_CONCAT(r.passwordstatus ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_password,
					GROUP_CONCAT(r.guid ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_guid,
					GROUP_CONCAT(rn.releases_id ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_nfoid,
					GROUP_CONCAT(g.name ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_grpname,
					GROUP_CONCAT(r.searchname ORDER BY r.postdate DESC SEPARATOR '#') AS grp_release_name,
					GROUP_CONCAT(r.postdate ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_postdate,
					GROUP_CONCAT(r.size ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_size,
					GROUP_CONCAT(r.totalpart ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_totalparts,
					GROUP_CONCAT(r.comments ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_comments,
					GROUP_CONCAT(r.grabs ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_grabs,
					GROUP_CONCAT(df.failed ORDER BY r.postdate DESC SEPARATOR ',') AS grp_release_failed,
				gi.*, YEAR (gi.releasedate) as year, r.gamesinfo_id, g.name AS group_name,
				rn.releases_id AS nfoid
				FROM releases r
				LEFT OUTER JOIN groups g ON g.id = r.groups_id
				LEFT OUTER JOIN release_nfos rn ON rn.releases_id = r.id
				LEFT OUTER JOIN dnzb_failures df ON df.release_id = r.id
				INNER JOIN gamesinfo gi ON gi.id = r.gamesinfo_id
				WHERE gi.id IN (%s)
				AND r.id IN (%s)
				%s
				GROUP BY gi.id
				ORDER BY %s %s",
				(is_array($gameIDs) ? implode(',', $gameIDs) : -1),
				(is_array($releaseIDs) ? implode(',', $releaseIDs) : -1),
				$catsrch,
				$order[0],
				$order[1]
			), true, nZEDb_CACHE_EXPIRY_MEDIUM
		);
		if (!empty($return)) {
			$return[0]['_totalcount'] = (isset($games['total']) ? $games['total'] : 0);
		}
		return $return;
	}

	/**
	 * @param $orderby
	 *
	 * @return array
	 */
	public function getGamesOrder($orderby)
	{
		$order = ($orderby == '') ? 'r.postdate' : $orderby;
		$orderArr = explode("_", $order);
		switch ($orderArr[0]) {
			case 'title':
				$orderfield = 'gi.title';
				break;
			case 'releasedate':
				$orderfield = 'gi.releasedate';
				break;
			case 'genre':
				$orderfield = 'gi.genre_id';
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

		return [$orderfield, $ordersort];
	}

	/**
	 * @return string[]
	 */
	public function getGamesOrdering()
	{
		return [
			'title_asc',
			'title_desc',
			'posted_asc',
			'posted_desc',
			'size_asc',
			'size_desc',
			'files_asc',
			'files_desc',
			'stats_asc',
			'stats_desc',
			'releasedate_asc',
			'releasedate_desc',
			'genre_asc',
			'genre_desc'
		];
	}

	/**
	 * @return array
	 */
	public function getBrowseByOptions()
	{
		return ['title' => 'title', 'genre' => 'genre_id', 'year' => 'year'];
	}

	/**
	 * @return string
	 */
	public function getBrowseBy()
	{
		$browseby = ' ';
		$browsebyArr = $this->getBrowseByOptions();
		$like = 'LIKE';

		foreach ($browsebyArr as $bbk => $bbv) {
			if (isset($_REQUEST[$bbk]) && !empty($_REQUEST[$bbk])) {
				$bbs = stripslashes($_REQUEST[$bbk]);
				if ($bbk === 'year') {
					$browseby .= 'AND YEAR (gi.releasedate) ' . $like . ' (' . $this->pdo->escapeString('%' . $bbs . '%') . ')';
				} else {
					$browseby .= 'AND gi.' . $bbv . ' ' . $like . ' (' . $this->pdo->escapeString('%' . $bbs . '%') . ')';
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
		$newArr = [];
		$i = 0;
		foreach ($tmpArr as $ta) {
			if (trim($ta) == '') {
				continue;
			}
			// Only use first 6.
			if ($i > 5) {
				break;
			}
			$newArr[] = '<a href="' . WWW_TOP . '/games?' . $field . '=' . urlencode($ta) . '" title="' . $ta . '">' . $ta . '</a>';
			$i++;
		}

		return implode(', ', $newArr);
	}

	/**
	 * Updates the game for game-edit.php
	 *
	 * @param $id
	 * @param $title
	 * @param $asin
	 * @param $url
	 * @param $publisher
	 * @param $releasedate
	 * @param $esrb
	 * @param $cover
	 * @param $trailerurl
	 * @param $genreID
	 */
	public function update($id, $title, $asin, $url, $publisher, $releasedate, $esrb, $cover, $trailerurl, $genreID)
	{

		$this->pdo->queryExec(
			sprintf("
				UPDATE gamesinfo
				SET title = %s, asin = %s, url = %s, publisher = %s,
					releasedate = %s, esrb = %s, cover = %d, trailer = %s, genre_id = %d, updateddate = NOW()
				WHERE id = %d",
				$this->pdo->escapeString($title),
				$this->pdo->escapeString($asin),
				$this->pdo->escapeString($url),
				$this->pdo->escapeString($publisher),
				$this->pdo->escapeString($releasedate),
				$this->pdo->escapeString($esrb),
				$cover,
				$this->pdo->escapeString($trailerurl),
				$genreID,
				$id
			)
		);
	}

	/**
	 * Process each game, updating game information from Steam and Giantbomb
	 *
	 * @param $gameInfo
	 *
	 * @return bool
	 * @throws \RuntimeException
	 * @throws \InvalidArgumentException
	 */
	public function updateGamesInfo($gameInfo)
	{
		//wait 10 seconds before proceeding (steam api limit)
		sleep(1);
		$gen = new Genres(['Settings' => $this->pdo]);
		$ri = new ReleaseImage($this->pdo);

		$game = [];

		// Process Steam first before GiantBomb as Steam has more details
		$this->_gameResults = false;
		$genreName = '';
		$this->_getGame = new Steam(['DB' => $this->pdo]);
		$this->_classUsed = 'Steam';

		$steamGameID = $this->_getGame->search($gameInfo['title']);

		if ($steamGameID !== false) {
			$this->_gameResults = $this->_getGame->getAll($steamGameID);
			if ($this->_gameResults !== false) {
				if (empty($this->_gameResults['title'])) {
					return false;
				}
				if (!empty($this->_gameResults['cover'])) {
					$game['coverurl'] = (string)$this->_gameResults['cover'];
				}

				if (!empty($this->_gameResults['backdrop'])) {
					$game['backdropurl'] = (string)$this->_gameResults['backdrop'];
				}

				$game['title'] = (string)$this->_gameResults['title'];
				$game['asin'] = $this->_gameResults['steamid'];
				$game['url'] = (string)$this->_gameResults['directurl'];

				if (!empty($this->_gameResults['publisher'])) {
					$game['publisher'] = (string)$this->_gameResults['publisher'];
				} else {
					$game['publisher'] = 'Unknown';
				}

				if (!empty($this->_gameResults['rating'])) {
					$game['esrb'] = (string)$this->_gameResults['rating'];
				} else {
					$game['esrb'] = 'Not Rated';
				}

				if (!empty($this->_gameResults['releasedate'])) {
					$dateReleased = $this->_gameResults['releasedate'];
					$date = \DateTime::createFromFormat('M j, Y', $dateReleased);
					if ($date instanceof \DateTime) {
						$game['releasedate'] = (string)$date->format('Y-m-d');
					}
				}

				if (!empty($this->_gameResults['description'])) {
					$game['review'] = (string)$this->_gameResults['description'];
				}

				if (!empty($this->_gameResults['genres'])) {
					$genres = $this->_gameResults['genres'];
					$genreName = $this->_matchGenre($genres);
				}
			}
		}

		if($this->publicKey !== '') {
			if ($steamGameID === false || $this->_gameResults === false) {
				$bestMatch = false;
				$this->_classUsed = 'GiantBomb';
				$result = $this->giantbomb->search($gameInfo['title'], 'Game');

				if (!is_object($result)) {
					foreach ($result as $res) {
						similar_text(strtolower($gameInfo['title']), strtolower($res->name), $percent1);
						similar_text(strtolower($gameInfo['title']), strtolower($res->aliases), $percent2);
						if ($percent1 >= self::GAME_MATCH_PERCENTAGE || $percent2 >= self::GAME_MATCH_PERCENTAGE) {
							$bestMatch = $res->id;
						}
					}

					if ($bestMatch !== false) {
						$this->_gameResults = $this->giantbomb->findOne('Game', '3030-' . $bestMatch);

						if (!empty($this->_gameResults->image['medium_url'])) {
							$game['coverurl'] = (string)$this->_gameResults->image['medium_url'];
						}

						if (!empty($this->_gameResults->image['screen_url'])) {
							$game['backdropurl'] = (string)$this->_gameResults->image['screen_url'];
						}

						$game['title'] = (string)$this->_gameResults->get('name');
						$game['asin'] = $this->_gameResults->get('id');
						if (!empty($this->_gameResults->get('site_detail_url'))) {
							$game['url'] = (string)$this->_gameResults->get('site_detail_url');
						} else {
							$game['url'] = '';
						}

						if ($this->_gameResults->get('publishers') !== '') {
							$game['publisher'] = (string)$this->_gameResults->publishers[0]['name'];
						} else {
							$game['publisher'] = 'Unknown';
						}


						if (!empty($this->_gameResults->original_game_rating[0]['name'])) {
							$game['esrb'] = (string)$this->_gameResults->original_game_rating[0]['name'];
						} else {
							$game['esrb'] = 'Not Rated';
						}

						if ($this->_gameResults->original_release_date !== '') {
							$dateReleased = $this->_gameResults->original_release_date;
							$date = \DateTime::createFromFormat('Y-m-d H:i:s', $dateReleased);
							if ($date instanceof \DateTime) {
								$game['releasedate'] = (string)$date->format('Y-m-d');
							}
						}

						if ($this->_gameResults->deck !== '') {
							$game['review'] = (string)$this->_gameResults->deck;
						}
					} else {
						ColorCLI::doEcho(ColorCLI::notice('GiantBomb returned no valid results'));

						return false;
					}
				} else {
					ColorCLI::doEcho(ColorCLI::notice('GiantBomb found no valid results'));

					return false;
				}
			}
		}

		// Load genres.
		$defaultGenres = $gen->getGenres(Genres::GAME_TYPE);
		$genreAssoc = [];
		foreach ($defaultGenres as $dg) {
			$genreAssoc[$dg['id']] = strtolower($dg['title']);
		}

		// Prepare database values.
		if (isset($game['coverurl'])) {
			$game['cover'] = 1;
		} else {
			$game['cover'] = 0;
		}
		if (isset($game['backdropurl'])) {
			$game['backdrop'] = 1;
		} else {
			$game['backdrop'] = 0;
		}
		if (!isset($game['trailer'])) {
			$game['trailer'] = 0;
		}
		if (empty($game['title'])) {
			$game['title'] = $gameInfo['title'];
		}
		if(!isset($game['releasedate'])){
			$game['releasedate'] = '';
		}

		if ($game['releasedate'] === '') {
			$game['releasedate'] = '';
		}
		if(!isset($game['review'])){
			$game['review'] = 'No Review';
		}
		$game['classused'] = $this->_classUsed;

		if (empty($genreName)) {
			$genreName = 'Unknown';
		}

		if (in_array(strtolower($genreName), $genreAssoc, false)) {
			$genreKey = array_search(strtolower($genreName), $genreAssoc, false);
		} else {
			$genreKey = $this->pdo->queryInsert(
				sprintf('
					INSERT INTO genres (title, type)
					VALUES (%s, %d)',
					$this->pdo->escapeString($genreName),
					Genres::GAME_TYPE
				)
			);
		}

		$game['gamesgenre'] = $genreName;
		$game['gamesgenreID'] = $genreKey;

		$check = $this->pdo->queryOneRow(
			sprintf('
				SELECT id
				FROM gamesinfo
				WHERE asin = %s',
				$this->pdo->escapeString($game['asin'])
			)
		);
		if ($check === false) {
			$gamesId = $this->pdo->queryInsert(
				sprintf('
					INSERT INTO gamesinfo
						(title, asin, url, publisher, genre_id, esrb, releasedate, review, cover, backdrop, trailer, classused, createddate, updateddate)
					VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %d, %d, %s, %s, NOW(), NOW())',
					$this->pdo->escapeString($game['title']),
					$this->pdo->escapeString($game['asin']),
					$this->pdo->escapeString($game['url']),
					$this->pdo->escapeString($game['publisher']),
					($game['gamesgenreID'] === -1 ? 'null' : $game['gamesgenreID']),
					$this->pdo->escapeString($game['esrb']),
					($game['releasedate'] !== '' ? $this->pdo->escapeString($game['releasedate']) : 'null'),
					$this->pdo->escapeString(substr($game['review'], 0, 3000)),
					$game['cover'],
					$game['backdrop'],
					$this->pdo->escapeString($game['trailer']),
					$this->pdo->escapeString($game['classused'])
				)
			);
		} else {
			$gamesId = $check['id'];
			$this->pdo->queryExec(
				sprintf('
					UPDATE gamesinfo
					SET
						title = %s, asin = %s, url = %s, publisher = %s, genre_id = %s,
						esrb = %s, releasedate = %s, review = %s, cover = %d, backdrop = %d, trailer = %s, classused = %s, updateddate = NOW()
					WHERE id = %d',
					$this->pdo->escapeString($game['title']),
					$this->pdo->escapeString($game['asin']),
					$this->pdo->escapeString($game['url']),
					$this->pdo->escapeString($game['publisher']),
					($game['gamesgenreID'] === -1 ? 'null' : $game['gamesgenreID']),
					$this->pdo->escapeString($game['esrb']),
					($game['releasedate'] !== '' ? $this->pdo->escapeString($game['releasedate']) : 'null'),
					$this->pdo->escapeString(substr($game['review'], 0, 3000)),
					$game['cover'],
					$game['backdrop'],
					$this->pdo->escapeString($game['trailer']),
					$this->pdo->escapeString($game['classused']),
					$gamesId
				)
			);
		}

		if ($gamesId) {
			if ($this->echoOutput) {
				ColorCLI::doEcho(
					ColorCLI::header('Added/updated game: ') .
					ColorCLI::alternateOver('   Title:    ') .
					ColorCLI::primary($game['title']) .
					ColorCLI::alternateOver( '   Source:   ') .
					ColorCLI::primary($this->_classUsed)
				);
			}
			if($game['cover'] === 1){
				$game['cover'] = $ri->saveImage($gamesId, $game['coverurl'], $this->imgSavePath, 250, 250);
			}
			if($game['backdrop'] === 1){
				$game['backdrop'] = $ri->saveImage($gamesId . '-backdrop', $game['backdropurl'], $this->imgSavePath, 1920, 1024);
			}
		} else {
			if ($this->echoOutput) {
				ColorCLI::doEcho(
					ColorCLI::headerOver('Nothing to update: ') .
					ColorCLI::primary($game['title'] . ' (PC)' )
				);
			}
		}

		return $gamesId;
	}

	/**
	 * Main function for retrieving and processing PC games titles
	 */
	public function processGamesReleases()
	{
		$res = $this->pdo->queryDirect(
			sprintf('
				SELECT searchname, id
				FROM releases
				WHERE nzbstatus = 1 %s
				AND gamesinfo_id = 0 %s
				ORDER BY postdate DESC
				LIMIT %d',
				$this->renamed,
				$this->catWhere,
				$this->gameQty
			)
		);

		if ($res instanceof \Traversable && $res->rowCount() > 0) {
			if ($this->echoOutput) {
				$this->pdo->log->doEcho($this->pdo->log->header("Processing " . $res->rowCount() . ' games release(s).'));
			}

			foreach ($res as $arr) {
				// Reset maxhitrequest
				$this->maxHitRequest = false;
				$startTime = microtime(true);
				$usedgb = false;
				$gameInfo = $this->parseTitle($arr['searchname']);
				if ($gameInfo !== false) {

					if ($this->echoOutput) {
						$this->pdo->log->doEcho(
							$this->pdo->log->headerOver('Looking up: ') .
							$this->pdo->log->primary($gameInfo['title'] . ' (PC)')
						);
					}

					// Check for existing games entry.
					$gameCheck = $this->getGamesInfoByName($gameInfo['title']);

					if ($gameCheck === false) {
						$gameId = $this->updateGamesInfo($gameInfo);
						$usedgb = true;
						if ($gameId === false) {
							$gameId = -2;

						// Leave gamesinfo_id 0 to parse again
							if ($this->maxHitRequest === true) {
								$gameId = 0;
							}
						}

					} else {
						$gameId = $gameCheck['id'];
					}
					// Update release.
					$this->pdo->queryExec(sprintf('UPDATE releases SET gamesinfo_id = %d WHERE id = %d %s', $gameId, $arr['id'], $this->catWhere));
				} else {
					// Could not parse release title.
					$this->pdo->queryExec(sprintf('UPDATE releases SET gamesinfo_id = %d WHERE id = %d %s', -2, $arr['id'], $this->catWhere));

					if ($this->echoOutput) {
						echo '.';
					}
				}

				// Sleep to not flood giantbomb.
				$diff = floor((microtime(true) - $startTime) * 1000000);
				if ($this->sleepTime * 1000 - $diff > 0 && $usedgb === true) {
					usleep($this->sleepTime * 1000 - $diff);
				}
			}
		} else {
			if ($this->echoOutput) {
				$this->pdo->log->doEcho($this->pdo->log->header('No games releases to process.'));
			}
		}
	}

	/**
	 * Parse the game release title
	 *
	 * @param string $releaseName
	 *
	 * @return array|bool
	 */
	public function parseTitle($releaseName)
	{

		// Get name of the game from name of release.
		if (preg_match(self::GAMES_TITLE_PARSE_REGEX, preg_replace('/\sMulti\d?\s/i', '', $releaseName), $matches)) {
			// Replace dots, underscores, colons, or brackets with spaces.
			$result = [];
			$result['title'] = str_replace(' RF ', ' ', preg_replace('/(\-|\:|\.|_|\%20|\[|\])/', ' ', $matches['title']));
			// Replace any foreign words at the end of the release
			$result['title'] = preg_replace('/(brazilian|chinese|croatian|danish|deutsch|dutch|english|estonian|flemish|finnish|french|german|greek|hebrew|icelandic|italian|latin|nordic|norwegian|polish|portuguese|japenese|japanese|russian|serbian|slovenian|spanish|spanisch|swedish|thai|turkish)$/i', '', $result['title']);
			// Remove PC ISO) ( from the beginning bad regex from Games category?
			$result['title'] = preg_replace('/^(PC\sISO\)\s\()/i', '', $result['title']);
			// Finally remove multiple spaces and trim leading spaces.
			$result['title'] = trim(preg_replace('/\s{2,}/', ' ', $result['title']));
			if (empty($result['title'])) {
				return false;
			}
			$result['release'] = $releaseName;

			return array_map('trim', $result);
		}

		return false;
	}

	/**
	 * See if genre name exists
	 *
	 * @param $nodeName
	 *
	 * @return false|string
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

	/**
	 * Matches Genres
	 *
	 * @param string $genre
	 *
	 * @return string
	 */
	protected function _matchGenre($genre = '')
	{
		$genreName = '';
		$a = str_replace('-', ' ', $genre);
		$tmpGenre = explode(',', $a);
		if (is_array($tmpGenre)) {
			foreach ($tmpGenre as $tg) {
				$genreMatch = $this->matchBrowseNode(ucwords($tg));
				if ($genreMatch !== false) {
					$genreName = (string)$genreMatch;
					break;
				}
			}
			if (empty($genreName)) {
				$genreName = $tmpGenre[0];
			}
		} else {
			$genreName = $genre;
		}

		return $genreName;
	}
}
