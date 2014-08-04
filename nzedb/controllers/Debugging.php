<?php
/**
 * Show debug to CLI/Web and log it to a file.
 * Turn these on in automated.config.php
 *
 * @example usage:
 *          (in constructor, initiate instance)
 *          $this->Debugging = new Debugging(['Class' => 'ClassName']);
 *
 *          (in method, DEBUG_INFO would be the severity of your error, see below)
 *          $this->Debugging->start("MyMethodName", "My debug message.", DEBUG_INFO);
 */
class Debugging
{
	/**
	 * How many old logs can we have max in the logs folder.
	 * (per log type, ex.: debug can have 50 logs, not_yEnc can have 50 logs, etc)
	 *
	 * @default 50
	 *
	 * @const int
	 * @access public
	 */
	const maxLogs = 50;

	/**
	 * Max log size in MegaBytes
	 *
	 * @default 4
	 *
	 * @const int
	 * @access public
	 */
	const logFileSize = 4;

	/**
	 * Extension for log files.
	 *
	 * @default .log
	 *
	 * @const string
	 * @access public
	 */
	const logFileExtension = '.log';

	/**
	 * Base name of debug log file.
	 *
	 * @default debug
	 *
	 * @const string
	 * @access public
	 */
	const debugLogName = 'debug';

	/**
	 * Show memory usage in log/cli out?
	 *
	 * @default true
	 *
	 * @const bool
	 * @access public
	 */
	const showMemoryUsage = true;

	/**
	 * Show average load in log/cli out?
	 *
	 * @note Does not work in windows.
	 *
	 * @default true
	 *
	 * @const bool
	 * @access public
	 */
	const showAverageLoad = true;

	/**
	 * Show running time of script on log/cli out?
	 *
	 * @default true
	 *
	 * @const bool
	 * @access public
	 */
	const showTimeRunning = true;

	/**
	 * Show resource usages.
	 *
	 * @default false
	 *
	 * @const bool
	 * @access public
	 */
	const showGetResUsage = false;

	/**
	 * Name of class that created an instance of debugging.
	 * @var string
	 * @access private
	 */
	private $class;

	/**
	 * The debug message.
	 * @var string
	 * @access private
	 */
	private $debugMessage = '';

	/**
	 * Severity level.
	 * @var string
	 * @access private
	 */
	private $severity = '';

	/**
	 * "\n" for unix, "\r\n" for windows.
	 * @var string
	 * @access private
	 */
	private $newLine;

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

	// You can use these constants when using the start method.
	const DEBUG_FATAL   = 1; // Fatal error, the program exited.
	const DEBUG_ERROR   = 2; // Recoverable error.
	const DEBUG_WARNING = 3; // Warnings.
	const DEBUG_NOTICE  = 4; // Notices.
	const DEBUG_INFO    = 5; // Info message, not important.
	const DEBUG_SQL     = 6; // Full SQL query when it fails.

	/**
	 * Constructor.
	 *
	 * @param array $options Class instances / Name of the class to debug.
	 * ex: new Debugging(['Class' => 'Binaries']);
	 *
	 * @access public
	 */
	public function __construct(array $options = array())
	{
		$defOptions = [
			'Class'    => '',
			'ColorCLI' => null
		];
		$defOptions = array_replace($defOptions, $options);

		$this->class = $defOptions['Class'];
		$this->colorCLI = ($defOptions['ColorCLI'] instanceof ColorCLI ? $defOptions['ColorCLI'] : new ColorCLI());

		$this->newLine = PHP_EOL;
		$this->outputCLI = (strtolower(PHP_SAPI) === 'cli');
		$this->isWindows = (strtolower(substr(PHP_OS, 0, 3)) === 'win');
		$this->timeStart = time();
	}

