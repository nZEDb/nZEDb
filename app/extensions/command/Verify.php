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
 *
 * Actions:
 *  * settings_table	Checks that all settings in the 10~settings.tsv exist in your Db.
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

	public function settingstable()
	{
		$filepath = nZEDb_RES . Text::pathCombine(['db', 'schema', 'data', '10-settings.tsv']);
		if (!file_exists($filepath)) {
			throw new \InvalidArgumentException("Unable to find {$filepath}");
		}
		$settings = file($filepath);

		if (!is_array($settings)) {
			var_dump($settings);
			throw new \InvalidArgumentException("Settings is not an array!");
		}

		$setting = [];
		$dummy = array_shift($settings);
		if ($dummy !== null) {
			$this->primary("Verifying settings table...");
			$this->info("(section, subsection, name):");
			foreach ($settings as $line) {
				$message = '';
				switch (PHP_MAJOR_VERSION) {
					case 7:
						list(
							$setting['section'],
							$setting['subsection'],
							$setting['name'],
							) = explode("\t", $line);
						break;
					case 5:
						list(
							$setting['name'],
							$setting['subsection'],
							$setting['section']
							) = explode("\t", $line);
						break;
					default:
						throw new \RuntimeException("PHP version not recognised!");
				}

				$value = Settings::value(
					[
						'section'    => $setting['section'],
						'subsection' => $setting['subsection'],
						'name'       => $setting['name']
					],
					true);
				if ($value === null) {
					$message = "error";
				}

				if ($message != '') {
					$this->out(" {$setting['section']}, {$setting['subsection']}, {$setting['name']}: " . "MISSING!");
				}
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

		var_dump($this->request->args());

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
