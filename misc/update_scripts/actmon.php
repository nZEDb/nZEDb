<?php
require_once(dirname(__FILE__)."/../../www/config.php");
require_once(WWW_DIR."lib/postprocess.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/tmux.php");
require_once(WWW_DIR."lib/site.php");

$version="0.2";

$db = new DB();
$DIR = MISC_DIR;
$db_name = DB_NAME;

$tmux = new Tmux();
$s = new Sites();
$alternate_nntp_provider = $s->get()->alternate_nntp;

//totals per category in db, results by parentID
$qry = "SELECT
	( SELECT COUNT( * ) FROM `releases` WHERE `categoryID` BETWEEN 1000 AND 1999 ) AS console, ( SELECT COUNT( * ) FROM `releases` WHERE `categoryID` BETWEEN 2000 AND 2999 ) AS movies,
	( SELECT COUNT( * ) FROM `releases` WHERE `categoryID` BETWEEN 3000 AND 3999 ) AS audio, ( SELECT COUNT( * ) FROM `releases` WHERE `categoryID` BETWEEN 4000 AND 4999 ) AS pc,
	( SELECT COUNT( * ) FROM `releases` WHERE `categoryID` BETWEEN 5000 AND 5999 ) AS tv, ( SELECT COUNT( * ) FROM `releases` WHERE `categoryID` BETWEEN 6000 AND 6999 ) AS xxx,
	( SELECT COUNT( * ) FROM `releases` WHERE `categoryID` BETWEEN 7000 AND 7999 ) AS misc, ( SELECT COUNT( * ) FROM `releases` WHERE `categoryID` BETWEEN 8000 AND 8999 ) AS books";

//needs to be processed query
$proc_work = "SELECT
	( SELECT COUNT( * ) FROM releases WHERE rageID = -1 and categoryID BETWEEN 5000 AND 5999 ) AS tv,
	( SELECT COUNT( * ) FROM releases WHERE imdbID IS NULL and categoryID BETWEEN 2000 AND 2999 ) AS movies,
	( SELECT COUNT( * ) FROM releases WHERE musicinfoID IS NULL and relnamestatus != 0 and categoryID in (3010, 3040, 3050) ) AS audio,
	( SELECT COUNT( * ) FROM releases WHERE consoleinfoID IS NULL and categoryID BETWEEN 1000 AND 1999 ) AS console,
	( SELECT COUNT( * ) FROM releases WHERE bookinfoID IS NULL and categoryID = 8010 ) AS book,
	( SELECT COUNT( * ) FROM releases WHERE nzbstatus = 1 ) AS releases,
	( SELECT COUNT( * ) FROM releases WHERE nfostatus = 1 ) AS nfo,
	( SELECT COUNT( * ) FROM releases WHERE nfostatus between -6 and -1 ) AS nforemains,
	( SELECT COUNT( * ) FROM releases WHERE reqidstatus = 0 AND relnamestatus = 1 ) AS requestID_inprogress,
	( SELECT COUNT( * ) FROM releases WHERE reqidstatus = 1 ) AS requestID_matched";

$proc_work2 = "SELECT
	( SELECT COUNT( * ) FROM releases r left join category c on c.ID = r.categoryID where categoryID BETWEEN 4000 AND 4999 and ((r.passwordstatus between -6 and -1) and (r.haspreview = -1 and c.disablepreview = 0))) AS pc,
	( SELECT COUNT( * ) FROM releases r left join category c on c.ID = r.categoryID where (r.passwordstatus between -6 and -1) and (r.haspreview = -1 and c.disablepreview = 0)) AS work,
	( SELECT COUNT( * ) FROM predb where releaseID IS NOT NULL ) AS predb_matched,
	( SELECT COUNT( * ) FROM collections WHERE collectionhash IS NOT NULL ) AS collections_table,
	( SELECT COUNT( * ) FROM binaries WHERE collectionID IS NOT NULL ) AS binaries_table,
	( SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES where table_name = 'predb' AND TABLE_SCHEMA = '$db_name' ) AS predb,
	( SELECT TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES where table_name = 'parts' AND TABLE_SCHEMA = '$db_name' ) AS parts_table,
	( SELECT COUNT( distinct( collectionhash )) FROM nzbs WHERE collectionhash IS NOT NULL ) AS distinctnzbs,
	( SELECT COUNT( collectionhash ) FROM nzbs WHERE collectionhash IS NOT NULL ) AS totalnzbs,
	( SELECT COUNT( collectionhash ) FROM ( SELECT collectionhash FROM nzbs GROUP BY collectionhash, totalparts HAVING COUNT(*) >= totalparts ) AS count) AS pendingnzbs";

