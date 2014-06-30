<?php
require_once dirname(__FILE__) . '/../../../../www/config.php';

use nzedb\db\Settings;

$c = new ColorCLI();
$versions = \nzedb\utility\Utility::getValidVersionsFile();

//exec('git log | grep "^commit" | wc -l', $commit);
$git = new \nzedb\utility\Git();

$version = $versions->versions->git->tag . 'r' . $git->commits();

$db = new Settings();
$DIR = nZEDb_MISC;
$db_name = DB_NAME;
$dbtype = DB_SYSTEM;

$t = new Tmux();
$tmux = $t->get();
$seq = (isset($tmux->sequential)) ? $tmux->sequential : 0;
$powerline = (isset($tmux->powerline)) ? $tmux->powerline : 0;
$run_ircscraper = $tmux->run_ircscraper;

$s = new Sites();
$site = $s->get();
$patch = $site->sqlpatch;
$alternate_nntp = ($site->alternate_nntp === '1') ? true : false;
$tablepergroup = (isset($site->tablepergroup)) ? $site->tablepergroup : 0;
$delay = (isset($site->delaytime)) ? $site->delaytime : 2;
$nntpproxy = (isset($site->nntpproxy)) ? $site->nntpproxy : 0;
$bookreqids = ($site->book_reqids == null || $site->book_reqids == "") ? 8010 : $site->book_reqids;
$request_hours = (isset($site->request_hours)) ? $site->request_hours : 1;

if (command_exist("python3")) {
	$PYTHON = "python3 -OOu";
} else {
	$PYTHON = "python -OOu";
}

if (command_exist("php5")) {
	$PHP = "php5";
} else {
	$PHP = "php";
}

if ($nntpproxy == 0) {
	$port = NNTP_PORT;
	$host = NNTP_SERVER;
	$ip = gethostbyname($host);
	if ($alternate_nntp) {
		$port_a = NNTP_PORT_A;
		$host_a = NNTP_SERVER_A;
		$ip_a = gethostbyname($host_a);
	}
} else {
	$filename = $DIR . "update/python/lib/nntpproxy.conf";
	$fp = fopen($filename, "r") or die("Couldn't open $filename");
	while (!feof($fp)) {
		$line = fgets($fp);
		if (preg_match('/"host": "(.+)",$/', $line, $match)) {
			$host = $match[1];
		}
		if (preg_match('/"port": (.+),$/', $line, $match)) {
			$port = $match[1];
			break;
		}
	}

	if ($alternate_nntp) {
		$filename = $DIR . "update/python/lib/nntpproxy_a.conf";
		$fp = fopen($filename, "r") or die("Couldn't open $filename");
		while (!feof($fp)) {
			$line = fgets($fp);
			if (preg_match('/"host": "(.+)",$/', $line, $match)) {
				$host_a = $match[1];
			}
			if (preg_match('/"port": (.+),$/', $line, $match)) {
				$port_a = $match[1];
				break;
			}
		}
	}
	$ip = gethostbyname($host);
	if ($alternate_nntp) {
		$ip_a = gethostbyname($host_a);
	}
}

// Returns random bool, weighted by $chance
function rand_bool($loop, $chance = 60)
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

//totals per category in db, results by parentID
$qry = "SELECT c.parentid AS parentid, COUNT(r.id) AS count FROM category c, releases r WHERE r.categoryid = c.id GROUP BY c.parentid";

//needs to be processed query
$proc_work = "SELECT "
	. "(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND categoryid BETWEEN 5000 AND 5999 AND rageid = -1) AS tv, "
	. "(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND categoryid BETWEEN 2000 AND 2999 AND imdbid IS NULL) AS movies, "
	. "(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND categoryid IN (3010, 3040, 3050) AND musicinfoid IS NULL) AS audio, "
	. "(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND categoryid BETWEEN 1000 AND 1999 AND consoleinfoid IS NULL) AS console, "
	. "(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND categoryid IN (" . $bookreqids . ") AND bookinfoid IS NULL) AS book, "
	. "(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1) AS releases, "
	. "(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND nfostatus = 1) AS nfo, "
	. "(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND nfostatus BETWEEN -6 AND -1) AS nforemains";

$proc_work2 = "SELECT "
	. "(SELECT COUNT(*) FROM releases r INNER JOIN category c WHERE r.nzbstatus = 1 AND c.id = r.categoryid AND r.categoryid BETWEEN 4000 AND 4999 AND r.passwordstatus BETWEEN -6 AND -1 AND r.haspreview = -1 AND c.disablepreview = 0) AS pc, "
	. "(SELECT COUNT(*) FROM releases r INNER JOIN category c WHERE r.nzbstatus = 1 AND c.id = r.categoryid AND r.categoryid BETWEEN 6000 AND 6999 AND r.passwordstatus BETWEEN -6 AND -1 AND r.haspreview = -1 AND c.disablepreview = 0) AS pron, "
	. "(SELECT COUNT(*) FROM releases r INNER JOIN category c WHERE r.nzbstatus = 1 AND c.id = r.categoryid AND r.passwordstatus BETWEEN -6 AND -1 AND r.haspreview = -1 AND c.disablepreview = 0) AS work, "
	. "(SELECT COUNT(*) FROM collections WHERE collectionhash IS NOT NULL) AS collections_table, "
	. "(SELECT COUNT(*) FROM partrepair WHERE attempts < 5) AS partrepair_table";

$proc_work3 = "SELECT "
	. "(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND isrequestid = 1 AND preid = 0 AND reqidstatus in (0, -1)) AS requestid_local, "
	. "(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND isrequestid = 1 AND preid = 0 AND reqidstatus = -3 AND adddate > NOW() - INTERVAL " . $request_hours . " HOUR) AS requestid_web, "
	. "(SELECT COUNT(*) FROM releases WHERE nzbstatus = 1 AND isrequestid = 1 AND reqidstatus = 1) AS requestid_matched, "
	. "(SELECT COUNT(*) FROM releases WHERE preid > 0) AS predb_matched, "
	. "(SELECT COUNT(DISTINCT(preid)) FROM releases WHERE preid > 0) AS distinct_predb_matched, "
	. "(SELECT COUNT(*) FROM binaries WHERE collectionid IS NOT NULL) AS binaries_table";

if ($dbtype == 'mysql') {
	$split_query = "SELECT "
		. "(SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES where table_name = 'predb' AND TABLE_SCHEMA = '$db_name') AS predb, "
		. "(SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES where table_name = 'parts' AND TABLE_SCHEMA = '$db_name') AS parts_table, "
		. "(SELECT COUNT(*) FROM groups WHERE first_record IS NOT NULL AND backfill = 1 AND (now() - interval backfill_target day) < first_record_postdate) AS backfill_groups_days, "
		. "(SELECT COUNT(*) FROM groups WHERE first_record IS NOT NULL AND backfill = 1 AND (now() - interval datediff(curdate(), "
		. "(SELECT VALUE FROM settings WHERE SETTING = 'safebackfilldate')) day) < first_record_postdate) AS backfill_groups_date, "
		. "(SELECT UNIX_TIMESTAMP(dateadded) FROM collections ORDER BY dateadded ASC LIMIT 1) AS oldestcollection, "
		. "(SELECT UNIX_TIMESTAMP(predate) FROM predb ORDER BY predate DESC LIMIT 1) AS newestpre, "
		. "(SELECT UNIX_TIMESTAMP(adddate) FROM releases WHERE nzbstatus = 1 ORDER BY adddate DESC LIMIT 1) AS newestadd";
} else if ($dbtype == 'pgsql') {
	$split_query = "SELECT "
		. "(SELECT COUNT(*) FROM predb WHERE id IS NOT NULL) AS predb, "
		. "(SELECT COUNT(*) FROM parts WHERE id IS NOT NULL) AS parts_table, "
		. "(SELECT COUNT(*) FROM groups WHERE first_record IS NOT NULL AND backfill = 1 AND (current_timestamp - backfill_target * interval '1 days') < first_record_postdate) AS backfill_groups_days, "
		. "(SELECT COUNT(*) FROM groups WHERE first_record IS NOT NULL AND backfill = 1 AND (current_timestamp - (date(current_date::date) - date((SELECT value FROM settings WHERE setting = 'safebackfilldate')::date)) * interval '1 days') < first_record_postdate) AS backfill_groups_date, "
		. "(SELECT extract(epoch FROM dateadded) FROM collections ORDER BY dateadded ASC LIMIT 1) AS oldestcollection, "
		. "(SELECT extract(epoch FROM predate) FROM predb ORDER BY predate DESC LIMIT 1) AS newestpre, "
		. "(SELECT extract(epoch FROM adddate) FROM releases WHERE nzbstatus = 1 ORDER BY adddate DESC LIMIT 1) AS newestadd";
}

// tmux and site settings, refreshes every loop
$proc_tmux = "SELECT "
	. "(SELECT searchname FROM releases ORDER BY adddate DESC LIMIT 1) AS newestname, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'monitor_delay') AS monitor, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'tmux_session') AS tmux_session, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'niceness') AS niceness, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'binaries') AS binaries_run, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'backfill') AS backfill, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'import') AS import, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'nzbs') AS nzbs, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'post') AS post, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'releases') AS releases_run, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'releases_threaded') AS releases_threaded, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'fix_names') as fix_names, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'seq_timer') as seq_timer, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'bins_timer') as bins_timer, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'back_timer') as back_timer, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'import_timer') as import_timer, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'rel_timer') as rel_timer, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'fix_timer') as fix_timer, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'post_timer') as post_timer, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'collections_kill') as collections_kill, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'postprocess_kill') as postprocess_kill, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'crap_timer') as crap_timer, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'fix_crap') as fix_crap, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'fix_crap_opt') as fix_crap_opt, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'tv_timer') as tv_timer, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'update_tv') as update_tv, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'post_kill_timer') as post_kill_timer, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'monitor_path') as monitor_path, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'monitor_path_a') as monitor_path_a, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'monitor_path_b') as monitor_path_b, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'progressive') as progressive, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'dehash') as dehash, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'dehash_timer') as dehash_timer, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'backfill_days') as backfilldays, "
	. "(SELECT VALUE FROM settings WHERE SETTING = 'lookupbooks') as processbooks, "
	. "(SELECT VALUE FROM settings WHERE SETTING = 'lookupmusic') as processmusic, "
	. "(SELECT VALUE FROM settings WHERE SETTING = 'lookupgames') as processgames, "
	. "(SELECT VALUE FROM settings WHERE SETTING = 'tmpunrarpath') as tmpunrar, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'post_amazon') as post_amazon, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'post_timer_amazon') as post_timer_amazon, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'post_non') as post_non, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'post_timer_non') as post_timer_non, "
	. "(SELECT COUNT(*) FROM groups WHERE active = 1) AS active_groups, "
	. "(SELECT COUNT(*) FROM groups WHERE name IS NOT NULL) AS all_groups, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'colors_start') AS colors_start, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'colors_end') AS colors_end, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'colors_exc') AS colors_exc, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'showquery') AS show_query, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'running') AS is_running, "
	. "(SELECT VALUE FROM tmux WHERE SETTING = 'sharing_timer') AS sharing_timer, "
	. "(SELECT value FROM settings WHERE setting = 'compressedheaders') AS compressed";

