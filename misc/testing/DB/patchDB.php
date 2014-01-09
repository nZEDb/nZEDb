<?php
//This inserts the patches into MySQL and PostgreSQL.

require_once dirname(__FILE__) . '/../../../www/config.php';
//require_once nZEDb_LIB . 'framework/db.php';
//require_once nZEDb_LIB . 'site.php';
//require_once nZEDb_LIB . 'ColorCLI.php';

function command_exist($cmd)
{
	$returnVal = shell_exec("which $cmd");
	return (empty($returnVal) ? false : true);
}

// Function inspired by : http://stackoverflow.com/questions/1883079/best-practice-import-mysql-file-in-php-split-queries/2011454#2011454
function SplitSQL($file, $delimiter = ';')
{
	set_time_limit(0);

	if (is_file($file) === true) {
		$file = fopen($file, 'r');

		if (is_resource($file) === true) {
			$query = array();
			$db = new DB();
			$dbsys = $db->dbSystem();
			$c = new ColorCLI();

			while (feof($file) === false) {
				$query[] = fgets($file);
				if (preg_match('~' . preg_quote($delimiter, '~') . '\s*$~iS', end($query)) === 1) {
					$query = trim(implode('', $query));

					if ($dbsys == "pgsql") {
						$query = str_replace(array("`", chr(96)), '', $query);
					}
					try {
						$qry = $db->prepare($query);
						$qry->execute();
						echo 'SUCCESS: ' . $query . "\n";
					} catch (PDOException $e) {
						if ($e->errorInfo[1] == 1091 || $e->errorInfo[1] == 1060 || $e->errorInfo[1] == 1054 || $e->errorInfo[1] == 1061 || $e->errorInfo[1] == 1062 || $e->errorInfo[1] == 1071 || $e->errorInfo[1] == 1072 || $e->errorInfo[1] == 1146 || $e->errorInfo[0] == 23505 || $e->errorInfo[0] == 42701 || $e->errorInfo[0] == 42703 || $e->errorInfo[0] == '42P07' || $e->errorInfo[0] == '42P16') {
							echo $c->error($query . " Skipped - Not Fatal.\n");
						} else {
							exit($c->error($query . " Failed\n"));
						}
					}

					while (ob_get_level() > 0) {
						ob_end_flush();
					}
					flush();
				}

				if (is_string($query) === true) {
					$query = array();
				}
			}
			return fclose($file);
		} else {
			return false;
		}
	} else {
		return false;
	}
}

function BackupDatabase()
{
	$db = new DB();
	$c = new ColorCLI();
	$DIR = nZEDb_MISC;

	if (command_exist("php5")) {
		$PHP = "php5";
	} else {
		$PHP = "php";
	}

	//Backup based on database system
	if($db->dbSystem() == "mysql")
	{
		system("$PHP ${DIR}testing/DB/mysqldump_tables.php db dump ../../../");
	}
	else if($db->dbSystem() == "pgsql")
	{
		exit($c->error("Currently not supported on this platform."));
	}
}

$os = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') ? "windows" : "unix";

if (isset($argv[1]) && $argv[1] == "safe") {
	$safeupgrade = true;
} else {
	$safeupgrade = false;
}

if (isset($os) && $os == "unix")
{
	$s = new Sites();
	$site = $s->get();
	$currentversion = $site->sqlpatch;
	$patched = 0;
	$patches = array();
	$db = new DB();
	$backedup = false;
	$c = new ColorCLI();

	if ($db->dbSystem() == "mysql") {
		$path = nZEDb_ROOT . 'db/mysql_patches/';
	} else if ($db->dbSystem() == "pgsql") {
		$path = nZEDb_ROOT . 'db/pgsql_patches/';
	}

	// Open the patch folder.
	if ($handle = @opendir($path)) {
		while (false !== ($patch = readdir($handle))) {
			$patches[] = $patch;
		}
		closedir($handle);
	} else {
		exit($c->error("\nHave you changed the path to the patches folder, or do you have the right permissions?\n"));
	}

	/*	if ($db->dbSystem() == "mysql")
		$patchpath = preg_replace('/\/misc\/testing\/DB/i', '/db/mysql_patches/', nZEDb_ROOT);
	else if ($db->dbSystem() == "pgsql")
		$patchpath = preg_replace('/\/misc\/testing\/DB/i', '/db/pgsql_patches/', nZEDb_ROOT);
*/	sort($patches);

	foreach($patches as $patch)
	{
		if (preg_match('/\.sql$/i', $patch))
		{
			$filepath = $path.$patch;
			$file = fopen($filepath, "r");
			$patch = fread($file, filesize($filepath));
			if (preg_match('/UPDATE `?site`? SET `?value`? = \'?(\d{1,})\'? WHERE `?setting`? = \'sqlpatch\'/i', $patch, $patchnumber))
			{
				if ($patchnumber['1'] > $currentversion)
				{
					if($safeupgrade == true && $backedup == false)
					{
						BackupDatabase();
						$backedup = true;
					}
					SplitSQL($filepath);
					$patched++;
				}
			}
		}
	}
}
else if (isset($os) && $os == "windows") {
	$s = new Sites();
	$site = $s->get();
	$currentversion = $site->sqlpatch;
	$patched = 0;
	$patches = array();

	// Open the patch folder.
	if (!isset($argv[1])) {
		exit($c->error("\nYou must supply the directory to the patches.\n"));
	}
	if ($handle = @opendir($argv[1])) {
		while (false !== ($patch = readdir($handle))) {
			$patches[] = $patch;
		}
		closedir($handle);
	} else {
		exit($c->error("\nHave you changed the path to the patches folder, or do you have the right permissions?\n"));
	}

	sort($patches);
	foreach ($patches as $patch) {
		if (preg_match('/\.sql$/i', $patch)) {
			$filepath = $argv[1] . $patch;
			$file = fopen($filepath, "r");
			$patch = fread($file, filesize($filepath));
			if (preg_match('/UPDATE `?site`? SET `?value`? = \'?(\d{1,})\'? WHERE `?setting`? = \'sqlpatch\'/i', $patch, $patchnumber)) {
				if ($patchnumber['1'] > $currentversion) {
					if ($safeupgrade == true && $backedup == false) {
						BackupDatabase();
						$backedup = true;
					}
					SplitSQL($filepath);
					$patched++;
				}
			}
		}
	}
} else {
	exit($c->error("\nUnable to determine OS.\n"));
}

if ($patched > 0) {
	exit($c->header($patched . " patch(es) applied.\nNow you need to delete the files inside of the www/lib/smarty/templates_c folder."));
}
if ($patched == 0) {
	exit($c->info("Nothing to patch, you are already on patch version " . $currentversion));
}
