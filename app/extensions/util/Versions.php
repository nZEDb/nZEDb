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

use \app\models\Settings;

class Versions extends \lithium\core\Object
{
	/**
	 * @var \app\extensions\util\Git object.
	 */
	protected $git;

	/**
	 * @var \simpleXMLElement object.
	 */
	protected $xml = null;

	public function __construct(array $config = [])
	{
		$defaults = [
			'git'	=> null,
			'path'	=> nZEDb_VERSIONS,
		];

		parent::__construct($config += $defaults);
	}

	public function getGitBranch()
	{
		$this->initialiseGit();

		return $this->git->getBranch();
	}

	public function getGitHeadHash()
	{
		$this->initialiseGit();
		return $this->git->getHeadHash();
	}

	public function getGitTagFromFile()
	{
		$this->loadXMLFile();
		return ($this->xml === null) ? null : $this->_vers->git->tag->__toString();
	}

	public function getGitTagFromRepo()
	{
		$this->initialiseGit();
		return $this->git->tagLatest();
	}

	public function getSQLPatchFromDB()
	{
		$dbVersion = Settings::value('..sqlpatch', true);

		if (!is_numeric($dbVersion)) {
			throw new \Exception('Bad sqlpatch value');
		}

		return $dbVersion;
	}

	public function getSQLPatchFromFile()
	{
		$this->loadXMLFile();
		return ($this->xml === null) ? null : $this->_vers->sql->file->__toString();
	}

	protected function initialiseGit()
	{
		if (!($this->git instanceof Git)) {
			$this->git = new Git();
		}
	}

	protected function loadXMLFile()
	{
		if ($this->xml === null) {
			$versions = $this->_config['path'];

			$temp = libxml_use_internal_errors(true);
			$this->xml = simplexml_load_file($versions);
			libxml_use_internal_errors($temp);

			if ($this->xml === false) {
				$this->error("Your versions XML file ($versions) is broken, try updating from git.");
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

	protected function _init()
	{
		parent::_init();

		if ($this->_config['git'] instanceof Git) {
			$this->git =& $this->_config['git'];
		}
	}
}
