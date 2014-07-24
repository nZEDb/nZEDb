<?php
//This inserts the patches into MySQL and PostgreSQL.
require_once dirname(__FILE__) . '/../../../www/config.php';

$log = new ColorCLI();

echo $log->warning("This file is deprecated and will be removed in a future version.\nUse 'php cli/update_db.php 1' instead");

if (\nzedb\utility\Utility::hasCommand("php5")) {
	$PHP = "php5";
} else {
	$PHP = "php";
}

$safe = (isset($argv[1]) && $argv[1] === "safe") ? true : false;
system("$PHP " . nZEDb_ROOT . 'cli' . DS . "update_db.php true $safe");

exit();
//// Function inspired by : http://stackoverflow.com/questions/1883079/best-practice-import-mysql-file-in-php-split-queries/2011454#2011454
//function SplitSQL($file, $delimiter = ';')
//{
//	set_time_limit(0);
//
//	if (is_file($file) === true) {
//		$file = fopen($file, 'r');
//
//		if (is_resource($file) === true) {
//			$query = array();
//			$pdo = new Settings();
//			$dbsys = $pdo->dbSystem();
//			$c = new ColorCLI();
//
//			while (feof($file) === false) {
//				$query[] = fgets($file);
//
//				if (preg_match('~' . preg_quote($delimiter, '~') . '\s*$~iS', end($query)) === 1) {
//					$query = trim(implode('', $query));
//
//					if ($dbsys == "pgsql") {
//						$query = str_replace(array("`", chr(96)), '', $query);
//					}
//					try {
//						$qry = $pdo->prepare($query);
//						$qry->execute();
//						echo $c->alternateOver('SUCCESS: ') . $c->primary($query);
//					} catch (PDOException $e) {
//
//						// Log the problem.
//						file_put_contents(
//							nZEDb_LOGS . 'patcherrors.log',
//							'[' . date('r') . '] [ERROR] [' . trim(preg_replace('/\s+/', ' ', $e->getMessage())) . ']' . PHP_EOL,
//							FILE_APPEND
//						);
//
//						// And the query..
//						file_put_contents(
//							nZEDb_LOGS . 'patcherrors.log',
//							'[' . date('r') . '] [QUERY] [' . trim(preg_replace('/\s+/', ' ', $query)) . ']' . PHP_EOL,
//							FILE_APPEND
//						);
//
//						if ($e->errorInfo[1] == 1091 || $e->errorInfo[1] == 1060 || $e->errorInfo[1] == 1054 || $e->errorInfo[1] == 1061 || $e->errorInfo[1] == 1062 || $e->errorInfo[1] == 1071 || $e->errorInfo[1] == 1072 || $e->errorInfo[1] == 1146 || $e->errorInfo[0] == 23505 || $e->errorInfo[0] == 42701 || $e->errorInfo[0] == 42703 || $e->errorInfo[0] == '42P07' || $e->errorInfo[0] == '42P16') {
//							if ($e->errorInfo[1] == 1060) {
//								echo $c->error($query . " The column already exists - Not Fatal {" . $e->errorInfo[1] . "}.\n");
//							} else {
//								echo $c->error($query . " Skipped - Not Fatal {" . $e->errorInfo[1] . "}.\n");
//							}
//						} else {
//							if (preg_match('/ALTER IGNORE/i', $query)) {
//								$pdo->queryExec("SET SESSION old_alter_table = 1");
//								try {
//									$qry = $pdo->prepare($query);
//									$qry->execute();
//									echo $c->alternateOver('SUCCESS: ') . $c->primary($query);
//								} catch (PDOException $e) {
//									exit($c->error($query . " Failed {" . $e->errorInfo[1] . "}\n\t" . $e->errorInfo[2]));
//								}
//							} else {
//								exit($c->error($query . " Failed {" . $e->errorInfo[1] . "}\n\t" . $e->errorInfo[2]));
//							}
//						}
//					}
//
//					while (ob_get_level() > 0) {
//						ob_end_flush();
//					}
//					flush();
//				}
//
//				if (is_string($query) === true) {
//					$query = array();
//				}
//			}
//			return fclose($file);
//		} else {
//			return false;
//		}
//	} else {
//		return false;
//	}
//}
//
//function BackupDatabase()
//{
//	$pdo = new Settings();
//	$c = new ColorCLI();
//	$DIR = nZEDb_MISC;
//
//	if (\nzedb\utility\Utility::hasCommand("php5")) {
//		$PHP = "php5";
//	} else {
//		$PHP = "php";
//	}
//
//	//Backup based on database system
//	if ($pdo->dbSystem() === "mysql") {
//		system("$PHP ${DIR}testing/DB/mysqldump_tables.php db dump ../../../");
//	} else if ($pdo->dbSystem() === "pgsql") {
//		exit($c->error("Currently not supported on this platform."));
//	}
//}
//
//$os = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') ? "windows" : "unix";
//
//if (isset($argv[1]) && $argv[1] == "safe") {
//	$safeupgrade = true;
//} else {
//	$safeupgrade = false;
//}
//
//if (isset($os) && $os == "unix") {
//	$s = new Sites();
//	$site = $s->get();
//	$currentversion = $site->sqlpatch;
//	$patched = 0;
//	$patches = array();
//	$pdo = new Settings();
//	$backedup = false;
//	$c = new ColorCLI();
//
//	if ($pdo->dbSystem() === "mysql") {
//		$path = nZEDb_RES . 'db/patches/mysql/';
//	} else if ($pdo->dbSystem() === "pgsql") {
//		$path = nZEDb_RES . 'db/patches/pgsql/';
//	}
//
//	// Open the patch folder.
//	if ($handle = @opendir($path)) {
//		while (false !== ($patch = readdir($handle))) {
//			$patches[] = $patch;
//		}
//		closedir($handle);
//	} else {
//		exit($c->error("\nHave you changed the path to the patches folder, or do you have the right permissions?\n"));
//	}
//
//	/* 	if ($pdo->dbSystem() === "mysql")
//	  $patchpath = preg_replace('/\/misc\/testing\/DB/i', '/db/patches/mysql/',
//	nZEDb_ROOT);
//	  else if ($pdo->dbSystem() === "pgsql")
//	  $patchpath = preg_replace('/\/misc\/testing\/DB/i', '/db/patches/pgsql/', nZEDb_ROOT);
//	 */ sort($patches);
//
//	foreach ($patches as $patch) {
//		if (preg_match('/\.sql$/i', $patch)) {
//			$filepath = $path . $patch;
//			$file = fopen($filepath, "r");
//			$patch = fread($file, filesize($filepath));
//			if (preg_match('/UPDATE `?site`? SET `?value`? = \'?(\d{1,})\'? WHERE `?setting`? = \'sqlpatch\'/i', $patch, $patchnumber)) {
//				if ($patchnumber['1'] > $currentversion) {
//					if ($safeupgrade == true && $backedup == false) {
//						BackupDatabase();
//						$backedup = true;
//					}
//					SplitSQL($filepath);
//					$patched++;
//				}
//			}
//		}
//	}
//} else if (isset($os) && $os == "windows") {
//	$s = new Sites();
//	$site = $s->get();
//	$currentversion = $site->sqlpatch;
//	$patched = 0;
//	$patches = array();
//
//	// Open the patch folder.
//	if (!isset($argv[1])) {
//		exit($c->error("\nYou must supply the directory to the patches.\n"));
//	}
//	if ($handle = @opendir($argv[1])) {
//		while (false !== ($patch = readdir($handle))) {
//			$patches[] = $patch;
//		}
//		closedir($handle);
//	} else {
//		exit($c->error("\nHave you changed the path to the patches folder, or do you have the right permissions?\n"));
//	}
//
//	sort($patches);
//	foreach ($patches as $patch) {
//		if (preg_match('/\.sql$/i', $patch)) {
//			$filepath = $argv[1] . $patch;
//			$file = fopen($filepath, "r");
//			$patch = fread($file, filesize($filepath));
//			if (preg_match('/UPDATE `?site`? SET `?value`? = \'?(\d{1,})\'? WHERE `?setting`? = \'sqlpatch\'/i', $patch, $patchnumber)) {
//				if ($patchnumber['1'] > $currentversion) {
//					if ($safeupgrade == true && $backedup == false) {
//						BackupDatabase();
//						$backedup = true;
//					}
//					SplitSQL($filepath);
//					$patched++;
//				}
//			}
//		}
//	}
//} else {
//	exit($c->error("\nUnable to determine OS.\n"));
//}
//
//if ($patched == 0) {
//	exit($c->info("Nothing to patch, you are already on patch version " . $currentversion));
//}
//if ($patched > 0) {
//	echo $c->header($patched . " patch(es) applied.");
//	$smarty = new Smarty;
//	$cleared = $smarty->clearCompiledTemplate();
//	if ($cleared) {
//		echo $c->header("The smarty template cache has been cleaned for you");
//	} else {
//		echo $c->header("You should clear your smarty template cache at: " . SMARTY_DIR . "templates_c");
//	}
//}
