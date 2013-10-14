<?php
require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");
$db = new DB();

if (isset($argv[1]) && $argv[1] === "all")
{
	if (isset($argv[2]) && $argv[2] === "true")
	{
		$where = "";
		echo "Trancating tables\n";
		$db->queryExec("TRUNCATE TABLE consoleinfo");
		$db->queryExec("TRUNCATE TABLE movieinfo");
        $db->queryExec("TRUNCATE TABLE releasevideo");
		$db->queryExec("TRUNCATE TABLE musicinfo");
		$db->queryExec("TRUNCATE TABLE bookinfo");
		$db->queryExec("TRUNCATE TABLE releasenfo");
		$db->queryExec("TRUNCATE TABLE releaseextrafull");
		// never truncate animdb tabe as thats asking for a ban
		echo "Resetting all postprocessing\n";
		$affected = $db->queryExec("UPDATE releases SET consoleinfoid = NULL, anidbid = NULL, imdbid = NULL, musicinfoid = NULL, bookinfoid = NULL, rageid = -1, passwordstatus = -1, haspreview = -1, jpgstatus = 0, videostatus = 0, audiostatus = 0, nfostatus = -1");
		echo number_format($affected->rowCount())." releases reset.\n";
	}
	else
	{
		$affected = $db->queryExec("UPDATE releases SET consoleinfoid = NULL WHERE consoleinfoid IN (-2, 0)");
		echo number_format($affected->rowCount())." consoleinfoID's reset.\n";
		$affected = $db->queryExec("UPDATE releases SET imdbid = NULL WHERE imdbid IN (-2, 0)");
		echo number_format($affected->rowCount())." imdbID's reset.\n";
		$affected = $db->queryExec("UPDATE releases SET musicinfoid = NULL WHERE musicinfoid IN (-2, 0)");
		echo number_format($affected->rowCount())." musicinfoID's reset.\n";
		$affected = $db->queryExec("UPDATE releases SET rageid = -1 WHERE rageid != 1 or rageid IS NULL");
		echo number_format($affected->rowCount())." rageID's reset.\n";
		$affected = $db->queryExec("UPDATE releases SET bookinfoid = NULL WHERE bookinfoid IN (-2, 0)");
		echo number_format($affected->rowCount())." bookinfoID's reset.\n";
		$affected = $db->queryExec("UPDATE releases SET nfostatus = -1 WHERE nfostatus != 1");
		echo number_format($affected->rowCount())." nfos reset.\n";
		$affected = $db->queryExec("UPDATE releases SET anidbid = NULL WHERE anidbid IN (-2, 0)");
		echo number_format($affected->rowCount())." aniDBID's's reset.\n";
		$affected = $db->queryExec("UPDATE releases SET passwordstatus = -1, haspreview = -1, jpgstatus = 0, videostatus = 0, audiostatus = 0 WHERE haspreview = 0");
		echo number_format($affected->rowCount())." releases reset.\n";
	}
}
elseif (isset($argv[1]) && $argv[1] === "consoles")
{
	$where = "";
	if (isset($argv[2]) && $argv[2] === "true")
		$db->queryExec("TRUNCATE TABLE consoleinfo");
	else
		$where = " WHERE consoleinfoid IN (-2, 0) AND categoryid BETWEEN 1000 AND 1999";

	$affected = $db->queryExec("UPDATE releases SET consoleinfoid = NULL".$where);
	echo number_format($affected->rowCount())." consoleinfoID's reset.\n";
}
elseif (isset($argv[1]) && $argv[1] === "movies")
{
	if (isset($argv[2]) && $argv[2] === "true")
	{
		$where = "";
		$db->queryExec("TRUNCATE TABLE releasevideo");
		$db->queryExec("TRUNCATE TABLE movieinfo");
	}
	else
		$where = " WHERE imdbid IN (-2, 0) AND categoryid BETWEEN 2000 AND 2999";

	$affected = $db->queryExec("UPDATE releases SET imdbid = NULL".$where);
	echo number_format($affected->rowCount())." imdbID's reset.\n";
}
elseif (isset($argv[1]) && $argv[1] === "music")
{
	$where = "";
	if (isset($argv[2]) && $argv[2] === "true")
		$db->queryExec("TRUNCATE TABLE musicinfo");
	else
		$where = " WHERE musicinfoid IN (-2, 0) AND categoryid BETWEEN 3000 AND 3999";

	$affected = $db->queryExec("UPDATE releases SET musicinfoid = NULL".$where);
	echo number_format($affected->rowCount())." musicinfoID's reset.\n";
}
elseif ((isset($argv[1]) && $argv[1] === "misc") && (isset($argv[2]) && $argv[2] === "true"))
{
	if (isset($argv[2]) && $argv[2] === "true")
		$where = "";
	else
		$where = " WHERE haspreview = 0";

	$affected = $db->queryExec("UPDATE releases SET passwordstatus = -1, haspreview = -1, jpgstatus = 0, videostatus = 0, audiostatus = 0");
	echo number_format($affected->rowCount())." releases reset.\n";
}
elseif (isset($argv[1]) && $argv[1] === "tv")
{
	if (isset($argv[2]) && $argv[2] === "true")
		$where = "";
	else
		$where = " WHERE rageid IN (-2, 0) OR rageid IS NULL AND categoryid BETWEEN 5000 AND 5999";

	$affected = $db->queryExec("UPDATE releases SET rageid = -1".$where);
	echo number_format($affected->rowCount())." rageID's reset.\n";
}
elseif (isset($argv[1]) && $argv[1] === "books")
{
	$where = "";
	if (isset($argv[2]) && $argv[2] === "true")
		$db->queryExec("TRUNCATE TABLE bookinfo");
	else
		$where = " WHERE bookinfoid IN (-2, 0) AND categoryid BETWEEN 8000 AND 8999";

	$affected = $db->queryExec("UPDATE releases SET bookinfoid = NULL".$where);
	echo number_format($affected->rowCount())." bookinfoID's reset.\n";
}
elseif (isset($argv[1]) && $argv[1] === "anime")
{
	// never truncate the anidb table as that leads to bans

	// in the case of anime never clear AniTtile or the anidb table
	$where = " WHERE anidbid IN (-2, 0) AND categoryid = 5070";

	$affected = $db->queryExec("UPDATE releases SET anidbid = NULL" . $where);
	echo number_format($affected->rowCount())." anidbID's reset.\n";
}
elseif (isset($argv[1]) && $argv[1] === "xxx")
{
	// in the case of anime never clear XXX
	$where = " FROM releases r, category c WHERE c.id = r.categoryid AND c.parentid=6000 AND r.preid is NULL AND r.nfostatus != 1 AND rageid = -2";

	$affected = $db->queryExec("UPDATE releases SET r.rageid = -1 " . $where);
	echo number_format($affected->rowCount())." XXX's Releases reset.\n";
}
elseif (isset($argv[1]) && $argv[1] === "pc")
{
	// in the case of anime never clear PC
	$where = " FROM releases r, category c WHERE c.id = r.categoryid AND c.parentid=4000 AND r.preid is NULL AND r.nfostatus != 1 AND rageid = -2";

	$affected = $db->queryExec("UPDATE releases SET r.rageid = -1" . $where);
	echo number_format($affected->rowCount())." PC's Releases reset.\n";
}
elseif (isset($argv[1]) && $argv[1] === "nfos")
{
	$where = "";
	if (isset($argv[2]) && $argv[2] === "true")
		$db->queryExec("TRUNCATE TABLE releasenfo");
	else
		$where = " WHERE nfostatus != 1";

	$affected = $db->queryExec("UPDATE releases SET nfostatus = -1".$where);
	echo number_format($affected->rowCount())." nfos reset.\n";
}
else
{
	exit("\033[1;33mTo reset consoles, run php reset_postprocessing.php consoles true\n"
		."To reset movies, run php reset_postprocessing.php movies true\n"
		."To reset music, run php reset_postprocessing.php music true\n"
		."To reset misc, run php reset_postprocessing.php misc true\n"
		."To reset tv, run php reset_postprocessing.php tv true\n"
		."To reset books, run php reset_postprocessing.php books true\n"
		."To reset anime, run php reset_postprocessing.php anime\n"
		."To reset xxx, run php reset_postprocessing.php xxx\n"
		."To reset pc, run php reset_postprocessing.php pc\n"
		."To reset nfos, run php reset_postprocessing.php nfos true\n"
		."To reset everything, run php reset_postprocessing.php all true\n"
		."To reset only those without covers or previews use second argument false\033[m\n");
}
?>
