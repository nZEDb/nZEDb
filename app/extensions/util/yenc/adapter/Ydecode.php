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

use app\models\Settings;
use nzedb\utility\Misc;

class Ydecode extends \lithium\core\Object
{
	/**
	 * Path to yyDecoder binary.
	 *
	 * @var bool|string
	 * @access protected
	 */
	protected static $pathBin;

	/**
	 * If on unix, hide yydecode CLI output.
	 *
	 * @var string
	 * @access protected
	 */
	protected static $silent;

	public static function decode(&$text, $ignore = false)
	{
		if (!preg_match('/^(=yBegin.*=yEnd[^$]*)$/ims', $text, $input)) {
			throw new \Exception('Text does not look like yEnc.');
		}
		$data = shell_exec(
			"echo '{$input[1]}' | '" . self::$pathBin . "' -o - " . ($ignore ? "-b " : " ") . self::$silent
		);
		if ($data === null) {
			throw new \Exception('Error getting data from yydecode.');
		}

		return $data;
	}

	public static function decodeIgnore(&$text)
	{
		self::decode($text, true);
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
		return Yenc::encode($data, $filename, $lineLength, $crc32, ['name' => 'Php']);
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
