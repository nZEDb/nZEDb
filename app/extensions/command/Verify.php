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

use app\extensions\command\verify\Permissions;
use app\extensions\command\verify\Tables;
use lithium\core\ClassNotFoundException;


/**
 * Verifies various parts of your indexer.
 * Actions:
 * * permissions [ db | dirs ]
 *                  Checks that path and/or db permissions for crucial locations in nZEDb for the
 *                  user running the command.
 *                  If an incorrect permission is encountered, a message will be printed.
 *                  IT IS STRONGLY RECOMMENDED that you run this against your apache/nginx user, in
 *                  addition to your normal CLI user.
 *                  On linux you can run it against the apache/nginx user this way:
 *                      sudo -u www-data ./zed verify permissions
 *                  See this page for a quick guide on setting up your permissions in linux:
 *                      https://github.com/nZEDb/nZEDb/wiki/Setting-permissions-on-linux
 * * table <list>   Run checks against specific table(sets). <list> is a series of table names (case
 * 					sensitive) or sets.
 *                  - tpg: Collections, Parts, Binaries set of tables.
 *                  - Settings: Check that settings in the 10~settings.tsv file exist in your Db.
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
		if ($this->request->args() === null) {
			return $this->_help();
		}

		return false;
	}

	/**
	 * Run the Permissions sub-command.
	 *
	 * @return boolean
	 */
	public function permissions(): bool
	{
		$request = $this->request;
		$perms = new Permissions(['request' => $request]);

		return $perms->run();
	}

	/**
	 * Run the Table sub-command
	 *
	 * @return bool
	 */
	public function table(): bool
	{
		$request = $this->request;
		$tables = new Tables(['request' => $request]);

		return $tables->run();
	}

	/**
	 * Execute the given sub-command for the current request.
	 *
	 * @param string $command The sub-command name. example: Permissions, Tables.
	 *
	 * @return boolean
	 */
	protected function execute($command)
	{
		try {
			if (!$class = $this->instance($command)) {
				return false;
			}
		} catch (ClassNotFoundException $e) {
			return false;
		}
		$data = [];
		$params = $class->invokeMethod('_params');

		foreach ($params as $param) {
			$data[$param] = $class->invokeMethod("_{$param}", [$this->request]);
		}

		if ($message = $class->invokeMethod('_save', [$data])) {
			$this->out($message);

			return true;
		}

		return false;
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

	/**
	 * Get an instance of a sub-command
	 *
	 * @param string $name the name of the sub-command to instantiate
	 * @param array  $config
	 *
	 * @return object
	 */
	protected function instance($name, array $config = [])
	{
		if ($class = Libraries::locate('command.create', Inflector::camelize($name))) {
			$this->request->params['template'] = $this->template;

			return new $class([
				'request' => $this->request,
				'classes' => $this->_classes
			]);
		}

		return $this->_instance($name, $config);
	}
}
