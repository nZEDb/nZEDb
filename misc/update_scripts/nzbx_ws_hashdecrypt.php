<?php
define("ENABLE_ECHO", TRUE);
require_once(dirname(__FILE__)."/config.php");
require_once(WWW_DIR."lib/backfill.php");
require_once(WWW_DIR."config.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/releases.php");
require_once(WWW_DIR."lib/category.php");

function getReleaseName($md5)
{
	return file_get_contents("http://nzbx.ws/decrypt/x0/?hash=" . $md5);
}

function isPreDBActive()
{
	return getReleaseName("0f4e871131b1d05c0010093021b521ca");
}

function isInnerActive()
{
	$db = new DB();
	return $db->query(sprintf("SELECT * FROM `groups` WHERE `name` = 'alt.binaries.inner-sanctum'"));
}

$y = isInnerActive();
foreach ($y as $z)
{
	if ($z["active"] == 0) {
		die("Please activate group alt.binaries.inner-sanctum");
	}
}

function getReleasez()
{
	$db = new DB();
	$result = $db->query(sprintf("SELECT * FROM releases WHERE dehashstatus = 0 AND fromname = 'HaShTaG@nzb.file' LIMIT 100"));
	return $result;
}

function updaterelease($foundName, $id, $groupname)
{
	$db  = new DB();
	$rel = new Releases();
	$cat = new Category();
	
	$cleanRelName = $foundName;
	$catid		= $cat->determineCategory($groupname, $foundName);
	
	$db->query(sprintf("UPDATE releases SET name = %s,  searchname = %s, categoryID = %d WHERE ID = %d", $db->escapeString($cleanRelName), $db->escapeString($cleanRelName), $catid, $id));
	
}
$results = getReleasez();
if (count($results) > 0)
{
	$db = new DB();
	foreach ($results as $result) {
		if (strlen(isPreDBActive()) < 10 && !strstr(isPreDBActive(), '_') == TRUE) {
			die("PreDB Maintenance");
		}
		$processed = FALSE;
		$x = substr($result['name'],0,32);
		if (!strstr($x, '.') == TRUE) {
			if (!strstr($x, ' ') == TRUE) {
				if (!strstr($x, '_') == TRUE) {
					if (!strstr($x, '(') == TRUE) {
						if (!strstr($x, '-') == TRUE) {
							$r = getReleaseName($result['name']);
							if (strlen($r) > 5) {
								if (!strstr($r, 'cloudflare') == TRUE) {
									if (strstr($r, '-') == TRUE) {
										updaterelease($r, $result['ID'], $result['name']);
									$processed = TRUE;
									}
								}
							}
						}
					}
				}
			}
		}
		if ($processed == TRUE) {
			if (ENABLE_ECHO == TRUE)
			{
				echo "Release found " . $r . "\n";
			}
			$db->query(sprintf("update releases set dehashstatus = 1 where ID = %s", $result['ID']));
		} else {
			if (ENABLE_ECHO == TRUE)
			{
				echo $result['name']." not found\n";
			}
			$db->query(sprintf("update releases set dehashstatus = -1 where ID = %s", $result['ID']));
		}
	}
}
else
	echo "All inner-sanctum releases have been processed.\n";
?>
