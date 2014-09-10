<?php
/**
 * Show log message to CLI/Web and log it to a file.
 * Turn these on in automated.config.php
 *
 * @example usage:
 *
 *          (in method, LOG_INFO would be the severity of your error, see below)
 *          $this->Logger->start("MyClassName", "MyMethodName", "My debug message.", LOG_INFO);
 */
class Logger
{
	// You can use these constants when using the start method.
	const LOG_FATAL   = 1; // Fatal error, the program exited.
	const LOG_ERROR   = 2; // Recoverable error.
	const LOG_WARNING = 3; // Warnings.
	const LOG_NOTICE  = 4; // Notices.
	const LOG_INFO    = 5; // Info message, not important.
	const LOG_SQL     = 6; // Full SQL query when it fails.

	/**
	 * Name of class we are currently logging.
	 * @var string
	 * @access private
	 */
	private $class;

	/**
	 * Name of method we are currently logging.
	 * @var string
	 * @access private
	 */
	private $method;

	/**
	 * The log message.
	 * @var string
	 * @access private
	 */
	private $logMessage = '';

	/**
	 * Severity level.
	 * @var string
	 * @access private
	 */
	private $severity = '';

	/**
	 * Class instance of colorCLI
	 * @var object
	 * @access private
	 */
	private $colorCLI;

	/**
	 * Should we echo to CLI or web?
	 * @var bool
	 * @access private
	 */
	private $outputCLI = true;

	/**
	 * Cache of the date.
	 * @var string
	 * @access private
	 */
	private $dateCache = '';

	/**
	 * Cache of unix time.
	 * @var int
	 * @access private
	 */
	private $timeCache;

	/**
	 * Is this the windows O/S?
	 * @var bool
	 * @access private
	 */
	private $isWindows;

	/**
	 * Unix time instance was created.
	 * @var int
	 * @access private
	 */
	private $timeStart;

	/**
	 * @var resource|null Resource for log file.
	 * @access private
	 */
	private $resource = null;

	/**
	 * How many old logs can we have max in the logs folder.
	 * (per log type, ex.: debug can have x logs, not_yEnc can have x logs, etc)
	 * @var int
	 * @access private
	 */
	private $maxLogs;

	/**
	 * Max log size in MegaBytes.
	 * @var int
	 * @access private
	 */
	private $maxLogSize;

	/**
	 * Full path to the log file.
	 * @var string
	 * @access private
	 */
	private $logPath;

	/**
	 * Current name of the log file.
	 * @var string
	 * @access private
	 */
	private $currentLogName;

	/**
	 * Current folder to store log files.
	 * @var string
	 * @access private
	 */
	private $currentLogFolder;

	/**
	 * Show memory usage in log/cli out?
	 * @var bool
	 * @access private
	 */
	private $showMemoryUsage;

	/**
	 * Show CPU load in log/cli out?
	 * @var bool
	 * @access private
	 */
	private $showCPULoad;

	/**
	 * Show running time of script on log/cli out?
	 * @var bool
	 * @access private
	 */
	private $showRunningTime;

	/**
	 * Show resource usages on log/cli out?.
	 * @var bool
	 * @access private
	 */
	private $showResourceUsage;

	/**
	 * Constructor.
	 *
	 * @param array $options (Optional) Class instances.
	 *                       (Optional) Folder to store log files in.
	 *                       (Optional) Filename of log, must be alphanumeric (a-z 0-9) and contain no file extensions.
	 *
	 * @access public
	 * @throws LoggerException
	 */
	public function __construct(array $options = [])
	{
		if (!nZEDb_LOGGING && !nZEDb_DEBUG) {
			return;
		}

		$defaults = [
			'ColorCLI'    => null,
			'LogFolder'   => '',
			'LogFileName' => ''
		];
		$options += $defaults;

		$this->colorCLI = ($options['ColorCLI'] instanceof \ColorCLI ? $options['ColorCLI'] : new \ColorCLI());

		$this->getSettings();

		$this->currentLogFolder = (
			!empty($options['LogFolder'])
				? $options['LogFolder']
				: $this->currentLogFolder
		);

		$this->currentLogName = (
			!empty($options['LogFileName'])
				? $options['LogFileName']
				: $this->currentLogName
		) . '.log';

		$this->setLogFile();

		$this->outputCLI = (strtolower(PHP_SAPI) === 'cli');
		$this->isWindows = (strtolower(substr(PHP_OS, 0, 3)) === 'win');
		$this->timeStart = time();
	}

