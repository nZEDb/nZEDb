<?php

namespace nzedb\db\populate;

use app\models\Settings;
use nzedb\ColorCLI;
use nzedb\ReleaseImage;
use nzedb\db\DB;

class AniDB
{
	const CLIENT_VERSION = 2;

	/**
	 * Whether or not to echo message output
	 * @var bool
	 */
	public $echooutput;

	/**
	 * The directory to store AniDB covers
	 * @var string
	 */
	public $imgSavePath;

	/**
	 * @var \nzedb\db\DB
	 */
	public $pdo;

	/**
	 * The name of the nZEDb client for AniDB lookups
	 * @var string
	 */
	private $apiKey;

	/**
	 * Whether or not AniDB thinks our client is banned
	 * @var bool
	 */
	private $banned;

	/**
	 * The last unixtime a full AniDB update was run
	 * @var string
	 */
	private $lastUpdate;

	/**
	 * The number of days between full AniDB updates
	 * @var string
	 */
	private $updateInterval;

	/**
	 * @param array $options Class instances / Echo to cli.
	 */
	public function __construct(array $options = [])
	{
		$defaults = [
			'Echo'     => false,
			'Settings' => null,
		];
		$options += $defaults;

		$this->echooutput = ($options['Echo'] && nZEDb_ECHOCLI);
		$this->pdo = ($options['Settings'] instanceof DB ? $options['Settings'] : new DB());

		$anidbupdint = Settings::value('APIs.AniDB.max_update_frequency');
		$lastupdated = Settings::value('APIs.AniDB.last_full_update');

		$this->imgSavePath = nZEDb_COVERS . 'anime' . DS;
		$this->apiKey      = Settings::value('APIs..anidbkey');

		$this->updateInterval = isset($anidbupdint) ? $anidbupdint : '7';
		$this->lastUpdate     = isset($lastupdated) ? $lastupdated : '0';
		$this->banned         = false;
	}

	/**
	 * Main switch that initiates AniDB table population
	 *
	 * @param string $type
	 * @param int|string    $anidbId
	 */
	public function populateTable($type = '', $anidbId = '')
	{
		switch ($type) {
			case 'full':
				$this->populateMainTable();
				break;
			case 'info':
				$this->populateInfoTable($anidbId);
				break;
		}
	}

	/**
	 * Checks for an existing anime title in anidb table
	 *
	 * @param int    $id    The AniDB ID to be inserted
	 * @param string $type  The title type
	 * @param string $lang  The title language
	 * @param string $title The title of the Anime
	 *
	 * @return array|bool
	 */
	private function checkDuplicateDbEntry($id, $type, $lang, $title)
	{
		return $this->pdo->queryOneRow(
			sprintf('
				SELECT anidbid
				FROM anidb_titles
				WHERE anidbid = %d
				AND type = %s
				AND lang = %s
				AND title = %s',
				$id,
				$this->pdo->escapeString($type),
				$this->pdo->escapeString($lang),
				$this->pdo->escapeString($title)
			)
		);
	}

