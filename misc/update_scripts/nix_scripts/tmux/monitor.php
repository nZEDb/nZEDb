<?php

require_once(dirname(__FILE__)."/../../../../www/config.php");
require_once(WWW_DIR."/lib/postprocess.php");
require_once(WWW_DIR."/lib/framework/db.php");

$version="0.1r147";

$db = new DB();
$DIR = WWW_DIR."/..";

//totals per category in db, results by parentID
$qry = "SELECT COUNT( releases.categoryID ) AS cnt, parentID FROM releases INNER JOIN category ON releases.categoryID = category.ID WHERE parentID IS NOT NULL GROUP BY parentID";

//needs to be processed query
$proc = "SELECT
			( SELECT COUNT( groupID ) AS cnt from releases where consoleinfoID IS NULL and categoryID BETWEEN 1000 AND 1999 ) AS console,
			( SELECT COUNT( groupID ) AS cnt from releases where imdbID IS NULL and categoryID BETWEEN 2000 AND 2999 ) AS movies,
			( SELECT COUNT( groupID ) AS cnt from releases where musicinfoID IS NULL and categoryID BETWEEN 3000 AND 3999 ) AS audio,
			( SELECT COUNT( groupID ) AS cnt from releases r left join category c on c.ID = r.categoryID where (categoryID BETWEEN 4000 AND 4999 and ((r.passwordstatus between -6 and -1) or (r.haspreview = -1 and c.disablepreview = 0)))) AS pc,
			( SELECT COUNT( groupID ) AS cnt from releases where rageID = -1 and categoryID BETWEEN 5000 AND 5999 ) AS tv,
			( SELECT COUNT( groupID ) AS cnt from releases r left join category c on c.ID = r.categoryID where (r.passwordstatus between -6 and -1) or (r.haspreview = -1 and c.disablepreview = 0)) AS work,
			( SELECT COUNT( groupID ) AS cnt from releases) AS releases,
			( SELECT COUNT( groupID ) AS cnt FROM releases WHERE nfostatus in ( 0, 1 )) AS nfo,
			( SELECT COUNT( groupID ) AS cnt FROM releases r WHERE r.nfostatus between -6 and -1 and nzbstatus = 1 ) AS nforemains,
			( SELECT UNIX_TIMESTAMP(adddate) from releases order by adddate desc limit 1 ) AS newestadd,
            ( SELECT COUNT( groupID ) from collections ) collections,
			( SELECT COUNT( groupID ) from collections where filecheck = 3 ) collections_3,
			( SELECT value from tmux where setting = 'DEFRAG_CACHE' ) defrag,
			( SELECT value from tmux where setting = 'MONITOR_DELAY' ) monitor,
			( SELECT value from tmux where setting = 'BACKFILL_DELAY' ) backfill,
			( SELECT value from tmux where setting = 'COLLECTIONS' ) collections_kill,
			( SELECT value from tmux where setting = 'TMUX_SESSION' ) tmux_session,
			( SELECT value from tmux where setting = 'NICENESS' ) niceness,
			( SELECT value from tmux where setting = 'RUNNING' ) running,
			( SELECT value from tmux where setting = 'BINARIES' ) binaries_run,
			( SELECT value from tmux where setting = 'BACKFILL' ) backfill,
			( SELECT value from tmux where setting = 'IMPORT' ) import,
			( SELECT value from tmux where setting = 'NZBS' ) nzbs,
			( SELECT value from tmux where setting = 'NFOS' ) nfos,
			( SELECT value from tmux where setting = 'POST' ) post,
			( SELECT value from tmux where setting = 'RELEASES' ) releases_run,
			( SELECT value from tmux where setting = 'MYSQL_PROC' ) process_list,
			( SELECT name from releases order by adddate desc limit 1 ) AS newestaddname";

//flush query cache
$qcache = "FLUSH QUERY CACHE";

