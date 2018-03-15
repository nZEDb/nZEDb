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
 * @author    ruhllatio
 * @copyright 2015 nZEDb
 */
namespace nzedb\utility;

/**
 * Class Time -- functions for working with time string and DTOs
 *
 * @package nzedb\utility
 */
class Time
{
	/**
	 * For a given timestamp, calculate the localized show/episode airdate
	 * via the provided local airing timezone
	 *
	 * @param string $time
	 * @param string $zone
	 *
	 * @return string
	 */
	public static function localizeAirdate($time = '', $zone = '')
	{
		$datetime = new \DateTime($time);
		$newzone = new \DateTimeZone($zone);
		$datetime->setTimezone($newzone);
		return $datetime->format('Y-m-d');
	}

	public static function shortMonthToNumber($month)
	{
		static $months = [
			'jan' => 1,
			'feb' => 2,
			'mar' => 3,
			'apr' => 4,
			'may' => 5,
			'jun' => 6,
			'jul' => 7,
			'aug' => 8,
			'sep' => 9,
			'oct' => 10,
			'nov' => 11,
			'dec' => 12
		];

		$month = strtolower($month);
		$digit = false;
		if (array_key_exists($month, $months)) {
			$digit = $months[$month];
		} else {
			echo 'Array key missing: ' . $month . PHP_EOL;
		}
		return $digit;
	}
}
