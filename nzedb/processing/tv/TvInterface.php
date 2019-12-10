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


interface TvInterface
{
	/**
	 * Retrieve banner image from site using its API.
	 *
	 * @return bool True if image was successfully retrieved and saved.
	 */
	//public function getBanner(): bool;

	/**
	 * Retrieve poster image for TV episode from site using its API.
	 *
	 * @param integer $videoId ID from videos table.
	 * @param integer $siteId  ID that this site uses for the programme.
	 *
	 * @return bool True if image was successfully retrieved and saved.
	 */
	public function getPoster($videoId, $siteId): bool;

	/**
	 * Process the Info returned by the search into the fields used by this class.
	 *
	 * @param string      $name
	 * @param string|null $country
	 *
	 * @return bool
	 */
	public function processInfo(string $name, string $country = null): bool;

	/**
	 * Searches for specified title, returning an array of possible matches or null if none.
	 *
	 * @param string $name	Name of the title to search for.
	 *
	 * @return array|null	An array of possible matches or null if none.
	 */
	public function searchTitle(string $name): ?array;

}
