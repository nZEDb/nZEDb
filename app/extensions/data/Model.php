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
namespace app\extensions\data;


class Model extends \lithium\data\Model
{
	public static function tableImport(array $options = [])
	{
		$source = static::connection(); // Gives the connected data source object - the adapter
		// ( e.g. `MySql`) class for type = 'database' connections.

		// Check for object of MySql type. This includes the base (lithium) and custom variants.
		if (!($source instanceof \lithium\data\source\database\adapter\MySql)) {
			return false;
		}

		$defaults = [
			'fields' => [],		// Fields to load data into. Defaults to all fields, in table order.
			'file'   => '',		// Full path spec to the file to load.
			'skip'   => 0,		// Number of lines to ignore from the file.
			'table'  => '',		// Table to load data into. Defaults to the table associated with
								// the model.
		];
		$options += $defaults;

		if (is_array($options['fields'])) {
			$fields = empty($options['fields']) ?
				array_keys(static::schema()->fields()) : $options['fields'];
			$options['fields'] = implode(',', $fields);
		}

		$options['table'] = empty($options['table']) ? static::meta('source') : $options['table'];

		return Model::loadInfile($options);
	}

	protected static function loadInfile(array $options = [])
	{
		if (isset($options['vardump'])) {
			var_dump($options['vardump']);
		} else {
			var_dump($options);
		}

	}
}
