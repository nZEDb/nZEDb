<?php
namespace App\Command;

use Cake\Console\Arguments;
use Cake\Console\Command;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Datasource\ConnectionManager;
use zed\Nzedb;
use zed\Setup;

/**
 * Setup command.
 */
class SetupCommand extends Command
{
	/**
	 * @var \Closure
	 */
	protected $askAndValidate;

	/**
	 * @var \Cake\Console\ConsoleIo
	 */
	protected $cio;

	protected $descriptions = [
		'preflight' => 'Some quick checks before we get started. If any of these fail, they must be ' .
			"\ncorrected before we can continue. You can make corrections in another screen " .
			"\nand hit ENTER to refresh this screen.",
		'database' => 'Here, we collect your database credentials and tests to make sure they work.' .
			"\nYou must have created the Db already. We'll create the tables later." .
			"\nIf using a unix-like operating system it is recommended to use sockets. The " .
			"\nsocket path can be found in your my.cnf file." .
			"\nEntering a socket path will prevent the host from being used." .
			"\n\nDo not use your root user. Create a new user for nZEDb (i.e. `nzedb`)" .
			"\nThe FILE permission must be given to your user, GRANT ALL does NOT include the " .
			"\nFILE permission.",
		'cacert' => 'An openssl CA bundle file is recommended to verify the authenticity of remote' .
			"\ncertificates when connecting to various servers using TLS/SSL. If you do not " .
			"\nhave a CA bundle, you can download one from: " .
			"\n        <comment>http://curl.haxx.se/docs/caextract.html</comment>" .
			"\nIf you are using Linux place the bundle in the /etc/ssl/certs/ directory.",
		'nntp' => 'Usenet server (NNTP) credentials.'
	];

	private $cacerts = [
		'cafile' => null,
		'cahost' => null,
		'capath' => null,
		'capeer' => null,
		'caself' => null,
	];

