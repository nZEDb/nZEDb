<?php
require_once dirname(__FILE__) . '/../../../../www/config.php';

use nzedb\db\Settings;

$c = new ColorCLI();
$versions = \nzedb\utility\Utility::getValidVersionsFile();

//exec('git log | grep "^commit" | wc -l', $commit);
$git = new \nzedb\utility\Git();

$version = $versions->versions->git->tag . 'r' . $git->commits();

$pdo = new Settings();
$DIR = nZEDb_MISC;
$db_name = DB_NAME;
$dbtype = DB_SYSTEM;

$t = new Tmux();
$tmux = $t->get();
$seq = (isset($tmux->sequential)) ? $tmux->sequential : 0;
$powerline = (isset($tmux->powerline)) ? $tmux->powerline : 0;
$run_ircscraper = $tmux->run_ircscraper;

$patch = $pdo->getSetting('sqlpatch');
$alternate_nntp = ($pdo->getSetting('alternate_nntp') == '1') ? true : false;
$tpg = $pdo->getSetting('tablepergroup');
$tablepergroup = isset($tpg) ? $tpg : 0;
$dt = $pdo->getSetting('delaytime');
$delay = isset($dt) ? $dt : 2;
$proxy = $pdo->getSetting('nntpproxy');
$nntpproxy = isset($proxy) ? $proxy : 0;
$bkreqid = $pdo->getSetting('book_reqids');
$bookreqids = ($bkreqid === null || $bkreqid == "") ? 8010 : $bkreqid;
$reqHours = $pdo->getSetting('request_hours');
$request_hours = isset($reqHours) ? $reqHours : 1;
$pre_lim = '';

if ($t->command_exist("python3")) {
	$PYTHON = "python3 -OOu";
} else {
	$PYTHON = "python -OOu";
}

