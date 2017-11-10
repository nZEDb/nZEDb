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

use app\models\Settings;
use lithium\console\command\Help;
use nzedb\utility\Text;


/**
 * Verifies various parts of your indexer.
 * Actions:
 * * settings_table Checks that all settings in the 10~settings.tsv exist in your Db.
 * * permissions    Checks that path and/or db permissions for crucial locations in nZEDb for the user running the command.
 *                  If an incorrect permission is encountered, a message will be printed.
 *                  IT IS STRONGLY RECOMMENDED that you run this against your apache/nginx user, in addition to your normal CLI user.
 *                  On linux you can run it against the apache/nginx user this way: sudo -u www-data ./zed verify permissions
 *                  See this page for a quick guide on setting up your permissions in linux: https://github.com/nZEDb/nZEDb/wiki/Setting-permissions-on-linux
 */
class Verify extends \app\extensions\console\Command
{
	/**
	 * Constructor.
	 *
	 * @param array $config
	 */
	public function __construct(array $config = [])
	{
		$defaults = [
			'classes'  => $this->_classes,
			'request'  => null,
			'response' => [],
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

	public function permissions($dbPerms = false)
	{
		$this->primary("Checking file permissions...");
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

	public function settingstable()
	{
		$dummy = Settings::hasAllEntries($this);
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
