<?php

// TODO Set these somewhere else?
$minMySQLVersion = 5.5;
$minPgSQLVersion = 9.3;

require_once realpath(__DIR__ . '/../automated.config.php');

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
 * @param string $dbType Is it mysql or pgsql?
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
	$pdo = null;

	// Check if user selected right DB type.
	if (!in_array($cfg->DB_SYSTEM, array('mysql', 'pgsql'))) {
		$cfg->emessage = 'Invalid database system. Must be: mysql or pgsql ; Not: ' . $cfg->DB_SYSTEM;
		$cfg->error = true;
	} else {

		// Check if user connects using socket or host/port.
		if (isset($cfg->DB_SOCKET) && !empty($cfg->DB_SOCKET)) {
			$pdoString = $cfg->DB_SYSTEM . ':unix_socket=' . $cfg->DB_SOCKET;
		} else {
			$pdoString = $cfg->DB_SYSTEM . ':host=' . $cfg->DB_HOST . (isset($cfg->DB_PORT) ?';port=' . $cfg->DB_PORT : '');
		}

		// If MySQL add charset, if PgSQL add database name.
		$pdoString .= ($cfg->DB_SYSTEM === 'mysql' ? ';charset=utf8' : ';dbname=' . $cfg->DB_NAME);

		// Connect to the SQL server.
		try {
			$pdo = new PDO($pdoString, $cfg->DB_USER, $cfg->DB_PASSWORD);
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$cfg->dbConnCheck = true;
		} catch (PDOException $e) {
			$cfg->emessage = 'Unable to connect to the SQL server.';
			$cfg->error = true;
			$cfg->dbConnCheck = false;
		}

		// Check if the MySQL or PgSQL versions are right.
		$vQuery = ($cfg->DB_SYSTEM === 'mysql' ? "SHOW VARIABLES WHERE Variable_name = 'version'" : 'SELECT version()');
		$goodVersion = false;
		try {
			$version = $pdo->query($vQuery);
			if ($version === false) {
				$cfg->error = true;
				$cfg->emessage = 'Could not get version from SQL server.';
			} else {
				foreach ($version as $row) {
					if ($cfg->DB_SYSTEM === 'mysql' && isset($row['Value'])) {
						if (preg_match('/^(5\.\d)/', $row['Value'], $match)) {
							if ((float)$match[1] >= $minMySQLVersion) {
								$goodVersion = true;
								break;
							}
						}
					} elseif ($cfg->DB_SYSTEM === 'pgsql' && isset($row['version'])) {
						if (preg_match('/PostgreSQL (\d\.\d)/i', $row['version'], $match)) {
							if ((float)$match[1] >= $minPgSQLVersion) {
								$goodVersion = true;
								break;
							}
						}
					}
				}
			}
		} catch (PDOException $e) {
			$cfg->error = true;
			$cfg->emessage = 'Could not get version from SQL server.';
		}

		if ($goodVersion === false) {
			$cfg->error = true;
			$cfg->emessage =
				'You are using an unsupported version of ' .
				$cfg->DB_SYSTEM .
				' the minimum allowed version is ' .
				($cfg->DB_SYSTEM === 'mysql' ? $minMySQLVersion : $minPgSQLVersion);
		}
	}

	$cfg->dbNameCheck = true;
	// Check if the database exists for PgSQL.
	if (!$cfg->error && $cfg->DB_SYSTEM === "pgsql") {
		if (databaseCheck($cfg->DB_NAME, $cfg->DB_SYSTEM, $pdo) === false) {
			$cfg->dbNameCheck = false;
			$cfg->error = true;
			$cfg->emessage =
				'Could not find your database called : ' .
				$cfg->DB_NAME .
				', please see Install.txt for instructions to create a database.'
			;
		}
	}

	// Check if database exists for MySQL, drop it if so.
	if (!$cfg->error && $cfg->DB_SYSTEM === "mysql") {
		// Check if it exists.
		if (databaseCheck($cfg->DB_NAME, $cfg->DB_SYSTEM, $pdo) === false) {
			$cfg->dbNameCheck = false;
		}

		// It exists so drop it.
		if ($cfg->dbNameCheck === true) {
			try {
				$pdo->query("DROP DATABASE " . $cfg->DB_NAME);
				if (databaseCheck($cfg->DB_NAME, $cfg->DB_SYSTEM, $pdo) === true) {
					$cfg->error = true;
					$cfg->emessage = 'Could not drop your old database.';
				} else {
					$cfg->dbNameCheck = false;
				}
			} catch (PDOException $e) {
				$cfg->error = true;
				$cfg->emessage = 'Could not drop your old database.';
			}
		}

		// Try to create the database.
		if (!$cfg->error && $cfg->dbNameCheck === false) {
			try {
				$pdo->query("CREATE DATABASE " . $cfg->DB_NAME . " DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci");
				if (databaseCheck($cfg->DB_NAME, $cfg->DB_SYSTEM, $pdo) === false) {
					$cfg->error = true;
					$cfg->emessage = 'Could not create the new database.';
				} else {
					$cfg->dbNameCheck = true;
				}
			} catch (PDOException $e) {
				$cfg->error = true;
				$cfg->emessage = 'Could not create the new database.';
			}
		}
	}

	// Start inserting data into the DB.
	if (!$cfg->error) {
		$cfg->setSession();

		$DbSetup = new \nzedb\db\DbUpdate(
			array(
				'backup' => false
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
		} catch (PDOException $err) {
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

			// If it all worked, move to the next page.
			if ($dbInstallWorked) {
				header("Location: ?success");
				if (file_exists($cfg->DB_DIR . '/post_install.php')) {
					exec("php " . $cfg->DB_DIR . "/post_install.php ${pdo}");
				}
				exit();
			} else {
				$cfg->dbCreateCheck = false;
				$cfg->error = true;
				$cfg->emessage = 'Could not select data from your database.';
			}
		}
	}
}

$page->smarty->assign('cfg', $cfg);
$page->smarty->assign('page', $page);
$page->content = $page->smarty->fetch('step2.tpl');
$page->render();
