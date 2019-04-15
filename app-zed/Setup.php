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
 * @copyright 2019 nZEDb
 */
namespace zed;

use nzedb\utility\Misc;


class Setup
{
	public const LOCK_FILE = Nzedb::CONFIGS . 'install.lock';

	public $apacheRewrite;

	public $configPath;

	public $coversAnime;

	public $coversAudio;

	public $coversAudioSample;

	public $coversBook;

	public $coversConsole;

	public $coversMovies;

	public $coversMusic;

	public $coversPreview;

	public $coversSample;

	public $coversVideo;

	public $crypt;

	public $curl;

	public $error = true;

	public $exif;

	public $gd;

	public $iconv;

	public $json;

	public $openssl;

	public $pdo;

	public $pdo_mysql;

	public $pear;

	public $phpVersion;

	public $phpMaxExec;

	public $phpMemoryLimit;

	public $phpTimeZone;

	public $sessionPathPerms;

	public $sha1;

	public $smartyCache;

	public function __construct()
	{
		;
	}

	public function isApache()
	{
		// Damnit, info not available from CLI
		//return preg_match('/apache/i', $_SERVER['SERVER_SOFTWARE']);
		return false;
	}

	public function runChecks() : bool
	{
		$this->isLocked();

		$this->checkSession();

		$this->checkPhp();
		$this->checkPhpExtensions();
		$this->checkPhpFunctions();

		$this->checkCoversPaths();
		$this->checkConfigPath();
		$this->checkPear();

		$this->checkSmartyCache();

		return $this->error;
	}


	protected function checkApache() : void
	{
		if ($this->isApache()) {
			$this->apacheRewrite = \function_exists('apache_get_modules') &&
				\in_array('mod_rewrite', apache_get_modules(), true);
		} else {
			$this->apacheRewrite = true;
		}
	}

	protected function checkConfigPath() : void
	{
		$this->configPath = \is_writable(Nzedb::CONFIGS);
		$this->error = $this->error || !$this->configPath;
	}

	protected function checkCoversPaths() : void
	{
		$covers = [
			'anime' => 'Anime',
			'audio' => 'Audio',
			'audiosample' => 'AudioSample',
			'book' => 'Book',
			'console' => 'Console',
			'movies' => 'Movies',
			'music' => 'Music',
			'preview' => 'Preview',
			'sample' => 'Sample',
			'video' => 'Video',
		];

		foreach ($covers as $dir => $var) {
			$field = 'covers' . $var;
			$this->$field = \is_writable(Nzedb::COVERS . $dir);
			$this->error = $this->error || !$this->$field;
		}
	}

	protected function checkPhpMemorytLimit() : void
	{
		$unlimited = ini_get('memory_limit') == -1;
		$enough = $unlimited ?: Misc::returnBytes(ini_get('memory_limit')) >= 1073741824;
		$this->phpMemoryLimit = $unlimited || $enough;
	}

	protected function checkPear() : void
	{
		@include 'System.php';
		$this->pear = \class_exists('System');
		$this->error = $this->error || !$this->pear;
	}

	protected function checkPhp() : void
	{
		$this->checkPhpMemorytLimit();
		$this->phpVersion = version_compare(PHP_VERSION, Nzedb::MIN_PHP_VER) !== -1;
		//$this->phpMaxExec = (\ini_get('max_execution_time') >= 120); // In CLI this is always 0
		$this->phpTimeZone = !empty(ini_get('date.timezone'));

		$this->error = $this->phpTimeZone || $this->error;
	}

	protected function checkPhpExtensions() : void
	{
		$this->testExtension('curl');
		$this->testExtension('exif');
		$this->testExtension('gd');
		$this->testExtension('iconv');
		$this->testExtension('json');
		$this->testExtension('pdo');
		$this->testExtension('pdo_mysql');

		$this->openssl = \extension_loaded('openssl');
	}

	protected function checkPhpFunctions() : void
	{
		$this->testFunction('crypt');
		$this->testFunction('sha1');
	}

	protected function checkSession() : void
	{
		$sessionPath = session_save_path();
		$sessionPath = $sessionPath ?: sys_get_temp_dir();

		if (!is_readable($sessionPath) || !is_writable($sessionPath)) {
			$this->sessionPathPerms = false;
			$this->error = true;
		} else {
			$this->sessionPathPerms = true;

			$this->error = false;
		}
	}

	protected function checkSmartyCache(): void
	{
		$this->smartyCache = is_writable(Nzedb::RESOURCES . 'smarty' . DS . 'templates_c');
		$this->error = $this->error || !$this->smartyCache;
	}

	/**
	 * Tests for the existence of a .lock file in the configuration directory.
	 *
	 * Throws an exception if the file exists, to prevent setup from making changes to the
	 * configuration. Otherwise it returns normally allowing the app to make changes.
	 *
	 * @return void
	 * @throws \ErrorException
	 */
	protected function isLocked() : void
	{
		if (file_exists(self::LOCK_FILE)) {
			$message = 'Installation is locked! Remove ' . self::LOCK_FILE . ' if you really want to redo configuration setup';
			$this->error($message);

			throw new \ErrorException($message);
		}
	}

	private function testExtension(string $ext) : bool
	{
		$result = \extension_loaded($ext);
		$this->$ext = $result;
		$this->error = $this->error || $result;

		return $result;
	}

	private function testFunction(string $function) : bool
	{
		$result = \function_exists($function);
		$this->$function = $result;
		$this->error = $this->error || $result;

		return $result;
	}
}
