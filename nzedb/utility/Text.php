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
	 * Convert Code page 437 chars to UTF.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	public static function cp437toUTF($string)
	{
		return iconv('CP437', 'UTF-8//IGNORE//TRANSLIT', $string);
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

	public static function stripBOM(&$text)
	{
		$bom = pack("CCC", 0xef, 0xbb, 0xbf);
		if (0 == strncmp($text, $bom, 3)) {
			$text = substr($text, 3);
		}
	}

	/**
	 * Strips non-printing characters from a string.
	 *
	 * Operates directly on the text string, but also returns the result for situations requiring a
	 * return value (use in ternary, etc.)/
	 *
	 * @param $text        String variable to strip.
	 *
	 * @return string    The stripped variable.
	 */
	public static function stripNonPrintingChars(&$text)
	{
		$lowChars = [
			"\x00", "\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07",
			"\x08", "\x09", "\x0A", "\x0B", "\x0C", "\x0D", "\x0E", "\x0F",
			"\x10", "\x11", "\x12", "\x13", "\x14", "\x15", "\x16", "\x17",
			"\x18", "\x19", "\x1A", "\x1B", "\x1C", "\x1D", "\x1E", "\x1F",
		];
		$text     = str_replace($lowChars, '', $text);

		return $text;
	}

	public static function trailingSlash($path)
	{
		if (substr($path, strlen($path) - 1) != '/') {
			$path .= '/';
		}

		return $path;
	}

	/**
	 * @note: Convert non-UTF-8 characters into UTF-8
	 * Function taken from http://stackoverflow.com/a/19366999
	 *
	 * @param $data
	 *
	 * @return array|string
	 */
	public static function encodeAsUTF8($data)
	{
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				$data[$key] = Text::encodeAsUTF8($value);
			}
		} else {
			if (is_string($data)) {
				return utf8_encode($data);
			}
		}

		return $data;
	}

}
