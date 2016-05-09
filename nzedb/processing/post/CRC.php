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
 * @copyright 2016 nZEDb
 */
namespace nzedb\processing\post;


class CRC
{
	/**
	 * @var \plugins\processing\post\CRC Usually, but configurable.
	 */
	private $crc = null;

	public function __construct(array $config = [])
	{
		$defaults = [
			'plugin' => '\plugins\processing\post\CRC'
		];
		$config += $defaults;

		if (class_exists()) {
			$this->crc = new $config['plugin'];
		}
	}

	/**
	 * Checks last file CRC information from PP Add's Data Summary for PreDB/SrrDB matches
	 *
	 * @param array  $release  The release being checked
	 * @param string $crcHash  The CRC hash to look up
	 * @param int    $fileSize The Size to look up in conjunction with CRC
	 * @param string $osoHash  The OpenSubtitles hash to look up
	 * @param string $fileDate The UNIXTIME date from the packed file
	 *
	 * @return bool|int False if no match, PreID if match found
	 */
	public function checkCRCInfo($release, $crcHash = '', $fileSize = 0, $osoHash = '', $fileDate = '')
	{
		if (is_null($this->crc)) {
			return null; // placeholder we should return whatever the caller consumes but
			// unchanged or in a state indicating nothin was done.
		} else {
			return $this->crc->checkCRCInfo();
		}

	}

	/**
	 * Looks up PRE Information (SrrDB sourced) using CRC/Size info
	 *
	 * @return false|array $preInfo The JSON decoded associative array
	 */
	public function checkWeb()
	{
	}

	/**
	 * Tries to find a local pre using the NZBFinder response's releasename
	 *
	 * @param string $preName The Pre title retrieved from the NZBFinder response
	 *
	 * @return false|int $preId The local PreDB ID of the scene release or false if no match found
	 */
	private function checkLocalByTitle($preName)
	{
	}

	/**
	 * Checks the local CRC database for a CRC/Size or OSO Hash match
	 *
	 * @return array|bool PDO Object including the local PreDB ID
	 */
	private function checkLocalDb()
	{
	}

	/**
	 * Sends requests to NZBFinder API
	 *
	 * @return false|string $response The Response from NZBFinder
	 */
	private function checkNZBFinder()
	{
	}

	/**
	 * Checks the NZBFinder response by looping through the keys
	 *
	 * @param array $required The required elements to be considered valid
	 * @param array $response The JSON decoded response from NZBFinder
	 *
	 * @return bool Whether or not the response has the required keys non-empty
	 */
	private function checkResponse($required, $response)
	{
	}

	/**
	 * Sends requests to SrrDB API
	 *
	 * @return false|string $response The Response from SRR DB
	 */
	private function checkSrrDB()
	{
	}

	/**
	 * Creates a new PreDB CRC table on __construct if the table doesn't exist
	 * NOTE shouldn't do this. create the table in schema, when/if people ask tell them it's for plugins.
	 */
	private function createTableIfNotExists()
	{
	}

	/**
	 * Checks a proper JSON Response is returned then decodes it
	 *
	 * @param string $response The NZBFinder response in string (JSON) format
	 *
	 * @return array|false $json The JSON decoded response from NZBFinder or false if bad response
	 */
	private function decodeResponse($response)
	{
	}

	/**
	 * Inserts a new row into the predb_crcs table when a new CRC/Filesize or OSO hash
	 * is retrieved from NZBFinder
	 *
	 * @param int   $preId   The locally matched (or inserted) PreID
	 * @param array $preInfo The JSON decoded response from NZBFinder
	 */
	private function insertNewCrc($preId, $preInfo)
	{
	}

	/**
	 * Inserts a new PreDB entry based on a new returned title from NZBFinder
	 *
	 * @param array $preInfo The returned Pre data from the NZBFinder response
	 *
	 * @return false|int Return either the inserted PreID or false for failure
	 */
	private function insertNewPre($preInfo)
	{
	}

	/**
	 * Sends a web request to NZBFinder and returns the response
	 *
	 * @param string $requestUrl  The URL of the requested source API
	 * @param string $requestPath The request path for the NZBFinder lookup
	 *
	 * @return false|string The NZBFinder response in string (JSON) format or flase if no match
	 */
	private function sendAPIRequest($requestUrl, $requestPath)
	{
	}
}
