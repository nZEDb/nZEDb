<?php
namespace nzedb;

use nzedb\utility\Misc;
use nzedb\libraries\TVDB\Client;

/**
 * Class TVDB
 */
class TVDB extends TV
{
	const TVDB_URL = 'http://thetvdb.com';
	const TVDB_API_KEY = '5296B37AEC35913D';
	const MATCH_PROBABILITY = 75;

	/**
	 * @param array $options Class instances / Echo to cli?
	 */
	public function __construct(array $options = [])
	{
		parent::__construct($options);

		$this->client = new \nzedb\libraries\TVDB\Client(self::TVDB_URL, self::TVDB_API_KEY);
	}
}
