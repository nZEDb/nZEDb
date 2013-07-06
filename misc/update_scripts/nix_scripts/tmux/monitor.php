<?php
require_once(dirname(__FILE__)."/../../../../www/config.php");
require_once(WWW_DIR."lib/postprocess.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/tmux.php");
require_once(WWW_DIR."lib/site.php");

$version="0.1r2691";

$db = new DB();
$DIR = MISC_DIR;
$db_name = DB_NAME;

$tmux = new Tmux();
$seq = $tmux->get()->SEQUENTIAL;
$powerline = $tmux->get()->POWERLINE;
$colors_start = $tmux->get()->COLORS_START;
$colors_end = $tmux->get()->COLORS_END;
$colors_exc = $tmux->get()->COLORS_EXC;

//totals per category in db, results by parentID
$qry = "SELECT COUNT( releases.categoryID ) AS cnt, parentID FROM releases INNER JOIN category ON releases.categoryID = category.ID WHERE nzbstatus = 1 and parentID IS NOT NULL GROUP BY parentID";

//needs to be processed query
$proc_work = "SELECT
	( SELECT COUNT( groupID ) from releases where rageID = -1 and categoryID BETWEEN 5000 AND 5999 and nzbstatus = 1 ) AS tv,
	( SELECT COUNT( groupID ) from releases where imdbID IS NULL and categoryID BETWEEN 2000 AND 2999 and nzbstatus = 1 ) AS movies,
	( SELECT COUNT( groupID ) from releases where musicinfoID IS NULL and relnamestatus != 0 and categoryID in (3010, 3040, 3050) and nzbstatus = 1 ) AS audio,
	( SELECT COUNT( groupID ) from releases where consoleinfoID IS NULL and categoryID BETWEEN 1000 AND 1999 and nzbstatus = 1 ) AS console,
	( SELECT COUNT( groupID ) from releases where bookinfoID IS NULL and nzbstatus = 1 and categoryID = 8010 ) AS book,
	( SELECT COUNT( groupID ) from releases where nzbstatus = 1 ) AS releases,
	( SELECT COUNT( groupID ) FROM releases WHERE nfostatus = 1 ) AS nfo,
	( SELECT COUNT( groupID ) FROM releases r WHERE r.nfostatus between -6 and -1 and nzbstatus = 1 ) AS nforemains,
	( SELECT COUNT( groupID ) from releases r left join category c on c.ID = r.categoryID where categoryID BETWEEN 4000 AND 4999 and nzbstatus = 1 and ((r.passwordstatus between -6 and -1) and (r.haspreview = -1 and c.disablepreview = 0))) AS pc,
	( SELECT COUNT( groupID ) from releases r left join category c on c.ID = r.categoryID where nzbstatus = 1 and (r.passwordstatus between -6 and -1) and (r.haspreview = -1 and c.disablepreview = 0)) AS work,
	( SELECT COUNT( ID ) FROM groups WHERE active = 1 ) AS active_groups,
	( SELECT COUNT( ID ) FROM groups WHERE first_record IS NOT NULL and backfill = 1 and first_record_postdate != '2000-00-00 00:00:00' and (now() - interval backfill_target day) < first_record_postdate ) AS backfill_groups_days,
	( SELECT COUNT( ID ) FROM groups WHERE first_record IS NOT NULL and backfill = 1 and first_record_postdate != '2000-00-00 00:00:00' and (now() - interval datediff(curdate(),(select value from site where setting = 'safebackfilldate')) day) < first_record_postdate) AS backfill_groups_date,
	( SELECT COUNT( ID ) FROM groups ) AS all_groups,
	( SELECT COUNT( ID ) from predb where releaseID is not NULL ) AS predb_matched,
	( SELECT COUNT( ID ) from collections ) AS collections_table,
	( SELECT TABLE_ROWS from INFORMATION_SCHEMA.TABLES where table_name = 'binaries' AND TABLE_SCHEMA = '$db_name' ) AS binaries_table,
	( SELECT TABLE_ROWS from INFORMATION_SCHEMA.TABLES where table_name = 'parts' AND TABLE_SCHEMA = '$db_name' ) AS parts_table,
	( SELECT TABLE_ROWS from INFORMATION_SCHEMA.TABLES where table_name = 'predb' AND TABLE_SCHEMA = '$db_name' ) AS predb,
    ( SELECT COUNT( distinct( collectionhash )) FROM nzbs ) AS distinctnzbs,
	( SELECT COUNT( collectionhash ) FROM nzbs ) AS totalnzbs,
	( SELECT COUNT( collectionhash ) FROM ( SELECT collectionhash FROM nzbs GROUP BY collectionhash, totalparts HAVING COUNT(*) >= totalparts ) AS count) AS pendingnzbs";

