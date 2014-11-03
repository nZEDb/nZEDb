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
 * @copyright 2014 nZEDb
 */
namespace nzedb;


use \Closure;

/**
 * Provides a base class for all static classes in the framework.
 */
class StaticObject
{

	/**
	 * Stores the closures that represent the method filters. They are indexed by called class.
	 *
	 * @var array Method filters, indexed by `get_called_class()`.
	 */
	protected static $_methodFilters = array();

	/**
	 * Keeps a cached list of each class' inheritance tree.
	 *
	 * @var array
	 */
	protected static $_parents = array();

	/**
	 * Apply a closure to a method of the current static object.
	 *
	 * @see lithium\core\StaticObject::_filter()
	 * @see lithium\util\collection\Filters
	 *
	 * @param mixed   $method The name of the method to apply the closure to. Can either be a single
	 *                        method name as a string, or an array of method names. Can also be false to remove
	 *                        all filters on the current object.
	 * @param Closure $filter The closure that is used to filter the method(s), can also be false
	 *                        to remove all the current filters for the given method.
	 *
	 * @return void
	 */
	public static function applyFilter($method, $filter = null)
	{
		$class = get_called_class();
		if ($method === false) {
			static::$_methodFilters[$class] = array();
			return;
		}
		foreach ((array)$method as $m) {
			if (!isset(static::$_methodFilters[$class][$m]) || $filter === false) {
				static::$_methodFilters[$class][$m] = array();
			}
			if ($filter !== false) {
				static::$_methodFilters[$class][$m][] = $filter;
			}
		}
	}

	/**
	 * Calls a method on this object with the given parameters. Provides an OO wrapper for
	 * `forward_static_call_array()`, and improves performance by using straight method calls
	 * in most cases.
	 *
	 * @param string $method Name of the method to call.
	 * @param array  $params Parameter list to use when calling `$method`.
	 *
	 * @return mixed Returns the result of the method call.
	 */
	public static function invokeMethod($method, $params = array())
	{
		switch (count($params)) {
			case 0:
				return static::$method();
			case 1:
				return static::$method($params[0]);
			case 2:
				return static::$method($params[0], $params[1]);
			case 3:
				return static::$method($params[0], $params[1], $params[2]);
			case 4:
				return static::$method($params[0], $params[1], $params[2], $params[3]);
			case 5:
				return static::$method($params[0], $params[1], $params[2], $params[3], $params[4]);
			default:
				return forward_static_call_array(array(get_called_class(), $method), $params);
		}
	}

	/**
	 * Gets and caches an array of the parent methods of a class.
	 *
	 * @return array Returns an array of parent classes for the current class.
	 */
	protected static function _parents()
	{
		$class = get_called_class();

		if (!isset(self::$_parents[$class])) {
			self::$_parents[$class] = class_parents($class);
		}
		return self::$_parents[$class];
	}

	/**
	 * Exit immediately. Primarily used for overrides during testing.
	 *
	 * @param integer|string $status integer range 0 to 254, string printed on exit
	 *
	 * @return void
	 */
	protected static function _stop($status = 0)
	{
		exit($status);
	}
}

?>
