<?php

require_once(dirname(__FILE__)."/../../../../www/config.php");
require_once(WWW_DIR."/lib/postprocess.php");
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/tmux.php");
require_once(WWW_DIR."/lib/site.php");

$version="0.1r2075";

$db = new DB();
$DIR = WWW_DIR."/..";
$db_name = DB_NAME;

$tmux = new Tmux;
$seq = $tmux->get()->SEQUENTIAL;

//totals per category in db, results by parentID
$qry = "SELECT COUNT( releases.categoryID ) AS cnt, parentID FROM releases INNER JOIN category ON releases.categoryID = category.ID WHERE nzbstatus = 1 and parentID IS NOT NULL GROUP BY parentID";

//needs to be processed query
$proc = "SELECT
	( SELECT COUNT( groupID ) AS cnt from releases where consoleinfoID IS NULL and categoryID BETWEEN 1000 AND 1999 and nzbstatus = 1 ) AS console,
	( SELECT COUNT( groupID ) AS cnt from releases where imdbID IS NULL and categoryID BETWEEN 2000 AND 2999 and nzbstatus = 1 ) AS movies,
	( SELECT COUNT( groupID ) AS cnt from releases where musicinfoID IS NULL and categoryID BETWEEN 3000 AND 3999 and nzbstatus = 1 ) AS audio,
	( SELECT COUNT( groupID ) AS cnt from releases r left join category c on c.ID = r.categoryID where (categoryID BETWEEN 4000 AND 4999 and nzbstatus = 1 and ((r.passwordstatus between -6 and -1) and (r.haspreview = -1 and c.disablepreview = 0)))) AS pc,
	( SELECT COUNT( groupID ) AS cnt from releases where rageID = -1 and categoryID BETWEEN 5000 AND 5999 and nzbstatus = 1 ) AS tv,
	( SELECT COUNT( groupID ) AS cnt from releases r left join category c on c.ID = r.categoryID where nzbstatus = 1 and (r.passwordstatus between -6 and -1) and (r.haspreview = -1 and c.disablepreview = 0)) AS work,
	( SELECT COUNT( groupID ) AS cnt from releases where bookinfoID IS NULL and nzbstatus = 1 and categoryID = 8010 ) AS book,
	( SELECT COUNT( groupID ) AS cnt from releases where nzbstatus = 1 ) AS releases,
	( SELECT COUNT( groupID ) AS cnt FROM releases WHERE nfostatus = 1 ) AS nfo,
	( SELECT COUNT( ID ) AS cnt FROM groups WHERE active = 1 ) AS active_groups,
	( SELECT COUNT( ID ) AS cnt FROM groups WHERE backfill = 1 ) AS backfill_groups,
	( SELECT COUNT( groupID ) AS cnt FROM releases r WHERE r.nfostatus between -6 and -1 and nzbstatus = 1 ) AS nforemains,
	( SELECT UNIX_TIMESTAMP(adddate) from releases order by adddate desc limit 1 ) AS newestadd,
	( SELECT COUNT( ID ) from collections ) collections_table,
	( SELECT TABLE_ROWS from INFORMATION_SCHEMA.TABLES where table_name = 'binaries' AND TABLE_SCHEMA = '$db_name' ) AS binaries_table,
	( SELECT TABLE_ROWS from INFORMATION_SCHEMA.TABLES where table_name = 'parts' AND TABLE_SCHEMA = '$db_name' ) AS parts_table,
	( SELECT value from tmux where setting = 'DEFRAG_CACHE' ) AS defrag,
	( SELECT value from tmux where setting = 'MONITOR_DELAY' ) AS monitor,
	( SELECT value from tmux where setting = 'COLLECTIONS_KILL' ) AS collections_kill,
	( SELECT value from tmux where setting = 'POSTPROCESS_KILL' ) AS postprocess_kill,
	( SELECT value from tmux where setting = 'TMUX_SESSION' ) AS tmux_session,
	( SELECT value from tmux where setting = 'NICENESS' ) AS niceness,
	( SELECT value from tmux where setting = 'RUNNING' ) AS running,
	( SELECT value from tmux where setting = 'BINARIES' ) AS binaries_run,
	( SELECT value from tmux where setting = 'BACKFILL' ) AS backfill,
	( SELECT value from tmux where setting = 'IMPORT' ) AS import,
	( SELECT value from tmux where setting = 'NZBS' ) AS nzbs,
	( SELECT value from tmux where setting = 'FIX_NAMES' ) AS fix_names,
	( SELECT value from tmux where setting = 'FIX_CRAP' ) AS fix_crap,
	( SELECT value from tmux where setting = 'SORTER' ) AS sorter,
	( SELECT value from tmux where setting = 'POST' ) AS post,
	( SELECT value from tmux where setting = 'UPDATE_TV' ) AS update_tv,
	( SELECT value from tmux where setting = 'RELEASES' ) AS releases_run,
	( SELECT value from tmux where setting = 'RELEASES_THREADED' ) AS releases_threaded,
	( SELECT value from tmux where setting = 'MYSQL_PROC' ) AS process_list,
	( SELECT value from tmux where setting = 'SEQ_TIMER' ) AS seq_timer,
	( SELECT value from tmux where setting = 'BINS_TIMER' ) AS bins_timer,
	( SELECT value from tmux where setting = 'BACK_TIMER' ) AS back_timer,
	( SELECT value from tmux where setting = 'IMPORT_TIMER' ) AS import_timer,
	( SELECT value from tmux where setting = 'REL_TIMER' ) AS rel_timer,
	( SELECT value from tmux where setting = 'FIX_TIMER' ) AS fix_timer,
	( SELECT value from tmux where setting = 'CRAP_TIMER' ) AS crap_timer,
	( SELECT value from tmux where setting = 'SORTER_TIMER' ) AS sorter_timer,
	( SELECT value from tmux where setting = 'TV_TIMER' ) AS tv_timer,
	( SELECT value from tmux where setting = 'POST_TIMER' ) AS post_timer,
	( SELECT value from tmux where setting = 'POST_KILL_TIMER' ) AS post_kill_timer,
	( SELECT value from tmux where setting = 'OPTIMIZE' ) AS optimize_tables,
	( SELECT value from tmux where setting = 'OPTIMIZE_TIMER' ) AS optimize_timer,
	( SELECT value from tmux where setting = 'MONITOR_PATH' ) AS monitor_path,
	( SELECT value from site where setting = 'debuginfo' ) AS debug,
	( SELECT name from releases order by adddate desc limit 1 ) AS newestaddname";

