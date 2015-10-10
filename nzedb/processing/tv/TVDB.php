<?php
namespace nzedb\processing\tv;

use nzedb\utility\Misc;
use nzedb\processing\tv\Moinax\TVDB\Client;

/**
 * Class TVDB
 */
class TVDB extends TV
{
	const TVDB_URL = 'http://thetvdb.com';
	const TVDB_API_KEY = '5296B37AEC35913D';
	const MATCH_PROBABILITY = 75;

	/**
	 * @var \nzedb\processing\tv\Moinax\TVDB\Client
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
}
