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

class LogFile extends Log
{
	private $file;
	private $filespec;

	public function __construct(array $options = array())
	{
		$defaults = [
			'filename'	=> 'nzedb.log',
			'filepath'	=> nZEDb_RES . 'logs' . DS,
			'logTo' => ['file' => true],
		];
		$options += $defaults;
		parent::__construct($options);

		$mode = 'a' . (Utility::isWin() ? 'b' : '');
		$this->file = fopen($this->getFileSpec(), $mode);
		if ($this->file === false) {
			throw new \RuntimeException("Couldn't create/open log file '{$this->filespec}''");
		}
	}

	public function getFileSpec()
	{
		return Utility::trailingSlash($this->_context['filepath']) . $this->_context['filename'];
	}

	public function log($level, $message, array $context = array())
	{
		if (!isset($context['timestamp']) || empty($context['timestamp'])) {
			$context['timestamp'] = time(); // Unix timestamp.
		}
		$context += $this->_context; // Merge in defaults, allowing parameter to override.
		if ($context['logTo']['file'] === false) {
			return;
		}

		if ($this->shouldLog($this->levelName2Number($level))) {
			$text = sprintf(
				'%s [%s] %s%s',
				date('Y-m-d H:i:s', $context['timestamp']),
				$level,
				$this->object2Message($message),
				PHP_EOL
			);
			$result = fwrite($this->file, $text);
		}
	}
}
