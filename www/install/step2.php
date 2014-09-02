<?php
require_once __DIR__ . '/../automated.config.php';

use nzedb\db\Settings;

$page = new InstallPage();
$page->title = "Database Setup";

$cfg = new Install();

if (!$cfg->isInitialized()) {
	header("Location: index.php");
	die();
}

/**
 * Check if the database exists.
 *
 * @param string $dbName The name of the database to be checked.
 * @param string $dbType mysql
 * @param PDO $pdo Class PDO instance.
 *
 * @return bool
 */
function databaseCheck($dbName, $dbType, $pdo)
{
	// Return value.
	$retVal = false;

	// Prepare queries.
	$stmt = ($dbType === "mysql" ? 'SHOW DATABASES' : 'SELECT datname AS Database FROM pg_database');
	$stmt = $pdo->prepare($stmt);
	$stmt->setFetchMode(PDO::FETCH_ASSOC);

	// Run the query.
	$stmt->execute();
	$tables = $stmt->fetchAll();

	// Store the query result as an array.
	$tablearr = array();
	foreach ($tables as $table) {
		$tablearr[] = $table;
	}

	// Loop over the query result.
	foreach ($tablearr as $tab) {

		// Check if the database is found.
		if (isset($tab["Database"])) {
			if ($tab["Database"] == $dbName) {
				$retVal = true;
				break;
			}
		}

		if (isset($tab["database"])) {
			if ($tab["database"] == $dbName) {
				$retVal = true;
				break;
			}
		}
	}
	return $retVal;
}

$cfg = $cfg->getSession();

if ($page->isPostBack()) {
	$cfg->doCheck = true;

	// Get the information the user typed into the website.
	$cfg->DB_HOST = trim($_POST['host']);
	$cfg->DB_PORT = trim($_POST['sql_port']);
	$cfg->DB_SOCKET = trim($_POST['sql_socket']);
	$cfg->DB_USER = trim($_POST['user']);
	$cfg->DB_PASSWORD = trim($_POST['pass']);
	$cfg->DB_NAME = trim($_POST['db']);
	$cfg->DB_SYSTEM = strtolower(trim($_POST['db_system']));
	$cfg->error = false;

	// Check if user selected right DB type.
	if (!in_array($cfg->DB_SYSTEM, array('mysql'))) {
		$cfg->emessage = 'Invalid database system. Must be: mysql ; Not: ' . $cfg->DB_SYSTEM;
		$cfg->error = true;
	} else {
		// Connect to the SQL server.
		try {
			$pdo = new Settings(
				array(
					'checkVersion' => true,
					'createDb'     => true,
					'dbhost'       => $cfg->DB_HOST,
					'dbname'       => $cfg->DB_NAME,
					'dbpass'       => $cfg->DB_PASSWORD,
					'dbport'       => $cfg->DB_PORT,
					'dbsock'       => $cfg->DB_SOCKET,
					'dbtype'       => $cfg->DB_SYSTEM,
					'dbuser'       => $cfg->DB_USER,
				)
			);
			$cfg->dbConnCheck = true;
		} catch (\PDOException $e) {
			$cfg->emessage = 'Unable to connect to the SQL server.';
			$cfg->error = true;
			$cfg->dbConnCheck = false;
		} catch (\RuntimeException $e) {
			switch ($e->getCode()) {
				case 1:
				case 2:
				case 3:
					$cfg->error    = true;
					$cfg->emessage = $e->getMessage();
					break;
				default:
					var_dump($e);
					throw new \RuntimeException($e->getMessage(), $e->getCode(), $e);
			}
		}

		// Check if the MySQL version is correct.
		$goodVersion = false;
		if (!$cfg->error) {
			try {
				$goodVersion = $pdo->isDbVersionAtLeast(nZEDb_MINIMUM_MYSQL_VERSION);
			} catch (\PDOException $e) {
				$goodVersion   = false;
				$cfg->error    = true;
				$cfg->emessage = 'Could not get version from SQL server.';
			}

			if ($goodVersion === false) {
				$cfg->error = true;
				$cfg->emessage =
					'You are using an unsupported version of ' .
					$cfg->DB_SYSTEM .
					' the minimum allowed version is ' .
					nZEDb_MINIMUM_MYSQL_VERSION;
			}
		}
	}

	// Start inserting data into the DB.
	if (!$cfg->error) {
		$cfg->setSession();

		$DbSetup = new \nzedb\db\DbUpdate(
			array(
				'backup' => false,
				'db'     => $pdo,
			)
		);

		try {
			$DbSetup->processSQLFile();	// Setup default schema
			$DbSetup->loadTables();		// Load default data files
			$DbSetup->processSQLFile(	// Process any custom stuff.
					array(
						 'filepath' =>	nZEDb_RES . 'db' . DS . 'schema' . DS . 'mysql-data.sql'
					)
			);
		} catch (\PDOException $err) {
			$cfg->error = true;
			$cfg->emessage = "Error inserting: (" . $err->getMessage() . ")";
		}

		if (!$cfg->error) {
			// Check one of the standard tables was created and has data.
			$dbInstallWorked = false;
			$reschk = $pdo->query("SELECT COUNT(*) AS num FROM countries");
			if ($reschk === false) {
				$cfg->dbCreateCheck = false;
				$cfg->error = true;
				$cfg->emessage = 'Could not select data from your database.';
			} else {
				foreach ($reschk as $row) {
					if ($row['num'] > 0) {
						$dbInstallWorked = true;
						break;
					}
				}
			}

			$ver = new \nzedb\utility\Versions();
			$patch = $ver->getSQLPatchFromFiles();
			$pdo->setSetting(['..sqlpatch' => $patch]);

			if ($dbInstallWorked) {
				$ver   = new \nzedb\utility\Versions();
				$patch = $ver->getSQLPatchFromFiles();
				if ($patch > 0) {
					$updateSettings = $pdo->setSetting(
										[
											'section'    => '',
											'subsection' => '',
											'name'       => 'sqlpatch',
											'value'      => $patch
										]);
				} else {
					$updateSettings = false;
				}
			}

			// If it all worked, move to the next page.
			if ($dbInstallWorked && $updateSettings) {
				header("Location: ?success");
				if (file_exists($cfg->DB_DIR . '/post_install.php')) {
					exec("php " . $cfg->DB_DIR . "/post_install.php ${pdo}");
				}
				exit();
			} else if (!$updateSettings) {
				$cfg->error    = true;
				$cfg->emessage = "Could not update sqlpatch to '$patch' for your database.";
			} else {
				$cfg->dbCreateCheck = false;
				$cfg->error         = true;
				$cfg->emessage      = 'Could not select data from your database.';
			}
		}
	}
}

$page->smarty->assign('cfg', $cfg);
$page->smarty->assign('page', $page);
$page->content = $page->smarty->fetch('step2.tpl');
$page->render();