if ($t->command_exist("php5")) {
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

//totals per category in db, results by parentID
$catcntqry = "SELECT c.parentid AS parentid, COUNT(r.id) AS count FROM category c, releases r WHERE r.categoryid = c.id GROUP BY c.parentid";

//create timers and set to now
$time = $time1 = $time2 = $time3 = $time4 = $time5 = $time6 = $time7 = time();

// variables
$newestadd = time();
$newestname = "";
$newestpre = time();
$oldestcollection = time();
$oldestnzb = time();

$active_groups = $all_groups = $running = $backfilldays = $backfill_groups_date = $colors_exc = 0;
$book_diff = $book_percent = $book_releases_now = $book_releases_proc = 0;
$games_diff = $games_percent = $games_releases_now = $games_releases_proc = 0;
$misc_diff = $misc_percent = $misc_releases_now = $work_start = $compressed = 0;
$music_diff = $music_percent = $music_releases_proc = $music_releases_now = 0;
$movie_diff = $movie_percent = $movie_releases_now = $movie_releases_proc = 0;
$nfo_diff = $nfo_percent = $nfo_remaining_now = $nfo_now = $tvrage_releases_proc_start = 0;
$apps_diff = $apps_percent = $apps_releases_now = $apps_releases_proc = $book_releases_proc_start = 0;
$pre_diff = $pre_percent = $predb_matched = $predb_start = $predb = $distinct_predb_matched = 0;
$xxx_diff = $xxx_percent = $xxx_releases_proc = $xxx_releases_proc_start = $xxx_releases_now = 0;
$nfo_remaining_start = $work_remaining_start = $releases_start = $releases_now = $releases_since_start = 0;
$request_percent = $requestid_inprogress_start = $requestid_inprogress = $requestid_diff = $requestid_matched = 0;
$total_work_now = $work_diff = $work_remaining_now = $apps_releases_proc_start = 0;
$tvrage_diff = $tvrage_percent = $tvrage_releases_now = $tvrage_releases_proc = 0;
$usp1activeconnections = $usp1totalconnections = $usp2activeconnections = $usp2totalconnections = 0;
$collections_table = $parts_table = $binaries_table = $partrepair_table = 0;
$music_releases_proc_start = 0;
$tmux_time = $split_time = $init_time = $proc1_time = $proc2_time = $proc3_time = $split1_time = 0;
$init1_time = $proc11_time = $proc21_time = $proc31_time = $tpg_count_time = $tpg_count_1_time = 0;
$games_releases_proc_start = $movie_releases_proc_start = $show_query = $run_releases = 0;
$last_history = "";

// Analyze tables
printf($c->info("\nAnalyzing your tables to refresh your indexes."));
$pdo->optimise(true, 'analyze');

$mask1 = $c->headerOver("%-18s") . " " . $c->tmuxOrange("%-48.48s");
$mask2 = $c->headerOver("%-20s") . " " . $c->tmuxOrange("%-33.33s");
$mask3 = $c->header("%-16.16s %25.25s %25.25s");
$mask4 = $c->primaryOver("%-16.16s") . " " . $c->tmuxOrange("%25.25s %25.25s");
$mask5 = $c->tmuxOrange("%-16.16s %25.25s %25.25s");

//create display
passthru('clear');
//printf("\033[1;31m First insert:\033[0m " . $t->relativeTime("$firstdate") . "\n");
if ($running == 1) {
	printf($mask2, "Monitor Running v$version [" . $patch . "]: ", $t->relativeTime("$time"));
} else {
	printf($mask2, "Monitor Off v$version [" . $patch . "]: ", $t->relativeTime("$time"));
}
printf($mask1, "USP Connections:", $usp1activeconnections . " active (" . $usp1totalconnections . " total) - " . $host . ":" . $port);
if ($alternate_nntp) {
	printf($mask1, "USP Alternate:", $usp2activeconnections . " active (" . $usp2totalconnections . " total) - " . (($alternate_nntp) ? $host_a . ":" . $port_a : "n/a"));
}
printf($mask1, "Newest Release:", "$newestname");
printf($mask1, "Release Added:", $t->relativeTime("$newestadd") . "ago");
printf($mask1, "Predb Updated:", $t->relativeTime("$newestpre") . "ago");
printf($mask1, "Collection Age[${delay}]:", $t->relativeTime("$oldestcollection") . "ago");
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
printf($mask4, "Games(1000)", number_format($games_releases_proc) . "(" . $games_diff . ")", number_format($games_releases_now) . "(" . $games_percent . "%)");
printf($mask4, "Movie(2000)", number_format($movie_releases_proc) . "(" . $movie_diff . ")", number_format($movie_releases_now) . "(" . $movie_percent . "%)");
printf($mask4, "Audio(3000)", number_format($music_releases_proc) . "(" . $music_diff . ")", number_format($music_releases_now) . "(" . $music_percent . "%)");
printf($mask4, "Apps(4000)", number_format($apps_releases_proc) . "(" . $apps_diff . ")", number_format($apps_releases_now) . "(" . $apps_percent . "%)");
printf($mask4, "TVShows(5000)", number_format($tvrage_releases_proc) . "(" . $tvrage_diff . ")", number_format($tvrage_releases_now) . "(" . $tvrage_percent . "%)");
printf($mask4, "xXx(6000)", number_format($xxx_releases_proc) . "(" . $xxx_diff . ")", number_format($xxx_releases_now) . "(" . $xxx_percent . "%)");
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
	//kill mediainfo, avconv and ffmpeg if exceeds 60 sec
	shell_exec("killall -o 60s -9 mediainfo 2>&1 1> /dev/null");
	shell_exec("killall -o 60s -9 ffmpeg 2>&1 1> /dev/null");
	shell_exec("killall -o 60s -9 avconv 2>&1 1> /dev/null");

	//check the db connection
	if ($pdo->ping(true) == false) {
		unset($pdo);
		$pdo = new Settings();
	}

	$time01 = time();
	// These queries are very fast, run every loop -- tmux and site settings
	$proc_tmux_result = false;
	$proc_tmux_result = $pdo->queryOneRow($t->getMonitorSettings(), false);
	$tmux_time = (time() - $time01);

	//run queries only after time exceeded, these queries can take awhile
	if ($i == 1 || (time() - $time1 >= $monitor && $running == 1)) {

		$proc_work_result = $proc_work_result2 = $proc_work_result3 = $split_result = false;

		echo $c->info("\nThe numbers(queries) above are currently being refreshed. \nNo pane(script) can be (re)started until these have completed.\n");
		$time02 = time();

		if ($dbtype == 'mysql') {
			$split_query = $t->proc_query(4, $bookreqids, $request_hours, $db_name);
		} else {
			$split_query = $t->proc_query(5, $bookreqids, $request_hours, $db_name);
		}

		$split_result = $pdo->queryOneRow($split_query, false);

		$split_time = (time() - $time02);
		$split1_time = (time() - $time01);

		$games_releases_now = $movie_releases_now = $games_releases_now = 0;
		$audio_releases_now = $pc_releases_now = $tv_releases_now = 0;
		$xxx_releases_now = $misc_releases_now = $books_releases_now = 0;

		$time03 = time();
		if ($pdo->dbSystem() === 'mysql') {
			//This is subpartition compatible -- loops through all partitions and adds their total row counts instead of doing a slow query count
			$partitions = $pdo->queryDirect(
							sprintf("
								SELECT SUM(TABLE_ROWS) AS count, PARTITION_NAME AS category
								FROM INFORMATION_SCHEMA.PARTITIONS
								WHERE TABLE_NAME = 'releases'
								AND TABLE_SCHEMA = %s
								GROUP BY PARTITION_NAME",
								$pdo->escapeString($db_name)
							)
			);
			foreach ($partitions as $partition) {
				switch ((string) $partition['category']) {
					case 'console':
						$games_releases_now = $partition['count'];
						break;
					case 'movies':
						$movie_releases_now = $partition['count'];
						break;
					case 'audio':
						$music_releases_now = $partition['count'];
						break;
					case 'pc':
						$apps_releases_now = $partition['count'];
						break;
					case 'tv':
						$tvrage_releases_now = $partition['count'];
						break;
					case 'xxx':
						$xxx_releases_now = $partition['count'];
						break;
					case 'misc':
						$misc_releases_now = $partition['count'];
						break;
					case 'books':
						$book_releases_now = $partition['count'];
						break;
					default:
						break;
				}
			}
		} else {
			$initquery = $pdo->query($catcntqry, false);
			foreach ($initquery as $cat) {
				switch ((int) $cat['parentid']) {
					case Category::CAT_PARENT_GAME:
						$games_releases_now = $cat['count'];
						break;
					case Category::CAT_PARENT_MOVIE:
						$movie_releases_now = $cat['count'];
						break;
					case Category::CAT_PARENT_MUSIC:
						$music_releases_now = $cat['count'];
						break;
					case Category::CAT_PARENT_PC:
						$apps_releases_now = $cat['count'];
						break;
					case Category::CAT_PARENT_TV:
						$tvrage_releases_now = $cat['count'];
						break;
					case Category::CAT_PARENT_XXX:
						$xxx_releases_now = $cat['count'];
						break;
					case Category::CAT_PARENT_MISC:
						$misc_releases_now = $cat['count'];
						break;
					case Category::CAT_PARENT_BOOKS:
						$book_releases_now = $cat['count'];
						break;
					default:
						break;
				}
			}
		}
		$init_time = (time() - $time03);
		$init1_time = (time() - $time01);

		//needs to be processed query -- moved to be be able to change SQL count queries on the fly
		$proc_work = $t->proc_query(1, $bookreqids, $request_hours, $db_name);
		$proc_work2 = $t->proc_query(2, $bookreqids, $request_hours, $db_name);
		$proc_work3 = $t->proc_query(3, $bookreqids, $request_hours, $db_name);

		$time04 = time();
		$proc_work_result = $pdo->queryOneRow($proc_work, $t->rand_bool($i));
		$proc1_time = (time() - $time04);
		$proc11_time = (time() - $time01);

		$time05 = time();
		$proc_work_result2 = $pdo->queryOneRow($proc_work2, $t->rand_bool($i));
		$proc2_time = (time() - $time05);
		$proc21_time = (time() - $time01);

		$time06 = time();
		$proc_work_result3 = $pdo->queryOneRow($proc_work3, $t->rand_bool($i));
		$proc3_time = (time() - $time06);
		$proc31_time = (time() - $time01);

		$time07 = time();
		if ($tablepergroup == 1) {
			if ($pdo->dbSystem() === 'mysql') {
				$sql = 'SHOW TABLE STATUS';
			} else {
				$sql = "SELECT relname FROM pg_class WHERE relname !~ '^(pg_|sql_)' AND relkind = 'r'";
			}
			$tables = $pdo->queryDirect($sql);
			$collections_table = $binaries_table = $parts_table = $partrepair_table = 0;
			$age = time();
			if (count($tables) > 0) {
				foreach ($tables as $row) {
					$cntsql = '';
					if ($pdo->dbSystem() === 'mysql') {
						$tbl = $row['name'];
						$stamp = 'MIN(UNIX_TIMESTAMP(dateadded))';
						$orderlim = '';
						$cntsql = sprintf('
								SELECT TABLE_ROWS AS count
								FROM INFORMATION_SCHEMA.TABLES
								WHERE TABLE_NAME = %s
								AND TABLE_SCHEMA = %s',
								$pdo->escapeString($tbl),
								$pdo->escapeString($db_name)
						);
					} else {
						$tbl = $row['relname'];
						$stamp = 'extract(epoch FROM dateadded)';
						$orderlim = 'ORDER BY dateadded ASC LIMIT 1';
						$cntsql = 'SELECT COUNT(*) AS count FROM ' . $tbl;
					}

					if (strpos($tbl, 'collections_') !== false) {
						$run = $pdo->queryOneRow($cntsql, $t->rand_bool($i));
						$collections_table += $run['count'];
						$run1 =
							$pdo->queryOneRow(
									sprintf('
										SELECT %s AS dateadded
										FROM %s %s',
										$stamp,
										$tbl,
										$orderlim
									),
							$t->rand_bool($i)
						);
						if (isset($run1['dateadded']) && is_numeric($run1['dateadded']) && $run1['dateadded'] < $age) {
							$age = $run1['dateadded'];
						}
					} else if (strpos($tbl, 'binaries_') !== false) {
						$run = $pdo->queryOneRow($cntsql, $t->rand_bool($i));
						if (isset($run['count']) && is_numeric($run['count'])) {
							$binaries_table += $run['count'];
						}
					} else if (strpos($tbl, 'parts_') !== false) {
						$run = $pdo->queryOneRow($cntsql, $t->rand_bool($i));
						if (isset($run['count']) && is_numeric($run['count'])) {
							$parts_table += $run['count'];
						}
					} else if (strpos($tbl, 'partrepair_') !== false) {
						$run = $pdo->queryOneRow($cntsql, $t->rand_bool($i));
						if (isset($run['count']) && is_numeric($run['count'])) {
							$partrepair_table += $run['count'];
						}
					}
				}
				$oldestcollection = $age;
				$tpg_count_time = (time() - $time07);
				$tpg_count_1_time = (time() - $time01);
			}
		}
		$time1 = time();
	}

	if (($proc_work_result == false) || ($proc_work_result2 == false) || ($proc_work_result3 == false) || ($split_result == false) || ($proc_tmux_result == false)) {
		echo $c->error(PHP_EOL . "Monitor encountered severe errors retrieving process data from MySQL.  Please diagnose and try running again." . PHP_EOL);
		exit;
	}

	//get initial start postproc values from work queries -- this is used to determine diff variables
	if ($i == 1) {
		$nfo_remaining_start = $proc_work_result['nforemains'];
		$predb_start = $proc_work_result3['predb_matched'];
		$games_releases_proc_start = $proc_work_result['games'];
		$movie_releases_proc_start = $proc_work_result['movies'];
		$xxx_releases_proc_start = $proc_work_result['xxx'];
		$music_releases_proc_start = $proc_work_result['audio'];
		$apps_releases_proc_start = $proc_work_result2['apps'];
		$tvrage_releases_proc_start = $proc_work_result['tv'];
		$book_releases_proc_start = $proc_work_result['book'];
		$work_start = $proc_work_result2['work'] - $proc_work_result2['apps'];
		$releases_start = $proc_work_result['releases'];
		$requestid_inprogress_start = $proc_work_result3['requestid_unproc'] + $proc_work_result3['requestid_local'] + $proc_work_result3['requestid_web'];
		$work_remaining_start = $proc_work_result2['work'] - $proc_work_result2['apps'];
	}

	//get postprocess values from $proc
	$games_releases_proc = $proc_work_result['games'];
	$movie_releases_proc = $proc_work_result['movies'];
	$music_releases_proc = $proc_work_result['audio'];
	$apps_releases_proc = $proc_work_result2['apps'];
	$tvrage_releases_proc = $proc_work_result['tv'];
	$book_releases_proc = $proc_work_result['book'];
	$work_remaining_now = $proc_work_result2['work'] - $proc_work_result2['apps'];
	$xxx_releases_proc = $proc_work_result['xxx'];
	$releases_loop = $proc_work_result['releases'];
	$nfo_remaining_now = $proc_work_result['nforemains'];
	$nfo_now = $proc_work_result['nfo'];

	//get pre-matching values from $proc
	$predb_matched = $proc_work_result3['predb_matched'];
	$distinct_predb_matched = $proc_work_result3['distinct_predb_matched'];
	$requestid_inprogress = $proc_work_result3['requestid_unproc'] + $proc_work_result3['requestid_local'] + $proc_work_result3['requestid_web'];
	$requestid_matched = $proc_work_result3['requestid_matched'];

	// get various counts from split result (different queries for different database types)
	if ($tablepergroup == 0) {
		$binaries_table = $proc_work_result3['binaries_table'];
		$parts_table = $split_result['parts_table'];
		$collections_table = $proc_work_result2['collections_table'];
		$partrepair_table = $proc_work_result2['partrepair_table'];
	}
	$predb = $split_result['predb'];
	$nowTime = time();
	if ($predb > $nowTime) {
		$predb = $nowTime;
	}
	if($split_result['oldestcollection'] != null) {
		$oldestcollection = $split_result['oldestcollection'];
	}
	$backfill_groups_days = $split_result['backfill_groups_days'];
	$backfill_groups_date = $split_result['backfill_groups_date'];
	$newestadd = $split_result['newestadd'];
	$newestpre = $split_result['newestpre'];

	// assign settings from tmux and settings tables
	$collections_kill = $proc_tmux_result['collections_kill'];
	$postprocess_kill = $proc_tmux_result['postprocess_kill'];
	$backfilldays = $proc_tmux_result['backfilldays'];
	$active_groups = $proc_tmux_result['active_groups'];
	$all_groups = $proc_tmux_result['all_groups'];
	$compressed = $proc_tmux_result['compressed'];
	$colors_start = $proc_tmux_result['colors_start'];
	$colors_end = $proc_tmux_result['colors_end'];
	$colors_exc = $proc_tmux_result['colors_exc'];
	$processbooks = $proc_tmux_result['processbooks'];
	$processgames = $proc_tmux_result['processgames'];
	$processmovies = $proc_tmux_result['processmovies'];
	$processmusic = $proc_tmux_result['processmusic'];
	$processtvrage = $proc_tmux_result['processtvrage'];
	$processxxx = $proc_tmux_result['processxxx'];
	$processnfo = $proc_tmux_result['processnfo'];
	$processpar2 = $proc_tmux_result['processpar2'];
	$tmux_session = $proc_tmux_result['tmux_session'];
	$monitor = $proc_tmux_result['monitor'];
	$backfill = $proc_tmux_result['backfill'];
	$niceness = $proc_tmux_result['niceness'];
	$progressive = $proc_tmux_result['progressive'];
	$binaries = $proc_tmux_result['binaries_run'];
	$import = $proc_tmux_result['import'];
	$nzbs = $proc_tmux_result['nzbs'];
	$fix_names = $proc_tmux_result['fix_names'];
	$fix_crap = explode(', ', ($proc_tmux_result['fix_crap']));
	$fix_crap_opt = $proc_tmux_result['fix_crap_opt'];
	$update_tv = $proc_tmux_result['update_tv'];
	$post = $proc_tmux_result['post'];
	$releases_run = $proc_tmux_result['releases_run'];
	$releases_threaded = $proc_tmux_result['releases_threaded'];
	$dehash = $proc_tmux_result['dehash'];
	$newestname = $proc_tmux_result['newestname'];
	$show_query = $proc_tmux_result['show_query'];
	$running = (int)$proc_tmux_result['is_running'];
	$sharing_timer = $proc_tmux_result['sharing_timer'];

	//reset monitor paths before query
	$monitor_path = "";
	$monitor_path_a = "";
	$monitor_path_b = "";

	// assign timers from tmux table
	if ($proc_tmux_result !== false) {
		$monitor_path = $proc_tmux_result['monitor_path'];
		$monitor_path_a = $proc_tmux_result['monitor_path_a'];
		$monitor_path_b = $proc_tmux_result['monitor_path_b'];
		$post_amazon = $proc_tmux_result['post_amazon'];
		$post_timer_amazon = $proc_tmux_result['post_timer_amazon'];
		$post_non = $proc_tmux_result['post_non'];
		$post_timer_non = $proc_tmux_result['post_timer_non'];
		$seq_timer = $proc_tmux_result['seq_timer'];
		$bins_timer = $proc_tmux_result['bins_timer'];
		$back_timer = $proc_tmux_result['back_timer'];
		$import_timer = $proc_tmux_result['import_timer'];
		$rel_timer = $proc_tmux_result['rel_timer'];
		$fix_timer = $proc_tmux_result['fix_timer'];
		$crap_timer = $proc_tmux_result['crap_timer'];
		$post_timer = $proc_tmux_result['post_timer'];
		$post_kill_timer = $proc_tmux_result['post_kill_timer'];
		$tv_timer = $proc_tmux_result['tv_timer'];
		$dehash_timer = $proc_tmux_result['dehash_timer'];
		$releases_now = $proc_work_result['releases'];
	}

	//calculate releases difference
	$releases_misc_diff = number_format($releases_now - $releases_start);
	$releases_since_start = number_format($releases_now - $releases_start);
	$work_misc_diff = $work_remaining_now - $work_remaining_start;

	// This switch is used purely to zero out any post proc counts when that type of pp has been turned off
	switch (true) {
		case $processtvrage == 0:
			$tvrage_releases_proc = $tvrage_releases_proc_start = 0;
			continue;
		case $processmusic == 0:
			$music_releases_proc = $music_releases_proc_start = 0;
			continue;
		case $processmovies == 0:
			$movie_releases_proc = $movie_releases_proc_start = 0;
			continue;
		case $processxxx == 0:
			$xxx_releases_proc = $xxx_releases_proc_start = 0;
			continue;
		case $processgames == 0:
			$games_releases_proc = $games_releases_proc_start = 0;
			continue;
		case $processbooks == 0:
			$book_releases_proc = $book_releases_proc_start = 0;
			continue;
		case $processnfo == 0:
			$nfo_remaining_now = $nfo_remaining_start = 0;
			continue;
	}

	$total_work_now = $work_remaining_now + $tvrage_releases_proc + $music_releases_proc + $movie_releases_proc + $games_releases_proc + $book_releases_proc + $nfo_remaining_now + $apps_releases_proc + $xxx_releases_proc;
	if ($i == 1) {
		$total_work_start = $total_work_now;
	}

	$nfo_diff = number_format($nfo_remaining_now - $nfo_remaining_start);
	$pre_diff = number_format($predb_matched - $predb_start);
	$requestid_diff = number_format($requestid_inprogress - $requestid_inprogress_start);

	$games_diff = number_format($games_releases_proc - $games_releases_proc_start);
	$movie_diff = number_format($movie_releases_proc - $movie_releases_proc_start);
	$xxx_diff = number_format($xxx_releases_proc - $xxx_releases_proc_start);
	$music_diff = number_format($music_releases_proc - $music_releases_proc_start);
	$apps_diff = number_format($apps_releases_proc - $apps_releases_proc_start);
	$tvrage_diff = number_format($tvrage_releases_proc - $tvrage_releases_proc_start);
	$book_diff = number_format($book_releases_proc - $book_releases_proc_start);

	//formatted output
	$misc_diff = number_format($work_remaining_now - $work_start);

	$work_since_start = ($total_work_now - $total_work_start);
	$work_diff = number_format($work_since_start);

	if ($releases_now != 0) {
		$nfo_percent = sprintf("%02s", floor(($nfo_now / $releases_now) * 100));
		$pre_percent = sprintf("%02s", floor(($predb_matched / $releases_now) * 100));
		$request_percent = sprintf("%02s", floor(($requestid_matched / $releases_now) * 100));
		$games_percent = sprintf("%02s", floor(($games_releases_now / $releases_now) * 100));
		$movie_percent = sprintf("%02s", floor(($movie_releases_now / $releases_now) * 100));
		$music_percent = sprintf("%02s", floor(($music_releases_now / $releases_now) * 100));
		$apps_percent = sprintf("%02s", floor(($apps_releases_now / $releases_now) * 100));
		$xxx_percent = sprintf("%02s", floor(($xxx_releases_now / $releases_now) * 100));
		$tvrage_percent = sprintf("%02s", floor(($tvrage_releases_now / $releases_now) * 100));
		$book_percent = sprintf("%02s", floor(($book_releases_now / $releases_now) * 100));
		$misc_percent = sprintf("%02s", floor(($misc_releases_now / $releases_now) * 100));
	} else {
		$nfo_percent = $pre_percent = $request_percent = $games_percent = $movie_percent = 0;
		$xxx_percent = $music_percent = $apps_percent = $tvrage_percent = $book_percent = 0;
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
	//printf("\033[1;31m First insert:\033[0m ".$t->relativeTime("$firstdate")."\n");
	if ($running == 1) {
		printf($mask2, "Monitor Running v$version [" . $patch . "]: ", $t->relativeTime("$time"));
	} else {
		printf($mask2, "Monitor Off v$version [" . $patch . "]: ", $t->relativeTime("$time"));
	}
	printf($mask1, "USP Connections:", $usp1activeconnections . " active (" . $usp1totalconnections . " total) - " . $host . ":" . $port);
	if ($alternate_nntp) {
		printf($mask1, "USP Alternate:", $usp2activeconnections . " active (" . $usp2totalconnections . " total) - " . (($alternate_nntp) ? $host_a . ":" . $port_a : "n/a"));
	}

	printf($mask1, "Newest Release:", "$newestname");
	printf($mask1, "Release Added:", $t->relativeTime("$newestadd") . "ago");
	printf($mask1, "Predb Updated:", $t->relativeTime("$newestpre") . "ago");
	printf($mask1, "Collection Age[${delay}]:", $t->relativeTime("$oldestcollection") . "ago");
	printf($mask1, "Parts in Repair:", number_format($partrepair_table));
	if (($post == "1" || $post == "3") && $seq != 2) {
		printf($mask1, "Postprocess:", "stale for " . $t->relativeTime($time2));
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
				$disk_use = $t->decodeSize(disk_total_space($monitor_path) - disk_free_space($monitor_path));
				$disk_free = $t->decodeSize(disk_free_space($monitor_path));
				if (basename($monitor_path) == "") {
					$show = "/";
				} else {
					$show = basename($monitor_path);
				}
				printf($mask4, $show, $disk_use, $disk_free);
			}
			if (isset($monitor_path_a) && $monitor_path_a != "" && file_exists($monitor_path_a)) {
				$disk_use = $t->decodeSize(disk_total_space($monitor_path_a) - disk_free_space($monitor_path_a));
				$disk_free = $t->decodeSize(disk_free_space($monitor_path_a));
				if (basename($monitor_path_a) == "") {
					$show = "/";
				} else {
					$show = basename($monitor_path_a);
				}
				printf($mask4, $show, $disk_use, $disk_free);
			}
			if (isset($monitor_path_b) && $monitor_path_b != "" && file_exists($monitor_path_b)) {
				$disk_use = $t->decodeSize(disk_total_space($monitor_path_b) - disk_free_space($monitor_path_b));
				$disk_free = $t->decodeSize(disk_free_space($monitor_path_b));
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
		printf($mask4, "Games(1000)", number_format($games_releases_proc) . "(" . $games_diff . ")", number_format($games_releases_now) . "(" . $games_percent . "%)");
		printf($mask4, "Movie(2000)", number_format($movie_releases_proc) . "(" . $movie_diff . ")", number_format($movie_releases_now) . "(" . $movie_percent . "%)");
		printf($mask4, "Audio(3000)", number_format($music_releases_proc) . "(" . $music_diff . ")", number_format($music_releases_now) . "(" . $music_percent . "%)");
		printf($mask4, "Apps(4000)", number_format($apps_releases_proc) . "(" . $apps_diff . ")", number_format($apps_releases_now) . "(" . $apps_percent . "%)");
		printf($mask4, "TVShows(5000)", number_format($tvrage_releases_proc) . "(" . $tvrage_diff . ")", number_format($tvrage_releases_now) . "(" . $tvrage_percent . "%)");
		printf($mask4, "xXx(6000)", number_format($xxx_releases_proc) . "(" . $xxx_diff . ")", number_format($xxx_releases_now) . "(" . $xxx_percent . "%)");
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

		$pieces = explode(" ", $pdo->getAttribute(PDO::ATTR_SERVER_INFO));
		echo $c->primaryOver("\nThreads = ") . $c->headerOver($pieces[4]) . $c->primaryOver(', Opens ') . $c->headerOver($pieces[14]) . $c->primaryOver(', Tables = ') . $c->headerOver($pieces[22]) . $c->primaryOver(', Slow = ') . $c->headerOver($pieces[11]) . $c->primaryOver(', QPS = ') . $c->header($pieces[28]);
	}

	//get list of panes by name
	switch ($seq) {
		case 0:
			$panes_win_1 = shell_exec("echo `tmux list-panes -t $tmux_session:0 -F '#{pane_title}'`");
			$panes0 = str_replace("\n", '', explode(" ", $panes_win_1));
			$panes_win_2 = shell_exec("echo `tmux list-panes -t $tmux_session:1 -F '#{pane_title}'`");
			$panes1 = str_replace("\n", '', explode(" ", $panes_win_2));
			$panes_win_3 = shell_exec("echo `tmux list-panes -t $tmux_session:2 -F '#{pane_title}'`");
			$panes2 = str_replace("\n", '', explode(" ", $panes_win_3));
			break;
		case 1:
			$panes_win_1 = shell_exec("echo `tmux list-panes -t $tmux_session:0 -F '#{pane_title}'`");
			$panes0 = str_replace("\n", '', explode(" ", $panes_win_1));
			$panes_win_2 = shell_exec("echo `tmux list-panes -t $tmux_session:1 -F '#{pane_title}'`");
			$panes1 = str_replace("\n", '', explode(" ", $panes_win_2));
			$panes_win_3 = shell_exec("echo `tmux list-panes -t $tmux_session:2 -F '#{pane_title}'`");
			$panes2 = str_replace("\n", '', explode(" ", $panes_win_3));
			break;
		case 2:
			$panes_win_1 = shell_exec("echo `tmux list-panes -t $tmux_session:0 -F '#{pane_title}'`");
			$panes0 = str_replace("\n", '', explode(" ", $panes_win_1));
			$panes_win_2 = shell_exec("echo `tmux list-panes -t $tmux_session:1 -F '#{pane_title}'`");
			$panes1 = str_replace("\n", '', explode(" ", $panes_win_2));
			break;
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
		//$which_bins = "$_python ${DIR}update/python/binaries_threaded.py";
		$which_bins = "$_php ${DIR}update/nix/multiprocessing/binaries.php 0";
	} else if ($binaries == 2) {
		$which_bins = "$_python ${DIR}update/python/binaries_safe_threaded.py";
	}

	$_sleep = "$_phpn ${DIR}update/nix/tmux/bin/showsleep.php";

	if ($releases_run != 0) {
		if ($tablepergroup == 0) {
			$run_releases = "$_php ${DIR}update/update_releases.php 1 false";
		} else {
			//$run_releases = "$_python ${DIR}update/python/releases_threaded.py";
			$run_releases = "$_php ${DIR}update/nix/multiprocessing/releases.php";
		}
	}

	if ($post_non == 2) {
		$clean = ' clean ';
	} else {
		$clean = ' ';
	}

	if ($i === 2) {
		$pre_lim = '7';
	}

	if ($running == 1) {
		//run these if complete sequential not set
		if ($seq != 2) {
			//fix names
			switch ($fix_names) {
				case 1:
					$log = $t->writelog($panes1[0]);
					shell_exec("tmux respawnp -t${tmux_session}:1.0 ' \
							$_python ${DIR}update/python/groupfixrelnames_threaded.py md5 $log; \
							$_python ${DIR}update/python/groupfixrelnames_threaded.py filename $log; \
							$_python ${DIR}update/python/groupfixrelnames_threaded.py nfo $log; \
							$_python ${DIR}update/python/groupfixrelnames_threaded.py par2 $log; \
							$_python ${DIR}update/python/groupfixrelnames_threaded.py miscsorter $log; \
							$_python ${DIR}update/python/groupfixrelnames_threaded.py predbft $log; date +\"%D %T\"; $_sleep $fix_timer' 2>&1 1> /dev/null"
					);
					break;
				default:
					$color = $t->get_color($colors_start, $colors_end, $colors_exc);
					shell_exec("tmux respawnp -k -t${tmux_session}:1.0 'echo \"\033[38;5;${color}m\n${panes1[0]} has been disabled/terminated by Fix Release Names\"'");
					break;
			}
			//dehash releases
			switch ($dehash) {
				case 1:
					$log = $t->writelog($panes1[3]);
					shell_exec("tmux respawnp -t${tmux_session}:1.3 ' \
							$_php ${DIR}update/decrypt_hashes.php 1000 $log; date +\"%D %T\"; $_sleep $dehash_timer' 2>&1 1> /dev/null"
					);
					break;
				case 2:
					$log = $t->writelog($panes1[3]);
					shell_exec("tmux respawnp -t${tmux_session}:1.3 ' \
							$_php ${DIR}update/nix/tmux/bin/postprocess_pre.php $pre_lim $log; date +\"%D %T\"; $_sleep $dehash_timer' 2>&1 1> /dev/null"
					);
					break;
				case 3:
					$log = $t->writelog($panes1[3]);
					shell_exec("tmux respawnp -t${tmux_session}:1.3 ' \
							$_php ${DIR}update/nix/tmux/bin/postprocess_pre.php $pre_lim $log; \
							$_php ${DIR}update/decrypt_hashes.php 1000 $log; date +\"%D %T\"; $_sleep $dehash_timer' 2>&1 1> /dev/null"
					);
					break;
				default:
					$color = $t->get_color($colors_start, $colors_end, $colors_exc);
					shell_exec("tmux respawnp -k -t${tmux_session}:1.3 'echo \"\033[38;5;${color}m\n${panes1[3]} has been disabled/terminated by Decrypt Hashes\"'");
					break;
			}
			// Remove crap releases.
			switch ($fix_crap_opt) {
				// Do all types up to 2 hours.
				case 'All':
					$log = $t->writelog($panes1[1]);
					shell_exec("tmux respawnp -t${tmux_session}:1.1 ' \
							$_php ${DIR}testing/Release/removeCrapReleases.php true 2 $log; date +\"%D %T\"; $_sleep $crap_timer' 2>&1 1> /dev/null"
					);
					break;
				// The user has specified custom types.
				case 'Custom':
					$log = $t->writelog($panes1[1]);

					// Check how many types the user picked.
					$fcmax = count($fix_crap);

					// Make sure the user actually selected some.
					if ($fcmax > 0) {

						// If this is the first run, do a full run, else run on last 2 hours of releases.
						$fctime = '4';
						if ((($i == 1) || $fcfirstrun)) {
							$fctime = 'full';
						}

						//Check to see if the pane is dead, if so respawn it.
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
					$color = $t->get_color($colors_start, $colors_end, $colors_exc);
					shell_exec("tmux respawnp -k -t${tmux_session}:1.1 'echo \"\033[38;5;${color}m\n${panes1[1]} has been disabled/terminated by Remove Crap Releases\"'");
					break;
			}
			//run postprocess_releases additional
			switch (true) {
				case $post == 1 && ($work_remaining_now + $apps_releases_proc + $xxx_releases_proc) > 0:
					$history = str_replace(" ", '', `tmux list-panes -t${tmux_session}:2 | grep 0: | awk '{print $4;}'`);
					if ($last_history != $history) {
						$last_history = $history;
						$time2 = time();
					} else {
						if (time() - $time2 >= $post_kill_timer) {
							$color = $t->get_color($colors_start, $colors_end, $colors_exc);
							passthru("tmux respawnp -k -t${tmux_session}:2.0 'echo \"\033[38;5;${color}m\n${panes2[0]} has been terminated by Possible Hung thread\"'");
							$wipe = `tmux clearhist -t${tmux_session}:2.0`;
							$time2 = time();
						}
					}
					$dead1 = str_replace(" ", '', `tmux list-panes -t${tmux_session}:2 | grep dead | grep 0: | wc -l`);
					if ($dead1 == 1) {
						$time2 = time();
					}
					$log = $t->writelog($panes2[0]);
					shell_exec("tmux respawnp -t${tmux_session}:2.0 'echo \"\033[38;5;${color}m\"; \
							$_php ${DIR}update/nix/multiprocessing/postprocess.php add $log; date +\"%D %T\"; $_sleep $post_timer' 2>&1 1> /dev/null"
					);
					break;
				case $post == 2 && $nfo_remaining_now > 0:
					$log = $t->writelog($panes2[0]);
					shell_exec("tmux respawnp -t${tmux_session}:2.0 ' \
							$_php ${DIR}update/nix/multiprocessing/postprocess.php nfo $log; date +\"%D %T\"; $_sleep $post_timer' 2>&1 1> /dev/null"
					);
					break;
				case $post == 3 && ($nfo_remaining_now > 0 || $work_remaining_now + $apps_releases_proc + $xxx_releases_proc > 0):
					//run postprocess_releases additional
					$history = str_replace(" ", '', `tmux list-panes -t${tmux_session}:2 | grep 0: | awk '{print $4;}'`);
					if ($last_history != $history) {
						$last_history = $history;
						$time2 = time();
					} else {
						if (time() - $time2 >= $post_kill_timer) {
							$color = $t->get_color($colors_start, $colors_end, $colors_exc);
							shell_exec("tmux respawnp -k -t${tmux_session}:2.0 'echo \"\033[38;5;${color}m\n${panes2[0]} has been terminated by Possible Hung thread\"'");
							$wipe = `tmux clearhist -t${tmux_session}:2.0`;
							$time2 = time();
						}
					}
					$dead1 = str_replace(" ", '', `tmux list-panes -t${tmux_session}:2 | grep dead | grep 0: | wc -l`);
					if ($dead1 == 1) {
						$time2 = time();
					}
					$log = $t->writelog($panes2[0]);
					shell_exec("tmux respawnp -t${tmux_session}:2.0 ' \
							$_php ${DIR}update/nix/multiprocessing/postprocess.php add $log; \
							$_php ${DIR}update/nix/multiprocessing/postprocess.php nfo $log; date +\"%D %T\"; $_sleep $post_timer' 2>&1 1> /dev/null"
					);
					break;
				case $post != 0 && ($nfo_remaining_now == 0) && ($work_remaining_now + $apps_releases_proc + $xxx_releases_proc == 0):
					$color = $t->get_color($colors_start, $colors_end, $colors_exc);
					shell_exec("tmux respawnp -k -t${tmux_session}:2.0 'echo \"\033[38;5;${color}m\n${panes2[0]} has been disabled/terminated by No Misc/Nfo to process\"'");
					break;
				default:
					$color = $t->get_color($colors_start, $colors_end, $colors_exc);
					shell_exec("tmux respawnp -k -t${tmux_session}:2.0 'echo \"\033[38;5;${color}m\n${panes2[0]} has been disabled/terminated by Postprocess Additional\"'");
					break;
			}
			//run postprocess_releases non amazon
			switch (true) {
				case $post_non != 0 && ($movie_releases_proc > 0 || $tvrage_releases_proc > 0):
					$log = $t->writelog($panes2[1]);
					shell_exec("tmux respawnp -t${tmux_session}:2.1 ' \
							$_php ${DIR}update/nix/multiprocessing/postprocess.php tv $clean $log; \
							$_php ${DIR}update/nix/multiprocessing/postprocess.php mov $clean $log; date +\"%D %T\"; $_sleep $post_timer_non' 2>&1 1> /dev/null"
					);
					break;
				case $post_non != 0 && $movie_releases_proc == 0 && $tvrage_releases_proc == 0:
					$color = $t->get_color($colors_start, $colors_end, $colors_exc);
					shell_exec("tmux respawnp -k -t${tmux_session}:2.1 'echo \"\033[38;5;${color}m\n${panes2[1]} has been disabled/terminated by No Movies/TV to process\"'");
					break;
				default:
					$color = $t->get_color($colors_start, $colors_end, $colors_exc);
					shell_exec("tmux respawnp -k -t${tmux_session}:2.1 'echo \"\033[38;5;${color}m\n${panes2[1]} has been disabled/terminated by Postprocess Non-Amazon\"'");
					break;
			}
			//run postprocess_releases amazon
			switch (true) {
				case $post_amazon == 1 && ($music_releases_proc > 0 || $book_releases_proc > 0 || $games_releases_proc > 0 || $apps_releases_proc > 0 || $xxx_releases_proc > 0)
						&& ($processbooks == 1 || $processmusic == 1 || $processgames == 1  || $processxxx == 1):
					$log = $t->writelog($panes2[2]);
					shell_exec("tmux respawnp -t${tmux_session}:2.2 ' \
							$_phpn ${DIR}update/postprocess.php amazon true $log; date +\"%D %T\"; $_sleep $post_timer_amazon' 2>&1 1> /dev/null"
					);
					break;
				case $post_amazon == 1 && $processbooks == 0 && $processmusic == 0 && $processgames == 0 && $processxxx == 0:
					$color = $t->get_color($colors_start, $colors_end, $colors_exc);
					shell_exec("tmux respawnp -k -t${tmux_session}:2.2 'echo \"\033[38;5;${color}m\n${panes2[2]} has been disabled/terminated in Admin Disable Music/Books/Console/XXX\"'");
					break;
				case $post_amazon == 1 && $music_releases_proc == 0 && $book_releases_proc == 0 && $games_releases_proc == 0 && $apps_releases_proc == 0 && $xxx_releases_proc == 0:
					$color = $t->get_color($colors_start, $colors_end, $colors_exc);
					shell_exec("tmux respawnp -k -t${tmux_session}:2.2 'echo \"\033[38;5;${color}m\n${panes2[2]} has been disabled/terminated by No Music/Books/Console/Games/XXX to process\"'");
					break;
				default:
					$color = $t->get_color($colors_start, $colors_end, $colors_exc);
					shell_exec("tmux respawnp -k -t${tmux_session}:2.2 'echo \"\033[38;5;${color}m\n${panes2[2]} has been disabled/terminated by Postprocess Amazon\"'");
					break;
				}
			//update tv and theaters
			switch (true) {
				case $update_tv == 1 && (time() - $time3 >= $tv_timer || $i == 1):
					$log = $t->writelog($panes1[3]);
					shell_exec("tmux respawnp -t${tmux_session}:1.2 ' \
							$_phpn ${DIR}update/update_theaters.php $log; $_phpn ${DIR}testing/PostProc/populate_tvrage.php true $log; \
							$_phpn ${DIR}update/update_tvschedule.php $log; $_phpn ${DIR}testing/PostProc/updateTvRage.php $log; date +\"%D %T\"' 2>&1 1> /dev/null"
					);
					$time3 = time();
					break;
				case $update_tv == 1:
					$run_time = $t->relativeTime($tv_timer + $time3);
					$color = $t->get_color($colors_start, $colors_end, $colors_exc);
					shell_exec("tmux respawnp -t${tmux_session}:1.2 'echo \"\033[38;5;${color}m\n${panes1[2]} will run in T[ $run_time]\"' 2>&1 1> /dev/null");
					break;
				default:
					$color = $t->get_color($colors_start, $colors_end, $colors_exc);
					shell_exec("tmux respawnp -k -t${tmux_session}:1.2 'echo \"\033[38;5;${color}m\n${panes1[2]} has been disabled/terminated by Update TV/Theater\"'");
				}
			}

		if ($seq == 1) {
			//run nzb-import
			if (($import != 0) && ($kill_pp == false)) {
				$log = $t->writelog($panes0[1]);
				shell_exec("tmux respawnp -t${tmux_session}:0.1 ' \
						$_python ${DIR}update/python/import_threaded.py $log; date +\"%D %T\"; $_sleep $import_timer' 2>&1 1> /dev/null"
				);
			} else {
				$color = $t->get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.1 'echo \"\033[38;5;${color}m\n${panes0[1]} has been disabled/terminated by Import\"'");
			}

			//run update_binaries
			$log = $t->writelog($panes0[2]);
			if (($kill_coll == false) && ($kill_pp == false) && (time() - $time6 <= 4800)) {
				switch (true) {
					//runs all/safe less than 4800
					case $binaries != 0 && $backfill == 4 && $releases_run != 0:
						shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
								$which_bins $log; \
								$_python ${DIR}update/python/backfill_safe_threaded.py $log; \
								$run_releases $log; date +\"%D %T\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
						);
						break;
					//runs all less than 4800
					case $binaries != 0 && $backfill != 0 && $releases_run != 0:
						shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
								$which_bins $log; \
								$_python ${DIR}update/python/backfill_threaded.py $log; \
								$run_releases $log; date +\"%D %T\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
						);
						break;
					//runs bin/back/safe less than 4800
					case $binaries != 0 && $backfill == 4 && $releases_run == 0:
						shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
								$which_bins $log; \
								$_python ${DIR}update/python/backfill_safe_threaded.py $log; date +\"%D %T\"; \
								echo \"\nreleases has been disabled/terminated by Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
						);
						break;
					//runs bin/back less than 4800
					case $binaries != 0 && $backfill != 0 && $releases_run == 0:
						shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
								$which_bins $log; \
								$_python ${DIR}update/python/backfill_threaded.py $log; date +\"%D %T\"; echo \"\nreleases have been disabled/terminated by Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
						);
						break;
					//runs back/safe/rel less than 4800
					case $binaries == 0 && $backfill == 4 && $releases_run != 0:
						shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
								$_python ${DIR}update/python/backfill_safe_threaded.py $log; \
								$run_releases $log; date +\"%D %T\"; echo \"\nbinaries has been disabled/terminated by Binaries\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
						);
						break;
					//runs back/rel less than 4800
					case $binaries == 0 && $backfill != 0 && $releases_run != 0:
						shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
								$_python ${DIR}update/python/backfill_threaded.py $log; \
								$run_releases $log; date +\"%D %T\"; echo \"\nbinaries has been disabled/terminated by Binaries\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
						);
						break;
					//runs bin/rel less than 4800
					case $binaries != 0 && $backfill == 0 && $releases_run != 0:
						shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
								$which_bins $log; \
								$run_releases $log; date +\"%D %T\"; echo \"\nbackfill has been disabled/terminated by Backfill\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
						);
						break;
					//runs bin less than 4800
					case $binaries != 0 && $backfill == 0 && $releases_run == 0:
						shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
								$which_bins $log; date +\"%D %T\"; echo \"\nbackfill and releases have been disabled/terminated by Backfill and Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
						);
						break;
					//runs back/safe less than 4800
					case $binaries == 0 && $backfill == 4 && $releases_run == 0:
						shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
								$_python ${DIR}update/python/backfill_safe_threaded.py $log; date +\"%D %T\"; echo \"\nbinaries and releases have been disabled/terminated by Binaries and Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
						);
						break;
					//runs back less than 4800
					case $binaries == 0 && $backfill == 4 && $releases_run == 0:
						shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
								$_python ${DIR}update/python/backfill_threaded.py $log; date +\"%D %T\"; echo \"\nbinaries and releases have been disabled/terminated by Binaries and Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
						);
						break;
					//runs rel less than 4800
					case $binaries == 0 && $backfill == 0 && $releases_run != 0:
						shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
								$run_releases $log; date +\"%D %T\"; echo \"\nbinaries and backfill has been disabled/terminated by Binaries and Backfill\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
						);
						break;
					case $binaries == 0 && $backfill == 0 && $releases_run == 0:
						shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
								echo \"\nbinaries, backfill and releases have been disabled/terminated by Binaries, Backfill and Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
						);
						break;
				}
			} else if (($kill_coll == false) && ($kill_pp == false) && (time() - $time6 >= 4800)) {
				//run backfill all once and resets the timer
				if ($backfill != 0) {
					shell_exec("tmux respawnp -k -t${tmux_session}:0.2 ' \
						$_php ${DIR}update/python/backfill_threaded.py all $log; date +\"%D %T\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
					);
					$time6 = time();
				}
				$time6 = time();
			} else if ((($kill_coll == true) || ($kill_pp == true)) && ($releases_run != 0)) {
				$color = $t->get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\"; \
					echo \"\nbinaries and backfill has been disabled/terminated by Exceeding Limits\"; \
					$run_releases $log; date +\"%D %T\"; echo \"\nbinaries and backfill has been disabled/terminated by Exceeding Limits\"; $_sleep $seq_timer' 2>&1 1> /dev/null"
				);
			} else if (($kill_coll == true) || ($kill_pp == true)) {
				$color = $t->get_color($colors_start, $colors_end, $colors_exc);
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
			$t->run_ircscraper($tmux_session, $_php, $ipane, $run_ircscraper);
			//run Sharing
			$t->run_sharing($tmux_session, $_php, $spane, $_sleep, $sharing_timer);
		} else if ($seq == 2) {
			//run nzb-import
			if (($import != 0) && ($kill_pp == false)) {
				$log = $t->writelog($panes0[1]);
				shell_exec("tmux respawnp -t${tmux_session}:0.1 ' \
						$_python ${DIR}update/python/import_threaded.py $log; date +\"%D %T\"; $_sleep $import_timer' 2>&1 1> /dev/null"
				);
			} else {
				$color = $t->get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.1 'echo \"\033[38;5;${color}m\n${panes0[1]} has been disabled/terminated by Import\"'");
			}

			//update tv and theaters
			if (($update_tv == 1) && ((time() - $time3 >= $tv_timer) || ($i == 1))) {
				$log = $t->writelog($panes1[0]);
				shell_exec("tmux respawnp -t${tmux_session}:1.0 ' \
						$_phpn ${DIR}update/update_theaters.php $log; $_phpn ${DIR}testing/PostProc/populate_tvrage.php true $log; \
                                                $_phpn ${DIR}update/update_tvschedule.php $log; $_phpn ${DIR}testing/PostProc/updateTvRage.php $log; date +\"%D %T\"' 2>&1 1> /dev/null"
				);
				$time3 = time();
			} else if ($update_tv == 1) {
				$run_time = $t->relativeTime($tv_timer + $time3);
				$color = $t->get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -t${tmux_session}:1.0 'echo \"\033[38;5;${color}m\n${panes1[0]} will run in T[ $run_time]\"' 2>&1 1> /dev/null");
			} else {
				$color = $t->get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:1.0 'echo \"\033[38;5;${color}m\n${panes1[0]} has been disabled/terminated by Update TV/Theater\"'");
			}

			if (($post_amazon == 1) && (($music_releases_proc > 0) || ($book_releases_proc > 0) ||
					($games_releases_proc > 0) || ($apps_releases_proc > 0) || ($xxx_releases_proc > 0)) && (($processbooks != 0) || ($processmusic != 0) || ($processgames != 0) || ($processxxx != 0))) {
				//run postprocess_releases amazon
				$log = $t->writelog($panes1[1]);
				shell_exec("tmux respawnp -t${tmux_session}:1.1 ' \
						$_phpn ${DIR}update/postprocess.php amazon true $log; date +\"%D %T\"; $_sleep $post_timer_amazon' 2>&1 1> /dev/null"
				);
			} else if (($post_amazon == 1) && ($processbooks == 0) && ($processmusic == 0) && ($processgames == 0)) {
				$color = $t->get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:1.1 'echo \"\033[38;5;${color}m\n${panes1[1]} has been disabled/terminated in Admin Disable Music/Books/Console/XXX\"'");
			} else if (($post_amazon == 1) && ($music_releases_proc == 0) && ($book_releases_proc == 0) && ($games_releases_proc == 0) && ($apps_releases_proc == 0) && ($xxx_releases_proc == 0)) {
				$color = $t->get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:1.1 'echo \"\033[38;5;${color}m\n${panes1[1]} has been disabled/terminated by No Music/Books/Console/Games/XXX to process\"'");
			} else {
				$color = $t->get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:1.1 'echo \"\033[38;5;${color}m\n${panes1[1]} has been disabled/terminated by Postprocess Amazon\"'");
			}

			//run user_threaded.sh
			$log = $t->writelog($panes0[2]);
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
			$t->run_ircscraper($tmux_session, $_php, $ipane, $run_ircscraper);

			//run Sharing
			$t->run_sharing($tmux_session, $_php, $spane, $_sleep, $sharing_timer);
		} else {
			//run update_binaries
			$color = $t->get_color($colors_start, $colors_end, $colors_exc);
			if (($binaries != 0) && ($kill_coll == false) && ($kill_pp == false)) {
				$log = $t->writelog($panes0[2]);
				shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
						$which_bins $log; date +\"%D %T\"; $_sleep $bins_timer' 2>&1 1> /dev/null"
				);
			} else if (($kill_coll == true) || ($kill_pp == true)) {
				$color = $t->get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\n${panes0[2]} has been disabled/terminated by Exceeding Limits\"'");
			} else {
				$color = $t->get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\n${panes0[2]} has been disabled/terminated by Binaries\"'");
			}

			//run backfill
			if ($progressive == 1 && floor($collections_table / 500) > $back_timer) {
				$backsleep = floor($collections_table / 500);
			} else {
				$backsleep = $back_timer;
			}

			if (($backfill == 4) && ($kill_coll == false) && ($kill_pp == false) && (time() - $time6 <= 4800)) {
				$log = $t->writelog($panes0[3]);
				shell_exec("tmux respawnp -t${tmux_session}:0.3 ' \
						$_python ${DIR}update/python/backfill_safe_threaded.py $log; date +\"%D %T\"; $_sleep $backsleep' 2>&1 1> /dev/null"
				);
			} else if (($backfill != 0) && ($kill_coll == false) && ($kill_pp == false) && (time() - $time6 <= 4800)) {
				$log = $t->writelog($panes0[3]);
				shell_exec("tmux respawnp -t${tmux_session}:0.3 ' \
						$_python ${DIR}update/python/backfill_threaded.py group $log; date +\"%D %T\"; $_sleep $backsleep' 2>&1 1> /dev/null"
				);
			} else if (($backfill != 0) && ($kill_coll == false) && ($kill_pp == false) && (time() - $time6 >= 4800)) {
				$log = $t->writelog($panes0[3]);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.3 ' \
						$_python ${DIR}update/python/backfill_threaded.py all $log; date +\"%D %T\"; $_sleep $backsleep' 2>&1 1> /dev/null"
				);
				$time6 = time();
			} else if (($kill_coll == true) || ($kill_pp == true)) {
				$color = $t->get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.3 'echo \"\033[38;5;${color}m\n${panes0[3]} has been disabled/terminated by Exceeding Limits\"'");
			} else {
				$color = $t->get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.3 'echo \"\033[38;5;${color}m\n${panes0[3]} has been disabled/terminated by Backfill\"'");
			}

			//run nzb-import
			if (($import != 0) && ($kill_pp == false)) {
				$log = $t->writelog($panes0[1]);
				shell_exec("tmux respawnp -t${tmux_session}:0.1 ' \
						$_python ${DIR}update/python/import_threaded.py $log; date +\"%D %T\"; $_sleep $import_timer' 2>&1 1> /dev/null"
				);
			} else if ($kill_pp == true) {
				$color = $t->get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.1 'echo \"\033[38;5;${color}m\n${panes0[1]} has been disabled/terminated by Exceeding Limits\"'");
			} else {
				$color = $t->get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.1 'echo \"\033[38;5;${color}m\n${panes0[1]} has been disabled/terminated by Import\"'");
			}

			//run update_releases
			if ($releases_run != 0) {
				$log = $t->writelog($panes0[4]);
				shell_exec("tmux respawnp -t${tmux_session}:0.4 ' \
						$run_releases $log; date +\"%D %T\"; $_sleep $rel_timer' 2>&1 1> /dev/null"
				);
			} else {
				$color = $t->get_color($colors_start, $colors_end, $colors_exc);
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
			$t->run_ircscraper($tmux_session, $_php, $ipane, $run_ircscraper);

			//run Sharing
			$t->run_sharing($tmux_session, $_php, $spane, $_sleep, $sharing_timer);
		}
	} else if ($seq == 0) {
		for ($g = 1; $g <= 4; $g++) {
			$color = $t->get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:0.$g 'echo \"\033[38;5;${color}m\n${panes0[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 3; $g++) {
			$color = $t->get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:1.$g 'echo \"\033[38;5;${color}m\n${panes1[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 2; $g++) {
			$color = $t->get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:2.$g 'echo \"\033[38;5;${color}m\n${panes2[$g]} has been disabled/terminated by Running\"'");
		}
	} else if ($seq == 1) {
		for ($g = 1; $g <= 2; $g++) {
			$color = $t->get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:0.$g 'echo \"\033[38;5;${color}m\n${panes0[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 3; $g++) {
			$color = $t->get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:1.$g 'echo \"\033[38;5;${color}m\n${panes1[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 2; $g++) {
			$color = $t->get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:2.$g 'echo \"\033[38;5;${color}m\n${panes2[$g]} has been disabled/terminated by Running\"'");
		}
	} else if ($seq == 2) {
		for ($g = 1; $g <= 2; $g++) {
			$color = $t->get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:0.$g 'echo \"\033[38;5;${color}m\n${panes0[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 1; $g++) {
			$color = $t->get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:1.$g 'echo \"\033[38;5;${color}m\n${panes1[$g]} has been disabled/terminated by Running\"'");
		}
	}

	$i++;
	sleep(10);
}
