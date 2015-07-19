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
 * @author    niel
 * @copyright 2015 nZEDb
 */
namespace nzedb\utility;

class Text
{
	/**
	 * Replace all white space chars for a single space.
	 *
	 * @param string $text
	 *
	 * @return string
	 *
	 * @static
	 * @access public
	 */
	public static function collapseWhiteSpace($text)
	{
		// Strip leading/trailing white space.
		return trim(
		// Replace 2 or more white space for a single space.
			preg_replace('/\s{2,}/',
						 ' ',
						// Replace new lines and carriage returns. DO NOT try removing '\r' or '\n' as they are valid in queries which uses this method.
						str_replace(["\n", "\r"], ' ', $text)
			)
		);
	}

	/**
	 * Removes the preceeding or proceeding portion of a string
	 * relative to the last occurrence of the specified character.
	 * The character selected may be retained or discarded.
	 *
	 * @param string $character      the character to search for.
	 * @param string $string         the string to search through.
	 * @param string $side           determines whether text to the left or the right of the character is returned.
	 *                               Options are: left, or right.
	 * @param bool   $keepCharacter  determines whether or not to keep the character.
	 *                               Options are: true, or false.
	 *
	 * @return string
	 */
	public static function cutStringUsingLast($character, $string, $side, $keepCharacter = true)
	{
		$offset      = ($keepCharacter ? 1 : 0);
		$wholeLength = strlen($string);
		$rightLength = (strlen(strrchr($string, $character)) - 1);
		$leftLength  = ($wholeLength - $rightLength - 1);
		switch ($side) {
			case 'left':
				$piece = substr($string, 0, ($leftLength + $offset));
				break;
			case 'right':
				$start = (0 - ($rightLength + $offset));
				$piece = substr($string, $start);
				break;
			default:
				$piece = false;
				break;
		}

		return ($piece);
	}

}
