<?php
/**
 * Logs/Reports stuff
 */
class Logging
{
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
		$this->db = new DB();
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
		$site = new Sites();
		$s = $site->get();
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

	/**
	 * @param string $message  The message to log.
	 * @param string $class    The class this is coming from.
	 * @param string $method   The method this is coming from.
	 * @param int    $severity How severe of a warning is this?
	 *
	 * @return void
	 */
	public function logDebug($message, $class, $method, $severity)
	{
		// Path to store the log file.
		$path = nZEDb_RES . DS . 'debug' . DS;

		// Current time. RFC2822 style ; Thu, 21 Dec 2000 16:01:07 +0200
		$time = Date(r);
	}
}