	/**
	 * Retrieves supplemental anime info from the AniDB API
	 *
	 * @return array|bool
	 */
	private function getAniDbAPI($anidbId)
	{
		$timestamp = Settings::value('APIs.AniDB.banned') + 90000;
		if ($timestamp > time()) {
			echo 'Banned from AniDB lookups until ' . date('Y-m-d H:i:s', $timestamp) . PHP_EOL;
			return false;
		}
		$apiresponse = $this->getAniDbResponse($anidbId);

		$AniDBAPIArray = [];

		if ($apiresponse === false) {
			echo 'AniDB: Error getting response.' . PHP_EOL;
		} elseif (preg_match('/\<error\>Banned\<\/error\>/', $apiresponse)) {
			$this->banned = true;
			Settings::update(
				['value' => time()],
				['section' => 'APIs', 'subsection' => 'AniDB', 'name' => 'banned']
			);
		} elseif (preg_match('/\<error\>Anime not found\<\/error\>/', $apiresponse)) {
			echo "AniDB   : Anime not yet on site. Remove until next update.\n";
		} elseif ($AniDBAPIXML = new \SimpleXMLElement($apiresponse)) {

			$AniDBAPIArray['similar'] = $this->processAPIResponseElement($AniDBAPIXML->similaranime, 'anime', false);
			$AniDBAPIArray['related'] = $this->processAPIResponseElement($AniDBAPIXML->relatedanime, 'anime', false);
			$AniDBAPIArray['creators'] = $this->processAPIResponseElement($AniDBAPIXML->creators, null, false);
			$AniDBAPIArray['characters'] = $this->processAPIResponseElement($AniDBAPIXML->characters, null, true);
			$AniDBAPIArray['categories'] = $this->processAPIResponseElement($AniDBAPIXML->categories, null, true);

			$episodeArray = [];
			if ($AniDBAPIXML->episodes && $AniDBAPIXML->episodes->episode[0]->attributes()) {
				$i = 1;
				foreach ($AniDBAPIXML->episodes->episode as $episode) {
					$titleArray = [];

					$episodeArray[$i]['episode_id'] = (int)$episode->attributes()->id;
					$episodeArray[$i]['episode_no'] = (int)$episode->epno;
					$episodeArray[$i]['airdate'] = (string)$episode->airdate;

					if (!empty($episode->title)) {
						foreach ($episode->title as $title) {
							$xmlAttribs = $title->attributes('xml', true);
							// only english, x-jat imploded episode titles for now
							if (in_array($xmlAttribs->lang, ['en', 'x-jat'], false)) {
								$titleArray[] = $title[0];
							}
						}
					}

					$episodeArray[$i]['episode_title'] = empty($titleArray) ? '' : implode(', ', $titleArray);
					$i++;
				}
			}

			//start and end date come from AniDB API as date strings -- no manipulation needed
			$AniDBAPIArray['startdate'] = isset($AniDBAPIXML->startdate) ? $AniDBAPIXML->startdate : '0000-00-00';
			$AniDBAPIArray['enddate'] = isset($AniDBAPIXML->enddate) ? $AniDBAPIXML->enddate : '0000-00-00';

			if (isset($AniDBAPIXML->ratings->permanent)) {
				$AniDBAPIArray['rating'] = $AniDBAPIXML->ratings->permanent;
			} else {
				$AniDBAPIArray['rating'] = isset($AniDBAPIXML->ratings->temporary) ? $AniDBAPIXML->ratings->temporary : $AniDBAPIArray['rating'] = '';
			}

			$AniDBAPIArray += [
				'type'        => isset($AniDBAPIXML->type[0]) ? (string)$AniDBAPIXML->type : '',
				'description' => isset($AniDBAPIXML->description) ? (string)$AniDBAPIXML->description : '',
				'picture'     => isset($AniDBAPIXML->picture[0]) ? (string)$AniDBAPIXML->picture : '',
				'epsarr'      => $episodeArray,
			];

			return $AniDBAPIArray;
		}

		return false;
	}

	/**
	 * @param \SimpleXMLElement $element
	 * @param string            $property
	 *
	 * @param bool              $children
	 *
	 * @return string
	 */
	private function processAPIResponseElement(\SimpleXMLElement $element, $property = null, $children = false)
	{
		$property = isset($property) ? $property : 'name';
		$temp = '';

		if (is_object($element) && !empty($element)) {
			$result = $children === true ? $element->children() : $element;
			foreach ($result as $entry) {
				$temp .= (string)$entry->$property . ', ';
			}
		}

		return (empty($temp) ? '' : substr($temp, 0, -2));
	}

	/**
	 * Requests and returns the API data from AniDB
	 *
	 * @return string
	 */
	private function getAniDbResponse($anidbId)
	{
		$curlString = sprintf(
			'http://api.anidb.net:9001/httpapi?request=anime&client=%s&clientver=%d&protover=1&aid=%d',
			$this->apiKey,
			self::CLIENT_VERSION,
			$anidbId
		);

		$ch = curl_init($curlString);

		$curlOpts = [
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_HEADER         => 0,
			CURLOPT_FAILONERROR    => 1,
			CURLOPT_ENCODING       => 'gzip'
		];

		curl_setopt_array($ch, $curlOpts);
		$apiresponse = curl_exec($ch);
		curl_close($ch);
		return $apiresponse;
	}

	/**
	 * Inserts new anime info from AniDB to anidb table
	 *
	 * @param int    $id    The AniDB ID to be inserted
	 * @param string $type  The title type
	 * @param string $lang  The title language
	 * @param string $title The title of the Anime
	 */
	private function insertAniDb($id, $type, $lang, $title)
	{
		$check = $this->checkDuplicateDbEntry($id, $type, $lang, $title);

		if ($check === false) {
			$this->pdo->queryInsert(
				sprintf('
					INSERT IGNORE INTO anidb_titles
					(anidbid, type, lang, title)
					VALUES (%d, %s, %s, %s)',
					$id,
					$this->pdo->escapeString($type),
					$this->pdo->escapeString($lang),
					$this->pdo->escapeString($title)
				)
			);
		} else {
			echo ColorCLI::warning("Duplicate: $id");
		}
	}

