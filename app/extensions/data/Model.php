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
			throw new \RuntimeException('Table Imports can only be applied to MySql adapters!');
		}

		$defaults = [
			'fields'			=> [],		// Fields to load data into. Defaults to all fields, in table order.
			'filepath'			=> '',		// Full path spec to the file to load.
			'skip'				=> 0,		// Number of lines to ignore from the file.
			'table'				=> '',		// Table to load data into. Defaults to the table associated with
										// the model.
			'terminatefieldby'	=> '"\t"',
			'terminatelineby'	=> '"\r\n"',
		];
		$options += $defaults;
		if (empty($options['filepath']) || !is_readable($options['filepath'])) {
			throw new \RuntimeException('Table Imports require a readable file!');
		}

//		$options['table'] = empty($options['table']) ? static::meta('source') : $options['table'];
		$options['table'] = static::meta('source');	// Force use of model's table.

		if (is_array($options['fields'])) {
			$fields = empty($options['fields']) ?
				array_keys(static::schema()->fields()) : $options['fields'];
			$options['fields'] = implode(',', $fields);
		}

		if ($options['skip'] > 0) {
			$options['ignorelines'] = "IGNORE {$options['skip']} LINES";
		}
		unset($options['skip']);

		//$options['vardump'] = $source->isConnectionLocal();

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
