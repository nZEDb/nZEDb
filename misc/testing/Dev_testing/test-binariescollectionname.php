<?php
require("../../../www/config.php");
require_once(WWW_DIR."lib/binaries.php");
require_once(WWW_DIR."lib/groups.php");
require_once(WWW_DIR."lib/nntp.php");

if(!isset($argv[1]))
	exit("You must start the script like this : php test-backfillcleansubject.php true for all groups, replace true for the group name if you want to do 1 group.\n");
else
{
	if ($argv[1] == "true")
	{
		$groups = new Groups();
		$grouplist = $groups->getActive();
		$nntp = new Nntp();
		foreach ($grouplist as $group)
		{
			$nntp->doConnect();
			dogroup($group, $nntp);
			$nntp->doQuit();
		}
	}
	else
	{
		$nntp = new Nntp();
		$nntp->doConnect();
		dogroup($argv[1], $nntp);
		$nntp->doQuit();
	}
}

function dogroup($group, $nntp)
{
	$binaries = new binaries();
	$binaries->updateGroup($nntp, $group);
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
