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
 * @copyright 2016 nZEDb
 */
namespace nzedb\http;

use nzedb\Utility\Misc;
use nzedb\Utility\Text;
use nzedb\Capabilities;

/**
 * Class Output -- abstract class for printing web requests outside of Smarty
 *
 * @package nzedb\http
 */
abstract class Output extends Capabilities
{
	public function __construct(array $options = [])
	{
		parent::__construct($options);
	}

	/**
	 * Print XML or JSON output.
	 *
	 * @param array  $data   Data to print.
	 * @param array  $params Additional request parameters
	 * @param bool   $xml    True: Print as XML False: Print as JSON.
	 * @param string $type   What type of API query to format if XML
	 */
	public function output($data, $params, $xml = true, $type = '')
	{
		if ($xml) {
			$response =
				(
				new XML_Response(
					[
						'Parameters' => $params,
						'Releases' => $data,
						'Server' => $this->getForMenu(),
						'Type' => $type
					]
				)
				)->returnXML();
			header('Content-type: text/xml');
		} else {
			$response = json_encode(Text::encodeAsUTF8($data));
			if ($response === false) {
				Misc::showApiError(201);
			}
			header('Content-type: application/json');
		}
		header('Content-Length: ' . strlen($response));
		echo $response;
	}
}