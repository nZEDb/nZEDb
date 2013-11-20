<?php
// This script removes releases with no NZBs, resets all groups, truncates article tables. All other releases are left alone.
require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'site.php';
require_once nZEDb_LIB . 'ColorCLI.php';
$c = new ColorCLI;

if(isset($argv[1]) && ($argv[1] == "true" || $argv[1] == "drop"))
{
	$db = new DB();
	$db->queryExec("UPDATE groups SET first_record = 0, first_record_postdate = NULL, last_record = 0, last_record_postdate = NULL, last_updated = NULL");
	echo $c->primary("Reseting all groups completed.");

	$arr = array("parts", "partrepair", "binaries", "collections", "nzbs");
	foreach ($arr as &$value)
	{
		$rel = $db->queryExec("TRUNCATE TABLE $value");
		if($rel !== false)
			echo $c->primary("Truncating ${value} completed.");
	}
	unset($value);

	$s = new Sites();
	$site = $s->get();
	$tablepergroup = (!empty($site->tablepergroup)) ? $site->tablepergroup : 0;

	if ($tablepergroup == 1)
	{
		$sql = "SHOW table status";
		$tables = $db->query($sql);
		foreach($tables as $row)
		{
			$tbl = $row['name'];
			if (preg_match('/\d+_collections/',$tbl) || preg_match('/\d+_binaries/',$tbl) || preg_match('/\d+_parts/',$tbl))
			{
				if ($argv[1] == "drop")
				{
					$rel = $db->queryDirect(sprintf('DROP TABLE %s', $tbl));
					if($rel !== false)
						echo $c->primary("Dropping ${tbl} completed.");
				}
				else
				{
					$rel = $db->queryDirect(sprintf('TRUNCATE TABLE %s', $tbl));
					if($rel !== false)
						$c->primary("Truncating ${tbl} completed.");
				}
			}
		}
	}

	$delcount = $db->prepare("DELETE FROM releases WHERE nzbstatus = 0");
	$delcount->execute();
	echo $c->primary($delcount->rowCount()." releases had no nzb, deleted.");
}
else
	exit($c->error("This script removes releases with no NZBs, resets all groups, truncates article tables. All other releases are left alone.\nIf you are sure you want to run it, type php reset_truncate.php true\n"));
?>
