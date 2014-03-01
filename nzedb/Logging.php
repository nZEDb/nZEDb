<?php
/**
 * Logs/Reports stuff
 */
class Logging
{
	////////////////////// START OF USER CHANGEABLE VARS ///////////////////
	////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////
	/**
	 * Turn on/off logging of debug messages.
	 *
	 * @default true
	 *
	 * @note You must turn on the site debug setting, otherwise logging will not occur regardless of this setting.
	 *
	 * @var bool $debugLogging
	 */
	private $debugLogging = true;

	/**
	 * Do you want to log all the types of debug messages?
	 * If set to false, change $debugLogLevel
	 *
	 * @default true
	 *
	 * @const bool
	 */
	const debugLogAll = true;

	/**
	 * What types of debug messages do you want to log, if $debugLogAll is set to false.
	 *
	 * @default 4
	 *
	 * 1 Info     (Events like connecting to usenet).
	 * 2 Notice   (Minor things like failed queries?).
	 * 3 Warning  (Wrong usage of api's, libraries, etc?)
	 * 4 Error    (Errors that can cause issues?)
	 * 5 Fatal    (This caused the program to close?).
	 *
	 * @const int
	 */
	const debugLogLevel = 4;

	/**
	 * Do you want to display the debug messages to the CLI?
	 *
	 * @default true
	 *
	 * @note Debugging must be on in site settings.
	 *
	 * @var bool
	 */
	private $debugCLI = true;

	////////////////////////////////////////////////////////////////////////
	////////////////////////////////////////////////////////////////////////
	////////////////////// END OF USER CHANGEABLE VARS /////////////////////

	/**
	 * Max log size in KiloBytes
	 *
	 * @default 512
	 *
	 * @const int
	 */
	const logFileSize = 512;

	/**
	 * Is site debug on?
	 *
	 * @var bool $siteDebug
	 */
	private $siteDebug;

	/**
	 * @var string If windows "\r\n" if unix "\n".
	 */
	private $newLine;

	/**
	 * @var object DB Class instance.
	 */
	private $db;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$site = new Sites();
		$this->site = $site->get();
		$this->db = new DB();

		$this->siteDebug = ($this->site->debuginfo == '0') ? false : true;
		if ($this->debugLogging) {
			$this->debugLogging = $this->siteDebug;
		}
		if ($this->debugCLI) {
			$this->debugCLI = $this->siteDebug;
		}

