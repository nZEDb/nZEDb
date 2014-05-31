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
 * @author    niel
 * @copyright 2014 nZEDb
 */
namespace nzedb\utility\log;

use Psr\Log\InvalidArgumentException;

class Logger extends Psr\Log\AbstractLogger
{
	const LEVEL_NONE		= 0;
	const LEVEL_EMERGENCY	= 0;
	const LEVEL_ALERT		= 1;
	const LEVEL_CRITICAL	= 2;
	const LEVEL_ERROR		= 3;
	const LEVEL_WARNING		= 4;
	const LEVEL_NOTICE		= 5;
	const LEVEL_INFO		= 6;
	const LEVEL_DEBUG		= 7;

	private $level;

	private $levels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

	public function __construct(array $options = array())
	{
		$default = array(
			'logLevel' => self::LEVEL_NONE,
		);
		$options += $default;

		$this->level = $options['logLevel'];
	}
/*
		public function log($level, $message, array $context = array())
	{

	}
*/
	/**
	 * @param $level	The name of the level to convert to its code
	 *
	 * @return integer	The code for the named level.
	 * @throws \Psr\Log\InvalidArgumentException
	 */
	public function levelName2Number($level)
	{
		if (empty($level)) {
			throw new InvalidArgumentException('Level cannot be empty, must be a string name.');
		}

		$index = array_search($level, $this->levels);
		if ($index === false) {
			throw new InvalidArgumentException('Level must be one of: ' . implode(', ', $this->levels));
		}

		return $index;
	}

	/**
	 * Takes a message (object or string) and returns a string representation.
	 *
	 * @param string|object $message If an object, it MUST implement the __toString() magic method.
	 *
	 * @return string
	 * @throws \Psr\Log\InvalidArgumentException If the object does not implement the __toString
	 *                                           magic method, an exception is thrown.
	 */
	public function object2Message($message)
	{
		if (($message instanceof stdClass) === false) {	// not an object
			$result = (string)$message;					// Return the string
		} else if (method_exists($message, '__toString')) {
			$result = $message->__toString();
		} else {
			throw new InvalidArgumentException('Message object must implement __toString to be used.');
		}
		return $result;
	}

	public function sanitiseContext(array $context = array())
	{
		$defaults = array(
			'exception' => false, // Exception class, MUST be tested before use
			'logTo'		=> ['cli' => false, 'db' => false, 'file' => false], // Default to no handler
			'timestamp' => time(), // Unix timestamp.
		);
		$context += $defaults;

		$context['exception'] = $context['exception'] instanceof \Exception;

		return $context;
	}

	/**
	 * Should the supplied level be logged.
	 *
	 * @param $level	The level that we want to know if we should log for.
	 *
	 * @return bool		True if the supplied level is within the logging threshold.
	 * @throws \Psr\Log\InvalidArgumentException
	 */
	public function shouldLog($level)
	{
		if (!is_numeric($level)) {
			throw new InvalidArgumentException('Level must be a positive integer less than 8.');
		}

		return !((integer)$level > $this->level);
	}
}
