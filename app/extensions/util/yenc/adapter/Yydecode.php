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

namespace app\extensions\util\yenc\adapter;


class Yydecode extends \lithium\core\Object
{
	/**
	 * Path to yyDecoder binary.
	 *
	 * @var bool|string
	 * @access protected
	 */
	protected $yyDecoderPath;

	/**
	 * If on unix, hide yydecode CLI output.
	 *
	 * @var string
	 * @access protected
	 */
	protected $yEncSilence;

	/**
	 * Path to temp yEnc input storage file.
	 *
	 * @var string
	 * @access protected
	 */
	protected $yEncTempInput;

	/**
	 * Path to temp yEnc output storage file.
	 *
	 * @var string
	 * @access protected
	 */
	protected $yEncTempOutput;

	public static function decode($string, $ignore = false, array $options = [])
	{
		throw new \Exception('Method not defined yet!');

		return null;
	}

	/**
	 * Determines if this adapter is enabled by checking if the `nzedb_yenc` extension is loaded.
	 *
	 * @return boolean Returns `true` if enabled, otherwise `false`.
	 */
	public static function enabled()
	{
		self::$adapter = 'yydecode';
		self::$yyDecoderPath = Settings::getSetting('yydecoderpath');
		self::$yEncSilence = (Misc::isWin() ? '' : ' > /dev/null 2>&1');
		self::$yEncTempOutput = nZEDb_TMP . 'yEnc' . DS . 'output';
		self::$yEncTempInput = nZEDb_TMP . 'yEnc' . DS . 'input';
		self::$yyDecoderPath = true;

		// Test if the user can read/write to the yEnc path.
		if (!is_file(self::$yEncTempInput)) {
			@file_put_contents(self::$yEncTempInput, 'x');
		}
		if (!is_file(self::$yEncTempInput) ||
			!is_readable(self::$yEncTempInput) ||
			!is_writable(self::$yEncTempInput)
		) {
			self::$yyDecoderPath = false;
		}
		if (is_file(self::$yEncTempInput)) {
			@unlink(self::$yEncTempInput);
		}

		return self::$yyDecoderPath;
	}

	public static function encode($data, $filename, $lineLength, $crc32)
	{
		throw new \Exception('Method not defined yet!');
		return null;
	}
}

?>
