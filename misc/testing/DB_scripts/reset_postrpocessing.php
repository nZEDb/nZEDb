<?php
require(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."/lib/framework/db.php");

$db = new DB();



if ((isset($argv[1]) && $argv[1] === "console") || (isset($argv[1]) && $argv[1] === "all"))
{
        $rels = $db->query(sprintf("update releases set consoleinfoID = 'NULL' where categoryID BETWEEN 1000 AND 1999"));
	$affected = $db->getAffectedRows();
	echo $affected." console releases reset.\n";
}
if ((isset($argv[1]) && $argv[1] === "movie") || (isset($argv[1]) && $argv[1] === "all"))
{
        $rels = $db->query(sprintf("update releases set imdbID = 'NULL' where categoryID BETWEEN 2000 AND 2999"));
        $affected = $db->getAffectedRows();
        echo $affected." movie releases reset.\n";
}
if ((isset($argv[1]) && $argv[1] === "music") || (isset($argv[1]) && $argv[1] === "all"))
{
        $rels = $db->query(sprintf("update releases set consoleinfoID = 'NULL' where categoryID BETWEEN 3000 AND 3999"));
        $affected = $db->getAffectedRows();
        echo $affected." music releases reset.\n";
}
if ((isset($argv[1]) && $argv[1] === "misc") || (isset($argv[1]) && $argv[1] === "all"))
{
        $rels = $db->query(sprintf("update releases set passwordstatus = -1, haspreview = -1"));
        $affected = $db->getAffectedRows();
        echo $affected." misc releases reset.\n";
}
if ((isset($argv[1]) && $argv[1] === "tv") || (isset($argv[1]) && $argv[1] === "all"))
{
        $rels = $db->query(sprintf("update releases set consoleinfoID = 'NULL' where categoryID BETWEEN 5000 AND 5999"));
        $affected = $db->getAffectedRows();
        echo $affected." tv releases reset.\n";
}
if ((isset($argv[1]) && $argv[1] === "book") || (isset($argv[1]) && $argv[1] === "all"))
{
        $rels = $db->query(sprintf("update releases set consoleinfoID = 'NULL' where categoryID BETWEEN 8000 AND 8999"));
        $affected = $db->getAffectedRows();
        echo $affected." book releases reset.\n";
}
if (!isset($argv[1]))
{
	echo "To reset console, run php reset_postrpocessing.php console\n";
        echo "To reset movie, run php reset_postrpocessing.php movie\n";
        echo "To reset music, run php reset_postrpocessing.php music\n";
        echo "To reset misc, run php reset_postrpocessing.php misc\n";
        echo "To reset tv, run php reset_postrpocessing.php tv\n";
        echo "To reset book, run php reset_postrpocessing.php book\n";
        echo "To reset everything, run php reset_postrpocessing.php all\n";
}
?>


