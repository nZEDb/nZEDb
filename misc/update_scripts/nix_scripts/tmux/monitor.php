<?php
require_once(dirname(__FILE__)."/../../../../www/config.php");
require_once(WWW_DIR."lib/postprocess.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/tmux.php");
require_once(WWW_DIR."lib/site.php");

$version="0.1r3793";

$db = new DB();
$DIR = MISC_DIR;
$db_name = DB_NAME;
$dbtype = DB_SYSTEM;

if ( isset($argv['1']) && $argv['1'] == "limited" )
	$limited = true;
else
	$limited = false;

$tmux = new Tmux();
$seq = $tmux->get()->SEQUENTIAL;
$powerline = $tmux->get()->POWERLINE;
$colors = $tmux->get()->COLORS;

$s = new Sites();
$site = $s->get();
$alternate_nntp_provider = $site->alternate_nntp;
$patch = $site->sqlpatch;

//totals per category in db, results by parentID
$qry = 'SELECT c.parentid AS parentid, COUNT(r.id) AS count FROM category c, releases r WHERE r.categoryid = c.id GROUP BY c.parentid';

//needs to be processed query
$proc_work = "SELECT
	( SELECT COUNT(*) FROM releases r, category c WHERE r.categoryid = c.id AND c.parentid = 5000 AND rageid = -1 ) AS tv,
	( SELECT COUNT(*) FROM releases r, category c WHERE r.categoryid = c.id AND c.parentid = 2000 AND r.imdbid IS NULL ) AS movies,
	( SELECT COUNT(*) FROM releases WHERE categoryid IN ( 3010, 3040, 3050 ) AND musicinfoid IS NULL AND relnamestatus != 0 ) AS audio,
	( SELECT COUNT(*) FROM releases r, category c WHERE r.categoryid = c.id AND c.parentid = 1000 AND consoleinfoid IS NULL ) AS console,
	( SELECT COUNT(*) FROM releases WHERE categoryid = 8010 AND bookinfoid IS NULL ) AS book,
	( SELECT COUNT(*) FROM releases WHERE NZBSTATUS = 1 ) AS releases,
	( SELECT COUNT(*) FROM releases WHERE nfostatus = 1 ) AS nfo,
	( SELECT COUNT(*) FROM releases WHERE nfostatus IN ( -6, -5, -4, -3, -2, -1 )) AS nforemains";

$proc_work2 = "SELECT
	( SELECT COUNT(*) FROM releases r, category c WHERE c.id = r.categoryid AND c.parentid = 4000 AND r.passwordstatus IN ( -6, -5, -4, -3, -2, -1 ) AND r.haspreview = -1 AND c.disablepreview = 0 ) AS pc,
	( SELECT COUNT(*) FROM releases r, category c WHERE c.id = r.categoryid AND c.parentid = 6000 AND r.passwordstatus IN ( -6, -5, -4, -3, -2, -1 ) AND r.haspreview = -1 AND c.disablepreview = 0 ) AS pron,
	( SELECT COUNT(*) FROM releases r, category c WHERE c.id = r.categoryid AND r.passwordstatus IN ( -6, -5, -4, -3, -2, -1 ) AND r.haspreview = -1 AND c.disablepreview = 0 ) AS work,
	( SELECT COUNT(*) FROM collections WHERE collectionhash IS NOT NULL ) AS collections_table";

$proc_work3 = "SELECT
	( SELECT COUNT(*) FROM releases WHERE relnamestatus IN (20, 21, 22) AND reqidstatus IN (0, -1) AND name REGEXP '^\\[[[:digit:]]+\\]' = 1 ) AS requestid_inprogress,
	( SELECT COUNT(*) FROM releases WHERE reqidstatus = 1 ) AS requestid_matched,
	( SELECT COUNT(*) FROM releases WHERE preid IS NOT NULL ) AS predb_matched,
	( SELECT COUNT(*) FROM binaries WHERE collectionid IS NOT NULL ) AS binaries_table";

if ($dbtype == 'mysql')
{
	$split_query = "SELECT
		( SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES where table_name = 'predb' AND TABLE_SCHEMA = '$db_name' ) AS predb,
		( SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES where table_name = 'parts' AND TABLE_SCHEMA = '$db_name' ) AS parts_table,
		( SELECT COUNT(*) FROM groups WHERE first_record IS NOT NULL AND backfill = 1 AND first_record_postdate != '2000-00-00 00:00:00' AND (now() - interval backfill_target day) < first_record_postdate ) AS backfill_groups_days,
		( SELECT COUNT(*) FROM groups WHERE first_record IS NOT NULL AND backfill = 1 AND first_record_postdate != '2000-00-00 00:00:00' AND (now() - interval datediff(curdate(),(SELECT VALUE FROM site WHERE SETTING = 'safebackfilldate')) day) < first_record_postdate) AS backfill_groups_date,
		( SELECT UNIX_TIMESTAMP(dateadded) FROM collections ORDER BY dateadded ASC LIMIT 1 ) AS oldestcollection,
		( SELECT UNIX_TIMESTAMP(adddate) FROM predb ORDER BY adddate DESC LIMIT 1 ) AS newestpre,
		( SELECT UNIX_TIMESTAMP(adddate) FROM releases WHERE nzbstatus = 1 ORDER BY adddate DESC LIMIT 1 ) AS newestadd,
		( SELECT UNIX_TIMESTAMP(dateadded) FROM nzbs ORDER BY dateadded ASC LIMIT 1 ) AS oldestnzb";
}
elseif ($dbtype == 'pgsql')
{
	$split_query = "SELECT
		( SELECT COUNT(*) FROM predb WHERE id IS NOT NULL ) AS predb,
		( SELECT COUNT(*) FROM parts WHERE id IS NOT NULL ) AS parts_table,
		( SELECT COUNT(*) FROM groups WHERE first_record IS NOT NULL AND backfill = 1 AND first_record_postdate != '2000-00-00 00:00:00' AND (now() - interval backfill_target days) < first_record_postdate ) AS backfill_groups_days,
		( SELECT COUNT(*) FROM groups WHERE first_record IS NOT NULL AND backfill = 1 AND first_record_postdate != '2000-00-00 00:00:00' AND (now() - interval datediff(curdate(),(SELECT VALUE FROM site WHERE SETTING = 'safebackfilldate')) days) < first_record_postdate) AS backfill_groups_date,
		( SELECT extract(epoch FROM dateadded) FROM collections ORDER BY dateadded ASC LIMIT 1 ) AS oldestcollection,
		( SELECT extract(epoch FROM adddate) FROM predb ORDER BY adddate DESC LIMIT 1 ) AS newestpre,
		( SELECT extract(epoch FROM adddate) FROM releases WHERE nzbstatus = 1 ORDER BY adddate DESC LIMIT 1 ) AS newestadd,
		( SELECT extract(epoch FROM dateadded) FROM nzbs ORDER BY dateadded ASC LIMIT 1 ) AS oldestnzb";
}

