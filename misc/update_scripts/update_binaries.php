<?php
require_once realpath(dirname(__FILE__) . '/config.php');
require_once nZEDb_LIB . 'binaries.php';
require_once nZEDb_LIB . 'groups.php';
require_once nZEDb_LIB . 'nntp.php';
require_once nZEDb_LIB . 'ColorCLI.php';
require_once nZEDb_LIB . 'site.php';

$binaries = new Binaries();
$c = new ColorCLI;
$site = new Sites();

// Create the connection here and pass
$nntp = new Nntp();
if ($nntp->doConnect() === false)
{
	echo $c->error("Unable to connect to usenet.\n");
	return;
}

if (isset($argv[1]))
{
	$groupName = $argv[1];
	echo "Updating group: $groupName\n";

	$grp = new Groups();
	$group = $grp->getByName($groupName);

	$binaries->updateGroup($group, $nntp);
}
else
	$binaries->updateAllGroups($nntp);
if ($site->get()->nntpproxy === false)
	$nntp->doQuit();
