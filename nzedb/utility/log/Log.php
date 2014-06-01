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
namespace nzedb\utility\log;

use nzedb\utility\Utility;


class Log extends \Psr\Log\AbstractLogger
{
	/**
	 * @var Array of instance variable for various logger classes (LoggerCLI, LoggerFile, etc.).
	 * 		Each MUST support the logger levels.
	 */
	public $loggers = array();

	private $context = [
		'logTo'		=> [
						'cli'  => false,
						'db'   => false,
						'file' => false
						],	// Default to no handlers;
		'exception'	=> null,
	];

	public function __construct(array $options = array())
	{
		$default = array(
			'logCLI'	=> null,
			'logDb'		=> null,
			'logFile'	=> null,
			'filename'	=> null,
			'filepath'	=> null,
			'logLevel'	=> Logger::LEVEL_NONE,
			'log2CLI'	=> Utility::isCLI(), // Output to CLI
			'log2Db'	=> false,
			'log2File'	=> !Utility::isCLI(), // Output to file
			'logTo'		=> [],
		);
		$options += $default;

		if ($options['log2CLI']) {
			if ($options['logCLI'] === null) {
				$this->loggers[] = new LoggerCLI(['logLevel' => $options['logLevel']]);
			} else {
				$this->loggers[] = $options['logCLI'];
			}
			$this->context['logTo']['cli'] = true;
		}

/* TODO implement a database handler
		if ($options['log2db']) {
			if ($options['logDb'] === null) {
				$this->loggers[] = new LoggerDb();
			} else {
				$this->loggers[] = $options['logDb'];
			}
			$this->context['logTo']['db'] = true;
		}
*/

		if ($options['log2File']) {
			if ($options['logFile'] === null) {
				$context = [];
				if (!empty($options['filename'])) {
					$context['filename'] = $options['filename'];
				}
				if (!empty($options['filepath'])) {
					$context['filepath'] = $options['filepath'];
				}
				$context['logLevel'] = $options['logLevel'];
				$this->loggers[] = new LoggerFile($context);
			} else {
				$this->loggers[] = $options['logFile'];
			}
			$this->context['logTo']['file'] = true;
		}
	}


	public function log($level, $message, array $context = array())
	{
		$context += $this->context;
		foreach ($this->loggers as $logger) {
			$logger->log($level, $message, $context);
		}
	}
}
