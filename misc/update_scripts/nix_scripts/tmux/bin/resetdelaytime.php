<?php

require_once realpath(dirname(__FILE__) . '/../../../../../www/config.php');
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'site.php';

$db = new DB();
$s = new Sites();
$site = $s->get();
$tablepergroup = (isset($site->tablepergroup)) ? $site->tablepergroup : 0;

//reset collections dateadded to now
print("Resetting expired collections and nzbs dateadded to now. This could take a minute or two. Really.\n");
if ($tablepergroup == 1)
{
	$sql = 'SHOW tables';
	$tables = $db->query($sql);
	$ran = 0;
	foreach($tables as $row)
	{
		$tbl = $row['tables_in_'.DB_NAME];
		if (preg_match('/\d+_collections/',$tbl))
		{
			$run = $db->queryExec('UPDATE '.$tbl.' SET dateadded = now()');
			$ran += $run->rowCount();
		}
	}
	echo $ran." collections reset\n";
}
else
{
	$run = $db->queryExec('update collections set dateadded = now()');
	echo $run->rowCount()." collections reset\n";
}

$run = $db->queryExec('update nzbs set dateadded = now()');
echo $run->rowCount()." nzbs reset\n";
sleep(2);
