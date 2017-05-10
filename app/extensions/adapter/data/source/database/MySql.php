<?php
/**
 * li₃: the most RAD framework for PHP (http://li3.me)
 * Copyright 2016, Union of RAD. All rights reserved. This source
 * code is distributed under the terms of the BSD 3-Clause License.
 * The full license text can be found in the LICENSE.txt file.
 */

namespace app\extensions\adapter\data\source\database;


/**
 * MySQL database driver. Extends the `Database` class to implement the necessary
 * SQL-formatting and resultset-fetching features for working with MySQL databases.
 * - Implements optional strict mode.
 * For more information on configuring the database connection, see
 * the `__construct()` method.
 *
 * @see \lithium\data\source\database\adapter\MySql::__construct()
 * @see \lithium\data\source\database\adapter\MySql::strict()
 */
class MySql extends \lithium\data\source\database\adapter\MySql
{
	/**
	 * Constructor.
	 *
	 * @link http://dev.mysql.com/doc/refman/5.7/en/sql-mode.html#sql-mode-strict
	 * @see  \lithium\data\source\Database::__construct()
	 * @see  \lithium\data\Source::__construct()
	 * @see  \lithium\data\Connections::add()
	 *
	 * @param array $config The available configuration options are the following. Further
	 *                      options are inherited from the parent classes. Typically, these parameters are
	 *                      set in `Connections::add()`, when adding the adapter to the list of active
	 *                      connections.
	 *                      - `'host'` _string_: A string in the form of `'<host>'`, `'<host>:<port>'` or
	 *                      `':<port>'` indicating the host and/or port to connect to. When one or both are
	 *                      not provided uses general server defaults.
	 *                      To use Unix sockets specify the path to the socket (i.e. `'/path/to/socket'`).
	 *                      - `'strict'` _boolean|null_: When `true` will enable strict mode by setting
	 *                      sql-mode to `STRICT_ALL_TABLES`. When `false` will disable strict mode
	 *                      explictly by settings sql-mode to an empty value ``. A value of `null`
	 *                      leaves the setting untouched (this is the default) and the default setting
	 *                      of the database is used.
	 *
	 * @return \app\extensions\adapter\data\source\database\MySql
	 */
	public function __construct(array $config = [])
	{
		$defaults = [
			'host'      => static::DEFAULT_HOST . ':' . static::DEFAULT_PORT,
			'lowercase' => false,
			'strict'    => null,
			'timezone'  => null,
		];
		parent::__construct($config + $defaults);
	}

	/**
	 * Connects to the database by creating a PDO intance using the constructed DSN string.
	 * Will set specific options on the connection as provided.
	 *
	 * @return boolean Returns `true` if a database connection could be established,
	 *         otherwise `false`.
	 */
	public function connect()
	{
		if (!parent::connect()) {
			return false;
		}
		if ($this->_config['strict'] !== null && !$this->strict($this->_config['strict'])) {
			return false;
		}
		if ($this->_config['timezone'] !== null && !$this->timezone($this->_config['timezone'])) {
			return false;
		}
		if ($this->_config['lowercase'] === true) {
			return $this->connection->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_LOWER);
		}

		return true;
	}

	/**
	 * @param null $value If null returns the current timezone for the connection. Otherwise it
	 *                    attemps to set the timezone for the connection to the value supplied.
	 *
	 * @return bool|string TZ string of the connection if $value is null, otherwise a boolean
	 *					   indicating if setting the timezone succeeded or failed.
	 */
	public function timezone($value = null)
	{
		if ($value === null) {
			return $this->connection->query('SELECT @@session.time_zone')->fetchColumn();
		}

		if ($value) {
			return $this->connection->exec("SET time_zone = '$value'") !== false;
		}

		trigger_error(__CLASS__ . '::' . __METHOD__ . ': Empty value passed for timezone setting', E_USER_WARNING);
	}
}

?>
