<?php

require("config.php");
require_once(WWW_DIR."/lib/releases.php");

if (isset($argv[1]))
{
	$releases = new Releases;
	if ($argv[1] == 1)
	{
		$releases->processReleases(1);
	}
	else if ($argv[1] == 3)
	{
		$releases->processReleases(3);
	}
	else
	{
		echo "Wrong argument, type php update_releases.php to see a list of valid arguments.\n";
		die;
	}
}
else
{
	echo "ERROR: You must supply an argument.\n"."php update_releases.php 1 ...: Categorizes releases using the stock nnplus category.php\n"."php update_releases.php 3 ...: Leaves all releases in other -> misc\n";
}

?>