// tmux and site settings, refreshes every loop
$proc_tmux = "SELECT
	( SELECT name FROM releases WHERE nzbstatus = 1 ORDER BY adddate DESC LIMIT 1 ) AS newestname,
	( SELECT VALUE FROM tmux WHERE SETTING = 'MONITOR_DELAY' ) AS monitor,
	( SELECT VALUE FROM tmux WHERE SETTING = 'TMUX_SESSION' ) AS tmux_session,
	( SELECT VALUE FROM tmux WHERE SETTING = 'NICENESS' ) AS niceness,
	( SELECT VALUE FROM tmux WHERE SETTING = 'BINARIES' ) AS binaries_run,
	( SELECT VALUE FROM tmux WHERE SETTING = 'BACKFILL' ) AS backfill,
	( SELECT VALUE FROM tmux WHERE SETTING = 'IMPORT' ) AS import,
	( SELECT VALUE FROM tmux WHERE SETTING = 'NZBS' ) AS nzbs,
	( SELECT VALUE FROM tmux WHERE SETTING = 'POST' ) AS post,
	( SELECT VALUE FROM tmux WHERE SETTING = 'RELEASES' ) AS releases_run,
	( SELECT VALUE FROM tmux WHERE SETTING = 'RELEASES_THREADED' ) AS releases_threaded,
	( SELECT VALUE FROM tmux WHERE SETTING = 'FIX_NAMES' ) AS fix_names,
	( SELECT VALUE FROM tmux WHERE SETTING = 'SEQ_TIMER' ) AS seq_timer,
	( SELECT VALUE FROM tmux WHERE SETTING = 'BINS_TIMER' ) AS bins_timer,
	( SELECT VALUE FROM tmux WHERE SETTING = 'BACK_TIMER' ) AS back_timer,
	( SELECT VALUE FROM tmux WHERE SETTING = 'IMPORT_TIMER' ) AS import_timer,
	( SELECT VALUE FROM tmux WHERE SETTING = 'REL_TIMER' ) AS rel_timer,
	( SELECT VALUE FROM tmux WHERE SETTING = 'FIX_TIMER' ) AS fix_timer,
	( SELECT VALUE FROM tmux WHERE SETTING = 'POST_TIMER' ) AS post_timer,
	( SELECT VALUE FROM tmux WHERE SETTING = 'COLLECTIONS_KILL' ) AS collections_kill,
	( SELECT VALUE FROM tmux WHERE SETTING = 'POSTPROCESS_KILL' ) AS postprocess_kill,
	( SELECT VALUE FROM tmux WHERE SETTING = 'CRAP_TIMER' ) AS crap_timer,
	( SELECT VALUE FROM tmux WHERE SETTING = 'FIX_CRAP' ) AS fix_crap,
	( SELECT VALUE FROM tmux WHERE SETTING = 'TV_TIMER' ) AS tv_timer,
	( SELECT VALUE FROM tmux WHERE SETTING = 'UPDATE_TV' ) AS update_tv,
	( SELECT VALUE FROM tmux WHERE SETTING = 'POST_KILL_TIMER' ) AS post_kill_timer,
	( SELECT VALUE FROM tmux WHERE SETTING = 'MONITOR_PATH' ) AS monitor_path,
	( SELECT VALUE FROM tmux WHERE SETTING = 'MONITOR_PATH_A' ) AS monitor_path_a,
	( SELECT VALUE FROM tmux WHERE SETTING = 'MONITOR_PATH_B' ) AS monitor_path_b,
	( SELECT VALUE FROM tmux WHERE SETTING = 'SORTER' ) AS sorter,
	( SELECT VALUE FROM tmux WHERE SETTING = 'SORTER_TIMER' ) AS sorter_timer,
	( SELECT VALUE FROM tmux WHERE SETTING = 'PROGRESSIVE' ) AS progressive,
	( SELECT VALUE FROM tmux WHERE SETTING = 'DEHASH' ) AS dehash,
	( SELECT VALUE FROM tmux WHERE SETTING = 'DEHASH_TIMER' ) AS dehash_timer,
	( SELECT VALUE FROM tmux WHERE SETTING = 'BACKFILL_DAYS' ) AS backfilldays,
	( SELECT VALUE FROM site WHERE SETTING = 'debuginfo' ) AS debug,
	( SELECT VALUE FROM site WHERE SETTING = 'lookupbooks' ) AS processbooks,
	( SELECT VALUE FROM site WHERE SETTING = 'lookupmusic' ) AS processmusic,
	( SELECT VALUE FROM site WHERE SETTING = 'lookupgames' ) AS processgames,
	( SELECT VALUE FROM site WHERE SETTING = 'tmpunrarpath' ) AS tmpunrar,
	( SELECT VALUE FROM tmux WHERE SETTING = 'POST_AMAZON' ) AS post_amazon,
	( SELECT VALUE FROM tmux WHERE SETTING = 'POST_TIMER_AMAZON' ) AS post_timer_amazon,
	( SELECT VALUE FROM tmux WHERE SETTING = 'POST_NON' ) AS post_non,
	( SELECT VALUE FROM tmux WHERE SETTING = 'POST_TIMER_NON' ) AS post_timer_non,
	( SELECT COUNT(*) FROM groups WHERE active = 1 ) AS active_groups,
	( SELECT COUNT(*) FROM groups WHERE name IS NOT NULL ) AS all_groups,
	( SELECT VALUE FROM tmux WHERE SETTING = 'COLORS_START' ) AS colors_start,
	( SELECT VALUE FROM tmux WHERE SETTING = 'COLORS_END' ) AS colors_end,
	( SELECT VALUE FROM tmux WHERE SETTING = 'COLORS_EXC' ) AS colors_exc,
	( SELECT VALUE FROM tmux WHERE SETTING = 'SHOWQUERY' ) AS show_query,
	( SELECT COUNT( DISTINCT( collectionhash )) FROM nzbs WHERE collectionhash IS NOT NULL ) AS distinctnzbs,
	( SELECT COUNT(*) FROM nzbs WHERE collectionhash IS NOT NULL ) AS totalnzbs,
	( SELECT COUNT(*) FROM ( SELECT id FROM nzbs GROUP BY collectionhash, totalparts HAVING COUNT(*) >= totalparts ) AS count) AS pendingnzbs";


//get microtime
function microtime_float()
{
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
}

function decodeSize( $bytes )
{
	$types = array( 'B', 'KB', 'MB', 'GB', 'TB' );
	for( $i = 0; $bytes >= 1024 && $i < ( count( $types ) -1 ); $bytes /= 1024, $i++ );
	return( round( $bytes, 2 ) . " " . $types[$i] );
}

function writelog( $pane )
{
	$path = dirname(__FILE__)."/logs";
	$getdate = gmDate("Ymd");
	$tmux = new Tmux();
	$logs = $tmux->get()->WRITE_LOGS;
	if ( $logs == "TRUE" )
	{
		return "2>&1 | tee -a $path/$pane-$getdate.log";
	}
	else
	{
		return "";
	}
}

function get_color($colors_start, $colors_end, $colors_exc)
{
	$exceptions = str_replace(".", ".", $colors_exc);
	$exceptions = explode( ",", $exceptions );
	sort($exceptions);
	$number = mt_rand($colors_start, $colors_end - count($exceptions));
	foreach ($exceptions as $exception)
	{
		if ($number >= $exception)
			$number++;
		else
			break;
	}
	return $number;
}

function relativeTime($_time) {
	$d[0] = array(1,"sec");
	$d[1] = array(60,"min");
	$d[2] = array(3600,"hr");
	$d[3] = array(86400,"day");
	$d[4] = array(31104000,"yr");

	$w = array();

	$return = "";
	$now = TIME();
	$diff = ($now-$_time);
	$secondsLeft = $diff;

	for($i=4;$i>-1;$i--)
	{
		$w[$i] = intval($secondsLeft/$d[$i][0]);
		$secondsLeft -= ($w[$i]*$d[$i][0]);
		if($w[$i]!=0)
		{
			//$return.= abs($w[$i]). " " . $d[$i][1] . (($w[$i]>1)?'s':'') ." ";
			$return.= $w[$i]. " " . $d[$i][1] . (($w[$i]>1)?'s':'') ." ";
		}
	}
	//$return .= ($diff>0)?"ago":"left";
	return $return;
}

