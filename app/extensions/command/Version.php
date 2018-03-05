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
namespace app\extensions\command;

use \app\extensions\util\Versions;
use \lithium\console\command\Help;


/**
 * Returns the current version (or branch) of the indexer.
 *
 * Actions:
 *  * all		Show all of following info.
 *  * branch	Show git branch name.
 *  * git		Show git tag for current version.
 *  * sql		Show SQL patch level
 *
 * @package app\extensions\command
 */
class Version extends \app\extensions\console\Command
{
	/**
	 * @var \app\extensions\util\Versions;
	 */
	protected $versions = null;

	/**
	 * Constructor.
	 *
	 * @param array $config
	 */
	public function __construct(array $config = [])
	{
		$defaults = [
			'classes'	=> $this->_classes,
			'request'	=> null,
			'response'	=> [],
		];
		parent::__construct($config + $defaults);
	}

	public function run()
	{
		if ($this->request->args() === null) {
			return $this->_help();
		}

		return false;
	}

	protected function all()
	{
		$this->git();
		$this->sql();
	}

	protected function branch()
	{
		$this->primary('Git branch: ' . $this->versions->getGitBranch());
	}

	/**
	 * Fetch git tag for latest version.
	 *
	 * @param string|null $path Optional path to the versions XML file.
	 */
	protected function git()
	{
		if (!$this->plain) {
			$this->primary('Looking up Git tag version(s)');
		}
		$this->out('Hash: ' . $this->versions->getGitHeadHash(), 0);
		$this->out('XML version: ' . $this->versions->getGitTagInFile());
		$this->out('Git version: ' . $this->versions->getGitTagInRepo());
	}

	/**
	 * Fetch SQL latest patch version.
	 */
	protected function sql()
	{
		if (!$this->plain) {
			$this->primary('Looking up SQL patch version(s)');
		}

		if (in_array($this->request->params['args']['sqlcheck'], ['xml', 'both', 'all'])) {
			$latest = $this->versions->getSQLPatchFromFile();
			$this->out("XML version: $latest");
		}

		if (in_array($this->request->params['args']['sqlcheck'], ['db', 'both', 'all'])) {
			try {
				$dbVersion = $this->versions->getSQLPatchFromDB();
				$this->out(" DB version: " . $dbVersion);
			} catch (\Exception $e) {
				$this->error($e->getMessage());
			}
		}
	}

	/**
	 * Invokes the `Help` command.
	 * The invoked Help command will take over request and response objects of
	 * the originally invoked command. Thus the response of the Help command
	 * becomes the response of the original one.
	 *
	 * @return boolean
	 */
	protected function _help()
	{
		$help = new Help([
			'request'  => $this->request,
			'response' => $this->response,
			'classes'  => $this->_classes
		]);

		return $help->run(get_class($this));
	}

	/**
	 * Class initializer. Parses template and sets up params that need to be filled.
	 *
	 * @return void
	 */
	protected function _init()
	{
		parent::_init();

		$this->request->params['args'] += ['sqlcheck' => 'all'];    // Default to all versions/
		$this->versions = new Versions();
	}
}
