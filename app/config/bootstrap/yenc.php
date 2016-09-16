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

use app\models\Settings;


if (defined('nZEDb_INSTALLER') && nZEDb_INSTALLER !== false) {
	$adapter = 'Php';
} else {
	switch (true) {
		case extension_loaded('yenc'):
			if (method_exists('yEnc', 'version') && version_compare(yEnc::version() >= '1.1.0') ) {
				$adapter = 'NzedbYenc';
				break;
			} else {
				trigger_error('Your version of the php-yenc extension is out of date and will be
				ignored. Please update it to use the extension.', E_USER_WARNING);
			}
		case extension_loaded('simple_php_yenc_decode'):
			$adapter = 'SimplePhpYencDecode';
			break;
		case !empty(Settings::value('..yydecoderpath', true)) &&
			(strpos(Settings::value('..yydecoderpath', true), 'simple_php_yenc_decode') === false):
			$adapter = 'Ydecode';
			break;
		default:
			$adapter = 'Php';
	}
}

app\extensions\util\Yenc::config(
	[
		'default' => [
			'adapter' => $adapter
		],

		'nzedb' => [
			'adapter' => 'NzedbYenc'
		],

		'php' => [
			'adapter' => 'Php'
		],

		'simple' => [
			'adapter' => 'SimplePhpYencDecode'
		],

		'ydecode' => [
			'adapter' => 'Ydecode'
		],
	]
);

?>
