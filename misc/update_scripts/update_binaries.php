<?php
require(dirname(__FILE__).'/config.php');
require_once(WWW_DIR.'lib/binaries.php');
require_once(WWW_DIR.'lib/groups.php');
require_once(WWW_DIR.'lib/nntp.php');
require_once(WWW_DIR.'lib/ColorCLI.php');
require_once(WWW_DIR.'lib/site.php');

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