//get microtime
function microtime_float()
{
	list($usec, $sec) = explode(" ", microtime());

	return ((float)$usec + (float)$sec);
}

function decodeSize($bytes)
{
	$types = array('B', 'KB', 'MB', 'GB', 'TB');
	for ($i = 0; $bytes >= 1024 && $i < (count($types) - 1); $bytes /= 1024, $i++)
		;

	return (round($bytes, 2) . " " . $types[$i]);
}

function writelog($pane)
{
	$path = dirname(__FILE__) . "/logs";
	$getdate = gmDate("Ymd");
	$t = new Tmux();
	$tmux = $t->get();
	$logs = (isset($tmux->write_logs)) ? $tmux->write_logs : 0;
	if ($logs == 1) {
		return "2>&1 | tee -a $path/$pane-$getdate.log";
	} else {
		return "";
	}
}

function get_color($colors_start, $colors_end, $colors_exc)
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

function relativeTime($_time)
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

function command_exist($cmd)
{
	$returnVal = shell_exec("which $cmd 2>/dev/null");

	return (empty($returnVal) ? false : true);
}

//create timers
$time = TIME();
$time1 = TIME();
$time2 = TIME();
$time3 = TIME();
$time4 = TIME();
$time5 = TIME();
$time6 = TIME();
$time7 = TIME();

// variables
$newestadd = TIME();
$newestname = "";
$newestpre = TIME();
$oldestcollection = TIME();
$oldestnzb = TIME();

$active_groups = $all_groups = $running = 0;
$backfilldays = $backfill_groups_date = $colors_exc = 0;
$book_diff = $book_percent = $book_releases_now = $book_releases_proc = 0;
$console_diff = $console_percent = $console_releases_now = $console_releases_proc = 0;
$misc_diff = $misc_percent = $misc_releases_now = $work_start = $compressed = 0;
$music_diff = $music_percent = $music_releases_proc = $music_releases_now = 0;
$movie_diff = $movie_percent = $movie_releases_now = $movie_releases_proc = 0;
$nfo_diff = $nfo_percent = $nfo_remaining_now = $nfo_now = $tvrage_releases_proc_start = 0;
$pc_diff = $pc_percent = $pc_releases_now = $pc_releases_proc = $book_releases_proc_start = 0;
$pre_diff = $pre_percent = $predb_matched = $predb_start = $predb = $distinct_predb_matched = 0;
$pron_diff = $pron_remaining_start = $pron_remaining_now = $pron_start = $pron_percent = $pron_releases_now = 0;
$nfo_remaining_start = $work_remaining_start = $releases_start = $releases_now = $releases_since_start = 0;
$request_percent = $requestid_inprogress_start = $requestid_inprogress = $requestid_diff = $requestid_matched = 0;
$total_work_now = $work_diff = $work_remaining_now = $pc_releases_proc_start = 0;
$tvrage_diff = $tvrage_percent = $tvrage_releases_now = $tvrage_releases_proc = 0;
$usp1activeconnections = $usp1totalconnections = $usp2activeconnections = $usp2totalconnections = 0;
$collections_table = $parts_table = $binaries_table = $partrepair_table = 0;
$music_releases_proc_start = 0;
$tmux_time = $split_time = $init_time = $proc1_time = $proc2_time = $proc3_time = $split1_time = 0;
$init1_time = $proc11_time = $proc21_time = $proc31_time = $tpg_count_time = $tpg_count_1_time = 0;
$console_releases_proc_start = $movie_releases_proc_start = $show_query = $run_releases = 0;
$last_history = "";

// Analyze tables
printf($c->info("\nAnalyzing your tables to refresh your indexes."));
$db->optimise(true, 'analyze');

$mask1 = $c->headerOver("%-18s") . " " . $c->tmuxOrange("%-48.48s");
$mask2 = $c->headerOver("%-20s") . " " . $c->tmuxOrange("%-33.33s");
$mask3 = $c->header("%-16.16s %25.25s %25.25s");
$mask4 = $c->primaryOver("%-16.16s") . " " . $c->tmuxOrange("%25.25s %25.25s");
$mask5 = $c->tmuxOrange("%-16.16s %25.25s %25.25s");

//create display
passthru('clear');
//printf("\033[1;31m First insert:\033[0m ".relativeTime("$firstdate")."\n");
if ($running == 1) {
	printf($mask2, "Monitor Running v$version [" . $patch . "]: ", relativeTime("$time"));
} else {
	printf($mask2, "Monitor Off v$version [" . $patch . "]: ", relativeTime("$time"));
}
printf($mask1, "USP Connections:", $usp1activeconnections . " active (" . $usp1totalconnections . " total) - " . $host . ":" . $port);
if ($alternate_nntp) {
	printf($mask1, "USP Alternate:", $usp2activeconnections . " active (" . $usp2totalconnections . " total) - " . (($alternate_nntp) ? $host_a . ":" . $port_a : "n/a"));
}
printf($mask1, "Newest Release:", "$newestname");
printf($mask1, "Release Added:", relativeTime("$newestadd") . "ago");
printf($mask1, "Predb Updated:", relativeTime("$newestpre") . "ago");
printf($mask1, "Collection Age[${delay}]:", relativeTime("$oldestcollection") . "ago");
printf($mask1, "Parts in Repair:", number_format($partrepair_table));
echo "\n";
printf($mask3, "Collections", "Binaries", "Parts");
printf($mask3, "======================================", "=========================", "======================================");
printf($mask5, number_format($collections_table), number_format($binaries_table), number_format($parts_table));
echo "\n";

printf($mask3, "Category", "In Process", "In Database");
printf($mask3, "======================================", "=========================", "======================================");
printf($mask4, "predb", number_format($predb - $distinct_predb_matched) . "(" . $pre_diff . ")", number_format($predb_matched) . "(" . $pre_percent . "%)");
printf($mask4, "requestID", $requestid_inprogress . "(" . $requestid_diff . ")", number_format($requestid_matched) . "(" . $request_percent . "%)");
printf($mask4, "NFO's", number_format($nfo_remaining_now) . "(" . $nfo_diff . ")", number_format($nfo_now) . "(" . $nfo_percent . "%)");
printf($mask4, "Console(1000)", number_format($console_releases_proc) . "(" . $console_diff . ")", number_format($console_releases_now) . "(" . $console_percent . "%)");
printf($mask4, "Movie(2000)", number_format($movie_releases_proc) . "(" . $movie_diff . ")", number_format($movie_releases_now) . "(" . $movie_percent . "%)");
printf($mask4, "Audio(3000)", number_format($music_releases_proc) . "(" . $music_diff . ")", number_format($music_releases_now) . "(" . $music_percent . "%)");
printf($mask4, "PC(4000)", number_format($pc_releases_proc) . "(" . $pc_diff . ")", number_format($pc_releases_now) . "(" . $pc_percent . "%)");
printf($mask4, "TVShows(5000)", number_format($tvrage_releases_proc) . "(" . $tvrage_diff . ")", number_format($tvrage_releases_now) . "(" . $tvrage_percent . "%)");
printf($mask4, "xXx(6000)", number_format($pron_remaining_now) . "(" . $pron_diff . ")", number_format($pron_releases_now) . "(" . $pron_percent . "%)");
printf($mask4, "Misc(7000)", number_format($work_remaining_now) . "(" . $misc_diff . ")", number_format($misc_releases_now) . "(" . $misc_percent . "%)");
printf($mask4, "Books(8000)", number_format($book_releases_proc) . "(" . $book_diff . ")", number_format($book_releases_now) . "(" . $book_percent . "%)");
printf($mask4, "Total", number_format($total_work_now) . "(" . $work_diff . ")", number_format($releases_now) . "(" . $releases_since_start . ")");
echo "\n";
printf($mask3, "Groups", "Active", "Backfill");
printf($mask3, "======================================", "=========================", "======================================");
if ($backfilldays == "1") {
	printf($mask4, "Activated", $active_groups . "(" . $all_groups . ")", $backfill_groups_days . "(" . $all_groups . ")");
} else {
	printf($mask4, "Activated", $active_groups . "(" . $all_groups . ")", $backfill_groups_date . "(" . $all_groups . ")");
}
echo "\n";
if ($show_query == 1) {
	printf($mask3, "Query Block", "Time", "Cumulative");
	printf($mask3, "======================================", "=========================", "======================================");
	printf($mask4, "Combined", "0", "0");
}

$monitor = 0;
$i = 1;
$fcfirstrun = true;
$fcnum = 0;

