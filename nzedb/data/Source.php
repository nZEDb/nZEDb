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
 * @link <http://www.gnu.org/licenses/>.
 * @author niel
 */
namespace nzedb\data;

/**
 * This is the base class for a data abstraction layer.
 */
abstract class Source
{
	/**
	 * Stores the status of this object's connection. Updated when `connect()` or `disconnect()` are
	 * called, or if an error occurs that closes the object's connection.
	 *
	 * @var boolean
	 */
	protected $_isConnected = false;

	/**
	 * Constructor. Sets defaults and returns object.
	 *
	 * Options defined:
	 * - 'autoConnect' `boolean` If true, a connection is made on initialization. Defaults to true.
	 *
	 * @param array $config
	 *
	 * @return Source object
	 */
	public function __construct(array $config = array())
	{
		$defaults = array('autoConnect' => true);
		$this->_config = $config + $defaults;
		$this->_init();
	}

	/**
	 * Ensures the connection is closed, before the object is destroyed.
	 *
	 * @return void
	 */
	public function __destruct()
	{
		if ($this->isConnected()) {
			$this->disconnect();
		}
	}

	protected function _init()
	{
		if ($this->_config['autoConnect']) {
			$this->connect();
		}
	}

	/**
	 * Checks the connection status of this data source. If the `'autoConnect'` option is set to
	 * true and the source connection is not currently active, a connection attempt will be made
	 * before returning the result of the connection status.
	 */
	public function isConnected(array $options = array())
	{
		$defaults = array('autoConnect' => false);
		$options += $defaults;

		if (!$this->_isConnected && $options['autoConnect']) {
			try {
				$this->connect();
			} catch (\NetworkException $e) {
				$this->_isConnected = false;
			}
		}
		return $this->_isConnected;
	}

	/**
	 * Quotes data-source-native identifiers, where applicable.
	 *
	 * @param string $name Identifier name.
	 *
	 * @return string Returns `$name`, quoted if applicable.
	 */
	public function name($name)
	{
		return $name;
	}

	/**
	 * Abstract. Must be defined by child classes.
	 */
	abstract public function connect();

	/**
	 * Abstract. Must be defined by child classes.
	 */
	abstract public function disconnect();

	/**
	 * Returns a list of objects (sources) that models can bind to, i.e. a list of tables in the
	 * case of a database, or REST collections, in the case of a web service.
	 *
	 * @param string $class The fully-name-spaced class name of the object making the request.
	 *
	 * @return array Returns an array of objects to which models can connect.
	 */
	abstract public function sources($class = null);

	/**
	 * Gets the column schema for a given entity (such as a database table).
	 *
	 * @param mixed $entity Specifies the table name for which the schema should be returned, or
	 *                      the class name of the model object requesting the schema, in which case the model
	 *                      class will be queried for the correct table name.
	 * @param array $schema
	 * @param array $meta   The meta-information for the model class, which this method may use in
	 *                      introspecting the schema.
	 *
	 * @return array Returns a `Schema` object describing the given model's schema, where the
	 *         array keys are the available fields, and the values are arrays describing each
	 *         field, containing the following keys:
	 *         - `'type'`: The field type name
	 */
	abstract public function describe($entity, $schema = array(), array $meta = array());

	/**
	 * Create a record. This is the abstract method that is implemented by specific data sources.
	 * This method should take a query object and use it to create a record in the data source.
	 *
	 * @param mixed $query   An object which defines the update operation(s) that should be performed
	 *                       against the data store.  This can be a `Query`, a `RecordSet`, a `Record`, or a
	 *                       subclass of one of the three. Alternatively, `$query` can be an adapter-specific
	 *                       query string.
	 * @param array $options The options from Model include,
	 *                       - `validate` _boolean_ default: true
	 *                       - `events` _string_ default: create
	 *                       - `whitelist` _array_ default: null
	 *                       - `callbacks` _boolean_ default: true
	 *                       - `locked` _boolean_ default: true
	 *
	 * @return boolean Returns true if the operation was a success, otherwise false.
	 */
	abstract public function create($query, array $options = array());

	/**
	 * Abstract. Must be defined by child classes.
	 *
	 * @param mixed $query
	 * @param array $options
	 *
	 * @return boolean Returns true if the operation was a success, otherwise false.
	 */
	abstract public function delete($query, array $options = array());

	/**
	 * Abstract. Must be defined by child classes.
	 *
	 * @param mixed $query
	 * @param array $options
	 *
	 * @return boolean Returns true if the operation was a success, otherwise false.
	 */
	abstract public function read($query, array $options = array());

	/**
	 * Updates a set of records in a concrete data store.
	 *
	 * @param mixed $query   An object which defines the update operation(s) that should be performed
	 *                       against the data store.  This can be a `Query`, a `RecordSet`, a `Record`, or a
	 *                       subclass of one of the three. Alternatively, `$query` can be an adapter-specific
	 *                       query string.
	 * @param array $options Options to execute, which are defined by the concrete implementation.
	 *
	 * @return boolean Returns true if the update operation was a success, otherwise false.
	 */
	abstract public function update($query, array $options = array());
}

?>