	/**
	 * Inserts new anime info from AniDB to anidb table
	 *
	 * @param array $AniDBInfoArray
	 *
	 * @return string
	 */
	private function insertAniDBInfoEps(array $AniDBInfoArray = [], $anidbId)
	{
		$this->pdo->queryInsert(
			sprintf('
				INSERT INTO anidb_info
				(
					anidbid, type, startdate, enddate, related, similar, creators,
					description, rating, picture, categories, characters, updated
				)
				VALUES (%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW())',
				$anidbId,
				$this->pdo->escapeString($AniDBInfoArray['type']),
				$this->pdo->escapeString($AniDBInfoArray['startdate']),
				$this->pdo->escapeString($AniDBInfoArray['enddate']),
				$this->pdo->escapeString($AniDBInfoArray['related']),
				$this->pdo->escapeString($AniDBInfoArray['similar']),
				$this->pdo->escapeString($AniDBInfoArray['creators']),
				$this->pdo->escapeString($AniDBInfoArray['description']),
				$this->pdo->escapeString($AniDBInfoArray['rating']),
				$this->pdo->escapeString($AniDBInfoArray['picture']),
				$this->pdo->escapeString($AniDBInfoArray['categories']),
				$this->pdo->escapeString($AniDBInfoArray['characters'])
			)
		);
		if (!empty($AniDBInfoArray['epsarr'])) {
			$this->insertAniDBEpisodes($AniDBInfoArray['epsarr'], $anidbId);
		}

		return $AniDBInfoArray['picture'];
	}

	/**
	 * Inserts new anime info from AniDB to anidb table
	 *
	 * @param array $episodeArr
	 */
	private function insertAniDBEpisodes(array $episodeArr = [], $anidbId)
	{
		if (!empty($episodeArr)) {
			foreach ($episodeArr as $episode) {
				$this->pdo->queryInsert(
					sprintf('
						INSERT IGNORE INTO anidb_episodes
						(anidbid, episodeid, episode_no, episode_title, airdate)
						VALUES (%d, %d, %d, %s, %s)',
						$anidbId,
						$episode['episode_id'],
						$episode['episode_no'],
						$this->pdo->escapeString($episode['episode_title']),
						$this->pdo->escapeString($episode['airdate'])
					)
				);
			}
		}
	}

	/**
	 *  Grabs AniDB Full Dump XML and inserts it into anidb table
	 */
	private function populateMainTable()
	{
		$lastUpdate = (new \DateTime)->setTimestamp($this->lastUpdate);
		$current = new \DateTime();

		if ($current->diff($lastUpdate)->format('%d') > $this->updateInterval) {
			if ($this->echooutput) {
				echo ColorCLI::header('Updating anime titles by grabbing full data AniDB dump.');
			}

			$animetitles = new \SimpleXMLElement('compress.zlib://http://anidb.net/api/anime-titles.xml.gz', null, true);

			//Even if the update process fails,
			//we must mark the last update time or risk ban
			$this->setLastUpdated();

			if ($animetitles instanceof \Traversable) {
				$count = $animetitles->count();
				if ($this->echooutput) {
					echo ColorCLI::header(
						'Total of ' . number_format($count) . ' titles to add.' . PHP_EOL
					);
				}

				foreach ($animetitles as $anime) {
					echo "Remaining: $count  \r";
					foreach ($anime->title as $title) {
						$xmlAttribs = $title->attributes('xml', true);
						$this->insertAniDb(
							(string)$anime['aid'],
							(string)$title['type'],
							(string)$xmlAttribs->lang,
							(string)$title[0]
						);
						ColorCLI::primary(
							sprintf(
								'Inserting: %d, %s, %s, %s',
								$anime['aid'],
								$title['type'],
								$xmlAttribs->lang,
								$title[0]
							)
						);
					}
					$count--;
				}
			} else {
				echo PHP_EOL .
					ColorCLI::error('Error retrieving XML data from AniDB. Please try again later.') .
					PHP_EOL;
			}
		} else {
			echo PHP_EOL . ColorCLI::info(
					'AniDB has been updated within the past ' . $this->updateInterval . ' days. ' .
					'Either set this value lower in Site Edit (at your own risk of being banned) or try again later.');
		}
	}

