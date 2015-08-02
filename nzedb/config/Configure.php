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
				$settings_file = nZEDb_CONFIGS . 'settings.php';
				if (is_file($settings_file)) {
					require_once($settings_file);
					if (php_sapi_name() == 'cli') {
						$current_settings_file_version = 3; // Update this when updating settings.example.php
						if (!defined('nZEDb_SETTINGS_FILE_VERSION') ||
							nZEDb_SETTINGS_FILE_VERSION != $current_settings_file_version
						) {
							echo("\033[0;31mNotice: Your $settings_file file is either out of date or you have not updated" .
								 " nZEDb_SETTINGS_FILE_VERSION to $current_settings_file_version in that file.\033[0m" .
								 PHP_EOL
							);
						}
						unset($current_settings_file_version);
					}
				} else {
					define('ITEMS_PER_PAGE', '50');
					define('ITEMS_PER_COVER_PAGE', '20');
					define('nZEDb_ECHOCLI', true);
					define('nZEDb_DEBUG', false);
					define('nZEDb_LOGGING', false);
					define('nZEDb_LOGINFO', false);
					define('nZEDb_LOGNOTICE', false);
					define('nZEDb_LOGWARNING', false);
					define('nZEDb_LOGERROR', false);
					define('nZEDb_LOGFATAL', false);
					define('nZEDb_LOGQUERIES', false);
					define('nZEDb_LOGAUTOLOADER', false);
					define('nZEDb_QUERY_STRIP_WHITESPACE', false);
					define('nZEDb_RENAME_PAR2', true);
					define('nZEDb_RENAME_MUSIC_MEDIAINFO', true);
					define('nZEDb_CACHE_EXPIRY_SHORT', 300);
					define('nZEDb_CACHE_EXPIRY_MEDIUM', 600);
					define('nZEDb_CACHE_EXPIRY_LONG', 900);
					define('nZEDb_PREINFO_OPEN', false);
					define('nZEDb_FLOOD_CHECK', false);
					define('nZEDb_FLOOD_WAIT_TIME', 5);
					define('nZEDb_FLOOD_MAX_REQUESTS_PER_SECOND', 5);
					define('nZEDb_USE_SQL_TRANSACTIONS', true);
					define('nZEDb_RELEASE_SEARCH_TYPE', 0);
					define('nZEDb_MAX_PAGER_RESULTS', '125000');
				}
				unset($settings_file);
				break;
		}
	}
}
