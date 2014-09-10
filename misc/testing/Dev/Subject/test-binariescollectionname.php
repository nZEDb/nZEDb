<?php
require_once dirname(__FILE__) . '/../../../../www/config.php';



if(!isset($argv[1]))
	exit("You must start the script like this : php test-backfillcleansubject.php true for all groups, replace true for the group name if you want to do 1 group.\n");
else
{
	if ($argv[1] == "true")
	{
		$groups = new \Groups();
		$grouplist = $groups->getActive();
		$nntp = new \NNTP(['Settings' => $groups->pdo]);
		$binaries = new \Binaries(['NNTP' => $nntp, 'Groups' => $groups, 'Settings' => $groups->pdo]);
		foreach ($grouplist as $group)
		{
			if ($nntp->doConnect() !== true) {
				exit();
			}
			dogroup($group, $binaries);
			$nntp->doQuit();
		}
	}
	else
	{
		$nntp = new \NNTP();
		$binaries = new \Binaries(['NNTP' => $nntp, 'Settings' => $nntp->pdo]);
		if ($nntp->doConnect() !== true) {
			exit();
		}
		dogroup($argv[1], $binaries);
		$nntp->doQuit();
	}
}

/**
 * @param array $group
 * @param Binaries $binaries
 *
 * @return bool
 */
function dogroup($group, $binaries)
{
	$binaries->updateGroup($group);
	echo "Press enter to continue, type n and press enter to quit.\n";
	$cmd = trim(fgets(fopen("php://stdin","r")));
	if($cmd == '')
		return true;
	else if ($cmd == "no")
		exit("Done.\n");
	else
		return true;
}

?>
