<?php
// This script removes releases with no NZBs, resets all groups, truncates article tables. All other releases are left alone.
require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/site.php");

if(isset($argv[1]) && $argv[1] == "true")
{
	$db = new DB();
	$db->queryExec("UPDATE groups SET first_record = 0, first_record_postdate = NULL, last_record = 0, last_record_postdate = NULL, last_updated = NULL");
	printf("Reseting all groups completed.\n");

	$arr = array("parts", "partrepair", "binaries", "collections", "nzbs");
	foreach ($arr as &$value)
	{
		$rel = $db->queryExec("TRUNCATE TABLE $value");
		if($rel !== false)
			printf("Truncating $value completed.\n");
	}
	unset($value);

	$delcount = $db->prepare("DELETE FROM releases WHERE nzbstatus = 0");
	$delcount->execute();
	echo $delcount->rowCount()." releases had no nzb, deleted.\n";


	$s = new Sites();
    $site = $s->get();
	$tablepergroup = (!empty($site->tablepergroup)) ? $site->tablepergroup : 0;

	if ($tablepergroup == 1)
	{
		$sql = "SHOW tables";
		$tables = $db->query($sql);
		foreach($tables as $row)
		{
			$tbl = $row['tables_in_'.DB_NAME];
			if (preg_match('/\d+_collections/',$tbl) || preg_match('/\d+_binaries/',$tbl) || preg_match('/\d+_parts/',$tbl))
			{
				$db->queryDirect(sprintf('DROP TABLE %s', $tbl));
				printf("DROP TABLE %s;\n", $tbl);
			}
		}
	}
}
else
	exit("This script removes releases with no NZBs, resets all groups, truncates article tables. All other releases are left alone.\nIf you are sure you want to run it, type php reset_truncate.php true\n");
?>
