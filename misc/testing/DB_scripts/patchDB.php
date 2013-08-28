<?php
//This inserts the patches into MySQL and PostgreSQL.

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../../www/lib/site.php");

// Function inspired by : http://stackoverflow.com/questions/1883079/best-practice-import-mysql-file-in-php-split-queries/2011454#2011454
function SplitSQL($file, $delimiter = ';')
{
	set_time_limit(0);

	if (is_file($file) === true)
	{
		$file = fopen($file, 'r');

		if (is_resource($file) === true)
		{
			$query = array();
			$db = new DB();
			$dbsys = $db->dbSystem();

			while (feof($file) === false)
			{
				$query[] = fgets($file);

				if (preg_match('~' . preg_quote($delimiter, '~') . '\s*$~iS', end($query)) === 1)
				{
					$query = trim(implode('', $query));

					if ($dbsys == "pgsql")
						$query = str_replace(array("`", chr(96)), '', $query);
					if (preg_match('/UPDATE|INSERT|DELETE/i', $query))
					{
						if ($db->queryExec($query) === false)
							exit();
						else
							echo 'SUCCESS: '.$query."\n";
					}
					else
					{
						if ($db->query($query) === false)
							exit();
						else
							echo 'SUCCESS: '.$query."\n";
					}

					while (ob_get_level() > 0)
					{
						ob_end_flush();
					}
					flush();
				}

				if (is_string($query) === true)
					$query = array();
			}
			return fclose($file);
		}
		else
			return false;
	}
	else
		return false;
}

$os = (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN') ? "windows" : "unix";

if (isset($os) && $os == "unix")
{
	$s = new Sites();
	$site = $s->get();
	$currentversion = $site->sqlpatch;
	$patched = 0;
	$patches = array();
	$db = new DB;

	if ($db->dbSystem() == "mysql")
		$path = '/../../../db/mysql_patches';
	else if ($db->dbSystem() == "pgsql")
		$path = '/../../../db/pgsql_patches'

	// Open the patch folder.
	if ($handle = @opendir(FS_ROOT.$path))
	{
		while (false !== ($patch = readdir($handle)))
		{
			$patches[] = $patch;
		}
		closedir($handle);
	}
	else
		exit("ERROR: Have you changed the path to the patches folder, or do you have the right permissions?\n");

	$patchpath = preg_replace('/\/misc\/testing\/DB_scripts/i', '/db/patches/', FS_ROOT);
	sort($patches);
	foreach($patches as $patch)
	{
		if (preg_match('/\.sql$/i', $patch))
		{
			$filepath = $patchpath.$patch;
			$file = fopen($filepath, "r");
			$patch = fread($file, filesize($filepath));
			if (preg_match('/UPDATE `?site`? SET `?value`? = \'?(\d{1,})\'? WHERE `?setting`? = \'sqlpatch\'/i', $patch, $patchnumber))
			{
				if ($patchnumber['1'] > $currentversion)
				{
					SplitSQL($filepath);
					$patched++;
				}
			}
		}
	}
}
else if (isset($os) && $os == "windows")
{
	$s = new Sites();
	$site = $s->get();
	$currentversion = $site->sqlpatch;
	$patched = 0;
	$patches = array();

	// Open the patch folder.
	if (!isset($argv[1]))
		exit("You must suply the directory to the patches.\n");
	if ($handle = @opendir($argv[1]))
	{
		while (false !== ($patch = readdir($handle)))
		{
			$patches[] = $patch;
		}
		closedir($handle);
	}
	else
		exit("ERROR: Have you changed the path to the patches folder, or do you have the right permissions?\n");

	sort($patches);
	foreach($patches as $patch)
	{
		if (preg_match('/\.sql$/i', $patch))
		{
			$filepath = $argv[1].$patch;
			$file = fopen($filepath, "r");
			$patch = fread($file, filesize($filepath));
			if (preg_match('/UPDATE `?site`? SET `?value`? = \'?(\d{1,})\'? WHERE `?setting`? = \'sqlpatch\'/i', $patch, $patchnumber))
			{
				if ($patchnumber['1'] > $currentversion)
				{
					SplitSQL($filepath);
					$patched++;
				}
			}
		}
	}
}
else
	exit("ERROR: Unable to determine OS\n");

if ($patched > 0)
	exit($patched." patch(es) applied. Now you need to delete the files inside of the www/lib/smarty/templates_c folder.\n");
if ($patched == 0)
	exit("Nothing to patch, you are already on patch version ".$currentversion.".\n");

?>
