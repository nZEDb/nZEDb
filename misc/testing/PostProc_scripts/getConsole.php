<?php
//This script will update all records in the consoleinfo table

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../../www/lib/console.php");
require_once(FS_ROOT."/../../../www/lib/category.php");

$console = new Console(true);

$db = new Db;

$res = $db->queryDirect(sprintf("SELECT searchname, ID from releases where consoleinfoID IS NULL and categoryID in ( select ID from category where parentID = %d ) ORDER BY id DESC", Category::CAT_PARENT_GAME));
if ($db->getNumRows($res) > 0) {

	while ($arr = $db->fetchAssoc($res)) 
	{				
		$gameInfo = $console->parseTitle($arr['searchname']);
		if ($gameInfo !== false) {
			echo 'Searching '.$gameInfo['release'].'<br />';
			$game = $console->updateConsoleInfo($gameInfo);
			if ($game !== false) {
				echo "<pre>";
				print_r($game);
				echo "</pre>";
			} else {
				echo '<br />Game not found<br /><br />';
			}
		}
	}
}

?>