	private $dbDetails = [
		'host' => null,
		'port' => 3306,
		'sock' => null,
		'user' => 'nzedb',
		'pass' => null,
		'db'   => 'nzedb',
	];

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
		//$this->step2();
		//$this->step3();
		/*
		$this->step4();
		$this->step5();
		$this->step6();
		$this->step7();
		$this->step8();
		$this->step9();
		*/
	}

	protected function getStatus(bool $status) : string
	{
		return $status ? '<success>Passed</success>' : '<error>FAILED</error>';
	}

	protected function header(string $info, string $description = '') : void
	{
		\passthru('clear');
		$this->cio->info('Setup nZEDb - You can quit this process at any time with CTRL-C');
		$this->cio->hr();
		$this->cio->out('');

		$this->cio->info($info);
		$this->cio->out($description);
		if (!empty($description)) {
			$this->cio->out('');
		}
	}

	protected function inputCertificateDetails(array &$certs) : void
	{
		$certs['cafile'] = $this->cio->ask('Location of Certificate Authority file (e.g. /etc/ssl/certs/cacert.pem)');
		$certs['capath'] = $this->cio->ask('Certificate  directory to search if the file above is not found. (e.g. /etc/ssl/certs/)');
		$certs['capeer'] = $this->cio->askChoice('Verify Peer? (Requires the CA bundle file path to be set).', ['y', 'n'], 'N');
		$certs['cahost'] = $this->cio->askChoice('Verify Host? (Requires the CA bundle file path to be set).', ['y', 'n'], 'N');
		$certs['caself'] = $this->cio->askChoice('Allow self-signed certificates? This stops self-signed certificates from being verified (and failing)',
			['y',	'n'], 'N');
	}

	protected function inputDatabaseDetails(array &$db) : void
	{
		$db['host'] = $this->cio->ask('Host - Name or IP (Empty if using Unix sockets)',
			$db['host']);
		$db['port'] = $this->cio->ask('Port number', $db['port']);
		$db['sock'] = $this->cio->ask('Socket file (Leave empty if using Host)',
			$db['sock']);
		$db['user'] = $this->cio->ask('User name (Required)', 'nzedb', $db['user']);
		$db['pass'] = $this->cio->ask('Password (Required)', $db['pass']);
		$db['db'] = $this->cio->ask('Database name (Required)', $db['db']);
	}

	protected function outputCertificateDetails(array $certs) : void
	{
		$this->cio->helper('Table')->output([
				['Setting', 'Value'],
				['Path to file', \strtoupper($certs['cafile'])],
				['Search directory', \strtoupper($certs['capath'])],
				['Verify Peer', \strtoupper($certs['capeer'])],
				['Verify Host', \strtoupper($certs['cahost'])],
				['Allow self-signing', \strtoupper($certs['caself'])],
			]);
	}

	protected function outputChecklist(string $info, $description = ''): void
	{
		$this->header($info, $description);

		$extensions = [
			['Required PHP Extensions', 'Status'],
			['Curl', $this->getStatus($this->setup->curl)],
			['Exif', $this->getStatus($this->setup->exif)],
			['GD', $this->getStatus($this->setup->gd)],
			['Iconv', $this->getStatus($this->setup->iconv)],
			['JSON', $this->getStatus($this->setup->json)],
			['OpenSSL', $this->getStatus($this->setup->openssl)],
			['PDO', $this->getStatus($this->setup->pdo)],
			['PDO MySQL', $this->getStatus($this->setup->pdo_mysql)],
		];

		$functions = [
			['Required functions', 'Status'],
			['Checking for crypt():', $this->getStatus($this->setup->crypt)],
			['Checking for SHA1', $this->getStatus($this->setup->sha1)],
		];

		$misc = [
			['Miscelaneous requirements', 'Status'],
			//['Session path (' . session_save_path() . ') is read/writeable', $this->getStatus($this->setup->sessionPathPerms)],
			['Configuration path is writable', $this->getStatus($this->setup->configPath)],
			['PHP\'s version >= ' . Nzedb::MIN_PHP_VER, $this->getStatus ($this->setup->phpVersion)],
			['PHP\'s date.timezone is set', $this->getStatus($this->setup->phpTimeZone)],
			//['PHP\'s max_execution_time >= 120', $this->getStatus($this->setup->phpMaxExec)],
			['PHP\'s memory_limit >= 1GB', $this->getStatus($this->setup->gd)],
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

	protected function outputDatabaseDetails(array $details) : void
	{
		$this->cio->helper('Table')->output([
			['Setting', 'Value'],
			['Host', $details['host']],
			['Port', $details['port']],
			['Socket', $details['sock']],
			['Username', $details['user']],
			['Password', $details['pass']],
			['Database', $details['db']],
		]);
	}

	/**
	 * Pre-flight checks
	 *
	 * @return void
	 */
	protected function step1() : void
	{
		while ($this->setup->runChecks() !== false) {
			$this->outputChecklist('Pre-start checklist', $this->descriptions['preflight']);
			$this->cio->ask('Press ENTER to refresh.');

			//exit('Correct the problems indicated above and run setup again!' . PHP_EOL);
		}

		$this->outputChecklist('Pre-start checklist');
		$this->cio->ask('Press ENTER to continue.');
	}

	/**
	 * Get Database Details
	 *
	 * @return void
	 */
	protected function step2() : void
	{
		$this->askAndCheck(
			[$this, 'inputDatabaseDetails'],
			[$this, 'outputDatabaseDetails'],
			[$this, 'testDbConnection'],
			$this->dbDetails,
			'Database credentials',
			$this->descriptions['database']
		);
	}

	protected function step3(): void
	{
		$valid = function () { return true; };

		$this->askAndCheck(
			[$this, 'inputCertificateDetails'],
			[$this, 'outputCertificateDetails'],
			$valid,
			$this->cacerts,
			'OpenSSL Certificates',
			$this->descriptions['cacert']
		);
	}

	protected function step4(): void
	{
		//;
	}

/*
	protected function step5(): void
	{
		//;
	}

	protected function step6(): void
	{
		//;
	}

	protected function step7(): void
	{
		//;
	}

	protected function step8(): void
	{
		//;
	}

	protected function step9(): void
	{
		//;
	}
*/

	protected function testDbConnection() : bool
	{
		$db = & $this->dbDetails;
		try {
			ConnectionManager::setConfig('test-db',
			[
				'className'     => 'Cake\Database\Connection',
				'driver'        => 'Cake\Database\Driver\Mysql',
				'persistent'    => false,
				'host'          => $db['host'],
				'port'          => $db['port'],
				'unix_socket'   => $db['sock'],
				'username'      => $db['user'],
				'password'      => $db['pass'],
				'database'      => $db['db'],
				'encoding'      => 'utf8mb4',
				'timezone'      => 'UTC',
				'cacheMetadata' => true,
			]);
			$connection = ConnectionManager::get('test-db');
			$connection->execute('SELECT @@version;');
			$connected = true;
		} catch (\Exception $e) {
			$connected = false;
			$this->cio->error($e->getMessage());
		}
		ConnectionManager::drop('test-db');

		return $connected;
	}

	protected function validDbDetails() : bool
	{
		$status = !empty($this->dbDetails['host']) && !empty($this->dbDetails['port']);
		$status &= !empty($this->dbDetails['user']);
		$status &= !empty($this->dbDetails['pass']);
		$status |= !empty($this->dbDetails['sock']);

		return $status;
	}

	private function askAndCheck(callable $ask,
								 callable $show,
								 callable $valid,
								 array &$data,
								 string $info,
								 string $description)
	{
		$confirm = 'y';
		while ($confirm == 'y') {
			$this->header($info, $description);
			$ask($data);

			$this->header($info);
			$show($data);

			if ($valid()) {
				$confirm = \strtolower($this->cio->askChoice('Change?', ['Y', 'n'], 'Y'));
			}
		}
	}
}
