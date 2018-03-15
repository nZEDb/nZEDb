<?php
/* Argument 1 is optional string, group name. Or numeric, number of header max to download.
 * Argument 2 is optional int, max number of headers to download.
 */
require_once realpath(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'bootstrap.php');

use app\models\Settings;
use nzedb\Binaries;
use nzedb\Groups;
use nzedb\NNTP;
use nzedb\db\DB;

$pdo = new DB();

$maxHeaders = Settings::value('max.headers.iteration') ?: 1000000;

// Create the connection here and pass
$nntp = new NNTP(['Settings' => $pdo]);
if ($nntp->doConnect() !== true) {
	exit($pdo->log->error("Unable to connect to usenet."));
}
$binaries = new Binaries(['NNTP' => $nntp, 'Settings' => $pdo]);

if (isset($argv[1]) && !is_numeric($argv[1])) {
	$groupName = $argv[1];
	echo $pdo->log->header("Updating group: $groupName");

	$grp = new Groups(['Settings' => $pdo]);
	$group = $grp->getByName($groupName);
	if (is_array($group)) {
		$headerCount = isset($argv[2]) && is_numeric($argv[2]) && $argv[2] > 0 ? $argv[2] : $maxHeaders;
		$binaries->updateGroup($group, $headerCount);
	}
} else {
	$headerCount = isset($argv[1]) && is_numeric($argv[1]) && $argv[1] > 0 ? $argv[1] : $maxHeaders;
	$binaries->updateAllGroups($headerCount);
}
?>
