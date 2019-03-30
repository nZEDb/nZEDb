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
	/**
	 * @var \Cake\Console\ConsoleIo;
	 */
	private $cio;

	/**
	 * @var \nzedb\utility\Git
	 */
	private $git;

	/**
	 * @var  \Cake\Console\ConsoleIo
	 */
	private $versions;

	/**
	 * @param \Cake\Console\Arguments $args
	 * @param \Cake\Console\ConsoleIo $cio
	 *
	 * @return int
	 */
	public function execute(Arguments $args, ConsoleIo $io) : int
	{
		$this->cio = $io;
		$this->git = new Git();
		$this->versions = new Versions(Nzedb::VERSIONS);

		$all = $args->getOption('all');

		if ($all || $args->getOption('nzedb')) {
			$this->nzedb();
		}

		if ($all || $args->getOption('cake') || $args->getOption('framework')) {
			$this->framework();
		}

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
				'help' => 'Show nZEDb version.',
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
	 *
	 *
	 * @return void
	 */
	protected function framework() : void
	{
		$this->cio->info('Framework version: ', 0);
		$this->cio->out(Configure::version());
	}

	/**
	 *
	 * @return void
	 */
	protected function git() : void
	{
		$this->cio->info('Git information');
		$this->cio->hr();
		$this->cio->out('Latest Hash: ' . trim($this->git->getHeadHash()));

		$this->cio->out('XML version: ' . $this->versions->getGitTagFromFile());
		$this->cio->out('Git version: ' . $this->git->getTagLatest());
		$this->cio->out('Git Branch : ' . $this->git->getBranch());
		$this->cio->out($this->cio->nl(1));
	}

	/**
	 *
	 * @return void
	 */
	protected function nzedb() : void
	{
		$this->cio->info('nZEDb version: ', 0);
		$this->cio->out($this->git->getTagLatest());
	}

	/**
	 *
	 * @return void
	 */
	protected function sql() : void
	{
		$this->cio->info('SQL versions');
		$this->cio->hr();
		$this->cio->out('XML version: ' . $this->versions->getSQLPatchFromFiles());
		$this->cio->out(' DB version: ' . $this->versions->getSQLPatchFromDb());
		$this->cio->out($this->cio->nl(1));
	}
}
