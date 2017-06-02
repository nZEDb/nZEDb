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
			'classes'  => []
		];
		parent::__construct($config + $defaults);
	}

	public function import(array $options, Source $source)
	{
		$defaults = [
			'fields'           => [],		// Fields to load data into. Defaults to all fields, in table order.
			'filepath'         => '',		// Full path spec to the file to load.
			'local'            => null,
			'skip'             => 0,		// Number of lines to ignore from the file.
			'table'            => '',		// Table to load data into. Defaults to the table associated with
											// the model.
			'terminatefieldby' => '"\t"',
			'terminatelineby'  => '"\r\n"',
			'truncate'         => false,	// Should the table be truncated before import.
		];
		$options += $defaults;

		if (is_array($options['fields'])) {
			$fields = empty($options['fields']) ? array_keys(static::schema()->fields()) :
				$options['fields'];
			$options['fields'] = implode(',', $fields);
		} else if ($options['fields'] == 'from file' || $options['fields'] == '') {
			if (nZEDb_DEBUG) {
				echo "Looking in file for fields\n";
			}

			$fields = $source->getInfileFields($options['filepath']);
			if ($fields === false) {
				throw new \RuntimeException(
					"Unable to get field list from import file '{$options['filepath']}'"
				);
			}
			$options['fields'] = $fields;
		}

		$options['ignorelines'] = ($options['skip'] > 0) ? "IGNORE {$options['skip']} LINES" : '';

		//$options['vardump'] = $source->isConnectionLocal();
		$data = array_intersect_key(
			$options,
			[
				'fields'           => '',
				'filepath'         => '',
				'ignorelines'      => '',
				'local'            => '',
				'table'            => '',
				'terminatefieldby' => '',
				'terminatelineby'  => '',
			]
		);

		MenuItems::import($data, $options);
	}
}
