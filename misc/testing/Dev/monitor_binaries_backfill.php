<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

// Set reset timer
$time1 = TIME();
$db = new DB();
$c = new ColorCLI();
$check = '';

// make sure compressed headers are enabled
$db->queryExec("UPDATE site SET value = 1 WHERE setting = 'compressedheaders'");

while (1 === 1) {
	$counted = $threads = 0;
	passthru('clear');
	exec('ps --no-header -eo pid,user,etime,command | grep $USER | grep "update_binaries.php\|backfill_all\|backfill.php\|backfill_interval\|safe_pull" | grep -v grep', $output);
	if (isset($output[0]) && strlen($output[0]) > 8) {
		foreach ($output as $line) {
			preg_match('/(\d+):(\d+) /', $line, $time);
			$line1 = preg_split('#\s+#', trim($line));
			$threads++;
			if ($time[1] >= 60) {
				// Disable compressed headers
				$db->queryExec("UPDATE site SET value = 0 WHERE setting = 'compressedheaders'");
				// kill pid
				echo $c->alternate("PID: $line1[0] USER: $line1[1] TIME: $time[0] CMD: $line");
				usleep(10000);
				exec("kill " . $line1[0] . " 2>&1 1> /dev/null");
				// reset good timer
				$time1 = TIME();
			} else {
				echo $c->primary("PID: $line1[0] USER: $line1[1] TIME: $time[0] CMD: $line");
			}
		}
	} else {
		echo $c->header("update_binaries.php does not appear to be running");
		$time1 = TIME();
	}

	echo $c->header("Monitoring ${threads} threads.");

	// re-enable compressed haders if good running 10 min
	if (TIME() - $time1 > 600) {
		$db->queryExec("UPDATE site SET value = 1 WHERE setting = 'compressedheaders'");
		$time1 = TIME();
	}

	passthru('php misc/update/nix/tmux/bin/showsleep.php 15');
	$output = '';
}
