<?php

require_once(dirname(__FILE__)."/config.php");
require_once(WWW_DIR."/lib/framework/db.php");

function optimize()
{
	$db = new DB;
	echo "Optimizing MYSQL tables, this can take a while...\n";
	$tablecnt = $db->optimise();
	if ($tablecnt > 0)
		exit ("Optimized ".$tablecnt." MYSQL tables succesfuly.\n");
	else
		exit ("No MYSQL tables to optimize.\n");
}
optimize();

?>
