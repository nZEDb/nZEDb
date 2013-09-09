<?php
require_once(dirname(__FILE__)."/config.php");
require_once(WWW_DIR."lib/category.php");
require_once(WWW_DIR."lib/groups.php");
require_once(WWW_DIR."lib/nfo.php");
require_once(WWW_DIR."lib/site.php");
require_once(WWW_DIR."lib/namecleaning.php");

if (!isset($argv[1]))
	exit ("This script tries to match an MD5 of the releases.name or releases.searchname to predb.md5.\nphp decrypt_hashes.php true to limit 1000.\nphp decrypt_hashes.php full to run on full database.\n");

echo "\nDecrypt Hashes Started at ".date("g:i:s")."\nMatching predb MD5 to md5(releases.name or releases.searchname)\n";
preName($argv);

function preName($argv)
{
	$db = new DB();
	$timestart = TIME();
	$limit = ($argv[1] == "full") ? "" : " LIMIT 1000";

	$res = $db->query("SELECT id, name, searchname, groupid, categoryid FROM releases WHERE dehashstatus BETWEEN -5 AND 0 AND hashed = true".$limit);
	$counter = 0;
	$total = count($res);
	$show = '';
	if($total > 0)
	{
		$consoletools = new ConsoleTools();
		$category = new Category();
		$reset = 0;
		$loops = 1;
		$n = "\n";
		foreach ($res as $row)
		{
			$success = false;
			if (preg_match('/([0-9a-fA-F]{32})/', $row['searchname'], $match) || preg_match('/([0-9a-fA-F]{32})/', $row['name'], $match))
			{
				$pre = $db->queryOneRow(sprintf("SELECT title, source FROM predb WHERE md5 = %s", $db->escapeString($match[1])));
				if ($pre !== false)
				{
					$determinedcat = $category->determineCategory($pre['title'], $row["groupid"]);
					$result = $db->prepare(sprintf("UPDATE releases SET dehashstatus = 1, relnamestatus = 5, searchname = %s, categoryid = %d WHERE id = %d", $db->escapeString($pre['title']), $determinedcat, $row['id']));
					$result->execute();
					if (count($result) > 0)
					{
						$groups = new Groups();
						$groupname = $groups->getByNameByID($row["groupid"]);
						$oldcatname = $category->getNameByID($row["categoryid"]);
						$newcatname = $category->getNameByID($determinedcat);

						echo	$n."New name:  ".$pre['title'].$n.
							"Old name:  ".$row["searchname"].$n.
							"New cat:   ".$newcatname.$n.
							"Old cat:   ".$oldcatname.$n.
							"Group:     ".$groupname.$n.
							"Method:    "."predb md5 release name: ".$pre["source"].$n.
							"ReleaseID: ". $row["id"].$n;

						$success = true;
						$counter++;
					}
				}
			}
			if ($success == false)
			{
				$fail = $db->prepare(sprintf("UPDATE releases SET dehashstatus = dehashstatus - 1 WHERE id = %d", $row['id']));
				$fail->execute();
			}
		}
	}
	if ($total > 0)
		echo "\nRenamed ".$counter." releases in ".$consoletools->convertTime(TIME() - $timestart)."\n";
	else
		echo "\nNothing to do.\n";
}
