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
namespace app\extensions\adapter\data\source;


use app\models\Settings;


/**
 * The `Nntp` class provides the base-level abstraction for connecting to a Usenet Service Provider.
 *
 * @package app\extensions\adapter\data\source
 */
abstract class Nntp extends \lithium\data\Source
{
	/**
	 * Constructor.
	 *
	 * @param $config array Available configuration options are:
	 *
	 * @return Nntp object on success, exception on failure.
	 */
	public function __construct(array $config = [])
	{
		$defaults = [
		];
		parent::__construct($config + $defaults);
	}

	public function connect()
	{
		// TODO: Implement connect() method.
	}

	public function create($query, array $options = [])
	{
		// TODO: Implement create() method.
	}

	public function delete($query, array $options = [])
	{
		// TODO: add reporting that this is not supported.
	}

	public function disconnect()
	{
		// TODO: Implement disconnect() method.
	}

	public function read($query, array $options = [])
	{
		// TODO: Implement read() method.
	}

	public function update($query, array $options = [])
	{
		// TODO: add reporting that this is not supported.
	}
}

