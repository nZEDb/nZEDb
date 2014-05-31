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


use Psr\Log\InvalidArgumentException;

class LoggerCLI extends Logger
{
	private $logCli;

	public function __construct(array $options = array())
	{
		$default = array(
			'cli'   => null,
		);
		$options += $default;

		if ($options['cli'] === null) {
			$this->logCli = new LoggerCLI();
		} else if (is_a($options['cli'], 'ColorCLI')) {
			$this->logCli = $options['cli'];
		} else {
			throw new InvalidArgumentException('Option "cli" must be an object of type ColorCLI.');
		}
	}

	public function log($level, $message, array $context = array())
	{
		$defaults = array(
			'logTo'		=> ['cli' => true],
		);
		$context += $defaults;
		$context = $this->sanitiseContext($context);

		$intLevel = $this->levelName2Number($level);
		if ($this->shouldLog($intLevel)) {
			switch ($level) {
				case 'debug':
				case 'error':
				case 'info':
				case 'notice':
				case 'warning':
					break;
				case 'alert':
				case 'critical':
				case 'emergency':
				default:
					$level = 'error';
			}
			$this->logCli->doEcho($this->logCli->$level($this->object2Message($message), true));
		}
	}
}
