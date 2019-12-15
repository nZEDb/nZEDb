<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 *
 * @link      <http://www.gnu.org/licenses/>.
 * @author    niel
 * @copyright 2019 nZEDb
 */
namespace nzedb\processing\tv;

use CanIHaveSomeCoffee\TheTVDbAPI\MultiLanguageWrapper\TheTVDbAPILanguageFallback;
use CanIHaveSomeCoffee\TheTVDbAPI\Exception\ResourceNotFoundException;
use CanIHaveSomeCoffee\TheTVDbAPI\Exception\UnauthorizedException;
use nzedb\ColorCLI;
use nzedb\entity\Imdb;
use nzedb\ReleaseImage;
use nzedb\processing\Tv;


class TvDb extends Tv implements TvInterface
{
	private const MATCH_PROBABILITY = 75;

	private const TVDB_API_KEY = '5296B37AEC35913D';

	private const TVDB_URL = 'https://thetvdb.com';

	protected $bannerUrl;

	/**
	 * @var \CanIHaveSomeCoffee\TheTVDbAPI\MultiLanguageWrapper\TheTVDbAPILanguageFallback
	 */
	protected $client;

	/**
	 * @var string URL for show fanart.
	 */
	protected $fanartUrl;

	protected $imageKeyTypes = ['fanart', 'poster', 'season', 'seasonwide', 'series'];

	protected $info;

	/**
	 * @var bool Has the $this->info data been processed into fields.
	 */
	protected $isProcessed = false;

	/**
	 * @bool If server is down, do local lookups.
	 */
	protected $local;

	/**
	 * @string URL for poster art.
	 */
	protected $posterUrl;

	protected $probability;

	/**
	 * @string URL for season art.
	 */
	protected $seasonUrl;

	/**
	 * @string URL for season wide art.
	 */
	protected $seasonwideUrl;

	/**
	 * @string URL for series art.
	 */
	protected $seriesUrl;

	/**
	 * @string Timestamp of the TvDb Server.
	 */
	protected $serverTime;

	/**
	 * @var string Authorisation token from TvDb.
	 */
	protected $token;

	public function __construct(array $options = [])
	{
		parent::__construct($options);
		$this->source = self::SOURCE_TVDB;

		$this->client = new TheTVDbAPILanguageFallback();
		$this->client->setAcceptedLanguages(['en']);

		$this->local = true;
		//Use provided API key or fallback to default.
		$this->connect();
	}

	/**
	 * @inheritDoc
	 */
	public function getBanner($videoID, $siteId): ?bool
	{
		$status = null;
		if (!empty($this->bannerUrl)) {
			$status = $this->fetchImage($this->imdb->getIdPadded(), TVDB_URL . $this->bannerUrl);
		}

		return $status;
	}

	/**
	 * @inheritDoc
	 */
	public function getBannerThumbnail()
	{
		$status = null;
		if (!empty($this->bannerUrl)) {
			$info = \pathinfo($this->bannerUrl);
			$status = $this->fetchImage(
				$this->imdb->getIdPadded() . '-thumb',
				\sprintf('%s%s/%s_t.%s',
					self::TVDB_URL,
					$info['dirname'],
					$info['filename'],
					$info['extension'])
			);
		}

		return $status;
	}

	/**
	 * @inheritDoc
	 */
	public function getBannerUrl()
	{
		return $this->bannerUrl;
	}

	public function getCover($videoId, $siteId)
	{
		//ToDo extend this to go through the various alternatives
		return $this->getBanner($videoId, $siteId);
	}

	/**
	 * @inheritDoc
	 */
	public function getFanartUrl()
	{
		return $this->fanartUrl;
	}

	public function getInfo(): ?object
	{
		return $this->info;
	}

	/**
	 * @inheritDoc
	 */
	public function getPoster($videoId, $siteId): bool
	{
		$ri = new ReleaseImage();
		$haveCover = $ri->saveImage($videoId, $this->posterUrl, \nZEDb_RES . 'covers', '', '',
			false);
		return $haveCover;
	}

	public function getPosterUrl(): ?string
	{
		return $this->posterUrl;
	}

	/**
	 * @inheritDoc
	 */
	public function getSeasonUrl()
	{
		return $this->seasonUrl;
	}