$proc_tmux = "SELECT
	( SELECT UNIX_TIMESTAMP(dateadded) FROM collections order by dateadded ASC limit 1 ) AS oldestcollection,
	( SELECT UNIX_TIMESTAMP(adddate) FROM predb order by adddate DESC limit 1 ) AS newestpre,
	( SELECT name FROM releases WHERE nzbstatus = 1 order by adddate DESC limit 1 ) AS newestaddname,
	( SELECT UNIX_TIMESTAMP(adddate) FROM releases WHERE nzbstatus = 1 order by adddate DESC limit 1 ) AS newestadd,
	( SELECT UNIX_TIMESTAMP(dateadded) FROM nzbs order by dateadded ASC limit 1 ) AS oldestnzb,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'MONITOR_DELAY' ) AS monitor,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'TMUX_SESSION' ) AS tmux_session,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'NICENESS' ) AS niceness,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'BINARIES' ) AS binaries_run,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'BACKFILL' ) AS backfill,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'IMPORT' ) AS import,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'NZBS' ) AS nzbs,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'POST' ) AS post,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'RELEASES' ) AS releases_run,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'RELEASES_THREADED' ) AS releases_threaded,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'FIX_NAMES' ) AS fix_names,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'SEQ_TIMER' ) AS seq_timer,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'BINS_TIMER' ) AS bins_timer,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'BACK_TIMER' ) AS back_timer,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'IMPORT_TIMER' ) AS import_timer,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'REL_TIMER' ) AS rel_timer,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'FIX_TIMER' ) AS fix_timer,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'POST_TIMER' ) AS post_timer,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'COLLECTIONS_KILL' ) AS collections_kill,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'POSTPROCESS_KILL' ) AS postprocess_kill,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'CRAP_TIMER' ) AS crap_timer,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'FIX_CRAP' ) AS fix_crap,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'TV_TIMER' ) AS tv_timer,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'UPDATE_TV' ) AS update_tv,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'POST_KILL_TIMER' ) AS post_kill_timer,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'MONITOR_PATH' ) AS monitor_path,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'MONITOR_PATH_A' ) AS monitor_path_a,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'MONITOR_PATH_B' ) AS monitor_path_b,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'SORTER' ) AS sorter,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'SORTER_TIMER' ) AS sorter_timer,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'PROGRESSIVE' ) AS progressive,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'DEHASH' ) AS dehash,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'DEHASH_TIMER' ) AS dehash_timer,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'BACKFILL_DAYS' ) AS backfilldays,
	( SELECT VALUE FROM `site` WHERE SETTING = 'debuginfo' ) AS debug,
	( SELECT VALUE FROM `site` WHERE SETTING = 'lookupbooks' ) AS processbooks,
	( SELECT VALUE FROM `site` WHERE SETTING = 'lookupmusic' ) AS processmusic,
	( SELECT VALUE FROM `site` WHERE SETTING = 'lookupgames' ) AS processgames,
	( SELECT VALUE FROM `site` WHERE SETTING = 'tmpunrarpath' ) AS tmpunrar,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'POST_AMAZON' ) AS post_amazon,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'POST_TIMER_AMAZON' ) AS post_timer_amazon,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'POST_NON' ) AS post_non,
	( SELECT VALUE FROM `tmux` WHERE SETTING = 'POST_TIMER_NON' ) AS post_timer_non,
	( SELECT COUNT( * ) FROM groups WHERE active = 1 ) AS active_groups,
	( SELECT COUNT( * ) FROM groups WHERE first_record IS NOT NULL and backfill = 1 and first_record_postdate != '2000-00-00 00:00:00' and (now() - interval backfill_target day) < first_record_postdate ) AS backfill_groups_days,
	( SELECT COUNT( * ) FROM groups WHERE first_record IS NOT NULL and backfill = 1 and first_record_postdate != '2000-00-00 00:00:00' and (now() - interval datediff(curdate(),(SELECT VALUE FROM `site` WHERE SETTING = 'safebackfilldate')) day) < first_record_postdate) AS backfill_groups_date,
	( SELECT COUNT( * ) FROM groups WHERE name IS NOT NULL ) AS all_groups";

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

// function command_exist($cmd) {
	// $returnVal = shell_exec("which $cmd 2>/dev/null");
	// return (empty($returnVal) ? false : true);
