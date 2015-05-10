<?php

namespace nzedb\db\populate;

use nzedb\ReleaseImage;
use nzedb\db\Settings;

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
	 * @var \nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * The AniDB ID we are looking up
	 * @var bool
	 */
	private $anidbId;

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
		$this->pdo = ($options['Settings'] instanceof Settings ? $options['Settings'] : new Settings());

		//		$maxanidbprocessed = $this->pdo->getSetting('maxanidbprocessed');
		$anidbupdint = $this->pdo->getSetting('intanidbupdate');
		$lastupdated = $this->pdo->getSetting('lastanidbupdate');

		$this->imgSavePath = nZEDb_COVERS . 'anime' . DS;
		$this->apiKey      = $this->pdo->getSetting('anidbkey');

		$this->updateInterval = (isset($anidbupdint) ? $anidbupdint : '7');
		$this->lastUpdate     = (isset($lastupdated) ? $lastupdated : '0');
		$this->banned         = false;
	}

	/**
	 * Main switch that initiates AniDB table population
	 *
	 * @param string  $type
	 * @param integer $anidbId
	 */
	public function populateTable($type = '', $anidbId = 0)
	{
		switch ($type) {
			case 'full':
				$this->populateMainTable();
				break;
			case 'info':
				$this->anidbId = $anidbId;
				$this->populateInfoTable();
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
	private function getAniDbAPI()
	{
		$timestamp = $this->pdo->getSetting('APIs.AniDB.banned') + 90000;
		if ($timestamp > time()) {
			echo "Banned from AniDB lookups until " . date('Y-m-d H:i:s', $timestamp) . "\n";
			return false;
		}
		$apiresponse = $this->getAniDbResponse();

		$AniDBAPIArray = [];

		if (!$apiresponse) {
			echo "AniDB: Error getting response." . PHP_EOL;
		} elseif (preg_match("/\<error\>Banned\<\/error\>/", $apiresponse)) {
			$this->banned = true;
			$this->pdo->setSetting(['APIs.AniDB.banned' => time()]);
		} elseif (preg_match("/\<error\>Anime not found\<\/error\>/", $apiresponse)) {
			echo "AniDB   : Anime not yet on site. Remove until next update.\n";
		} elseif ($AniDBAPIXML = new \SimpleXMLElement($apiresponse)) {
			$AniDBAPIArray['similar'] = $this->processAPIResponceElement($AniDBAPIXML->similaranime, 'anime');

			$AniDBAPIArray['related'] = $this->processAPIResponceElement($AniDBAPIXML->relatedanime, 'anime');

			$AniDBAPIArray['creators'] = $this->processAPIResponceElement($AniDBAPIXML->creators);

			$AniDBAPIArray['characters'] = $this->processAPIResponceElement($AniDBAPIXML->characters);

			$AniDBAPIArray['categories'] = $this->processAPIResponceElement($AniDBAPIXML->categories);

			$episodeArray = [];
			if ($AniDBAPIXML->episodes && $AniDBAPIXML->episodes[0]->attributes()) {
				$i = 1;
				foreach ($AniDBAPIXML->episodes->episode as $episode) {
					$titleArray = [];

					$episodeArray[$i]['episode_id'] = (int)$episode->attributes()->id[0];
					$episodeArray[$i]['episode_no'] = (int)$episode->epno;
					$episodeArray[$i]['airdate']    = (string)$episode->airdate;

					if ($AniDBAPIXML->title && $AniDBAPIXML->title[0]->attributes()) {
						foreach ($AniDBAPIXML->title->children() as $title) {
							$xmlAttribs = $title->attributes('xml', true);
							// only english, x-jat imploded episode titles for now
							if (in_array($xmlAttribs->lang, ['en', 'x-jat'])) {
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
			$AniDBAPIArray['enddate']   = isset($AniDBAPIXML->enddate) ? $AniDBAPIXML->enddate : '0000-00-00';

			if (isset($AniDBAPIXML->ratings->permanent)) {
				$AniDBAPIArray['rating'] = $AniDBAPIXML->ratings->permanent;
			} else {
				$AniDBAPIArray['rating'] = isset($AniDBAPIXML->ratings->temporary) ?
					$AniDBAPIXML->ratings->temporary : $AniDBAPIArray['rating'] = '';
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
	 * @return string
	 */
	private function processAPIResponceElement(\SimpleXMLElement $element, $property = null)
	{
		$property = empty($property) ? 'name' : $property;
		$temp     = '';
		if ($element && $element[0]->attributes()) {
			foreach ($element->children() as $entry) {
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
	private function getAniDbResponse()
	{
		$curlString = sprintf(
			'http://api.anidb.net:9001/httpapi?request=anime&client=%s&clientver=%d&protover=1&aid=%d',
			$this->apiKey,
			self::CLIENT_VERSION,
			$this->anidbId
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
								VALUES
									(%d, %s, %s, %s)',
							  $id,
							  $this->pdo->escapeString($type),
							  $this->pdo->escapeString($lang),
							  $this->pdo->escapeString($title)
					  ));
		} else {
			echo $this->pdo->log->warning("Duplicate: $id");
		}
	}

	/**
	 * Inserts new anime info from AniDB to anidb table
	 *
	 * @param array $AniDBInfoArray
	 *
	 * @return string
	 */
	private function insertAniDBInfoEps(array $AniDBInfoArray = [])
	{
		$this->pdo->queryInsert(
				  sprintf('
						INSERT INTO anidb_info (anidbid, type, startdate, enddate, related, similar, creators, description, rating, picture, categories, characters, updated)
						VALUES
							(%d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, NOW())',
						  $this->anidbId,
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

		if (isset($AniDBInfoArray['epsarr'])) {
			$this->insertAniDBEpisodes($AniDBInfoArray['epsarr']);
		}
		return $AniDBInfoArray['picture'];
	}

	/**
	 * Inserts new anime info from AniDB to anidb table
	 *
	 * @param array $episodeArr
	 */
	private function insertAniDBEpisodes(array $episodeArr = [])
	{
		if (!empty($episodeArr)) {
			foreach ($episodeArr as $episode) {
				$this->pdo->queryInsert(
						  sprintf('
								INSERT IGNORE INTO anidb_episodes (anidbid, episodeid, episode_no, episode_title, airdate)
								VALUES (%d, %d, %d, %s, %s)',
								  $this->anidbId,
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
		if ((time() - (int)$this->lastUpdate) > ((int)$this->updateInterval * 86400)) {

			if ($this->echooutput) {
				echo $this->pdo->log->header("Updating anime titles by grabbing full data AniDB dump.");
			}

			$animetitles = new \SimpleXMLElement("compress.zlib://http://anidb.net/api/anime-titles.xml.gz", null, true);
			/*
			$lines = gzfile(realpath(nZEDb_ROOT . '..' . DS . 'anime-titles.xml.gz'));
			$file = implode('', $lines);
			$animetitles = new \SimpleXMLElement($file, 0, false);
			*/

			//Even if the update process fails,
			//we must mark the last update time or risk ban
			$this->setLastUpdated();

			if ($animetitles instanceof \Traversable) {
				$count = count($animetitles);
				if ($this->echooutput) {
					echo $this->pdo->log->header(
										"Total of " . number_format($count) .
										" titles to add." . PHP_EOL
					);
				}

				foreach ($animetitles as $anime) {
					echo "Remaining: $count  \r";
					foreach ($anime->title as $title) {
						$xmlAttribs = $title->attributes('xml', true);
						$this->insertAniDb((string)$anime['aid'],
										   (string)$title['type'],
										   (string)$xmlAttribs->lang,
										   (string)$title[0]);
						$this->pdo->log->primary(
									   "Inserting: %d, %s, %s, %s",
									   $anime['aid'],
									   $title['type'],
									   $xmlAttribs->lang,
									   $title[0]);
					}
					$count--;
				}
			} else {
				echo PHP_EOL .
					 $this->pdo->log->error("Error retrieving XML data from AniDB. Please try again later.") .
					 PHP_EOL;
			}
		} else {
			echo PHP_EOL . $this->pdo->log->info(
										  "AniDB has been updated within the past {$this->updateInterval} days.  " .
										  "Either set this value lower in Site Edit (at your own risk of being banned) or try again later.") .
				 PHP_EOL;
		}
	}

	/**
	 * Directs flow for populating the AniDB Info/Episodes table
	 */
	private function populateInfoTable()
	{
		$AniDBAPIArray = $this->getAniDbAPI();

		if ($this->banned === true) {
			$this->pdo->log->doEcho($this->pdo->log->error("AniDB Banned, import will fail, please wait 24 hours before retrying."),
									true);
			exit;
		} elseif ($AniDBAPIArray === false && $this->echooutput) {
			$this->pdo->log->doEcho($this->pdo->log->info("Anime ID: {$this->anidbId} not available for update yet."),
									true);
		} else {
			$this->updateAniChildTables($AniDBAPIArray);
			if (nZEDb_DEBUG) {
				$this->pdo->log->doEcho($this->pdo->log->headerOver("Added/Updated AniDB ID: {$this->anidbId}"),
										true);
			}
		}
	}

	/**
	 * Sets the database time for last full AniDB update
	 */
	private function setLastUpdated()
	{
		$this->pdo->setSetting(['APIs.anidb.last_full_update' => time()]);
	}

	/**
	 * Updates existing anime info in anidb info/episodes tables
	 *
	 * @param array $AniDBInfoArray
	 *
	 * @return string
	 */
	private function updateAniDBInfoEps($AniDBInfoArray = [])
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
						  $this->anidbId
				  )
		);

		$this->insertAniDBEpisodes($AniDBInfoArray['epsarr']);
		return $AniDBInfoArray['picture'];
	}

	/**
	 * Directs flow for updating child AniDB tables
	 *
	 * @param array $AniDBInfoArray
	 */
	private function updateAniChildTables($AniDBInfoArray = [])
	{
		$check = $this->pdo->queryOneRow(
						   sprintf('
							SELECT ai.anidbid AS info
							FROM anidb_info ai
							WHERE ai.anidbid = %d',
								   $this->anidbId
						   )
		);

		if ($check === false) {
			$picture = $this->insertAniDBInfoEps($AniDBInfoArray);
		} else {
			$picture = $this->updateAniDBInfoEps($AniDBInfoArray);
		}

		if (!empty($picture) && !file_exists($this->imgSavePath . $this->anidbId . ".jpg")) {
			(new ReleaseImage($this->pdo))->saveImage(
										   $this->anidbId,
										   'http://img7.anidb.net/pics/anime/' . $picture,
										   $this->imgSavePath
			);
		}
	}
}