$proc_tmux = "SELECT
	( SELECT UNIX_TIMESTAMP(dateadded) from collections order by dateadded ASC limit 1 ) AS oldestcollection,
	( SELECT UNIX_TIMESTAMP(adddate) from predb order by adddate DESC limit 1 ) AS newestpre,
	( SELECT name from releases where nzbstatus = 1 order by adddate DESC limit 1 ) AS newestaddname,
	( SELECT UNIX_TIMESTAMP(adddate) from releases where nzbstatus = 1 order by adddate DESC limit 1 ) AS newestadd,
	( SELECT UNIX_TIMESTAMP(dateadded) from nzbs order by dateadded ASC limit 1 ) AS oldestnzb,
	( SELECT value from tmux where setting = 'MONITOR_DELAY' ) AS monitor,
	( SELECT value from tmux where setting = 'TMUX_SESSION' ) AS tmux_session,
	( SELECT value from tmux where setting = 'NICENESS' ) AS niceness,
	( SELECT value from tmux where setting = 'BINARIES' ) AS binaries_run,
	( SELECT value from tmux where setting = 'BACKFILL' ) AS backfill,
	( SELECT value from tmux where setting = 'IMPORT' ) AS import,
	( SELECT value from tmux where setting = 'NZBS' ) AS nzbs,
	( SELECT value from tmux where setting = 'POST' ) AS post,
	( SELECT value from tmux where setting = 'RELEASES' ) AS releases_run,
	( SELECT value from tmux where setting = 'RELEASES_THREADED' ) AS releases_threaded,
	( SELECT value from tmux where setting = 'FIX_NAMES' ) AS fix_names,
	( SELECT value from tmux where setting = 'SEQ_TIMER' ) AS seq_timer,
	( SELECT value from tmux where setting = 'BINS_TIMER' ) AS bins_timer,
	( SELECT value from tmux where setting = 'BACK_TIMER' ) AS back_timer,
	( SELECT value from tmux where setting = 'IMPORT_TIMER' ) AS import_timer,
	( SELECT value from tmux where setting = 'REL_TIMER' ) AS rel_timer,
	( SELECT value from tmux where setting = 'FIX_TIMER' ) AS fix_timer,
	( SELECT value from tmux where setting = 'POST_TIMER' ) AS post_timer,
	( SELECT value from tmux where setting = 'COLLECTIONS_KILL' ) AS collections_kill,
	( SELECT value from tmux where setting = 'POSTPROCESS_KILL' ) AS postprocess_kill,
	( SELECT value from tmux where setting = 'CRAP_TIMER' ) AS crap_timer,
	( SELECT value from tmux where setting = 'FIX_CRAP' ) AS fix_crap,
	( SELECT value from tmux where setting = 'TV_TIMER' ) AS tv_timer,
	( SELECT value from tmux where setting = 'UPDATE_TV' ) AS update_tv,
	( SELECT value from tmux where setting = 'POST_KILL_TIMER' ) AS post_kill_timer,
	( SELECT value from tmux where setting = 'MONITOR_PATH' ) AS monitor_path,
	( SELECT value from tmux where setting = 'SORTER' ) AS sorter,
	( SELECT value from tmux where setting = 'SORTER_TIMER' ) AS sorter_timer,
	( SELECT value from tmux where setting = 'PROGRESSIVE' ) AS progressive,
	( SELECT value from tmux where setting = 'DEHASH' ) AS dehash,
	( SELECT value from tmux where setting = 'DEHASH_TIMER' ) AS dehash_timer,
	( SELECT value from tmux where setting = 'BACKFILL_DAYS' ) AS backfilldays,
	( SELECT value from site where setting = 'debuginfo' ) AS debug,
	( SELECT value from site where setting = 'lookupbooks' ) AS processbooks,
	( SELECT value from site where setting = 'lookupmusic' ) AS processmusic,
	( SELECT value from site where setting = 'lookupgames' ) AS processgames,
	( SELECT value from site where setting = 'tmpunrarpath' ) AS tmpunrar,
	( SELECT value from tmux where setting = 'POST_AMAZON' ) AS post_amazon,
	( SELECT value from tmux where setting = 'POST_TIMER_AMAZON' ) AS post_timer_amazon,
	( SELECT value from tmux where setting = 'POST_NON' ) AS post_non,
	( SELECT value from tmux where setting = 'POST_TIMER_NON' ) AS post_timer_non";

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
	$exceptions = array( $colors_exc );
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

//initial values
$newestname = "Unknown";
$newestadd = TIME();
$newestpre = TIME();
$oldestcollection = TIME();
$oldestnzb = TIME();

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
$pre_diff = 0;

$misc_percent = 0;
$book_percent = 0;
$tvrage_percent = 0;
$pc_percent = 0;
$music_percent = 0;
$movie_percent = 0;
$console_percent = 0;
$nfo_percent = 0;
$pre_percent = 0;

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
$predb_matched = 0;
$predb = 0;

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
$backfill_groups_days = 0;
$backfill_groups_date = 0;
$backfilldays = 0;
$all_groups = 0;
$totalnzbs = 0;
$distinctnzbs = 0;
$pendingnzbs = 0;

$mask1 = "\033[1;33m%-16s \033[38;5;214m%-44.44s \n";
$mask2 = "\033[1;33m%-16s \033[38;5;214m%-34.34s \n";

//create display
passthru('clear');
//printf("\033[1;31m First insert:\033[0m ".relativeTime("$firstdate")."\n");
printf($mask2, "Monitor Running v$version: ", relativeTime("$time"));
printf($mask1, "Newest Release:", "$newestname");
printf($mask1, "Release Added:", relativeTime("$newestadd")."ago");
printf($mask1, "Predb Updated:", relativeTime("$newestpre")."ago");
printf($mask1, "Collection Age:", relativeTime("$oldestcollection")."ago");
printf($mask1, "NZBs Age:", relativeTime("$oldestnzb")."ago");

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
printf($mask, "NZBs",number_format($totalnzbs)."(".number_format($distinctnzbs).")", number_format($pendingnzbs));
printf($mask, "predb",number_format($predb_matched)."(".$pre_diff.")",number_format($predb)."(".$pre_percent."%)");
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
if ( $backfilldays == "1" )
	printf($mask, "Activated", $active_groups."(".$all_groups.")", $backfill_groups_days."(".$all_groups.")");
else
	printf($mask, "Activated", $active_groups."(".$all_groups.")", $backfill_groups_date."(".$all_groups.")");

