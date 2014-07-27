<?php
/* This is for making predb patches. The size is not accurate if it was not in MB, some sites post also in GB. */
/*
require_once '../../../www/config.php';

use nzedb\db\Settings;
$pdo = new Settings();

// Last patch date +1 day.
$lpdpo = "2013-05-30";
// Last patch number +1.
$lppo = "143";
// Wanted patch: round(( select count(*) from predb where adddate > lpdpo )/10000).
$wp = "144";
if (!file_exists(nZEDb_WWW."/a"))
	mkdir(nZEDb_WWW."/a", 0755, true);

if ($pdo->dbSystem() === "mysql")
	$uta = "UNIX_TIMESTAMP(adddate)";
else if ($pdo->dbSystem() === "pgsql")
	$uta = "extract(epoch FROM adddate)";

foreach (range($lppo, $wp) as $number)
{
	$loop = $number*10000+1;
	$number2 = str_pad($number, 3, '0', STR_PAD_LEFT);
	if ($number == 1)
		$pdo->query("select {$uta},title,category,replace(size,'[a-zA-Z]',''),predate from predb where adddate > '".$lpdpo."' limit 1,10000 INTO OUTFILE 'data001.txt' FIELDS TERMINATED BY ',' ENCLOSED BY '~' LINES TERMINATED BY '\n'");
	else
		$pdo->query("select {$uta},title,category,replace(size,'[a-zA-Z]',''),predate from predb where adddate > '".$lpdpo."' limit ${loop}, 10000 INTO OUTFILE 'data${number2}.txt' FIELDS TERMINATED BY ',' ENCLOSED BY '~' LINES TERMINATED BY '\n'");

	$fp = gzopen (nZEDb_WWW."/a/data".$number2.".gz", 'w9');
	gzwrite ($fp, file_get_contents("/var/lib/mysql/nzedb/data".$number2.'.txt'));
	gzclose($fp);
	chmod(nZEDb_WWW."/a/data".$number2.".gz", 0777);
	unlink("/var/lib/mysql/nzedb/data".$number2.'.txt');
}
*/