	/**
	 * Close the log file resource.
	 *
	 * @access public
	 */
	public function __destruct()
	{
		$this->closeFile();
	}

	/**
	 * Public method for logging and/or echoing log messages.
	 *
	 * @param string $class    The name of the class.
	 * @param string $method   The method this is coming from.
	 * @param string $message  The message to log/echo.
	 * @param int    $severity How severe is this message?
	 *               1 Fatal    - The program had to stop (exit).
	 *               2 Error    - Something went very wrong but we recovered.
	 *               3 Warning  - Not an error, but something we can probably fix.
	 *               4 Notice   - User errors - the user did not enable any groups for example.
	 *               5 Info     - General info, like we logged in to usenet for example.
	 *               6 Query    - Failed SQL queries. (the full query).
	 *
	 * @access public
	 */
	public function log($class, $method, $message, $severity)
	{
		// Check if echo debugging or logging is on.
		if (!nZEDb_DEBUG && !nZEDb_LOGGING) {
			return;
		}

		$this->severity = $severity;
		// Check the severity of the message, if disabled return, if enabled create part of the log message.
		if (!$this->checkSeverity()) {
			return;
		}

		$this->class = $class;
		$this->method = $method;
		$this->logMessage = $message;

		$this->formLogMessage();
		$this->echoMessage();
		$this->logMessage();
	}

	/**
	 * Return current/peak memory usage or difference between current/peak memory usage and previous usage.
	 *
	 * @param int  $oldUsage  Output from a previous memory_get_usage().
	 * @param bool $realUsage Use (true)system memory usage or (false)emalloc() usage, use emalloc() for debugging.
	 * @param bool $peak      Get peak memory usage.
	 *
	 * @return string
	 *
	 * @access public
	 */
	public function showMemUsage($oldUsage = 0, $realUsage = false, $peak = false)
	{
		$currentUsage = ($peak ? memory_get_peak_usage($realUsage)  : memory_get_usage($realUsage));
		$actualUsage = ($oldUsage > 0 ? $currentUsage - $oldUsage : $currentUsage);

		$units = [
			'B ',
			'KB',
			'MB',
			'GB',
			'TB',
			'PB'
		];
		return
			str_pad(
				number_format(
					round(
						$actualUsage
						/
						pow(
							1024,
							($i =
								floor(
									log(
										$actualUsage,
										1024
									)
								)
							)
						), 2
					)
				), 4, '~~~', STR_PAD_LEFT
			) .
			$units[(int)$i];
	}

	/**
	 * Get resource usage string.
	 *
	 * @return bool|string
	 *
	 * @access public
	 */
	public function getResUsage()
	{
		if (!$this->isWindows) {
			$usage = getrusage();

			return
				'USR: '  . $this->formatTimeString($usage['ru_utime.tv_sec']) .
				' SYS: ' . $this->formatTimeString($usage['ru_stime.tv_sec']) .
				' FAULTS: ' . $usage['ru_majflt'] .
				' SWAPS: ' . $usage['ru_nswap'];
		}
		return false;
	}

	/**
	 * Get system load.
	 *
	 * @return string|bool
	 *
	 * @access public
	 */
	public function getSystemLoad()
	{
		if (!$this->isWindows) {
			$string = '';
			// Fix for single digits (2) or single float (2.1).
			foreach(sys_getloadavg() as $load) {
				$strLen = strlen($load);
				if ($strLen === 1) {
					$string .= $load . '.00,';
				} elseif ($strLen === 3) {
					$string .= str_pad($load, 4, '0', STR_PAD_RIGHT) . ',';
				} else {
					$string .= $load . ',';
				}
			}
			return substr($string, 0, -1);
		}
		return false;
	}

