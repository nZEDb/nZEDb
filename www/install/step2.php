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

function tablecheck($dbname, $pdo)
{
	$stmt = $pdo->prepare('SHOW DATABASES');
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$stmt->execute();
	$tables = $stmt->fetchAll();
	$a = false;
	$tablearr = array();
	foreach ($tables as $table)
	{
		$tablearr[] = $table;
	}
	foreach ($tablearr as $tab)
	{
		if($tab["Database"] == $dbname)
		{
			$a = true;
			break;
		}
	}
	return $a;
}

$cfg = $cfg->getSession();

if  ($page->isPostBack())
{
	$cfg->doCheck = true;
	$cfg->DB_HOST = trim($_POST['host']);
	$cfg->DB_PORT = trim($_POST['sql_port']);
	$cfg->DB_SOCKET = trim($_POST['sql_socket']);
	$cfg->DB_USER = trim($_POST['user']);
	$cfg->DB_PASSWORD = trim($_POST['pass']);
	$cfg->DB_NAME = trim($_POST['db']);

	// todo: set db type in config.
	$dbtype = 'mysql';
	$charset = '';
	if (strtolower($dbtype) == 'mysql')
		$charset = ';charset=utf8';

	if (isset($cfg->DB_PORT))
		$pdos = $dbtype.':host='.$cfg->DB_HOST.';port='.$cfg->DB_PORT.$charset;
	else
		$pdos = $dbtype.':host='.$cfg->DB_HOST.$charset;

	$cfg->dbConnCheck = true;
	try
	{
		$pdo = new PDO($pdos, $cfg->DB_USER, $cfg->DB_PASSWORD);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	catch (PDOException $e)
	{
		printf("Connection failed: (".$e->getMessage().")");
		$cfg->error = true;
		$cfg->dbConnCheck = false;
	}

	if (!$cfg->error)
	{
		if (tablecheck($cfg->DB_NAME, $pdo) === false)
			$cfg->dbNameCheck = false;

		if ($cfg->dbNameCheck === false)
		{
			$pdo->query("CREATE DATABASE ".$cfg->DB_NAME." DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci");
			if (tablecheck($cfg->DB_NAME, $pdo) === false)
			{
				$cfg->dbNameCheck = false;
				$cfg->error = true;
			}
			else
				$cfg->dbNameCheck = true;
		}
		else
		{
			$pdo->query("DROP DATABASE ".$cfg->DB_NAME);
			$pdo->query("CREATE DATABASE ".$cfg->DB_NAME." DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci");
			if (tablecheck($cfg->DB_NAME, $pdo) === false)
			{
				$cfg->dbNameCheck = false;
				$cfg->error = true;
			}
			else
				$cfg->dbNameCheck = true;
		}
	}
	if (!$cfg->error)
	{
		$cfg->setSession();

		//Load schema.sql
		if (file_exists($cfg->DB_DIR.'/schema.sql'))
		{
			$dbData = file_get_contents($cfg->DB_DIR.'/schema.sql');
			//fix to remove BOM in UTF8 files
			$bom = pack("CCC", 0xef, 0xbb, 0xbf);
			if (0 == strncmp($dbData, $bom, 3))
				$dbData = substr($dbData, 3);

			// Select DB.
			$pdo->query("USE ".$cfg->DB_NAME);

			$queries = explode(";", $dbData);
			$queries = array_map("trim", $queries);
			foreach($queries as $q)
			{
				if (preg_match('/DELETE|DROP|UPDATE/i', $q))
				{
					try
					{
						$pdo->exec($q);
					}
					catch (PDOException $err)
					{
						printf("Error inserting: (".$err->getMessage().")");
					}
				}
				else
				{
					try
					{
						$pdo->query($q);
					}
					catch (PDOException $err)
					{
						printf("Error inserting: (".$err->getMessage().")");
					}
				}
			}

			//check one of the standard tables was created and has data
			$dbInstallWorked = false;
			$reschk = $pdo->query("select count(*) as num from category");
			if ($reschk === false)
			{
				$cfg->dbCreateCheck = false;
				$cfg->error = true;
			}
			else
			{
				foreach ($reschk as $row)
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
