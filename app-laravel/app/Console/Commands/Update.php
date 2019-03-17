<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use nzedb\utility\Git;

class Update extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update {target=all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update various parts of your nZEDb installation.';

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
    public function handle() : void
    {
    	$this->info('Putting site into maintainance mode while performing updates!');
    	Artisan::call('down');

		$target = $this->argument('target');

		if (\in_array($target, ['all', 'nzedb', 'git', 'repo', 'app'])) {
			$this->git();
		}

		if (\in_array($target, ['all', 'nzedb', 'db'])) {
			$this->db();
		}

		if ($target ==='predb') {
			$this->predb();
		}

		$this->info('Coming out of maintainance mode.');
		Artisan::call('up');
	}

	protected function db() : void
	{
		Artisan::call('migrate');
	}

	/**
	 * Update the code base to the latest version for its branch, provided that branch is one of the
	 * stable or main development branches.
	 */
	protected function git()
	{
		$git = new Git();
		$branch = $git->getBranch();
		$this->info('Performing code updates');
		if (!\in_array($branch, $git->getBranchesMain(), false)) {
			$this->error('Not on the stable or dev branch! Refusing to update repository!');
		} else {
			$git->pull('origin', $branch);

			$command = 'composer install';
			if (\in_array($branch, $git->getBranchesStable(), false)) {
				$command .= ' --prefer-dist --no-dev';
			} else {
				$command .= ' --prefer-source';
			}

			system($command, $status);

			return $status;
		}
	}

	protected function predb()
	{
		$this->error('predb not available yet!');
	}
}
