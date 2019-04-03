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

	public $error = false;

	public $exif;

	public $gd;

	public $iconv;

	public $json;

	public $openssl;

	public $pdo;

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

	public function runChecks() : void
	{
		$this->isLocked();

		$this->checkSession();
		$this->checkPhp();
		$this->checkCoversPaths();
		$this->checkConfigPath();
		$this->checkPear();

		$this->checkSmartyCache();
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
		$this->error |= !$this->configPath;
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
			$this->error |= ! $this->$field;
		}
	}

	protected function checkCrypt() : void
	{
		$this->crypt = \function_exists('crypt');
		$this->error |= !$this->crypt;
	}

	protected function checkIconv() : void
	{
		$this->iconv = \function_exists('iconv');
		$this->error |= !$this->iconv;
	}

	protected function checkJson() : void
	{
		$this->json = \extension_loaded('json');
		$this->error |= !$this->json;
	}

	protected function checkPhpMemorytLimit() : void
	{
		$unlimited = \ini_get('memory_limit') == -1;
		$enough = $unlimited ?: Misc::returnBytes(ini_get('memory_limit')) >= 1073741824;
		$this->phpMemoryLimit = $unlimited || $enough;
	}

	protected function checkPDO(): void
	{
		$this->pdo = \extension_loaded('PDO');
		$this->error |= !$this->pdo;
	}

	protected function checkPear() : void
	{
		@include 'System.php';
		$this->pear = \class_exists('System');
		$this->error |= !$this->pear;
	}

	protected function checkPhp() : void
	{
		$this->checkPhpMemorytLimit();
		$this->phpVersion = \version_compare(PHP_VERSION, nZEDb_MINIMUM_PHP_VERSION, '>=');
		//$this->phpMaxExec = (\ini_get('max_execution_time') >= 120); // In CLI this is always 0
		$this->phpTimeZone = \ini_get('date.timezone') !== '';

		$this->error |= !$this->phpTimeZone;

		$this->checkPhpExtensions();
	}

	protected function checkPhpExtensions() : void
	{

		$this->checkIconv();
		$this->checkCrypt();
		$this->checkSHA1();
		$this->checkPDO();
		$this->checkJson();

		$this->curl = \function_exists('curl_init');
		$this->exif = \extension_loaded('exif');
		$this->gd = \function_exists('imagecreatetruecolor');
		$this->openssl = \extension_loaded('openssl');
	}

	protected function checkSession() : void
	{
		$sessionPath = session_save_path();
		$sessionPath = $sessionPath ?: sys_get_temp_dir();

		if (!is_readable($sessionPath) || !is_writable($sessionPath)) {
			$this->error = true;
			$this->sessionPathPerms = false;
		}
	}

	protected function checkSHA1() : void
	{
		$this->sha1 = \function_exists('sha1');
		$this->error |= !$this->sha1;
	}

	protected function checkSmartyCache(): void
	{
		$this->smartyCache = is_writable(nZEDb_RES . 'smarty' . DS . 'templates_c');
		$this->error |= !$this->smartyCache;
	}

	protected function isLocked() : void
	{
		if (file_exists(self::LOCK_FILE)) {
			$message = 'Installation is locked! Remove ' . self::LOCK_FILE . ' if you really want to redo configuration setup';
			$this->error($message);

			throw new \ErrorException($message);
		}
	}
}
