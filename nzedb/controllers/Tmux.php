<?php

use nzedb\db\Settings;

class Tmux
{
	public $pdo;

	function __construct()
	{
		$this->pdo = new Settings();
	}

	public function version()
	{
		return $this->pdo->version();
	}

	public function update($form)
	{
		$pdo = $this->pdo;
		$tmux = $this->row2Object($form);

		$sql = $sqlKeys = array();
		foreach ($form as $settingK => $settingV) {
			if (is_array($settingV)) {
				$settingV = implode(', ', $settingV);
			}
			$sql[] = sprintf("WHEN %s THEN %s", $pdo->escapeString($settingK), $pdo->escapeString($settingV));
			$sqlKeys[] = $pdo->escapeString($settingK);
		}

		$pdo->queryExec(sprintf("UPDATE tmux SET value = CASE setting %s END WHERE setting IN (%s)", implode(' ', $sql), implode(', ', $sqlKeys)));

		return $tmux;
	}

	public function get()
	{
		$pdo = $this->pdo;
		$rows = $pdo->query("SELECT * FROM tmux");

		if ($rows === false) {
			return false;
		}

		return $this->rows2Object($rows);
	}

	public function getMonitorSettings()
	{
		$tmuxstr = 'SELECT value FROM tmux WHERE setting =';
		$settstr = 'SELECT value FROM settings WHERE setting =';

		$sql = sprintf(
				"SELECT
					(SELECT searchname FROM releases ORDER BY adddate DESC LIMIT 1) AS newestname,
					(%1\$s 'monitor_delay') AS monitor,
					(%1\$s 'tmux_session') AS tmux_session,
					(%1\$s 'niceness') AS niceness,
					(%1\$s 'binaries') AS binaries_run,
					(%1\$s 'backfill') AS backfill,
					(%1\$s 'import') AS import,
					(%1\$s 'nzbs') AS nzbs,
					(%1\$s 'post') AS post,
					(%1\$s 'releases') AS releases_run,
					(%1\$s 'releases_threaded') AS releases_threaded,
					(%1\$s 'fix_names') as fix_names,
					(%1\$s 'seq_timer') as seq_timer,
					(%1\$s 'bins_timer') as bins_timer,
					(%1\$s 'back_timer') as back_timer,
					(%1\$s 'import_timer') as import_timer,
					(%1\$s 'rel_timer') as rel_timer,
					(%1\$s 'fix_timer') as fix_timer,
					(%1\$s 'post_timer') as post_timer,
					(%1\$s 'collections_kill') as collections_kill,
					(%1\$s 'postprocess_kill') as postprocess_kill,
					(%1\$s 'crap_timer') as crap_timer,
					(%1\$s 'fix_crap') as fix_crap,
					(%1\$s 'fix_crap_opt') as fix_crap_opt,
					(%1\$s 'tv_timer') as tv_timer,
					(%1\$s 'update_tv') as update_tv,
					(%1\$s 'post_kill_timer') as post_kill_timer,
					(%1\$s 'monitor_path') as monitor_path,
					(%1\$s 'monitor_path_a') as monitor_path_a,
					(%1\$s 'monitor_path_b') as monitor_path_b,
					(%1\$s 'progressive') as progressive,
					(%1\$s 'dehash') as dehash,
					(%1\$s 'dehash_timer') as dehash_timer,
					(%1\$s 'backfill_days') as backfilldays,
					(%1\$s 'post_amazon') as post_amazon,
					(%1\$s 'post_timer_amazon') as post_timer_amazon,
					(%1\$s 'post_non') as post_non,
					(%1\$s 'post_timer_non') as post_timer_non,
					(SELECT COUNT(*) FROM groups WHERE active = 1) AS active_groups,
					(SELECT COUNT(*) FROM groups WHERE name IS NOT NULL) AS all_groups,
					(%1\$s 'colors_start') AS colors_start,
					(%1\$s 'colors_end') AS colors_end,
					(%1\$s 'colors_exc') AS colors_exc,
					(%1\$s 'showquery') AS show_query,
					(%1\$s 'running') AS is_running,
					(%1\$s 'sharing_timer') AS sharing_timer,
					(%2\$s 'lookupbooks') as processbooks,
					(%2\$s 'lookupmusic') as processmusic,
					(%2\$s 'lookupgames') as processgames,
					(%2\$s 'lookupxxx') as processxxx,
					(%2\$s 'lookupimdb') as processmovies,
					(%2\$s 'lookuptvrage') as processtvrage,
					(%2\$s 'lookupnfo') as processnfo,
					(%2\$s 'lookuppar2') as processpar2,
					(%2\$s 'tmpunrarpath') as tmpunrar,
					(%2\$s 'compressedheaders') AS compressed",
					$tmuxstr,
					$settstr
		);
		return $sql;
	}

