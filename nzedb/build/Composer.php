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
 * @copyright 2017-2019 nZEDb
 */
namespace nzedb\build;

use Composer\Script\Event;


class Composer
{
	private static $cwd = null;


	public static function postInstallCmd(Event $event) : void
	{
		self::setDirectory();

		self::addGitHooks();

		self::createComposerLink();

		self::createVendorLink();
	}

	public static function postUpdateCmd(Event $event) : void
	{
		self::setDirectory();

		self::addGitHooks();

		self::createComposerLink();

		self::createVendorLink();
	}

	/**
	 *  IF we are in Composer's dev mode Create symlink(s) from .../.git/hooks/* to the file in
	 *  .../build/git-hooks/*.
	 */
	protected static function addGitHooks(): void
	{
		if (getenv('COMPOSER_DEV_MODE') == 1) {
			echo 'Updating git hooks... ';

			$hooks = ['pre-commit', 'post-merge'];
			foreach ($hooks as $hook) {
				$link = self::$cwd . '.git/hooks/' . $hook;
				if (!\file_exists($link) || !\is_link($link)) {
					if (\file_exists($link)) {
						unlink($link);
					}

					self::createSymlink(self::$cwd . 'build/git-hooks/' . $hook, $link);
				}
			}
			echo 'done,' . PHP_EOL;
		}
	}

	protected static function createComposerLink() : void
	{
		if (!\file_exists(self::$cwd . 'app-laravel/composer.json')) {
			self::createSymlink(self::$cwd . 'composer.json', self::$cwd . 'app-laravel/composer.json');
		}
	}

	protected static function createVendorLink() : void
	{
		if (!\file_exists(self::$cwd . 'app-laravel/vendor')) {
			self::createSymlink(self::$cwd . 'vendor', self::$cwd . 'app-laravel/vendor');
		}
	}


	private static function createSymlink(string $target, string $link, bool $verbose = false) : void
	{
		if ($verbose) {
			echo "Creating symlink from '$link' to '$target'... ";
		}

		$result = \symlink($target, $link);

		if (!$result) {
			echo 'FAILED' . PHP_EOL;
			throw new \ErrorException("Failed to create symlink from '$link' to '$target'");
		}

		if ($verbose) {
			echo 'done.' . \PHP_EOL;
		}
	}

	private static function setDirectory() : void
	{
		if (\is_null(self::$cwd)) {
			self::$cwd = \getcwd() . \DIRECTORY_SEPARATOR;
		}
	}
}

?>
