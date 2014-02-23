<?php
require dirname(__FILE__) . '/config.php';

$binaries = new Binaries();
$c = new ColorCLI();
$s = new Sites();
$site = $s->get();

// Create the connection here and pass
$nntp = new NNTP();
if ($nntp->doConnect() === false) {
	exit($c->error("Unable to connect to usenet."));
}
if ($site->nntpproxy === "1") {
	usleep(500000);
}

if (isset($argv[1]) && $argv[1] == 'all' && $argv[1] !== 'safe' && $argv[1] !== 'alph' && $argv[1] !== 'date' && !is_numeric($argv[1]) && !isset($argv[2])) {
	$backfill = new Backfill();
	$groupName = '';
	$backfill->backfillAllGroups($nntp, $groupName);
} else if (isset($argv[1]) && $argv[1] !== 'all' && $argv[1] !== 'safe' && $argv[1] !== 'alph' && $argv[1] !== 'date' && !is_numeric($argv[1]) && !isset($argv[2])) {
	$backfill = new Backfill();
	$backfill->backfillAllGroups($nntp, $argv[1]);
} else if (isset($argv[1]) && $argv[1] !== 'all' && $argv[1] !== 'safe' && $argv[1] !== 'alph' && $argv[1] !== 'date' && !is_numeric($argv[1]) && isset($argv[2]) && is_numeric($argv[2])) {
	$backfill = new Backfill();
	$backfill->backfillPostAllGroups($nntp, $argv[1], $argv[2], 'groupname');
} else if (isset($argv[1]) && $argv[1] !== 'all' && $argv[1] !== 'safe' && $argv[1] == 'alph' && $argv[1] !== 'date' && !is_numeric($argv[1]) && isset($argv[2]) && is_numeric($argv[2])) {
	$backfill = new Backfill();
	$groupName = '';
	$backfill->backfillPostAllGroups($nntp, $groupName, $argv[2], 'normal');
} else if (isset($argv[1]) && $argv[1] !== 'all' && $argv[1] !== 'safe' && $argv[1] !== 'alph' && $argv[1] == 'date' && !is_numeric($argv[1]) && isset($argv[2]) && is_numeric($argv[2])) {
	$backfill = new Backfill();
	$groupName = '';
	$backfill->backfillPostAllGroups($nntp, $groupName, $argv[2], 'date');
} else if (isset($argv[1]) && $argv[1] !== 'all' && $argv[1] == 'safe' && $argv[1] !== 'alph' && $argv[1] !== 'date' && !is_numeric($argv[1]) && isset($argv[2]) && is_numeric($argv[2])) {
	$backfill = new Backfill();
	$backfill->safeBackfill($nntp, $argv[2]);
} else {
	exit($c->error("\nWrong set of arguments.\n"
			. 'php backfill.php safe 200000		 ...: Backfill an active group alphabetically, x articles, the script stops,' . "\n"
			. '					 ...: if the group has reached reached 2012-06-24, the next group will backfill.' . "\n"
			. 'php backfill.php alph 200000 		 ...: Backfills all groups (sorted alphabetically) by number of articles' . "\n"
			. 'php backfill.php date 200000 		 ...: Backfills all groups (sorted by least backfilled in time) by number of articles' . "\n"
			. 'php backfill.php alt.binaries.ath 200000 ...: Backfills a group by name by number of articles' . "\n"
			. 'php backfill.php all			 ...: Backfills all groups 1 at a time, by date (set in admin-view groups)' . "\n"
			. 'php backfill.php alt.binaries.ath	 ...: Backfills a group by name, by date (set in admin-view groups)' . "\n"));
}
if ($site->nntpproxy != "1") {
	$nntp->doQuit();
}
