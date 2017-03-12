<?php
require_once realpath(__DIR__ . DIRECTORY_SEPARATOR . 'install.php');


use app\extensions\util\Versions;
use nzedb\db\DB;
use nzedb\Install;

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
	$tablearr = [];
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

	$validTypes = ['mysql'];
	// Check if user selected a valid DB type.
	if (!in_array($cfg->DB_SYSTEM, $validTypes)) {
		$cfg->emessage = 'Invalid database system. Must be one of: [' . implode(', ', $validTypes) .
			']; Not: ' . $cfg->DB_SYSTEM;
		$cfg->error = true;
	} else {
		// Connect to the SQL server.
		try {
			// HAS to be DB because settings table does not exist yet.
			$pdo = new DB(
				[
					'checkVersion' => true,
					'createDb'     => true,
					'dbhost'       => $cfg->DB_HOST,
					'dbname'       => $cfg->DB_NAME,
					'dbpass'       => $cfg->DB_PASSWORD,
					'dbport'       => $cfg->DB_PORT,
					'dbsock'       => $cfg->DB_SOCKET,
					'dbtype'       => $cfg->DB_SYSTEM,
					'dbuser'       => $cfg->DB_USER,
				]
			);
			$cfg->dbConnCheck = true;
		} catch (\PDOException $e) {
			$cfg->emessage = "Unable to connect to the SQL server.\n" . $e->getMessage();
			$cfg->error = true;
			$cfg->dbConnCheck = false;
		} catch (\RuntimeException $e) {
			switch ($e->getCode()) {
				case 1:
				case 2:
				case 3:
				case 4:
				case 5:
					$cfg->error    = true;
					$cfg->emessage = $e->getMessage();
					trigger_error($e->getMessage(), E_USER_WARNING);
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
				$goodVersion = $pdo->isVendorVersionValid();
			} catch (\PDOException $e) {
				$goodVersion   = false;
				$cfg->error    = true;
				$cfg->emessage = 'Could not get version from SQL server.';
			}

			if ($goodVersion === false) {
				$cfg->error = true;
				$vendor = $pdo->getVendor();
				switch (strtolower($vendor)) {
					case 'mariadb':
						$version = DB::MINIMUM_VERSION_MARIADB;
						break;
					default:
						$version = DB::MINIMUM_VERSION_MYSQL;
				}
				$cfg->emessage = 'You are using an unsupported version of the ' . $vendor .
					' server, the minimum allowed version is ' . $version;
			}
		}
	}

	// Start inserting data into the DB.
	if (!$cfg->error) {
		$cfg->setSession();

		$DbSetup = new \nzedb\db\DbUpdate(
			[
				'backup' => false,
				'db'     => $pdo,
			]
		);

		try {
			$DbSetup->processSQLFile(); // Setup default schema
			$DbSetup->processSQLFile( // Process any custom stuff.
				[
					'filepath' => nZEDb_RES . 'db' . DS . 'schema' . DS . 'mysql-data.sql'
				]
			);
			$DbSetup->loadTables(); // Load default data files
		} catch (\PDOException $err) {
			$cfg->error = true;
			$cfg->emessage = "Error inserting: (" . $err->getMessage() . ")";
		}

		if (!$cfg->error) {
			// Check one of the standard tables was created and has data.
			$dbInstallWorked = false;
			$reschk = $pdo->query("SELECT COUNT(id) AS num FROM tmux");
			if ($reschk === false) {
				$cfg->dbCreateCheck = false;
				$cfg->error = true;
				$cfg->emessage = 'Could not select data from your database, check that tables and data are properly created/inserted.';
			} else {
				foreach ($reschk as $row) {
					if ($row['num'] > 0) {
						$dbInstallWorked = true;
						break;
					}
				}
			}

			$ver = new Versions();
			$patch = $ver->getSQLPatchFromFile();

			if ($dbInstallWorked) {
				if ($patch > 0) {
					$updateSettings = $pdo->exec(
						"UPDATE settings SET value = '$patch' WHERE section = '' AND subsection = '' AND name = 'sqlpatch'"
					);
				} else {
					$updateSettings = false;
				}

				// If it all worked, move to the next page.
				if ($updateSettings) {
					header("Location: ?success");
					if (file_exists($cfg->DB_DIR . '/post_install.php')) {
						exec("php " . $cfg->DB_DIR . "/post_install.php ${pdo}");
					}
					exit();
				} else {
					$cfg->error    = true;
					$cfg->emessage = "Could not update sqlpatch to '$patch' for your database.";
				}
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
