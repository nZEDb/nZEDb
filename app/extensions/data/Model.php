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


use lithium\data\Source;

class Model extends \lithium\data\Model
{
	public static function import(array $data, array $options = [])
	{
		$source = static::connection();

		$local = $source->isConnectionLocal() ? '' : 'LOCAL';

		$defaults = [
			'fields'           => self::getInfileFields(),
			'filepath'         => '',
			'ignorelines'      => 1,
			'local'            => $local,
			'table'            => static::meta('source'),
			'terminatefieldby' => '"\t"',
			'terminatelineby'  => '"\r\n"',
		];
		$data += $defaults;

		if (empty($options['filepath']) || !is_readable($options['filepath'])) {
			throw new \RuntimeException('Table Imports require a readable file!');
		}

		try {
			return $source->import($data, $options);
		} catch (\Exception $e) {
			throw new \RuntimeException('Table Imports can only be applied to MySql adapters!');
		}
	}

	protected static function getInfileFields($filename)
	{
		$handle = @fopen($filename, "r");
		if (is_resource($handle)) {
			$line = fgets($handle);
			fclose($handle);
			if ($line === false) {
				echo "FAILED reading first line of '$filename'\n";
				return false;
			}
			return trim($line);

		} else {
			throw new \RuntimeException("Failed to open file: '$filename'");
		}
	}
}
