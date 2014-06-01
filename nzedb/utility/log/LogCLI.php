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

class LogCLI extends Log
{
	private $logCLI;

	public function __construct(array $options = array())
	{
		$default = ['logTo'	=> ['cli' => true]];
		$options += $default;

		$this->logCLI = new \ColorCLI();
		parent::__construct($options);
	}

	public function log($level, $message, array $context = array())
	{
		$context += $this->_context; // Merge in defaults, allowing parameter to override.
		if ($context['logTo']['cli'] === false) {
			if (nZEDb_DEBUG) {
				echo $this->logCLI->debug("Context prevents displaying message");
			}
			return;
		}

		if ($this->shouldLog($this->levelName2Number($level))) {
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
			$this->logCLI->doEcho($this->logCLI->$level($this->object2Message($message), true));
		} else if (nZEDb_DEBUG) {
			echo $this->logCLI->debug("Log message below reporting threshold");
		}
	}
}