	public function rows2Object($rows)
	{
		$obj = new stdClass;
		foreach ($rows as $row) {
			$obj->{$row['setting']} = $row['value'];
		}

		$obj->{'version'} = $this->version();
		return $obj;
	}

	public function row2Object($row)
	{
		$obj = new stdClass;
		$rowKeys = array_keys($row);
		foreach ($rowKeys as $key) {
			$obj->{$key} = $row[$key];
		}
		return $obj;
	}

	public function updateItem($setting, $value)
	{
		$pdo = $this->pdo;
		$sql = sprintf("UPDATE tmux SET value = %s WHERE setting = %s", $pdo->escapeString($value), $pdo->escapeString($setting));
		return $pdo->queryExec($sql);
	}

	//get microtime
	public function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	public function decodeSize($bytes)
	{
		$types = array('B', 'KB', 'MB', 'GB', 'TB');
		for ($i = 0; $bytes >= 1024 && $i < (count($types) - 1); $bytes /= 1024, $i++);
		return (round($bytes, 2) . " " . $types[$i]);
	}

	public function writelog($pane)
	{
		$path = dirname(__FILE__) . "/logs";
		$getdate = gmDate("Ymd");
		$tmux = $this->get();
		$logs = (isset($tmux->write_logs)) ? $tmux->write_logs : 0;
		if ($logs == 1) {
			return "2>&1 | tee -a $path/$pane-$getdate.log";
		} else {
			return "";
		}
	}

	public function get_color($colors_start, $colors_end, $colors_exc)
	{
		$exception = str_replace(".", ".", $colors_exc);
		$exceptions = explode(",", $exception);
		sort($exceptions);
		$number = mt_rand($colors_start, $colors_end - count($exceptions));
		foreach ($exceptions as $exception) {
			if ($number >= $exception) {
				$number++;
			} else {
				break;
			}
		}
		return $number;
	}

	// Returns random bool, weighted by $chance
	public function rand_bool($loop, $chance = 60)
	{
		$t = new Tmux();
		$tmux = $t->get();
		$usecache = (isset($tmux->usecache)) ? $tmux->usecache : 0;
		if ($loop == 1 || $usecache == 0) {
			return false;
		} else {
			return (mt_rand(1, 100) <= $chance);
		}
	}

	public function relativeTime($_time)
	{
		$d[0] = array(1, "sec");
		$d[1] = array(60, "min");
		$d[2] = array(3600, "hr");
		$d[3] = array(86400, "day");
		$d[4] = array(31104000, "yr");

		$w = array();

		$return = "";
		$now = TIME();
		$diff = ($now - ($_time >= $now ? $_time - 1 : $_time));
		$secondsLeft = $diff;

		for ($i = 4; $i > -1; $i--) {
			$w[$i] = intval($secondsLeft / $d[$i][0]);
			$secondsLeft -= ($w[$i] * $d[$i][0]);
			if ($w[$i] != 0) {
				//$return.= abs($w[$i]). " " . $d[$i][1] . (($w[$i]>1)?'s':'') ." ";
				$return .= $w[$i] . " " . $d[$i][1] . (($w[$i] > 1) ? 's' : '') . " ";
			}
		}
		//$return .= ($diff>0)?"ago":"left";
		return $return;
	}

