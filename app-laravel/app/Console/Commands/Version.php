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
namespace App\Console\Commands;

use Illuminate\Console\Command;
use nzedb\Nzedb;
use nzedb\utility\Git;
use nzedb\utility\Versions;


class Version extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'version {target=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List version information about various parts of nZEDb';

    private $git;
    private $version;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
    	$this->git = new Git();
    	$this->version = new Versions(Nzedb::VERSIONS);

        $target = $this->argument('target');

        if (\in_array($target, ['all', 'git'])) {
			$this->git();
		}

		if (\in_array($target, ['all', 'sql', 'db'])) {
			$this->sql();
		}
    }

    protected function git()
	{
		$this->info('Looking up Git tag version(s)');
		$this->line('Latest Hash: ' . trim($this->git->getHeadHash()));
		$this->line('XML version: ' . $this->version->getGitTagFromFile());
		$this->line('Git version: ' . $this->git->getTagLatest());
		$this->line('');
	}

	protected function sql()
	{
		$this->info('Looking up SQL patch version(s)');
		$this->line('XML version: ' . $this->version->getSQLPatchFromFiles());
		$this->line(' DB version: ' . $this->version->getSQLPatchFromDb());
		$this->line('');
	}
}