	/**
	 * Public method for logging and/or echoing debug messages.
	 *
	 * @param string $method   The method this is coming from.
	 * @param string $message  The message to log/echo.
	 * @param int    $severity How severe is this message?
	 *               1 Fatal    - The program had to stop (exit).
	 *               2 Error    - Something went very wrong but we recovered.
	 *               3 Warning  - Not an error, but something we can probably fix.
	 *               4 Notice   - User errors - the user did not enable any groups for example.
	 *               5 Info     - General info, like we logged in to usenet for example.
	 *               6 Query    - Failed SQL queries. (the full query).
	 *               Anything else causes the script to return void.
	 *
	 * @return void
	 *
	 * @access public
	 */
	public function start($method, $message, $severity)
	{
		// Check if echo debugging or logging is on.
		if (!nZEDb_DEBUG && !nZEDb_LOGGING) {
			return;
		}

		// Check the severity of the message, if disabled return, if enabled create part of the debug message.
		if (!$this->checkSeverity($severity)) {
			return;
		}

		// Form the debug message.
		$this->formMessage($method, $message);

		// Echo debug message if user enabled it.
		$this->echoDebug();

		// Log debug message to file if user enabled it.
		$this->logDebug();
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
	 * Base method for logging to files.
	 *
	 * @param string $path Path where all the log files are. ex.: .../nZEDb/resources/logs/
	 * @param string $name The name of the log type.         ex.: debug
	 * @param string $message The message to log.
	 *
	 * @return bool
	 *
	 * @access protected
	 */
	protected function logMain($path, $name, $message)
	{
		// Check if the log folder exists, create it if not.
		if (!$this->createFolder($path)) {
			return false;
		}

		// Check if we need to initiate a new log if we don't have one.
		if (!$this->initiateLog($path, $name)) {
			return false;
		}

		// Check if we need to rotate the log if it exceeds max size..
		if (!$this->rotateLog($path, $name)) {
			return false;
		}

		// Append the message to the log.
		if (!file_put_contents($path . $name . self::logFileExtension, $message . $this->newLine, FILE_APPEND)) {
// Error appending message to log file.
			return false;
		}
		return true;
	}

	/**
	 * Log debug message to file.
	 *
	 * @return bool
	 *
	 * @access protected
	 */
	protected function logDebug()
	{
		// Check if debug logging is on.
		if (!nZEDb_LOGGING) {
			return false;
		}

		if (!$this->logMain(nZEDb_LOGS, self::debugLogName, $this->debugMessage)) {
			return false;
		}
		return true;
	}

	/**
	 * Get the date and cache it.
	 *
	 * @return string
	 *
	 * @access protected
	 */
	protected function getDate()
	{
		// Cache the date, update it every 1 minute, since date() is extremely slow and time() is extremely fast.
		if ($this->dateCache === '' || $this->timeCache < (time() - 60)) {
			$this->dateCache = date('d/M/Y H:i');
			$this->timeCache = time();
		}

		return $this->dateCache;
	}

	/**
	 * Check if a folder exists, if not create it.
	 *
	 * @param string $path Path where all the log files are. ex.: .../nZEDb/resources/logs/
	 *
	 * @return bool
	 *
	 * @access protected
	 */
	protected function createFolder($path)
	{
		// Check if the log folder exists, create it if not.
		if (!is_dir($path)) {
			if (!mkdir($path)) {
// Error creating log folder.
				return false;
			}
		}

		return true;
	}

	/**
	 * Initiate a log file.
	 *
	 * @param string $path Path where all the log files are. ex.: .../nZEDb/resources/logs/
	 * @param string $name The name of the log type.         ex.: debug
	 *
	 * @return bool
	 *
	 * @access protected
	 */
	protected function initiateLog($path, $name)
	{
		if (!file_exists($path . $name . self::logFileExtension)) {
			if (!file_put_contents(
					$path . $name . self::logFileExtension,
					'[' . $this->getDate() . '] [INIT]   [Initiating new log file.]' . $this->newLine)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Rotate log file if it exceeds a certain size.
	 *
	 * @param string $path Path where all the log files are. ex.: .../nZEDb/resources/logs/
	 * @param string $name The name of the log type.         ex.: debug
	 *
	 * @return bool
	 *
	 * @access protected
	 */
	protected function rotateLog($path, $name)
	{
		$file = $path . $name . self::logFileExtension;

		// Check if we need to rotate the log if it exceeds max size..
		$logSize = filesize($file);
		if ($logSize === false) {
// Error getting log size.
			return false;
		} else if ($logSize >= (self::logFileSize * 1024 * 1024)) {
			if (!$this->compressLog($path, $name)) {
// Error compressing/renaming old log.
				return false;
			}

			// Create a new log.
			if (!$this->initiateLog($path, $name)) {
// Error creating new log file.
				return false;
			}

			// Delete old logs.
			$this->pruneLogs($path, $name);
		}
		return true;
	}

	/**
	 * Compress the old log using GZip.
	 *
	 * @param string $path Path where all the log files are. ex.: .../nZEDb/resources/logs/
	 * @param string $name The name of the log type.         ex.: debug
	 *
	 * @return bool
	 *
	 * @access protected
	 */
	protected function compressLog($path, $name)
	{
		$file = $path . $name . self::logFileExtension;

		// Get the log as a string.
		$log = file_get_contents($file);
		if (!$log) {
// Error reading log file.
			return false;
		}

		// Create an empty gz file.
		$gz = gzopen($path . $name . '.' . time() . '.gz', 'w6');
		if (!$gz) {
// Error creating gz file.
			return false;
		}

		// Write the log's data into the gz file.
		gzwrite($gz, $log);

		// Close the gz file.
		if (!gzclose($gz)) {
// Error closing gz file.
			return false;
		}

		// Delete the original uncompressed log file.
		return unlink($file);
	}

	/**
	 * Delete old logs.
	 *
	 * @param string $path Path where all the log files are. ex.: .../nZEDb/resources/logs/
	 * @param string $name The name of the log type.         ex.: debug
	 *
	 * @return bool
	 *
	 * @access protected
	 */
	protected function pruneLogs($path, $name)
	{
		// Get all the logs with the name.
		$logs = glob($path . $name . '.[0-9]*.gz');

		// If there are no old logs or less than maxLogs return false.
		if (!$logs || (count($logs) < self::maxLogs)) {
			return false;
		}

		// Sort the logs alphabetically, so the oldest ones are at the top, the new at the bottom.
		asort($logs);

		// Remove all new logs from array (all elements under the last 51 elements of the array).
		array_splice($logs, -self::maxLogs+1);

		// Delete all the logs left in the array.
		array_map('unlink', $logs);

		return true;
	}

	/**
	 * Echo debug message to CLI or web.
	 *
	 * @return void
	 *
	 * @access protected
	 */
	protected  function echoDebug()
	{
		if (!nZEDb_DEBUG) {
			return;
		}

		// Check if this is CLI or web.
		if ($this->outputCLI) {
			echo $this->colorCLI->debug($this->debugMessage);
		} else {
			echo '<pre>' . $this->debugMessage . '</pre><br />';
		}
	}

	/**
	 * Sets the message object to the debug message,
	 *
	 * @param string $method  The function this debug message came from.
	 * @param string $message The actual debug message.
	 *
	 * @return void
	 *
	 * @access protected
	 */
	protected function formMessage($method, $message)
	{
		$pid = getmypid();

		$this->debugMessage =
			// Current date/time ; [02/Mar/2014 14:50 EST
			'[' . $this->getDate() . '] ' .

			// The severity.
			$this->severity .

			// Average system load.
			((self::showAverageLoad && !$this->isWindows) ? ' [' . $this->getSystemLoad() . ']' : '') .

			// Script running time.
			(self::showTimeRunning ? ' [' . $this->formatTimeString(time() - $this->timeStart) . ']' : '') .

			// PHP memory usage.
			(self::showMemoryUsage ? ' [MEM:' . $this->showMemUsage(0, true) . ']' : '') .

			// Resource usage (user time, system time, major page faults, memory swaps).
			((self::showGetResUsage && !$this->isWindows) ? ' [' . $this->getResUsage() . ']' : '') .

			// Running process ID.
			($pid ? ' [PID:' . $pid . ']' : '') .

			// The class/function.
			' [' . $this->class . '.' . $method . ']' .

			' [' .

			// Now reformat the debug message, first stripping leading spaces.
			trim(

				// Removing 2 or more spaces.
				preg_replace('/\s{2,}/', ' ',

					// Removing new lines and carriage returns.
					str_replace(array("\n", '\n', "\r", '\r'), ' ', $message)
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
	 * @access protected
	 */
	protected function formatTimeString($seconds)
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
	 * Check if the user wants to echo or log this message, form part of the debug message at the same time.
	 *
	 * @param int $severity How severe is this debug message?
	 *
	 * @return bool
	 *
	 * @access protected
	 */
	protected function checkSeverity($severity)
	{
		switch ($severity) {
			case self::DEBUG_FATAL:
				if (nZEDb_LOGFATAL) {
					$this->severity = '[FATAL] ';
					return true;
				}
				return false;
			case self::DEBUG_ERROR:
				if (nZEDb_LOGERROR) {
					$this->severity = '[ERROR] ';
					return true;
				}
				return false;
			case self::DEBUG_WARNING:
				if (nZEDb_LOGWARNING) {
					$this->severity = '[WARN]  ';
					return true;
				}
				return false;
			case self::DEBUG_NOTICE:
				if (nZEDb_LOGNOTICE) {
					$this->severity = '[NOTICE]';
					return true;
				}
				return false;
			case self::DEBUG_INFO:
				if (nZEDb_LOGINFO) {
					$this->severity = '[INFO]  ';
					return true;
				}
				return false;
			case self::DEBUG_SQL:
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