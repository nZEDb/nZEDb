<?php
require_once(dirname(__FILE__)."/config.php");
require_once(WWW_DIR."lib/category.php");
require_once(WWW_DIR."lib/groups.php");
require_once(WWW_DIR."lib/nfo.php");
require_once(WWW_DIR."lib/site.php");
require_once(WWW_DIR."lib/namecleaning.php");

if (!isset($argv[1]))
	exit ("Type php decrypt_hashes.php true to start.\n");

echo "Matching predb MD5 to md5(releasename)\n";
preName();

function preName()
{
	$db = new DB();

	if ($db->dbSystem() == "mysql")
	{
		$res = $db->query("SELECT id, name, searchname, groupid, categoryid FROM releases WHERE dehashstatus BETWEEN -5 AND 0 AND name REGEXP '[a-fA-F0-9]{32}'");
	}
	else if ($db->dbSystem() == "pgsql")
	{
		$res = $db->query("SELECT id, name, searchname, groupid, categoryid FROM releases WHERE dehashstatus BETWEEN -5 AND 0 AND regexp_matches(name, '[a-fA-F0-9]{32}')");
	}

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
			if (preg_match('/([0-9a-fA-F]{32})/', $row['name'], $match))
			{
				if($res1 = $db->queryOneRow(sprintf("SELECT title, source FROM predb WHERE md5 = %s", $db->escapeString($match[1]))))
				{
					var_dump($resl);
					$determinedcat = $category->determineCategory($res1['title'], $row["groupid"]);
					$result = $db->prepare(sprintf("UPDATE releases SET dehashstatus = 1, relnamestatus = 5, searchname = %s, categoryid = %d WHERE id = %d", $db->escapeString($res1['title']), $determinedcat, $row['id']));
					$result->execute();
					if ($result->rowCount() > 0)
					{
						$groups = new Groups();
						$groupname = $groups->getByNameByID($row["groupid"]);
						$oldcatname = $category->getNameByID($row["categoryid"]);
						$newcatname = $category->getNameByID($determinedcat);

						echo	$n."New name:  ".$res1['title'].$n.
							"Old name:  ".$row["searchname"].$n.
							"New cat:   ".$newcatname.$n.
							"Old cat:   ".$oldcatname.$n.
							"Group:     ".$groupname.$n.
							"Method:    "."predb md5 release name: ".$row["source"].$n.
							"ReleaseID: ". $row["id"].$n;


						$show = $res1['title'];
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
		echo "\n".$counter." release(s) names changed.\n";
	else
		echo "\nNothing to do.\n";
}