	public function run_ircscraper($tmux_session, $_php, $pane, $run_ircscraper)
	{
		if ($run_ircscraper == 1) {
			//Check to see if the pane is dead, if so respawn it.
			if (shell_exec("tmux list-panes -t${tmux_session}:${pane} | grep ^0 | grep -c dead") == 1) {
				$DIR = nZEDb_MISC;
				$ircscraper = $DIR . "testing/IRCScraper/scrape.php";
				shell_exec(
					"tmux respawnp -t${tmux_session}:${pane}.0 ' \
							$_php $ircscraper true'"
				);
			}
		} else {
			shell_exec("tmux respawnp -t${tmux_session}:${pane}.0 'echo \"\nIRCScraper has been disabled/terminated by IRCSCraper\"'");
		}
	}

	public function run_sharing($tmux_session, $_php, $pane, $_sleep, $sharing_timer)
	{
		$pdo = new Settings();
		$sharing = $pdo->queryOneRow('SELECT enabled, posting, fetching FROM sharing');
		$tmux = $this->get();
		$tmux_share = (isset($tmux->run_sharing)) ? $tmux->run_sharing : 0;

		if ($tmux_share && $sharing['enabled'] == 1 && ($sharing['posting'] == 1 || $sharing['fetching'] == 1)) {
			if (shell_exec("tmux list-panes -t${tmux_session}:${pane} | grep ^0 | grep -c dead") == 1) {
				$DIR = nZEDb_MISC;
				$sharing2 = $DIR . "/update/postprocess.php sharing true";
				shell_exec(
					"tmux respawnp -t${tmux_session}:${pane}.0 ' \
						$_php $sharing2; $_sleep $sharing_timer' 2>&1 1> /dev/null"
				);
			}
		}
	}

	public function command_exist($cmd)
	{
		$returnVal = shell_exec("which $cmd 2>/dev/null");
		return (empty($returnVal) ? false : true);
	}

