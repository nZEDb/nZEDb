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

use \Exception;
use \app\models\Settings;
use \app\extensions\util\Git;
use \lithium\console\command\Help;


/**
 * Returns the current version (or branch) of the indexer.
 *
 * Actions:
 *  * all	Show all of following info.
 *  * git	Show git tag for current version.
 *  * sql	Show SQL patch level
 *
 * @package app\extensions\command
 */
class Version extends \app\extensions\console\Command
{
	/**
	 * @var object simpleXMLElement
	 */
	protected $xml = null;

	/**
	 * Constructor.
	 *
	 * @param array $config
	 */
	public function __construct(array $config = [])
	{
		$defaults = [
			'request'  => null,
			'response' => [],
			'classes'  => $this->_classes
		];
		parent::__construct($config + $defaults);
	}

	public function run()
	{
		if (!$this->request->args()) {
			return $this->_help();
		}

		return false;
	}

	protected function all()
	{
		$this->git();
		$this->sql();
	}

	protected function getGitTagFromFile()
	{
		$this->_loadVersionsFile();
		return ($this->xml === null) ? null : $this->_vers->git->tag->__toString();
	}

	protected function getGitTagFromRepo()
	{
		$git = new Git();
		return $git->tagLatest();
	}

	protected function getSQLPatchFromDB()
	{
		return Settings::find('setting', ['conditions' => '..sqlpatch']);

	}

	protected function getSQLPatchFromFile()
	{
		$this->_loadVersionsFile();
		return ($this->xml === null) ? null : $this->_vers->sql->file->__toString();
	}

	/**
	 * Fetch git tag for latest version.
	 *
	 * @param null $path Optional path to the versions XML file.
	 */
	protected function git()
	{
		$current = $this->getGitTagFromFile();
		$latest = $this->getGitTagFromRepo();

		$this->primary('Looking up Git tag version(s)');
		$this->out("XML version: $current");
		$this->out("Git version: $latest");
	}

	/**
	 * Fetch SQL latest patch version.
	 *
	 * @param null $path Optional path to the versions XML file.
	 */
	protected function sql()
	{
		$this->request->params['args'] += ['sqlcheck' => 'all'];
		$this->primary('Looking up SQL patch version(s)');

		if (in_array($this->request->params['args']['sqlcheck'], ['xml', 'both', 'all'])) {
			$latest = $this->getSQLPatchFromFile();
			$this->out("XML version: $latest");
		}

		if (in_array($this->request->params['args']['sqlcheck'], ['db', 'both', 'all'])) {
			$dbpatch = self::getSQLPatchFromDB();

			if ($dbpatch->count()) {
				$dbVersion = $dbpatch->data()[0]['value'];
				if (!is_numeric($dbVersion)) {
					$this->error("Bad sqlpatch value: '$dbVersion'\n");
				} else {
					$this->out(" DB version: " . $dbVersion);
				}
			} else {
				$this->error("Unable to fetch Databse SQL level ");
			}
		}
	}

	protected function _loadVersionsFile($versions = null)
	{
		if ($this->xml === null) {
			if ($versions == '') {
				$versions = nZEDb_VERSIONS;
			}

			$temp = libxml_use_internal_errors(true);
			$this->xml = simplexml_load_file($versions);
			libxml_use_internal_errors($temp);

			if ($this->xml === false) {
				if (Misc::isCLI()) {
					$this->error("Your versions XML file ($versions) is broken, try updating from git.");
				}
				throw new \Exception("Failed to open versions XML file '$versions'");
			}

			if ($this->xml->count() > 0) {
				$vers = $this->xml->xpath('/nzedb/versions');

				if ($vers[0]->count() == 0) {
					$this->error("Your versions XML file ($versions) does not contain version info, try updating from git.");
					throw new \Exception("Failed to find versions node in XML file '$versions'");
				} else {
					//$this->primary("Your versions XML file ($versions) looks okay, continuing.");
					$this->_vers = &$this->xml->versions;
				}
			} else {
				throw new \RuntimeException("No elements in file!\n");
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
	}
}
