<?php
define("ENABLE_ECHO", TRUE);
require(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."lib/backfill.php");
require_once(WWW_DIR."config.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/releases.php");
require_once(WWW_DIR."lib/category.php");
require_once(WWW_DIR."lib/tmux.php");

$tmux = new Tmux;

function getReleaseName($md5)
{
	return file_get_contents("http://nzbx.ws/decrypt/x0/?hash=" . $md5);
}

function isPreDBActive()
{
	return getReleaseName("0f4e871131b1d05c0010093021b521ca");
}

if (strlen(isPreDBActive()) < 10 && !strstr(isPreDBActive(), '_') == TRUE) {
	die("PreDB Maintenance");
}

function isInnerActive()
{
	$db = new DB();
	return $db->query(sprintf("SELECT * FROM `groups` WHERE `name` = 'alt.binaries.inner-sanctum'"));
}

$y = isInnerActive();
foreach ($y as $z) {
	if ($z["active"] == 0) {
		die("Please activate group alt.binaries.inner-sanctum");
	}
}

function getReleasez($i)
{
	$db = new DB();
	$run = $i*30;
	$result = $db->query(sprintf("SELECT * FROM `releases` WHERE dehashstatus = 0 AND `fromname` = 'HaShTaG@nzb.file' ORDER BY ID DESC LIMIT %d, 30", $run));
	if (count($result) > 0)
		echo "Beginning Decrypt Hashed Releases 30 releases starting at $run\n";
	else
		$i = 0;
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
$counter = 0;
$i = 0;
while(1)
{
	$sleep = $tmux->get()->DEHASH_TIMER;
	$results = getReleasez($i);
	$i++;
	$db = new DB();
	foreach ($results as $result) {
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
			if (ENABLE_ECHO == TRUE) {
				echo "Release found " . $r . "\n";
				$counter++;
			}
			$db->query(sprintf("update releases set dehashstatus = 1 where ID = %s", $result['ID']));
		} else {
			if (ENABLE_ECHO == TRUE) {
				echo $result['name']." not found\n";
			}
			$db->query(sprintf("update releases set dehashstatus = -1 where ID = %s", $result['ID']));
		}
	}
	echo $counter." releases matched\n";
	sleep($sleep);
}
?>
