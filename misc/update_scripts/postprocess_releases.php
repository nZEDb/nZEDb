<?php

require("config.php");
require_once(WWW_DIR."/lib/postprocess.php");
require_once(WWW_DIR."/lib/framework/db.php");

$i=1;
while($i=1)

{
	$db = new DB;
	$ppquery = $db->queryOneRow("SELECT COUNT(*) as cnt from releases r left join category c on c.ID = r.categoryID where (r.passwordstatus = -1) or (r.haspreview = -1 and c.disablepreview = 0)");
	$ppleft = $ppquery['cnt'];
	$limit = "1";
	
	if ($limit < $ppleft) 
	{
		echo "$ppleft releases have to be post processed\n";
		$postprocess = new PostProcess(true);
		$postprocess->processAll();
	}
	else 
	{
		echo "No releases have to be post processed\n";
		sleep(45);
	}
}