function command_exist($cmd) {
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

// variables
$newestadd = TIME();
$newestname = "";
$newestpre = TIME();
$oldestcollection = TIME();
$oldestnzb = TIME();

$active_groups = $all_groups = $show_query = 0;
$backfilldays = $backfill_groups_date = 0;
$book_diff = $book_percent = $book_releases_now = $book_releases_proc = 0;
$console_diff = $console_percent = $console_releases_now = $console_releases_proc = 0;
$misc_diff = $misc_percent = $misc_releases_now = 0;
$music_diff = $music_percent = $music_releases_proc = $music_releases_now = 0;
$movie_diff = $movie_percent = $movie_releases_now = $movie_releases_proc = 0;
$nfo_diff = $nfo_percent = $nfo_remaining_now = $nfo_now = 0;
$pc_diff = $pc_percent = $pc_releases_now = $pc_releases_proc = 0;
$pre_diff = $pre_percent = $predb_matched = $predb_start = $predb = 0;
$pron_diff = $pron_remaining_start = $pron_remaining_now = $pron_start = $pron_percent = $pron_releases_now = 0;
$releases_now = $releases_since_start = 0;
$request_percent = $requestid_inprogress_start = $requestid_inprogress = $requestid_diff = $requestid_matched = 0;
$total_work_now = $work_diff = $work_remaining_now = 0;
$tvrage_diff = $tvrage_percent = $tvrage_releases_now = $tvrage_releases_proc = 0;
$usp1activeconnections = $usp1totalconnections = $usp2activeconnections = $usp2totalconnections = 0;
$collections_table = $parts_table = $binaries_table = 0;
$totalnzbs = $distinctnzbs = $pendingnzbs = 0;
$tmux_time = $split_time = $init_time = $proc1_time = $proc2_time = $proc3_time = 0;
$last_history = "";

$mask1 = "\033[1;33m%-16s \033[38;5;214m%-50.50s \n";
$mask2 = "\033[1;33m%-20s \033[38;5;214m%-33.33s \n";

//create display
passthru('clear');
//printf("\033[1;31m First insert:\033[0m ".relativeTime("$firstdate")."\n");
printf($mask2, "Monitor Running v$version [".$patch."]: ", relativeTime("$time"));
printf($mask1, "USP Connections:", $usp1activeconnections." active (".$usp1totalconnections." total used) - ".NNTP_SERVER);
if ($alternate_nntp_provider == "1")
	printf($mask1, "USP Alternate:", $usp2activeconnections." active (".$usp2totalconnections." total used) - ".( ($alternate_nntp_provider == "1") ? NNTP_SERVER_A : "n/a" ));
printf($mask1, "Newest Release:", "$newestname");
printf($mask1, "Release Added:", relativeTime("$newestadd")."ago");
printf($mask1, "Predb Updated:", relativeTime("$newestpre")."ago");
printf($mask1, "Collection Age:", relativeTime("$oldestcollection")."ago");
printf($mask1, "NZBs Age:", relativeTime("$oldestnzb")."ago");

$mask = "%-16.16s %25.25s %25.25s\n";
printf("\033[1;33m\n");
printf($mask, "Collections", "Binaries", "Parts");
printf($mask, "==============================", "=========================", "==============================");
printf("\033[38;5;214m");
printf($mask, number_format($collections_table), number_format($binaries_table), number_format($parts_table));

printf("\033[1;33m\n");
printf($mask, "Category", "In Process", "In Database");
printf($mask, "==============================", "=========================", "==============================");
printf("\033[38;5;214m");
printf($mask, "NZBs",number_format($totalnzbs)."(".number_format($distinctnzbs).")", number_format($pendingnzbs));
printf($mask, "predb",number_format($predb - $predb_matched)."(".$pre_diff.")",number_format($predb_matched)."(".$pre_percent."%)");
printf($mask, "requestID",$requestid_inprogress."(".$requestid_diff.")",number_format($requestid_matched)."(".$request_percent."%)");
printf($mask, "NFO's",number_format($nfo_remaining_now)."(".$nfo_diff.")",number_format($nfo_now)."(".$nfo_percent."%)");
printf($mask, "Console(1000)",number_format($console_releases_proc)."(".$console_diff.")",number_format($console_releases_now)."(".$console_percent."%)");
printf($mask, "Movie(2000)",number_format($movie_releases_proc)."(".$movie_diff.")",number_format($movie_releases_now)."(".$movie_percent."%)");
printf($mask, "Audio(3000)",number_format($music_releases_proc)."(".$music_diff.")",number_format($music_releases_now)."(".$music_percent."%)");
printf($mask, "PC(4000)",number_format($pc_releases_proc)."(".$pc_diff.")",number_format($pc_releases_now)."(".$pc_percent."%)");
printf($mask, "TVShows(5000)",number_format($tvrage_releases_proc)."(".$tvrage_diff.")",number_format($tvrage_releases_now)."(".$tvrage_percent."%)");
printf($mask, "Pron(6000)",number_format($pron_remaining_now)."(".$pron_diff.")",number_format($pron_releases_now)."(".$pron_percent."%)");
printf($mask, "Misc(7000)",number_format($work_remaining_now)."(".$misc_diff.")",number_format($misc_releases_now)."(".$misc_percent."%)");
printf($mask, "Books(8000)",number_format($book_releases_proc)."(".$book_diff.")",number_format($book_releases_now)."(".$book_percent."%)");
printf($mask, "Total", number_format($total_work_now)."(".$work_diff.")", number_format($releases_now)."(".$releases_since_start.")");

printf("\n\033[1;33m\n");
printf($mask, "Groups", "Active", "Backfill");
printf($mask, "==============================", "=========================", "==============================");
printf("\033[38;5;214m");
if ( $backfilldays == "1" )
	printf($mask, "Activated", $active_groups."(".$all_groups.")", $backfill_groups_days."(".$all_groups.")");
else
	printf($mask, "Activated", $active_groups."(".$all_groups.")", $backfill_groups_date."(".$all_groups.")");

if ( $show_query == "TRUE" )
{
	printf("\n\033[1;33m\n");
	printf($mask, "Query Block", "Time", "Cumulative");
	printf($mask, "==============================", "=========================", "==============================");
	printf("\033[38;5;214m");
	printf($mask, "Combined", "0",  "0");
}

$monitor = 30;
$i = 1;
while( $i > 0 )
{
	//check the db connection
	if ( $db->ping() === false )
	{
		unset($db);
		$db = NULL;
		$db = new DB();
	}

	$getdate = gmDate("Ymd");

	// These queries are very fast, run every loop
	$time01 = TIME();
	$proc_tmux_result = $db->query($proc_tmux, false);
	$tmux_time = ( TIME() - $time01 );

	//run queries only after time exceeded, these queries can take awhile
	$running = $tmux->get()->RUNNING;
	if (((( TIME() - $time1 ) >= $monitor ) && ( $running == "TRUE" ) && !$limited ) || ( $i == 1 ))
	{
		$time02 = TIME();
		$split_result = $db->query($split_query, false);
		$split_time = ( TIME() - $time02 );
		$split1_time = ( TIME() - $time01 );
		$time03 = TIME();
		$initquery = $db->query($qry, false);
		$init_time = ( TIME() - $time03 );
		$init1_time = ( TIME() - $time01 );
		$time04 = TIME();
		$proc_work_result = $db->query($proc_work, false);
		$proc1_time = ( TIME() - $time04 );
		$proc11_time = ( TIME() - $time01 );
		$time05 = TIME();
		$proc_work_result2 = $db->query($proc_work2, false);
		$proc2_time = ( TIME() - $time05 );
		$proc21_time = ( TIME() - $time01 );
        $time06 = TIME();
        $proc_work_result3 = $db->query($proc_work3, true);
        $proc3_time = ( TIME() - $time06 );
        $proc31_time = ( TIME() - $time01 );
		$time1 = TIME();
		$runloop = "true";
	}
	else
		$runloop = "false";

	//get start values from $qry
	if ( $i == 1 )
	{
		if ( $proc_work_result[0]['nforemains'] != NULL ) { $nfo_remaining_start = $proc_work_result[0]['nforemains']; }
		if ( $proc_work_result3[0]['predb_matched'] != NULL ) { $predb_start = $proc_work_result3[0]['predb_matched']; }
		if ( $proc_work_result[0]['console'] != NULL ) { $console_releases_proc_start = $proc_work_result[0]['console']; }
		if ( $proc_work_result[0]['movies'] != NULL ) { $movie_releases_proc_start = $proc_work_result[0]['movies']; }
		if ( $proc_work_result[0]['audio'] != NULL ) { $music_releases_proc_start = $proc_work_result[0]['audio']; }
		if ( $proc_work_result2[0]['pc'] != NULL ) { $pc_releases_proc_start = $proc_work_result2[0]['pc']; }
		if ( $proc_work_result[0]['tv'] != NULL ) { $tvrage_releases_proc_start = $proc_work_result[0]['tv']; }
		if ( $proc_work_result[0]['book'] != NULL ) { $book_releases_proc_start = $proc_work_result[0]['book']; }
		if ( $proc_work_result2[0]['work'] != NULL ) { $work_start = $proc_work_result2[0]['work'] - $proc_work_result2[0]['pc'] - $proc_work_result2[0]['pron']; }
		if ( $proc_work_result2[0]['pron'] != NULL ) { $pron_remaining_start = $proc_work_result2[0]['pron']; }
		if ( $proc_work_result2[0]['pron'] != NULL ) { $pron_start = $proc_work_result2[0]['pron']; }
		if ( $proc_work_result[0]['releases'] != NULL ) { $releases_start = $proc_work_result[0]['releases']; }
		if ( $proc_work_result3[0]['requestid_inprogress'] != NULL ) { $requestid_inprogress_start = $proc_work_result3[0]['requestid_inprogress']; }
		if ( $proc_work_result2[0]['work'] != NULL ) { $work_remaining_start = $proc_work_result2[0]['work'] - $proc_work_result2[0]['pc'] - $proc_work_result2[0]['pron']; }
	}

	//get values from $qry
	foreach ($initquery as $cat)
	{
		if ( $cat['parentid'] == 1000 ) { $console_releases_now = $cat['count']; }
		if ( $cat['parentid'] == 2000 ) { $movie_releases_now = $cat['count']; }
		if ( $cat['parentid'] == 3000 ) { $music_releases_now = $cat['count']; }
		if ( $cat['parentid'] == 4000 ) { $pc_releases_now = $cat['count']; }
		if ( $cat['parentid'] == 5000 ) { $tvrage_releases_now = $cat['count']; }
		if ( $cat['parentid'] == 6000 ) { $pron_releases_now = $cat['count']; }
		if ( $cat['parentid'] == 7000 ) { $misc_releases_now = $cat['count']; }
		if ( $cat['parentid'] == 8000 ) { $book_releases_now = $cat['count']; }
	}

	//get values from $proc
	if ( $proc_work_result[0]['console'] != NULL ) { $console_releases_proc = $proc_work_result[0]['console']; }
	if ( $proc_work_result[0]['movies'] != NULL ) { $movie_releases_proc = $proc_work_result[0]['movies']; }
	if ( $proc_work_result[0]['audio'] != NULL ) { $music_releases_proc = $proc_work_result[0]['audio']; }
	if ( $proc_work_result2[0]['pc'] != NULL ) { $pc_releases_proc = $proc_work_result2[0]['pc']; }
	if ( $proc_work_result[0]['tv'] != NULL ) { $tvrage_releases_proc = $proc_work_result[0]['tv']; }
	if ( $proc_work_result[0]['book'] != NULL ) { $book_releases_proc = $proc_work_result[0]['book']; }
	if ( $proc_work_result2[0]['work'] != NULL ) { $work_remaining_now = $proc_work_result2[0]['work'] - $proc_work_result2[0]['pc'] - $proc_work_result2[0]['pron']; }
	if ( $proc_work_result2[0]['pron'] != NULL ) { $pron_remaining_now = $proc_work_result2[0]['pron']; }
	if ( $proc_work_result[0]['releases'] != NULL ) { $releases_loop = $proc_work_result[0]['releases']; }
	if ( $proc_work_result[0]['nforemains'] != NULL ) { $nfo_remaining_now = $proc_work_result[0]['nforemains']; }
	if ( $proc_work_result[0]['nfo'] != NULL ) { $nfo_now = $proc_work_result[0]['nfo']; }
	if ( $proc_work_result2[0]['collections_table'] != NULL ) { $collections_table = $proc_work_result2[0]['collections_table']; }
	
	if ( $proc_work_result3[0]['binaries_table'] != NULL ) { $binaries_table = $proc_work_result3[0]['binaries_table']; }
	
	if ( $split_result[0]['parts_table'] != NULL ) { $parts_table = $split_result[0]['parts_table']; }
	if ( $split_result[0]['predb'] != NULL ) { $predb = $split_result[0]['predb']; }
	
	if ( $proc_work_result3[0]['predb_matched'] != NULL ) { $predb_matched = $proc_work_result3[0]['predb_matched']; }
	if ( $proc_work_result3[0]['requestid_inprogress'] != NULL ) { $requestid_inprogress = $proc_work_result3[0]['requestid_inprogress']; }
	if ( $proc_work_result3[0]['requestid_matched'] != NULL ) { $requestid_matched = $proc_work_result3[0]['requestid_matched']; }

	if ( $proc_tmux_result[0]['collections_kill'] != NULL ) { $collections_kill = $proc_tmux_result[0]['collections_kill']; }
	if ( $proc_tmux_result[0]['postprocess_kill'] != NULL ) { $postprocess_kill = $proc_tmux_result[0]['postprocess_kill']; }
	if ( $proc_tmux_result[0]['backfilldays'] != NULL ) { $backfilldays = $proc_tmux_result[0]['backfilldays']; }
	if ( $proc_tmux_result[0]['tmpunrar'] != NULL ) { $tmpunrar = $proc_tmux_result[0]['tmpunrar']; }
    if ( $proc_tmux_result[0]['distinctnzbs'] != NULL ) { $distinctnzbs = $proc_tmux_result[0]['distinctnzbs']; }
    if ( $proc_tmux_result[0]['totalnzbs'] != NULL ) { $totalnzbs = $proc_tmux_result[0]['totalnzbs']; }
    if ( $proc_tmux_result[0]['pendingnzbs'] != NULL ) { $pendingnzbs = $proc_tmux_result[0]['pendingnzbs']; }

	if ( $proc_tmux_result[0]['active_groups'] != NULL ) { $active_groups = $proc_tmux_result[0]['active_groups']; }
	if ( $proc_tmux_result[0]['all_groups'] != NULL ) { $all_groups = $proc_tmux_result[0]['all_groups']; }

	if ( $proc_tmux_result[0]['colors_start'] != NULL ) { $colors_start = $proc_tmux_result[0]['colors_start']; }
	if ( $proc_tmux_result[0]['colors_end'] != NULL ) { $colors_end = $proc_tmux_result[0]['colors_end']; }
	if ( $proc_tmux_result[0]['colors_exc'] != NULL ) { $colors_exc = $proc_tmux_result[0]['colors_exc']; }

	if ( $proc_tmux_result[0]['processbooks'] != NULL ) { $processbooks = $proc_tmux_result[0]['processbooks']; }
	if ( $proc_tmux_result[0]['processmusic'] != NULL ) { $processmusic = $proc_tmux_result[0]['processmusic']; }
	if ( $proc_tmux_result[0]['processgames'] != NULL ) { $processgames = $proc_tmux_result[0]['processgames']; }
	if ( $proc_tmux_result[0]['tmux_session'] != NULL ) { $tmux_session = $proc_tmux_result[0]['tmux_session']; }
	if ( $proc_tmux_result[0]['monitor'] != NULL ) { $monitor = $proc_tmux_result[0]['monitor']; }
	if ( $proc_tmux_result[0]['backfill'] != NULL ) { $backfill = $proc_tmux_result[0]['backfill']; }
	if ( $proc_tmux_result[0]['niceness'] != NULL ) { $niceness = $proc_tmux_result[0]['niceness']; }
	if ( $proc_tmux_result[0]['progressive'] != NULL ) { $progressive = $proc_tmux_result[0]['progressive']; }

	if ( $proc_tmux_result[0]['binaries_run'] != NULL ) { $binaries = $proc_tmux_result[0]['binaries_run']; }
	if ( $proc_tmux_result[0]['import'] != NULL ) { $import = $proc_tmux_result[0]['import']; }
	if ( $proc_tmux_result[0]['nzbs'] != NULL ) { $nzbs = $proc_tmux_result[0]['nzbs']; }
	if ( $proc_tmux_result[0]['fix_names'] != NULL ) { $fix_names = $proc_tmux_result[0]['fix_names']; }
	if ( $proc_tmux_result[0]['fix_crap'] != NULL ) { $fix_crap = $proc_tmux_result[0]['fix_crap']; }
	if ( $proc_tmux_result[0]['sorter'] != NULL ) { $sorter = $proc_tmux_result[0]['sorter']; }
	if ( $proc_tmux_result[0]['update_tv'] != NULL ) { $update_tv = $proc_tmux_result[0]['update_tv']; }
	if ( $proc_tmux_result[0]['post'] != NULL ) { $post = $proc_tmux_result[0]['post']; }
	if ( $proc_tmux_result[0]['releases_run'] != NULL ) { $releases_run = $proc_tmux_result[0]['releases_run']; }
	if ( $proc_tmux_result[0]['releases_threaded'] != NULL ) { $releases_threaded = $proc_tmux_result[0]['releases_threaded']; }
	if ( $proc_tmux_result[0]['dehash'] != NULL ) { $dehash = $proc_tmux_result[0]['dehash']; }
	if ( $proc_tmux_result[0]['newestname'] ) { $newestname = $proc_tmux_result[0]['newestname']; }
	if ( $proc_tmux_result[0]['show_query'] ) { $show_query = $proc_tmux_result[0]['show_query']; }

	if ( $split_result[0]['oldestnzb'] != NULL ) { $oldestnzb = $split_result[0]['oldestnzb']; }
	if ( $split_result[0]['newestpre'] ) { $newestpre = $split_result[0]['newestpre']; }
	if ( $split_result[0]['oldestcollection'] != NULL ) { $oldestcollection = $split_result[0]['oldestcollection']; }
	if ( $split_result[0]['backfill_groups_days'] != NULL ) { $backfill_groups_days = $split_result[0]['backfill_groups_days']; }
	if ( $split_result[0]['backfill_groups_date'] != NULL ) { $backfill_groups_date = $split_result[0]['backfill_groups_date']; }
    if ( $split_result[0]['newestadd'] ) { $newestadd = $split_result[0]['newestadd']; }


	//reset monitor paths before query
	$monitor_path = "";
	$monitor_path_a = "";
	$monitor_path_b = "";

	if ( $proc_tmux_result[0]['monitor_path'] != NULL ) { $monitor_path = $proc_tmux_result[0]['monitor_path']; }
	if ( $proc_tmux_result[0]['monitor_path_a'] != NULL ) { $monitor_path_a = $proc_tmux_result[0]['monitor_path_a']; }
	if ( $proc_tmux_result[0]['monitor_path_b'] != NULL ) { $monitor_path_b = $proc_tmux_result[0]['monitor_path_b']; }

	if ( $proc_tmux_result[0]['debug'] != NULL ) { $debug = $proc_tmux_result[0]['debug']; }
	if ( $proc_tmux_result[0]['post_amazon'] != NULL ) { $post_amazon = $proc_tmux_result[0]['post_amazon']; }
	if ( $proc_tmux_result[0]['post_timer_amazon'] != NULL ) { $post_timer_amazon = $proc_tmux_result[0]['post_timer_amazon']; }
	if ( $proc_tmux_result[0]['post_non'] != NULL ) { $post_non = $proc_tmux_result[0]['post_non']; }
	if ( $proc_tmux_result[0]['post_timer_non'] != NULL ) { $post_timer_non = $proc_tmux_result[0]['post_timer_non']; }

	if ( $proc_tmux_result[0]['seq_timer'] != NULL ) { $seq_timer = $proc_tmux_result[0]['seq_timer']; }
	if ( $proc_tmux_result[0]['bins_timer'] != NULL ) { $bins_timer = $proc_tmux_result[0]['bins_timer']; }
	if ( $proc_tmux_result[0]['back_timer'] != NULL ) { $back_timer = $proc_tmux_result[0]['back_timer']; }
	if ( $proc_tmux_result[0]['import_timer'] != NULL ) { $import_timer = $proc_tmux_result[0]['import_timer']; }
	if ( $proc_tmux_result[0]['rel_timer'] != NULL ) { $rel_timer = $proc_tmux_result[0]['rel_timer']; }
	if ( $proc_tmux_result[0]['fix_timer'] != NULL ) { $fix_timer = $proc_tmux_result[0]['fix_timer']; }
	if ( $proc_tmux_result[0]['crap_timer'] != NULL ) { $crap_timer = $proc_tmux_result[0]['crap_timer']; }
	if ( $proc_tmux_result[0]['sorter_timer'] != NULL ) { $sorter_timer = $proc_tmux_result[0]['sorter_timer']; }
	if ( $proc_tmux_result[0]['post_timer'] != NULL ) { $post_timer = $proc_tmux_result[0]['post_timer']; }
	if ( $proc_tmux_result[0]['post_kill_timer'] != NULL ) { $post_kill_timer = $proc_tmux_result[0]['post_kill_timer']; }
	if ( $proc_tmux_result[0]['tv_timer'] != NULL ) { $tv_timer = $proc_tmux_result[0]['tv_timer']; }
	if ( $proc_tmux_result[0]['dehash_timer'] != NULL ) { $dehash_timer = $proc_tmux_result[0]['dehash_timer']; }
	if ( $proc_work_result[0]['releases'] ) { $releases_now = $proc_work_result[0]['releases']; }

	//calculate releases difference
	$releases_misc_diff = number_format( $releases_now - $releases_start );
	$releases_since_start = number_format( $releases_now - $releases_start );
	$work_misc_diff = $work_remaining_now - $work_remaining_start;
	$pron_misc_diff = $pron_remaining_now - $pron_remaining_start;

	// Make sure thes types of post procs are on or off in the site first.
	// Otherwise if they are set to off, article headers will stop downloading as these off post procs queue up.
	if ($site->lookuptvrage != 1)
		$tvrage_releases_proc = $tvrage_releases_proc_start = 0;
	if ($site->lookupmusic != 1)
		$music_releases_proc = $music_releases_proc_start = 0;
	if ($site->lookupimdb != 1)
		$movie_releases_proc = $movie_releases_proc_start = 0;
	if ($site->lookupgames != 1)
		$console_releases_proc = $console_releases_proc_start = 0;
	if ($site->lookupbooks != 1)
		$book_releases_proc = $book_releases_proc_start = 0;
	if ($site->lookupnfo != 1)
		$nfo_remaining_now = $nfo_remaining_start = 0;

	$total_work_now = $work_remaining_now + $tvrage_releases_proc + $music_releases_proc + $movie_releases_proc + $console_releases_proc + $book_releases_proc + $nfo_remaining_now + $pc_releases_proc + $pron_remaining_now;
	if ( $i == 1 ) { $total_work_start = $total_work_now; }

	$nfo_diff = number_format( $nfo_remaining_now - $nfo_remaining_start );
	$pre_diff = number_format( $predb_matched - $predb_start );
	$requestid_diff = number_format( $requestid_inprogress - $requestid_inprogress_start );

	$console_diff = number_format( $console_releases_proc - $console_releases_proc_start );
	$movie_diff = number_format( $movie_releases_proc - $movie_releases_proc_start );
	$music_diff = number_format( $music_releases_proc - $music_releases_proc_start );
	$pc_diff = number_format( $pc_releases_proc - $pc_releases_proc_start );
	$tvrage_diff = number_format( $tvrage_releases_proc - $tvrage_releases_proc_start );
	$book_diff = number_format( $book_releases_proc - $book_releases_proc_start );

	//formatted output
	$misc_diff = number_format( $work_remaining_now - $work_start );
	$pron_diff = number_format( $pron_remaining_now - $pron_start );

	$work_since_start = ( $total_work_now - $total_work_start );
	$work_diff = number_format($work_since_start);

	if ( $releases_now != 0 ) {
		$nfo_percent = sprintf( "%02s", floor(( $nfo_now / $releases_now) * 100 ));
		$pre_percent = sprintf( "%02s", floor(( $predb_matched / $releases_now) * 100 ));
		$request_percent = sprintf( "%02s", floor(( $requestid_matched / $releases_now) * 100 ));
		$console_percent = sprintf( "%02s", floor(( $console_releases_now / $releases_now) * 100 ));
		$movie_percent = sprintf( "%02s", floor(( $movie_releases_now / $releases_now) * 100 ));
		$music_percent = sprintf( "%02s", floor(( $music_releases_now / $releases_now) * 100 ));
		$pc_percent = sprintf( "%02s", floor(( $pc_releases_now / $releases_now) * 100 ));
		$pron_percent = sprintf( "%02s", floor(( $pron_releases_now / $releases_now) * 100 ));
		$tvrage_percent = sprintf( "%02s", floor(( $tvrage_releases_now / $releases_now) * 100 ));
		$book_percent = sprintf( "%02s", floor(( $book_releases_now / $releases_now) * 100 ));
		$misc_percent = sprintf( "%02s", floor(( $misc_releases_now / $releases_now) * 100 ));
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
	if ($alternate_nntp_provider == "1")
	{
		$usp1activeconnections = str_replace("\n", '', shell_exec ("ss -n --resolve | grep ".NNTP_SERVER.":".NNTP_PORT." | grep -c ESTAB"));
		$usp1totalconnections  = str_replace("\n", '', shell_exec ("ss -n --resolve | grep -c ".NNTP_SERVER.":".NNTP_PORT.""));
		$usp2activeconnections = str_replace("\n", '', shell_exec ("ss -n --resolve | grep ".NNTP_SERVER_A.":".NNTP_PORT_A." | grep -c ESTAB"));
		$usp2totalconnections  = str_replace("\n", '', shell_exec ("ss -n --resolve | grep -c ".NNTP_SERVER_A.":".NNTP_PORT_A.""));
	} else {

		$usp1activeconnections = str_replace("\n", '', shell_exec ("ss -n | grep :".NNTP_PORT." | grep -c ESTAB"));
		$usp1totalconnections  = str_replace("\n", '', shell_exec ("ss -n | grep -c :".NNTP_PORT.""));
	}

	//update display
	passthru('clear');
	//printf("\033[1;31m First insert:\033[0m ".relativeTime("$firstdate")."\n");
	printf($mask2, "Monitor Running v$version [".$patch."]: ", relativeTime("$time"));
	printf($mask1, "USP Connections:", $usp1activeconnections." active (".$usp1totalconnections." total used) - ".NNTP_SERVER);
	if ($alternate_nntp_provider == "1")
		printf($mask1, "USP Alternate:", $usp2activeconnections." active (".$usp2totalconnections." total used) - ".( ($alternate_nntp_provider == "1") ? NNTP_SERVER_A : "n/a" ));

	printf($mask1, "Newest Release:", "$newestname");
	printf($mask1, "Release Added:", relativeTime("$newestadd")."ago");
	printf($mask1, "Predb Updated:", relativeTime("$newestpre")."ago");
	printf($mask1, "Collection Age:", relativeTime("$oldestcollection")."ago");
	printf($mask1, "NZBs Age:", relativeTime("$oldestnzb")."ago");
	if ( $post == "1" || $post == "3" )
	{
		printf($mask1, "Postprocess:", "stale for ".relativeTime($time2));
	}

	printf("\033[1;33m\n");
	printf($mask, "Collections", "Binaries", "Parts");
	printf($mask, "==============================", "=========================", "==============================");
	printf("\033[38;5;214m");
	printf($mask, number_format($collections_table), number_format($binaries_table), number_format($parts_table));

	if ((( isset( $monitor_path )) && ( file_exists( $monitor_path ))) || (( isset( $monitor_path_a )) && ( file_exists( $monitor_path_a ))) || (( isset( $monitor_path_b )) && ( file_exists( $monitor_path_b ))))
	{
		printf("\033[1;33m\n");
		printf($mask, "Ramdisk", "Used", "Free");
		printf($mask, "==============================", "=========================", "==============================");
		printf("\033[38;5;214m");
		if ( isset( $monitor_path ) && $monitor_path != "" && file_exists( $monitor_path ))
		{
			$disk_use = decodeSize( disk_total_space($monitor_path) - disk_free_space($monitor_path) );
			$disk_free = decodeSize( disk_free_space($monitor_path) );
			if ( basename($monitor_path) == "" )
				$show = "/";
			else
				$show = basename($monitor_path);
			printf($mask, $show, $disk_use, $disk_free);
		}
		if ( isset( $monitor_path_a ) && $monitor_path_a != "" && file_exists( $monitor_path_a ))
		{
			$disk_use = decodeSize( disk_total_space($monitor_path_a) - disk_free_space($monitor_path_a) );
			$disk_free = decodeSize( disk_free_space($monitor_path_a) );
			if ( basename($monitor_path_a) == "" )
				$show = "/";
			else
				$show = basename($monitor_path_a);
			printf($mask, $show, $disk_use, $disk_free);
		}
		if ( isset( $monitor_path_b ) && $monitor_path_b != "" && file_exists( $monitor_path_b ))
		{
			$disk_use = decodeSize( disk_total_space($monitor_path_b) - disk_free_space($monitor_path_b) );
			$disk_free = decodeSize( disk_free_space($monitor_path_b) );
			if ( basename($monitor_path_b) == "" )
				$show = "/";
			else
				$show = basename($monitor_path_b);
			printf($mask, $show, $disk_use, $disk_free);
		}
	}

	printf("\033[1;33m\n");
	printf($mask, "Category", "In Process", "In Database");
	printf($mask, "==============================", "=========================", "==============================");
	printf("\033[38;5;214m");
	printf($mask, "NZBs",number_format($totalnzbs)."(".number_format($distinctnzbs).")", number_format($pendingnzbs));
	printf($mask, "predb", number_format($predb - $predb_matched)."(".$pre_diff.")",number_format($predb_matched)."(".$pre_percent."%)");
	printf($mask, "requestID",number_format($requestid_inprogress)."(".$requestid_diff.")",number_format($requestid_matched)."(".$request_percent."%)");
	printf($mask, "NFO's",number_format($nfo_remaining_now)."(".$nfo_diff.")",number_format($nfo_now)."(".$nfo_percent."%)");
	printf($mask, "Console(1000)",number_format($console_releases_proc)."(".$console_diff.")",number_format($console_releases_now)."(".$console_percent."%)");
	printf($mask, "Movie(2000)",number_format($movie_releases_proc)."(".$movie_diff.")",number_format($movie_releases_now)."(".$movie_percent."%)");
	printf($mask, "Audio(3000)",number_format($music_releases_proc)."(".$music_diff.")",number_format($music_releases_now)."(".$music_percent."%)");
	printf($mask, "PC(4000)",number_format($pc_releases_proc)."(".$pc_diff.")",number_format($pc_releases_now)."(".$pc_percent."%)");
	printf($mask, "TVShows(5000)",number_format($tvrage_releases_proc)."(".$tvrage_diff.")",number_format($tvrage_releases_now)."(".$tvrage_percent."%)");
	printf($mask, "Pron(6000)",number_format($pron_remaining_now)."(".$pron_diff.")",number_format($pron_releases_now)."(".$pron_percent."%)");
	printf($mask, "Misc(7000)",number_format($work_remaining_now)."(".$misc_diff.")",number_format($misc_releases_now)."(".$misc_percent."%)");
	printf($mask, "Books(8000)",number_format($book_releases_proc)."(".$book_diff.")",number_format($book_releases_now)."(".$book_percent."%)");
	printf($mask, "Total", number_format($total_work_now)."(".$work_diff.")", number_format($releases_now)."(".$releases_since_start.")");

	printf("\n\033[1;33m\n");
	printf($mask, "Groups", "Active", "Backfill");
	printf($mask, "==============================", "=========================", "==============================");
	printf("\033[38;5;214m");
	if ( $backfilldays == "1" )
		printf($mask, "Activated", $active_groups."(".$all_groups.")", $backfill_groups_days."(".$all_groups.")");
	else
		printf($mask, "Activated", $active_groups."(".$all_groups.")", $backfill_groups_date."(".$all_groups.")");

	if ( $show_query == "TRUE" )
	{
		printf("\n\033[1;33m\n");
		printf($mask, "Query Block", "Time", "Cumulative");
		printf($mask, "==============================", "=========================", "==============================");
		printf("\033[38;5;214m");
		printf($mask, "Combined", $tmux_time.", ".$split_time.", ".$init_time.", ".$proc1_time.", ".$proc2_time.", ".$proc3_time, $tmux_time.", ".$split1_time.", ".$init1_time.", ".$proc11_time.", ".$proc21_time.", ".$proc31_time);
	}

	//get list of panes by name
	$panes_win_1 = shell_exec("echo `tmux list-panes -t $tmux_session:0 -F '#{pane_title}'`");
	$panes0 = str_replace("\n", '', explode(" ", $panes_win_1));
	if ( $seq != 2 )
	{
		$panes_win_2 = shell_exec("echo `tmux list-panes -t $tmux_session:1 -F '#{pane_title}'`");
		$panes_win_3 = shell_exec("echo `tmux list-panes -t $tmux_session:2 -F '#{pane_title}'`");
		$panes_win_4 = shell_exec("echo `tmux list-panes -t $tmux_session:3 -F '#{pane_title}'`");
		$panes1 = str_replace("\n", '', explode(" ", $panes_win_2));
		$panes2 = str_replace("\n", '', explode(" ", $panes_win_3));
		$panes3 = str_replace("\n", '', explode(" ", $panes_win_4));
	}
	if ( $seq == 2 )
	{
		$panes_win_2 = shell_exec("echo `tmux list-panes -t $tmux_session:1 -F '#{pane_title}'`");
		$panes1 = str_replace("\n", '', explode(" ", $panes_win_2));
		$panes_win_3 = shell_exec("echo `tmux list-panes -t $tmux_session:2 -F '#{pane_title}'`");
		$panes2 = str_replace("\n", '', explode(" ", $panes_win_3));
	}

	if (command_exist("php5"))
		$PHP = "php5";
	else
		$PHP = "php";
	if ($debug == "1")
		$show_time = "/usr/bin/time";
	else
		$show_time = "";

	$_php = $show_time." nice -n$niceness $PHP";
	$_phpn = "nice -n$niceness $PHP";
	if (command_exist("python3"))
		$PYTHON = "python3 -OOu";
	else
		$PYTHON = "python -OOu";

	$_python = $show_time." nice -n$niceness $PYTHON";
	$_pythonn = "nice -n$niceness $PYTHON";
	$run_releases = "$_php ${DIR}update_scripts/update_releases.php 1 false";

	if (( $postprocess_kill < $total_work_now ) && ( $postprocess_kill != 0 ))
		$kill_pp = "TRUE";
	else
		$kill_pp = "FALSE";
	if (( $collections_kill < $collections_table ) && ( $collections_kill != 0 ))
		$kill_coll = "TRUE";
	else
		$kill_coll = "FALSE";

	if ($binaries != 0)
		$which_bins = "$_python ${DIR}update_scripts/threaded_scripts/binaries_threaded.py";
	elseif ($binaries == 2)
		$which_bins = "$_python ${DIR}update_scripts/threaded_scripts/binaries_safe_threaded.py";

	$_sleep = "$_phpn ${DIR}update_scripts/nix_scripts/tmux/bin/showsleep.php";

	if ( $running == "TRUE" )
	{
		//run these if complete sequential not set
		if ( $seq != 2 )
		{
			// Show all available colors
			if ($colors == "TRUE")
				shell_exec("tmux respawnp -t${tmux_session}:3.0 '$_php ${DIR}testing/Dev_testing/tmux_colors.php; sleep 30' 2>&1 1> /dev/null");

			//fix names
			if ( $fix_names == "TRUE"  &&  $i == 1 )
			{
				$log = writelog($panes1[0]);
				shell_exec("tmux respawnp -t${tmux_session}:1.0 ' \
						$_phpn ${DIR}testing/Release_scripts/fixReleaseNames.php 2 true all yes $log; \
						$_phpn ${DIR}testing/Release_scripts/fixReleaseNames.php 4 true all yes $log; \
						$_phpn ${DIR}testing/Release_scripts/fixReleaseNames.php 6 true all yes $log; date +\"%D %T\"; $_sleep $fix_timer' 2>&1 1> /dev/null");
			}
			elseif ( $fix_names == "TRUE" )
			{
				$log = writelog($panes1[0]);
				shell_exec("tmux respawnp -t${tmux_session}:1.0 ' \
						$_phpn ${DIR}testing/Release_scripts/fixReleaseNames.php 1 true all yes $log; \
						$_phpn ${DIR}testing/Release_scripts/fixReleaseNames.php 3 true all yes $log; \
						$_phpn ${DIR}testing/Release_scripts/fixReleaseNames.php 5 true all yes $log; date +\"%D %T\"; $_sleep $fix_timer' 2>&1 1> /dev/null");
			}
			else
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:1.0 'echo \"\033[38;5;${color}m\n${panes1[0]} has been disabled/terminated by Fix Release Names\"'");
			}

			//misc sorter
			if ( $sorter == "TRUE" )
			{
				$log = writelog($panes1[2]);
				shell_exec("tmux respawnp -t${tmux_session}:1.2 ' \
						$_php ${DIR}testing/Dev_testing/test_misc_sorter.php $log; date +\"%D %T\"; $_sleep $sorter_timer' 2>&1 1> /dev/null");
			}
			else
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:1.2 'echo \"\033[38;5;${color}m\n${panes1[2]} has been disabled/terminated by Misc Sorter\"'");
			}

			//dehash releases
			if ( $dehash == 1 )
			{
				$log = writelog($panes1[3]);
				shell_exec("tmux respawnp -t${tmux_session}:1.3 ' \
						$_php ${DIR}update_scripts/decrypt_hashes.php $log; date +\"%D %T\"; $_sleep $dehash_timer' 2>&1 1> /dev/null");
			}
			elseif ( $dehash == 2 )
			{
				$log = writelog($panes1[3]);
				shell_exec("tmux respawnp -t${tmux_session}:1.3 ' \
						$_php ${DIR}update_scripts/nix_scripts/tmux/bin/postprocess_pre.php $log; date +\"%D %T\"; $_sleep $dehash_timer' 2>&1 1> /dev/null");
			}
			elseif ( $dehash == 3 )
			{
				$log = writelog($panes1[3]);
				shell_exec("tmux respawnp -t${tmux_session}:1.3 ' \
						$_php ${DIR}update_scripts/nix_scripts/tmux/bin/postprocess_pre.php $log; \
						$_php ${DIR}update_scripts/decrypt_hashes.php true $log; date +\"%D %T\"; $_sleep $dehash_timer' 2>&1 1> /dev/null");
			}
			else
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:1.3 'echo \"\033[38;5;${color}m\n${panes1[3]} has been disabled/terminated by Decrypt Hashes\"'");
			}

			//remove crap releases
			if (( $fix_crap != "Disabled" ) && ( $i == 1 ))
			{
				if ( $fix_crap == "All" )
					$remove = '';
				else
					$remove = $fix_crap;
				$log = writelog($panes1[1]);
				shell_exec("tmux respawnp -t${tmux_session}:1.1 ' \
						$_php ${DIR}testing/Release_scripts/removeCrapReleases.php true full $remove $log; date +\"%D %T\"; $_sleep $crap_timer' 2>&1 1> /dev/null");
			}
			elseif ( $fix_crap != "Disabled" )
			{
				if ( $fix_crap == "All" )
					$remove = '';
				else
					$remove = $fix_crap;
				$log = writelog($panes1[1]);
				shell_exec("tmux respawnp -t${tmux_session}:1.1 ' \
						$_php ${DIR}testing/Release_scripts/removeCrapReleases.php true 2 $remove $log; date +\"%D %T\"; $_sleep $crap_timer' 2>&1 1> /dev/null");
			}
			else
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:1.1 'echo \"\033[38;5;${color}m\n${panes1[1]} has been disabled/terminated by Remove Crap Releases\"'");
			}

			if ( $post == 1 && ( $work_remaining_now + $pc_releases_proc + $pron_remaining_now ) > 0 )
			{
				//run postprocess_releases additional
				$history = str_replace( " ", '', `tmux list-panes -t${tmux_session}:2 | grep 0: | awk '{print $4;}'` );
				if ( $last_history != $history )
				{
					$last_history = $history;
					$time2 = TIME();
				}
				else
				{
					if ( TIME() - $time2 >= $post_kill_timer )
					{
						$color = get_color($colors_start, $colors_end, $colors_exc);
						passthru("tmux respawnp -k -t${tmux_session}:2.0 'echo \"\033[38;5;${color}m\n${panes2[0]} has been terminated by Possible Hung thread\"'");
						$wipe = `tmux clearhist -t${tmux_session}:2.0`;
						$time2 = TIME();
					}
				}
				$dead1 = str_replace( " ", '', `tmux list-panes -t${tmux_session}:2 | grep dead | grep 0: | wc -l` );
				if ( $dead1 == 1 )
					$time2 = TIME();
				$log = writelog($panes2[0]);
				shell_exec("tmux respawnp -t${tmux_session}:2.0 'echo \"\033[38;5;${color}m\"; \
						rm -rf $tmpunrar/*; \
						$_python ${DIR}update_scripts/threaded_scripts/postprocess_threaded.py additional $log; date +\"%D %T\"; $_sleep $post_timer' 2>&1 1> /dev/null");
			}
			elseif ( $post == 2 && $nfo_remaining_now > 0)
			{
				$log = writelog($panes2[0]);
				shell_exec("tmux respawnp -t${tmux_session}:2.0 ' \
						rm -rf $tmpunrar/*; \
						$_python ${DIR}update_scripts/threaded_scripts/postprocess_threaded.py nfo $log; date +\"%D %T\"; $_sleep $post_timer' 2>&1 1> /dev/null");
			}
			elseif (( $post == "3" ) && (( $nfo_remaining_now > 0) || ( $work_remaining_now + $pc_releases_proc + $pron_remaining_now > 0)))
			{
				//run postprocess_releases additional
				$history = str_replace( " ", '', `tmux list-panes -t${tmux_session}:2 | grep 0: | awk '{print $4;}'` );
				if ( $last_history != $history )
				{
					$last_history = $history;
					$time2 = TIME();
				}
				else
				{
					if ( TIME() - $time2 >= $post_kill_timer )
					{
						$color = get_color($colors_start, $colors_end, $colors_exc);
						shell_exec("tmux respawnp -k -t${tmux_session}:2.0 'echo \"\033[38;5;${color}m\n${panes2[0]} has been terminated by Possible Hung thread\"'");
						$wipe = `tmux clearhist -t${tmux_session}:2.0`;
						$time2 = TIME();
					}
				}
				$dead1 = str_replace( " ", '', `tmux list-panes -t${tmux_session}:2 | grep dead | grep 0: | wc -l` );
				if ( $dead1 == 1 )
					$time2 = TIME();
				$log = writelog($panes2[0]);
				shell_exec("tmux respawnp -t${tmux_session}:2.0 ' \
						rm -rf $tmpunrar/*; \
						$_python ${DIR}update_scripts/threaded_scripts/postprocess_threaded.py additional $log; \
						$_python ${DIR}update_scripts/threaded_scripts/postprocess_threaded.py nfo $log; date +\"%D %T\"; $_sleep $post_timer' 2>&1 1> /dev/null");
			}
			elseif (( $post != "0" ) && ( $nfo_remaining_now == 0) && ( $work_remaining_now + $pc_releases_proc + $pron_remaining_now == 0 ))
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:2.0 'echo \"\033[38;5;${color}m\n${panes2[0]} has been disabled/terminated by No Misc/Nfo to process\"'");
			}
			else
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:2.0 'echo \"\033[38;5;${color}m\n${panes2[0]} has been disabled/terminated by Postprocess Additional\"'");
			}

			if (( $post_non == "TRUE" ) && (( $movie_releases_proc > 0 ) || ( $tvrage_releases_proc > 0 )))
			{
				//run postprocess_releases non amazon
				$log = writelog($panes2[1]);
				shell_exec("tmux respawnp -t${tmux_session}:2.1 ' \
						$_python ${DIR}update_scripts/threaded_scripts/postprocess_threaded.py tv $log; \
						$_python ${DIR}update_scripts/threaded_scripts/postprocess_threaded.py movie $log; date +\"%D %T\"; $_sleep $post_timer_non' 2>&1 1> /dev/null");
			}
			elseif (( $post_non == "TRUE" ) && ( $movie_releases_proc == 0 ) && ( $tvrage_releases_proc == 0 ))
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:2.1 'echo \"\033[38;5;${color}m\n${panes2[1]} has been disabled/terminated by No Movies/TV to process\"'");
			}
			else
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:2.1 'echo \"\033[38;5;${color}m\n${panes2[1]} has been disabled/terminated by Postprocess Non-Amazon\"'");
			}

			if (( $post_amazon == "TRUE" ) && (( $music_releases_proc > 0 ) || ( $book_releases_proc > 0 ) || ( $console_releases_proc > 0 )) && (( $processbooks == 1 ) || ( $processmusic == 1 ) || ( $processgames == 1 )))
			{
				//run postprocess_releases amazon
				$log = writelog($panes2[2]);
				shell_exec("tmux respawnp -t${tmux_session}:2.2 ' \
						$_python ${DIR}update_scripts/threaded_scripts/postprocess_old_threaded.py amazon $log; date +\"%D %T\"; $_sleep $post_timer_amazon' 2>&1 1> /dev/null");
			}
			elseif (( $post_amazon == "TRUE" ) && ( $processbooks == 0 ) && ( $processmusic == 0 ) && ( $processgames == 0 ))
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:2.2 'echo \"\033[38;5;${color}m\n${panes2[2]} has been disabled/terminated in Admin Disable Music/Books/Console\"'");
			}
			elseif (( $post_amazon == "TRUE" ) && ( $music_releases_proc == 0 ) && ( $book_releases_proc== 0 ) && ( $console_releases_proc == 0 ))
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:2.2 'echo \"\033[38;5;${color}m\n${panes2[2]} has been disabled/terminated by No Music/Books/Console to process\"'");
			}
			else
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:2.2 'echo \"\033[38;5;${color}m\n${panes2[2]} has been disabled/terminated by Postprocess Amazon\"'");
			}

			//update tv and theaters
			if (( $update_tv == "TRUE" ) && (( TIME() - $time3 >= $tv_timer ) || ( $i == 1 )))
			{
				$log = writelog($panes1[3]);
				shell_exec("tmux respawnp -t${tmux_session}:1.4 ' \
						$_phpn ${DIR}update_scripts/update_theaters.php $log; $_phpn ${DIR}update_scripts/update_tvschedule.php $log; date +\"%D %T\"' 2>&1 1> /dev/null");
				$time3 = TIME();
			}
			elseif ( $update_tv == "TRUE" )
			{
				$run_time = relativeTime( $tv_timer + $time3 );
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -t${tmux_session}:1.4 'echo \"\033[38;5;${color}m\n${panes1[4]} will run in T[ $run_time]\"' 2>&1 1> /dev/null");
			}
			else
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:1.4 'echo \"\033[38;5;${color}m\n${panes1[4]} has been disabled/terminated by Update TV/Theater\"'");
			}
		}

		if ( $seq == 1 )
		{
			//run import-nzb-bulk
			if (( $import != "0" ) && ( $kill_pp == "FALSE" ))
			{
				$log = writelog($panes0[1]);
				shell_exec("tmux respawnp -t${tmux_session}:0.1 ' \
						$_python ${DIR}update_scripts/threaded_scripts/import_threaded.py $log; date +\"%D %T\"; $_sleep $import_timer' 2>&1 1> /dev/null");
			}
			else
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.1 'echo \"\033[38;5;${color}m\n${panes0[1]} has been disabled/terminated by Import\"'");
			}

			//run update_binaries
			$log = writelog($panes0[2]);
			if (( $kill_coll == "FALSE" ) && ( $kill_pp == "FALSE" ) && ( TIME() - $time6 <= 4800 ))
			{
				//runs all/safe less than 4800
				if (( $binaries != 0 ) && ( $backfill == "4" ) && ( $releases_run == "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$_python ${DIR}update_scripts/threaded_scripts/partrepair_threaded.py $log; \
							$which_bins $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/backfill_safe_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; \
							$run_releases $log; date +\"%D %T\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				//runs all less than 4800
				elseif (( $binaries != 0 ) && ( $backfill != "0" ) && ( $releases_run == "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$_python ${DIR}update_scripts/threaded_scripts/partrepair_threaded.py $log; \
							$which_bins $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/backfill_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; \
							$run_releases $log; date +\"%D %T\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				//runs bin/back/safe less than 4800
				elseif (( $binaries != 0 ) && ( $backfill == "4" ) && ( $releases_run != "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$_python ${DIR}update_scripts/threaded_scripts/partrepair_threaded.py $log; \
							$which_bins $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/backfill_safe_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; date +\"%D %T\"; \
							echo \"\nreleases has been disabled/terminated by Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				//runs bin/back less than 4800
				elseif (( $binaries != 0 ) && ( $backfill != "0" ) && ( $releases_run != "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$_python ${DIR}update_scripts/threaded_scripts/partrepair_threaded.py $log; \
							$which_bins $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/backfill_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; date +\"%D %T\"; echo \"\nreleases have been disabled/terminated by Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				//runs back/safe/rel less than 4800
				elseif (( $binaries != "TRUE" ) && ( $backfill == "4" ) && ( $releases_run == "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$_python ${DIR}update_scripts/threaded_scripts/backfill_safe_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; \
							$run_releases $log; date +\"%D %T\"; echo \"\nbinaries has been disabled/terminated by Binaries\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				//runs back/rel less than 4800
				elseif (( $binaries != "TRUE" ) && ( $backfill != "0" ) && ( $releases_run == "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$_python ${DIR}update_scripts/threaded_scripts/backfill_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; \
							$run_releases $log; date +\"%D %T\"; echo \"\nbinaries has been disabled/terminated by Binaries\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				//runs bin/rel less than 4800
				elseif (( $binaries != 0 ) && ( $backfill == "0" ) && ( $releases_run == "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$_python ${DIR}update_scripts/threaded_scripts/partrepair_threaded.py $log; \
							$which_bins $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; \
							$run_releases $log; date +\"%D %T\"; echo \"\nbackfill has been disabled/terminated by Backfill\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				//runs bin less than 4800
				elseif (( $binaries != 0 ) && ( $backfill == "0" ) && ( $releases_run != "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$_python ${DIR}update_scripts/threaded_scripts/partrepair_threaded.py $log; \
							$which_bins $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; date +\"%D %T\"; echo \"\nbackfill and releases have been disabled/terminated by Backfill and Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				//runs back/safe less than 4800
				elseif (( $binaries != "TRUE" ) && ( $backfill == "4" ) && ( $releases_run != "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$_python ${DIR}update_scripts/threaded_scripts/backfill_safe_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; date +\"%D %T\"; echo \"\nbinaries and releases have been disabled/terminated by Binaries and Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				//runs back less than 4800
				elseif (( $binaries != "TRUE" ) && ( $backfill == "4" ) && ( $releases_run != "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$_python ${DIR}update_scripts/threaded_scripts/backfill_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; date +\"%D %T\"; echo \"\nbinaries and releases have been disabled/terminated by Binaries and Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				//runs rel less than 4800
				elseif (( $binaries != "TRUE" ) && ( $backfill == "0" ) && ( $releases_run == "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							$run_releases $log; date +\"%D %T\"; echo \"\nbinaries and backfill has been disabled/terminated by Binaries and Backfill\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				elseif (( $binaries != "TRUE" ) && ( $backfill == "0" ) && ( $releases_run != "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
							echo \"binaries, backfill and releases have been disabled/terminated by Binaries, Backfill and Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}

			}
			elseif (( $kill_coll == "FALSE" ) && ( $kill_pp == "FALSE" ) && ( TIME() - $time6 >= 4800 ))
			{
				//run backfill all once and resets the timer
				if ( $backfill != "0" )
				{
					shell_exec("tmux respawnp -k -t${tmux_session}:0.2 ' \
						$_python ${DIR}update_scripts/threaded_scripts/backfill_threaded.py all $log; \
						$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; date +\"%D %T\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
					$time6 = TIME();
				}
				$time6 = TIME();
			}
			elseif ((( $kill_coll == "TRUE" ) || ( $kill_pp == "TRUE" )) && ( $releases_run == "TRUE" ))
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\"; \
					echo \"\nbinaries and backfill has been disabled/terminated by Exceeding Limits\"; \
					$run_releases $log; date +\"%D %T\"; echo \"\nbinaries and backfill has been disabled/terminated by Exceeding Limits\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
			}
			elseif (( $kill_coll == "TRUE" ) || ( $kill_pp == "TRUE" ))
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\n${panes0[2]} has been disabled/terminated by Exceeding Limits\"'");
			}
		}
		elseif ( $seq == 2 )
		{
			// Show all available colors
			if ($colors = "TRUE")
				shell_exec("tmux respawnp -t${tmux_session}:2.0 '$_php ${DIR}testing/Dev_testing/tmux_colors.php; sleep 30' 2>&1 1> /dev/null");

			//run import-nzb-bulk
			if (( $import != "0" ) && ( $kill_pp == "FALSE" ))
			{
				$log = writelog($panes0[1]);
				shell_exec("tmux respawnp -t${tmux_session}:0.1 ' \
						$_python ${DIR}update_scripts/threaded_scripts/import_threaded.py $log; date +\"%D %T\"; $_sleep $import_timer' 2>&1 1> /dev/null");
			}
			else
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.1 'echo \"\033[38;5;${color}m\n${panes0[1]} has been disabled/terminated by Import\"'");
			}

			//update tv and theaters
			if (( $update_tv == "TRUE" ) && (( TIME() - $time3 >= $tv_timer ) || ( $i == 1 )))
			{
				$log = writelog($panes1[0]);
				shell_exec("tmux respawnp -t${tmux_session}:1.0 ' \
						$_phpn ${DIR}update_scripts/update_theaters.php $log; $_phpn ${DIR}update_scripts/update_tvschedule.php $log; date +\"%D %T\"' 2>&1 1> /dev/null");
				$time3 = TIME();
			}
			elseif ( $update_tv == "TRUE" )
			{
				$run_time = relativeTime( $tv_timer + $time3 );
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -t${tmux_session}:1.0 'echo \"\033[38;5;${color}m\n${panes1[0]} will run in T[ $run_time]\"' 2>&1 1> /dev/null");
			}
			else
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:1.0 'echo \"\033[38;5;${color}m\n${panes1[0]} has been disabled/terminated by Update TV/Theater\"'");
			}

			//run user_threaded.sh
			$log = writelog($panes0[2]);
			shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
					${DIR}update_scripts/nix_scripts/screen/sequential/user_threaded.sh true $log; date +\"%D %T\"' 2>&1 1> /dev/null");

		}
		else
		{
			//run update_binaries
			$color = get_color($colors_start, $colors_end, $colors_exc);
			if (( $binaries != 0 ) && ( $kill_coll == "FALSE" ) && ( $kill_pp == "FALSE" ))
			{
				$log = writelog($panes0[2]);
				shell_exec("tmux respawnp -t${tmux_session}:0.2 ' \
						$_python ${DIR}update_scripts/threaded_scripts/partrepair_threaded.py $log; \
						$which_bins $log; \
						$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; date +\"%D %T\"; $_sleep $bins_timer' 2>&1 1> /dev/null");
			}
			elseif (( $kill_coll == "TRUE" ) || ( $kill_pp == "TRUE" ))
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\n${panes0[2]} has been disabled/terminated by Exceeding Limits\"'");
			}
			else
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\n${panes0[2]} has been disabled/terminated by Binaries\"'");
			}

			//run backfill
			if ( $progressive == "TRUE" && floor($collections_table / 500) > $back_timer)
				$backsleep = floor($collections_table / 500);
			else
				$backsleep = $back_timer;

			if (( $backfill == "4" ) && ( $kill_coll == "FALSE" ) && ( $kill_pp == "FALSE" ) && ( TIME() - $time6 <= 4800 ))
			{
				$log = writelog($panes0[3]);
				shell_exec("tmux respawnp -t${tmux_session}:0.3 ' \
						$_python ${DIR}update_scripts/threaded_scripts/backfill_safe_threaded.py $log; date +\"%D %T\"; $_sleep $backsleep' 2>&1 1> /dev/null");
			}
			elseif (( $backfill != "0" ) && ( $kill_coll == "FALSE" ) && ( $kill_pp == "FALSE" ) && ( TIME() - $time6 <= 4800 ))
			{
				$log = writelog($panes0[3]);
				shell_exec("tmux respawnp -t${tmux_session}:0.3 ' \
						$_python ${DIR}update_scripts/threaded_scripts/backfill_threaded.py group $log; date +\"%D %T\"; $_sleep $backsleep' 2>&1 1> /dev/null");
			}
			elseif (( $backfill != "0" ) && ( $kill_coll == "FALSE" ) && ( $kill_pp == "FALSE" ) && ( TIME() - $time6 >= 4800 ))
			{
				$log = writelog($panes0[3]);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.3 ' \
						$_python ${DIR}update_scripts/threaded_scripts/backfill_threaded.py all $log; date +\"%D %T\"; $_sleep $backsleep' 2>&1 1> /dev/null");
				$time6 = TIME();
			}
			elseif (( $kill_coll == "TRUE" ) || ( $kill_pp == "TRUE" ))
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.3 'echo \"\033[38;5;${color}m\n${panes0[3]} has been disabled/terminated by Exceeding Limits\"'");
			}
			else
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.3 'echo \"\033[38;5;${color}m\n${panes0[3]} has been disabled/terminated by Backfill\"'");
			}

			//run import-nzb-bulk
			if (( $import != "0" ) && ( $kill_pp == "FALSE" ))
			{
				$log = writelog($panes0[1]);
				shell_exec("tmux respawnp -t${tmux_session}:0.1 ' \
						$_python ${DIR}update_scripts/threaded_scripts/import_threaded.py $log; date +\"%D %T\"; $_sleep $import_timer' 2>&1 1> /dev/null");
			}
			elseif ( $kill_pp == "TRUE" )
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.1 'echo \"\033[38;5;${color}m\n${panes0[1]} has been disabled/terminated by Exceeding Limits\"'");
			}
			else
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.1 'echo \"\033[38;5;${color}m\n${panes0[1]} has been disabled/terminated by Import\"'");
			}

			//run update_releases
			if ( $releases_run == "TRUE" )
			{
				$log = writelog($panes0[4]);
				shell_exec("tmux respawnp -t${tmux_session}:0.4 ' \
						$run_releases $log; date +\"%D %T\"; $_sleep $rel_timer' 2>&1 1> /dev/null");
			}
			else
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.4 'echo \"\033[38;5;${color}m\n${panes0[4]} has been disabled/terminated by Releases\"'");
			}
		}
	}
	elseif ( $seq == 0 )
	{
		for ($g=1; $g<=4; $g++)
		{
			$color = get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:0.$g 'echo \"\033[38;5;${color}m\n${panes0[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g=0; $g<=4; $g++)
		{
			$color = get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:1.$g 'echo \"\033[38;5;${color}m\n${panes1[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g=0; $g<=2; $g++)
		{
			$color = get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:2.$g 'echo \"\033[38;5;${color}m\n${panes2[$g]} has been disabled/terminated by Running\"'");
		}
	}
	elseif ( $seq == 1 )
	{
		for ($g=1; $g<=2; $g++)
		{
			$color = get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:0.$g 'echo \"\033[38;5;${color}m\n${panes0[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g=0; $g<=4; $g++)
		{
			$color = get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:1.$g 'echo \"\033[38;5;${color}m\n${panes1[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g=0; $g<=2; $g++)
		{
			$color = get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:2.$g 'echo \"\033[38;5;${color}m\n${panes2[$g]} has been disabled/terminated by Running\"'");
		}
	}
	elseif ( $seq == 2 )
	{
		for ($g=1; $g<=2; $g++)
		{
			$color = get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:0.$g 'echo \"\033[38;5;${color}m\n${panes0[$g]} has been disabled/terminated by Running\"'");
		}
	}

	$i++;
	sleep(5);
}
?>