		$this->newLine = ((strtolower(substr(php_uname('s'), 0, 3)) === 'win') ? "\r\n" : "\n");
	}

	/**
	 * Get all rows from logging table.
	 *
	 * @return array
	 */
	public function get()
	{
		return $this->db->query('SELECT * FROM logging');
	}

	/**
	 * Log bad login attempts.
	 *
	 * @param string $username
	 * @param string $host
	 *
	 * @return void
	 */
	public function LogBadPasswd($username='', $host='')
	{
		$s = $this->site ;
		// If logggingopt is = 0, then we do nothing, 0 = logging off.
		if ($s->loggingopt == '1') {
			$this->db->queryInsert(sprintf('INSERT INTO logging (time, username, host) VALUES (NOW(), %s, %s)',
				$this->db->escapeString($username), $this->db->escapeString($host)));
		}
		else if ($s->loggingopt == '2')
		{
			$this->db->queryInsert(sprintf('INSERT INTO logging (time, username, host) VALUES (NOW(), %s, %s)',
				$this->db->escapeString($username), $this->db->escapeString($host)));
			$logData = date('M d H:i:s ')."Login Failed for ".$username." from ".$host.".\n";
			if (isset($s->logfile) && $s->logfile != "") {
				file_put_contents($s->logfile, $logData, FILE_APPEND);
			}
		}
		else if ($s->loggingopt == '3')
		{
			$logData = date('M d H:i:s ')."Login Failed for ".$username." from ".$host.".\n";
			if (isset($s->logfile) && $s->logfile != '') {
				file_put_contents($s->logfile, $logData, FILE_APPEND);
			}
		}
	}

	/**
	 * @return array
	 */
	public function getTopCombined()
	{
		return $this->db->query('SELECT MAX(time) AS time, username, host, COUNT(host) AS count FROM logging GROUP BY host, username ORDER BY count DESC LIMIT 10');
	}

	/**
	 * @return array
	 */
	public function getTopIPs()
	{
		return $this->db->query('SELECT MAX(time) AS time, host, COUNT(host) AS count FROM logging GROUP BY host ORDER BY count DESC LIMIT 10');
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
	 *               Anything else is unknown.
	 *
	 * @return void
	 */
	public function logDebug ($class, $method, $message, $severity)
	{
		// Check if site debug is on.
		if (!$this->siteDebug) {
			return;
		}

		// Create a string based on the severity of the this message.
		switch ($severity) {
			case 1:
				$severity = '] [FATAL]    [';
				break;
			case 2:
				$severity = '] [ERROR]    [';
				break;
			case 3:
				$severity = '] [WARNING]  [';
				break;
			case 4:
				$severity = '] [NOTICE]   [';
				break;
			case 5:
				$severity = '] [INFO]     [';
				break;
			default:
				$severity = '] [UNKNOWN]  [';
		}
		// Strip \r \n , multiple spaces and trim the message.
		$message = trim(preg_replace('/\s{2,}/', ' ', str_replace(array("\n", "\r", '\r', '\n'), ' ', $message)));

		// Current time. RFC2822 style ; Thu, 21 Dec 2000 16:01:07 +0200
		$time = '[' . Date('r');

		// Create message : [Sat, 1 Mar 2014 16:01:07 +0500] [ERROR] [NNTP.doConnect() Could not connect to news.tweaknews.com (ssl) Password is wrong.]
		$data = $time . $severity . $class . '.' . $method . '() ' . $message . ']' . $this->newLine;

		// Check if we want to echo the message.
		if ($this->debugCLI) {
			echo $data;
		}

		// Check if debug logging is on.
		if (!$this->debugLogging) {
			return;
		}

		// Check if we should log this type of message if the setting is on in the top of this script.
		if (!self::debugLogAll && self::debugLogLevel !== $severity) {
			return;
		}

		// Path to folder where log files are stored..
		$path = nZEDb_RES . DS . 'logs' . DS;

		// Check if the folder exists.
		if (!is_dir($path)) {
			if (!mkdir($path)) {
// Error creating log folder.
				return;
			}
		}

		// Name of the log file.
		$fileName = 'debug.log';

		// Full path to the log file.
		$fileLocation = $path.$fileName;

		// Initiate a new log file if we don't have one.
		if (!file_exists($fileLocation)) {
			if (!$this->initiateLog($fileLocation, $time)) {
// Error creating log file.
				return;
			}
		}

		// Check if we need to rotate the log if it exceeds max size..
		$logSize = filesize($fileLocation);
		if ($logSize === false) {
// Error getting log size.
			return;
		} else if ($logSize >= (self::logFileSize * 1024)) {
			if (!rename($fileLocation, $path . 'debug.old.' . time())) {
// Error renaming log.
				return;
			}

			// Create a new log.
			if (!$this->initiateLog($fileLocation, $time)) {
// Error creating log file.
				return;
			}
		}

		// Append the message to the log.
		if (!file_put_contents($fileLocation, $data, FILE_APPEND)) {
// Error appending message to log file.
			return;
		}
	}

	/**
	 * Initiate a log file.
	 *
	 * @param string $path The full path to the log file.
	 * @param string $time The time in RFC2822 style.
	 *
	 * @return bool
	 */
	protected function initiateLog($path, $time)
	{
		if (!file_exists($path)) {
			if (file_put_contents($path, $time . '] [INITIATE] [Initiating new log file.]' . $this->newLine)) {
				return true;
			}
		}
		return false;
	}
}
