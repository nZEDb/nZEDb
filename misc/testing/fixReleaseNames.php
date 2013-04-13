<?php

require("config.php");
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/categorizer.php");
$n = "\n";

	public function fixUsingName()
	{
		
	}

if (isset($argv[1]))
{
	if ($argv[1] == 1)
	{
		$this->fixUsingName();
	}
	else if ($argv[1] == 2)
	{
		echo "Placeholder".$n;
	}
	else
	{
		echo "Wrong argument, type php update_releases.php to see a list of valid arguments.".$n;
		die;
	}
}
else
{
	echo "ERROR: You must supply an argument.\n"."php fixReleaseNames.php 1 ...: Attempts to find a name from the name itself using strict rules.\n".
	"php update_releases.php 2 ...: Placeholder - Will fix release names from files or nfo later on.".$n;
}

?>
