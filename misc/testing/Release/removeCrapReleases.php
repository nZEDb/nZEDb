<?php

/* This script deletes releases that match certain criteria, type php removeCrapReleases.php false for details. */
require_once dirname(__FILE__) . '/../../../www/config.php';

$cli = new \ColorCLI();
$n = PHP_EOL;

$argCnt = count($argv);
if ($argCnt === 1) {
	exit(
		$cli->error(
			$n .
			'Run fixReleaseNames.php first to attempt to fix release names.'. $n .
			'This will miss some releases if you have not set fixReleaseNames to set the release as checked.' . $n . $n .
			"php $argv[0] false Display full usage of this script." . $n .
			"php $argv[0] true full Run this script with all options."
		)
	);
}
if ($argCnt === 2) {
	if ($argv[1] === 'false') {
		exit(
			"php $argv[0] arg1 arg2 arg3 arg4" . $n . $n .
			'arg1 (Required) = true/false' . $n .
			'                  true   = Run this script and delete releases.' . $n .
			'                  false  = Run this script and show what could be deleted.' . $n . $n .
			'arg2 (Required) = full/number' . $n .
			'                  full   = Run without a time limit.' . $n .
			'                  number = Run on releases up to this old.' . $n . $n .
			'arg3 (Optional) = blacklist | blfiles | executable | gibberish | hashed | installbin | passworded | passwordurl | sample | scr | short | size | wmv' . $n .
			'                  blacklist   = Remove releases using the enabled blacklists in admin section of site.' . $n .
			'                  blfiles     = Remove releases using the enabled blacklists in admin section of site against filenames.' . $n .
			'                  executable  = Remove releases containing an exe file.' . $n .
			'                  gibberish   = Remove releases where the name is letters/numbers only and 15 characters or longer.' . $n .
			'                  hashed      = Remove releases where the name is letters/numbers only and 25 characters or longer.' . $n .
			'                  installbin  = Remove releases which contain an install.bin file.' . $n .
			'                  passworded  = Remove releases which contain the word password in the title.' . $n .
			'                  passwordurl = Remove releases which contain a password.url file.' . $n .
			'                  sample      = Remove releases that are smaller than 40MB more than 1 file and have sample in the title' . $n .
			'                  scr         = Remove releases where .scr extension is found in the files or subject.' . $n .
			'                  short       = Remove releases where the name is only numbers or letters and is 5 characters or less.' . $n .
			'                  codec       = Remove releases where the release contains WMV file, x264 name, and Codec\Setup.exe file (Spammer).' . $n .
			'                  size        = Remove releases smaller than 2MB and have only 1 file and not in books or mp3 section.' . $n .
			'                  huge        = Remove releases bigger than 200MB with just a single file.' . $n . $n .
			'arg4 (Optional) = blacklist regular expression id number.  Only works when blacklist is selected as third argument.' . $n .
			'                  100001      = Remove releases where the Binary Blacklist ID is 100001.' . $n . $n .
			'examples:' . $n .
			"php $argv[0] true 12 blacklist     = Remove releases up to 12 hours old using site blacklists." . $n .
			"php $argv[0] false full            = Show what releases could have been removed." . $n .
			"php $argv[0] true full installbin  = Remove releases which containing an install.bin file." . $n .
			"php $argv[0] true full blacklist 1 = Remove releases matching blacklist id 1." . $n
		);
	} else {
		exit ($cli->error("Wrong usage! Type php $argv[0] false"));
	}
}
if ($argCnt < 3) {
	exit ($cli->error("Wrong usage! Type php $argv[0] false"));
}

if (isset($argv[3]) && $argv[3] === 'blacklist' && isset($argv[4])) {
	$blacklistID = $argv[4];
}

$RR = new \ReleaseRemover();
$RR->removeCrap(($argv[1] === 'true' ? true : false), $argv[2], (isset($argv[3]) ? $argv[3] : ''), (isset($blacklistID) ? $argv[4] : ''));
