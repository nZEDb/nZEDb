<?php
/**
 * Show debug to CLI/Web and log it to a file.
 * Turn these on in automated.config.php
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
	 */
	const maxLogs = 50;

	/**
	 * Max log size in MegaBytes
	 *
	 * @default 4
	 *
	 * @const int
	 */
	const logFileSize = 4;

	/**
	 * Extension for log files.
	 *
	 * @default .log
	 *
	 * @const string
	 */
	const logFileExtension = '.log';

	/**
	 * Base name of debug log file.
	 *
	 * @default debug
	 *
	 * @const string
	 */
	const debugLogName = 'debug';

	/**
	 * Name of class that created an instance of debugging.
	 * @var string
	 */
	private $class;

	/**
	 * The debug message.
	 * @var string
	 */
	private $debugMessage = '';

	/**
	 * "\n" for unix, "\r\n" for windows.
	 * @var string
	 */
	private $newLine;

	/**
	 * Class instance of colorCLI
	 * @var object
	 */
	private $colorCLI;

	/**
	 * Should we echo to CLI or web?
	 * @var bool
	 */
	private $outputCLI = true;

	/**
	 * Cache of the date.
	 * @var string
	 */
	private $dateCache = '';

	/**
	 * Cache of unix time.
	 * @var int
	 */
	private $timeCache;

	/**
	 * Constructor.
	 *
	 * @param string $class The name of the class. ex,: $d = new Debugging("Binaries");
	 */
	public function __construct($class="")
	{
		$this->class = $class;
		$this->colorCLI = new ColorCLI();
		$this->newLine = PHP_EOL;
		$this->outputCLI = (strtolower(PHP_SAPI) === 'cli') ? true : false;
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
	 * Base method for logging to files.
	 *
	 * @param string $path Path where all the log files are. ex.: .../nZEDb/resources/logs/
	 * @param string $name The name of the log type.         ex.: debug
	 * @param string $message The message to log.
	 *
	 * @return bool
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
	 */
	protected function getDate()
	{
		// Cache the date, update it every 1 minute, since date() is extremely slow and time() is extremely fast.
		if ($this->dateCache === '' || $this->timeCache < (time() - 60)) {
			$this->dateCache = $this->formDate();
			$this->timeCache = time();
		}

		return $this->dateCache;
	}

	/**
	 * Form a date in this format: 02/Mar/2014 14:50 EST
	 *
	 * @return string
	 */
	protected  function formDate()
	{
		return date('d/M/Y H:i T');
	}

	/**
	 * Check if a folder exists, if not create it.
	 *
	 * @param string $path Path where all the log files are. ex.: .../nZEDb/resources/logs/
	 *
	 * @return bool
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

		// Delete the original log file.
		return unlink($file);
	}

	/**
	 * Delete old logs.
	 *
	 * @param string $path Path where all the log files are. ex.: .../nZEDb/resources/logs/
	 * @param string $name The name of the log type.         ex.: debug
	 *
	 * @return bool
	 */
	protected function pruneLogs($path, $name)
	{
		// Get all the logs with the name.
		$logs = glob($path . $name . '.[0-9]*.gz');

		// If there are no old logs or less than maxLogs return false.
		if (!$logs || count($logs) < self::maxLogs) {
			return false;
		}

		// Sort the logs alphabetically.
		asort($logs);

		// Remove all old logs.
		array_splice($logs, -self::maxLogs+1);

		// Delete the logs.
		array_map('unlink', $logs);

		return true;
	}

	/**
	 * Echo debug message to CLI or web.
	 *
	 * @return void
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
			echo '<pre>' . $this->debugMessage . '</pre>';
		}
	}

	/**
	 * Sets the message object to the debug message,
	 *
	 * @param string $method  The function this debug message came from.
	 * @param string $message The actual debug message.
	 *
	 * @return void
	 */
	protected function formMessage($method, $message)
	{
		$this->debugMessage =
			// Current date/time ; [02/Mar/2014 14:50 EST
			'[' . $this->getDate() .

			// The severity.
			$this->debugMessage .

			// The class/function.
			$this->class . '.' . $method . '] [' .

			// Now reformat the debug message, first stripping leading spaces.
			trim(

				// Removing 2 or more spaces.
				preg_replace('/\s{2,}/', ' ',

					// Removing new lines and carriage returns.
					str_replace(array("\n", '\n', "\r", '\r'), ' ', $message)))

			// Finally, add a closing brace.
			. ']';
	}

	/**
	 * Check if the user wants to echo or log this message, form part of the debug message at the same time.
	 *
	 * @param int $severity How severe is this debug message?
	 *
	 * @return bool
	 */
	protected function checkSeverity($severity)
	{
		$this->debugMessage = '';
		switch ($severity) {
			case 1:
				if (nZEDb_LOGFATAL) {
					$this->debugMessage = '] [FATAL]  [';
					return true;
				}
				return false;
			case 2:
				if (nZEDb_LOGERROR) {
					$this->debugMessage = '] [ERROR]  [';
					return true;
				}
				return false;
			case 3:
				if (nZEDb_LOGWARNING) {
					$this->debugMessage = '] [WARN]   [';
					return true;
				}
				return false;
			case 4:
				if (nZEDb_LOGNOTICE) {
					$this->debugMessage = '] [NOTICE] [';
					return true;
				}
				return false;
			case 5:
				if (nZEDb_LOGINFO) {
					$this->debugMessage = '] [INFO]   [';
					return true;
				}
				return false;
			case 6:
				if (nZEDb_LOGQUERIES) {
					$this->debugMessage = '] [SQL]    [';
					return true;
				}
				return false;
			default:
				return false;
		}
	}
}
