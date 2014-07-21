<?php
namespace nzedb\libraries;

require_once(nZEDb_LIBS . 'forkdaemon-php' . DS . 'fork_daemon.php');

/**
 * Class Forking
 *
 * @package nzedb\libraries
 */
class Forking extends \fork_daemon
{
	/**
	 * Setup the class to work on a type of work, then process the work.
	 * Valid work types:
	 *
	 * @param string $type    The type of multiProcessing to do : backfill, binaries, releases, postprocess
	 * @param array  $options Array containing arguments for the type of work.
	 *
	 * @throws ForkingException
	 */
	public function processWorkType($type, array $options = array())
	{
		$time = microtime(true);
		// Init Settings here, as forking causes errors when the class goes out of scope.
		$this->pdo = new \nzedb\db\Settings();
		$this->getWork($type, $options);
		// Now we destroy settings, to prevent errors from forking.
		unset($this->pdo);
		$this->processWork();
		if (nZEDb_ECHOCLI) {
			echo 'Multiprocessing for ' . $type . ' finished in ' . (microtime(true) - $time) . ' seconds.' . PHP_EOL;
		}
	}

	/**
	 *
	 */
	public function __construct()
	{
		parent::__construct();
		$this->register_logging(
			[0 => $this, 1 => 'logger'],
			(nZEDb_DEBUG === true ? \fork_daemon::LOG_LEVEL_ALL : \fork_daemon::LOG_LEVEL_INFO)
		);
		$this->max_children_set(3);
		$this->max_work_per_child_set(1);
		$this->child_max_run_time_set(600);
		$this->register_parent_child_exit([0 => $this, 1 => 'childExit']);
	}

	/**
	 * @var array
	 */
	private $work = array();

	/**
	 * Get work for our workers to work on.
	 *
	 * @param string $type
	 * @param array  $options
	 */
	private function getWork(&$type, array &$options = array())
	{
		$maxProcesses = 0;
		switch ($type) {
			// The option for backFill is for doing up to x articles. Else it's done by date.
			case 'backfill':
				$this->register_child_run([0 => $this, 1 => 'backFillChildWorker']);
				$this->work = $this->pdo->query(
					sprintf(
						'SELECT name %s FROM groups WHERE backfill = 1',
						($options[0] === false ? '' : (', ' . $options[0] . ' AS max'))
					)
				);
				$maxProcesses = $this->pdo->getSetting('backfillthreads');
				break;

			case 'binaries':
				$this->register_child_run([0 => $this, 1 => 'binariesChildWorker']);
				$this->work = $this->pdo->query('SELECT name FROM groups WHERE active = 1');
				$maxProcesses = $this->pdo->getSetting('binarythreads');
				break;

			case 'releases':
				$this->register_child_run([0 => $this, 1 => 'releasesChildWorker']);
				$this->work = $this->pdo->query('SELECT name FROM groups WHERE (active = 1 OR backfill = 1)');
				$maxProcesses = $this->pdo->getSetting('releasesthreads');
				break;

			case 'postprocess':
				$postProcess = new \PostProcess(true);
				//$postProcess->processAnime();
				$postProcess->processBooks();
				$postProcess->processConsoles();
				$postProcess->processGames();
				$postProcess->processMusic();
				$postProcess->processXXX();

				$sharing = $this->pdo->queryOneRow('SELECT enabled FROM sharing');
				if ($sharing !== false && $sharing['enabled'] == 1) {
					$nntp = new \NNTP(true);
					if (($this->pdo->getSetting('alternate_nntp') == 1 ? $nntp->doConnect(true, true) : $nntp->doConnect()) === true) {
						$postProcess->processSharing($nntp);
					}
				}

				unset($postProcess, $nntp);
				$this->register_child_run([0 => $this, 1 => 'postProcessChildWorker']);
				$this->work = $this->pdo->query('SELECT id FROM groups WHERE (active = 1 OR backfill = 1)');
				$maxProcesses = $this->pdo->getSetting('releasesthreads');
				break;

			default:
				$this->work = array();
				break;
		}

		if (is_numeric($maxProcesses) && $maxProcesses > 0) {
			$this->max_children_set($maxProcesses);
		}
	}

	/**
	 * Process work if we have any.
	 */
	private function processWork()
	{
		if (count($this->work) > 0) {
			$this->addwork($this->work);
			$this->process_work(true);
		} else if (nZEDb_ECHOCLI) {
			echo 'Nothing to do!' . PHP_EOL;
		}
	}

	/**
	 * @var \nzedb\db\Settings
	 */
	private $pdo;

	/**
	 *
	 */
	public function __destruct()
	{
		parent::__destruct();
	}

	/**
	 * All the following methods should not be accessed, they are used by the parent class.
	 */

	/**
	 * Echo a message to CLI.
	 *
	 * @param string $message
	 */
	public function logger($message)
	{
		if (nZEDb_ECHOCLI) {
			echo $message . PHP_EOL;
		}
	}

	/**
	 * This method is executed whenever a child is finished doing work.
	 *
	 * @param string $pid        The PID numbers.
	 * @param string $identifier Optional identifier to give a PID a name.
	 */
	public function childExit($pid, $identifier = '')
	{
		echo 'Process ID #' . $pid . ' has completed.' . PHP_EOL;
	}

	/**
	 * The following methods are where the work is done with the data sent from the parent process.
	 *
	 * @param array  $groups     Array of group data.
	 * @param string $identifier Optional identifier to give a PID a name.
	 */
	public function backFillChildWorker($groups, $identifier = '')
	{
		foreach ($groups as $group) {
			passthru(
				PHP_BINARY . ' ' . nZEDb_MISC . 'update' . DS . 'backfill.php ' .
				$group['name'] . (isset($group['max']) ? (' ' . $group['max']) : ''));
		}
	}

	public function binariesChildWorker($groups, $identifier = '')
	{
		foreach ($groups as $group) {
			passthru(PHP_BINARY . ' ' . nZEDb_MISC . 'update' . DS . 'update_binaries.php ' . $group['name']);
		}
	}

	public function releasesChildWorker($groups, $identifier = '')
	{
		foreach ($groups as $group) {
			passthru(PHP_BINARY . ' ' . nZEDb_MISC . 'update' . DS . 'update_releases.php 1 false ' . $group['name']);
		}
	}

	public function postProcessChildWorker($groups, $identifier = '')
	{
		foreach ($groups as $group) {
			passthru(PHP_BINARY . ' ' . nZEDb_MISC . 'update' . DS . 'postprocess.php additional true ' . $group['id']);
			passthru(PHP_BINARY . ' ' . nZEDb_MISC . 'update' . DS . 'postprocess.php nfo true ' . $group['id']);
		}
	}
}

class ForkingException extends \Exception {}