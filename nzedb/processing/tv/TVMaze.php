<?php
namespace nzedb\processing\tv;
use \libs\JPinkney\TVMaze\Client;

/**
 * Class TVMaze
 *
 * Process information retrieved from the TVMaze API.
 */
class TVMaze extends TV
{
	/**
	 * Client for TVMaze API
	 *
	 * @var \libs\JPinkney\TVMaze\Client
	 */
	public $client;

	/**
	 * Construct. Instanciate TVMaze Client Class
	 *
	 * @param array $options Class instances.
	 *
	 * @access public
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);
		$this->client = new Client();
	}

	/**
	 * Fetch banner from site.
	 *
	 * @param $videoId
	 * @param $siteID
	 *
	 * @return bool
	 */
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
		return false;
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
		return false;
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
		return false;
	}
}