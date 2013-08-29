<?php
require(dirname(__FILE__)."/config.php");
require_once(WWW_DIR."/lib/binaries.php");
require_once(WWW_DIR."/lib/groups.php");

$binaries = new Binaries();

if (isset($argv[1]))
{
	$groupName = $argv[1];
	echo "Updating group: $groupName\n";

	$grp = new Groups();
	$group = $grp->getByName($groupName);

	$binaries->updateGroup($group);
}
else
	$binaries->updateAllGroups();

?>
