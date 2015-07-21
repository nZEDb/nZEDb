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

require_once nZEDb_LIBS . 'autoloader.php';

class Configure
{
	private $environments = [
		'indexer'	=> [
			'config' => true,
			'settings' => false
		],
		'install'	=> [],
		'smarty'	=> [
			'config' => true,
			'settings' => false
		],
	];

	public function __construct($environment = 'indexer')
	{
		$this->loadEnvironment($environment);
	}

	private function loadEnvironment($environment)
	{
		if (array_key_exists($environment, $this->environments)) {
			foreach ($this->environments[$environment] as $config => $throwException) {
				$this->loadSettings($config, $throwException);
			}
		} else {
			throw new \RuntimeException("Unknown environment passed to Configure class!");
		}
	}

	public function loadSettings($filename, $throwException = true)
	{
		$file = nZEDb_CONFIGS . $filename . '.php';
		if (!file_exists($file)) {
			if ($throwException) {
				$errorCode = (int)($filename === 'config');
				throw new \RuntimeException(
					"Unable to load configuration file '$file'. Make sure it has been created and contains correct settings.",
					$errorCode
				);
			}
		} else {
			require_once $file;
		}

		switch ($filename) {
			case 'config':
				// Check if they updated config.php for the openssl changes. Only check 1 to save speed.
				if (!defined('nZEDb_SSL_VERIFY_PEER')) {
					define('nZEDb_SSL_CAFILE', '');
					define('nZEDb_SSL_CAPATH', '');
					define('nZEDb_SSL_VERIFY_PEER', '0');
					define('nZEDb_SSL_VERIFY_HOST', '0');
					define('nZEDb_SSL_ALLOW_SELF_SIGNED', '1');
				}
				break;
			case 'settings':
				break;
		}
	}
}
