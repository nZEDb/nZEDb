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
 * @copyright 2015 nZEDb
 */
namespace nzedb\config;


class Configure
{
	private $environments = [
		'indexer'	=> ['config'],
		'install'	=> [],
		'shared'	=> [],
		'smarty'	=> ['config'],
	];

	public function __construct($environment = 'indexer')
	{
		$this->loadEnvironment('shared');
		$this->loadEnvironment($environment);
	}

	private function loadEnvironment($environment)
	{
		if (array_key_exists($environment, $this->environments)) {
			foreach ($this->environments[$environment] as $settings) {
				$file = nZEDb_CONFIGS . $settings . '.php';
				if (file_exists($file)) {
					require_once $file;
				} else {
					throw new \RuntimeException("Unable to load configuration file '$settings'. Make sure it has been created and contains correct settings.");
				}
			}
		}
	}
}
