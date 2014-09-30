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
namespace nzedb\utility;

require_once nZEDb_LIBS . 'Git.php' . DS . 'Git.php';

/**
 * Class Git - Wrapper for various git operations.
 * @package nzedb\utility
 */
class Git extends \GitRepo
{
	private $branch;
	private $mainBranches = ['dev', 'next-master', 'master', 'dev-test'];

	public function __construct(array $options = array())
	{
		$defaults = array(
			'create'		=> false,
			'initialise'	=> false,
			'filepath'		=> nZEDb_ROOT,
		);
		$options += $defaults;

		parent::__construct($options['filepath'], $options['create'], $options['initialise']);
		$this->branch = parent::active_branch();
	}

	/**
	 * Return the number of commits made to repo
	 */
	public function commits()
	{
		$count = 0;
		$log = explode("\n", $this->log());
		foreach($log as $line) {
			if (preg_match('#^commit#', $line)) {
				++$count;
			}
		}
		return $count;
	}

	public function describe($options = null)
	{
		return $this->run("describe $options");
	}

	public function getBranch()
	{
		return $this->branch;
	}

	public function log($options = null)
	{
		return $this->run("log $options");
	}

	public function mainBranches()
	{
		return $this->mainBranches;
	}

	public function tag($options = null)
	{
		return $this->run("tag $options");
	}

	public function tagLatest()
	{
		return $this->describe("--tags --abbrev=0 HEAD");
	}
}

?>
