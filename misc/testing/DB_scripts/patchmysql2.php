<?php

/*
 * This inserts the patches into MYSQL.
 */

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../../www/config.php");
require_once(FS_ROOT."/../../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../../www/lib/site.php");

//
// Function from : http://stackoverflow.com/questions/1883079/best-practice-import-mysql-file-in-php-split-queries/2011454#2011454
//
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
			
            while (feof($file) === false)
            {
                $query[] = fgets($file);

                if (preg_match('~' . preg_quote($delimiter, '~') . '\s*$~iS', end($query)) === 1)
                {
                    $query = trim(implode('', $query));

                    if ($db->query($query) === false)
                    {
                        echo 'ERROR: ' . $query . "\n";
                    }

                    else
                    {
                        echo 'SUCCESS: ' . $query . "\n";
                    }

                    while (ob_get_level() > 0)
                    {
                        ob_end_flush();
                    }

                    flush();
                }

                if (is_string($query) === true)
                {
                    $query = array();
                }
            }

            return fclose($file);
        }
    }

    return false;
}

/*
$s = new Sites();
$site = $s->get();
$currentversion = $site->sqlpatch;
$patched = 0;

echo "Patching process started, DO NOT stop this script!\n";

if ($handle = @opendir(FS_ROOT.'/../../../db/patches'))
{
	$patchpath = preg_replace('/\/misc\/testing\/DB_scripts/i', '/db/patches/', FS_ROOT);
	while (false !== ($entry = readdir($handle))) 
	{
        if (preg_match('/\.sql$/i', $entry))
        {
			$filepath = $patchpath.$entry;
			$file = fopen($filepath, "r");
			$patch = fread($file, filesize($filepath));
			if (preg_match('/UPDATE `site` set `value` = \'(\d{1,})\' where `setting` = \'sqlpatch\'/i', $patch, $patchnumber))
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
	exit("ERROR: Have you changed the path to the patches folder, or do you have the right permissions?\n");

if ($patched > 0)
	exit($patched." patch(es) applied. Now you need to delete the files inside of the www/lib/smarty/templates_c folder.\n");
if ($patched == 0)
	exit("Nothing to patch, you are already on patch version ".$currentversion.".\n");
*/

$s = new Sites();
$site = $s->get();
$currentversion = $site->sqlpatch;
$patched = 0;
$patches = array();

// Open the patch folder.
if ($handle = @opendir(FS_ROOT.'/../../../db/patches')) 
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
		if (preg_match('/UPDATE `site` set `value` = \'(\d{1,})\' where `setting` = \'sqlpatch\'/i', $patch, $patchnumber))
		{
			if ($patchnumber['1'] > $currentversion)
			{
				SplitSQL($filepath);
				$patched++;
			}
		}
	}
}
if ($patched > 0)
	exit($patched." patch(es) applied. Now you need to delete the files inside of the www/lib/smarty/templates_c folder.\n");
if ($patched == 0)
	exit("Nothing to patch, you are already on patch version ".$currentversion.".\n");

?>
