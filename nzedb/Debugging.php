<?php
/**
 * Show debug to CLI/Web and log it to a file.
 * Turn these on in automated.config.php
 */
class Debugging
{
	/**
	 * Max log size in KiloBytes
	 *
	 * @default 512
	 *
	 * @const int
	 */
	const logFileSize = 512;

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
	 */
	public function __construct()
	{
		$this->colorCLI = new ColorCLI();
		$this->newLine = (strtolower(substr(php_uname('s'), 0, 3)) === 'win') ? "\r\n" : "\n";
		$this->outputCLI = (strtolower(PHP_SAPI) === 'cli') ? true : false;
	}

	/*
	 * @param string $class    The class this is coming from.
	 * @param string $method   The method this is coming from.
	 * @param string $message  The message to log.
	 * @param int    $severity How severe is this message?
	 *               1 Fatal   - The program had to stop.
	 *               2 Error   - Something went very wrong but we recovered.
	 *               3 Warning - Not an error, but something we can probably fix.
	 *               4 Notice  - Like warning but not as bad?
	 *               5 Info    - General info, like we logged in to usenet for example.
	 *               Anything else returns false.
	 *
	 * @return void
	 */
	public function start($class, $method, $message, $severity)
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
		$this->formMessage($class, $method, $message);

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
		$fileLocation = $path . 'debug.log';

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
	 * @param $path /The path to the file (/example/debug.log) MUST END IN .log or .txt
	 *
	 * @return bool
	 */
	protected function rotateLog($path)
	{
		// Check if we need to rotate the log if it exceeds max size..
		$logSize = filesize($path);
		if ($logSize === false) {
// Error getting log size.
			return false;
		} else if ($logSize >= (self::logFileSize * 1024)) {
			if (!rename($path, str_replace(array('.log', '.txt'), '.old.', $path) . time())) {
// Error renaming log.
				return false;
			}

			// Create a new log.
			if (!$this->initiateLog($path)) {
// Error creating log file.
				return false;
			}
		}
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
	protected function formMessage($class, $method, $message)
	{
		$this->message =
			// Current time. RFC2822 style ; [Thu, 21 Dec 2000 16:01:07 +0200
			'[' . Date('r') .

			// The severity.
			$this->message .

			// The class/function.
			$class . '.' . $method . '() ' .

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