//flush query cache
$qcache = "FLUSH QUERY CACHE";

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
	$tmux = new Tmux;
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

function get_color()
{
	$from = 1;
	$to = 231;
	$exceptions = array( 4, 8, 16, 17, 18, 19, 52, 53, 59, 60, 67 );
	sort($exceptions); // lets us use break; in the foreach reliably
	$number = mt_rand($from, $to - count($exceptions)); // or mt_rand()
	foreach ($exceptions as $exception) {
		if ($number >= $exception) {
			$number++; // make up for the gap
		} else /*if ($number < $exception)*/ {
			break;
		}
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
	$returnVal = shell_exec("which $cmd");
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
$time8 = TIME();
$time9 = TIME();

//initial values
$newestname = "Unknown";
$newestdate = TIME();
$releases_now_formatted = 0;
$releases_since_start = 0;
$work_diff = 0;
$misc_diff = 0;
$book_diff = 0;
$tvrage_diff = 0;
$pc_diff = 0;
$music_diff = 0;
$movie_diff = 0;
$console_diff = 0;
$nfo_diff = 0;

$misc_percent = 0;
$book_percent = 0;
$tvrage_percent = 0;
$pc_percent = 0;
$music_percent = 0;
$movie_percent = 0;
$console_percent = 0;
$nfo_percent = 0;

$work_start = 0;
$releases_start = 0;

$console_releases_now = 0;
$movie_releases_now = 0;
$music_releases_now = 0;
$pc_releases_now = 0;
$tvrage_releases_now = 0;
$misc_releases_now = 0;
$book_releases_now = 0;
$nfo_remaining_now = 0;
$nfo_now = 0;
$releases_now = 0;
$collections_table = 0;
$parts_table = 0;
$binaries_table = 0;

$console_releases_proc = 0;
$movie_releases_proc = 0;
$music_releases_proc = 0;
$pc_releases_proc = 0;
$tvrage_releases_proc = 0;
$work_remaining_now = 0;
$book_releases_proc = 0;

$console_releases_proc_start = 0;
$movie_releases_proc_start = 0;
$music_releases_proc_start = 0;
$pc_releases_proc_start = 0;
$tvrage_releases_proc_start = 0;
$book_releases_proc_start = 0;
$work_remaining_start = 0;
$nfo_remaining_start = 0;

$misc_releases_now = 0;
$work_remaining_now = 0;
$book_releases_now = 0;
$book_releases_proc = 0;
$tvrage_releases_now = 0;
$tvrage_releases_proc = 0;
$pc_releases_now = 0;
$pc_releases_proc = 0;
$music_releases_now = 0;
$music_releases_proc = 0;
$movie_releases_now = 0;
$movie_releases_proc = 0;
$console_releases_now = 0;
$console_releases_proc = 0;
$total_work_now = 0;
$last_history = "";
$debug = 0;
$active_groups = 0;
$backfill_groups = 0;

$mask1 = "\033[1;33m%-16s \033[38;5;214m%-44.44s \n";
$mask2 = "\033[1;33m%-16s \033[38;5;214m%-34.34s \n";

//create display
passthru('clear');
//printf("\033[1;31m First insert:\033[0m ".relativeTime("$firstdate")."\n");
printf($mask2, "Monitor Running v$version: ", relativeTime("$time"));
printf($mask1, "Newest Release:", "$newestname");
printf($mask1, "Release Added:", relativeTime("$newestdate")."ago");

$mask = "%-15.15s %22.22s %22.22s\n";
printf("\033[1;33m\n");
printf($mask, "Collections", "Binaries", "Parts");
printf($mask, "====================", "====================", "====================");
printf("\033[38;5;214m");
printf($mask, number_format($collections_table), number_format($binaries_table), number_format($parts_table));

printf("\033[1;33m\n");
printf($mask, "Category", "In Process", "In Database");
printf($mask, "====================", "====================", "====================");
printf("\033[38;5;214m");
printf($mask, "NFO's",number_format($nfo_remaining_now)."(".$nfo_diff.")",number_format($nfo_now)."(".$nfo_percent."%)");
printf($mask, "Console(1000)",number_format($console_releases_proc)."(".$console_diff.")",number_format($console_releases_now)."(".$console_percent."%)");
printf($mask, "Movie(2000)",number_format($movie_releases_proc)."(".$movie_diff.")",number_format($movie_releases_now)."(".$movie_percent."%)");
printf($mask, "Audio(3000)",number_format($music_releases_proc)."(".$music_diff.")",number_format($music_releases_now)."(".$music_percent."%)");
printf($mask, "PC(4000)",number_format($pc_releases_proc)."(".$pc_diff.")",number_format($pc_releases_now)."(".$pc_percent."%)");
printf($mask, "TVShows(5000)",number_format($tvrage_releases_proc)."(".$tvrage_diff.")",number_format($tvrage_releases_now)."(".$tvrage_percent."%)");
printf($mask, "Misc(7000)",number_format($work_remaining_now)."(".$misc_diff.")",number_format($misc_releases_now)."(".$misc_percent."%)");
printf($mask, "Books(8000)",number_format($book_releases_proc)."(".$book_diff.")",number_format($book_releases_now)."(".$book_percent."%)");
printf($mask, "Total", number_format($total_work_now)."(".$work_diff.")", number_format($releases_now)."(".$releases_since_start.")");

printf("\n\033[1;33m\n");
printf($mask, "Groups", "Active", "Backfill");
printf($mask, "====================", "====================", "====================");
printf("\033[38;5;214m");
printf($mask, "Activated", $active_groups, $backfill_groups);

$monitor = 30;
$i = 1;
while( $i > 0 )
{

	//get microtime at start of loop
	$time_loop_start = microtime_float();

	$getdate = gmDate("Ymd");

	//run queries
	if ((( TIME() - $time2 ) >= $monitor ) || ( $i == 1 )) {
		//get microtime to at start of queries
		$query_timer_start=microtime_float();
		$result = @$db->query($qry);
		$initquery = array();
		foreach ($result as $cat=>$sub)
		{
			$initquery[$sub['parentID']] = $sub['cnt'];
		}
		$proc_result = @$db->query($proc);
		$time2 = TIME();
		$runloop = "true";
	} else {
		$runloop = "false";
	}

	//get start values from $qry
	if ( $i == 1 ) 
	{
		if ( @$proc_result[0]['nforemains'] != NULL ) { $nfo_remaining_start = $proc_result[0]['nforemains']; }
		if ( @$proc_result[0]['console'] != NULL ) { $console_releases_proc_start = $proc_result[0]['console']; }
		if ( @$proc_result[0]['movies'] != NULL ) { $movie_releases_proc_start = $proc_result[0]['movies']; }
		if ( @$proc_result[0]['audio'] != NULL ) { $music_releases_proc_start = $proc_result[0]['audio']; }
		if ( @$proc_result[0]['pc'] != NULL ) { $pc_releases_proc_start = $proc_result[0]['pc']; }
		if ( @$proc_result[0]['tv'] != NULL ) { $tvrage_releases_proc_start = $proc_result[0]['tv']; }
		if ( @$proc_result[0]['book'] != NULL ) { $book_releases_proc_start = $proc_result[0]['book']; }
		if ( @$proc_result[0]['work'] != NULL ) { $work_remaining_start = $proc_result[0]['work']; }
		if ( @$proc_result[0]['work'] != NULL ) { $work_start = $proc_result[0]['work']; }
		if ( @$proc_result[0]['releases'] != NULL ) { $releases_start = $proc_result[0]['releases']; }
	}

	//get values from $qry
	if ( @$initquery['1000'] != NULL ) { $console_releases_now = $initquery['1000']; }
	if ( @$initquery['2000'] != NULL ) { $movie_releases_now = $initquery['2000']; }
	if ( @$initquery['3000'] != NULL ) { $music_releases_now = $initquery['3000']; }
	if ( @$initquery['4000'] != NULL ) { $pc_releases_now = $initquery['4000']; }
	if ( @$initquery['5000'] != NULL ) { $tvrage_releases_now = $initquery['5000']; }
	if ( @$initquery['8000'] != NULL ) { $book_releases_now = $initquery['8000']; }
	if ( @$initquery['7000'] != NULL ) { $misc_releases_now = $initquery['7000']; }

	//get values from $proc
	if ( @$proc_result[0]['console'] != NULL ) { $console_releases_proc = $proc_result[0]['console']; }
	if ( @$proc_result[0]['console'] != NULL ) { $console_releases_proc = $proc_result[0]['console']; }
	if ( @$proc_result[0]['movies'] != NULL ) { $movie_releases_proc = $proc_result[0]['movies']; }
	if ( @$proc_result[0]['audio'] != NULL ) { $music_releases_proc = $proc_result[0]['audio']; }
	if ( @$proc_result[0]['pc'] != NULL ) { $pc_releases_proc = $proc_result[0]['pc']; }
	if ( @$proc_result[0]['tv'] != NULL ) { $tvrage_releases_proc = $proc_result[0]['tv']; }
	if ( @$proc_result[0]['book'] != NULL ) { $book_releases_proc = $proc_result[0]['book']; }
	if ( @$proc_result[0]['work'] != NULL ) { $work_remaining_now = $proc_result[0]['work']; }
	if ( @$proc_result[0]['releases'] != NULL ) { $releases_loop = $proc_result[0]['releases']; }
	if ( @$proc_result[0]['nforemains'] != NULL ) { $nfo_remaining_now = $proc_result[0]['nforemains']; }
	if ( @$proc_result[0]['nfo'] != NULL ) { $nfo_now = $proc_result[0]['nfo']; }
	if ( @$proc_result[0]['active_groups'] != NULL ) { $active_groups = $proc_result[0]['active_groups']; }
	if ( @$proc_result[0]['backfill_groups'] != NULL ) { $backfill_groups = $proc_result[0]['backfill_groups']; }
	if ( @$proc_result[0]['parts'] != NULL ) { $parts_rows = $proc_result[0]['parts']; }
	if ( @$proc_result[0]['partsize'] != NULL ) { $parts_size_gb = $proc_result[0]['partsize']; }
	if ( @$proc_result[0]['collections_table'] != NULL ) { $collections_table = $proc_result[0]['collections_table']; }
	if ( @$proc_result[0]['binaries_table'] != NULL ) { $binaries_table = $proc_result[0]['binaries_table']; }
	if ( @$proc_result[0]['parts_table'] != NULL ) { $parts_table = $proc_result[0]['parts_table']; }

	if ( @$proc_result[0]['collections_kill'] != NULL ) { $collections_kill = $proc_result[0]['collections_kill']; }
	if ( @$proc_result[0]['postprocess_kill'] != NULL ) { $postprocess_kill = $proc_result[0]['postprocess_kill']; }

	if ( @$proc_result[0]['defrag'] != NULL ) { $defrag = $proc_result[0]['defrag']; }
	if ( @$proc_result[0]['tmux_session'] != NULL ) { $tmux_session = $proc_result[0]['tmux_session']; }
	if ( @$proc_result[0]['monitor'] != NULL ) { $monitor = $proc_result[0]['monitor']; }
	if ( @$proc_result[0]['backfill'] != NULL ) { $backfill = $proc_result[0]['backfill']; }
	if ( @$proc_result[0]['niceness'] != NULL ) { $niceness = $proc_result[0]['niceness']; }

	if ( @$proc_result[0]['running'] != NULL ) { $running = $proc_result[0]['running']; }
	if ( @$proc_result[0]['binaries_run'] != NULL ) { $binaries = $proc_result[0]['binaries_run']; }
	if ( @$proc_result[0]['import'] != NULL ) { $import = $proc_result[0]['import']; }
	if ( @$proc_result[0]['nzbs'] != NULL ) { $nzbs = $proc_result[0]['nzbs']; }
	if ( @$proc_result[0]['fix_names'] != NULL ) { $fix_names = $proc_result[0]['fix_names']; }
	if ( @$proc_result[0]['fix_crap'] != NULL ) { $fix_crap = $proc_result[0]['fix_crap']; }
	if ( @$proc_result[0]['sorter'] != NULL ) { $sorter = $proc_result[0]['sorter']; }
	if ( @$proc_result[0]['update_tv'] != NULL ) { $update_tv = $proc_result[0]['update_tv']; }
	if ( @$proc_result[0]['post'] != NULL ) { $post = $proc_result[0]['post']; }
	if ( @$proc_result[0]['releases_run'] != NULL ) { $releases_run = $proc_result[0]['releases_run']; }
	if ( @$proc_result[0]['releases_threaded'] != NULL ) { $releases_threaded = $proc_result[0]['releases_threaded']; }
	if ( @$proc_result[0]['process_list'] != NULL ) { $process_list = $proc_result[0]['process_list']; }
	if ( @$proc_result[0]['optimize_tables'] != NULL ) { $optimize_tables = $proc_result[0]['optimize_tables']; }
	if ( @$proc_result[0]['monitor_path'] != NULL ) { $monitor_path = $proc_result[0]['monitor_path']; }

	if ( @$proc_result[0]['debug'] != NULL ) { $debug = $proc_result[0]['debug']; }

	if ( @$proc_result[0]['seq_timer'] != NULL ) { $seq_timer = $proc_result[0]['seq_timer']; }
	if ( @$proc_result[0]['bins_timer'] != NULL ) { $bins_timer = $proc_result[0]['bins_timer']; }
	if ( @$proc_result[0]['back_timer'] != NULL ) { $back_timer = $proc_result[0]['back_timer']; }
	if ( @$proc_result[0]['import_timer'] != NULL ) { $import_timer = $proc_result[0]['import_timer']; }
	if ( @$proc_result[0]['rel_timer'] != NULL ) { $rel_timer = $proc_result[0]['rel_timer']; }
	if ( @$proc_result[0]['fix_timer'] != NULL ) { $fix_timer = $proc_result[0]['fix_timer']; }
	if ( @$proc_result[0]['crap_timer'] != NULL ) { $crap_timer = $proc_result[0]['crap_timer']; }
	if ( @$proc_result[0]['sorter_timer'] != NULL ) { $sorter_timer = $proc_result[0]['sorter_timer']; }
	if ( @$proc_result[0]['post_timer'] != NULL ) { $post_timer = $proc_result[0]['post_timer']; }
	if ( @$proc_result[0]['post_kill_timer'] != NULL ) { $post_kill_timer = $proc_result[0]['post_kill_timer']; }
	if ( @$proc_result[0]['tv_timer'] != NULL ) { $tv_timer = $proc_result[0]['tv_timer']; }
	if ( @$proc_result[0]['optimize_timer'] != NULL ) { $optimize_timer = $proc_result[0]['optimize_timer']; }

	if ( @$proc_result[0]['binaries'] != NULL ) { $binaries_rows = $proc_result[0]['binaries']; }
	if ( @$proc_result[0]['binaries'] != NULL ) { $binaries_total = $proc_result[0]['binaries_total']; }

	if ( @$proc_result[0]['binariessize'] != NULL ) { $binaries_size_gb = $proc_result[0]['binariessize']; }

	if ( @$proc_result[0]['releases'] ) { $releases_now = $proc_result[0]['releases']; }
	if ( @$proc_result[0]['newestaddname'] ) { $newestname = $proc_result[0]['newestaddname']; }
	if ( @$proc_result[0]['newestadd'] ) { $newestdate = $proc_result[0]['newestadd']; }

	//calculate releases difference
	$releases_misc_diff = number_format( $releases_now - $releases_start );
	$releases_since_start = number_format( $releases_now - $releases_start );
	$work_misc_diff = $work_remaining_now - $work_remaining_start;

	$total_work_now = $work_remaining_now + $tvrage_releases_proc + $music_releases_proc + $movie_releases_proc + $console_releases_proc + $book_releases_proc + $nfo_remaining_now;
	if ( $i == 1 ) { $total_work_start = $total_work_now; }

	$nfo_diff = number_format( $nfo_remaining_now - $nfo_remaining_start );
	$console_diff = number_format( $console_releases_proc - $console_releases_proc_start );
	$movie_diff = number_format( $movie_releases_proc - $movie_releases_proc_start );
	$music_diff = number_format( $music_releases_proc - $music_releases_proc_start );
	$pc_diff = number_format( $pc_releases_proc - $pc_releases_proc_start );
	$tvrage_diff = number_format( $tvrage_releases_proc - $tvrage_releases_proc_start );
	$book_diff = number_format( $book_releases_proc - $book_releases_proc_start );

	//formatted output
	$misc_diff = number_format( $work_remaining_now - $work_start );

	$work_since_start = ( $total_work_now - $total_work_start );
	$work_diff = number_format($work_since_start);

	if ( $releases_now != 0 ) {
		$nfo_percent = sprintf( "%02s", floor(( $nfo_now / $releases_now) * 100 ));
		$console_percent = sprintf( "%02s", floor(( $console_releases_now / $releases_now) * 100 ));
		$movie_percent = sprintf( "%02s", floor(( $movie_releases_now / $releases_now) * 100 ));
		$music_percent = sprintf( "%02s", floor(( $music_releases_now / $releases_now) * 100 ));
		$pc_percent = sprintf( "%02s", floor(( $pc_releases_now / $releases_now) * 100 ));
		$tvrage_percent = sprintf( "%02s", floor(( $tvrage_releases_now / $releases_now) * 100 ));
		$book_percent = sprintf( "%02s", floor(( $book_releases_now / $releases_now) * 100 ));
		$misc_percent = sprintf( "%02s", floor(( $misc_releases_now / $releases_now) * 100 ));
	} else {
		$nfo_percent = 0;
		$console_percent = 0;
		$movie_percent = 0;
		$music_percent = 0;
		$pc_percent = 0;
		$tvrage_percent = 0;
		$book_percent = 0;
		$misc_percent = 0;
	}

	//get microtime at end of queries
	if ( $runloop == "true" ) {
		$query_timer = microtime_float()-$query_timer_start;
	}

	//update display
	passthru('clear');
	//printf("\033[1;31m First insert:\033[0m ".relativeTime("$firstdate")."\n");
	printf($mask2, "Monitor Running v$version: ", relativeTime("$time"));
	printf($mask1, "Newest Release:", "$newestname");
	printf($mask1, "Release Added:", relativeTime("$newestdate")."ago");
	if ( $post == "TRUE" )
	{
		printf($mask1, "Postprocess:", "stale for ".relativeTime($time3));
	}

	$mask = "%-15.15s %22.22s %22.22s\n";
	printf("\033[1;33m\n");
	printf($mask, "Collections", "Binaries", "Parts");
	printf($mask, "====================", "====================", "====================");
	printf("\033[38;5;214m");
	printf($mask, number_format($collections_table), number_format($binaries_table), number_format($parts_table));
	if (( isset($monitor_path) ) && ( file_exists( $monitor_path ))) {
		printf("\033[1;33m\n");
		printf($mask, "Ramdisk", "Used", "Free");
		printf($mask, "====================", "====================", "====================");
		printf("\033[38;5;214m");
		$disk_use = decodeSize( disk_total_space($monitor_path) - disk_free_space($monitor_path) );
		$disk_free = decodeSize( disk_free_space($monitor_path) );
		printf($mask, basename($monitor_path), $disk_use, $disk_free);
	}

	printf("\033[1;33m\n");
	printf($mask, "Category", "In Process", "In Database");
	printf($mask, "====================", "====================", "====================");
	printf("\033[38;5;214m");
	printf($mask, "NFO's",number_format($nfo_remaining_now)."(".$nfo_diff.")",number_format($nfo_now)."(".$nfo_percent."%)");
	printf($mask, "Console(1000)",number_format($console_releases_proc)."(".$console_diff.")",number_format($console_releases_now)."(".$console_percent."%)");
	printf($mask, "Movie(2000)",number_format($movie_releases_proc)."(".$movie_diff.")",number_format($movie_releases_now)."(".$movie_percent."%)");
	printf($mask, "Audio(3000)",number_format($music_releases_proc)."(".$music_diff.")",number_format($music_releases_now)."(".$music_percent."%)");
	printf($mask, "PC(4000)",number_format($pc_releases_proc)."(".$pc_diff.")",number_format($pc_releases_now)."(".$pc_percent."%)");
	printf($mask, "TVShows(5000)",number_format($tvrage_releases_proc)."(".$tvrage_diff.")",number_format($tvrage_releases_now)."(".$tvrage_percent."%)");
	printf($mask, "Misc(7000)",number_format($work_remaining_now)."(".$misc_diff.")",number_format($misc_releases_now)."(".$misc_percent."%)");
	printf($mask, "Books(8000)",number_format($book_releases_proc)."(".$book_diff.")",number_format($book_releases_now)."(".$book_percent."%)");
	printf($mask, "Total", number_format($total_work_now)."(".$work_diff.")", number_format($releases_now)."(".$releases_since_start.")");

	printf("\n\033[1;33m\n");
	printf($mask, "Groups", "Active", "Backfill");
	printf($mask, "====================", "====================", "====================");
	printf("\033[38;5;214m");
	printf($mask, "Activated", $active_groups, $backfill_groups);

	//defrag the query cache every 15 minutes
	if ( TIME() - $time1 >= $defrag )
	{
		$result = @$db->query($qcache);
		printf($mask2, "Query cache cleaned", "", "");
		$time1 = TIME();
	}

	//get microtime at end of queries
	if ( $runloop == "true" )
	{
		$query_timer = microtime_float()-$query_timer_start;
	}

	//get list of panes by name
	$panes_win_1 = shell_exec("echo `tmux list-panes -t $tmux_session:0 -F '#{pane_title}'`");
	$panes_win_2 = shell_exec("echo `tmux list-panes -t $tmux_session:1 -F '#{pane_title}'`");
	$panes_win_3 = shell_exec("echo `tmux list-panes -t $tmux_session:2 -F '#{pane_title}'`");
	$panes0 = str_replace("\n", '', explode(" ", $panes_win_1));
	$panes1 = str_replace("\n", '', explode(" ", $panes_win_2));
    $panes2 = str_replace("\n", '', explode(" ", $panes_win_3));

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
	$_python = $show_time." nice -n$niceness python -OO";
	$_pythonn = "nice -n$niceness python -OO";

	//$run_releases = "$_python $DIR/misc/update_scripts/threaded_scripts/releases_threaded.py";
	if (( $i == 1 ) || ( $i % 3 == 0 ))
		$run_releases = "$_php $DIR/misc/update_scripts/update_releases.php 6 false && $_php $DIR/misc/update_scripts/update_releases.php 1 false ";
	else
		$run_releases = "$_php $DIR/misc/update_scripts/update_releases.php 1 false";

	if (( $postprocess_kill < $total_work_now ) && ( $postprocess_kill != 0 ))
		$kill_pp = "TRUE";
	else
		$kill_pp = "FALSE";
	if (( $collections_kill < $collections_table ) && ( $collections_kill != 0 ))
		$kill_coll = "TRUE";
	else
		$kill_coll = "FALSE";

	if ( $running == "TRUE" )
	{
		//fix names
		if (( $fix_names == "TRUE" ) && ( $i == 1 ) && ( TIME() - $time8 < 3600 ))
		{
			$color = get_color();
			$log = writelog($panes1[0]);
			shell_exec("tmux respawnp -t ${tmux_session}:1.0 'echo \"\033[38;5;${color}m\" && \
					$_phpn $DIR/misc/testing/Release_scripts/fixReleaseNames.php 4 true other yes $log && \
					$_phpn $DIR/misc/testing/Release_scripts/fixReleaseNames.php 6 true other no $log && date +\"%D %T\" && sleep $fix_timer' 2>&1 1> /dev/null");
		}
		elseif (( $fix_names == "TRUE" ) && ( TIME() - $time8 < 3600 ))
		{
			$color = get_color();
			$log = writelog($panes1[0]);
			shell_exec("tmux respawnp -t ${tmux_session}:1.0 'echo \"\033[38;5;${color}m\" && \
					$_phpn $DIR/misc/testing/Release_scripts/fixReleaseNames.php 3 true other yes $log && \
					$_phpn $DIR/misc/testing/Release_scripts/fixReleaseNames.php 5 true other no $log && date +\"%D %T\" && sleep $fix_timer' 2>&1 1> /dev/null");
		}
		elseif (( $fix_names == "TRUE" ) && ( TIME() - $time8 >= 3600 ))
 		{
 			$color = get_color();
			$log = writelog($panes1[0]);
			shell_exec("tmux respawnp -t ${tmux_session}:1.0 'echo \"\033[38;5;${color}m\" && \
					$_phpn $DIR/misc/testing/Release_scripts/fixReleaseNames.php 3 true all yes $log && \
					$_phpn $DIR/misc/testing/Release_scripts/fixReleaseNames.php 5 true all no $log && date +\"%D %T\" && sleep $fix_timer' 2>&1 1> /dev/null");
			$time8 = TIME();
			}
		else
		{
			$color = get_color();
			shell_exec("tmux respawnp -k -t ${tmux_session}:1.0 'echo \"\033[38;5;${color}m\n${panes1[0]} has been disabled/terminated by Fix Release Names\"'");
		}

		//remove crap releases
		if ( $sorter == "TRUE" )
		{
			$color = get_color();
			$log = writelog($panes1[2]);
			shell_exec("tmux respawnp -t ${tmux_session}:1.2 'echo \"\033[38;5;${color}m\" && \
					$_php $DIR/misc/testing/Dev_testing/test_misc_sorter.php $log && date +\"%D %T\" && sleep $sorter_timer' 2>&1 1> /dev/null");
		}
		else
		{
			$color = get_color();
			shell_exec("tmux respawnp -k -t ${tmux_session}:1.2 'echo \"\033[38;5;${color}m\n${panes1[2]} has been disabled/terminated by Misc Sorter\"'");
		}

		//remove crap releases
		if (( $fix_crap == "TRUE" ) && ( $i == 1 ))
		{
			$color = get_color();
			$log = writelog($panes1[1]);
			shell_exec("tmux respawnp -t ${tmux_session}:1.1 'echo \"\033[38;5;${color}m\" && \
					$_php $DIR/misc/testing/Release_scripts/removeCrapReleases.php true full $log && date +\"%D %T\" && sleep $crap_timer' 2>&1 1> /dev/null");
		}
		elseif ( $fix_crap == "TRUE" )
		{
			$color = get_color();
			$log = writelog($panes1[1]);
			shell_exec("tmux respawnp -t ${tmux_session}:1.1 'echo \"\033[38;5;${color}m\" && \
					$_php $DIR/misc/testing/Release_scripts/removeCrapReleases.php true 2 $log && date +\"%D %T\" && sleep $crap_timer' 2>&1 1> /dev/null");
		}
		else
		{
			$color = get_color();
			shell_exec("tmux respawnp -k -t ${tmux_session}:1.1 'echo \"\033[38;5;${color}m\n${panes1[1]} has been disabled/terminated by Remove Crap Releases\"'");
		}

		if ( $post == "TRUE" )
		{
			//run postprocess_releases non amzon
			$history = str_replace( " ", '', `tmux list-panes -t ${tmux_session}:2 | grep 0: | awk '{print $4;}'` );
			if ( $last_history != $history )
			{
				$last_history = $history;
				$time3 = TIME();
			}
			else
			{
				if ( TIME() - $time3 >= $post_kill_timer )
				{
					shell_exec("tmux respawnp -k -t ${tmux_session}:2.0 'echo \"\033[38;5;${color}m\n${panes2[0]} has been terminated by Possible Hung thread\"'");
					$wipe = `tmux clearhist -t ${tmux_session}:2.0`;
					$color = get_color();
					$time3 = TIME();
				}
			}
			$dead1 = str_replace( " ", '', `tmux list-panes -t ${tmux_session}:2 | grep dead | grep 0: | wc -l` );
			if ( $dead1 == 1 )
				$time3 = TIME();
			$log = writelog($panes2[0]);
			shell_exec("tmux respawnp -t ${tmux_session}:2.0 'echo \"\033[38;5;${color}m\" && \
					$_python $DIR/misc/update_scripts/threaded_scripts/postprocess_threaded.py non_amazon $log && date +\"%D %T\" && sleep $post_timer' 2>&1 1> /dev/null");
		}
		else
		{
			$color = get_color();
			shell_exec("tmux respawnp -k -t ${tmux_session}:2.0 'echo \"\033[38;5;${color}m\n${panes2[0]} has been disabled/terminated by Postprocess All\"'");
		}

        if ( $post == "TRUE" )
        {
            //run postprocess_releases amazon
            $history = str_replace( " ", '', `tmux list-panes -t ${tmux_session}:2 | grep 1: | awk '{print $4;}'` );
            if ( $last_history != $history )
            {
                $last_history = $history;
                $time9 = TIME();
            }
            else
            {
                if ( TIME() - $time9 >= $post_kill_timer )
                {
                    shell_exec("tmux respawnp -k -t ${tmux_session}:2.1 'echo \"\033[38;5;${color}m\n${panes2[1]} has been terminated by Possible Hung thread\"'");
                    $wipe = `tmux clearhist -t ${tmux_session}:2.1`;
                    $color = get_color();
                    $time9 = TIME();
                }
            }
            $dead1 = str_replace( " ", '', `tmux list-panes -t ${tmux_session}:2 | grep dead | grep 1: | wc -l` );
            if ( $dead1 == 1 )
                $time9 = TIME();
            $log = writelog($panes2[1]);
            shell_exec("tmux respawnp -t ${tmux_session}:2.1 'echo \"\033[38;5;${color}m\" && \
                    $_python $DIR/misc/update_scripts/threaded_scripts/postprocess_threaded.py amazon $log && date +\"%D %T\" && sleep $post_timer' 2>&1 1> /dev/null");
        }
        else
        {
            $color = get_color();
            shell_exec("tmux respawnp -k -t ${tmux_session}:2.1 'echo \"\033[38;5;${color}m\n${panes2[1]} has been disabled/terminated by Postprocess All\"'");
        }

		//update tv and theaters
		if (( $update_tv == "TRUE" ) && (( TIME() - $time4 >= $tv_timer ) || ( $i == 1 )))
		{
			$color = get_color();
			$log = writelog($panes1[3]);
			shell_exec("tmux respawnp -t ${tmux_session}:1.'echo \"\033[38;5;${color}m\" && \
					$_phpn $DIR/misc/update_scripts/update_theaters.php $log && $_phpn $DIR/misc/update_scripts/update_tvschedule.php $log && date +\"%D %T\"' 2>&1 1> /dev/null");
			$time4 = TIME();
		}
		elseif ( $update_tv == "TRUE" )
		{
			$run_time1 = relativeTime( $tv_timer + $time4 );
			$color = get_color();
			shell_exec("tmux respawnp -t ${tmux_session}:1.3 'echo \"\033[38;5;${color}m\n${panes1[3]} will run in T[ $run_time1]\"' 2>&1 1> /dev/null");
		}
		else
		{
			$color = get_color();
			shell_exec("tmux respawnp -k -t ${tmux_session}:1.3 'echo \"\033[38;5;${color}m\n${panes1[3]} has been disabled/terminated by Update TV/Theater\"'");
		}

		if ( $seq == "TRUE" )
		{
			//run import-nzb-bulk
			if (( $import == "TRUE" ) && ( $kill_pp == "FALSE" ))
			{
				$color = get_color();
				$log = writelog($panes0[1]);
				shell_exec("tmux respawnp -t ${tmux_session}:0.1 'echo \"\033[38;5;${color}m\" && \
						$_python $DIR/misc/update_scripts/threaded_scripts/import_threaded.py $log && date +\"%D %T\" && sleep $import_timer' 2>&1 1> /dev/null");
			}
			else
			{
				$color = get_color();
				shell_exec("tmux respawnp -k -t ${tmux_session}:0.1 'echo \"\033[38;5;${color}m\n${panes0[1]} has been disabled/terminated by Import\"'");
			}

			//run update_binaries
			$color = get_color();
			$log = writelog($panes0[2]);
			if (( $binaries == "TRUE" ) && ( $backfill == "TRUE" ) && ( $releases_run == "TRUE" ) && ( $kill_coll == "FALSE" ) && ( $kill_pp == "FALSE" ) && ( TIME() - $time7 <= 4800 ))
			{
				shell_exec("tmux respawnp -t ${tmux_session}:0.2 'echo \"\033[38;5;${color}m\" && \
						$_python $DIR/misc/update_scripts/threaded_scripts/binaries_threaded.py $log && \
						$_python $DIR/misc/update_scripts/threaded_scripts/backfill_threaded.py group $log && \
						$run_releases $log && date +\"%D %T\" && sleep $seq_timer' 2>&1 1> /dev/null");
			}
			elseif (( $binaries == "TRUE" ) && ( $backfill == "TRUE" ) && ( $releases_run == "TRUE" ) && ( $kill_coll == "FALSE" ) && ( $kill_pp == "FALSE" ) && ( TIME() - $time7 >= 4800 ))
			{
				shell_exec("tmux respawnp -k -t ${tmux_session}:0.2 'echo \"\033[38;5;${color}m\" && \
						$_python $DIR/misc/update_scripts/threaded_scripts/binaries_threaded.py $log && \
						$_python $DIR/misc/update_scripts/threaded_scripts/backfill_threaded.py all $log && \
						$run_releases $log && date +\"%D %T\" && sleep $seq_timer' 2>&1 1> /dev/null");
				$time7 = TIME();
			}
			elseif (( $binaries == "TRUE" ) && ( $releases_run == "TRUE" ) && ( $kill_coll == "FALSE" ) && ( $kill_pp == "FALSE" ))
			{
				shell_exec("tmux respawnp -t ${tmux_session}:0.2 'echo \"\033[38;5;${color}m\" && \
						$_python $DIR/misc/update_scripts/threaded_scripts/binaries_threaded.py $log && \
						$run_releases $log && date +\"%D %T\" && sleep $seq_timer' 2>&1 1> /dev/null");
			}
			elseif (( $backfill == "TRUE" ) && ( $releases_run == "TRUE" ) && ( $kill_coll == "FALSE" ) && ( $kill_pp == "FALSE" ) && ( TIME() - $time7 <= 4800 ))
			{
				shell_exec("tmux respawnp -t ${tmux_session}:0.2 'echo \"\033[38;5;${color}m\" && \
						$_python $DIR/misc/update_scripts/threaded_scripts/backfill_threaded.py group $log && \
						$run_releases $log && date +\"%D %T\" && sleep $seq_timer' 2>&1 1> /dev/null");
			}
			elseif (( $backfill == "TRUE" ) && ( $releases_run == "TRUE" ) && ( $kill_coll == "FALSE" ) && ( $kill_pp == "FALSE" ) && ( TIME() - $time7 <= 4800 ))
			{
				shell_exec("tmux respawnp -k -t ${tmux_session}:0.2 'echo \"\033[38;5;${color}m\" && \
						$_python $DIR/misc/update_scripts/threaded_scripts/backfill_threaded.py all $log && \
						$run_releases $log && date +\"%D %T\" && sleep $seq_timer' 2>&1 1> /dev/null");
				$time7 = TIME();
			}
			elseif ( $releases_run == "TRUE" )
			{
				shell_exec("tmux respawnp -t ${tmux_session}:0.2 'echo \"\033[38;5;${color}m\" && \
						$run_releases && date +\"%D %T\" && sleep $seq_timer' 2>&1 1> /dev/null");
			}
			elseif (( $kill_coll == "TRUE" ) || ( $kill_pp == "TRUE" ))
			{
				$color = get_color();
				shell_exec("tmux respawnp -k -t ${tmux_session}:0.2 'echo \"\033[38;5;${color}m\n${panes0[2]} has been disabled/terminated by Exceeding Limits\"'");
			}
			else
			{
				$color = get_color();
				shell_exec("tmux respawnp -k -t ${tmux_session}:0.2 'echo \"\033[38;5;${color}m\n${panes0[2]} has been disabled/terminated by Binaries\"'");
			}
		}
		else
		{
			//run update_binaries
			$color = get_color();
			if (( $binaries == "TRUE" ) && ( $kill_coll == "FALSE" ) && ( $kill_pp == "FALSE" ))
			{
				$log = writelog($panes0[2]);
				shell_exec("tmux respawnp -t ${tmux_session}:0.2 'echo \"\033[38;5;${color}m\" && \
						$_python $DIR/misc/update_scripts/threaded_scripts/binaries_threaded.py $log && date +\"%D %T\" && sleep $bins_timer' 2>&1 1> /dev/null");
			}
			elseif (( $kill_coll == "TRUE" ) || ( $kill_pp == "TRUE" ))
			{
				$color = get_color();
				shell_exec("tmux respawnp -k -t ${tmux_session}:0.2 'echo \"\033[38;5;${color}m\n${panes0[2]} has been disabled/terminated by Exceeding Limits\"'");
			}
			else
			{
				$color = get_color();
				shell_exec("tmux respawnp -k -t ${tmux_session}:0.2 'echo \"\033[38;5;${color}m\n${panes0[2]} has been disabled/terminated by Binaries\"'");
			}

			//run backfill
			if (( $backfill == "TRUE" ) && ( $kill_coll == "FALSE" ) && ( $kill_pp == "FALSE" ) && ( TIME() - $time7 <= 4800 ))
			{
				$color = get_color();
				$log = writelog($panes0[3]);
				shell_exec("tmux respawnp -t ${tmux_session}:0.3 'echo \"\033[38;5;${color}m\" && \
						$_python $DIR/misc/update_scripts/threaded_scripts/backfill_threaded.py group $log && date +\"%D %T\" && sleep $back_timer' 2>&1 1> /dev/null");
			}
			elseif (( $backfill == "TRUE" ) && ( $kill_coll == "FALSE" ) && ( $kill_pp == "FALSE" ) && ( TIME() - $time7 >= 4800 ))
			{
				$color = get_color();
				$log = writelog($panes0[3]);
				shell_exec("tmux respawnp -k -t ${tmux_session}:0.3 'echo \"\033[38;5;${color}m\" && \
						$_python $DIR/misc/update_scripts/threaded_scripts/backfill_threaded.py all $log && date +\"%D %T\" && sleep $back_timer' 2>&1 1> /dev/null");
				$time7 = TIME();
			}
			elseif (( $kill_coll == "TRUE" ) || ( $kill_pp == "TRUE" ))
			{
				$color = get_color();
				shell_exec("tmux respawnp -k -t ${tmux_session}:0.3 'echo \"\033[38;5;${color}m\n${panes0[3]} has been disabled/terminated by Exceeding Limits\"'");
			}
			else
			{
				$color = get_color();
				shell_exec("tmux respawnp -k -t ${tmux_session}:0.3 'echo \"\033[38;5;${color}m\n${panes0[3]} has been disabled/terminated by Backfill\"'");
			}

			//run import-nzb-bulk
			if (( $import == "TRUE" ) && ( $kill_pp == "FALSE" ))
			{
				$color = get_color();
				$log = writelog($panes0[1]);
				shell_exec("tmux respawnp -t ${tmux_session}:0.1 'echo \"\033[38;5;${color}m\" && \
						$_python $DIR/misc/update_scripts/threaded_scripts/import_threaded.py $log && date +\"%D %T\" && sleep $import_timer' 2>&1 1> /dev/null");
			}
			elseif ( $kill_pp == "TRUE" )
			{
				$color = get_color();
				shell_exec("tmux respawnp -k -t ${tmux_session}:0.1 'echo \"\033[38;5;${color}m\n${panes0[1]} has been disabled/terminated by Exceeding Limits\"'");
			}
			else
			{
				$color = get_color();
				shell_exec("tmux respawnp -k -t ${tmux_session}:0.1 'echo \"\033[38;5;${color}m\n${panes0[1]} has been disabled/terminated by Import\"'");
			}

			//run update_releases
			if ( $releases_run == "TRUE" )
			{
				$color = get_color();
				$log = writelog($panes0[4]);
				shell_exec("tmux respawnp -t ${tmux_session}:0.4 'echo \"\033[38;5;${color}m\" && \
						$run_releases $log && date +\"%D %T\" && sleep $rel_timer' 2>&1 1> /dev/null");
			}
			else
			{
				$color = get_color();
				shell_exec("tmux respawnp -k -t ${tmux_session}:0.4 'echo \"\033[38;5;${color}m\n${panes0[4]} has been disabled/terminated by Releases\"'");
			}
		}
	}
	elseif ( $seq != "TRUE" )
	{
		for ($g=1; $g<=4; $g++)
		{
			$color = get_color();
			shell_exec("tmux respawnp -k -t ${tmux_session}:0.$g 'echo \"\033[38;5;${color}m\n${panes0[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g=0; $g<=3; $g++)
		{
			$color = get_color();
			shell_exec("tmux respawnp -k -t ${tmux_session}:1.$g 'echo \"\033[38;5;${color}m\n${panes1[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g=0; $g<=1; $g++)
		{
			$color = get_color();
			shell_exec("tmux respawnp -k -t ${tmux_session}:2.$g 'echo \"\033[38;5;${color}m\n${panes2[$g]} has been disabled/terminated by Running\"'");
		}
	}
	else
	{
		for ($g=1; $g<=2; $g++)
		{
			$color = get_color();
			shell_exec("tmux respawnp -k -t ${tmux_session}:0.$g 'echo \"\033[38;5;${color}m\n${panes0[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g=0; $g<=3; $g++)
		{
			$color = get_color();
			shell_exec("tmux respawnp -k -t ${tmux_session}:1.$g 'echo \"\033[38;5;${color}m\n${panes1[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g=0; $g<=1; $g++)
		{
			$color = get_color();
			shell_exec("tmux respawnp -k -t ${tmux_session}:2.$g 'echo \"\033[38;5;${color}m\n${panes2[$g]} has been disabled/terminated by Running\"'");
		}
	}

	$i++;
	sleep(5);
}
?>
