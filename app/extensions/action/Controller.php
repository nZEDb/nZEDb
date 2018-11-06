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
 * not, see:.
 *
 * @link      <http://www.gnu.org/licenses/>.
 *
 * @author    niel
 * @copyright 2017 nZEDb
 */
namespace app\extensions\action;

use nzedb\data\Source;

class Controller extends \lithium\action\Controller
{
	/**
	 * Constructor.
	 *
	 * @see lithium\action\Controller::$request
	 * @see lithium\action\Controller::$response
	 * @see lithium\action\Controller::$_render
	 * @see lithium\action\Controller::$_classes
	 *
	 * @param array $config Available configuration options are:
	 *                      - `'request'` _object|null_: Either a request object or `null`.
	 *                      - `'response'` _array_: Options for constructing the response object.
	 *                      - `'render'` _array_: Rendering control options.
	 *                      - `'classes'` _array_
	 *
	 * @return void
	 */
	public function __construct(array $config = [])
	{
		$defaults = [
			'request'  => null,
			'response' => [],
			'render'   => [],
			'classes'  => [],
		];
		parent::__construct($config + $defaults);
	}
}
