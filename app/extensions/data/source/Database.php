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
namespace app\extensions\data\source;

/**
 * Class Database - extend the base Database to add:
 * - abstract methods for importing/exporting tables.
 * - support specifying the timezone for the connection.
 *
 * @package app\extensions\data\source
 */
abstract class Database extends \lithium\data\source\Database
{
	//abstract public function export(array $options = []);

	//abstract public function import(array $options = []);

	/**
	 * Constructor.
	 *
	 * @param $config array Available configuration options are:
	 *                - `'database'` _string_: Name of the database to use. Defaults to `null`.
	 *                - `'host'` _string_: Name/address of server to connect to. Defaults to `'localhost'`.
	 *                - `'login'` _string_: Username to use when connecting to server.
	 *                Defaults to `'root'`.
	 *                - `'password'` _string_: Password to use when connecting to server. Defaults to `''`.
	 *                - `'persistent'` _boolean_: If true a persistent connection will be attempted,
	 *                provided the  adapter supports it. Defaults to `true`.
	 *                - `'options'` _array_: An array with additional PDO options. Maps
	 *                (driver specific) PDO attribute constants to values.
	 *
	 * @return void
	 */
	public function __construct(array $config = [])
	{
		$defaults = [
			'persistent' => true,
			'host'       => 'localhost',
			'login'      => 'root',
			'password'   => '',
			'database'   => null,
			'encoding'   => null,
			'dsn'        => null,
			'timezone'   => ini_get('date.timezone'),
			'options'    => []
		];
		parent::__construct($config + $defaults);
	}

	/**
	 * Connects to the database by creating a PDO intance using the constructed DSN string.
	 * Will set general options on the connection as provided (persistence, encoding, timezone).
	 *
	 * @see \lithium\data\source\Database::encoding()
	 * @return boolean Returns `true` if a database connection could be established,
	 *         otherwise `false`.
	 */
	public function connect()
	{
		$this->_isConnected = false;
		$config = $this->_config;

		if (!$config['dsn']) {
			throw new ConfigException('No DSN setup for database connection.');
		}
		$dsn = $config['dsn'];

		$options = $config['options'] + [
				PDO::ATTR_PERSISTENT => $config['persistent'],
				PDO::ATTR_ERRMODE    => PDO::ERRMODE_EXCEPTION
			];

		try {
			$this->connection = new PDO($dsn, $config['login'], $config['password'], $options);
		} catch (PDOException $e) {
			preg_match('/SQLSTATE\[(.+?)\]/', $e->getMessage(), $code);
			var_dump($e->getMessage());
			$code = empty($code[1]) ? 0 : $code[0];
			switch (true) {
				case $code === 'HY000' || substr($code, 0, 2) === '08':
					$msg = "Unable to connect to host `{$config['host']}`.";
					throw new NetworkException($msg, null, $e);
					break;
				case in_array($code, ['28000', '42000']):
					$msg = "Host connected, but could not access database `{$config['database']}`.";
					throw new ConfigException($msg, null, $e);
					break;
			}
			throw new ConfigException('An unknown configuration error has occured.', null, $e);
		}
		$this->_isConnected = true;

		if ($this->_config['encoding'] && !$this->encoding($this->_config['encoding'])) {
			return false;
		}

		if ($this->_config['timezone'] && !$this->timezone($this->_config['timezone'])) {
			return false;
		}

		return $this->_isConnected;
	}

	/**
	 * Getter/Setter for the connection's timezone.
	 * Abstract. Must be defined by child class.
	 *
	 * @param null|string $timezone Either `null` to retrieve the current encoding, or a string
	 *                              to set the current timezone to.
	 *
	 * @return string|boolean       When $timezone is `null` returns the current timezone in
	 *								effect, otherwise a boolean indicating if setting the timezone
	 *								succeeded or failed.
	 */
	abstract public function timezone($timezone = null);

}
