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
use Cake\Core\Configure;
use nzedb\utility\Git;
use nzedb\utility\Versions;
use zed\Nzedb;


class VersionCommand extends Command
{
	private $git;

	private $version;


	public function execute(Arguments $args, ConsoleIo $io)
	{
		$versions = new Versions(Nzedb::VERSIONS);

		$all = $args->getOption('all');

		if ($all || $args->getOption('nzedb')) {
			$this->nzedb($io);
		}

		if ($all || $args->getOption('cake') || $args->getOption('framework')) {
			$this->framework($io);
		}

		if ($all || $args->getOption('git')) {
			$this->git($io, $versions);
		}

		if ($all || $args->getOption('db') || $args->getOption('sql')) {
			$this->sql($io, $versions);
		}
	}

	/**
	 * Creates a ConsoleOptionParser for the command
	 *
	 * @param \Cake\Console\ConsoleOptionParser $parser
	 *
	 * @return \Cake\Console\ConsoleOptionParser
	 */
	protected function buildOptionParser(ConsoleOptionParser $parser) : ConsoleOptionParser
	{
		$parser->addOptions([
			'all'	=> [
				'help'	=> 'Show all available info.',
				'boolean'	=> true
			],
			'cake' => [
				'help'    => 'Show Cake version.',
				'boolean' => true
			],
			'db'	=> [
				'help' => 'Show database info.',
				'boolean' => true
			],
			'framework'	=> [
				'help'	=> 'Show Cake version.',
				'boolean' => true
			],
			'git'	=> [
				'help' => 'Show git info.',
				'boolean' => true
			],
			'nzedb'	=> [
				'help' => 'Show all nZEDb info.',
				'boolean' => true
			],
			'sql'	=> [
				'help' => 'Show database info.',
				'boolean' => true
			],
		]);

		return $parser;
	}

	/**
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return int
	 */
	protected function framework(ConsoleIo $io) : int
	{
		$io->info('Framework version: ', 0);
		$io->out(Configure::version());

		return static::CODE_SUCCESS;
	}

	/**
	 * @param \Cake\Console\ConsoleIo $io
	 *
	 * @return int
	 */
	protected function git(ConsoleIo $io, Versions $versions) : int
	{
		$git = new Git();
		$io->info('Git information');
		$io->hr();
		$io->out('Latest Hash: ' . trim($git->getHeadHash()));

		$io->out('XML version: ' . $versions->getGitTagFromFile());
		$io->out('Git version: ' . $git->getTagLatest());
		$io->out('Git Branch : ' . $git->getBranch());
		$io->out($io->nl(1));

		return static::CODE_SUCCESS;
	}

	protected function nzedb(ConsoleIo $io)
	{
		$git = new Git();
		$io->info('nZEDb version: ', 0);
		$io->out($git->getTagLatest());
	}

	/**
	 * @param \Cake\Console\ConsoleIo $io
	 * @param \nzedb\utility\Versions $versions
	 *
	 * @return int
	 */
	protected function sql(ConsoleIo $io, Versions $versions) : int
	{
		$io->info('SQL versions');
		$io->hr();
		$io->out('XML version: ' . $versions->getSQLPatchFromFiles());
		$io->out(' DB version: ' . $versions->getSQLPatchFromDb());
		$io->out($io->nl(1));

		return static::CODE_SUCCESS;
	}
}
