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
	protected static $pathBin;

	protected static $pathSource;

	protected static $pathTarget;

	/**
	 * If on unix, hide yydecode CLI output.
	 *
	 * @var string
	 * @access protected
	 */
	protected static $silent;

	public static function decode($text, $ignore = false, array $options = [])
	{
		$source = tempnam(nZEDb_TMP . 'yEnc', 'yenc-source-');
		$target = tempnam(nZEDb_TMP . 'yEnc', 'yenc-target-');

		preg_match('/^(=yBegin.*=yEnd[^$]*)$/ims', $text, $input);
		file_put_contents($source, $input[1]);
		file_put_contents($target, '');
		Misc::runCmd(
			"'" . self::$pathBin . "' '" .	$source . "' -o '" . $target . "' -f -b" . self::$silent
		);
		$data = file_get_contents($target);
		unlink($source);
		unlink($target);
		if ($data === false && $ignore === false) {
			throw new \Exception('Error getting data from yydecode.');
		}

		return $data;
	}

	public static function decodeIgnore($text, array $options = [])
	{
		self::decode($text, true, $options);
	}

	/**
	 * Determines if this adapter is enabled by checking if the `yydecode` path is enabled.
	 *
	 * @return boolean Returns `true` if enabled, otherwise `false`.
	 */
	public static function enabled()
	{
		return !empty(self::$pathBin);
	}

	public static function encode($data, $filename, $lineLength, $crc32)
	{
		throw new \Exception('Method not implemented!');
		return null;
	}

	protected function _init()
	{
		parent::_init();

		$path = Settings::value('..yydecoderpath', true);
		if (!empty($path) && strpos($path, 'simple_php_yenc_decode') === false) {
			if (file_exists($path) && is_executable($path)) {
				self::$silent = (Misc::isWin() ? '' : ' > /dev/null 2>&1');
				self::$pathBin = $path;
			} else {
				self::$pathBin = false;
			}
		} else {
			self::$pathBin = false;
		}
	}
}

?>