$monitor = 30;
$i = 1;
while( $i > 0 )
{

	//get microtime at start of loop
	$time_loop_start = microtime_float();

	$getdate = gmDate("Ymd");
	$proc_tmux_result = @$db->query($proc_tmux);

	//run queries only after time exceeded, this query take take awhile
	$running = $tmux->get()->RUNNING;
	if (((( TIME() - $time1 ) >= $monitor ) && ( $running == "TRUE" )) || ( $i == 1 )) {
		//get microtime to at start of queries
		$query_timer_start=microtime_float();
		$result = @$db->query($qry);
		$initquery = array();
		foreach ($result as $cat=>$sub)
		{
			$initquery[$sub['parentID']] = $sub['cnt'];
		}
		$proc_work_result = @$db->query($proc_work);
		$time1 = TIME();
		$runloop = "true";
	} else {
		$runloop = "false";
	}

	//get start values from $qry
	if ( $i == 1 )
	{
		if ( @$proc_work_result[0]['nforemains'] != NULL ) { $nfo_remaining_start = $proc_work_result[0]['nforemains']; }
		if ( @$proc_work_result[0]['predb_matched'] != NULL ) { $predb_matched_start = $proc_work_result[0]['predb_matched']; }
		if ( @$proc_work_result[0]['console'] != NULL ) { $console_releases_proc_start = $proc_work_result[0]['console']; }
		if ( @$proc_work_result[0]['movies'] != NULL ) { $movie_releases_proc_start = $proc_work_result[0]['movies']; }
		if ( @$proc_work_result[0]['audio'] != NULL ) { $music_releases_proc_start = $proc_work_result[0]['audio']; }
		if ( @$proc_work_result[0]['pc'] != NULL ) { $pc_releases_proc_start = $proc_work_result[0]['pc']; }
		if ( @$proc_work_result[0]['tv'] != NULL ) { $tvrage_releases_proc_start = $proc_work_result[0]['tv']; }
		if ( @$proc_work_result[0]['book'] != NULL ) { $book_releases_proc_start = $proc_work_result[0]['book']; }
		if ( @$proc_work_result[0]['work'] != NULL ) { $work_remaining_start = $proc_work_result[0]['work']; }
		if ( @$proc_work_result[0]['work'] != NULL ) { $work_start = $proc_work_result[0]['work']; }
		if ( @$proc_work_result[0]['releases'] != NULL ) { $releases_start = $proc_work_result[0]['releases']; }
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
	if ( @$proc_work_result[0]['console'] != NULL ) { $console_releases_proc = $proc_work_result[0]['console']; }
	if ( @$proc_work_result[0]['console'] != NULL ) { $console_releases_proc = $proc_work_result[0]['console']; }
	if ( @$proc_work_result[0]['movies'] != NULL ) { $movie_releases_proc = $proc_work_result[0]['movies']; }
	if ( @$proc_work_result[0]['audio'] != NULL ) { $music_releases_proc = $proc_work_result[0]['audio']; }
	if ( @$proc_work_result[0]['pc'] != NULL ) { $pc_releases_proc = $proc_work_result[0]['pc']; }
	if ( @$proc_work_result[0]['tv'] != NULL ) { $tvrage_releases_proc = $proc_work_result[0]['tv']; }
	if ( @$proc_work_result[0]['book'] != NULL ) { $book_releases_proc = $proc_work_result[0]['book']; }
	if ( @$proc_work_result[0]['work'] != NULL ) { $work_remaining_now = $proc_work_result[0]['work']; }
	if ( @$proc_work_result[0]['releases'] != NULL ) { $releases_loop = $proc_work_result[0]['releases']; }
	if ( @$proc_work_result[0]['nforemains'] != NULL ) { $nfo_remaining_now = $proc_work_result[0]['nforemains']; }
	if ( @$proc_work_result[0]['nfo'] != NULL ) { $nfo_now = $proc_work_result[0]['nfo']; }
	if ( @$proc_work_result[0]['active_groups'] != NULL ) { $active_groups = $proc_work_result[0]['active_groups']; }
	if ( @$proc_work_result[0]['backfill_groups_days'] != NULL ) { $backfill_groups_days = $proc_work_result[0]['backfill_groups_days']; }
	if ( @$proc_work_result[0]['backfill_groups_date'] != NULL ) { $backfill_groups_date = $proc_work_result[0]['backfill_groups_date']; }
	if ( @$proc_work_result[0]['all_groups'] != NULL ) { $all_groups = $proc_work_result[0]['all_groups']; }
	if ( @$proc_work_result[0]['parts'] != NULL ) { $parts_rows = $proc_work_result[0]['parts']; }
	if ( @$proc_work_result[0]['partsize'] != NULL ) { $parts_size_gb = $proc_work_result[0]['partsize']; }
	if ( @$proc_work_result[0]['collections_table'] != NULL ) { $collections_table = $proc_work_result[0]['collections_table']; }
	if ( @$proc_work_result[0]['binaries_table'] != NULL ) { $binaries_table = $proc_work_result[0]['binaries_table']; }
	if ( @$proc_work_result[0]['parts_table'] != NULL ) { $parts_table = $proc_work_result[0]['parts_table']; }

	if ( @$proc_work_result[0]['predb'] != NULL ) { $predb = $proc_work_result[0]['predb']; }
	if ( @$proc_work_result[0]['predb_matched'] != NULL ) { $predb_matched = $proc_work_result[0]['predb_matched']; }
	if ( @$proc_work_result[0]['distinctnzbs'] != NULL ) { $distinctnzbs = $proc_work_result[0]['distinctnzbs']; }
	if ( @$proc_work_result[0]['totalnzbs'] != NULL ) { $totalnzbs = $proc_work_result[0]['totalnzbs']; }
	if ( @$proc_work_result[0]['pendingnzbs'] != NULL ) { $pendingnzbs = $proc_work_result[0]['pendingnzbs']; }

	if ( @$proc_tmux_result[0]['collections_kill'] != NULL ) { $collections_kill = $proc_tmux_result[0]['collections_kill']; }
	if ( @$proc_tmux_result[0]['postprocess_kill'] != NULL ) { $postprocess_kill = $proc_tmux_result[0]['postprocess_kill']; }
	if ( @$proc_tmux_result[0]['backfilldays'] != NULL ) { $backfilldays = $proc_tmux_result[0]['backfilldays']; }
	if ( @$proc_tmux_result[0]['tmpunrar'] != NULL ) { $tmpunrar = $proc_tmux_result[0]['tmpunrar']; }

	if ( @$proc_tmux_result[0]['defrag'] != NULL ) { $defrag = $proc_tmux_result[0]['defrag']; }
	if ( @$proc_tmux_result[0]['processbooks'] != NULL ) { $processbooks = $proc_tmux_result[0]['processbooks']; }
	if ( @$proc_tmux_result[0]['processmusic'] != NULL ) { $processmusic = $proc_tmux_result[0]['processmusic']; }
	if ( @$proc_tmux_result[0]['processgames'] != NULL ) { $processgames = $proc_tmux_result[0]['processgames']; }
	if ( @$proc_tmux_result[0]['tmux_session'] != NULL ) { $tmux_session = $proc_tmux_result[0]['tmux_session']; }
	if ( @$proc_tmux_result[0]['monitor'] != NULL ) { $monitor = $proc_tmux_result[0]['monitor']; }
	if ( @$proc_tmux_result[0]['backfill'] != NULL ) { $backfill = $proc_tmux_result[0]['backfill']; }
	if ( @$proc_tmux_result[0]['niceness'] != NULL ) { $niceness = $proc_tmux_result[0]['niceness']; }
	if ( @$proc_tmux_result[0]['progressive'] != NULL ) { $progressive = $proc_tmux_result[0]['progressive']; }
	if ( @$proc_tmux_result[0]['oldestcollection'] != NULL ) { $oldestcollection = $proc_tmux_result[0]['oldestcollection']; }
	if ( @$proc_tmux_result[0]['oldestnzb'] != NULL ) { $oldestnzb = $proc_tmux_result[0]['oldestnzb']; }

	if ( @$proc_tmux_result[0]['binaries_run'] != NULL ) { $binaries = $proc_tmux_result[0]['binaries_run']; }
	if ( @$proc_tmux_result[0]['import'] != NULL ) { $import = $proc_tmux_result[0]['import']; }
	if ( @$proc_tmux_result[0]['nzbs'] != NULL ) { $nzbs = $proc_tmux_result[0]['nzbs']; }
	if ( @$proc_tmux_result[0]['fix_names'] != NULL ) { $fix_names = $proc_tmux_result[0]['fix_names']; }
	if ( @$proc_tmux_result[0]['fix_crap'] != NULL ) { $fix_crap = $proc_tmux_result[0]['fix_crap']; }
	if ( @$proc_tmux_result[0]['sorter'] != NULL ) { $sorter = $proc_tmux_result[0]['sorter']; }
	if ( @$proc_tmux_result[0]['update_tv'] != NULL ) { $update_tv = $proc_tmux_result[0]['update_tv']; }
	if ( @$proc_tmux_result[0]['post'] != NULL ) { $post = $proc_tmux_result[0]['post']; }
	if ( @$proc_tmux_result[0]['releases_run'] != NULL ) { $releases_run = $proc_tmux_result[0]['releases_run']; }
	if ( @$proc_tmux_result[0]['releases_threaded'] != NULL ) { $releases_threaded = $proc_tmux_result[0]['releases_threaded']; }
	if ( @$proc_tmux_result[0]['monitor_path'] != NULL ) { $monitor_path = $proc_tmux_result[0]['monitor_path']; }
	if ( @$proc_tmux_result[0]['dehash'] != NULL ) { $dehash = $proc_tmux_result[0]['dehash']; }

	if ( @$proc_tmux_result[0]['debug'] != NULL ) { $debug = $proc_tmux_result[0]['debug']; }
	if ( @$proc_tmux_result[0]['post_amazon'] != NULL ) { $post_amazon = $proc_tmux_result[0]['post_amazon']; }
	if ( @$proc_tmux_result[0]['post_timer_amazon'] != NULL ) { $post_timer_amazon = $proc_tmux_result[0]['post_timer_amazon']; }
	if ( @$proc_tmux_result[0]['post_non'] != NULL ) { $post_non = $proc_tmux_result[0]['post_non']; }
	if ( @$proc_tmux_result[0]['post_timer_non'] != NULL ) { $post_timer_non = $proc_tmux_result[0]['post_timer_non']; }

	if ( @$proc_tmux_result[0]['seq_timer'] != NULL ) { $seq_timer = $proc_tmux_result[0]['seq_timer']; }
	if ( @$proc_tmux_result[0]['bins_timer'] != NULL ) { $bins_timer = $proc_tmux_result[0]['bins_timer']; }
	if ( @$proc_tmux_result[0]['back_timer'] != NULL ) { $back_timer = $proc_tmux_result[0]['back_timer']; }
	if ( @$proc_tmux_result[0]['import_timer'] != NULL ) { $import_timer = $proc_tmux_result[0]['import_timer']; }
	if ( @$proc_tmux_result[0]['rel_timer'] != NULL ) { $rel_timer = $proc_tmux_result[0]['rel_timer']; }
	if ( @$proc_tmux_result[0]['fix_timer'] != NULL ) { $fix_timer = $proc_tmux_result[0]['fix_timer']; }
	if ( @$proc_tmux_result[0]['crap_timer'] != NULL ) { $crap_timer = $proc_tmux_result[0]['crap_timer']; }
	if ( @$proc_tmux_result[0]['sorter_timer'] != NULL ) { $sorter_timer = $proc_tmux_result[0]['sorter_timer']; }
	if ( @$proc_tmux_result[0]['post_timer'] != NULL ) { $post_timer = $proc_tmux_result[0]['post_timer']; }
	if ( @$proc_tmux_result[0]['post_kill_timer'] != NULL ) { $post_kill_timer = $proc_tmux_result[0]['post_kill_timer']; }
	if ( @$proc_tmux_result[0]['tv_timer'] != NULL ) { $tv_timer = $proc_tmux_result[0]['tv_timer']; }
	if ( @$proc_tmux_result[0]['dehash_timer'] != NULL ) { $dehash_timer = $proc_tmux_result[0]['dehash_timer']; }

	if ( @$proc_work_result[0]['binaries'] != NULL ) { $binaries_rows = $proc_work_result[0]['binaries']; }
	if ( @$proc_work_result[0]['binaries'] != NULL ) { $binaries_total = $proc_work_result[0]['binaries_total']; }

	if ( @$proc_work_result[0]['binariessize'] != NULL ) { $binaries_size_gb = $proc_work_result[0]['binariessize']; }

	if ( @$proc_work_result[0]['releases'] ) { $releases_now = $proc_work_result[0]['releases']; }
	if ( @$proc_tmux_result[0]['newestaddname'] ) { $newestname = $proc_tmux_result[0]['newestaddname']; }
	if ( @$proc_tmux_result[0]['newestpre'] ) { $newestpre = $proc_tmux_result[0]['newestpre']; }
	if ( @$proc_tmux_result[0]['newestadd'] ) { $newestadd = $proc_tmux_result[0]['newestadd']; }

	//calculate releases difference
	$releases_misc_diff = number_format( $releases_now - $releases_start );
	$releases_since_start = number_format( $releases_now - $releases_start );
	$work_misc_diff = $work_remaining_now - $work_remaining_start;

	$total_work_now = $work_remaining_now + $tvrage_releases_proc + $music_releases_proc + $movie_releases_proc + $console_releases_proc + $book_releases_proc + $nfo_remaining_now;
	if ( $i == 1 ) { $total_work_start = $total_work_now; }

	$nfo_diff = number_format( $nfo_remaining_now - $nfo_remaining_start );
	$pre_diff = number_format( $predb_matched - $predb_matched_start );
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
		$pre_percent = sprintf( "%02s", floor(( $predb_matched / $releases_now) * 100 ));
		$console_percent = sprintf( "%02s", floor(( $console_releases_now / $releases_now) * 100 ));
		$movie_percent = sprintf( "%02s", floor(( $movie_releases_now / $releases_now) * 100 ));
		$music_percent = sprintf( "%02s", floor(( $music_releases_now / $releases_now) * 100 ));
		$pc_percent = sprintf( "%02s", floor(( $pc_releases_now / $releases_now) * 100 ));
		$tvrage_percent = sprintf( "%02s", floor(( $tvrage_releases_now / $releases_now) * 100 ));
		$book_percent = sprintf( "%02s", floor(( $book_releases_now / $releases_now) * 100 ));
		$misc_percent = sprintf( "%02s", floor(( $misc_releases_now / $releases_now) * 100 ));
	} else {
		$nfo_percent = 0;
		$pre_percent = 0;
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
	printf($mask1, "Release Added:", relativeTime("$newestadd")."ago");
	printf($mask1, "Predb Updated:", relativeTime("$newestpre")."ago");
	printf($mask1, "Collection Age:", relativeTime("$oldestcollection")."ago");
	printf($mask1, "NZBs Age:", relativeTime("$oldestnzb")."ago");
	if ( $post == "1" || $post == "3" )
	{
		printf($mask1, "Postprocess:", "stale for ".relativeTime($time2));
	}

	$mask = "%-15.15s %22.22s %22.22s\n";
	printf("\033[1;33m\n");
	printf($mask, "Collections", "Binaries", "Parts");
	printf($mask, "====================", "====================", "====================");
	printf("\033[38;5;214m");
	printf($mask, number_format($collections_table), "~".number_format($binaries_table), "~".number_format($parts_table));

	if (( isset($monitor_path) ) && ( file_exists( $monitor_path ))) {
		printf("\033[1;33m\n");
		printf($mask, "Ramdisk", "Used", "Free");
		printf($mask, "====================", "====================", "====================");
		printf("\033[38;5;214m");
		$disk_use = decodeSize( disk_total_space($monitor_path) - disk_free_space($monitor_path) );
		$disk_free = decodeSize( disk_free_space($monitor_path) );
		if ( basename($monitor_path) == "" )
			$show = "/";
		else
			$show = basename($monitor_path);
		printf($mask, $show, $disk_use, $disk_free);
	}

	printf("\033[1;33m\n");
	printf($mask, "Category", "In Process", "In Database");
	printf($mask, "====================", "====================", "====================");
	printf("\033[38;5;214m");
	printf($mask, "NZBs",number_format($totalnzbs)."(".number_format($distinctnzbs).")", number_format($pendingnzbs));
	printf($mask, "predb",number_format($predb_matched)."(".$pre_diff.")","~".number_format($predb)."(".$pre_percent."%)");
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
	if ( $backfilldays == "1" )
		printf($mask, "Activated", $active_groups."(".$all_groups.")", $backfill_groups_days."(".$all_groups.")");
	else
		printf($mask, "Activated", $active_groups."(".$all_groups.")", $backfill_groups_date."(".$all_groups.")");

	//get microtime at end of queries
	if ( $runloop == "true" )
	{
		$query_timer = microtime_float()-$query_timer_start;
	}

	//get list of panes by name
	$panes_win_1 = shell_exec("echo `tmux list-panes -t $tmux_session:0 -F '#{pane_title}'`");
	$panes_win_2 = shell_exec("echo `tmux list-panes -t $tmux_session:1 -F '#{pane_title}'`");
	$panes_win_3 = shell_exec("echo `tmux list-panes -t $tmux_session:2 -F '#{pane_title}'`");
	$panes_win_4 = shell_exec("echo `tmux list-panes -t $tmux_session:3 -F '#{pane_title}'`");
	$panes0 = str_replace("\n", '', explode(" ", $panes_win_1));
	$panes1 = str_replace("\n", '', explode(" ", $panes_win_2));
	$panes2 = str_replace("\n", '', explode(" ", $panes_win_3));
	$panes3 = str_replace("\n", '', explode(" ", $panes_win_4));

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
		$PYTHON = "python3 -OO";
	else
		$PYTHON = "python -OO";

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

	$_sleep = "$_phpn ${DIR}testing/Release_scripts/showsleep.php";

	if ( $running == "TRUE" )
	{
		//fix names
		if (( $fix_names == "TRUE" ) && ( $i == 1 ))
		{
			$color = get_color($colors_start, $colors_end, $colors_exc);
			$log = writelog($panes1[0]);
			shell_exec("tmux respawnp -t${tmux_session}:1.0 'echo \"\033[38;5;${color}m\"; \
					$_phpn ${DIR}testing/Release_scripts/fixReleaseNames.php 2 true all no $log; \
					$_phpn ${DIR}testing/Release_scripts/fixReleaseNames.php 4 true all yes $log; \
					$_phpn ${DIR}testing/Release_scripts/fixReleaseNames.php 6 true all no $log; date +\"%D %T\"; $_sleep $fix_timer' 2>&1 1> /dev/null");
		}
		elseif ( $fix_names == "TRUE" )
		{
			$color = get_color($colors_start, $colors_end, $colors_exc);
			$log = writelog($panes1[0]);
			shell_exec("tmux respawnp -t${tmux_session}:1.0 'echo \"\033[38;5;${color}m\"; \
					$_phpn ${DIR}testing/Release_scripts/fixReleaseNames.php 1 true all no $log; \
					$_phpn ${DIR}testing/Release_scripts/fixReleaseNames.php 3 true all yes $log; \
					$_phpn ${DIR}testing/Release_scripts/fixReleaseNames.php 5 true all no $log; date +\"%D %T\"; $_sleep $fix_timer' 2>&1 1> /dev/null");
		}
		else
		{
			$color = get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:1.0 'echo \"\033[38;5;${color}m\n${panes1[0]} has been disabled/terminated by Fix Release Names\"'");
		}

		//misc sorter
		if ( $sorter == "TRUE" )
		{
			$color = get_color($colors_start, $colors_end, $colors_exc);
			$log = writelog($panes1[2]);
			shell_exec("tmux respawnp -t${tmux_session}:1.2 'echo \"\033[38;5;${color}m\"; \
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
			$color = get_color($colors_start, $colors_end, $colors_exc);
			$log = writelog($panes1[3]);
			shell_exec("tmux respawnp -t${tmux_session}:1.3 'echo \"\033[38;5;${color}m\"; \
					$_php ${DIR}update_scripts/nzbx_ws_hashdecrypt.php $log; date +\"%D %T\"; $_sleep $dehash_timer' 2>&1 1> /dev/null");
		}
		elseif ( $dehash == 2 )
		{
			$color = get_color($colors_start, $colors_end, $colors_exc);
			$log = writelog($panes1[3]);
			shell_exec("tmux respawnp -t${tmux_session}:1.3 'echo \"\033[38;5;${color}m\"; \
					$_php ${DIR}update_scripts/nix_scripts/tmux/bin/postprocess_pre.php $log; date +\"%D %T\"; $_sleep $dehash_timer' 2>&1 1> /dev/null");
		}
		elseif ( $dehash == 3 )
		{
			$color = get_color($colors_start, $colors_end, $colors_exc);
			$log = writelog($panes1[3]);
			shell_exec("tmux respawnp -t${tmux_session}:1.3 'echo \"\033[38;5;${color}m\"; \
					$_php ${DIR}update_scripts/nix_scripts/tmux/bin/postprocess_pre.php $log; \
					$_php ${DIR}update_scripts/nzbx_ws_hashdecrypt.php $log; date +\"%D %T\"; $_sleep $dehash_timer' 2>&1 1> /dev/null");
		}
		else
		{
			$color = get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:1.3 'echo \"\033[38;5;${color}m\n${panes1[3]} has been disabled/terminated by Decrypt Hashes\"'");
		}

		//remove crap releases
		if (( $fix_crap == "TRUE" ) && ( $i == 1 ))
		{
			$color = get_color($colors_start, $colors_end, $colors_exc);
			$log = writelog($panes1[1]);
			shell_exec("tmux respawnp -t${tmux_session}:1.1 'echo \"\033[38;5;${color}m\"; \
					$_php ${DIR}testing/Release_scripts/removeCrapReleases.php true full $log; date +\"%D %T\"; $_sleep $crap_timer' 2>&1 1> /dev/null");
		}
		elseif ( $fix_crap == "TRUE" )
		{
			$color = get_color($colors_start, $colors_end, $colors_exc);
			$log = writelog($panes1[1]);
			shell_exec("tmux respawnp -t${tmux_session}:1.1 'echo \"\033[38;5;${color}m\"; \
					$_php ${DIR}testing/Release_scripts/removeCrapReleases.php true 2 $log; date +\"%D %T\"; $_sleep $crap_timer' 2>&1 1> /dev/null");
		}
		else
		{
			$color = get_color($colors_start, $colors_end, $colors_exc);
			shell_exec("tmux respawnp -k -t${tmux_session}:1.1 'echo \"\033[38;5;${color}m\n${panes1[1]} has been disabled/terminated by Remove Crap Releases\"'");
		}

		if (( $post != "0" ) && (( $nfo_remaining_now > 0) || ( $work_remaining_now > 0)))
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
					shell_exec("tmux respawnp -k -t${tmux_session}:2.0 'echo \"\033[38;5;${color}m\n${panes2[0]} has been terminated by Possible Hung thread\"'");
					$wipe = `tmux clearhist -t${tmux_session}:2.0`;
					$color = get_color($colors_start, $colors_end, $colors_exc);
					$time2 = TIME();
				}
			}
			$dead1 = str_replace( " ", '', `tmux list-panes -t${tmux_session}:2 | grep dead | grep 0: | wc -l` );
			if ( $dead1 == 1 )
				$time2 = TIME();
			$log = writelog($panes2[0]);
			shell_exec("tmux respawnp -t${tmux_session}:2.0 'echo \"\033[38;5;${color}m\"; \
					rm -rf $tmpunrar/*; \
					$_python ${DIR}update_scripts/threaded_scripts/postprocess_threaded.py additional $log; \
					$_python ${DIR}update_scripts/threaded_scripts/postprocess_threaded.py nfo $log; date +\"%D %T\"; $_sleep $post_timer' 2>&1 1> /dev/null");
		}
		elseif (( $post != "0" ) && ( $nfo_remaining_now == 0) && ( $work_remaining_now == 0 ))
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
			$color = get_color($colors_start, $colors_end, $colors_exc);
			$log = writelog($panes2[1]);
			shell_exec("tmux respawnp -t${tmux_session}:2.1 'echo \"\033[38;5;${color}m\"; \
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
			$color = get_color($colors_start, $colors_end, $colors_exc);
			$log = writelog($panes2[2]);
			shell_exec("tmux respawnp -t${tmux_session}:2.2 'echo \"\033[38;5;${color}m\"; \
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
			$color = get_color($colors_start, $colors_end, $colors_exc);
			$log = writelog($panes1[3]);
			shell_exec("tmux respawnp -t${tmux_session}:1.4 'echo \"\033[38;5;${color}m\"; \
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

		if ( $seq == "TRUE" )
		{
			//run import-nzb-bulk
			if (( $import != "0" ) && ( $kill_pp == "FALSE" ))
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				$log = writelog($panes0[1]);
				shell_exec("tmux respawnp -t${tmux_session}:0.1 'echo \"\033[38;5;${color}m\"; \
						$_python ${DIR}update_scripts/threaded_scripts/import_threaded.py $log; date +\"%D %T\"; $_sleep $import_timer' 2>&1 1> /dev/null");
			}
			else
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.1 'echo \"\033[38;5;${color}m\n${panes0[1]} has been disabled/terminated by Import\"'");
			}

			//run update_binaries
			$color = get_color($colors_start, $colors_end, $colors_exc);
			$log = writelog($panes0[2]);
			if (( $kill_coll == "FALSE" ) && ( $kill_pp == "FALSE" ) && ( TIME() - $time6 <= 4800 ))
			{
				//runs all/safe less than 4800
				if (( $binaries == "TRUE" ) && ( $backfill == "4" ) && ( $releases_run == "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\"; \
							$_python ${DIR}update_scripts/threaded_scripts/partrepair_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/binaries_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/backfill_safe_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; \
							$run_releases $log; date +\"%D %T\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				//runs all less than 4800
				elseif (( $binaries == "TRUE" ) && ( $backfill != "0" ) && ( $releases_run == "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\"; \
							$_python ${DIR}update_scripts/threaded_scripts/partrepair_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/binaries_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/backfill_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; \
							$run_releases $log; date +\"%D %T\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				//runs bin/back/safe less than 4800
				elseif (( $binaries == "TRUE" ) && ( $backfill == "4" ) && ( $releases_run != "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\"; \
							$_python ${DIR}update_scripts/threaded_scripts/partrepair_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/binaries_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/backfill_safe_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; date +\"%D %T\"; \
							echo \"\nreleases has been disabled/terminated by Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				//runs bin/back less than 4800
				elseif (( $binaries == "TRUE" ) && ( $backfill != "0" ) && ( $releases_run != "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\"; \
							$_python ${DIR}update_scripts/threaded_scripts/partrepair_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/binaries_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/backfill_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; date +\"%D %T\"; echo \"\nreleases have been disabled/terminated by Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				//runs back/safe/rel less than 4800
				elseif (( $binaries != "TRUE" ) && ( $backfill == "4" ) && ( $releases_run == "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\"; \
							$_python ${DIR}update_scripts/threaded_scripts/backfill_safe_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; \
							$run_releases $log; date +\"%D %T\"; echo \"\nbinaries has been disabled/terminated by Binaries\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				//runs back/rel less than 4800
				elseif (( $binaries != "TRUE" ) && ( $backfill != "0" ) && ( $releases_run == "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\"; \
							$_python ${DIR}update_scripts/threaded_scripts/backfill_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; \
							$run_releases $log; date +\"%D %T\"; echo \"\nbinaries has been disabled/terminated by Binaries\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				//runs bin/rel less than 4800
				elseif (( $binaries == "TRUE" ) && ( $backfill == "0" ) && ( $releases_run == "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\"; \
							$_python ${DIR}update_scripts/threaded_scripts/partrepair_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/binaries_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; \
							$run_releases $log; date +\"%D %T\"; echo \"\nbackfill has been disabled/terminated by Backfill\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				//runs bin less than 4800
				elseif (( $binaries == "TRUE" ) && ( $backfill == "0" ) && ( $releases_run != "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\"; \
							$_python ${DIR}update_scripts/threaded_scripts/partrepair_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/binaries_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; date +\"%D %T\"; echo \"\nbackfill and releases have been disabled/terminated by Backfill and Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				//runs back/safe less than 4800
				elseif (( $binaries != "TRUE" ) && ( $backfill == "4" ) && ( $releases_run != "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\"; \
							$_python ${DIR}update_scripts/threaded_scripts/backfill_safe_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; date +\"%D %T\"; echo \"\nbinaries and releases have been disabled/terminated by Binaries and Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				//runs back less than 4800
				elseif (( $binaries != "TRUE" ) && ( $backfill == "4" ) && ( $releases_run != "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\"; \
							$_python ${DIR}update_scripts/threaded_scripts/backfill_threaded.py $log; \
							$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; date +\"%D %T\"; echo \"\nbinaries and releases have been disabled/terminated by Binaries and Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				//runs rel less than 4800
				elseif (( $binaries != "TRUE" ) && ( $backfill == "0" ) && ( $releases_run == "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\"; \
							$run_releases $log; date +\"%D %T\"; echo \"\nbinaries and backfill has been disabled/terminated by Binaries and Backfill\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}
				elseif (( $binaries != "TRUE" ) && ( $backfill == "0" ) && ( $releases_run != "TRUE" ))
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\"; \
							echo \"binaries, backfill and releases have been disabled/terminated by Binaries, Backfill and Releases\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
				}

			}
			elseif (( $kill_coll == "FALSE" ) && ( $kill_pp == "FALSE" ) && ( TIME() - $time6 >= 4800 ))
			{
				//run backfill all once and resets the timer
				if ( $backfill != "0" )
				{
					shell_exec("tmux respawnp -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\"; \
						$_python ${DIR}update_scripts/threaded_scripts/backfill_threaded.py all $log; \
						$_python ${DIR}update_scripts/threaded_scripts/grabnzbs_threaded.py $log; date +\"%D %T\"; $_sleep $seq_timer' 2>&1 1> /dev/null");
					$time6 = TIME();
				}
			}
			elseif (( $kill_coll == "TRUE" ) || ( $kill_pp == "TRUE" ))
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\n${panes0[2]} has been disabled/terminated by Exceeding Limits\"'");
			}
		}
		else
		{
			//run update_binaries
			$color = get_color($colors_start, $colors_end, $colors_exc);
			if (( $binaries == "TRUE" ) && ( $kill_coll == "FALSE" ) && ( $kill_pp == "FALSE" ))
			{
				$log = writelog($panes0[2]);
				shell_exec("tmux respawnp -t${tmux_session}:0.2 'echo \"\033[38;5;${color}m\"; \
						$_python ${DIR}update_scripts/threaded_scripts/partrepair_threaded.py $log; \
						$_python ${DIR}update_scripts/threaded_scripts/binaries_threaded.py $log; \
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
				$color = get_color($colors_start, $colors_end, $colors_exc);
				$log = writelog($panes0[3]);
				shell_exec("tmux respawnp -t${tmux_session}:0.3 'echo \"\033[38;5;${color}m\"; \
						$_python ${DIR}update_scripts/threaded_scripts/backfill_safe_threaded.py $log; date +\"%D %T\"; $_sleep $backsleep' 2>&1 1> /dev/null");
			}
			elseif (( $backfill != "0" ) && ( $kill_coll == "FALSE" ) && ( $kill_pp == "FALSE" ) && ( TIME() - $time6 <= 4800 ))
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				$log = writelog($panes0[3]);
				shell_exec("tmux respawnp -t${tmux_session}:0.3 'echo \"\033[38;5;${color}m\"; \
						$_python ${DIR}update_scripts/threaded_scripts/backfill_threaded.py group $log; date +\"%D %T\"; $_sleep $backsleep' 2>&1 1> /dev/null");
			}
			elseif (( $backfill != "0" ) && ( $kill_coll == "FALSE" ) && ( $kill_pp == "FALSE" ) && ( TIME() - $time6 >= 4800 ))
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				$log = writelog($panes0[3]);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.3 'echo \"\033[38;5;${color}m\"; \
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
				$color = get_color($colors_start, $colors_end, $colors_exc);
				$log = writelog($panes0[1]);
				shell_exec("tmux respawnp -t${tmux_session}:0.1 'echo \"\033[38;5;${color}m\"; \
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
				$color = get_color($colors_start, $colors_end, $colors_exc);
				$log = writelog($panes0[4]);
				shell_exec("tmux respawnp -t${tmux_session}:0.4 'echo \"\033[38;5;${color}m\"; \
						$run_releases $log; date +\"%D %T\"; $_sleep $rel_timer' 2>&1 1> /dev/null");
			}
			else
			{
				$color = get_color($colors_start, $colors_end, $colors_exc);
				shell_exec("tmux respawnp -k -t${tmux_session}:0.4 'echo \"\033[38;5;${color}m\n${panes0[4]} has been disabled/terminated by Releases\"'");
			}
		}
	}
	elseif ( $seq != "TRUE" )
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
	else
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

	$i++;
	sleep(5);
}
?>
