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

use nzedb\Nzedb;
use \GitRepo;

/**
 * Class Git - Wrapper for various git operations.
 * @package nzedb\utility
 */
class Git extends GitRepo
{
	private $branch;
	private $branches = [
		'stable'      => ['0.x', 'Latest-testing', '\d+\.\d+\.\d+(\.\d+)?'],
		'development' => ['dev', 'dev-test']
	];
	private $mainBranches = ['0.x', 'dev', 'Latest-testing', 'dev-test'];
	private $tagLatest = '';

	public function __construct(array $options = [])
	{
		$defaults = [
			'create'		=> false,
			'initialise'	=> false,
			'filepath'		=> Nzedb::BASE,
		];
		$options += $defaults;

		parent::__construct($options['filepath'], $options['create'], $options['initialise']);
		$this->branch = parent::active_branch();
	}

	/**
	 * Return the number of commits made to repo
	 */
	public function commits() : int
	{
		$count = 0;
		$log = explode("\n", $this->log());
		foreach ($log as $line) {
			if (preg_match('#^commit#', $line)) {
				++$count;
			}
		}
		return $count;
	}

	/**
	 * @param string $options
	 *
	 * @return string
	 */
	public function describe($options = null) : string
	{
		return $this->run("describe $options");
	}

	public function getBranch() : string
	{
		return $this->branch;
	}

	public function getBranchesDevelop() : array
	{
		return $this->branches['development'];
	}

	/**
	 * Fetches the array of branch names that are considered to be core.
	 *
	 * @return array
	 */
	public function getBranchesMain() : array
	{
		$main = array_merge($this->getBranchesStable(), $this->getBranchesDevelop());

		return $main;
	}

	public function getBranchesStable() : array
	{
		return $this->branches['stable'];
	}

	public function getHeadHash() : string
	{
		return $this->run('rev-parse HEAD');
	}

	public function getTagLatest(): string
	{
		if (empty($this->tagLatest)) {
			$this->tagLatest = trim($this->describe('--tags --abbrev=0 HEAD'));
			if (strtolower($this->tagLatest[0]) === 'v') {
				$this->tagLatest = substr($this->tagLatest, 1);
			}
		}

		return $this->tagLatest;
	}

	/**
	 * @param string $gitObject
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function isCommited($gitObject) : bool
	{
		$cmd = "cat-file -e $gitObject";

		try {
			$result = $this->run($cmd);
		} catch (\Exception $e) {
			$message = explode("\n", $e->getMessage());
			if ($message[0] === "fatal: Not a valid object name $gitObject") {
				$result = false;
			} else {
				throw new \Exception($message);
			}
		}
		return ($result === '');
	}

	public function isStable($branch) : bool
	{
		foreach ($this->getBranchesStable() as $pattern) {
			if (!preg_match("#$pattern#", $branch)) {
				continue;
			} else {
				return true;
			}
		}

		return false;
	}

	public function log($options = null) : string
	{
		return $this->run("log $options");
	}

	public function mainBranches() : array
	{
		return $this->mainBranches;
	}

	/**
	 * @param string $options
	 *
	 * @return string
	 */
	public function tag($options = null) : string
	{
		return $this->run("tag $options");
	}
}

?>
