<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program (see LICENSE.txt in the base directory.  If
 * not, see:
 *
 * @link      <http://www.gnu.org/licenses/>.
 * @author    niel
 * @copyright 2018 nZEDb
 */

namespace app\extensions\command\verify;

/**
 * Verifies permissions of various parts of the indexer.
 * Actions:
 * * db		TODO
 * * dirs	Directories
 */
class Permissions extends \app\extensions\console\Command
{
	public function __construct(array $config = [])
	{
		parent::__construct($config);
	}

	public function run()
	{
		if (empty($this->request->params['args'])) {
			return $this->_help();
		}

		foreach ($this->request->params['args'] as $arg) {
			switch ($arg) {
				case 'db':
					$this->database();
					break;
				case 'dirs':
					$this->dirs();
					break;
				default:
					$this->out("Unknown action: '$arg'", ['style' =>'error']);
			}
		}
	}

	protected function database()
	{
		$this->primary("Db permission checking, is not implemented yet!");
	}

	protected function dirs()
	{
		$this->primary("Checking permissions on directories...");
		$error = false;

		// Check All folders up to nZEDb root folder.
		$path = DS;
		foreach (explode(DS, nZEDb_ROOT) as $folder) {
			if ($folder) {
				$path .= $folder . DS;
				$error |= !$this->isReadable($path);
				$error |= !$this->isExecutable($path);
			}
		}

		if ($error) {
			$this->primary("Fix the above errors and rerun the command.");
		} else {
			$this->primary("Congratulations, file permissions look good!");
		}

	}

	protected function _init()
	{
		parent::_init();
	}

	protected function isExecutable($path)
	{
		$check = is_executable($path);
		if (!$check) {
			$this->error("This path is not executable/traversable: '$path'.");
		}

		return $check;
	}

	protected function isReadable($path)
	{
		$check = is_readable($path);
		if (!$check) {
			$this->error("This path is not readable: '$path'.");
		}

		return $check;
	}
}
