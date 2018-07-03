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
use lithium\aop\Filters;
use lithium\action\Dispatcher;


if (defined('nZEDb_INSTALLER') && nZEDb_INSTALLER !== false) {
		$adapter = 'Php';
} else {
		switch (true) {
		case extension_loaded('yenc'):
			if (method_exists('\yenc\yEnc', 'version') &&
				version_compare(
					\yenc\yEnc::version(),
					'1.2.2',
					'>='
				)
			) {
				$adapter = 'NzedbYenc';
				break;
			} else {
				trigger_error('Your version of the php-yenc extension is out of date and will be
				ignored. Please update it to use the extension.', E_USER_WARNING);
			}
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
	]
);

Filters::apply(Ypart::class,
	'parseBlock',
	function ($params, $next) {
		return $next($params);
	});



?>
