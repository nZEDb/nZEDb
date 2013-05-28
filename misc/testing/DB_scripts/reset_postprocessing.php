<?php
require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");

$db = new DB();


if (isset($argv[1]) && $argv[1] === "all")
{
	if (isset($argv[2]) && $argv[2] === "true")
	{
		$where = "";
		$db->query("truncate table consoleinfo");
		$db->query("truncate table movieinfo");
        $db->query("truncate table releasevideo");
		$db->query("truncate table musicinfo");
		$db->query("truncate table bookinfo");
		$db->query("truncate table releasenfo");
		$db->query("update releases set consoleinfoID = NULL, imdbID = NULL, musicinfoID = NULL, bookinfoID = NULL, rageID = NULL, passwordstatus = -1, haspreview = -1, nfostatus = -1");
		$affected = $db->getAffectedRows();
		echo $affected." releases reset.\n";
	}
	else
	{
		$db->query("update releases set consoleinfoID = NULL where consoleinfoID in (-2, 0)");
		$affected = $db->getAffectedRows();
		echo $affected." consoleinfoID's reset.\n";
		$db->query("update releases set imdbID = NULL where imdbID in (-2, 0)");
		$affected = $db->getAffectedRows();
		echo $affected." imdbID's reset.\n";
		$db->query("update releases set musicinfoID = NULL where musicinfoID in (-2, 0)");
		$affected = $db->getAffectedRows();
		echo $affected." musicinfoID's reset.\n";
		$db->query("update releases set rageID = NULL where rageID in (-2, 0)");
		$affected = $db->getAffectedRows();
		echo $affected." rageID's reset.\n";
		$db->query("update releases set bookinfoID = NULL where bookinfoID in (-2, 0)");
		$affected = $db->getAffectedRows();
		echo $affected." bookinfoID's reset.\n";
		$db->query("update releases set nfostatus = -1 where nfostatus <= 0");
		$affected = $db->getAffectedRows();
		echo $affected." nfos reset.\n";
		$db->query("update releases set passwordstatus = -1, haspreview = -1 where haspreview = 0");
		$affected = $db->getAffectedRows();
		echo $affected." releases reset.\n";
	}
}
elseif (isset($argv[1]) && $argv[1] === "consoles")
{
	if (isset($argv[2]) && $argv[2] === "true")
	{
		$where = "";
		$db->query("truncate table consoleinfo");
	}
	else
	{
		$where = " where consoleinfoID in (-2, 0) and categoryID BETWEEN 1000 AND 1999";
	}
	$db->query("update releases set consoleinfoID = NULL".$where);
	$affected = $db->getAffectedRows();
	echo $affected." consoleinfoID's reset.\n";
}
elseif (isset($argv[1]) && $argv[1] === "movies")
{
	if (isset($argv[2]) && $argv[2] === "true")
	{
		$where = "";
		$db->query("truncate table releasevideo");
		$db->query("truncate table movieinfo");
	}
	else
	{
		$where = " where imdbID in (-2, 0) and categoryID BETWEEN 2000 AND 2999";
	}
	$db->query("update releases set imdbID = NULL".$where);
	$affected = $db->getAffectedRows();
	echo $affected." imdbID's reset.\n";
}
elseif (isset($argv[1]) && $argv[1] === "music")
{
	if (isset($argv[2]) && $argv[2] === "true")
	{
		$where = "";
		$db->query("truncate table musicinfo");
	}
	else
	{
		$where = " where musicinfoID in (-2, 0) and categoryID BETWEEN 3000 AND 3999";
	}
	$db->query("update releases set musicinfoID = NULL".$where);
	$affected = $db->getAffectedRows();
	echo $affected." musicinfoID's reset.\n";
}
elseif ((isset($argv[1]) && $argv[1] === "misc") && (isset($argv[2]) && $argv[2] === "true"))
{
	if (isset($argv[2]) && $argv[2] === "true")
	{
		$where = "";
	}
	else
	{
		$where = " where haspreview = 0";
	}
	$db->query("update releases set passwordstatus = -1, haspreview = -1");
	$affected = $db->getAffectedRows();
	echo $affected." releases reset.\n";
}
elseif (isset($argv[1]) && $argv[1] === "tv")
{
	if (isset($argv[2]) && $argv[2] === "true")
	{
		$where = "";
	}
	else
	{
		$where = " where rageID in (-2, 0) and categoryID BETWEEN 5000 AND 5999";
	}
	$db->query("update releases set rageID = NULL".$where);
	$affected = $db->getAffectedRows();
	echo $affected." rageID's reset.\n";
}
elseif (isset($argv[1]) && $argv[1] === "books")
{
	if (isset($argv[2]) && $argv[2] === "true")
	{
		$where = "";
		$db->query("truncate table bookinfo");
	}
	else
	{
		$where = " where bookinfoID in (-2, 0) and categoryID BETWEEN 8000 AND 8999";
	}
	$db->query("update releases set bookinfoID = NULL".$where);
	$affected = $db->getAffectedRows();
	echo $affected." bookinfoID's reset.\n";
}
elseif (isset($argv[1]) && $argv[1] === "nfos")
{
	if (isset($argv[2]) && $argv[2] === "true")
	{
		$where = "";
		$db->query("truncate table releasenfo");
	}
	else
	{
		$where = " where nfostatus <= 0";
	}
	$db->query("update releases set nfostatus = -1".$where);
	$affected = $db->getAffectedRows();
	echo $affected." nfos reset.\n";
}
else
{
	echo "\033[1;33mTo reset consoles, run php reset_postrpocessing.php consoles true\n";
	echo "To reset movies, run php reset_postrpocessing.php movies true\n";
	echo "To reset music, run php reset_postrpocessing.php music true\n";
	echo "To reset misc, run php reset_postrpocessing.php misc true\n";
	echo "To reset tv, run php reset_postrpocessing.php tv true\n";
	echo "To reset books, run php reset_postrpocessing.php books true\n";
	echo "To reset nfos, run php reset_postrpocessing.php nfosook true\n";
	echo "To reset everything, run php reset_postrpocessing.php all true\n";
	echo "To reset only those without covers or previews use second argument false\033[m\n";
}
?>
