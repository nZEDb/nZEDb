<?php
/**
 * Show debug to CLI/Web and log it to a file.
 * Turn these on in automated.config.php
 */
class Debugging
{
	/**
	 * How many old logs can we have max in the logs folder.
	 * (per log type, ex.: debug can have 50 logs, not_yenc can have 50 logs, etc)
	 *
	 * @default 50
	 *
	 * @const int
	 */
	const maxLogs = 50;

	/**
	 * Max log size in KiloBytes
	 *
	 * @default 1024
	 *
	 * @const int
	 */
	const logFileSize = 1024;

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
	private $message = '';

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
	 *
	 * @var bool
	 */
	private $outputCLI = true;

	/**
	 * Constructor.
	 * @param string $class The name of the class. ex,: $d = new Debugging("Binaries");
	 */
	public function __construct($class="")
	{
		$this->class = $class;
		$this->colorCLI = new ColorCLI();
		$this->newLine = (strtolower(substr(php_uname('s'), 0, 3)) === 'win') ? "\r\n" : "\n";
		$this->outputCLI = (strtolower(PHP_SAPI) === 'cli') ? true : false;
	}

	/**
	 * Public method for logging and/or echoing debug messages.
	 *
	 * @param string $method   The method this is coming from.
	 * @param string $message  The message to log/echo.
	 * @param int    $severity How severe is this message?
	 *               1 Fatal   - The program had to stop (exit).
	 *               2 Error   - Something went very wrong but we recovered.
	 *               3 Warning - Not an error, but something we can probably fix.
	 *               4 Notice  - User errors - the user did not enable any groups for example.
	 *               5 Info    - General info, like we logged in to usenet for example.
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

		// Reset debug message.
		$this->message = '';
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

		// Path to folder where log files are stored..
		$path = nZEDb_LOGS;

		// Check if the log folder exists, create it if not.
		if (!is_dir($path)) {
			if (!mkdir($path)) {
// Error creating log folder.
				return false;
			}
		}

		// Full path to the log file.
		$fileLocation = $path . self::debugLogName . '.log';

		// Check if we need to initiate a new log if we don't have one.
		if (!$this->initiateLog($fileLocation)) {
			return false;
		}

		// Check if we need to rotate the log if it exceeds max size..
		if (!$this->rotateLog($fileLocation)) {
			return false;
		}

		// Append the message to the log.
		if (!file_put_contents($fileLocation, $this->message . $this->newLine, FILE_APPEND)) {
// Error appending message to log file.
			return false;
		}
		return true;
	}

	/**
	 * Initiate a log file.
	 *
	 * @param string $path The full path to the log file.
	 *
	 * @return bool
	 */
	protected function initiateLog($path)
	{
		if (!file_exists($path)) {
			if (!file_put_contents($path, '[' . Date('r') . '] [INITIATE] [Initiating new log file.]' . $this->newLine)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Rotate log file if it exceeds a certain size.
	 *
	 * @param $fullPath /The path to the file (/var/www/nZEDb/resources/logs/debug.log) MUST END IN .log or .txt
	 *
	 * @return bool
	 */
	protected function rotateLog($fullPath)
	{
		// Check if we need to rotate the log if it exceeds max size..
		$logSize = filesize($fullPath);
		if ($logSize === false) {
// Error getting log size.
			return false;
		} else if ($logSize >= (self::logFileSize * 1024)) {
			if (!rename($fullPath, str_replace(array('.log', '.txt'), '.old.', $fullPath) . time())) {
// Error renaming log.
				return false;
			}

			// Create a new log.
			if (!$this->initiateLog($fullPath)) {
// Error creating log file.
				return false;
			}

			$this->pruneLogs(nZEDb_LOGS, self::debugLogName);
		}
		return true;
	}

	/**
	 * Delete old logs.
	 *
	 * @param string $path Path where all the log files are.        ex.: /var/www/nZEDb/resources/logs/
	 * @param string $name The name of the name without extensions. ex.: debug
	 *
	 * @return bool
	 */
	protected function pruneLogs($path, $name)
	{
		// Get all the logs with the name.
		$logs = glob($path . $name . '.old.[0-9]*');

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
			echo $this->colorCLI->debug($this->message);
		} else {
			echo '<pre>' . $this->message . '</pre>';
		}
	}

	/**
	 * Sets the message object to the debug message,
	 *
	 * @param string $class
	 * @param string $method
	 * @param string $message
	 *
	 * @return void
	 */
	protected function formMessage($method, $message)
	{
		$this->message =
			// Current time. RFC2822 style ; [Thu, 21 Dec 2000 16:01:07 +0200
			'[' . Date('r') .

			// The severity.
			$this->message .

			// The class/function.
			$this->class . '.' . $method . '() ' .

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
	 * @param int $severity
	 *
	 * @return bool
	 */
	protected function checkSeverity($severity)
	{
		$this->message = '';
		switch ($severity) {
			case 1:
				if (nZEDb_LOGFATAL) {
					$this->message = '] [FATAL]    [';
					return true;
				}
				return false;
			case 2:
				if (nZEDb_LOGERROR) {
					$this->message = '] [ERROR]    [';
					return true;
				}
				return false;
			case 3:
				if (nZEDb_LOGWARNING) {
					$this->message = '] [WARNING]  [';
					return true;
				}
				return false;
			case 4:
				if (nZEDb_LOGNOTICE) {
					$this->message = '] [NOTICE]   [';
					return true;
				}
				return false;
			case 5:
				if (nZEDb_LOGINFO) {
					$this->message = '] [INFO]     [';
					return true;
				}
				return false;
			default:
				return false;
		}
	}
}