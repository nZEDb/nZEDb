<?php

use nzedb\db\DB;

/**
 * Logs/Reports stuff
 */
class Logging
{
	/**
	 * @var string If windows "\r\n" if unix "\n".
	 * @access private
	 */
	private $newLine;

	/**
	 * @var object DB Class instance.
	 * @access private
	 */
	private $db;

	/**
	 * @var object Class instance.
	 * @access private
	 */
	private $colorCLI;

	/**
	 * Constructor.
	 *
	 * @access public
	 */
	public function __construct()
	{
		$this->db = new DB();
		$this->colorCLI = new ColorCLI();

		$this->newLine = PHP_EOL;
	}

	/**
	 * Get all rows from logging table.
	 *
	 * @return array
	 *
	 * @access public
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
	 *
	 * @access public
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
			$logData = date('M d H:i:s ')."Login Failed for ".$username." from ".$host . "." . $this->newLine;
			if (isset($s->logfile) && $s->logfile != "") {
				file_put_contents($s->logfile, $logData, FILE_APPEND);
			}
		}
		else if ($s->loggingopt == '3')
		{
			$logData = date('M d H:i:s ')."Login Failed for ".$username." from ".$host . "." . $this->newLine;
			if (isset($s->logfile) && $s->logfile != '') {
				file_put_contents($s->logfile, $logData, FILE_APPEND);
			}
		}
	}

	/**
	 * @return array
	 *
	 * @access public
	 */
	public function getTopCombined()
	{
		return $this->db->query('SELECT MAX(time) AS time, username, host, COUNT(host) AS count FROM logging GROUP BY host, username ORDER BY count DESC LIMIT 10');
	}

	/**
	 * @return array
	 *
	 * @access public
	 */
	public function getTopIPs()
	{
		return $this->db->query('SELECT MAX(time) AS time, host, COUNT(host) AS count FROM logging GROUP BY host ORDER BY count DESC LIMIT 10');
	}
}
