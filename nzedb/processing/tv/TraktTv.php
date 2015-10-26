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
	 * Client for Trakt API
	 *
	 * @var \nzedb\libraries\TraktAPI
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
		$this->client = new TraktAPI(
				[
					'clientID'	=> $this->pdo->getSetting('trakttvclientkey'),
					'headers'	=> $this->requestHeaders,
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