	/**
	 * Directs flow for populating the AniDB Info/Episodes table
	 *
	 * @param string $anidbId
	 */
	private function populateInfoTable($anidbId = '')
	{
		if (empty($anidbId)) {
			$anidbIds = $this->pdo->query(sprintf(
					'SELECT DISTINCT at.anidbid
					FROM anidb_titles at
					LEFT JOIN anidb_info ai ON ai.anidbid = at.anidbid
                    WHERE ai.updated IS NULL'
				)
			);
			foreach ($anidbIds as $anidb) {
				$AniDBAPIArray = $this->getAniDbAPI($anidb['anidbid']);

				if ($this->banned === true) {
					ColorCLI::doEcho(
						ColorCLI::error(
							'AniDB Banned, import will fail, please wait 24 hours before retrying.'
						),
						true
					);
					exit;
				}

				if ($AniDBAPIArray === false && $this->echooutput) {
					ColorCLI::doEcho(
						ColorCLI::info(
							'Anime ID: ' . $anidb['anidbid'] . ' not available for update yet.'
						),
						true
					);
				} else {
					$this->updateAniChildTables($AniDBAPIArray, $anidb['anidbid']);
					if (nZEDb_DEBUG) {
						ColorCLI::doEcho(
							ColorCLI::headerOver(
								'Added/Updated AniDB ID: ' . $anidb['anidbid']
							),
							true
						);
					}
				}
				sleep(random_int(120, 240));
			}
		} else {
			$AniDBAPIArray = $this->getAniDbAPI($anidbId);

			if ($this->banned === true) {
				ColorCLI::doEcho(
					ColorCLI::error(
						'AniDB Banned, import will fail, please wait 24 hours before retrying.'
					),
					true
				);
				exit;
			}

			if ($AniDBAPIArray === false && $this->echooutput) {
				ColorCLI::doEcho(
					ColorCLI::info(
						'Anime ID: ' . $anidbId . ' not available for update yet.'
					),
					true
				);
			} else {
				$this->updateAniChildTables($AniDBAPIArray, $anidbId);
				if (nZEDb_DEBUG) {
					ColorCLI::doEcho(
						ColorCLI::headerOver(
							'Added/Updated AniDB ID: ' . $anidbId
						),
						true
					);
				}
			}
		}
	}

	/**
	 * Sets the database time for last full AniDB update
	 */
	private function setLastUpdated()
	{
		Settings::update(
			['value' => time()],
			['section' => 'APIs', 'subsection' => 'AniDB', 'name' => 'last_full_update']
		);
	}

	/**
	 * Updates existing anime info in anidb info/episodes tables
	 *
	 * @param array $AniDBInfoArray
	 *
	 * @return string
	 */
	private function updateAniDBInfoEps(array $AniDBInfoArray = [], $anidbId)
	{
		$this->pdo->queryExec(
			sprintf('
				UPDATE anidb_info
				SET type = %s, startdate = %s, enddate = %s, related = %s,
					similar = %s, creators = %s, description = %s,
					rating = %s, picture = %s, categories = %s, characters = %s,
					updated = NOW()
				WHERE anidbid = %d',
				$this->pdo->escapeString($AniDBInfoArray['type']),
				$this->pdo->escapeString($AniDBInfoArray['startdate']),
				$this->pdo->escapeString($AniDBInfoArray['enddate']),
				$this->pdo->escapeString($AniDBInfoArray['related']),
				$this->pdo->escapeString($AniDBInfoArray['similar']),
				$this->pdo->escapeString($AniDBInfoArray['creators']),
				$this->pdo->escapeString($AniDBInfoArray['description']),
				$this->pdo->escapeString($AniDBInfoArray['rating']),
				$this->pdo->escapeString($AniDBInfoArray['picture']),
				$this->pdo->escapeString($AniDBInfoArray['categories']),
				$this->pdo->escapeString($AniDBInfoArray['characters']),
				$anidbId
			)
		);
		if (!empty($AniDBInfoArray['epsarr'])) {
			$this->insertAniDBEpisodes($AniDBInfoArray['epsarr'], $anidbId);
		}

		return $AniDBInfoArray['picture'];
	}

	/**
	 * Directs flow for updating child AniDB tables
	 *
	 * @param array $AniDBInfoArray
	 * @param       $anidbId
	 */
	private function updateAniChildTables(array $AniDBInfoArray = [], $anidbId)
	{
		$check = $this->pdo->queryOneRow(
			sprintf('
				SELECT ai.anidbid AS info
				FROM anidb_info ai
				WHERE ai.anidbid = %d',
				$anidbId
			)
		);

		if ($check === false) {
			$picture = $this->insertAniDBInfoEps($AniDBInfoArray, $anidbId);
		} else {
			$picture = $this->updateAniDBInfoEps($AniDBInfoArray, $anidbId);
		}

		if (!empty($picture) && !file_exists($this->imgSavePath . $anidbId . '.jpg')) {
			(new ReleaseImage($this->pdo))->saveImage(
				$anidbId,
				'http://img7.anidb.net/pics/anime/' . $picture,
				$this->imgSavePath
			);
		}
	}
}
