<?php
namespace nzedb\processing\tv;

use nzedb\utility\Misc;
use libs\Moinax\TVDB\Client;

/**
 * Class TVDB
 */
class TVDB extends TV
{
	const TVDB_URL = 'http://thetvdb.com';
	const TVDB_API_KEY = '5296B37AEC35913D';
	const MATCH_PROBABILITY = 75;

	/**
	 * @var \libs\Moinax\TVDB\Client
	 */
	public $client;

	/**
	 * @param array $options Class instances / Echo to cli?
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);
		$this->client = new Client(self::TVDB_URL, self::TVDB_API_KEY);
	}

	public function processTVDB ($groupID, $guidChar, $processTV, $local = false)
	{
		$res = $this->getTvReleases($groupID, $guidChar, $lookupSetting, $local, parent::PROCESS_TVDB);

		$tvcount = count($res);

		if ($this->echooutput && $tvcount > 1) {
			echo $this->pdo->log->header("Processing TVDB lookup for " . number_format($tvcount) . " release(s).");
		}

		if ($res instanceof \Traversable) {
			foreach ($res as $arr) {
				$show = $this->parseNameEpSeason($arr['searchname']);
				if (is_array($show) && $show['name'] != '') {

					// Find the Video ID if it already exists by checking the title.
					$video = $this->getByTitle($show['cleanname']);

					// Force local lookup only
					if ($local == true) {
						$lookupSetting = false;
					}

					if ($video === false && $lookupSetting) {

						// If it doesnt exist locally and lookups are allowed lets try to get it.
						if ($this->echooutput) {
							echo $this->pdo->log->primaryOver("Video ID for ") .
								 $this->pdo->log->headerOver($show['cleanname']) .
								 $this->pdo->log->primary(" not found in local db, checking web.");
						}

						// Get the show from TVDB
						$tvdbShow = $this->getTVDBShow($show);

						if ($tvdbShow !== false && is_array($tvdbShow)) {

							// Organize the response to nZEDb format
							$prepared = $this->prepareShow($tvdbShow);

							$video = $this->add(
										'tvdb',
										$prepared['id'],
										$prepared['title'],
										$prepared['summary'],
										$prepared['country'],
										$prepared['started'],
										$prepared['publisher'],
										$prepared['hascover'],
										parent::SOURCE_TVDB
							);
						} else {
							$this->setVideoNotFound(parent::PROCESS_TRAKT, $arr['id']);
						}
					}
					if (is_numeric($video) && $video > 0) {
						$episodeId = $this->getBySeasonEp($video, $show['season'], $show['episode']);
						if ($episodeId === false && $lookupSetting) {
							$episodeId =  '';
						}
					}
				}
			}
		}
	}

	private function getTVDBShow() { }
		
}
