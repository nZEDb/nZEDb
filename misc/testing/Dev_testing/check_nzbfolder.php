<?php
require_once(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/site.php");
require_once(WWW_DIR."lib/consoletools.php");

$site = new Sites();
$db = new DB();

$consoleTools = new ConsoleTools();
$iFilesCounted = 0;
$notexist = 0;

$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($site->get()->nzbpath));
foreach($objects as $nzbFile)
{
    if($nzbFile->getExtension() != "gz")
        continue;
	$releaseGUID = str_replace(".nzb.gz", "", $nzbFile->getFilename());
	$consoleTools->overWrite("Checked: ".$iFilesCounted++." ok, ".$notexist." nzbs not in db  ");
	$rel = $db->queryOneRow(sprintf("SELECT ID from releases where guid = %s", $db->escapeString($releaseGUID)));
	if (!$rel)
	{
		$consoleTools = new ConsoleTools();
		$consoleTools->overWrite("Checked: ".$iFilesCounted." ok, ".$notexist++." nzbs not in db  ");
		echo $releaseGUID." - Does not exist in the database\n";
	}
}
