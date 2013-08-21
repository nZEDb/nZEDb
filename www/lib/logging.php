<?php
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/site.php");

class Logging
{

	public function get()
	{
		$db = new DB();
		return $db->query("select * from logging");
	}

	public function LogBadPasswd($username="", $host="")
	{
		$db = new DB();
		$site = new Sites();
		$s = $site->get();
		// If logggingopt is = 0, then we do nothing, 0 = logging off.
		if ($s->loggingopt == "1")
		{
			$db->queryInsert(sprintf("insert into logging (time, username, host) values (now(), %s, %s)", $db->escapeString($username), $db->escapeString($host)));
		}
		else if ($s->loggingopt == "2")
		{
			$db->queryInsert(sprintf("insert into logging (time, username, host) values (now(), %s, %s)", $db->escapeString($username), $db->escapeString($host)));
			$logdata = date('D d H:i:s ')."Login Failed for ".$username." from ".$host.".\n";
			if (isset($s->logfile) && $s->logfile != "")
				file_put_contents($s->logfile, $logdata, FILE_APPEND);
		}
		else if ($s->loggingopt == "3")
		{
			$logdata = date('D d H:i:s ')."Login Failed for ".$username." from ".$host.".\n";
			if (isset($s->logfile) && $s->logfile != "")
				file_put_contents($s->logfile, $logdata, FILE_APPEND);
		}
	}

	public function getTopCombined()
	{
		$db = new DB();
		return $db->query("select max(time) as time, username, host, count(host) as count from logging
		group by host, username
		order by count desc
		limit 10");
	}

	public function getTopIPs()
	{
		$db = new DB();
		return $db->query("select max(time) as time, host, count(host) as count from logging
		group by host
		order by count desc
		limit 10");
	}
}
