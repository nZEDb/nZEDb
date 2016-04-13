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
 * @copyright 2016 nZEDb
 */
namespace app\extensions\util;


class Git extends \lithium\core\Object
{
	/**
	 * @var \GitRepo object
	 */
	protected $repo;

	private $branch;

	public function __construct(array $config = [])
	{
		$defaults = [
			'branches'		=> ['0.x', 'dev', 'latest-testing', 'dev-test'],
			'create'		=> false,
			'initialise'	=> false,
			'filepath'		=> nZEDb_ROOT,
		];
		$config += $defaults;

		parent::__construct($config);
	}

	/**
	 * Run describe command.
	 *
	 * @param string $options
	 *
	 * @return string
	 */
	public function describe($options = null)
	{
		return $this->run("describe $options");
	}

	/**
	 * Return the currently active branch
	 *
	 * @return string
	 */
	public function getBranch()
	{
		return $this->branch;
	}

	/**
	 * Determine if the supplied object is commited to the repository or not.
	 *
	 * @param $gitObject
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function isCommited($gitObject)
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

	/**
	 * Run the log command.
	 *
	 * @param null $options
	 *
	 * @return string
	 */
	public function log($options = null)
	{
		return $this->run("log $options");
	}

	/**
	 * Fetches the array of branch names that are considered to be core.
	 *
	 * @return array
	 */
	public function mainBranches()
	{
		return $this->_config->branches;
	}

	/**
	 * Run a git command in the git repository
	 * Accepts a git command to run
	 *
	 * @access  public
	 *
	 * @param   string  $command Command to run
	 *
	 * @return  string
	 */
	public function run($command)
	{
		return $this->repo->run($command);
	}

	/**
	 * Run the tag command.
	 *
	 * @param string $options
	 *
	 * @return string
	 */
	public function tag($options = null)
	{
		return $this->run("tag $options");
	}

	/**
	 * Fetch the most recently added tag.
	 *
	 * Be aware this might cause problems if tags are added out of order?
	 *
	 * @return string
	 */
	public function tagLatest()
	{
		return $this->describe("--tags --abbrev=0 HEAD");
	}

	protected function _init()
	{
		parent::_init();

		$this->repo = new GitRepo(
			$this->config['filepath'],
			$this->config['create'],
			$this->config['initialise']
		);
		$this->branch = $this->repo->active_branch();
	}
}
