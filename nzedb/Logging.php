<?php
/**
 * Logs/Reports stuff
 */
class Logging
{
	public function get()
	{
		$db = new DB();
		return $db->query('SELECT * FROM logging');
	}

	public function LogBadPasswd($username='', $host='')
	{
		$db = new DB();
		$site = new Sites();
		$s = $site->get();
		// If logggingopt is = 0, then we do nothing, 0 = logging off.
		if ($s->loggingopt == '1')
			$db->queryInsert(sprintf('INSERT INTO logging (time, username, host) VALUES (NOW(), %s, %s)', $db->escapeString($username), $db->escapeString($host)));
		else if ($s->loggingopt == '2')
		{
			$db->queryInsert(sprintf('INSERT INTO logging (time, username, host) VALUES (NOW(), %s, %s)', $db->escapeString($username), $db->escapeString($host)));
			$logdata = date('M d H:i:s ')."Login Failed for ".$username." from ".$host.".\n";
			if (isset($s->logfile) && $s->logfile != "")
				file_put_contents($s->logfile, $logdata, FILE_APPEND);
		}
		else if ($s->loggingopt == '3')
		{
			$logdata = date('M d H:i:s ')."Login Failed for ".$username." from ".$host.".\n";
			if (isset($s->logfile) && $s->logfile != '')
				file_put_contents($s->logfile, $logdata, FILE_APPEND);
		}
	}

	public function getTopCombined()
	{
		$db = new DB();
		return $db->query('SELECT MAX(time) AS time, username, host, COUNT(host) AS count FROM logging GROUP BY host, username ORDER BY count DESC LIMIT 10');
	}

	public function getTopIPs()
	{
		$db = new DB();
		return $db->query('SELECT MAX(time) AS time, host, COUNT(host) AS count FROM logging GROUP BY host ORDER BY count DESC LIMIT 10');
	}
}
?>