	/**
	 * Changes the location of the log file.
	 *
	 * @param string $folder   Folder where the log should be stored.
	 * @param string $fileName Name of the file (must be alphanumeric and contain no file extensions).
	 * @access public
	 */
	public function changeLogFileLocation($folder, $fileName)
	{
		$this->currentLogFolder = $folder;
		$this->currentLogName = $fileName;
		$this->setLogFile();
	}

	/**
	 * Get the log folder, log name and full path to the default log.
	 *
	 * @return array
	 * @access public
	 * @static
	 */
	static public function getDefaultLogPaths()
	{
		$defaultLogName = (defined('nZEDb_LOGGING_LOG_NAME') ? nZEDb_LOGGING_LOG_NAME : 'nzedb');
		$defaultLogName = (ctype_alnum($defaultLogName) ? $defaultLogName : 'nzedb');
		$defaultLogFolder = (defined('nZEDb_LOGGING_LOG_FOLDER') && is_dir(nZEDb_LOGGING_LOG_FOLDER) ? nZEDb_LOGGING_LOG_FOLDER : nZEDb_LOGS);
		$defaultLogFolder = (in_array(substr($defaultLogFolder, -1), ['/', '\\']) ? $defaultLogFolder : $defaultLogFolder . DS);
		return [
			'LogFolder' => $defaultLogFolder,
			'LogName'   => $defaultLogName,
			'LogPath'   => $defaultLogFolder . $defaultLogName . '.log'
		];
	}

	/**
	 * Sets the path and name for the log file.
	 *
	 * @throws LoggerException
	 * @access private
	 */
	private function setLogFile()
	{
		// Only run this if nZEDb_LOGGING is on.
		if (!nZEDb_LOGGING) {
			return;
		}

		$this->closeFile();

		$this->logPath = $this->currentLogFolder . $this->currentLogName;

		if (!is_dir($this->currentLogFolder)) {
			$this->createFolder();
		}

		$this->initiateLog();

		// Check if we need to rotate the log if it exceeds max size..
		$this->rotateLog();

		$this->openFile();
	}

	/**
	 * Get/set all settings.
	 * @access private
	 */
	private function getSettings()
	{
		$this->maxLogs = (defined('nZEDb_LOGGING_MAX_LOGS') ? nZEDb_LOGGING_MAX_LOGS : 20);
		$this->maxLogs = ($this->maxLogs < 1 ? 20 : $this->maxLogs);
		$this->maxLogSize = (defined('nZEDb_LOGGING_MAX_SIZE') ? nZEDb_LOGGING_MAX_SIZE : 30);
		$this->maxLogSize = ($this->maxLogSize < 1 ? 30 : $this->maxLogSize);
		$this->showMemoryUsage = (bool)(defined('nZEDb_LOGGING_LOG_MEMORY_USAGE') ? nZEDb_LOGGING_LOG_MEMORY_USAGE : true);
		$this->showCPULoad = (bool)(defined('nZEDb_LOGGING_LOG_CPU_LOAD') ? nZEDb_LOGGING_LOG_CPU_LOAD : true);
		$this->showRunningTime = (bool)(defined('nZEDb_LOGGING_LOG_RUNNING_TIME') ? nZEDb_LOGGING_LOG_RUNNING_TIME : true);
		$this->showResourceUsage = (bool)(defined('nZEDb_LOGGING_LOG_RESOURCE_USAGE') ? nZEDb_LOGGING_LOG_RESOURCE_USAGE : false);
		$paths = self::getDefaultLogPaths();
		$this->currentLogName = $paths['LogName'];
		$this->currentLogFolder = $paths['LogFolder'];
	}

	/**
	 * Close the file resource.
	 *
	 * @access private
	 */
	private function closeFile()
	{
		if (is_resource($this->resource)) {
			@fclose($this->resource);
		}
		$this->resource = null;
	}