while ($i > 0) {
	//kill mediainfo and ffmpeg if exceeds 60 sec
	shell_exec("killall -o 60s -9 mediainfo 2>&1 1> /dev/null");
	shell_exec("killall -o 60s -9 ffmpeg 2>&1 1> /dev/null");

	//check the db connection
	if ($db->ping(true) == false) {
		unset($db);
		$db = new Settings();
	}

	// These queries are very fast, run every loop
	$time01 = TIME();
	$proc_tmux_result = $db->query($proc_tmux, false);
	$tmux_time = (TIME() - $time01);

	//run queries only after time exceeded, these queries can take awhile
	if ($i == 1 || (TIME() - $time1 >= $monitor && $running == 1)) {
		echo $c->info("\nThe numbers(queries) above are currently being refreshed. \nNo pane(script) can be (re)started until these have completed.\n");
		$time02 = TIME();
		$split_result = $db->query($split_query, false);
		$split_time = (TIME() - $time02);
		$split1_time = (TIME() - $time01);

		$time03 = TIME();
		$initquery = $db->query($qry, false);
		$init_time = (TIME() - $time03);
		$init1_time = (TIME() - $time01);

		$time04 = TIME();
		$proc_work_result = $db->query($proc_work, rand_bool($i));
		$proc1_time = (TIME() - $time04);
		$proc11_time = (TIME() - $time01);

		$time05 = TIME();
		$proc_work_result2 = $db->query($proc_work2, rand_bool($i));
		$proc2_time = (TIME() - $time05);
		$proc21_time = (TIME() - $time01);

		$time06 = TIME();
		$proc_work_result3 = $db->query($proc_work3, rand_bool($i));
		$proc3_time = (TIME() - $time06);
		$proc31_time = (TIME() - $time01);

		$time07 = TIME();
		if ($tablepergroup == 1) {
			if ($db->dbSystem() === 'mysql') {
				$sql = 'SHOW table status';
			} else {
				$sql = "SELECT relname FROM pg_class WHERE relname !~ '^(pg_|sql_)' AND relkind = 'r'";
			}
			$tables = $db->queryDirect($sql);
			$collections_table = $binaries_table = $parts_table = $partrepair_table = 0;
			$age = TIME();
			if (count($tables) > 0) {
				foreach ($tables as $row) {
					if ($db->dbSystem() === 'mysql') {
						$tbl = $row['name'];
						$stamp = 'UNIX_TIMESTAMP(dateadded)';
					} else {
						$tbl = $row['relname'];
						$stamp = 'extract(epoch FROM dateadded)';
					}
					if (strpos($tbl, 'collections_') !== false) {
						$run = $db->query('SELECT COUNT(*) AS count FROM ' . $tbl, rand_bool($i));
						$collections_table += $run[0]['count'];
						$run1 = $db->query('SELECT ' . $stamp . ' AS dateadded FROM ' . $tbl . ' ORDER BY dateadded ASC LIMIT 1', rand_bool($i));
						if (isset($run1[0]['dateadded']) && is_numeric($run1[0]['dateadded']) && $run1[0]['dateadded'] < $age) {
							$age = $run1[0]['dateadded'];
						}
					} else if (strpos($tbl, 'binaries_') !== false) {
						$run = $db->query('SELECT COUNT(*) AS count FROM ' . $tbl, rand_bool($i));
						if (isset($run[0]['count']) && is_numeric($run[0]['count'])) {
							$binaries_table += $run[0]['count'];
						}
					} else if (strpos($tbl, 'parts_') !== false) {
						$run = $db->query('SELECT COUNT(*) AS count FROM ' . $tbl, rand_bool($i));
						if (isset($run[0]['count']) && is_numeric($run[0]['count'])) {
							$parts_table += $run[0]['count'];
						}
					} else if (strpos($tbl, 'partrepair_') !== false) {
						$run = $db->query('SELECT COUNT(*) AS count FROM ' . $tbl, rand_bool($i));
						if (isset($run[0]['count']) && is_numeric($run[0]['count'])) {
							$partrepair_table += $run[0]['count'];
						}
					}
				}
				$oldestcollection = $age;
				$tpg_count_time = (TIME() - $time07);
				$tpg_count_1_time = (TIME() - $time01);
			}
		}
		$time1 = TIME();
	}

	if (!isset($proc_work_result[0])) {
		$proc_work_result = $db->query($proc_work, rand_bool($i));
	}
	if (!isset($proc_work_result2[0])) {
		$proc_work_result2 = $db->query($proc_work2, rand_bool($i));
	}
	if (!isset($proc_work_result3[0])) {
		$proc_work_result3 = $db->query($proc_work3, rand_bool($i));
	}

	//get start values from $qry
	if ($i == 1) {
		if ($proc_work_result[0]['nforemains'] != null) {
			$nfo_remaining_start = $proc_work_result[0]['nforemains'];
		}
		if ($proc_work_result3[0]['predb_matched'] != null) {
			$predb_start = $proc_work_result3[0]['predb_matched'];
		}
		if ($proc_work_result[0]['console'] != null) {
			$console_releases_proc_start = $proc_work_result[0]['console'];
		}
		if ($proc_work_result[0]['movies'] != null) {
			$movie_releases_proc_start = $proc_work_result[0]['movies'];
		}
		if ($proc_work_result[0]['audio'] != null) {
			$music_releases_proc_start = $proc_work_result[0]['audio'];
		}
		if ($proc_work_result2[0]['pc'] != null) {
			$pc_releases_proc_start = $proc_work_result2[0]['pc'];
		}
		if ($proc_work_result[0]['tv'] != null) {
			$tvrage_releases_proc_start = $proc_work_result[0]['tv'];
		}
		if ($proc_work_result[0]['book'] != null) {
			$book_releases_proc_start = $proc_work_result[0]['book'];
		}
		if ($proc_work_result2[0]['work'] != null) {
			$work_start = $proc_work_result2[0]['work'] - $proc_work_result2[0]['pc'] - $proc_work_result2[0]['pron'];
		}
		if ($proc_work_result2[0]['pron'] != null) {
			$pron_remaining_start = $proc_work_result2[0]['pron'];
		}
		if ($proc_work_result2[0]['pron'] != null) {
			$pron_start = $proc_work_result2[0]['pron'];
		}
		if ($proc_work_result[0]['releases'] != null) {
			$releases_start = $proc_work_result[0]['releases'];
		}
		if ($proc_work_result3[0]['requestid_local'] != null || $proc_work_result3[0]['requestid_web'] != null) {
			$requestid_inprogress_start = $proc_work_result3[0]['requestid_local'] + $proc_work_result3[0]['requestid_web'];
		}
		if ($proc_work_result2[0]['work'] != null) {
			$work_remaining_start = $proc_work_result2[0]['work'] - $proc_work_result2[0]['pc'] - $proc_work_result2[0]['pron'];
		}
	}

	//get values from $qry
	foreach ($initquery as $cat) {
		if ($cat['parentid'] == 1000) {
			$console_releases_now = $cat['count'];
		}
		if ($cat['parentid'] == 2000) {
			$movie_releases_now = $cat['count'];
		}
		if ($cat['parentid'] == 3000) {
			$music_releases_now = $cat['count'];
		}
		if ($cat['parentid'] == 4000) {
			$pc_releases_now = $cat['count'];
		}
		if ($cat['parentid'] == 5000) {
			$tvrage_releases_now = $cat['count'];
		}
		if ($cat['parentid'] == 6000) {
			$pron_releases_now = $cat['count'];
		}
		if ($cat['parentid'] == 7000) {
			$misc_releases_now = $cat['count'];
		}
		if ($cat['parentid'] == 8000) {
			$book_releases_now = $cat['count'];
		}
	}


	//get values from $proc
	if ($proc_work_result[0]['console'] != null) {
		$console_releases_proc = $proc_work_result[0]['console'];
	}
	if ($proc_work_result[0]['movies'] != null) {
		$movie_releases_proc = $proc_work_result[0]['movies'];
	}
	if ($proc_work_result[0]['audio'] != null) {
		$music_releases_proc = $proc_work_result[0]['audio'];
	}
	if ($proc_work_result2[0]['pc'] != null) {
		$pc_releases_proc = $proc_work_result2[0]['pc'];
	}
	if ($proc_work_result[0]['tv'] != null) {
		$tvrage_releases_proc = $proc_work_result[0]['tv'];
	}
	if ($proc_work_result[0]['book'] != null) {
		$book_releases_proc = $proc_work_result[0]['book'];
	}
	if ($proc_work_result2[0]['work'] != null) {
		$work_remaining_now = $proc_work_result2[0]['work'] - $proc_work_result2[0]['pc'] - $proc_work_result2[0]['pron'];
	}
	if ($proc_work_result2[0]['pron'] != null) {
		$pron_remaining_now = $proc_work_result2[0]['pron'];
	}
	if ($proc_work_result[0]['releases'] != null) {
		$releases_loop = $proc_work_result[0]['releases'];
	}
	if ($proc_work_result[0]['nforemains'] != null) {
		$nfo_remaining_now = $proc_work_result[0]['nforemains'];
	}
	if ($proc_work_result[0]['nfo'] != null) {
		$nfo_now = $proc_work_result[0]['nfo'];
	}

	if ($tablepergroup == 0) {
		if ($proc_work_result3[0]['binaries_table'] != null) {
			$binaries_table = $proc_work_result3[0]['binaries_table'];
		}
		if ($split_result[0]['parts_table'] != null) {
			$parts_table = $split_result[0]['parts_table'];
		}
		if ($proc_work_result2[0]['collections_table'] != null) {
			$collections_table = $proc_work_result2[0]['collections_table'];
		}
		if ($proc_work_result2[0]['partrepair_table'] != null) {
			$partrepair_table = $proc_work_result2[0]['partrepair_table'];
		}
	}

	if ($split_result[0]['predb'] != null) {
		$predb = $split_result[0]['predb'];
		$nowTime = time();
		if ($predb > $nowTime) {
			$predb = $nowTime;
		}
	}

	if ($proc_work_result3[0]['predb_matched'] != null) {
		$predb_matched = $proc_work_result3[0]['predb_matched'];
	}
	if ($proc_work_result3[0]['distinct_predb_matched'] != null) {
		$distinct_predb_matched = $proc_work_result3[0]['distinct_predb_matched'];
	}
	if ($proc_work_result3[0]['requestid_local'] != null || $proc_work_result3[0]['requestid_web'] != null) {
		$requestid_inprogress = $proc_work_result3[0]['requestid_local'] + $proc_work_result3[0]['requestid_web'];
	}
	if ($proc_work_result3[0]['requestid_matched'] != null) {
		$requestid_matched = $proc_work_result3[0]['requestid_matched'];
	}

	if ($proc_tmux_result[0]['collections_kill'] != null) {
		$collections_kill = $proc_tmux_result[0]['collections_kill'];
	}
	if ($proc_tmux_result[0]['postprocess_kill'] != null) {
		$postprocess_kill = $proc_tmux_result[0]['postprocess_kill'];
	}
	if ($proc_tmux_result[0]['backfilldays'] != null) {
		$backfilldays = $proc_tmux_result[0]['backfilldays'];
	}
	if ($proc_tmux_result[0]['tmpunrar'] != null) {
		$tmpunrar = $proc_tmux_result[0]['tmpunrar'];
	}
	if ($proc_tmux_result[0]['active_groups'] != null) {
		$active_groups = $proc_tmux_result[0]['active_groups'];
	}
	if ($proc_tmux_result[0]['all_groups'] != null) {
		$all_groups = $proc_tmux_result[0]['all_groups'];
	}
	if ($proc_tmux_result[0]['compressed'] != null) {
		$compressed = $proc_tmux_result[0]['compressed'];
	}

	if ($proc_tmux_result[0]['colors_start'] != null) {
		$colors_start = $proc_tmux_result[0]['colors_start'];
	}
	if ($proc_tmux_result[0]['colors_end'] != null) {
		$colors_end = $proc_tmux_result[0]['colors_end'];
	}
	if ($proc_tmux_result[0]['colors_exc'] != null) {
		$colors_exc = $proc_tmux_result[0]['colors_exc'];
	}

	if ($proc_tmux_result[0]['processbooks'] != null) {
		$processbooks = $proc_tmux_result[0]['processbooks'];
	}
	if ($proc_tmux_result[0]['processmusic'] != null) {
		$processmusic = $proc_tmux_result[0]['processmusic'];
	}
	if ($proc_tmux_result[0]['processgames'] != null) {
		$processgames = $proc_tmux_result[0]['processgames'];
	}
	if ($proc_tmux_result[0]['tmux_session'] != null) {
		$tmux_session = $proc_tmux_result[0]['tmux_session'];
	}
	if ($proc_tmux_result[0]['monitor'] != null) {
		$monitor = $proc_tmux_result[0]['monitor'];
	}
	if ($proc_tmux_result[0]['backfill'] != null) {
		$backfill = $proc_tmux_result[0]['backfill'];
	}
	if ($proc_tmux_result[0]['niceness'] != null) {
		$niceness = $proc_tmux_result[0]['niceness'];
	}
	if ($proc_tmux_result[0]['progressive'] != null) {
		$progressive = $proc_tmux_result[0]['progressive'];
	}

	if ($proc_tmux_result[0]['binaries_run'] != null) {
		$binaries = $proc_tmux_result[0]['binaries_run'];
	}
	if ($proc_tmux_result[0]['import'] != null) {
		$import = $proc_tmux_result[0]['import'];
	}
	if ($proc_tmux_result[0]['nzbs'] != null) {
		$nzbs = $proc_tmux_result[0]['nzbs'];
	}
	if ($proc_tmux_result[0]['fix_names'] != null) {
		$fix_names = $proc_tmux_result[0]['fix_names'];
	}
	if ($proc_tmux_result[0]['fix_crap'] != null) {
		$fix_crap = explode(', ', ($proc_tmux_result[0]['fix_crap']));
	}
	if ($proc_tmux_result[0]['fix_crap_opt'] != null) {
		$fix_crap_opt = $proc_tmux_result[0]['fix_crap_opt'];
	}
	if ($proc_tmux_result[0]['update_tv'] != null) {
		$update_tv = $proc_tmux_result[0]['update_tv'];
	}
	if ($proc_tmux_result[0]['post'] != null) {
		$post = $proc_tmux_result[0]['post'];
	}
	if ($proc_tmux_result[0]['releases_run'] != null) {
		$releases_run = $proc_tmux_result[0]['releases_run'];
	}
	if ($proc_tmux_result[0]['releases_threaded'] != null) {
		$releases_threaded = $proc_tmux_result[0]['releases_threaded'];
	}
	if ($proc_tmux_result[0]['dehash'] != null) {
		$dehash = $proc_tmux_result[0]['dehash'];
	}
	if ($proc_tmux_result[0]['newestname'] != null) {
		$newestname = $proc_tmux_result[0]['newestname'];
	}
	if ($proc_tmux_result[0]['show_query'] != null) {
		$show_query = $proc_tmux_result[0]['show_query'];
	}
	if ($proc_tmux_result[0]['is_running'] != null) {
		$running = (int)$proc_tmux_result[0]['is_running'];
	}
	if ($proc_tmux_result[0]['sharing_timer'] != null) {
		$sharing_timer = $proc_tmux_result[0]['sharing_timer'];
	}
	if ($split_result[0]['newestpre'] != null) {
		$newestpre = $split_result[0]['newestpre'];
		$nowTime = time();
		if ($newestpre > $nowTime) {
			$newestpre = $nowTime;
		}
	}
	if ($tablepergroup == 0) {
		if ($split_result[0]['oldestcollection'] != null) {
			$oldestcollection = $split_result[0]['oldestcollection'];
		}
	}
	if ($split_result[0]['backfill_groups_days'] != null) {
		$backfill_groups_days = $split_result[0]['backfill_groups_days'];
	}
	if ($split_result[0]['backfill_groups_date'] != null) {
		$backfill_groups_date = $split_result[0]['backfill_groups_date'];
	}
	if ($split_result[0]['newestadd'] != null) {
		$newestadd = $split_result[0]['newestadd'];
	}

	//reset monitor paths before query
	$monitor_path = "";
	$monitor_path_a = "";
	$monitor_path_b = "";

	if ($proc_tmux_result[0]['monitor_path'] != null) {
		$monitor_path = $proc_tmux_result[0]['monitor_path'];
	}
	if ($proc_tmux_result[0]['monitor_path_a'] != null) {
		$monitor_path_a = $proc_tmux_result[0]['monitor_path_a'];
	}
	if ($proc_tmux_result[0]['monitor_path_b'] != null) {
		$monitor_path_b = $proc_tmux_result[0]['monitor_path_b'];
	}

	if ($proc_tmux_result[0]['post_amazon'] != null) {
		$post_amazon = $proc_tmux_result[0]['post_amazon'];
	}
	if ($proc_tmux_result[0]['post_timer_amazon'] != null) {
		$post_timer_amazon = $proc_tmux_result[0]['post_timer_amazon'];
	}
	if ($proc_tmux_result[0]['post_non'] != null) {
		$post_non = $proc_tmux_result[0]['post_non'];
	}
	if ($proc_tmux_result[0]['post_timer_non'] != null) {
		$post_timer_non = $proc_tmux_result[0]['post_timer_non'];
	}

	if ($proc_tmux_result[0]['seq_timer'] != null) {
		$seq_timer = $proc_tmux_result[0]['seq_timer'];
	}
	if ($proc_tmux_result[0]['bins_timer'] != null) {
		$bins_timer = $proc_tmux_result[0]['bins_timer'];
	}
	if ($proc_tmux_result[0]['back_timer'] != null) {
		$back_timer = $proc_tmux_result[0]['back_timer'];
	}
	if ($proc_tmux_result[0]['import_timer'] != null) {
		$import_timer = $proc_tmux_result[0]['import_timer'];
	}
	if ($proc_tmux_result[0]['rel_timer'] != null) {
		$rel_timer = $proc_tmux_result[0]['rel_timer'];
	}
	if ($proc_tmux_result[0]['fix_timer'] != null) {
		$fix_timer = $proc_tmux_result[0]['fix_timer'];
	}
	if ($proc_tmux_result[0]['crap_timer'] != null) {
		$crap_timer = $proc_tmux_result[0]['crap_timer'];
	}
	if ($proc_tmux_result[0]['post_timer'] != null) {
		$post_timer = $proc_tmux_result[0]['post_timer'];
	}
	if ($proc_tmux_result[0]['post_kill_timer'] != null) {
		$post_kill_timer = $proc_tmux_result[0]['post_kill_timer'];
	}
	if ($proc_tmux_result[0]['tv_timer'] != null) {
		$tv_timer = $proc_tmux_result[0]['tv_timer'];
	}
	if ($proc_tmux_result[0]['dehash_timer'] != null) {
		$dehash_timer = $proc_tmux_result[0]['dehash_timer'];
	}
	if ($proc_work_result[0]['releases'] != null) {
		$releases_now = $proc_work_result[0]['releases'];
	}

	//calculate releases difference
	$releases_misc_diff = number_format($releases_now - $releases_start);
	$releases_since_start = number_format($releases_now - $releases_start);
	$work_misc_diff = $work_remaining_now - $work_remaining_start;
	$pron_misc_diff = $pron_remaining_now - $pron_remaining_start;

	// Make sure thes types of post procs are on or off in the site first.
	// Otherwise if they are set to off, article headers will stop downloading as these off post procs queue up.
	if ($site->lookuptvrage == 0) {
		$tvrage_releases_proc = $tvrage_releases_proc_start = 0;
	}
	if ($site->lookupmusic == 0) {
		$music_releases_proc = $music_releases_proc_start = 0;
	}
	if ($site->lookupimdb == 0) {
		$movie_releases_proc = $movie_releases_proc_start = 0;
	}
	if ($site->lookupgames == 0) {
		$console_releases_proc = $console_releases_proc_start = 0;
	}
	if ($site->lookupbooks == 0) {
		$book_releases_proc = $book_releases_proc_start = 0;
	}
	if ($site->lookupnfo == 0) {
		$nfo_remaining_now = $nfo_remaining_start = 0;
	}

	$total_work_now = $work_remaining_now + $tvrage_releases_proc + $music_releases_proc + $movie_releases_proc + $console_releases_proc + $book_releases_proc + $nfo_remaining_now + $pc_releases_proc + $pron_remaining_now;
	if ($i == 1) {
		$total_work_start = $total_work_now;
	}

	$nfo_diff = number_format($nfo_remaining_now - $nfo_remaining_start);
	$pre_diff = number_format($predb_matched - $predb_start);
	$requestid_diff = number_format($requestid_inprogress - $requestid_inprogress_start);

	$console_diff = number_format($console_releases_proc - $console_releases_proc_start);
	$movie_diff = number_format($movie_releases_proc - $movie_releases_proc_start);
	$music_diff = number_format($music_releases_proc - $music_releases_proc_start);
	$pc_diff = number_format($pc_releases_proc - $pc_releases_proc_start);
	$tvrage_diff = number_format($tvrage_releases_proc - $tvrage_releases_proc_start);
	$book_diff = number_format($book_releases_proc - $book_releases_proc_start);

	//formatted output
	$misc_diff = number_format($work_remaining_now - $work_start);
	$pron_diff = number_format($pron_remaining_now - $pron_start);

	$work_since_start = ($total_work_now - $total_work_start);
	$work_diff = number_format($work_since_start);

	if ($releases_now != 0) {
		$nfo_percent = sprintf("%02s", floor(($nfo_now / $releases_now) * 100));
		$pre_percent = sprintf("%02s", floor(($predb_matched / $releases_now) * 100));
		$request_percent = sprintf("%02s", floor(($requestid_matched / $releases_now) * 100));
		$console_percent = sprintf("%02s", floor(($console_releases_now / $releases_now) * 100));
		$movie_percent = sprintf("%02s", floor(($movie_releases_now / $releases_now) * 100));
		$music_percent = sprintf("%02s", floor(($music_releases_now / $releases_now) * 100));
		$pc_percent = sprintf("%02s", floor(($pc_releases_now / $releases_now) * 100));
		$pron_percent = sprintf("%02s", floor(($pron_releases_now / $releases_now) * 100));
		$tvrage_percent = sprintf("%02s", floor(($tvrage_releases_now / $releases_now) * 100));
		$book_percent = sprintf("%02s", floor(($book_releases_now / $releases_now) * 100));
		$misc_percent = sprintf("%02s", floor(($misc_releases_now / $releases_now) * 100));
	} else {
		$nfo_percent = 0;
		$pre_percent = 0;
		$request_percent = 0;
		$console_percent = 0;
		$movie_percent = 0;
		$music_percent = 0;
		$pc_percent = 0;
		$tvrage_percent = 0;
		$book_percent = 0;
		$misc_percent = 0;
	}

	//get usenet connections
	if ($alternate_nntp) {
		$usp1activeconnections = str_replace("\n", '', shell_exec("ss -n | grep " . $ip . ":" . $port . " | grep -c ESTAB"));
		$usp1totalconnections = str_replace("\n", '', shell_exec("ss -n | grep -c " . $ip . ":" . $port));
		$usp2activeconnections = str_replace("\n", '', shell_exec("ss -n | grep " . $ip_a . ":" . $port_a . " | grep -c ESTAB"));
		$usp2totalconnections = str_replace("\n", '', shell_exec("ss -n | grep -c " . $ip_a . ":" . $port_a));
		if ($usp1activeconnections == 0 && $usp1totalconnections == 0 && $usp2activeconnections == 0 && $usp2totalconnections == 0 && $port != $port_a) {
			$usp1activeconnections = str_replace("\n", '', shell_exec("ss -n | grep " . $ip . ":https | grep -c ESTAB"));
			$usp1totalconnections = str_replace("\n", '', shell_exec("ss -n | grep -c " . $ip . ":https"));
			$usp2activeconnections = str_replace("\n", '', shell_exec("ss -n | grep " . $ip_a . ":https | grep -c ESTAB"));
			$usp2totalconnections = str_replace("\n", '', shell_exec("ss -n | grep -c " . $ip_a . ":https"));
		}
		if ($usp1activeconnections == 0 && $usp1totalconnections == 0 && $usp2activeconnections == 0 && $usp2totalconnections == 0 && $port != $port_a) {
			$usp1activeconnections = str_replace("\n", '', shell_exec("ss -n | grep " . $port . " | grep -c ESTAB"));
			$usp1totalconnections = str_replace("\n", '', shell_exec("ss -n | grep -c " . $port));
			$usp2activeconnections = str_replace("\n", '', shell_exec("ss -n | grep " . $port_a . " | grep -c ESTAB"));
			$usp2totalconnections = str_replace("\n", '', shell_exec("ss -n | grep -c " . $port_a));
		}
		if ($usp1activeconnections == 0 && $usp1totalconnections == 0 && $usp2activeconnections == 0 && $usp2totalconnections == 0 && $port != $port_a) {
			$usp1activeconnections = str_replace("\n", '', shell_exec("ss -n | grep " . $ip . " | grep -c ESTAB"));
			$usp1totalconnections = str_replace("\n", '', shell_exec("ss -n | grep -c " . $ip));
			$usp2activeconnections = str_replace("\n", '', shell_exec("ss -n | grep " . $ip . " | grep -c ESTAB"));
			$usp2totalconnections = str_replace("\n", '', shell_exec("ss -n | grep -c " . $ip));
		}
	} else {
		$usp1activeconnections = str_replace("\n", '', shell_exec("ss -n | grep " . $ip . ":" . $port . " | grep -c ESTAB"));
		$usp1totalconnections = str_replace("\n", '', shell_exec("ss -n | grep -c " . $ip . ":" . $port));
		if ($usp1activeconnections == 0 && $usp1totalconnections == 0 && $usp2activeconnections == 0 && $usp2totalconnections == 0) {
			$usp1activeconnections = str_replace("\n", '', shell_exec("ss -n | grep " . $ip . ":https | grep -c ESTAB"));
			$usp1totalconnections = str_replace("\n", '', shell_exec("ss -n | grep -c " . $ip . ":https"));
		}
		if ($usp1activeconnections == 0 && $usp1totalconnections == 0 && $usp2activeconnections == 0 && $usp2totalconnections == 0) {
			$usp1activeconnections = str_replace("\n", '', shell_exec("ss -n | grep " . $port . " | grep -c ESTAB"));
			$usp1totalconnections = str_replace("\n", '', shell_exec("ss -n | grep -c " . $port));
		}
		if ($usp1activeconnections == 0 && $usp1totalconnections == 0 && $usp2activeconnections == 0 && $usp2totalconnections == 0) {
			$usp1activeconnections = str_replace("\n", '', shell_exec("ss -n | grep " . $ip . " | grep -c ESTAB"));
			$usp1totalconnections = str_replace("\n", '', shell_exec("ss -n | grep -c " . $ip));
		}
	}

	if ($compressed === '1') {
		$mask2 = $c->headerOver("%-20s") . " " . $c->tmuxOrange("%-33.33s");
	} else {
		$mask2 = $c->alternateOver("%-20s") . " " . $c->tmuxOrange("%-33.33s");
	}

	//update display
	passthru('clear');
	//printf("\033[1;31m First insert:\033[0m ".relativeTime("$firstdate")."\n");
	if ($running == 1) {
		printf($mask2, "Monitor Running v$version [" . $patch . "]: ", relativeTime("$time"));
	} else {
		printf($mask2, "Monitor Off v$version [" . $patch . "]: ", relativeTime("$time"));
	}
	printf($mask1, "USP Connections:", $usp1activeconnections . " active (" . $usp1totalconnections . " total) - " . $host . ":" . $port);
	if ($alternate_nntp) {
		printf($mask1, "USP Alternate:", $usp2activeconnections . " active (" . $usp2totalconnections . " total) - " . (($alternate_nntp) ? $host_a . ":" . $port_a : "n/a"));
	}

	printf($mask1, "Newest Release:", "$newestname");
	printf($mask1, "Release Added:", relativeTime("$newestadd") . "ago");
	printf($mask1, "Predb Updated:", relativeTime("$newestpre") . "ago");
	printf($mask1, "Collection Age[${delay}]:", relativeTime("$oldestcollection") . "ago");
	printf($mask1, "Parts in Repair:", number_format($partrepair_table));
	if (($post == "1" || $post == "3") && $seq != 2) {
		printf($mask1, "Postprocess:", "stale for " . relativeTime($time2));
	}
	echo "\n";

	if ($monitor > 0) {
		printf($mask3, "Collections", "Binaries", "Parts");
		printf($mask3, "======================================", "=========================", "======================================");
		printf($mask5, number_format($collections_table), number_format($binaries_table), number_format($parts_table));

		if (((isset($monitor_path)) && (file_exists($monitor_path))) || ((isset($monitor_path_a)) && (file_exists($monitor_path_a))) || ((isset($monitor_path_b)) && (file_exists($monitor_path_b)))) {
			echo "\n";
			printf($mask3, "File System", "Used", "Free");
			printf($mask3, "======================================", "=========================", "======================================");
			if (isset($monitor_path) && $monitor_path != "" && file_exists($monitor_path)) {
				$disk_use = decodeSize(disk_total_space($monitor_path) - disk_free_space($monitor_path));
				$disk_free = decodeSize(disk_free_space($monitor_path));
				if (basename($monitor_path) == "") {
					$show = "/";
				} else {
					$show = basename($monitor_path);
				}
				printf($mask4, $show, $disk_use, $disk_free);
			}
			if (isset($monitor_path_a) && $monitor_path_a != "" && file_exists($monitor_path_a)) {
				$disk_use = decodeSize(disk_total_space($monitor_path_a) - disk_free_space($monitor_path_a));
				$disk_free = decodeSize(disk_free_space($monitor_path_a));
				if (basename($monitor_path_a) == "") {
					$show = "/";
				} else {
					$show = basename($monitor_path_a);
				}
				printf($mask4, $show, $disk_use, $disk_free);
			}
			if (isset($monitor_path_b) && $monitor_path_b != "" && file_exists($monitor_path_b)) {
				$disk_use = decodeSize(disk_total_space($monitor_path_b) - disk_free_space($monitor_path_b));
				$disk_free = decodeSize(disk_free_space($monitor_path_b));
				if (basename($monitor_path_b) == "") {
					$show = "/";
				} else {
					$show = basename($monitor_path_b);
				}
				printf($mask4, $show, $disk_use, $disk_free);
			}
		}
		echo "\n";
		printf($mask3, "Category", "In Process", "In Database");
		printf($mask3, "======================================", "=========================", "======================================");
		printf($mask4, "predb", number_format($predb - $distinct_predb_matched) . "(" . $pre_diff . ")", number_format($predb_matched) . "(" . $pre_percent . "%)");
		printf($mask4, "requestID", number_format($requestid_inprogress) . "(" . $requestid_diff . ")", number_format($requestid_matched) . "(" . $request_percent . "%)");
		printf($mask4, "NFO's", number_format($nfo_remaining_now) . "(" . $nfo_diff . ")", number_format($nfo_now) . "(" . $nfo_percent . "%)");
		printf($mask4, "Console(1000)", number_format($console_releases_proc) . "(" . $console_diff . ")", number_format($console_releases_now) . "(" . $console_percent . "%)");
		printf($mask4, "Movie(2000)", number_format($movie_releases_proc) . "(" . $movie_diff . ")", number_format($movie_releases_now) . "(" . $movie_percent . "%)");
		printf($mask4, "Audio(3000)", number_format($music_releases_proc) . "(" . $music_diff . ")", number_format($music_releases_now) . "(" . $music_percent . "%)");
		printf($mask4, "PC(4000)", number_format($pc_releases_proc) . "(" . $pc_diff . ")", number_format($pc_releases_now) . "(" . $pc_percent . "%)");
		printf($mask4, "TVShows(5000)", number_format($tvrage_releases_proc) . "(" . $tvrage_diff . ")", number_format($tvrage_releases_now) . "(" . $tvrage_percent . "%)");
		printf($mask4, "xXx(6000)", number_format($pron_remaining_now) . "(" . $pron_diff . ")", number_format($pron_releases_now) . "(" . $pron_percent . "%)");
		printf($mask4, "Misc(7000)", number_format($work_remaining_now) . "(" . $misc_diff . ")", number_format($misc_releases_now) . "(" . $misc_percent . "%)");
		printf($mask4, "Books(8000)", number_format($book_releases_proc) . "(" . $book_diff . ")", number_format($book_releases_now) . "(" . $book_percent . "%)");
		printf($mask4, "Total", number_format($total_work_now) . "(" . $work_diff . ")", number_format($releases_now) . "(" . $releases_since_start . ")");
		echo "\n";
		printf($mask3, "Groups", "Active", "Backfill");
		printf($mask3, "======================================", "=========================", "======================================");
		if ($backfilldays == "1") {
			printf($mask4, "Activated", $active_groups . "(" . $all_groups . ")", $backfill_groups_days . "(" . $all_groups . ")");
		} else {
			printf($mask4, "Activated", $active_groups . "(" . $all_groups . ")", $backfill_groups_date . "(" . $all_groups . ")");
		}
	}

	if ($show_query == 1) {
		echo "\n";
		printf($mask3, "Query Block", "Time", "Cumulative");
		printf($mask3, "======================================", "=========================", "======================================");
		printf($mask4, "Combined", $tmux_time . " " . $split_time . " " . $init_time . " " . $proc1_time . " " . $proc2_time . " " . $proc3_time . " " . $tpg_count_time, $tmux_time . " " . $split1_time . " " . $init1_time . " " . $proc11_time . " " . $proc21_time . " " . $proc31_time . " " . $tpg_count_1_time);

		$pieces = explode(" ", $db->getAttribute(PDO::ATTR_SERVER_INFO));
		echo $c->primaryOver("\nThreads = ") . $c->headerOver($pieces[4]) . $c->primaryOver(', Opens ') . $c->headerOver($pieces[14]) . $c->primaryOver(', Tables = ') . $c->headerOver($pieces[22]) . $c->primaryOver(', Slow = ') . $c->headerOver($pieces[11]) . $c->primaryOver(', QPS = ') . $c->header($pieces[28]);
	}

	//get list of panes by name
	if ($seq == 0) {
		$panes_win_1 = shell_exec("echo `tmux list-panes -t $tmux_session:0 -F '#{pane_title}'`");
		$panes0 = str_replace("\n", '', explode(" ", $panes_win_1));
		$panes_win_2 = shell_exec("echo `tmux list-panes -t $tmux_session:1 -F '#{pane_title}'`");
		$panes1 = str_replace("\n", '', explode(" ", $panes_win_2));
		$panes_win_3 = shell_exec("echo `tmux list-panes -t $tmux_session:2 -F '#{pane_title}'`");
		$panes2 = str_replace("\n", '', explode(" ", $panes_win_3));
	} else if ($seq == 1) {
		$panes_win_1 = shell_exec("echo `tmux list-panes -t $tmux_session:0 -F '#{pane_title}'`");
		$panes0 = str_replace("\n", '', explode(" ", $panes_win_1));
		$panes_win_2 = shell_exec("echo `tmux list-panes -t $tmux_session:1 -F '#{pane_title}'`");
		$panes1 = str_replace("\n", '', explode(" ", $panes_win_2));
		$panes_win_3 = shell_exec("echo `tmux list-panes -t $tmux_session:2 -F '#{pane_title}'`");
		$panes2 = str_replace("\n", '', explode(" ", $panes_win_3));
	} else if ($seq == 2) {
		$panes_win_1 = shell_exec("echo `tmux list-panes -t $tmux_session:0 -F '#{pane_title}'`");
		$panes0 = str_replace("\n", '', explode(" ", $panes_win_1));
		$panes_win_2 = shell_exec("echo `tmux list-panes -t $tmux_session:1 -F '#{pane_title}'`");
		$panes1 = str_replace("\n", '', explode(" ", $panes_win_2));
	}

	if (nZEDb_DEBUG) {
		$show_time = "/usr/bin/time";
	} else {
		$show_time = "";
	}

	$_php = $show_time . " nice -n$niceness $PHP";
	$_phpn = "nice -n$niceness $PHP";

	$_python = $show_time . " nice -n$niceness $PYTHON";
	$_pythonn = "nice -n$niceness $PYTHON";

	if (($postprocess_kill < $total_work_now) && ($postprocess_kill != 0)) {
		$kill_pp = true;
	} else {
		$kill_pp = false;
	}
	if (($collections_kill < $collections_table) && ($collections_kill != 0)) {
		$kill_coll = true;
	} else {
		$kill_coll = false;
	}

	if ($binaries != 0) {
		$which_bins = "$_python ${DIR}update/python/binaries_threaded.py";
	} else if ($binaries == 2) {
		$which_bins = "$_python ${DIR}update/python/binaries_safe_threaded.py";
	}

	$_sleep = "$_phpn ${DIR}update/nix/tmux/bin/showsleep.php";

	if ($releases_run != 0) {
		if ($tablepergroup == 0) {
			$run_releases = "$_php ${DIR}update/update_releases.php 1 false";
		} else {
			$run_releases = "$_python ${DIR}update/python/releases_threaded.py";
		}
	}

	if ($post_non == 2) {
		$clean = ' clean ';
	} else {
		$clean = ' ';
	}

	if ($running == 1) {
		//run these if complete sequential not set
		if ($seq != 2) {
			//fix names
			if ($fix_names == 1) {
				$log = writelog($panes1[0]);
				shell_exec("tmux respawnp -t${tmux_session}:1.0 ' \
						$_python ${DIR}update/python/fixreleasenames_threaded.py md5 $log; \
						$_python ${DIR}update/python/fixreleasenames_threaded.py filename preid $log; \
						$_python ${DIR}update/python/fixreleasenames_threaded.py nfo preid $log; \
						$_python ${DIR}update/python/fixreleasenames_threaded.py par2 preid $log; \
						$_python ${DIR}update/python/fixreleasenames_threaded.py miscsorter $log; \
						$_python ${DIR}update/python/fixreleasenames_threaded.py predbft $log; date +\"%D %T\"; $_sleep $fix_timer' 2>&1 1> /dev/null"
				);
			} else {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:1.0 'echo \"\033[38;5;${color}m\n${panes1[0]} has been disabled/terminated by Fix Release Names\"'");
			}

			//dehash releases
			if ($dehash == 1) {
				$log = writelog($panes1[3]);
				shell_exec("tmux respawnp -t${tmux_session}:1.3 ' \
						$_php ${DIR}update/decrypt_hashes.php 1000 $log; date +\"%D %T\"; $_sleep $dehash_timer' 2>&1 1> /dev/null"
				);
			} else if ($dehash == 2) {
				$log = writelog($panes1[3]);
				shell_exec("tmux respawnp -t${tmux_session}:1.3 ' \
						$_php ${DIR}update/nix/tmux/bin/postprocess_pre.php $log; date +\"%D %T\"; $_sleep $dehash_timer' 2>&1 1> /dev/null"
				);
			} else if ($dehash == 3) {
				$log = writelog($panes1[3]);
				shell_exec("tmux respawnp -t${tmux_session}:1.3 ' \
						$_php ${DIR}update/nix/tmux/bin/postprocess_pre.php $log; \
						$_php ${DIR}update/decrypt_hashes.php 1000 $log; date +\"%D %T\"; $_sleep $dehash_timer' 2>&1 1> /dev/null"
				);
			} else {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:1.3 'echo \"\033[38;5;${color}m\n${panes1[3]} has been disabled/terminated by Decrypt Hashes\"'");
			}

			// Remove crap releases.
			switch ($fix_crap_opt) {
				// Do all types up to 2 hours.
				case 'All':
					$log = writelog($panes1[1]);
					shell_exec("tmux respawnp -t${tmux_session}:1.1 ' \
							$_php ${DIR}testing/Release/removeCrapReleases.php true 2 $log; date +\"%D %T\"; $_sleep $crap_timer' 2>&1 1> /dev/null"
					);
					break;
				// The user has specified custom types.
				case 'Custom':
					$log = writelog($panes1[1]);

					// Check how many types the user picked.
					$fcmax = count($fix_crap);

					// Make sure he actually selected some.
					if ($fcmax > 0) {

						// If this is the first run, do a full run, else run on last 2 hours of releases.
						$fctime = '4';
						if ((($i == 1) || $fcfirstrun)) {
							$fctime = 'full';
						}

						//Check to see if the pane is dead, if so resawn it.
						if (shell_exec("tmux list-panes -t${tmux_session}:1 | grep ^1 | grep -c dead") == 1) {

							// Run remove crap releases.
							shell_exec("tmux respawnp -t${tmux_session}:1.1 ' \
								echo \"Running removeCrapReleases for $fix_crap[$fcnum]\"; \
								php ${DIR}testing/Release/removeCrapReleases.php true $fctime $fix_crap[$fcnum] $log; date +\"%D %T\"; $_sleep $crap_timer' 2>&1 1> /dev/null"
							);

							// Increment so we know which type to run next.
							$fcnum++;
						}

						// If we reached the end, reset the type.
						if ($fcnum == $fcmax) {
							$fcnum = 0;

							// And say we are not on the first run, so we run 2 hours the next times.
							$fcfirstrun = false;
						}
					}
					break;
				case 'Disabled':
				default:
					$color = get_color($colors_start, $colors_end, $colors_exc);
					shell_exec("tmux respawnp -k -t${tmux_session}:1.1 'echo \"\033[38;5;${color}m\n${panes1[1]} has been disabled/terminated by Remove Crap Releases\"'");
					break;
			}

			if ($post == 1 && ($work_remaining_now + $pc_releases_proc + $pron_remaining_now) > 0) {
				//run postprocess_releases additional
				$history = str_replace(" ", '', `tmux list-panes -t${tmux_session}:2 | grep 0: | awk '{print $4;}'`);
				if ($last_history != $history) {
					$last_history = $history;
					$time2 = TIME();
				} else {
					if (TIME() - $time2 >= $post_kill_timer) {
						$color = get_color($colors_start, $colors_end, $colors_exc);
						passthru("tmux respawnp -k -t${tmux_session}:2.0 'echo \"\033[38;5;${color}m\n${panes2[0]} has been terminated by Possible Hung thread\"'");
						$wipe = `tmux clearhist -t${tmux_session}:2.0`;
						$time2 = TIME();
					}
				}
				$dead1 = str_replace(" ", '', `tmux list-panes -t${tmux_session}:2 | grep dead | grep 0: | wc -l`);
				if ($dead1 == 1) {
					$time2 = TIME();
				}
				$log = writelog($panes2[0]);
				shell_exec("tmux respawnp -t${tmux_session}:2.0 'echo \"\033[38;5;${color}m\"; \
						$_python ${DIR}update/python/postprocess_threaded.py additional $log; date +\"%D %T\"; $_sleep $post_timer' 2>&1 1> /dev/null"
				);
			} else if ($post == 2 && $nfo_remaining_now > 0) {
				$log = writelog($panes2[0]);
				shell_exec("tmux respawnp -t${tmux_session}:2.0 ' \
						$_python ${DIR}update/python/postprocess_threaded.py nfo $log; date +\"%D %T\"; $_sleep $post_timer' 2>&1 1> /dev/null"
				);
			} else if (($post == 3) && (($nfo_remaining_now > 0) || ($work_remaining_now + $pc_releases_proc + $pron_remaining_now > 0))) {
				//run postprocess_releases additional
				$history = str_replace(" ", '', `tmux list-panes -t${tmux_session}:2 | grep 0: | awk '{print $4;}'`);
				if ($last_history != $history) {
					$last_history = $history;
					$time2 = TIME();
				} else {
					if (TIME() - $time2 >= $post_kill_timer) {
						$color = get_color($colors_start, $colors_end, $colors_exc);
						shell_exec("tmux respawnp -k -t${tmux_session}:2.0 'echo \"\033[38;5;${color}m\n${panes2[0]} has been terminated by Possible Hung thread\"'");
						$wipe = `tmux clearhist -t${tmux_session}:2.0`;
						$time2 = TIME();
					}
				}
				$dead1 = str_replace(" ", '', `tmux list-panes -t${tmux_session}:2 | grep dead | grep 0: | wc -l`);
				if ($dead1 == 1) {
					$time2 = TIME();
				}
				$log = writelog($panes2[0]);
				shell_exec("tmux respawnp -t${tmux_session}:2.0 ' \
						$_python ${DIR}update/python/postprocess_threaded.py additional $log; \
						$_python ${DIR}update/python/postprocess_threaded.py nfo $log; date +\"%D %T\"; $_sleep $post_timer' 2>&1 1> /dev/null"
				);
			} else if (($post != 0) && ($nfo_remaining_now == 0) && ($work_remaining_now + $pc_releases_proc + $pron_remaining_now == 0)) {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:2.0 'echo \"\033[38;5;${color}m\n${panes2[0]} has been disabled/terminated by No Misc/Nfo to process\"'");
			} else {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:2.0 'echo \"\033[38;5;${color}m\n${panes2[0]} has been disabled/terminated by Postprocess Additional\"'");
			}

			if (($post_non != 0) && (($movie_releases_proc > 0) || ($tvrage_releases_proc > 0))) {
				//run postprocess_releases non amazon
				$log = writelog($panes2[1]);
				shell_exec("tmux respawnp -t${tmux_session}:2.1 ' \
						$_python ${DIR}update/python/postprocess_threaded.py tv $clean $log; \
						$_python ${DIR}update/python/postprocess_threaded.py movie $clean $log; date +\"%D %T\"; $_sleep $post_timer_non' 2>&1 1> /dev/null"
				);
			} else if (($post_non != 0) && ($movie_releases_proc == 0) && ($tvrage_releases_proc == 0)) {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:2.1 'echo \"\033[38;5;${color}m\n${panes2[1]} has been disabled/terminated by No Movies/TV to process\"'");
			} else {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:2.1 'echo \"\033[38;5;${color}m\n${panes2[1]} has been disabled/terminated by Postprocess Non-Amazon\"'");
			}

			if (($post_amazon == 1) && (($music_releases_proc > 0) || ($book_releases_proc > 0) || ($console_releases_proc > 0)) && (($processbooks == 1) || ($processmusic == 1) || ($processgames == 1))) {
				//run postprocess_releases amazon
				$log = writelog($panes2[2]);
				shell_exec("tmux respawnp -t${tmux_session}:2.2 ' \
						$_python ${DIR}update/python/postprocess_old_threaded.py amazon $log; date +\"%D %T\"; $_sleep $post_timer_amazon' 2>&1 1> /dev/null"
				);
			} else if (($post_amazon == 1) && ($processbooks == 0) && ($processmusic == 0) && ($processgames == 0)) {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:2.2 'echo \"\033[38;5;${color}m\n${panes2[2]} has been disabled/terminated in Admin Disable Music/Books/Console\"'");
			} else if (($post_amazon == 1) && ($music_releases_proc == 0) && ($book_releases_proc == 0) && ($console_releases_proc == 0)) {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:2.2 'echo \"\033[38;5;${color}m\n${panes2[2]} has been disabled/terminated by No Music/Books/Console to process\"'");
			} else {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:2.2 'echo \"\033[38;5;${color}m\n${panes2[2]} has been disabled/terminated by Postprocess Amazon\"'");
			}

			//update tv and theaters
			if (($update_tv == 1) && ((TIME() - $time3 >= $tv_timer) || ($i == 1))) {
				$log = writelog($panes1[3]);
				shell_exec("tmux respawnp -t${tmux_session}:1.2 ' \
						$_phpn ${DIR}update/update_theaters.php $log; $_phpn ${DIR}testing/PostProc/populate_tvrage.php true $log; \
						$_phpn ${DIR}update/update_tvschedule.php $log; $_phpn ${DIR}testing/PostProc/updateTvRage.php $log; date +\"%D %T\"' 2>&1 1> /dev/null"
				);
				$time3 = TIME();
			} else if ($update_tv == 1) {
				$run_time = relativeTime($tv_timer + $time3);
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -t${tmux_session}:1.2 'echo \"\033[38;5;${color}m\n${panes1[2]} will run in T[ $run_time]\"' 2>&1 1> /dev/null");
			} else {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:1.2 'echo \"\033[38;5;${color}m\n${panes1[2]} has been disabled/terminated by Update TV/Theater\"'");
			}
		}

		if ($seq == 1) {
			//run nzb-import
			if (($import != 0) && ($kill_pp == false)) {
				$log = writelog($panes0[1]);
				shell_exec("tmux respawnp -t${tmux_session}:0.1 ' \
						$_python ${DIR}update/python/import_threaded.py $log; date +\"%D %T\"; $_sleep $import_timer' 2>&1 1> /dev/null"
				);
			} else {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.1 'echo \"\033[38;5;${color}m\n${panes0[1]} has been disabled/terminated by Import\"'");
			}

			//run update_binaries
			$log = writelog($panes0[2]);
			if (($kill_coll == false) && ($kill_pp == false) && (TIME() - $time6 <= 4800)) {
				//runs all/safe less than 4800
				if (($binaries != 0) && ($backfill == 4) && ($releases_run != 0)) {
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$which_bins $log; \
							$_python ${DIR}update/python/backfill_safe_threaded.py $log; \
							$run_releases $log; date +\"%D %T\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
					);
				} //runs all less than 4800
				else if (($binaries != 0) && ($backfill != 0) && ($releases_run != 0)) {
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$which_bins $log; \
							$_python ${DIR}update/python/backfill_threaded.py $log; \
							$run_releases $log; date +\"%D %T\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
					);
				} //runs bin/back/safe less than 4800
				else if (($binaries != 0) && ($backfill == 4) && ($releases_run == 0)) {
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$which_bins $log; \
							$_python ${DIR}update/python/backfill_safe_threaded.py $log; date +\"%D %T\"; \
							echo \"\nreleases has been disabled/terminated by Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
					);
				} //runs bin/back less than 4800
				else if (($binaries != 0) && ($backfill != 0) && ($releases_run == 0)) {
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$which_bins $log; \
							$_python ${DIR}update/python/backfill_threaded.py $log; date +\"%D %T\"; echo \"\nreleases have been disabled/terminated by Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
					);
				} //runs back/safe/rel less than 4800
				else if (($binaries == 0) && ($backfill == 4) && ($releases_run != 0)) {
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$_python ${DIR}update/python/backfill_safe_threaded.py $log; \
							$run_releases $log; date +\"%D %T\"; echo \"\nbinaries has been disabled/terminated by Binaries\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
					);
				} //runs back/rel less than 4800
				else if (($binaries == 0) && ($backfill != 0) && ($releases_run != 0)) {
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$_python ${DIR}update/python/backfill_threaded.py $log; \
							$run_releases $log; date +\"%D %T\"; echo \"\nbinaries has been disabled/terminated by Binaries\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
					);
				} //runs bin/rel less than 4800
				else if (($binaries != 0) && ($backfill == 0) && ($releases_run != 0)) {
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$which_bins $log; \
							$run_releases $log; date +\"%D %T\"; echo \"\nbackfill has been disabled/terminated by Backfill\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
					);
				} //runs bin less than 4800
				else if (($binaries != 0) && ($backfill == 0) && ($releases_run == 0)) {
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$which_bins $log; date +\"%D %T\"; echo \"\nbackfill and releases have been disabled/terminated by Backfill and Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
					);
				} //runs back/safe less than 4800
				else if (($binaries == 0) && ($backfill == 4) && ($releases_run == 0)) {
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$_python ${DIR}update/python/backfill_safe_threaded.py $log; date +\"%D %T\"; echo \"\nbinaries and releases have been disabled/terminated by Binaries and Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
					);
				} //runs back less than 4800
				else if (($binaries == 0) && ($backfill == 4) && ($releases_run == 0)) {
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$_python ${DIR}update/python/backfill_threaded.py $log; date +\"%D %T\"; echo \"\nbinaries and releases have been disabled/terminated by Binaries and Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
					);
				} //runs rel less than 4800
				else if (($binaries == 0) && ($backfill == 0) && ($releases_run != 0)) {
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$run_releases $log; date +\"%D %T\"; echo \"\nbinaries and backfill has been disabled/terminated by Binaries and Backfill\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
					);
				} else if (($binaries == 0) && ($backfill == 0) && ($releases_run == 0)) {
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							echo \"\nbinaries, backfill and releases have been disabled/terminated by Binaries, Backfill and Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
					);
				}
			} else if (($kill_coll == false) && ($kill_pp == false) && (TIME() - $time6 >= 4800)) {
				//run backfill all once and resets the timer
				if ($backfill != 0) {
					shell_exec("tmux respawnp -k -t${tmux_session}:0.2 ' \
						$_python ${DIR}update/python/backfill_threaded.py all $log; date +\"%D %T\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
					);
					$time6 = TIME();
				}
				$time6 = TIME();
			} else if ((($kill_coll == true) || ($kill_pp == true)) && ($releases_run != 0)) {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\"; \
					echo \"\nbinaries and backfill has been disabled/terminated by Exceeding Limits\"; \
					$run_releases $log; date +\"%D %T\"; echo \"\nbinaries and backfill has been disabled/terminated by Exceeding Limits\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
				);
			} else if (($kill_coll == true) || ($kill_pp == true)) {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\n${panes0[2]} has been disabled/terminated by Exceeding Limits\"'");
			}

			//pane setup for IrcScraper / Sharing
			$ipane = 3;
			if ($nntpproxy == 1) {
				$spane = 5;
			} else {
				$spane = 4;
			}

			//run IRCScraper
			run_ircscraper($tmux_session, $_php, $ipane, $run_ircscraper);

			//run Sharing
			run_sharing($tmux_session, $_php, $spane, $_sleep, $sharing_timer);
		} else if ($seq == 2) {
			//run nzb-import
			if (($import != 0) && ($kill_pp == false)) {
				$log = writelog($panes0[1]);
				shell_exec("tmux respawnp -t${tmux_session}:0.1 ' \
						$_python ${DIR}update/python/import_threaded.py $log; date +\"%D %T\"; $_sleep $import_timer' 2>&1 1> /dev/null"
				);
			} else {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.1 'echo \"\033[38;5;${color}m\n${panes0[1]} has been disabled/terminated by Import\"'");
			}

			//update tv and theaters
			if (($update_tv == 1) && ((TIME() - $time3 >= $tv_timer) || ($i == 1))) {
				$log = writelog($panes1[0]);
				shell_exec("tmux respawnp -t${tmux_session}:1.0 ' \
						$_phpn ${DIR}update/update_theaters.php $log; $_phpn ${DIR}testing/PostProc/populate_tvrage.php true $log; \
                                                $_phpn ${DIR}update/update_tvschedule.php $log; $_phpn ${DIR}testing/PostProc/updateTvRage.php $log; date +\"%D %T\"' 2>&1 1> /dev/null"
				);
				$time3 = TIME();
			} else if ($update_tv == 1) {
				$run_time = relativeTime($tv_timer + $time3);
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -t${tmux_session}:1.0 'echo \"\033[38;5;${color}m\n${panes1[0]} will run in T[ $run_time]\"' 2>&1 1> /dev/null");
			} else {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:1.0 'echo \"\033[38;5;${color}m\n${panes1[0]} has been disabled/terminated by Update TV/Theater\"'");
			}

			if (($post_amazon == 1) && (($music_releases_proc > 0) || ($book_releases_proc > 0) || ($console_releases_proc > 0)) && (($processbooks != 0) || ($processmusic != 0) || ($processgames != 0))) {
				//run postprocess_releases amazon
				$log = writelog($panes1[1]);
				shell_exec("tmux respawnp -t${tmux_session}:1.1 ' \
						$_python ${DIR}update/python/postprocess_old_threaded.py amazon $log; date +\"%D %T\"; $_sleep $post_timer_amazon' 2>&1 1> /dev/null"
				);
			} else if (($post_amazon == 1) && ($processbooks == 0) && ($processmusic == 0) && ($processgames == 0)) {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:1.1 'echo \"\033[38;5;${color}m\n${panes1[1]} has been disabled/terminated in Admin Disable Music/Books/Console\"'");
			} else if (($post_amazon == 1) && ($music_releases_proc == 0) && ($book_releases_proc == 0) && ($console_releases_proc == 0)) {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:1.1 'echo \"\033[38;5;${color}m\n${panes1[1]} has been disabled/terminated by No Music/Books/Console to process\"'");
			} else {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:1.1 'echo \"\033[38;5;${color}m\n${panes1[1]} has been disabled/terminated by Postprocess Amazon\"'");
			}

			//run user_threaded.sh
			$log = writelog($panes0[2]);
			shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
					${DIR}update/nix/screen/sequential/user_threaded.sh true $log; date +\"%D %T\"' 2>&1 1> /dev/null"
			);

			//pane setup for IrcScraper / Sharing
			$ipane = 2;
			if ($nntpproxy == 1) {
				$spane = 4;
			} else {
				$spane = 3;
			}

			//run IRCScraper
			run_ircscraper($tmux_session, $_php, $ipane, $run_ircscraper);

			//run Sharing
			run_sharing($tmux_session, $_php, $spane, $_sleep, $sharing_timer);
		} else {
			//run update_binaries
			$color = get_color($colors_start, $colors_end, $colors_exc);
			if (($binaries != 0) && ($kill_coll == false) && ($kill_pp == false)) {
				$log = writelog($panes0[2]);
				shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
						$which_bins $log; date +\"%D %T\"; $_sleep $bins_timer' 2>&1 1> /dev/null"
				);
			} else if (($kill_coll == true) || ($kill_pp == true)) {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\n${panes0[2]} has been disabled/terminated by Exceeding Limits\"'");
			} else {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\n${panes0[2]} has been disabled/terminated by Binaries\"'");
			}

			//run backfill
			if ($progressive == 1 && floor($collections_table / 500) > $back_timer) {
				$backsleep = floor($collections_table / 500);
			} else {
				$backsleep = $back_timer;
			}

			if (($backfill == 4) && ($kill_coll == false) && ($kill_pp == false) && (TIME() - $time6 <= 4800)) {
				$log = writelog($panes0[3]);
				shell_exec("tmux respawnp -t${tmux_session}:0.3 ' \
						$_python ${DIR}update/python/backfill_safe_threaded.py $log; date +\"%D %T\"; $_sleep $backsleep' 2>&1 1> /dev/null"
				);
			} else if (($backfill != 0) && ($kill_coll == false) && ($kill_pp == false) && (TIME() - $time6 <= 4800)) {
				$log = writelog($panes0[3]);
				shell_exec("tmux respawnp -t${tmux_session}:0.3 ' \
						$_python ${DIR}update/python/backfill_threaded.py group $log; date +\"%D %T\"; $_sleep $backsleep' 2>&1 1> /dev/null"
				);
			} else if (($backfill != 0) && ($kill_coll == false) && ($kill_pp == false) && (TIME() - $time6 >= 4800)) {
				$log = writelog($panes0[3]);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.3 ' \
						$_python ${DIR}update/python/backfill_threaded.py all $log; date +\"%D %T\"; $_sleep $backsleep' 2>&1 1> /dev/null"
				);
				$time6 = TIME();
			} else if (($kill_coll == false) || ($kill_pp == false)) {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.3 'echo \"\033[38;5;${color}m\n${panes0[3]} has been disabled/terminated by Exceeding Limits\"'");
			} else {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.3 'echo \"\033[38;5;${color}m\n${panes0[3]} has been disabled/terminated by Backfill\"'");
			}

			//run nzb-import
			if (($import != 0) && ($kill_pp == false)) {
				$log = writelog($panes0[1]);
				shell_exec("tmux respawnp -t${tmux_session}:0.1 ' \
						$_python ${DIR}update/python/import_threaded.py $log; date +\"%D %T\"; $_sleep $import_timer' 2>&1 1> /dev/null"
				);
			} else if ($kill_pp == true) {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.1 'echo \"\033[38;5;${color}m\n${panes0[1]} has been disabled/terminated by Exceeding Limits\"'");
			} else {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.1 'echo \"\033[38;5;${color}m\n${panes0[1]} has been disabled/terminated by Import\"'");
			}

			//run update_releases
			if ($releases_run != 0) {
				$log = writelog($panes0[4]);
				shell_exec("tmux respawnp -t${tmux_session}:0.4 ' \
						$run_releases $log; date +\"%D %T\"; $_sleep $rel_timer' 2>&1 1> /dev/null"
				);
			} else {
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.4 'echo \"\033[38;5;${color}m\n${panes0[4]} has been disabled/terminated by Releases\"'");
			}

			//pane setup for IrcScraper / Sharing
			$ipane = 3;
			if ($nntpproxy == 1) {
				$spane = 5;
			} else {
				$spane = 4;
			}

			//run IRCScraper
			run_ircscraper($tmux_session, $_php, $ipane, $run_ircscraper);

			//run Sharing
			run_sharing($tmux_session, $_php, $spane, $_sleep, $sharing_timer);
		}
	} else if ($seq == 0) {
		for ($g = 1; $g <= 4; $g++) {
			$color = get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:0.$g 'echo \"\033[38;5;${color}m\n${panes0[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 3; $g++) {
			$color = get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:1.$g 'echo \"\033[38;5;${color}m\n${panes1[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 2; $g++) {
			$color = get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:2.$g 'echo \"\033[38;5;${color}m\n${panes2[$g]} has been disabled/terminated by Running\"'");
		}
	} else if ($seq == 1) {
		for ($g = 1; $g <= 2; $g++) {
			$color = get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:0.$g 'echo \"\033[38;5;${color}m\n${panes0[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 3; $g++) {
			$color = get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:1.$g 'echo \"\033[38;5;${color}m\n${panes1[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 2; $g++) {
			$color = get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:2.$g 'echo \"\033[38;5;${color}m\n${panes2[$g]} has been disabled/terminated by Running\"'");
		}
	} else if ($seq == 2) {
		for ($g = 1; $g <= 2; $g++) {
			$color = get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:0.$g 'echo \"\033[38;5;${color}m\n${panes0[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 1; $g++) {
			$color = get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:1.$g 'echo \"\033[38;5;${color}m\n${panes1[$g]} has been disabled/terminated by Running\"'");
		}
	}

	$i++;
	sleep(10);
}

function run_ircscraper($tmux_session, $_php, $pane, $run_ircscraper)
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

function run_sharing($tmux_session, $_php, $pane, $_sleep, $sharing_timer)
{
	$db = new Settings();
	$sharing = $db->queryOneRow('SELECT enabled, posting, fetching FROM sharing');
	$t = new Tmux();
	$tmux = $t->get();
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
