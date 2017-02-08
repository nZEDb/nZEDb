<?php
namespace nzedb;

use app\models\Settings;
use app\extensions\util\Versions;
use nzedb\db\DB;

/**
 * Class Tmux
 *
 * @package nzedb
 */
class Tmux
{
	/**
	 * @var \nzedb\db\Settings
	 */
	public $pdo;

	/**
	 * @var
	 */
	public $tmux_session;

	/**
	 * Tmux constructor.
	 *
	 * @param \nzedb\db\DB|null $pdo
	 */
	function __construct(DB $pdo = null)
	{
		$this->pdo = (empty($pdo) ? new DB() : $pdo);
	}

	/**
	 * @return string
	 */
	public function version()
	{
		return (new Versions())->getGitTagInRepo();
	}

	/**
	 * @param $form
	 *
	 * @return \stdClass
	 */
	public function update($form)
	{
		$tmux = $this->row2Object($form);

		$sql = $sqlKeys = [];
		foreach ($form as $settingK => $settingV) {
			if (is_array($settingV)) {
				$settingV = implode(', ', $settingV);
			}
			$sql[] = sprintf("WHEN %s THEN %s", $this->pdo->escapeString($settingK), $this->pdo->escapeString($settingV));
			$sqlKeys[] = $this->pdo->escapeString($settingK);
		}

		$this->pdo->queryExec(sprintf("UPDATE tmux SET value = CASE setting %s END WHERE setting IN (%s)", implode(' ', $sql), implode(', ', $sqlKeys)));

		return $tmux;
	}

	/**
	 * @param string $setting
	 *
	 * @return bool|\stdClass
	 */
	public function get($setting = '')
	{
		$where = ($setting !== '' ? sprintf('WHERE setting = %s', $this->pdo->escapeString($setting)) : '');

		$rows = $this->pdo->query(sprintf("SELECT * FROM tmux %s", $where));

		if ($rows === false) {
			return false;
		}

		return $this->rows2Object($rows);
	}

	/**
	 * @param $constants
	 *
	 * @return mixed
	 */
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

	/**
	 * @param $which
	 * @param $connections
	 *
	 * @return mixed
	 */
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

	/**
	 * @param $constants
	 *
	 * @return array
	 */
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

	/**
	 * @return string
	 */
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

