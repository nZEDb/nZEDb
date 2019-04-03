<?php
namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use zed\Setup;

/**
 * Setup command.
 */
class SetupCommand extends Command
{
	/**
	 * @var \Cake\Console\ConsoleIo
	 */
	protected $cio;

	/**
	 * @var \zed\Setup
	 */
	private $setup;

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/3.0/en/console-and-shells/commands.html#defining-arguments-and-options
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser) : ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        return $parser;
    }

    /**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io) : void
    {
		if (! \defined('nZEDb_INSTALLER')) {
			\define('nZEDb_INSTALLER', true);
		}

		$this->cio = $io;

		$this->setup = new Setup();
		$this->step1();
	}

	protected function getStatus(bool $status) : string
	{
		return $status ? '<success>Passed</success>' : '<error>FAILED</error>';
	}

	protected function header() : void
	{
		\passthru('clear');
		$this->cio->info('Setup nZEDb - You can quit this process at any time with CTRL-C');
		$this->cio->hr();
		$this->cio->out('');
	}

	protected function outputChecklist(string $info): void
	{
		$this->header();
		$this->cio->info($info);

		$extensions = [
			['Required PHP Extensions', 'Status'],
			['Exif', $this->getStatus($this->setup->exif)],
			['GD', $this->getStatus($this->setup->gd)],
			['JSON', $this->getStatus($this->setup->json)],
			['OpenSSL', $this->getStatus($this->setup->openssl)],
			['PDO', $this->getStatus($this->setup->pdo)],
		];

		$functions = [
			['Required functions', 'Status'],
			['Checking for crypt():', $this->getStatus($this->setup->crypt)],
			['Checking for Curl support:', $this->getStatus($this->setup->curl)],
			['Checking for iconv support:', $this->getStatus($this->setup->iconv)],
			['Checking for SHA1', $this->getStatus($this->setup->sha1)],
		];

		$misc = [
			['Miscelaneous requirements', 'Status'],
			['Configuration path is writable', $this->getStatus($this->setup->configPath)],
			['PHP\'s version >= ' . nZEDb_MINIMUM_PHP_VERSION, $this->getStatus ($this->setup->phpVersion)],
			['PHP\'s date.timezone is set', $this->getStatus($this->setup->phpTimeZone)],
			//['PHP\'s max_execution_time >= 120', $this->getStatus($this->setup->phpMaxExec)],
			['PHP\'s memory_limit >= 1GB', $this->getStatus($this->setup->gd, true)],
			['PEAR is available', $this->getStatus($this->setup->pear)],
			['Smarty\'s compile dir is writable', $this->getStatus($this->setup->smartyCache)],
			['Anime covers directory is writable', $this->getStatus($this->setup->coversAnime)],
			['Audio covers directory is writable', $this->getStatus($this->setup->coversAudio)],
			['Audio Sample  covers directory is writable', $this->getStatus($this->setup->coversAudioSample)],
			['Book covers directory is writable', $this->getStatus($this->setup->coversBook)],
			['Console covers directory is writable', $this->getStatus($this->setup->coversConsole)],
			['Movie covers directory is writable', $this->getStatus($this->setup->coversMovies)],
			['Music covers directory is writable', $this->getStatus($this->setup->coversMusic)],
			['Preview covers directory is writable', $this->getStatus($this->setup->coversPreview)],
			['Sample covers directory is writable', $this->getStatus($this->setup->coversSample)],
			['Video covers directory is writable', $this->getStatus($this->setup->coversVideo)],
		];

		if ($this->setup->isApache()) {
			$misc[] = ['Apache\'s mod_rewrite', $this->getStatus($this->setup->apacheRewrite)];
		}

		$this->cio->helper('Table')->output($extensions);
		$this->cio->out('');
		$this->cio->helper('Table')->output($functions);
		$this->cio->out('');
		$this->cio->helper('Table')->output($misc);
	}

	protected function step1() : void
	{
		$this->setup->runChecks();

		while ($this->setup->error === true) {
			$this->outputChecklist('Pre-start checklist');
			$this->cio->ask('Press ENTER to refresh.');

			$this->setup->runChecks();
		}

		$this->outputChecklist('Pre-start checklist');
		$this->cio->ask('Press ENTER to continue.');
	}
}
