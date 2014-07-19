<?php
require_once dirname(__FILE__) . '/config.php';

$pdo = new \nzedb\db\Settings();
$c = new ColorCLI();

if (isset($argv[2]) && $argv[2] === 'true') {
	// Create the connection here and pass
	$nntp = new NNTP();
	if ($nntp->doConnect() !== true) {
		exit($c->error("Unable to connect to usenet."));
	}
	if ($pdo->getSetting('nntpproxy') === "1") {
		usleep(500000);
	}
}
if ($pdo->getSetting('tablepergroup') === 1) {
	exit($c->error("You are using 'tablepergroup', you must use releases_threaded.py"));
}

$groupName = isset($argv[3]) ? $argv[3] : '';
if (isset($argv[1]) && isset($argv[2])) {
	$consoletools = new ConsoleTools();
	$releases = new ProcessReleases(true, array('Settings' => $pdo, 'ColorCLI' => $c, 'ConsoleTools' => $consoletools));
	if ($argv[1] == 1 && $argv[2] == 'true') {
		$releases->processReleases(1, 1, $groupName, $nntp, true);
	} else if ($argv[1] == 1 && $argv[2] == 'false') {
		$releases->processReleases(1, 2, $groupName, $nntp, true);
	} else if ($argv[1] == 2 && $argv[2] == 'true') {
		$releases->processReleases(2, 1, $groupName, $nntp, true);
	} else if ($argv[1] == 2 && $argv[2] == 'false') {
		$releases->processReleases(2, 2, $groupName, $nntp, true);
	} else if ($argv[1] == 4 && ($argv[2] == 'true' || $argv[2] == 'false')) {
		echo $c->header("Moving all releases to other -> misc, this can take a while, be patient.");
		$releases->resetCategorize();
	} else if ($argv[1] == 5 && ($argv[2] == 'true' || $argv[2] == 'false')) {
		echo $c->header("Categorizing all non-categorized releases in other->misc using usenet subject. This can take a while, be patient.");
		$timestart = TIME();
		$relcount = $releases->categorizeRelease('name', 'WHERE iscategorized = 0 AND categoryID = 7010', true);
		$time = $consoletools->convertTime(TIME() - $timestart);
		echo $c->primary("\n" . 'Finished categorizing ' . $relcount . ' releases in ' . $time . " seconds, using the usenet subject.");
	} else if ($argv[1] == 6 && $argv[2] == 'true') {
		echo $c->header("Categorizing releases in all sections using the searchname. This can take a while, be patient.");
		$timestart = TIME();
		$relcount = $releases->categorizeRelease('searchname', '', true);
		$consoletools = new ConsoleTools();
		$time = $consoletools->convertTime(TIME() - $timestart);
		echo $c->primary("\n" . 'Finished categorizing ' . $relcount . ' releases in ' . $time . " seconds, using the search name.");
	} else if ($argv[1] == 6 && $argv[2] == 'false') {
		echo $c->header("Categorizing releases in misc sections using the searchname. This can take a while, be patient.");
		$timestart = TIME();
		$relcount = $releases->categorizeRelease('searchname', 'WHERE categoryID IN (1090, 2020, 3050, 5050, 6050, 7010)', true);
		$consoletools = new ConsoleTools();
		$time = $consoletools->convertTime(TIME() - $timestart);
		echo $c->primary("\n" . 'Finished categorizing ' . $relcount . ' releases in ' . $time . " seconds, using the search name.");
	} else {
		exit($c->error("Wrong argument, type php update_releases.php to see a list of valid arguments."));
	}
} else {
	exit($c->error("\nWrong set of arguments.\n"
			. "php update_releases.php 1 true			...: Creates releases and attempts to categorize new releases\n"
			. "php update_releases.php 2 true			...: Creates releases and leaves new releases in other -> misc\n"
			. "\nYou must pass a second argument whether to post process or not, true or false\n"
			. "You can pass a third optional argument, a group name (ex.: alt.binaries.multimedia).\n"
			. "\nExtra commands::\n"
			. "php update_releases.php 4 true			...: Puts all releases in other-> misc (also resets to look like they have never been categorized)\n"
			. "php update_releases.php 5 true			...: Categorizes all releases in other-> misc (which have not been categorized already)\n"
			. "php update_releases.php 6 false			...: Categorizes releases in misc sections using the search name\n"
			. "php update_releases.php 6 true			...: Categorizes releases in all sections using the search name\n"));
}
if ($pdo->getSetting('nntpproxy') != "1" && $argv[2] === 'true') {
	$nntp->doQuit();
}
