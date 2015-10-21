<?php
namespace nzedb\processing\tv;

use nzedb\libraries\TraktAPI;

/**
 * Class TraktTv
 *
 * Process information retrieved from the Trakt API.
 */
class TraktTv extends TV
{
	/**
	 * The Trakt.tv API v2 Client ID (SHA256 hash - 64 characters long string). Used for movie and tv lookups.
	 * Create one here: https://trakt.tv/oauth/applications/new
	 *
	 * @var array|bool|string
	 */
	private $clientID;

	/**
	 * List of headers to send to Trakt.tv when making a request.
	 *
	 * @see http://docs.trakt.apiary.io/#introduction/required-headers
	 * @var array
	 */
	private $requestHeaders;

	/**
	 * Library for Trakt API
	 *
	 * @var \libs\TraktAPI
	 */
	public $client;

	/**
	 * Construct. Set up API key.
	 *
	 * @param array $options Class instances.
	 *
	 * @access public
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);
		$this->clientID = $this->pdo->getSetting('trakttvclientkey');
		$this->requestHeaders = [
			'Content-Type: application/json',
			'trakt-api-version: 2',
			'trakt-api-key: ' . $this->clientID
		];
		$this->client = new TraktAPI(
				[
					'headers' => $this->requestHeaders,
					'clientID' => $this->clientID
				]
		);
	}

	public function getBanner($videoId, $siteID)
	{
		return false;
	}

	/**
	 * Retrieve info of TV episode from site using its API.
	 *
	 * @param integer $siteId
	 * @param integer $series
	 * @param integer $episode
	 *
	 * @return array|false    False on failure, an array of information fields otherwise.
	 */
	public function getEpisodeInfo($siteId, $series, $episode)
	{
		;
	}

	/**
	 * Retrieve poster image for TV episode from site using its API.
	 *
	 * @param integer $videoId ID from videos table.
	 * @param integer $siteId  ID that this site uses for the programme.
	 *
	 * @return null
	 */
	public function getPoster($videoId, $siteId)
	{
		;
	}

	/**
	 * Retrieve info of TV programme from site using it's API.
	 *
	 * @param string $name Title of programme to look up. Usually a cleaned up version from releases table.
	 *
	 * @return array|false    False on failure, an array of information fields otherwise.
	 */
	public function getShowInfo($name)
	{
		;
	}
}
