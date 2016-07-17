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

use \app\extensions\util\Git;
use \app\extensions\util\Versions;
use \lithium\console\command\Help;
use \nzedb\db\DbUpdate;
use \Smarty;


/**
 * Update various aspects of your indexer.
 *
 * Actions:
 *  * all|nzedb Fetches current git repo, composer dependencies, and update latest Db patches.
 *  * db		Update the Db with any patches not yet applied.
 *  * git		Performs git pull.
 *  * predb		Fetch and import TSV files into the predb table.
 *
 *@package app\extensions\command
 */
class Update extends \app\extensions\console\Command
{
	const UPDATES_FILE = nZEDb_CONFIGS . 'updates.json';

	/**
	 * @var \app\extensions\util\Git object.
	 */
	protected $git;

	/**
	 * @var array Decoded JSON updates file.
	 */
	protected $updates = null;

	private $gitBranch;

	private $gitTag;

	/**
	 * Constructor.
	 *
	 * @param array $config
	 */
	public function __construct(array $config = [])
	{
		$defaults = [
			'classes'	=> $this->_classes,
			'git'		=> null,
			'request'	=> null,
			'response'	=> [],
		];
		parent::__construct($config + $defaults);
	}

	public function all()
	{
		$this->nzedb();
	}

	public function db()
	{
		// TODO Add check to determine if the indexer or other scripts are running. Hopefully
		// also prevent web access.
		$this->out("Checking database version...", 'primary');

		$versions = new Versions(['git' => ($this->git instanceof Git) ? $this->git : null]);

		try {
			$currentDb = $versions->getSQLPatchFromDB();
			$currentXML = $versions->getSQLPatchFromFile();
		} catch (\PDOException $e) {
			$this->out('Error fetching patch versions!', 'error');
			return 1;
		}
		$this->out("Db: $currentDb,\tFile: $currentXML");
		if ($currentDb < $currentXML) {
			$db = new DbUpdate(['backup' => false]);
			$db->processPatches(['safe' => false]);
		} else {
			$this->out("Up to date.", 'info');
		}
	}

	public function git()
	{
		// TODO Add check to determine if the indexer or other scripts are running. Hopefully
		// also prevent web access.
		$this->initialiseGit();
		if (!in_array($this->git->getBranch(), $this->git->getBranchesMain())) {
			$this->out("Not on the stable or dev branch! Refusing to update repository", 'error');
			return;
		}

		$this->out($this->git->pull());
	}

	public function nzedb()
	{
		try {
			$output = $this->git();
			if ($output === 'Already up-to-date.') {
				$this->out($output, 'info');
			} else {
				$status = $this->composer();
				if ($status) {
					$this->out('Composer failed to update!!', 'error');

					return false;
				} else {
					$fail = $this->db();
					if ($fail) {
						$this->out('Db updating failed!!', 'error');

						return 1;
					}
				};
			}

			$this->scripts();

			$smarty = new Smarty();
			$smarty->setCompileDir(nZEDb_SMARTY_TEMPLATES);
			$cleared = $smarty->clearCompiledTemplate();
			if ($cleared) {
				$this->out('The Smarty compiled template cache has been cleaned for you', 'primary');
			} else {
				$this->out('You should clear your Smarty compiled template cache at: ' .
					nZEDb_RES . "smarty" . DS . 'templates_c',
					'primary');
			}
		} catch (\Exception $e) {
			$this->error($e->getMessage());
		}
	}

	/**
	 * Import/Update the predb table using tab separated value files.
	 */
	public function predb()
	{
		$this->out('predb not available yet!', 'error');
	}

	public function run($command = null)
	{
		if (!$command || !$this->request->args()) {
			return $this->_help($command);
		}

		if (!$command) {
			return $this->_help($command);
		}

		if ($this->_execute($command)) {
			return true;
		}

		$this->error("{$command} could not be created.");

		return false;
	}

	/**
	 * Issues the command to 'install' the composer package.
	 *
	 * It first checks the current branch for stable versions. If found then the '--no-dev'
	 * option is added to the command to prevent development packages being also downloded.
	 *
	 * @return integer Return status from Composer.
	 */
	protected function composer()
	{
		$this->initialiseGit();
		$command = 'composer install --prefer-source';
		if (in_array($this->gitBranch, $this->git->getBranchesStable())) {
			$command .= ' --no-dev';
		}
		$this->out('Running composer install process...', 'primary');
		system($command, $status);
		return $status;
	}

	protected function initialiseGit()
	{
		if (!($this->git instanceof Git)) {
			$this->git = new Git();
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

		if ($this->_config['git'] instanceof Git) {
			$this->git =& $this->_config['git'];
		}

		if (file_exists(UPDATES_FILE)) {
			$this->updates = json_decode(file_get_contents(UPDATES_FILE), true);
		}
	}

	/**
	 * Fetches and executes scripts for customised updating tasks.
	 */
	protected function scripts()
	{
		if (![$this->updates]) {
			$this->updates = ['script' => '0000-00-00 00:00:00'];
		}
	}
}
