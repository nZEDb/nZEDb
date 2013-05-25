<?php
require_once('../lib/installpage.php');
require_once('../lib/install.php');

$page = new Installpage();
$page->title = "Database Setup";

$cfg = new Install();

if (!$cfg->isInitialized()) {
	header("Location: index.php");
	die();
}

$cfg = $cfg->getSession();

if  ($page->isPostBack()) {
	$cfg->doCheck = true;
	$cfg->DB_HOST = trim($_POST['host']);
    $cfg->DB_PORT = trim($_POST['sql_port']);
	$cfg->DB_USER = trim($_POST['user']);
	$cfg->DB_PASSWORD = trim($_POST['pass']);
	$cfg->DB_NAME = trim($_POST['db']);
	
	$cfg->dbConnCheck = @mysql_connect($cfg->DB_HOST, $cfg->DB_USER, $cfg->DB_PASSWORD, $cfg->DB_PORT);
	if ($cfg->dbConnCheck === false) {
		$cfg->error = true;
	}
	$cfg->dbNameCheck = mysql_select_db($cfg->DB_NAME);

	if ($cfg->dbNameCheck === false) 
	{
		$result = @mysql_query("CREATE DATABASE ".$cfg->DB_NAME." DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci");	
		$cfg->dbNameCheck = mysql_select_db($cfg->DB_NAME);
		if ($cfg->dbNameCheck === false) 
		{
			$cfg->error = true;
		}
	}
	else
	{
		$result = @mysql_query("DROP DATABASE ".$cfg->DB_NAME);
        $result = @mysql_query("CREATE DATABASE ".$cfg->DB_NAME." DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci");
        $cfg->dbNameCheck = mysql_select_db($cfg->DB_NAME);
        if ($cfg->dbNameCheck === false)
        {
            $cfg->error = true;
        }
	}
	if (!$cfg->error) {
		$cfg->setSession();
	
		//Load schema.sql
		if (file_exists($cfg->DB_DIR.'/schema.sql')) {
			$dbData = file_get_contents($cfg->DB_DIR.'/schema.sql');
			//fix to remove BOM in UTF8 files
			$bom = pack("CCC", 0xef, 0xbb, 0xbf);
			if (0 == strncmp($dbData, $bom, 3)) {
				$dbData = substr($dbData, 3);
			}
			$queries = explode(";", $dbData);
			$queries = array_map("trim", $queries);
			foreach($queries as $q) {
				mysql_query($q);
			}
			
			//check one of the standard tables was created and has data
			$dbInstallWorked = false;
			$reschk = @mysql_query("select count(*) as num from category");	
			if ($reschk === false)
			{
				$cfg->dbCreateCheck = false;
				$cfg->error = true;
			}
			else
			{
				while ($row = mysql_fetch_assoc($reschk)) 
				{
					if ($row['num'] > 0)
					{
						$dbInstallWorked = true;
						break;
					}
				}
			}
			
			if ($dbInstallWorked)
			{
				header("Location: ?success");
				die();
			}
			else
			{
				$cfg->dbCreateCheck = false;
				$cfg->error = true;
			}
		}
	}
}

$page->smarty->assign('cfg', $cfg);

$page->smarty->assign('page', $page);

$page->content = $page->smarty->fetch('step2.tpl');
$page->render();

?>