	/**
	 * @inheritDoc
	 */
	public function getSeasonWideUrl()
	{
		return $this->seasonwideUrl;
	}

	/**
	 * @inheritDoc
	 */
	public function getSeriesUrl()
	{
		return $this->seriesUrl;
	}

	/**
	 * @inheritDoc
	 */
	public function getShowInfo(string $name, string $country = null): bool
	{
		$status = false;
		unset($this->info);
		$result = $this->searchTitle($name);

		if ($result === null && !empty($country)) {
			$result = $this->searchTitle(rtrim(str_replace($country, '', $name)));
		}

		if (\is_array($result) && $this->findBestMatch($result, $name)) {
			$status = $this->processInfo($name, $country);
		} else {
			ColorCLI::out("TvDb: '$name' not found!", true, 'info');
		}

		return $status;
	}

	public function getToken()
	{
		return $this->token;
	}

	/**
	 * @inheritDoc
	 */
	public function processAll($groupID, $guidChar, $process, $local = false): void
	{	//TODO break this into smaller methods
		$results = $this->getTvReleases($groupID, $guidChar, $process, parent::PROCESS_TVDB);

		if ($results instanceof \PDOStatement) {
			$tvcount = $results->rowCount();
			if ($tvcount > 0) {
				if ($this->echooutput) {
					ColorCLI::out(
						\sprintf('Processing TVDB lookup for %s release(s).',
							number_format($tvcount)),
						true,
						'header'
					);
				}

				$this->titleCache = [];
				foreach ($res as $row) {
					$tvdbid = false;
					// Clean the show name for better match probability
					$release = $this->parseInfo($row['searchname']);
					if (empty($release['name'])) { // Parsing failed, take it out of the queue for examination.
						$this->setVideoNotFound(parent::FAILED_PARSE, $row['id']);
						$this->titleCache[] = $release['cleanname'];
					} else {
						if (in_array($release['cleanname'], $this->titleCache, true)) {
							if ($this->echooutput) {
								ColorCLI::out(
									ColorCLI::header('Title: ', false) .
									ColorCLI::warning((string)$release['cleanname'], false) .
									ColorCLI::header(' already failed lookup for this site.  Skipping.',
										true)
								);
							}

							$this->setVideoNotFound('', $row['id']);
							continue;
						}
						// Find the Video ID if it already exists by checking the title.
						$videoId = $this->getByTitle($release['cleanname'], parent::TYPE_TV);

						if ($videoId !== false) {
							$tvdbid = $this->getSiteByID('tvdb', $videoId);
						}

						// If only local lookup is indicated, set the flag.
						$lookupSetting = $local === true || $this->local === true;

						// If it doesnt exist locally and lookups are allowed, lets try to get it.
						if ($tvdbid === false && $lookupSetting) {
							if ($this->echooutput) {
								ColorCLI::out(
									ColorCLI::primaryNoNL('Video ID for ') .
									ColorCLI::headerNoNL($release['cleanname']) .
									ColorCLI::primary('not found in local db, checking web.',
										true)
								);
							}

							// Check if we have a valid country code and set it.
							$country = $release['country'] ?? '';

							// Get the show from TVDB
							if ($this->getShowInfo((string)$release['cleanname'], $country)) {
								$tvdbShow['country'] = $country;
								$videoId = $this->add($tvdbShow);
								$tvdbid = (int)$tvdbShow['tvdb'];
							}
						} else if ($this->echooutput && $tvdbid !== false) {
							ColorCLI::out(
								ColorCLI::primary('Video ID for ', false) .
								ColorCLI::header($release['cleanname']) .
								ColorCLI::primary(' found in local db, attempting episode match.', true)
							);
						}

						if ($videoId && $tvdbid) { // Now we have both IDs, time to try fetching a cover
							$this->getCover($videoId, $tvdbid);

							$seriesNo = !empty($release['season']) ?
								preg_replace('/^S0*/i', '', $release['season']) : '';
							$episodeNo = !empty($release['episode']) ?
								preg_replace('/^E0*/i', '', $release['episode']) : '';

							if ($episodeNo === 'all') { // Set the video ID and leave episode as 0
								$this->setVideoIdFound($videoId, $row['id'], 0);
								ColorCLI::out('Found TVDB Match for Full Season!', 'primary', false);
								continue;
							}

							// If it is a new entry, download all episode info to save on API usage.
							if ($this->countEpsByVideoID($videoId) === false) {
								$this->getEpisodeInfo($tvdbid, -1, -1, null, $videoId);
							}

							// Do we have the episode info for this video ID.
							$episode = $this->getBySeasonEp($videoId,
								$seasonNo,
								$episodeNo,
								$release['airdate']);

							if ($episode === false && $lookupSetting) {
								// Request the episode info from TvDb.
								$tvdbEpisode = $this->getEpisodeInfo(
									$tvdbid.
									$seriesNo,
									$episodeNo,
									$release['airdate']
								);

								if ($tvdbEpisode) {
									$episode = $this->addEpisode($videoId, $tvdbEpisode);
								}
							}

							if ($episode !== false) {
								// Mark the release's video and episode IDs
								$this->setVideoIdFound($videoId, $row['id'], $episode);
								if ($this->echooutput) {
									ColorCLI::out('Found TvDb Match!', 'primary' , true);
								}
							} else { // Failed, set the episode ID to the next processing group.
								$this->setVideoNotFound(-1, $row['id']);
							}
						} else { // Failed, set the episode ID to the next processing group.
							$this->setVideoNotFound(-1, $row['id']);
							$this->titleCache[] = $release['cleanname'];
						}
					}
				}
			}
		}
	}

