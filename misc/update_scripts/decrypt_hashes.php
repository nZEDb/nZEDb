<?php
require_once(dirname(__FILE__)."/config.php");
require_once(WWW_DIR."lib/category.php");
require_once(WWW_DIR."lib/groups.php");
require_once(WWW_DIR."lib/nfo.php");
require_once(WWW_DIR."lib/site.php");
require_once(WWW_DIR."lib/namecleaning.php");

preName();

function preName()
{
	$db = new DB();

	if ($db->dbSystem() == "mysql")
	{
		$db->queryExec("UPDATE releases SET dehashstatus = -1 WHERE dehashstatus = 0 AND searchname REGEXP '[a-fA-F0-9]{32}'");
		$res = $db->query("SELECT id, searchname FROM releases WHERE dehashstatus BETWEEN -6 AND -1 AND searchname REGEXP '[a-fA-F0-9]{32}'");
	}
	else if ($db->dbSystem() == "pgsql")
	{
		$db->queryExec("UPDATE releases SET dehashstatus = -1 WHERE dehashstatus = 0 AND regexp_matches(searchname, '[a-fA-F0-9]{32}')");
		$res = $db->query("SELECT id, searchname FROM releases WHERE dehashstatus BETWEEN -6 AND -1 AND regexp_matches(searchname, '[a-fA-F0-9]{32}')");
	}
	
	$counter = 0;
	$db->queryExec("UPDATE releases SET dehashstatus = -1 WHERE dehashstatus = 0 AND searchname REGEXP '[a-fA-F0-9]{32}'");
	if(count($res) > 0)
	{
		$consoletools = new ConsoleTools();
		$reset = 0;
		$loops = 1;
		foreach ($res as $row)
		{
			$success = false;
			if (preg_match('/([0-9a-fA-F]{32})/', $row['searchname'], $match))
			{
				if($res1 = $db->queryOneRow(sprintf("SELECT title FROM predb WHERE md5 = %s", $db->escapeString($match[1]))))
				{
					$result = $db->queryExec(sprintf("UPDATE releases SET dehashstatus = 1, relnamestatus = 5, searchname = %s WHERE id = %d", $db->escapeString($res1['title']), $row['id']));
					if (count($result) >= 1)
					{
						echo "Renamed hashed release: ".$res1['title']."\n";
						$success = true;
						$counter++;
					}
				}
			}
			if ($success == false)
				$db->queryExec(sprintf("UPDATE releases SET dehashstatus = dehashstatus - 1 WHERE id = %d", $row['id']));
			$consoletools->overWrite("Renaming hashed releases:".$consoletools->percentString($loops++,count($res)));
			echo "\n";
		}
	}
	if ($counter > 0)
		echo $counter." release(s) names changed.\n";
	else
		echo "Nothing to do.\n";
}