	public function proc_query($qry, $bookreqids, $request_hours, $db_name)
	{

		$this->qry = $qry;
		$this->bookreqids = $bookreqids;
		$this->request_hours = $request_hours;
		$this->db_name = $db_name;

		$qry1 = "SELECT
				(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND categoryid BETWEEN 5000 AND 5999 AND rageid = -1) AS tv,
				(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND categoryid BETWEEN 2000 AND 2999 AND imdbid IS NULL) AS movies,
				(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND categoryid IN (3010, 3040, 3050) AND musicinfoid IS NULL) AS audio,
				(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND categoryid BETWEEN 1000 AND 1999 AND consoleinfoid IS NULL) +
					(SELECT COUNT(*) FROM releases WHERE categoryid = 4050 AND gamesinfo_id = 0) AS games,
				(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND categoryid IN (" . $this->bookreqids . ") AND bookinfoid IS NULL) AS book,
				(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND categoryid BETWEEN 6000 AND 6040 AND xxxinfo_id = 0) AS xxx,
				(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1) AS releases,
				(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND nfostatus = 1) AS nfo,
				(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND nfostatus BETWEEN -8 AND -1) AS nforemains";
		$qry2 = "SELECT
				(SELECT COUNT(*) FROM releases r INNER JOIN category c ON c.id = r.categoryid WHERE r.nzbstatus = 1 AND r.categoryid BETWEEN 4000 AND 4999 AND r.categoryid != 4050 AND r.passwordstatus BETWEEN -6 AND -1 AND r.haspreview = -1 AND c.disablepreview = 0) AS apps,
				(SELECT COUNT(*) FROM releases r INNER JOIN category c ON c.id = r.categoryid WHERE r.nzbstatus = 1 AND r.passwordstatus BETWEEN -6 AND -1 AND r.haspreview = -1 AND c.disablepreview = 0) AS work,
				(SELECT COUNT(*) FROM collections WHERE collectionhash IS NOT NULL) AS collections_table,
				(SELECT COUNT(*) FROM partrepair WHERE attempts < 5) AS partrepair_table";
		$qry3 = "SELECT
				(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND isrequestid = 1 AND preid = 0 AND reqidstatus = 0) AS requestid_unproc,
				(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND isrequestid = 1 AND preid = 0 AND reqidstatus = -1) AS requestid_local,
				(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND isrequestid = 1 AND preid = 0 AND reqidstatus = -3 AND adddate > NOW() - INTERVAL " . $this->request_hours . " HOUR) AS requestid_web,
				(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND isrequestid = 1 AND reqidstatus = 1) AS requestid_matched,
				(SELECT COUNT(*) FROM releases WHERE preid > 0) AS predb_matched,
				(SELECT COUNT(DISTINCT(preid)) FROM releases WHERE preid > 0) AS distinct_predb_matched,
				(SELECT COUNT(*) FROM binaries WHERE collectionid IS NOT NULL) AS binaries_table";
		$qry4 = "SELECT
				(SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES where table_name = 'predb' AND TABLE_SCHEMA = '$this->db_name') AS predb,
				(SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES where table_name = 'parts' AND TABLE_SCHEMA = '$this->db_name') AS parts_table,
				(SELECT COUNT(*) FROM groups WHERE first_record IS NOT NULL AND backfill = 1 AND (now() - interval backfill_target day) < first_record_postdate) AS backfill_groups_days,
				(SELECT COUNT(*) FROM groups WHERE first_record IS NOT NULL AND backfill = 1 AND (now() - interval datediff(curdate(),
				(SELECT VALUE FROM settings WHERE SETTING = 'safebackfilldate')) day) < first_record_postdate) AS backfill_groups_date,
				(SELECT UNIX_TIMESTAMP(dateadded) FROM collections ORDER BY dateadded ASC LIMIT 1) AS oldestcollection,
				(SELECT UNIX_TIMESTAMP(predate) FROM predb ORDER BY predate DESC LIMIT 1) AS newestpre,
				(SELECT UNIX_TIMESTAMP(adddate) FROM releases WHERE nzbstatus = 1 ORDER BY adddate DESC LIMIT 1) AS newestadd";
		$qry5 = "SELECT
				(SELECT COUNT(*) FROM predb WHERE id IS NOT NULL) AS predb,
				(SELECT COUNT(*) FROM parts WHERE id IS NOT NULL) AS parts_table,
				(SELECT COUNT(*) FROM groups WHERE first_record IS NOT NULL AND backfill = 1 AND (current_timestamp - backfill_target * interval '1 days') < first_record_postdate) AS backfill_groups_days,
				(SELECT COUNT(*) FROM groups WHERE first_record IS NOT NULL AND backfill = 1 AND (current_timestamp - (date(current_date::date) - date((SELECT value FROM settings WHERE setting = 'safebackfilldate')::date)) * interval '1 days') < first_record_postdate) AS backfill_groups_date,
				(SELECT extract(epoch FROM dateadded) FROM collections ORDER BY dateadded ASC LIMIT 1) AS oldestcollection,
				(SELECT extract(epoch FROM predate) FROM predb ORDER BY predate DESC LIMIT 1) AS newestpre,
				(SELECT extract(epoch FROM adddate) FROM releases WHERE nzbstatus = 1 ORDER BY adddate DESC LIMIT 1) AS newestadd";

		switch ((int) $this->qry) {
			case 1:
				return $qry1;
			case 2:
				return $qry2;
			case 3:
				return $qry3;
			case 4:
				return $qry4;
			case 5:
				return $qry5;
			default:
				return false;
		}
	}
}
