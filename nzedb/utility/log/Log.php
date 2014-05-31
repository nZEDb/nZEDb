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

class Log
{
	/**
	 * @var Array of instance variable for various logger classes (LoggerCLI, LoggerFile, etc.).
	 * 		Each MUST support the logger levels.
	 */
	public $loggers = array();

	private $context = [
		'logTo' =>
			[
				'cli'  => false,
				'db'   => false,
				'file' => false
			]
	]; // Default to no handlers;

	public function __construct(array $options = array())
	{
		$default = array(
			'logCli'   => null,
			'logDb'    => null,
			'logFile'  => null,
			'filename' => nZEDb_RES . 'logs' . DS . 'nzedb.log',
			'logLevel' => Logger::LEVEL_NONE,
			'Log2CLI'  => Utility::isCLI(), // Output to CLI
			'log2Db'   => false,
			'Log2File' => !Utility::isCLI(), // Output to file
		);
		$options += $default;

		if ($options['log2cli']) {
			if ($options['logCli'] === null) {
				$this->loggers[] = new LoggerCLI();
			} else {
				$this->loggers[] = $options['logCli'];
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
		if ($options['log2file']) {
			if ($options['logFile'] === null) {
				$this->loggers[] = new LoggerFile($options['filename']);
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
