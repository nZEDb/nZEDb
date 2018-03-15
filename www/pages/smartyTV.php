<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 *
 * @link      <http://www.gnu.org/licenses/>.
 *
 * @author    niel
 * @copyright 2015 nZEDb
 */
require_once '../../nzedb/processing/tv/TV.php';

use nzedb\processing\tv\TV;

class smartyTV extends TV
{
	/**
	 * Main processing director function for scrapers
	 * Calls work query function and initiates processing.
	 *
	 * @param      $groupID
	 * @param      $guidChar
	 * @param      $process
	 * @param bool $local
	 */
	protected function processSite($groupID, $guidChar, $process, $local = false)
	{
		;
	}

	protected function getBanner($videoID, $siteId)
	{
		;
	}

	/**
	 * Retrieve info of TV episode from site using its API.
	 *
	 * @param int $siteId
	 * @param int $series
	 * @param int $episode
	 *
	 * @return array|false False on failure, an array of information fields otherwise.
	 */
	protected function getEpisodeInfo($siteId, $series, $episode)
	{
		;
	}

	/**
	 * Retrieve poster image for TV episode from site using its API.
	 *
	 * @param int $videoId ID from videos table.
	 * @param int $siteId  ID that this site uses for the programme.
	 *
	 * @return null
	 */
	protected function getPoster($videoId, $siteId)
	{
		;
	}

	/**
	 * Retrieve info of TV programme from site using it's API.
	 *
	 * @param string $name Title of programme to look up. Usually a cleaned up version from releases table.
	 *
	 * @return array|false False on failure, an array of information fields otherwise.
	 */
	protected function getShowInfo($name)
	{
		;
	}

	/**
	 * Assigns API show response values to a formatted array for insertion
	 * Returns the formatted array.
	 *
	 * @param $show
	 *
	 * @return array
	 */
	protected function formatShowInfo($show)
	{
		;
	}

	/**
	 * Assigns API episode response values to a formatted array for insertion
	 * Returns the formatted array.
	 *
	 * @param $episode
	 *
	 * @return array
	 */
	protected function formatEpisodeInfo($episode)
	{
		;
	}
}

?>
