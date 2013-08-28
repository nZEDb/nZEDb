<?php
require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");
$db = new DB();

if (isset($argv[1]) && $argv[1] === "all")
{
	if (isset($argv[2]) && $argv[2] === "true")
	{
		$where = "";
		$db->query("TRUNCATE TABLE consoleinfo");
		$db->query("TRUNCATE TABLE movieinfo");
        $db->query("TRUNCATE TABLE releasevideo");
		$db->query("TRUNCATE TABLE musicinfo");
		$db->query("TRUNCATE TABLE bookinfo");
		$db->query("TRUNCATE TABLE releasenfo");
		$db->query("TRUNCATE TABLE releaseextrafull");
		$affected = $db->queryExec("UPDATE RELEASES SET consoleinfoid = NULL, imdbid = NULL, musicinfoid = NULL, bookinfoid = NULL, rageid = -1, passwordstatus = -1, haspreview = -1, jpgstatus = 0, videostatus = 0, audiostatus = 0, nfostatus = -1");
		echo $affected." releases reset.\n";
	}
	else
	{
		$affected = $db->queryExec("UPDATE RELEASES SET consoleinfoid = NULL WHERE consoleinfoid IN (-2, 0)");
		echo $affected." consoleinfoID's reset.\n";
		$affected = $db->queryExec("UPDATE RELEASES SET imdbid = NULL WHERE imdbid IN (-2, 0)");
		echo $affected." imdbID's reset.\n";
		$affected = $db->queryExec("UPDATE RELEASES SET musicinfoid = NULL WHERE musicinfoid IN (-2, 0)");
		echo $affected." musicinfoID's reset.\n";
		$affected = $db->queryExec("UPDATE RELEASES SET rageid = -1 WHERE rageid != 1 or rageid IS NULL");
		echo $affected." rageID's reset.\n";
		$affected = $db->queryExec("UPDATE RELEASES SET bookinfoid = NULL WHERE bookinfoid IN (-2, 0)");
		echo $affected." bookinfoID's reset.\n";
		$affected = $db->queryExec("UPDATE RELEASES SET nfostatus = -1 WHERE nfostatus != 1");
		echo $affected." nfos reset.\n";
		$affected = $db->queryExec("UPDATE RELEASES SET passwordstatus = -1, haspreview = -1, jpgstatus = 0, videostatus = 0, audiostatus = 0 WHERE haspreview = 0");
		echo $affected." releases reset.\n";
	}
}
elseif (isset($argv[1]) && $argv[1] === "consoles")
{
	$where = "";
	if (isset($argv[2]) && $argv[2] === "true")
		$db->query("TRUNCATE TABLE consoleinfo");
	else
		$where = " WHERE consoleinfoid IN (-2, 0) AND categoryid BETWEEN 1000 AND 1999";

	$affected = $db->queryExec("UPDATE RELEASES SET consoleinfoid = NULL".$where);
	echo $affected." consoleinfoID's reset.\n";
}
elseif (isset($argv[1]) && $argv[1] === "movies")
{
	if (isset($argv[2]) && $argv[2] === "true")
	{
		$where = "";
		$db->query("TRUNCATE TABLE releasevideo");
		$db->query("TRUNCATE TABLE movieinfo");
	}
	else
		$where = " WHERE imdbid IN (-2, 0) AND categoryid BETWEEN 2000 AND 2999";

	$affected = $db->queryExec("UPDATE RELEASES SET imdbid = NULL".$where);
	echo $affected." imdbID's reset.\n";
}
elseif (isset($argv[1]) && $argv[1] === "music")
{
	$where = "";
	if (isset($argv[2]) && $argv[2] === "true")
		$db->query("TRUNCATE TABLE musicinfo");
	else
		$where = " WHERE musicinfoid IN (-2, 0) AND categoryid BETWEEN 3000 AND 3999";

	$affected = $db->queryExec("UPDATE RELEASES SET musicinfoid = NULL".$where);
	echo $affected." musicinfoID's reset.\n";
}
elseif ((isset($argv[1]) && $argv[1] === "misc") && (isset($argv[2]) && $argv[2] === "true"))
{
	if (isset($argv[2]) && $argv[2] === "true")
		$where = "";
	else
		$where = " WHERE haspreview = 0";

	$affected = $db->queryExec("UPDATE RELEASES SET passwordstatus = -1, haspreview = -1, jpgstatus = 0, videostatus = 0, audiostatus = 0");
	echo $affected." releases reset.\n";
}
elseif (isset($argv[1]) && $argv[1] === "tv")
{
	if (isset($argv[2]) && $argv[2] === "true")
		$where = "";
	else
		$where = " WHERE rageid IN (-2, 0) OR rageid IS NULL AND categoryid BETWEEN 5000 AND 5999";

	$affected = $db->queryExec("UPDATE RELEASES SET rageid = -1".$where);
	echo $affected." rageID's reset.\n";
}
elseif (isset($argv[1]) && $argv[1] === "books")
{
	$where = "";
	if (isset($argv[2]) && $argv[2] === "true")
		$db->query("TRUNCATE TABLE bookinfo");
	else
		$where = " WHERE bookinfoid IN (-2, 0) AND categoryid BETWEEN 8000 AND 8999";

	$affected = $db->queryExec("UPDATE RELEASES SET bookinfoid = NULL".$where);
	echo $affected." bookinfoID's reset.\n";
}
elseif (isset($argv[1]) && $argv[1] === "nfos")
{
	$where = "";
	if (isset($argv[2]) && $argv[2] === "true")
		$db->query("TRUNCATE TABLE releasenfo");
	else
		$where = " WHERE nfostatus != 1";

	$affected = $db->queryExec("UPDATE RELEASES SET nfostatus = -1".$where);
	echo $affected." nfos reset.\n";
}
else
{
	exit("\033[1;33mTo reset consoles, run php reset_postrpocessing.php consoles true\n"
		."To reset movies, run php reset_postrpocessing.php movies true\n"
		."To reset music, run php reset_postrpocessing.php music true\n"
		."To reset misc, run php reset_postrpocessing.php misc true\n"
		."To reset tv, run php reset_postrpocessing.php tv true\n"
		."To reset books, run php reset_postrpocessing.php books true\n"
		."To reset nfos, run php reset_postrpocessing.php nfos true\n"
		."To reset everything, run php reset_postrpocessing.php all true\n"
		."To reset only those without covers or previews use second argument false\033[m\n");
}
?>
