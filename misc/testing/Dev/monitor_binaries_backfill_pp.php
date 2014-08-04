<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();

if ($argc !== 3 || !is_numeric($argv[1]) || !is_numeric($argv[2])) {
	exit($pdo->log->error("\nThis script monirtors both the threaded and unthreaded update_binaries and backfill scripts.\n"
		. "This will also kill any medianinfo/ffmpeg process running longer than 60 seconds."
		. "The first argument is the time in minutes to allow before killing.\n"
		. "The second argument is the time in seconds to sleep between each check.\n"
		. "Compression will be enabled at the beginning of this script and will be (re-)enabled if disabled if\n"
		. "it appears to be running well. It will be disabled if any thread exceeds the time set with argument 1.\n\n"
		. "php $argv[0] 60 15    ....: To update every 15 seconds and kill any script running more than 60 minutes.\n"));
} else {
	// Set reset timer
	$time1 = TIME();
	$check = '';
	$killtime = $argv[1];
	$sleep = $argv[2];

	// make sure compressed headers are enabled
	$pdo->queryExec("UPDATE settings SET value = 1 WHERE setting = 'compressedheaders'");

	while (1 === 1) {
		//kill mediainfo and ffmpeg if exceeds 60 sec
		shell_exec("killall -o 60s -9 mediainfo 2>&1 1> /dev/null");
		shell_exec("killall -o 60s -9 ffmpeg 2>&1 1> /dev/null");

		$counted = $threads = 0;
		passthru('clear');
		$output = array();
		exec('ps --no-header -eo pid,user,etime,command | grep $USER | grep "update_groups\|update_binaries.php\|backfill_all\|backfill.php\|backfill_interval\|safe_pull" | grep -v monitor_binaries_backfill.php | grep -v grep', $output);
		if (isset($output[0]) && strlen($output[0]) > 8) {
			foreach ($output as $line) {
				preg_match('/(\d+):(\d+) /', $line, $time);
				$line1 = preg_split('#\s+#', trim($line));
				$threads++;
				if ($time[1] >= $killtime) {
					// Disable compressed headers
					$pdo->queryExec("UPDATE settings SET value = 0 WHERE setting = 'compressedheaders'");
					// kill pid
					echo $pdo->log->alternate("PID: $line1[0] USER: $line1[1] TIME: $time[0] CMD: $line");
					usleep(10000);
					exec("kill " . $line1[0] . " 2>&1 1> /dev/null");
					// reset good timer
					$time1 = TIME();
				} else {
					echo $pdo->log->primary("PID: $line1[0] USER: $line1[1] TIME: $time[0] CMD: $line");
				}
			}
		} else {
			echo $pdo->log->header("update_binaries or backfill does not appear to be running");
			$time1 = TIME();
		}

		echo $pdo->log->header("Monitoring ${threads} threads.");

		// re-enable compressed haders if good running 10 min
		if (TIME() - $time1 > ($killtime + 300)) {
			$pdo->queryExec("UPDATE settings SET value = 1 WHERE setting = 'compressedheaders'");
			$time1 = TIME();
		}

		passthru("php misc/update/nix/tmux/bin/showsleep.php $sleep");
	}
}
