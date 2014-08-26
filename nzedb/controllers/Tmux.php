<?php

use nzedb\db\Settings;

class Tmux
{
	/**
	 * @var \nzedb\db\Settings
	 */
	public $pdo;

	function __construct(Settings $pdo = null)
	{
		$this->pdo = (empty($pdo) ? new Settings() : $pdo);
	}

	public function version()
	{
		return $this->pdo->version();
	}

	public function update($form)
	{
		$pdo = $this->pdo;
		$tmux = $this->row2Object($form);

		$sql = $sqlKeys = [];
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

	public function get($setting = '')
	{
		$pdo = $this->pdo;
		$where = ($setting !== '' ? sprintf('WHERE setting = %s', $pdo->escapeString($setting)) : '');

		$rows = $pdo->query(
					sprintf(
						"SELECT * FROM tmux %s",
						$where
					)
		);

		if ($rows === false) {
			return false;
		}

		return $this->rows2Object($rows);
	}

	public function getConnectionsInfo($constants)
	{
		$runVar['connections']['port_a'] = $runVar['connections']['host_a'] = $runVar['connections']['ip_a'] = false;

		if ($constants['nntpproxy'] == 0) {
			$runVar['connections']['port'] = NNTP_PORT;
			$runVar['connections']['host'] = NNTP_SERVER;
			$runVar['connections']['ip'] = gethostbyname($runVar['connections']['host']);
			if ($constants['alternate_nntp'] === '1') {
				$runVar['connections']['port_a'] = NNTP_PORT_A;
				$runVar['connections']['host_a'] = NNTP_SERVER_A;
				$runVar['connections']['ip_a'] = gethostbyname($runVar['connections']['host_a']);
			}
		} else {
			$filename = nZEDb_MISC . "update/python/lib/nntpproxy.conf";
			$fp = fopen($filename, "r") or die("Couldn't open $filename");
			while (!feof($fp)) {
				$line = fgets($fp);
				if (preg_match('/"host": "(.+)",$/', $line, $match)) {
					$runVar['connections']['host'] = $match[1];
				}
				if (preg_match('/"port": (.+),$/', $line, $match)) {
					$runVar['connections']['port'] = $match[1];
					break;
				}
			}
			if ($constants['alternate_nntp']) {
				$filename = nZEDb_MISC . "update/python/lib/nntpproxy_a.conf";
				$fp = fopen($filename, "r") or die("Couldn't open $filename");
				while (!feof($fp)) {
					$line = fgets($fp);
					if (preg_match('/"host": "(.+)",$/', $line, $match)) {
						$runVar['connections']['host_a'] = $match[1];
					}
					if (preg_match('/"port": (.+),$/', $line, $match)) {
						$runVar['connections']['port_a'] = $match[1];
						break;
					}
				}
			}
			$runVar['connections']['ip'] = gethostbyname($runVar['connections']['host']);
			if ($constants['alternate_nntp'] === '1') {
				$runVar['connections']['ip_a'] = gethostbyname($runVar['connections']['host_a']);
			}
		}
		return $runVar['connections'];
	}

	public function getUSPConnections($which, $connections)
	{

		switch ($which) {
			case 'alternate':
					$ip = 'ip_a';
					$port = 'port_a';
					break;
			case 'primary':
			default:
				$ip = 'ip';
				$port = 'port';
				break;
		}

		$runVar['conncounts'][$which]['active'] = $runVar['conncounts'][$which]['total'] = 0;

		$runVar['conncounts'][$which]['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $connections[$ip] . ":" . $connections[$port] . " | grep -c ESTAB"));
		$runVar['conncounts'][$which]['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $connections[$ip] . ":" . $connections[$port]));

		if ($runVar['conncounts'][$which]['active'] == 0 && $runVar['conncounts'][$which]['total'] == 0) {
				$runVar['conncounts'][$which]['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $connections[$ip] . ":https | grep -c ESTAB"));
				$runVar['conncounts'][$which]['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $connections[$ip] . ":https"));
		}
		if ($runVar['conncounts'][$which]['active'] == 0 && $runVar['conncounts'][$which]['total'] == 0) {
			$runVar['conncounts'][$which]['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $connections[$port] . " | grep -c ESTAB"));
			$runVar['conncounts'][$which]['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $connections[$port]));
		}
		if ($runVar['conncounts'][$which]['active'] == 0 && $runVar['conncounts'][$which]['total'] == 0) {
			$runVar['conncounts'][$which]['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $connections[$ip] . " | grep -c ESTAB"));
			$runVar['conncounts'][$which]['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $connections[$ip]));
		}
		return ($runVar['conncounts']);
	}

	public function getListOfPanes($constants)
	{
		$panes = ['zero' => '', 'one' => '', 'two' => ''];
		switch ($constants['sequential']) {
			case 0:
				$panes_win_1 = shell_exec("echo `tmux list-panes -t {$constants['tmux_session']}:0 -F '#{pane_title}'`");
				$panes['zero'] = str_replace("\n", '', explode(" ", $panes_win_1));
				$panes_win_2 = shell_exec("echo `tmux list-panes -t {$constants['tmux_session']}:1 -F '#{pane_title}'`");
				$panes['one'] = str_replace("\n", '', explode(" ", $panes_win_2));
				$panes_win_3 = shell_exec("echo `tmux list-panes -t {$constants['tmux_session']}:2 -F '#{pane_title}'`");
				$panes['two'] = str_replace("\n", '', explode(" ", $panes_win_3));
				break;
			case 1:
				$panes_win_1 = shell_exec("echo `tmux list-panes -t {$constants['tmux_session']}:0 -F '#{pane_title}'`");
				$panes['zero'] = str_replace("\n", '', explode(" ", $panes_win_1));
				$panes_win_2 = shell_exec("echo `tmux list-panes -t {$constants['tmux_session']}:1 -F '#{pane_title}'`");
				$panes['one'] = str_replace("\n", '', explode(" ", $panes_win_2));
				$panes_win_3 = shell_exec("echo `tmux list-panes -t {$constants['tmux_session']}:2 -F '#{pane_title}'`");
				$panes['two'] = str_replace("\n", '', explode(" ", $panes_win_3));
				break;
			case 2:
				$panes_win_1 = shell_exec("echo `tmux list-panes -t {$constants['tmux_session']}:0 -F '#{pane_title}'`");
				$panes['zero'] = str_replace("\n", '', explode(" ", $panes_win_1));
				$panes_win_2 = shell_exec("echo `tmux list-panes -t {$constants['tmux_session']}:1 -F '#{pane_title}'`");
				$panes['one'] = str_replace("\n", '', explode(" ", $panes_win_2));
				break;
		}
		return $panes;
	}

	public function getConstantSettings()
	{
		$tmuxstr = 'SELECT value FROM tmux WHERE setting =';
		$settstr = 'SELECT value FROM settings WHERE setting =';

		$sql = sprintf(
				"SELECT
					(%1\$s 'sequential') AS sequential,
					(%1\$s 'tmux_session') AS tmux_session,
					(%1\$s 'run_ircscraper') AS run_ircscraper,
					(%2\$s 'sqlpatch') AS sqlpatch,
					(%2\$s 'alternate_nntp') AS alternate_nntp,
					(%2\$s 'tablepergroup') AS tablepergroup,
					(%2\$s 'delaytime') AS delaytime,
					(%2\$s 'nntpproxy') AS nntpproxy",
					$tmuxstr,
					$settstr
		);
		return $sql;
	}

	public function getMonitorSettings()
	{
		$tmuxstr = 'SELECT value FROM tmux WHERE setting =';
		$settstr = 'SELECT value FROM settings WHERE setting =';

		$sql = sprintf(
				"SELECT
					(%1\$s 'monitor_delay') AS monitor,
					(%1\$s 'binaries') AS binaries_run,
					(%1\$s 'backfill') AS backfill,
					(%1\$s 'backfill_qty') AS backfill_qty,
					(%1\$s 'import') AS import,
					(%1\$s 'nzbs') AS nzbs,
					(%1\$s 'post') AS post,
					(%1\$s 'releases') AS releases_run,
					(%1\$s 'releases_threaded') AS releases_threaded,
					(%1\$s 'fix_names') AS fix_names,
					(%1\$s 'seq_timer') AS seq_timer,
					(%1\$s 'bins_timer') AS bins_timer,
					(%1\$s 'back_timer') AS back_timer,
					(%1\$s 'import_timer') AS import_timer,
					(%1\$s 'rel_timer') AS rel_timer,
					(%1\$s 'fix_timer') AS fix_timer,
					(%1\$s 'post_timer') AS post_timer,
					(%1\$s 'collections_kill') AS collections_kill,
					(%1\$s 'postprocess_kill') AS postprocess_kill,
					(%1\$s 'crap_timer') AS crap_timer,
					(%1\$s 'fix_crap') AS fix_crap,
					(%1\$s 'fix_crap_opt') AS fix_crap_opt,
					(%1\$s 'tv_timer') AS tv_timer,
					(%1\$s 'update_tv') AS update_tv,
					(%1\$s 'post_kill_timer') AS post_kill_timer,
					(%1\$s 'monitor_path') AS monitor_path,
					(%1\$s 'monitor_path_a') AS monitor_path_a,
					(%1\$s 'monitor_path_b') AS monitor_path_b,
					(%1\$s 'progressive') AS progressive,
					(%1\$s 'dehash') AS dehash,
					(%1\$s 'dehash_timer') AS dehash_timer,
					(%1\$s 'backfill_days') AS backfilldays,
					(%1\$s 'post_amazon') AS post_amazon,
					(%1\$s 'post_timer_amazon') AS post_timer_amazon,
					(%1\$s 'post_non') AS post_non,
					(%1\$s 'post_timer_non') AS post_timer_non,
					(%1\$s 'colors_start') AS colors_start,
					(%1\$s 'colors_end') AS colors_end,
					(%1\$s 'colors_exc') AS colors_exc,
					(%1\$s 'showquery') AS show_query,
					(%1\$s 'running') AS is_running,
					(%1\$s 'run_sharing') AS run_sharing,
					(%1\$s 'sharing_timer') AS sharing_timer,
					(%2\$s 'lookupbooks') AS processbooks,
					(%2\$s 'lookupmusic') AS processmusic,
					(%2\$s 'lookupgames') AS processgames,
					(%2\$s 'lookupxxx') AS processxxx,
					(%2\$s 'lookupimdb') AS processmovies,
					(%2\$s 'lookuptvrage') AS processtvrage,
					(%2\$s 'lookupnfo') AS processnfo,
					(%2\$s 'lookuppar2') AS processpar2,
					(%2\$s 'nzbthreads') AS nzbthreads,
					(%2\$s 'tmpunrarpath') AS tmpunrar,
					(%2\$s 'compressedheaders') AS compressed,
					(%2\$s 'book_reqids') AS book_reqids,
					(%2\$s 'request_hours') AS request_hours",
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
		$types = ['B', 'KB', 'MB', 'GB', 'TB'];
		for ($i = 0; $bytes >= 1024 && $i < (count($types) - 1); $bytes /= 1024, $i++);
		return (round($bytes, 2) . " " . $types[$i]);
	}

	public function writelog($pane)
	{
		$path = nZEDb_LOGS;
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
		$tmux = $this->get();
		$usecache = (isset($tmux->usecache)) ? $tmux->usecache : 0;
		if ($loop == 1 || $usecache == 0) {
			return false;
		} else {
			return (mt_rand(1, 100) <= $chance);
		}
	}

	public function relativeTime($_time)
	{
		$d[0] = [1, "sec"];
		$d[1] = [60, "min"];
		$d[2] = [3600, "hr"];
		$d[3] = [86400, "day"];
		$d[4] = [31104000, "yr"];

		$w = [];

		$return = '';
		$now = time();
		$diff = ($now - ($_time >= $now ? $_time - 1 : $_time));
		$secondsLeft = $diff;

		for ($i = 4; $i > -1; $i--) {
			$w[$i] = intval($secondsLeft / $d[$i][0]);
			$secondsLeft -= ($w[$i] * $d[$i][0]);
			if ($w[$i] != 0) {
				$return .= $w[$i] . " " . $d[$i][1] . (($w[$i] > 1) ? 's' : '') . " ";
			}
		}
		return $return;
	}

	public function command_exist($cmd)
	{
		$returnVal = shell_exec("which $cmd 2>/dev/null");
		return (empty($returnVal) ? false : true);
	}

	public function proc_query($qry, $bookreqids, $request_hours, $db_name)
	{
		switch ((int) $qry) {
			case 1:
				return sprintf("SELECT
					(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND categoryid BETWEEN 5000 AND 5999 AND rageid = -1) AS processtvrage,
					(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND categoryid BETWEEN 2000 AND 2999 AND imdbid IS NULL) AS processmovies,
					(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND categoryid IN (3010, 3040, 3050) AND musicinfoid IS NULL) AS processmusic,
					(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND categoryid BETWEEN 1000 AND 1999 AND consoleinfoid IS NULL) AS processconsole,
					(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND categoryid IN (%s) AND bookinfoid IS NULL) AS processbooks,
					(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND categoryid = 4050 AND gamesinfo_id = 0) AS processgames,
					(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND categoryid BETWEEN 6000 AND 6040 AND xxxinfo_id = 0) AS processxxx,
					(SELECT COUNT(*) FROM releases r WHERE 1=1 %s) AS processnfo", $bookreqids, Nfo::NfoQueryString($this->pdo));
			case 2:
				return "SELECT
					(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND nfostatus = 1) AS nfo,
					(SELECT COUNT(*) FROM releases r
						INNER JOIN category c ON c.id = r.categoryid
						WHERE r.nzbstatus = 1
						AND r.passwordstatus BETWEEN -6 AND -1 AND r.haspreview = -1 AND c.disablepreview = 0
					) AS work,
					(SELECT COUNT(*) FROM groups WHERE active = 1) AS active_groups,
					(SELECT COUNT(*) FROM groups WHERE name IS NOT NULL) AS all_groups";
			case 3:
				return sprintf("SELECT
					(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND isrequestid = 1 AND preid = 0 AND reqidstatus = 0) +
					(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND isrequestid = 1 AND preid = 0 AND reqidstatus = -1) +
					(SELECT COUNT(*) FROM releases
						WHERE nzbstatus = 1
						AND isrequestid = 1 AND preid = 0 AND reqidstatus = -3 AND adddate > NOW() - INTERVAL %s HOUR
					) AS requestid_inprogress,
					(SELECT COUNT(*) FROM releases WHERE preid > 0 AND nzbstatus = 1 AND isrequestid = 1 AND reqidstatus = 1) AS requestid_matched,
					(SELECT COUNT(*) FROM releases WHERE preid > 0 AND searchname IS NOT NULL) AS predb_matched,
					(SELECT COUNT(DISTINCT(preid)) FROM releases WHERE preid > 0 AND searchname IS NOT NULL) AS distinct_predb_matched", $request_hours);
			case 4:
				return sprintf("
					SELECT
					(SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES WHERE table_name = 'predb' AND TABLE_SCHEMA = %1\$s) AS predb,
					(SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES WHERE table_name = 'partrepair' AND TABLE_SCHEMA = %1\$s) AS partrepair_table,
					(SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES WHERE table_name = 'parts' AND TABLE_SCHEMA = %1\$s) AS parts_table,
					(SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES WHERE table_name = 'binaries' AND TABLE_SCHEMA = %1\$s) AS binaries_table,
					(SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES WHERE table_name = 'collections' AND TABLE_SCHEMA = %1\$s) AS collections_table,
					(SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES WHERE table_name = 'releases' AND TABLE_SCHEMA = %1\$s) AS releases,
					(SELECT COUNT(*) FROM groups WHERE first_record IS NOT NULL AND backfill = 1
						AND (now() - INTERVAL backfill_target DAY) < first_record_postdate
					) AS backfill_groups_days,
					(SELECT COUNT(*) FROM groups WHERE first_record IS NOT NULL AND backfill = 1 AND (now() - INTERVAL datediff(curdate(),
					(SELECT VALUE FROM settings WHERE setting = 'safebackfilldate')) DAY) < first_record_postdate) AS backfill_groups_date",
					$this->pdo->escapeString($db_name)
				);
			case 6:
				return "SELECT
					(SELECT searchname FROM releases ORDER BY id DESC LIMIT 1) AS newestrelname,
					(SELECT UNIX_TIMESTAMP(MIN(dateadded)) FROM collections) AS oldestcollection,
					(SELECT UNIX_TIMESTAMP(MAX(predate)) FROM predb) AS newestpre,
					(SELECT UNIX_TIMESTAMP(adddate) FROM releases ORDER BY id DESC LIMIT 1) AS newestrelease";
			default:
				return false;
		}
	}

	/**
	 * Check if Tmux is running, stop it if it is.
	 *
	 * @return bool
	 * @access public
	 */
	public function isRunning()
	{
		if ($this->get()->running == 1) {
			$this->pdo->queryExec("UPDATE tmux SET value = '0' WHERE setting = 'RUNNING'");
			$sleep = $this->get()->monitor_delay;
			echo $this->pdo->log->header("Stopping tmux scripts and waiting $sleep seconds for all panes to shutdown");
			sleep($sleep);
			return true;
		}
		return false;
	}

}
