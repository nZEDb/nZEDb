<?php
namespace nzedb\libraries;

/**
 * Class ForkingImportNZB
 *
 * Multi-processing of NZB Import.
 */
class ForkingImportNZB extends Forking
{
	/**
	 *
	 */
	public function __construct()
	{
		parent::__construct();
		$this->importPath = (PHP_BINARY . ' ' . nZEDb_MISC . 'testing' . DS . 'nzb-import.php ');
	}

	public function __destruct()
	{
		parent::__destruct();
	}

	private $deleteComplete;
	private $deleteFailed;
	private $useFileName;
	private $maxPerProcess;

	public function start($folder, $maxProcesses, $deleteComplete, $deleteFailed, $useFileName, $maxPerProcess)
	{
		$startTime = microtime(true);
		$directories = glob($folder . '/*' , GLOB_ONLYDIR);

		$this->_workCount = count($directories);

		if ($this->_workCount == 0) {
			echo $this->_colorCLI->error('No sub-folders were found in your specified folder (' . $folder . ').');
			exit();
		}

		if (nZEDb_ECHOCLI) {
			echo $this->_colorCLI->header(
				'Multi-processing started at ' . date(DATE_RFC2822) . ' with ' . $this->_workCount .
				' job(s) to do using a max of ' . $maxProcesses . ' child process(es).'
			);
		}

		$this->deleteComplete = $deleteComplete;
		$this->deleteFailed = $deleteFailed;
		$this->useFileName = $useFileName;
		$this->maxPerProcess = $maxPerProcess;

		$this->max_children_set($maxProcesses);
		$this->register_child_run([0 => $this, 1 => 'importChildWorker']);
		$this->child_max_run_time_set(86400);
		$this->addwork($directories);
		$this->process_work(true);

		if (nZEDb_ECHOCLI) {
			$this->_colorCLI->doEcho(
				$this->_colorCLI->header(
					'Multi-processing for import finished in ' . (microtime(true) - $startTime) .
					' seconds at ' . date(DATE_RFC2822) . '.' . PHP_EOL
				)
			);
		}
	}

	public function importChildWorker($directories, $identifier = '')
	{
		foreach ($directories as $directory) {
			$this->_executeCommand(
				$this->importPath  . '"' .
				$directory . '" ' .
				$this->deleteComplete . ' ' .
				$this->deleteFailed . ' ' .
				$this->useFileName . ' ' .
				$this->maxPerProcess
			);
		}
	}
}