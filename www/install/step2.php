<?php
require_once('../lib/installpage.php');
require_once('../lib/install.php');

$page = new Installpage();
$page->title = "Database Setup";

$cfg = new Install();

if (!$cfg->isInitialized())
{
	header("Location: index.php");
	die();
}

function tablecheck($dbname, $dbtype, $pdo)
{
	$a = false;
	if ($dbtype == "mysql")
		$stmt = $pdo->prepare('SHOW DATABASES');
	else if ($dbtype == "pgsql")
		$stmt = $pdo->prepare('SELECT datname AS Database FROM pg_database');
	else
		return $a;
	$stmt->setFetchMode(PDO::FETCH_ASSOC);
	$stmt->execute();
	$tables = $stmt->fetchAll();
	$tablearr = array();
	foreach ($tables as $table)
	{
		$tablearr[] = $table;
	}
	foreach ($tablearr as $tab)
	{
		if($tab["Database"] == $dbname || $tab["database"] == $dbname)
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
	$cfg->DB_SYSTEM = trim($_POST['db_system']);

	$dbtype = $cfg->DB_SYSTEM;
	$charset = '';
	if (strtolower($dbtype) == 'mysql')
		$charset = ';charset=utf8';

	if (isset($cfg->DB_PORT))
		$pdos = $dbtype.':host='.$cfg->DB_HOST.';port='.$cfg->DB_PORT.$charset;
	else
		$pdos = $dbtype.':host='.$cfg->DB_HOST.$charset;

	$cfg->dbConnCheck = true;
	try {
		$pdo = new PDO($pdos, $cfg->DB_USER, $cfg->DB_PASSWORD);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	} catch (PDOException $e) {
		printf("Connection failed: (".$e->getMessage().")");
		$cfg->error = true;
		$cfg->dbConnCheck = false;
	}

	if (!$cfg->error && $dbtype == "mysql")
	{
		if (tablecheck($cfg->DB_NAME, $dbtype, $pdo) === false)
			$cfg->dbNameCheck = false;

		$charsql = '';
		if ($dbtype == "mysql")
			$charsql = " DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci";
		if ($cfg->dbNameCheck === false)
		{
			$pdo->query("CREATE DATABASE ".$cfg->DB_NAME.$charsql);
			if (tablecheck($cfg->DB_NAME, $dbtype, $pdo) === false)
			{
				$cfg->dbNameCheck = false;
				$cfg->error = true;
			}
			else
				$cfg->dbNameCheck = true;
		}
		else
		{
			try {
				$pdo->query("DROP DATABASE ".$cfg->DB_NAME);
			} catch (PDOException $e) {
				printf($e);
			}
			$pdo->query("CREATE DATABASE ".$cfg->DB_NAME.$charsql);
			if (tablecheck($cfg->DB_NAME, $dbtype, $pdo) === false)
			{
				$cfg->dbNameCheck = false;
				$cfg->error = true;
			}
			else
				$cfg->dbNameCheck = true;
		}
	}
	elseif (!$cfg->error && $dbtype == "pgsql")
	{
		$cfg->dbNameCheck = true;
		if (tablecheck($cfg->DB_NAME, $dbtype, $pdo) === false)
		{
			$cfg->pgNameCheck = false;
			$cfg->error = true;
		}
		else
			$cfg->pgNameCheck = true;
	}

	if (!$cfg->error)
	{
		$cfg->setSession();

		// Load schema.sql.
		if (file_exists($cfg->DB_DIR.'/schema.mysql') && file_exists($cfg->DB_DIR.'/schema.pgsql'))
		{
			if ($dbtype == "mysql")
				$dbData = file_get_contents($cfg->DB_DIR.'/schema.mysql');
			if ($dbtype == "pgsql")
				$dbData = file_get_contents($cfg->DB_DIR.'/schema.pgsql');
			// Fix to remove BOM in UTF8 files.
			$bom = pack("CCC", 0xef, 0xbb, 0xbf);
			if (0 == strncmp($dbData, $bom, 3))
				$dbData = substr($dbData, 3);

			// Select DB.
			if ($dbtype == "mysql")
				$pdo->query("USE ".$cfg->DB_NAME);

			$queries = explode(";", $dbData);
			$queries = array_map("trim", $queries);
			foreach($queries as $q)
			{
				if (strlen($q) > 0)
				{
					if (preg_match('/CREATE|DELETE|DROP|UPDATE/i', $q))
					{
						try {
							$pdo->exec($q);
						} catch (PDOException $err){
							printf("Error inserting: (".$err->getMessage().")");
							exit();
						}
					}
					else
					{
						try {
							$pdo->query($q);
						} catch (PDOException $err) {
							printf("Error inserting: (".$err->getMessage().")");
							exit();
						}
					}
				}
			}

			// Check one of the standard tables was created and has data.
			$dbInstallWorked = false;
			$reschk = $pdo->query("SELECT COUNT(*) AS num FROM category");
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