// }

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
$request_percent = 0;

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
$requestID_inprogress = 0;
$requestID_diff = 0;
$requestID_matched = 0;

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
$usp1activeconnections = 0;
$usp1totalconnections = 0;
$usp2activeconnections = 0;
$usp2totalconnections = 0;


$mask1 = "\033[1;33m%-16s \033[38;5;214m%-49.49s \n";
$mask2 = "\033[1;33m%-16s \033[38;5;214m%-39.39s \n";

//create display
passthru('clear');
//printf("\033[1;31m First insert:\033[0m ".relativeTime("$firstdate")."\n");
printf($mask2, "Monitor Running v$version: ", relativeTime("$time"));
printf($mask1, "USP Connections:", $usp1activeconnections." active (".$usp1totalconnections." total used) - ".NNTP_SERVER);
if ($alternate_nntp_provider == "1")
	printf($mask1, "USP Alternate:", $usp2activeconnections." active (".$usp2totalconnections." total used) - ".( ($alternate_nntp_provider == "1") ? NNTP_SERVER_A : "n/a" ));
printf($mask1, "Newest Release:", "$newestname");
printf($mask1, "Release Added:", relativeTime("$newestadd")."ago");
printf($mask1, "Predb Updated:", relativeTime("$newestpre")."ago");
printf($mask1, "Collection Age:", relativeTime("$oldestcollection")."ago");
printf($mask1, "NZBs Age:", relativeTime("$oldestnzb")."ago");

$mask = "%-15.15s %27.27s %22.22s\n";
printf("\033[1;33m\n");
printf($mask, "Collections", "Binaries", "Parts");
printf($mask, "====================", "=========================", "====================");
printf("\033[38;5;214m");
printf($mask, number_format($collections_table), number_format($binaries_table), number_format($parts_table));

printf("\033[1;33m\n");
printf($mask, "Category", "In Process", "In Database");
printf($mask, "====================", "=========================", "====================");
printf("\033[38;5;214m");
printf($mask, "NZBs",number_format($totalnzbs)."(".number_format($distinctnzbs).")", number_format($pendingnzbs));
printf($mask, "predb",number_format($predb - $predb_matched)."(".$pre_diff.")",number_format($predb_matched)."(".$pre_percent."%)");
printf($mask, "requestID",$requestID_inprogress."(".$requestID_diff.")",number_format($requestID_matched)."(".$request_percent."%)");
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
printf($mask, "====================", "=========================", "====================");
printf("\033[38;5;214m");
if ( $backfilldays == "1" )
	printf($mask, "Activated", $active_groups."(".$all_groups.")", $backfill_groups_days."(".$all_groups.")");
else
	printf($mask, "Activated", $active_groups."(".$all_groups.")", $backfill_groups_date."(".$all_groups.")");