	public function processInfo(string $name, string $country = null): bool
	{
		$this->isProcessed = false;
		if (isset($this->info)) {
			$this->aliases = $this->info->aliases;
			$this->bannerUrl = $this->info->banner;
			$this->country = $country;
			$this->imdb = new Imdb($this->client->series()->getById($this->info->id)->imdbId);
			$this->publisher = $this->info->network;
			$this->started = $this->info->firstAired;
			$this->summary = $this->info->overview;
			$this->title = $this->info->seriesName;
			$this->tvdb = (int)$this->info->id;

			foreach ($this->imageKeyTypes as $keyType) {
				$this->getImageUrl($keyType);
			}

			$this->isProcessed = !$this->isProcessed;
		}

		return $this->isProcessed;
	}

	public function processSite($groupID, $guidChar, $process, $local = false)
	{
		$this->processAll($groupID, $guidChar, $process, $local);
	}

	/**
	 * @inheritDoc
	 */
	public function searchTitle(string $name): ?array
	{
		ColorCLI::out("Searching for '$name'", 'info', true);
		$result = null;
		try {
			$result = $this->client->search()->searchByName($name);
		} catch (ResourceNotFoundException $exception) {
			$result = null;
		}

		return $result;
	}

	protected function connect(?string $apiKey = null): bool
	{
		if ($this->local) {
			$apiKey = $apiKey ?? self::TVDB_API_KEY;
			try {
				$this->token = $this->client->authentication()->login($apiKey);
			} catch (UnauthorizedException $exception) {
				ColorCLI::out('Could not reach TvDb API. Running in local mode only!', 'warning', true);
			}

			if ($this->token) {
				$this->client->setToken($this->token);
				$this->local = false;
				ColorCLI::out('Connected to TvDb API.', 'notice', true);
			}
		}

		return $this->local;
	}

	protected function fetchImage($name, $url, string $savePath = \nZEDb_RES . 'covers/'): bool
	{
		$ri = new ReleaseImage();
		$haveImage = $ri->saveImage(
			$name,
			$url,
			$savePath,
			'',
			'',
			false
		);

		return $haveImage;
	}

	protected function findBestMatch(
		array $result,
		string $name,
		float $matchProbability = self::MATCH_PROBABILITY,
		bool $overwrite = true
	): bool
	{
		if (!$overwrite) {
			$this->info = [];
			$this->probability = 0;
		}

		$nameLowerCased = strtolower($name);

		foreach ($result as $title) {

			if ($this->checkRequiredAttr($title, 'tvdbS')) {
				ColorCLI::out("Checking for match against: {$title->seriesName}   \r", 'primary',
					false);
				// Check for exact match first and exit early if found
				if (strtolower($title->seriesName) === $nameLowerCased) {
					$this->probability = 99.999;
					$this->info = $title;
					break;
				}

				// Check each title for similarity and then find the highest similar value
				$match = $this->checkMatch(
					strtolower($title->seriesName),
					$nameLowerCased,
					$matchProbability);

//				echo 'Probability: ' . $match . ' for ' . $title->seriesName . \PHP_EOL;

				// If latest match has a higher percentage, set it as new best title
				if ($match > $this->probability) {
					[$this->probability, $this->info] = [$match, $title];
				}

				// Check for title aliases and try matching against those too
				if (!empty($title->aliases)) {
					// Use current probability value as it is already meets our threshhold.
					$this->findBestMatch($title->aliases, $nameLowerCased, false, $this->probability);
				}
			}
		}
		//ColorCLI::out((" \n"), null, true);
		if ($this->probability > 0) {
			ColorCLI::out("{$this->probability}% probable match '{$title->seriesName}'", 'info',
				true);
		}


		return $this->probability > 0;
	}

