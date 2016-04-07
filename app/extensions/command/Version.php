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
use \lithium\console\command\Help;
use \nzedb\utility\Git;


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
	 * @var \nzedb\utility\Git instance variable.
	 */
	//protected $git;

	/**
	 * @var array of stable branches.
	 */
	//protected $stable = ['0.x'];

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

	/**
	 * Fetch git tag for latest version.
	 *
	 * @param null $path	Optional path to the versions XML file.
	 */
	protected function git($versions = null)
	{
		$git = new Git();
		$latest = $git->tagLatest();

		$this->out("nZEDb version: $latest");
	}

	/**
	 * Fetch SQL latest patch version.
	 *
	 * @param null $path Optional path to the versions XML file.
	 */
	protected function sql($versions = null)
	{
		$this->_loadVersionsFile($versions);
		$latest = $this->getSQLPatchFromFile();
		$this->out("SQL version: $latest");
	}

	protected function getSQLPatchFromFile()
	{
		return ($this->xml === null) ? null : $this->_vers->sql->file->__toString();
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
					$this->error("Your versions XML file ({nZEDb_VERSIONS}) does not contain version info, try updating from git.");
					throw new \Exception("Failed to find versions node in XML file '$versions'");
				} else {
					$this->primary("Your versions XML file ({nZEDb_VERSIONS}) looks okay, continuing.");
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
