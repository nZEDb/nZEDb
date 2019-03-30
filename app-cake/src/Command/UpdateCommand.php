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

namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use nzedb\utility\Git;


class UpdateCommand extends Command
{
	/**
	 * @var \Cake\Console\ConsoleIo;
	 */
	private $cio;

	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return int
	 */
	public function execute(Arguments $args, ConsoleIo $io) : int
	{
		$this->cio = $io;
		$all = $args->getOption('all') || $args->getOption('nzedb');

		if ($all || $args->getOption('git')) {
			$this->git();
		}

		if ($all || $args->getOption('db') || $args->getOption('sql')) {
			$this->sql();
		}

		return static::CODE_SUCCESS;
	}

	/**
	 * Creates a ConsoleOptionParser for the command
	 *
	 * @param \Cake\Console\ConsoleOptionParser $parser
	 *
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
	{
		$parser->addOptions([
			'all'       => [
				'help'    => 'Update all of below.',
				'boolean' => true
			],
			'db'        => [
				'help'    => 'Update the database to the latest schema.',
				'boolean' => true
			],
			'git'       => [
				'help'    => 'Fetch updates from github.',
				'boolean' => true
			],
			'nzedb'     => [
				'help'    => 'Same as `all`.',
				'boolean' => true
			],
			'sql'       => [
				'help'    => 'Update the database to the latest schema.',
				'boolean' => true
			],
		]);

		return $parser;
	}

	protected function git() : int
	{
		$git = new Git();
		$branch = $git->getBranch();
		$status = 0;

		$this->cio->info('Updating from git ...');

		if (! \in_array($branch, $git->getBranchesMain(), false)) {
			$this->cio->error('Not on the stable or dev branch! Refusing to update repository!');
		} else {
			$git->pull('origin', $branch);

			$command = 'composer install';
			if (\in_array($branch, $git->getBranchesStable(), false)) {
				$command .= ' --prefer-dist --no-dev';
			} else {
				$command .= ' --prefer-source';
			}

			system($command, $status);
		}

		return $status;
	}

	protected function sql() : void
	{
		$this->cio->info('Checking for any database changes to appy ...');
		\passthru('./zed migrations migrate');
	}
}