	/**
	 * Opens the log file.
	 *
	 * @throws LoggerException
	 */
	private function openFile()
	{
		if (!is_resource($this->resource)) {
			$this->resource = @fopen($this->logPath, 'ab');

			if (!$this->resource) {
				throw new \LoggerException('Unable to open log file ' . $this->logPath);
			}
		}
	}

	/**
	 * Log message to file.
	 *
	 * @access private
	 */
	private function logMessage()
	{
		// Check if debug logging is on.
		if (!nZEDb_LOGGING) {
			return;
		}

		clearstatcache(true, $this->logPath);

		// Check if we should rotate the logs.
		$this->rotateLog();

		// If another process deleted the file, try to re-open it.
		if (!is_file($this->logPath)) {
			$this->setLogFile();
		}

		@fwrite($this->resource, $this->logMessage . PHP_EOL);
	}

	/**
	 * Get the date and cache it.
	 *
	 * @return string
	 *
	 * @access private
	 */
	private function getDate()
	{
		// Cache the date, update it every 1 minute, since date() is extremely slow and time() is extremely fast.
		if ($this->dateCache === '' || $this->timeCache < (time() - 60)) {
			$this->dateCache = date('d/M/Y H:i');
			$this->timeCache = time();
		}

		return $this->dateCache;
	}

	/**
	 * Check if the log folder exists, if not create it.
	 *
	 * @access private
	 * @throws LoggerException
	 */
	private function createFolder()
	{
		// Check if the log folder exists, create it if not.
		if (!is_dir($this->currentLogFolder)) {
			$old = umask(0777);
			if (!mkdir($this->currentLogFolder)) {
				throw new \LoggerException('Unable to create log file folder ' . $this->currentLogFolder);
			}
			chmod($this->currentLogFolder, 0777);
			umask($old);
		}
	}

	/**
	 * Initiate a log file.
	 *
	 * @access private
	 * @throws LoggerException
	 */
	private function initiateLog()
	{
		if (!is_file($this->logPath)) {
			if (!file_put_contents(
					$this->logPath,
					'[' . $this->getDate() . '] [INIT]   [Initiating new log file.]' . PHP_EOL)
			) {
				throw new \LoggerException('Unable to create new log file ' . $this->logPath);
			}
		}
	}

	/**
	 * Rotate log file if it exceeds a certain size.
	 *
	 * @access private
	 */
	private function rotateLog()
	{
		// Check if we need to rotate the log if it exceeds max size..
		$logSize = filesize($this->logPath);
		if ($logSize === false) {
			return;
		} else if ($logSize >= ($this->maxLogSize * 1024 * 1024)) {
			$this->closeFile();
			$this->compressLog();
			$this->initiateLog();
			$this->pruneLogs();
			$this->openFile();
		}
	}

	/**
	 * Compress the old log using GZip.
	 *
	 * @access private
	 */
	private function compressLog()
	{
		$handle = @fopen($this->logPath, 'rb');
		$zipHandle = @gzopen(str_replace('.log', '', $this->logPath) . '.' . time() . '.gz', 'w6');
		if (!$handle || !$zipHandle) {
			return;
		}

		while (!feof($handle)) {
			$data = @fread($handle, 32768);
			@gzwrite($zipHandle, $data);
		}

		@fclose($handle);
		@gzclose($zipHandle);

		// Delete the original uncompressed log file.
		unlink($this->logPath);
	}

	/**
	 * Delete old logs (if we have more than $this->maxLogs).
	 *
	 * @access private
	 */
	private function pruneLogs()
	{
		// Get all the logs with the name.
		$logs = glob(str_replace('.log', '', $this->logPath) . '.[0-9]*.gz');

		// If there are no old logs or less than maxLogs return false.
		if (!$logs || (count($logs) < $this->maxLogs)) {
			return;
		}

		// Sort the logs alphabetically, so the oldest ones are at the top, the new at the bottom.
		asort($logs);

		// Remove all new logs from array (all elements under the last 51 elements of the array).
		array_splice($logs, -$this->maxLogs+1);

		// Delete all the logs left in the array.
		array_map('unlink', $logs);
	}