$monitor = 30;
$i = 0;
while( true )
{
	//create new db connection
	unset($db);
	$db = new DB();

	$getdate = gmDate("Ymd");
	$proc_tmux_result = @$db->query($proc_tmux);
	$initquery = @$db->query($qry);

	//run queries only after time exceeded, this query take take awhile
	$running = $tmux->get()->RUNNING;
	if ( $i % 6 == 0 )
	{
		print("Updating Display");
		$proc_work_result = @$db->query($proc_work);
		$proc_work_result2 = @$db->query($proc_work2);
		$time1 = TIME();
	}
	else
	{
		print("NOT Updating Display");
	}
	
	//get start values from $qry
	if ( $i == 1 )
	{
		if ( @$proc_work_result[0]['nforemains'] != NULL ) { $nfo_remaining_start = $proc_work_result[0]['nforemains']; }
		if ( @$proc_work_result2[0]['predb_matched'] != NULL ) { $predb_start = $proc_work_result2[0]['predb_matched']; }
		if ( @$proc_work_result[0]['console'] != NULL ) { $console_releases_proc_start = $proc_work_result[0]['console']; }
		if ( @$proc_work_result[0]['movies'] != NULL ) { $movie_releases_proc_start = $proc_work_result[0]['movies']; }
		if ( @$proc_work_result[0]['audio'] != NULL ) { $music_releases_proc_start = $proc_work_result[0]['audio']; }
		if ( @$proc_work_result2[0]['pc'] != NULL ) { $pc_releases_proc_start = $proc_work_result2[0]['pc']; }
		if ( @$proc_work_result[0]['tv'] != NULL ) { $tvrage_releases_proc_start = $proc_work_result[0]['tv']; }
		if ( @$proc_work_result[0]['book'] != NULL ) { $book_releases_proc_start = $proc_work_result[0]['book']; }
		if ( @$proc_work_result2[0]['work'] != NULL ) { $work_remaining_start = $proc_work_result2[0]['work']; }
		if ( @$proc_work_result2[0]['work'] != NULL ) { $work_start = $proc_work_result2[0]['work']; }
		if ( @$proc_work_result[0]['releases'] != NULL ) { $releases_start = $proc_work_result[0]['releases']; }
		if ( @$proc_work_result[0]['requestID_inprogress'] != NULL ) { $requestID_inprogress_start = $proc_work_result[0]['requestID_inprogress']; }
	}

	//get values from $qry
	if ( @$initquery[0]['console'] != NULL ) { $console_releases_now = $initquery[0]['console']; }
	if ( @$initquery[0]['movies'] != NULL ) { $movie_releases_now = $initquery[0]['movies']; }
	if ( @$initquery[0]['audio'] != NULL ) { $music_releases_now = $initquery[0]['audio']; }
	if ( @$initquery[0]['pc'] != NULL ) { $pc_releases_now = $initquery[0]['pc']; }
	if ( @$initquery[0]['tv'] != NULL ) { $tvrage_releases_now = $initquery[0]['tv']; }
	if ( @$initquery[0]['xxx'] != NULL ) { $pron_releases_now = $initquery[0]['xxx']; }
	if ( @$initquery[0]['misc'] != NULL ) { $misc_releases_now = $initquery[0]['misc']; }
	if ( @$initquery[0]['books'] != NULL ) { $book_releases_now = $initquery[0]['books']; }

	//get values from $proc
	if ( @$proc_work_result[0]['console'] != NULL ) { $console_releases_proc = $proc_work_result[0]['console']; }
	if ( @$proc_work_result[0]['console'] != NULL ) { $console_releases_proc = $proc_work_result[0]['console']; }
	if ( @$proc_work_result[0]['movies'] != NULL ) { $movie_releases_proc = $proc_work_result[0]['movies']; }
	if ( @$proc_work_result[0]['audio'] != NULL ) { $music_releases_proc = $proc_work_result[0]['audio']; }
	if ( @$proc_work_result2[0]['pc'] != NULL ) { $pc_releases_proc = $proc_work_result2[0]['pc']; }
	if ( @$proc_work_result[0]['tv'] != NULL ) { $tvrage_releases_proc = $proc_work_result[0]['tv']; }
	if ( @$proc_work_result[0]['book'] != NULL ) { $book_releases_proc = $proc_work_result[0]['book']; }
	if ( @$proc_work_result2[0]['work'] != NULL ) { $work_remaining_now = $proc_work_result2[0]['work']; }
	if ( @$proc_work_result[0]['releases'] != NULL ) { $releases_loop = $proc_work_result[0]['releases']; }
	if ( @$proc_work_result[0]['nforemains'] != NULL ) { $nfo_remaining_now = $proc_work_result[0]['nforemains']; }
	if ( @$proc_work_result[0]['nfo'] != NULL ) { $nfo_now = $proc_work_result[0]['nfo']; }
	if ( @$proc_work_result[0]['parts'] != NULL ) { $parts_rows = $proc_work_result[0]['parts']; }
	if ( @$proc_work_result[0]['partsize'] != NULL ) { $parts_size_gb = $proc_work_result[0]['partsize']; }
	if ( @$proc_work_result2[0]['collections_table'] != NULL ) { $collections_table = $proc_work_result2[0]['collections_table']; }
	if ( @$proc_work_result2[0]['binaries_table'] != NULL ) { $binaries_table = $proc_work_result2[0]['binaries_table']; }
	if ( @$proc_work_result2[0]['parts_table'] != NULL ) { $parts_table = $proc_work_result2[0]['parts_table']; }

	if ( @$proc_work_result2[0]['predb'] != NULL ) { $predb = $proc_work_result2[0]['predb']; }
	if ( @$proc_work_result2[0]['predb_matched'] != NULL ) { $predb_matched = $proc_work_result2[0]['predb_matched']; }
	if ( @$proc_work_result2[0]['distinctnzbs'] != NULL ) { $distinctnzbs = $proc_work_result2[0]['distinctnzbs']; }
	if ( @$proc_work_result2[0]['totalnzbs'] != NULL ) { $totalnzbs = $proc_work_result2[0]['totalnzbs']; }
	if ( @$proc_work_result2[0]['pendingnzbs'] != NULL ) { $pendingnzbs = $proc_work_result2[0]['pendingnzbs']; }
	if ( @$proc_work_result[0]['requestID_inprogress'] != NULL ) { $requestID_inprogress = $proc_work_result[0]['requestID_inprogress']; }
	if ( @$proc_work_result[0]['requestID_matched'] != NULL ) { $requestID_matched = $proc_work_result[0]['requestID_matched']; }

	if ( @$proc_tmux_result[0]['collections_kill'] != NULL ) { $collections_kill = $proc_tmux_result[0]['collections_kill']; }
	if ( @$proc_tmux_result[0]['postprocess_kill'] != NULL ) { $postprocess_kill = $proc_tmux_result[0]['postprocess_kill']; }
	if ( @$proc_tmux_result[0]['backfilldays'] != NULL ) { $backfilldays = $proc_tmux_result[0]['backfilldays']; }
	if ( @$proc_tmux_result[0]['tmpunrar'] != NULL ) { $tmpunrar = $proc_tmux_result[0]['tmpunrar']; }

	if ( @$proc_tmux_result[0]['active_groups'] != NULL ) { $active_groups = $proc_tmux_result[0]['active_groups']; }
	if ( @$proc_tmux_result[0]['backfill_groups_days'] != NULL ) { $backfill_groups_days = $proc_tmux_result[0]['backfill_groups_days']; }
	if ( @$proc_tmux_result[0]['backfill_groups_date'] != NULL ) { $backfill_groups_date = $proc_tmux_result[0]['backfill_groups_date']; }
	if ( @$proc_tmux_result[0]['all_groups'] != NULL ) { $all_groups = $proc_tmux_result[0]['all_groups']; }

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
	if ( @$proc_tmux_result[0]['dehash'] != NULL ) { $dehash = $proc_tmux_result[0]['dehash']; }

	//reset monitor paths before query
	$monitor_path = "";
	$monitor_path_a = "";
	$monitor_path_b = "";
	if ( @$proc_tmux_result[0]['monitor_path'] != NULL ) { $monitor_path = $proc_tmux_result[0]['monitor_path']; }
	if ( @$proc_tmux_result[0]['monitor_path_a'] != NULL ) { $monitor_path_a = $proc_tmux_result[0]['monitor_path_a']; }
	if ( @$proc_tmux_result[0]['monitor_path_b'] != NULL ) { $monitor_path_b = $proc_tmux_result[0]['monitor_path_b']; }

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
	$pre_diff = number_format( $predb_matched - $predb_start );
	$requestID_diff = number_format( $requestID_inprogress - $requestID_inprogress_start );

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
		$request_percent = sprintf( "%02s", floor(( $requestID_matched / $releases_now) * 100 ));
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
	printf($mask2, "Monitor Running v$version: ", relativeTime("$time"));
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
	printf($mask, "====================", "=========================", "====================");
	printf("\033[38;5;214m");
	printf($mask, number_format($collections_table), number_format($binaries_table), "~".number_format($parts_table));

	if ((( isset( $monitor_path )) && ( file_exists( $monitor_path ))) || (( isset( $monitor_path_a )) && ( file_exists( $monitor_path_a ))) || (( isset( $monitor_path_b )) && ( file_exists( $monitor_path_b ))))
	{
		printf("\033[1;33m\n");
		printf($mask, "Ramdisk", "Used", "Free");
		printf($mask, "====================", "=========================", "====================");
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
	printf($mask, "====================", "=========================", "====================");
	printf("\033[38;5;214m");
	printf($mask, "NZBs",number_format($totalnzbs)."(".number_format($distinctnzbs).")", number_format($pendingnzbs));
	printf($mask, "predb","~".number_format($predb - $predb_matched)."(".$pre_diff.")",number_format($predb_matched)."(".$pre_percent."%)");
	printf($mask, "requestID",number_format($requestID_inprogress)."(".$requestID_diff.")",number_format($requestID_matched)."(".$request_percent."%)");
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
	printf($mask, "====================", "=========================", "====================");
	printf("\033[38;5;214m");
	if ( $backfilldays == "1" )
		printf($mask, "Activated", $active_groups."(".$all_groups.")", $backfill_groups_days."(".$all_groups.")");
	else
		printf($mask, "Activated", $active_groups."(".$all_groups.")", $backfill_groups_date."(".$all_groups.")");
	
	$i++;
	sleep(5);
}
?>