	/**
	 * @return string
	 */
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
					(%1\$s 'import_count') AS import_count,
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
					(%2\$s 'lookupanidb') AS processanime,
					(%2\$s 'lookupnfo') AS processnfo,
					(%2\$s 'lookuppar2') AS processpar2,
					(%2\$s 'nzbthreads') AS nzbthreads,
					(%2\$s 'tmpunrarpath') AS tmpunrar,
					(%2\$s 'compressedheaders') AS compressed,
					(%2\$s 'book_reqids') AS book_reqids,
					(%2\$s 'request_hours') AS request_hours,
					(%2\$s 'maxsizetopostprocess') AS maxsize_pp,
					(%2\$s 'minsizetopostprocess') AS minsize_pp",
					$tmuxstr,
					$settstr
		);
		return $sql;
	}

	/**
	 * @param $rows
	 *
	 * @return \stdClass
	 */
	public function rows2Object($rows)
	{
		$obj = new \stdClass;
		foreach ($rows as $row) {
			$obj->{$row['setting']} = $row['value'];
		}

		$obj->{'version'} = $this->version();
		return $obj;
	}

	/**
	 * @param $row
	 *
	 * @return \stdClass
	 */
	public function row2Object($row)
	{
		$obj = new \stdClass;
		$rowKeys = array_keys($row);
		foreach ($rowKeys as $key) {
			$obj->{$key} = $row[$key];
		}
		return $obj;
	}

	/**
	 * @param $setting
	 * @param $value
	 *
	 * @return bool|\PDOStatement
	 */
	public function updateItem($setting, $value)
	{
		$sql = sprintf("UPDATE tmux SET value = %s WHERE setting = %s", $this->pdo->escapeString($value), $this->pdo->escapeString($setting));
		return $this->pdo->queryExec($sql);
	}

	//get microtime
	/**
	 * @return float
	 */
	public function microtime_float()
	{
		list($usec, $sec) = explode(" ", microtime());
		return ((float)$usec + (float)$sec);
	}

	/**
	 * @param double $bytes
	 *
	 * @return string
	 */
	public function decodeSize($bytes)
	{
		$types = ['B', 'KB', 'MB', 'GB', 'TB'];
		/*
		for ($i = 0; $bytes >= 1024 && $i < (count($types) - 1); $bytes /= 1024, $i++);

		return (round($bytes, 2) . " " . $types[$i]);
		*/

		$suffix = 'B';
		foreach ($types as $type) {
			if ($bytes < 1024.0) {
				$suffix = $type;
				break;
			}
			$bytes /= 1024;
		}
		return (round($bytes, 2) . " " . $suffix);
	}

	/**
	 * @param $pane
	 *
	 * @return string
	 */
	public function writelog($pane)
	{
		$path = nZEDb_LOGS;
		$getdate = gmdate("Ymd");
		$tmux = $this->get();
		$logs = (isset($tmux->write_logs)) ? $tmux->write_logs : 0;
		if ($logs == 1) {
			return "2>&1 | tee -a $path/$pane-$getdate.log";
		} else {
			return "";
		}
	}

	/**
	 * @param $colors_start
	 * @param $colors_end
	 * @param $colors_exc
	 *
	 * @return int
	 */
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
	/**
	 * @param     $loop
	 * @param int $chance
	 *
	 * @return bool
	 */
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

	/**
	 * @param $_time
	 *
	 * @return string
	 */
	public function relativeTime($_time)
	{
		$d = [];
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

	/**
	 * @param $cmd
	 *
	 * @return bool
	 */
	public function command_exist($cmd)
	{
		$returnVal = shell_exec("which $cmd 2>/dev/null");
		return (empty($returnVal) ? false : true);
	}

	/**
	 * @param        $qry
	 * @param        $bookreqids
	 * @param int    $request_hours
	 * @param string $db_name
	 * @param string $ppmax
	 * @param string $ppmin
	 *
	 * @return bool|string
	 */
	public function proc_query($qry, $bookreqids, $request_hours, $db_name, $ppmax = '', $ppmin = '')
	{
		switch ((int)$qry) {
			case 1:
				return sprintf("
					SELECT
					SUM(IF(nzbstatus = %d AND categories_id BETWEEN %d AND %d AND categories_id != %d AND videos_id = 0 AND tv_episodes_id BETWEEN -3 AND 0 AND size > 1048576,1,0)) AS processtv,
					SUM(IF(nzbstatus = %1\$d AND categories_id = %d AND anidbid IS NULL,1,0)) AS processanime,
					SUM(IF(nzbstatus = %1\$d AND categories_id BETWEEN %d AND %d AND imdbid IS NULL,1,0)) AS processmovies,
					SUM(IF(nzbstatus = %1\$d AND categories_id IN (%d, %d, %d) AND musicinfo_id IS NULL,1,0)) AS processmusic,
					SUM(IF(nzbstatus = %1\$d AND categories_id BETWEEN %d AND %d AND consoleinfo_id IS NULL,1,0)) AS processconsole,
					SUM(IF(nzbstatus = %1\$d AND categories_id IN (%s) AND bookinfo_id IS NULL,1,0)) AS processbooks,
					SUM(IF(nzbstatus = %1\$d AND categories_id = %d AND gamesinfo_id = 0,1,0)) AS processgames,
					SUM(IF(nzbstatus = %1\$d AND categories_id BETWEEN %d AND %d AND xxxinfo_id = 0,1,0)) AS processxxx,
					SUM(IF(1=1 %s,1,0)) AS processnfo,
					SUM(IF(nzbstatus = %1\$d AND isrenamed = %d AND predb_id = 0 AND passwordstatus >= 0 AND nfostatus > %d
						AND ((nfostatus = %d AND proc_nfo = %d) OR proc_files = %d OR proc_uid = %d OR proc_par2 = %d OR (nfostatus = %20\$d AND proc_sorter = %d)
							OR (ishashed = 1 AND dehashstatus BETWEEN -6 AND 0)) AND categories_id IN (%s),1,0)) AS processrenames,
					SUM(IF(isrenamed = %d,1,0)) AS renamed,
					SUM(IF(nzbstatus = %1\$d AND nfostatus = %20\$d,1,0)) AS nfo,
					SUM(IF(nzbstatus = %1\$d AND isrequestid = %d AND predb_id = 0 AND ((reqidstatus = %d) OR (reqidstatus = %d) OR (reqidstatus = %d AND adddate > NOW() - INTERVAL %s HOUR)),1,0)) AS requestid_inprogress,
					SUM(IF(predb_id > 0 AND nzbstatus = %1\$d AND isrequestid = %28\$d AND reqidstatus = %d,1,0)) AS requestid_matched,
					SUM(IF(predb_id > 0,1,0)) AS predb_matched,
					COUNT(DISTINCT(predb_id)) AS distinct_predb_matched
					FROM releases r",
					NZB::NZB_ADDED,
					Category::TV_ROOT,
					Category::TV_OTHER,
					Category::TV_ANIME,
					Category::TV_ANIME,
					Category::MOVIE_ROOT,
					Category::MOVIE_OTHER,
					Category::MUSIC_MP3,
					Category::MUSIC_LOSSLESS,
					Category::MUSIC_OTHER,
					Category::GAME_ROOT,
					Category::GAME_OTHER,
					$bookreqids,
					Category::PC_GAMES,
					Category::XXX_ROOT,
					Category::XXX_X264,
					Nfo::NfoQueryString($this->pdo),
					NameFixer::IS_RENAMED_NONE,
					Nfo::NFO_UNPROC,
					Nfo::NFO_FOUND,
					NameFixer::PROC_NFO_NONE,
					NameFixer::PROC_FILES_NONE,
					NameFixer::PROC_UID_NONE,
					NameFixer::PROC_PAR2_NONE,
					MiscSorter::PROC_SORTER_NONE,
					Category::getCategoryOthersGroup(),
					NameFixer::IS_RENAMED_DONE,
					RequestID::IS_REQID_TRUE,
					RequestID::REQID_UPROC,
					RequestID::REQID_NOLL,
					RequestID::REQID_NONE,
					RequestID::REQID_FOUND,
					$request_hours
				);

			case 2:
				$ppminString = $ppmaxString = '';
				if (is_numeric($ppmax) && !empty($ppmax)) {
					$ppmax *= 1073741824;
					$ppmaxString = "AND r.size < {$ppmax}";
				}
				if (is_numeric($ppmin) && !empty($ppmin)) {
					$ppmin *= 1048576;
					$ppminString = "AND r.size > {$ppmin}";
				}
				return "SELECT
					(SELECT COUNT(r.id) FROM releases r
						LEFT JOIN categories c ON c.id = r.categories_id
						WHERE r.nzbstatus = 1
						AND r.passwordstatus BETWEEN -6 AND -1
						AND r.haspreview = -1
						{$ppminString}
						{$ppmaxString}
						AND c.disablepreview = 0
					) AS work,
					(SELECT COUNT(id) FROM groups WHERE active = 1) AS active_groups,
					(SELECT COUNT(id) FROM groups WHERE name IS NOT NULL) AS all_groups";

			case 4:
				return sprintf("
					SELECT
					(SELECT TABLE_ROWS FROM information_schema.TABLES WHERE table_name = 'predb' AND TABLE_SCHEMA = %1\$s) AS predb,
					(SELECT TABLE_ROWS FROM information_schema.TABLES WHERE table_name = 'missed_parts' AND TABLE_SCHEMA = %1\$s) AS missed_parts_table,
					(SELECT TABLE_ROWS FROM information_schema.TABLES WHERE table_name = 'parts' AND TABLE_SCHEMA = %1\$s) AS parts_table,
					(SELECT TABLE_ROWS FROM information_schema.TABLES WHERE table_name = 'binaries' AND TABLE_SCHEMA = %1\$s) AS binaries_table,
					(SELECT TABLE_ROWS FROM information_schema.TABLES WHERE table_name = 'collections' AND TABLE_SCHEMA = %1\$s) AS collections_table,
					(SELECT TABLE_ROWS FROM information_schema.TABLES WHERE table_name = 'releases' AND TABLE_SCHEMA = %1\$s) AS releases,
					(SELECT COUNT(id) FROM groups WHERE first_record IS NOT NULL AND backfill = 1
						AND (now() - INTERVAL backfill_target DAY) < first_record_postdate
					) AS backfill_groups_days,
					(SELECT COUNT(id) FROM groups WHERE first_record IS NOT NULL AND backfill = 1 AND (now() - INTERVAL datediff(curdate(),
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
	 * @return bool true if tmux is running, false otherwise.
	 */
	public function isRunning()
	{
		$running = $this->get()->running;
		if ($running === false) {
			throw new \RuntimeException("Tmux's running flag was not found in the database.\nPlease check the tables are correctly setup.\n");
		}
		return ($running == 1);
	}

	/**
	 * Check if Tmux is running, if it is, stop it.
	 *
	 * @return bool true if scripts were running, false otherwise.
	 * @access public
	 */
	public function stopIfRunning()
	{
		if ($this->isRunning() == 1) {
			$this->pdo->queryExec("UPDATE tmux SET value = '0' WHERE setting = 'running'");
			$sleep = $this->get()->monitor_delay;
			echo $this->pdo->log->header("Stopping tmux scripts and waiting $sleep seconds for all panes to shutdown");
			sleep($sleep);
			return true;
		}
		return false;
	}

	/**
	 * @return bool|\PDOStatement
	 */
	public function startRunning()
	{
		if (!$this->isRunning()) {
			return $this->pdo->queryExec("UPDATE tmux SET value = '1' WHERE setting = 'running'");
		}
		return true;
	}

	/**
	 * Retrieves and returns ALL collections, binaries, parts, and missed parts table names from the Db
	 *
	 * @return bool|\PDOStatement
	 */
	public function cbpmTableQuery()
	{
		$regstr = '^(multigroup_)?(collections|binaries|parts|missed_parts)(_[0-9]+)?$';

		return $this->pdo->queryDirect("
			SELECT TABLE_NAME AS name
      		FROM information_schema.TABLES
      		WHERE TABLE_SCHEMA = (SELECT DATABASE())
			AND TABLE_NAME REGEXP {$this->pdo->escapeString($regstr)}
			ORDER BY TABLE_NAME ASC
		);
	}
}
