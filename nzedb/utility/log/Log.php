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
	 * @var Instance variable for CLI output. It MUST support the logger levels.
	 */
	public $logCli = null;
	public $logDb = null;
	public $logFile = null;

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
				$this->logCli = new LoggerCLI();
			} else {
				$this->logCli = $options['logCli'];
			}
		}

		if ($options['log2db']) {
			if ($options['logDb'] === null) {
				//$this->logDb = new LoggerDb();
			} else {
				//$this->logDb = $options['logDb'];
			}
		}

		if ($options['log2file']) {
			if ($options['logFile'] === null) {
				//$this->logFile = new LoggerFile($options['filename']);
			} else {
				//$this->logFile = $options['logFile'];
			}
		}
	}

	public function log($level, $message, array $context = array())
	{
		if (!in_array(strtolower($level),
					  [
						  'alert', 'critical', 'debug', 'emergency', 'error', 'info', 'notice',
						  'warning'
					  ])
		) {
			throw new InvalidArgumentException();
		}

		if ($this->logCli) {
			$this->logCli->log($level, $message, $context);
		}

		if ($this->logDb) {
			$this->logDb->log($level, $message, $context);
		}

		if ($this->logFile) {
			$this->logFile->log($level, $message, $context);
		}
	}
}