//get microtime
function microtime_float()
{
	list($usec, $sec) = explode(" ", microtime());
	return ((float)$usec + (float)$sec);
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

//create timers
$time = TIME();
$time18 = TIME();
$time19 = TIME();

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
$collections = 0;
$collections_3 = 0;

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

$total_work_now_formatted = 0;
$misc_releases_now_formatted = 0;
$misc_remaining_now_formatted = 0;
$book_releases_now_formatted = 0;
$book_releases_proc_formatted = 0;
$tvrage_releases_now_formatted = 0;
$tvrage_releases_proc_formatted = 0;
$pc_releases_now_formatted = 0;
$pc_releases_proc_formatted = 0;
$music_releases_now_formatted = 0;
$music_releases_proc_formatted = 0;
$movie_releases_now_formatted = 0;
$movie_releases_proc_formatted = 0;
$console_releases_now_formatted = 0;
$console_releases_proc_formatted = 0;
$nfo_now_formatted = 0;
$nfo_remaining_now_formatted = 0;

$defrag = 900;
$monitor = 15;
$backfill = 30;
$collections_kill = 1000;

$running = true;

//create initial display
passthru('clear');
//printf("\033[1;31m  First insert:\033[0m ".relativeTime("$firstdate")."\n");
$mask1 = "\033[1;33m%-16s \033[38;5;214m%-44.44s \n";
$mask2 = "\033[1;33m%-16s \033[38;5;214m%-34.34s \n";
printf($mask2, "Monitor Running v$version: ", relativeTime("$time"));
printf($mask1, "Newest Release:", "$newestname");
printf($mask1, "Release Added:", relativeTime("$newestdate")."ago");

$mask = "%-15.15s %22.22s %22.22s\n";
printf("\033[1;33m\n");
printf($mask, "Tables", "Not Ready", "Ready");
printf($mask, "====================", "====================", "====================");
printf("\033[38;5;214m");
printf($mask, "Collections", "$collections", "$collections_3");

printf("\033[1;33m\n");
printf($mask, "Category", "In Process", "In Database");
printf($mask, "====================", "====================", "====================");
printf("\033[38;5;214m");
printf($mask, "NFO's","$nfo_remaining_now_formatted($nfo_diff)","$nfo_now_formatted($nfo_percent%)");
printf($mask, "Console(1000)","$console_releases_proc_formatted($console_diff)","$console_releases_now_formatted($console_percent%)");
printf($mask, "Movie(2000)","$movie_releases_proc_formatted($movie_diff)","$movie_releases_now_formatted($movie_percent%)");
printf($mask, "Audio(3000)","$music_releases_proc_formatted($music_diff)","$music_releases_now_formatted($music_percent%)");
printf($mask, "PC(4000)","$pc_releases_proc_formatted($pc_diff)","$pc_releases_now_formatted($pc_percent%)");
printf($mask, "TVShows(5000)","$tvrage_releases_proc_formatted($tvrage_diff)","$tvrage_releases_now_formatted($tvrage_percent%)");
printf($mask, "Misc(7000)","$misc_remaining_now_formatted($misc_diff)","$misc_releases_now_formatted($misc_percent%)");
printf($mask, "Books(8000)","$book_releases_proc_formatted($book_diff)","$book_releases_now_formatted($book_percent%)");
printf($mask, "Total", "$total_work_now_formatted($work_diff)", "$releases_now_formatted($releases_since_start)");

$i = 1;
while( $i > 0 )
{

	//get microtime at start of loop
	$time_loop_start = microtime_float();

	$getdate = gmDate("Ymd");

	//defrag the query cache every 15 minutes
	if (( TIME() - $time18 >= $defrag ) || ( $i == 1 ))
	{
		$result = @$db->query($qcache);
		printf($mask2, "Query cache cleaned", "", "");
	}

	if ((( TIME() - $time19 ) >= $monitor ) || ( $i == 1 )) {
		//get microtime to at start of queries
		$query_timer_start=microtime_float();
		$result = @$db->query($qry);
		$initquery = array();
		foreach ($result as $cat=>$sub)
		{
			$initquery[$sub['parentID']] = $sub['cnt'];
		}
		$proc_result = @$db->query($proc);
		$time19 = TIME();
		$runloop = "true";
	} else {
		$runloop = "false";
	}

	//run queries
	if ((( TIME() - $time19 ) >= $monitor ) || ( $i == 1 )) {
		//get microtime to at start of queries
		$query_timer_start=microtime_float();
		$result = @$db->query($qry);
		$initquery = array();
		foreach ($result as $cat=>$sub)
		{
			$initquery[$sub['parentID']] = $sub['cnt'];
		}
		$proc_result = @$db->query($proc);
		$time19 = TIME();
		$runloop = "true";
	} else {
		$runloop = "false";
	}

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

	//initial query for total releases
	if (( @$proc_result[0]['work'] != NULL ) && ( $work_start == 0 )) { $work_start = $proc_result[0]['work']; }
	if (( @$proc_result[0]['releases'] ) && ( $releases_start == 0 )) { $releases_start = $proc_result[0]['releases']; }

	//get start values from $qry
	if ( $i == "1" ) 
	{
		if ( @$proc_result[0]['nforemains'] != NULL ) { $nfo_remaining_start = $proc_result[0]['nforemains']; }
		if ( @$proc_result[0]['console'] != NULL ) { $console_releases_proc_start = $proc_result[0]['console']; }
		if ( @$proc_result[0]['movies'] != NULL ) { $movie_releases_proc_start = $proc_result[0]['movies']; }
		if ( @$proc_result[0]['audio'] != NULL ) { $music_releases_proc_start = $proc_result[0]['audio']; }
		if ( @$proc_result[0]['pc'] != NULL ) { $pc_releases_proc_start = $proc_result[0]['pc']; }
		if ( @$proc_result[0]['tv'] != NULL ) { $tvrage_releases_proc_start = $proc_result[0]['tv']; }
		if ( @$proc_result[0]['book'] != NULL ) { $book_releases_proc_start = $proc_result[0]['book']; }
		if ( @$proc_result[0]['work'] != NULL ) { $work_remaining_start = $proc_result[0]['work']; }
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
	if ( @$proc_result[0]['console'] != NULL ) { $console_releases_proc_formatted = number_format($proc_result[0]['console']); }
	if ( @$proc_result[0]['movies'] != NULL ) { $movie_releases_proc = $proc_result[0]['movies']; }
	if ( @$proc_result[0]['audio'] != NULL ) { $music_releases_proc = $proc_result[0]['audio']; }
	if ( @$proc_result[0]['pc'] != NULL ) { $pc_releases_proc = $proc_result[0]['pc']; }
	if ( @$proc_result[0]['tv'] != NULL ) { $tvrage_releases_proc = $proc_result[0]['tv']; }
	if ( @$proc_result[0]['book'] != NULL ) { $book_releases_proc = $proc_result[0]['book']; }
	if ( @$proc_result[0]['work'] != NULL ) { $work_remaining_now = $proc_result[0]['work']; }
	if ( @$proc_result[0]['releases'] != NULL ) { $releases_loop = $proc_result[0]['releases']; }
	if ( @$proc_result[0]['nforemains'] != NULL ) { $nfo_remaining_now = $proc_result[0]['nforemains']; }
	if ( @$proc_result[0]['nfo'] != NULL ) { $nfo_now = $proc_result[0]['nfo']; }
	if ( @$proc_result[0]['parts'] != NULL ) { $parts_rows_unformatted = $proc_result[0]['parts']; }
	if ( @$proc_result[0]['parts'] != NULL ) { $parts_rows = number_format($proc_result[0]['parts']); }
	if ( @$proc_result[0]['partsize'] != NULL ) { $parts_size_gb = $proc_result[0]['partsize']; }
    if ( @$proc_result[0]['collections'] != NULL ) { $collections = $proc_result[0]['collections']; }
    if ( @$proc_result[0]['collections_3'] != NULL ) { $collections_3 = $proc_result[0]['collections_3']; }

    if ( @$proc_result[0]['defrag'] != NULL ) { $defrag = $proc_result[0]['defrag']; }
    if ( @$proc_result[0]['tmux_session'] != NULL ) { $tmux_session = $proc_result[0]['tmux_session']; }
    if ( @$proc_result[0]['monitor'] != NULL ) { $monitor = $proc_result[0]['monitor']; }
    if ( @$proc_result[0]['backfill'] != NULL ) { $backfill = $proc_result[0]['backfill']; }
    if ( @$proc_result[0]['niceness'] != NULL ) { $niceness = $proc_result[0]['niceness']; }

    if ( @$proc_result[0]['running'] != NULL ) { $running = $proc_result[0]['running']; }
    if ( @$proc_result[0]['binaries_run'] != NULL ) { $binaries = $proc_result[0]['binaries_run']; }
    if ( @$proc_result[0]['backfill'] != NULL ) { $backfill = $proc_result[0]['backfill']; }
    if ( @$proc_result[0]['import'] != NULL ) { $import = $proc_result[0]['import']; }
    if ( @$proc_result[0]['nzbs'] != NULL ) { $nzbs = $proc_result[0]['nzbs']; }
    if ( @$proc_result[0]['nfos'] != NULL ) { $nfos = $proc_result[0]['nfos']; }
    if ( @$proc_result[0]['post'] != NULL ) { $post = $proc_result[0]['post']; }
    if ( @$proc_result[0]['releases_run'] != NULL ) { $releases = $proc_result[0]['releases_run']; }
    if ( @$proc_result[0]['process_list'] != NULL ) { $process_list = $proc_result[0]['process_list']; }

	if ( @$proc_result[0]['binaries'] != NULL ) { $binaries_rows_unformatted = $proc_result[0]['binaries']; }
	if ( @$proc_result[0]['binaries'] != NULL ) { $binaries_rows = number_format($proc_result[0]['binaries']); }
	if ( @$proc_result[0]['binaries'] != NULL ) { $binaries_total_unformatted = $proc_result[0]['binaries_total']; }
	if ( @$proc_result[0]['binaries'] != NULL ) { $binaries_total = number_format($proc_result[0]['binaries_total']); }

	if ( @$proc_result[0]['binariessize'] != NULL ) { $binaries_size_gb = $proc_result[0]['binariessize']; }

	if ( @$proc_result[0]['releases'] ) { $releases_now = $proc_result[0]['releases']; }
	if ( @$proc_result[0]['releases'] ) { $releases_now_formatted = number_format($proc_result[0]['releases']); }
	if ( @$proc_result[0]['newestaddname'] ) { $newestname = $proc_result[0]['newestaddname']; }
	if ( @$proc_result[0]['newestadd'] ) { $newestdate = $proc_result[0]['newestadd']; }

	//calculate releases difference
	$releases_misc_diff = number_format( $releases_now - $releases_start );
	$releases_since_start = number_format( $releases_now - $releases_start );
	$work_misc_diff = $work_remaining_now - $work_remaining_start;

	$total_work_now = $work_remaining_now + $tvrage_releases_proc + $music_releases_proc + $movie_releases_proc + $console_releases_proc + $book_releases_proc + $nfo_remaining_now;
	if ( $i == 1 ) { $total_work_start = $total_work_now; }
	$total_work_now_formatted = number_format($total_work_now);

	$nfo_diff = number_format( $nfo_remaining_now - $nfo_remaining_start );
	$console_diff = number_format( $console_releases_proc - $console_releases_proc_start );
	$movie_diff = number_format( $movie_releases_proc - $movie_releases_proc_start );
	$music_diff = number_format( $music_releases_proc - $music_releases_proc_start );
	$pc_diff = number_format( $pc_releases_proc - $pc_releases_proc_start );
	$tvrage_diff = number_format( $tvrage_releases_proc - $tvrage_releases_proc_start );
	$book_diff = number_format( $book_releases_proc - $book_releases_proc_start );

	//formatted  output
	$console_releases_proc_formatted = number_format( $console_releases_proc );
	$movie_releases_proc_formatted = number_format( $movie_releases_proc );
	$music_releases_proc_formatted = number_format( $music_releases_proc );
	$pc_releases_proc_formatted = number_format( $pc_releases_proc );
	$tvrage_releases_proc_formatted = number_format( $tvrage_releases_proc );
	$misc_remaining_now_formatted = number_format( $work_remaining_now );
	$book_releases_proc_formatted = number_format( $book_releases_proc );
	$nfo_remaining_now_formatted = number_format( $nfo_remaining_now );
	$nfo_now_formatted = number_format( $nfo_now );
	$console_releases_now_formatted = number_format( $console_releases_now );
	$movie_releases_now_formatted = number_format( $movie_releases_now );
	$music_releases_now_formatted = number_format( $music_releases_now );
	$pc_releases_now_formatted = number_format( $pc_releases_now );
	$tvrage_releases_now_formatted = number_format( $tvrage_releases_now );
	$book_releases_now_formatted = number_format( $book_releases_now );
	$misc_releases_now_formatted = number_format( $misc_releases_now );
	$misc_diff = number_format( $work_remaining_now - $work_start );

	$work_since_start = ( $total_work_now - $total_work_start );
	$work_diff = number_format($work_since_start);

	//get microtime at end of queries
	if ( $runloop == "true" ) {
		$query_timer = microtime_float()-$query_timer_start;
	}


	//update display
	passthru('clear');
	//printf("\033[1;31m  First insert:\033[0m ".relativeTime("$firstdate")."\n");
	printf($mask2, "Monitor Running v$version: ", relativeTime("$time"));
	printf($mask1, "Newest Release:", "$newestname");
	printf($mask1, "Release Added:", relativeTime("$newestdate")."ago");

	$mask = "%-15.15s %22.22s %22.22s\n";
	printf("\033[1;33m\n");
	printf($mask, "Tables", "Not Ready", "Ready");
	printf($mask, "====================", "====================", "====================");
	printf("\033[38;5;214m");
	printf($mask, "Collections", "$collections", "$collections_3");

	printf("\033[1;33m\n");
	printf($mask, "Category", "In Process", "In Database");
	printf($mask, "====================", "====================", "====================");
	printf("\033[38;5;214m");
	printf($mask, "NFO's","$nfo_remaining_now_formatted($nfo_diff)","$nfo_now_formatted($nfo_percent%)");
	printf($mask, "Console(1000)","$console_releases_proc_formatted($console_diff)","$console_releases_now_formatted($console_percent%)");
	printf($mask, "Movie(2000)","$movie_releases_proc_formatted($movie_diff)","$movie_releases_now_formatted($movie_percent%)");
	printf($mask, "Audio(3000)","$music_releases_proc_formatted($music_diff)","$music_releases_now_formatted($music_percent%)");
	printf($mask, "PC(4000)","$pc_releases_proc_formatted($pc_diff)","$pc_releases_now_formatted($pc_percent%)");
	printf($mask, "TVShows(5000)","$tvrage_releases_proc_formatted($tvrage_diff)","$tvrage_releases_now_formatted($tvrage_percent%)");
	printf($mask, "Misc(7000)","$misc_remaining_now_formatted($misc_diff)","$misc_releases_now_formatted($misc_percent%)");
	printf($mask, "Books(8000)","$book_releases_proc_formatted($book_diff)","$book_releases_now_formatted($book_percent%)");
	printf($mask, "Total", "$total_work_now_formatted($work_diff)", "$releases_now_formatted($releases_since_start)");

	//get microtime at end of queries
	if ( $runloop == "true" )
	{
		$query_timer = microtime_float()-$query_timer_start;
	}

	//get list of panes by name
	$panes_win_1 = shell_exec("echo `tmux list-panes -t  $tmux_session:1 -F '#{pane_title}'`");
	$panes1 = str_replace("\n", '', explode(" ", $panes_win_1));

	if ( $running  == "TRUE" )
	{

		//run postprocess_releases
		if ( $nfos == "TRUE" )
		{
			$color = get_color();
			shell_exec("tmux respawnp -t $tmux_session:1.1 'echo \"\033[38;5;\"$color\"m\" && nice -n$niceness php $DIR/misc/update_scripts/postprocess_nfos.php' 2>&1 1> /dev/null");
		}
		else
		{
			$color = get_color();
			shell_exec("tmux respawnp -k -t $tmux_session:1.1 'echo \"\033[38;5;\"$color\"m\n$panes1[1] has been terminated by Postrocess Nfos\"'");
		}

		//run postprocess_releases
		if ( $post == "TRUE" )
		{
			$color = get_color();
			shell_exec("tmux respawnp -t $tmux_session:1.2 'echo \"\033[38;5;\"$color\"m\" && nice -n$niceness php $DIR/misc/update_scripts/postprocess_all.php' 2>&1 1> /dev/null");
		}
		else
		{
			$color = get_color();
			shell_exec("tmux respawnp -k -t $tmux_session:1.2 'echo \"\033[38;5;\"$color\"m\n$panes1[2] has been terminated by Postprocess All Others\"'");
		}

		//run update_binaries
		$color = get_color();
		if ( $binaries == "TRUE" )
		{
			shell_exec("tmux respawnp -t $tmux_session:1.3 'echo \"\033[38;5;\"$color\"m\" && nice -n$niceness python $DIR/misc/update_scripts/update_binaries_threaded.py' 2>&1 1> /dev/null");
		}
		else
		{
			$color = get_color();
			shell_exec("tmux respawnp -k -t $tmux_session:1.3 'echo \"\033[38;5;\"$color\"m\n$panes1[3] has been terminated by Binaries\"'");
		}

		//run backfill
		$color = get_color();
		if (( $i == 1 ) && ( $backfill == "TRUE" ))
		{
			shell_exec("tmux respawnp -t $tmux_session:1.4 'echo \"\033[38;5;\"$color\"m\" && echo \"Sleeping 30 to ensure the first group has finished update_binaries\" && sleep 30 && nice -n$niceness python $DIR/misc/update_scripts/backfill_threaded.py' 2>&1 1> /dev/null");
		}
		elseif ( $backfill == "TRUE" )
		{
			shell_exec("tmux respawnp -t $tmux_session:1.4 'echo \"\033[38;5;\"$color\"m\" && nice -n$niceness python $DIR/misc/update_scripts/backfill_threaded.py' 2>&1 1> /dev/null");
		}
		else
		{
			$color = get_color();
			shell_exec("tmux respawnp -k -t $tmux_session:1.4 'echo \"\033[38;5;\"$color\"m\n$panes1[4] has been terminated by Backfill\"'");
		}

		//run import-nzb-bulk
		if ( $import == "TRUE" )
		{
			$color = get_color();
			shell_exec("tmux respawnp -t $tmux_session:1.5 'echo \"\033[38;5;\"$color\"m\" && nice -n$niceness php $DIR/misc/testing/nzb-import-bulk.php /import/nzbs' 2>&1 1> /dev/null");
		}
		else
		{
			$color = get_color();
			shell_exec("tmux respawnp -k -t $tmux_session:1.5 'echo \"\033[38;5;\"$color\"m\n$panes1[5] has been terminated by Import\"'");
		}

		//run update_releases
		if ( $releases == "TRUE" )
		{
			$color = get_color();
			shell_exec("tmux respawnp -t $tmux_session:1.6 'echo \"\033[38;5;\"$color\"m\" && nice -n$niceness php $DIR/misc/update_scripts/update_releases.php 1 false' 2>&1 1> /dev/null");
		}
		else
		{
			$color = get_color();
			shell_exec("tmux respawnp -k -t $tmux_session:1.6 'echo \"\033[38;5;\"$color\"m\n$panes1[6] has been terminated by Releases\"'");
		}

	}
	else
	{
		for ($g=1; $g<=6; $g++)
		{
			$color = get_color();
			shell_exec("tmux respawnp -k -t $tmux_session:1.$g 'echo \"\033[38;5;\"$color\"m\n$panes1[$g] has been terminated by Running\"'");
		}
	}
	$i++;
	sleep(5);
}
?>