	/**
	 * Echo log message to CLI or web.
	 *
	 * @access private
	 */
	private function echoMessage()
	{
		if (!nZEDb_DEBUG) {
			return;
		}

		// Check if this is CLI or web.
		if ($this->outputCLI) {
			echo $this->colorCLI->debug($this->logMessage);
		} else {
			echo '<pre>' . $this->logMessage . '</pre><br />';
		}
	}

	/**
	 * Creates the message object for the log message.
	 *
	 * @access private
	 */
	private function formLogMessage()
	{
		$pid = getmypid();

		$this->logMessage =
			// Current date/time ; [02/Mar/2014 14:50 EST
			'[' . $this->getDate() . '] ' .

			// The severity.
			$this->severity .

			// Average system load.
			(($this->showCPULoad && !$this->isWindows) ? ' [' . $this->getSystemLoad() . ']' : '') .

			// Script running time.
			($this->showRunningTime ? ' [' . $this->formatTimeString(time() - $this->timeStart) . ']' : '') .

			// PHP memory usage.
			($this->showMemoryUsage ? ' [MEM:' . $this->showMemUsage(0, true) . ']' : '') .

			// Resource usage (user time, system time, major page faults, memory swaps).
			(($this->showResourceUsage && !$this->isWindows) ? ' [' . $this->getResUsage() . ']' : '') .

			// Running process ID.
			($pid ? ' [PID:' . $pid . ']' : '') .

			// The class/function.
			' [' . $this->class . '.' . $this->method . ']' .

			' [' .

			// Now reformat the log message, first stripping leading spaces.
			trim(

				// Removing 2 or more spaces.
				preg_replace('/\s{2,}/', ' ',

					// Removing new lines and carriage returns.
					str_replace(["\n", '\n', "\r", '\r'], ' ', $this->logMessage)
				)
			) .

			']';
	}

	/**
	 * Convert seconds to hours minutes seconds string.
	 *
	 * @param int $seconds
	 *
	 * @return string
	 *
	 * @access private
	 */
	private function formatTimeString($seconds)
	{
		$time = '';
		if ($seconds > 3600) {
			$time .= str_pad(round((($seconds % 86400) / 3600)), 2, '0', STR_PAD_LEFT) . 'H:';
		} else {
			$time .= '00H:';
		}
		if ($seconds > 60) {
			$time .= str_pad(round((($seconds % 3600) / 60)), 2 , '0', STR_PAD_LEFT) . 'M:';
		} else {
			$time .= '00M:';
		}
		$time .= str_pad($seconds % 60, 2 , '0', STR_PAD_LEFT) . 'S';
		return $time;
	}

	/**
	 * Check if the user wants to echo or log this message, form part of the log message at the same time.
	 *
	 * @return bool
	 *
	 * @access private
	 */
	private function checkSeverity()
	{
		switch ($this->severity) {
			case self::LOG_FATAL:
				if (nZEDb_LOGFATAL) {
					$this->severity = '[FATAL] ';
					return true;
				}
				return false;
			case self::LOG_ERROR:
				if (nZEDb_LOGERROR) {
					$this->severity = '[ERROR] ';
					return true;
				}
				return false;
			case self::LOG_WARNING:
				if (nZEDb_LOGWARNING) {
					$this->severity = '[WARN]  ';
					return true;
				}
				return false;
			case self::LOG_NOTICE:
				if (nZEDb_LOGNOTICE) {
					$this->severity = '[NOTICE]';
					return true;
				}
				return false;
			case self::LOG_INFO:
				if (nZEDb_LOGINFO) {
					$this->severity = '[INFO]  ';
					return true;
				}
				return false;
			case self::LOG_SQL:
				if (nZEDb_LOGQUERIES) {
					$this->severity = '[SQL]   ';
					return true;
				}
				return false;
			default:
				return false;
		}
	}

}

class LoggerException extends Exception {}
