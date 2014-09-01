<?php
/* Argument 1 is optional string, group name. Or numeric, number of header max to download.
 * Argument 2 is optional int, max number of headers to download.
 */

require_once dirname(__FILE__) . '/config.php';

$pdo = new \nzedb\db\Settings();

// Create the connection here and pass
$nntp = new \NNTP(['Settings' => $pdo]);
if ($nntp->doConnect() !== true) {
	exit($pdo->log->error("Unable to connect to usenet."));
}
$binaries = new \Binaries(['NNTP' => $nntp, 'Settings' => $pdo]);

if (isset($argv[1]) && !is_numeric($argv[1])) {
	$groupName = $argv[1];
	echo $pdo->log->header("Updating group: $groupName");

	$grp = new \Groups(['Settings' => $pdo]);
	$group = $grp->getByName($groupName);
	if (is_array($group)) {
		$binaries->updateGroup($group, (isset($argv[2]) && is_numeric($argv[2]) && $argv[2] > 0 ? $argv[2] : 0));
	}
} else {
	$binaries->updateAllGroups((isset($argv[1]) && is_numeric($argv[1]) && $argv[1] > 0 ? $argv[1] : 0));
}