	/**
	 * @inheritDoc
	 */
	protected function formatEpisodeInfo($episode)
	{
		return [
			'episode'     => (int)$episode->airedEpisodeNumber,
			'firstaired'  => $episode->firstAired,
			'title'       => $episode->episodeName,
			'series'      => (int)$episode->airedSeason,
			'se_complete' => sprintf(
				'S%02dE%02d',
				$episode->airedSeason,
				$episode->airedEpisodeNumber
			),
			'summary'     => $episode->overview,
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function formatShowInfo($title): array
	{
		$imdb = new Imdb($this->client->series()->getById($title->id));

		// TODO: Implement formatShowInfo() method.

		return [
			'aliases'   => (!empty($title->aliasNames) ? (array)$title->aliasNames : ''),
//			'fanart' => '',	//to be implemented later
			'imdb'      => $imdb,
			'localzone' => "''",
//			'poster' => '',	//to be implemented later
			'publisher' => $title->network,
//			'season' => '',	//to be implemented later
//			'seasonwide' => '',	//to be implemented later
//			'series' => '',	//to be implemented later
			'source'    => self::SOURCE_TVDB,
			'started'   => $title->firstAired->format('Y-m-d'),
			'summary'   => $title->overview,
			'title'     => $title->name,
			'tmdb'      => 0,
			'trakt'     => 0,
			'tvdb'      => (int)$title->id,
			'tvmaze'    => 0,
			'tvrage'    => 0,
			'type'      => self::TYPE_TV,
		];
	}

	/**
	 * @inheritDoc
	 */
	protected function getEpisodeInfo($siteId, $series = -1, $episode = -1, $airDate = null,
									  $videoId = null): bool
	{
		$error = false;

		if ($airDate !== '') {
			try {
				$this->episodes = $this->client->series()
					->getEpisodesWithQuery($siteId, ['firstAired' => $airDate])->getData();
			} catch (ResourceNotFoundException $exception) {
				$error = true;
			}
		} else if ($videoId > 0) {
			try {
				$this->episodes = $this->client->series()->getEpisodes($siteId)->getData();
			} catch (ResourceNotFoundException $exception) {
				$error = true;
			}
		} else {
			try {
				$this->episodes = $this->client->series()
					->getEpisodesWithQuery($siteId,
						['airedSeason' => $series, 'airedEpisode' => $episode])->getData();
			} catch (ResourceNotFoundException $exception) {
				$error = true;
			}
		}

		if (!$error) {
			sleep(0.5);

// $his->episodes should always be an array of BasicEpisode objects, so check if it is an empty one.
// Being \CanIHaveSomeCoffee\TheTVDbAPI\Model\BasicEpisode objects also guarantees the fields.
			if ($videoId !== null && !empty($this->episodes)) {
				foreach ($this->episodes as $ep) {
					$this->addEpisode($videoId, $this->formatEpisodeInfo($ep));
				}
			}
		}

		return !$error;
	}

	protected function getImageUrl(string $keyType)
	{
		$field = $keyType . 'Url';
		if (\in_array($keyType, $this->imageKeyTypes, false)) {
			unset($this->$field);
			try {
				$image = $this->client->series()
					->getImagesWithQuery($this->info->id, ['keyType' => $keyType]);
				$this->$field = $image[0]->thumbnail ?? null;
			} catch (ResourceNotFoundException $exception) {
				ColorCLI::out(\ucfirst($keyType) . ' image not found on TvDb', 'info', true);
			}
		} else {
			ColorCLI::out("No processing for: $keyType", 'primary', true);
		}

		return !empty($this->$field);
	}
}
