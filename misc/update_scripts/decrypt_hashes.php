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
	$consoletools = new ConsoleTools();
	$counter = $reset = 0;
	$loops = 1;

	$db->queryExec("UPDATE releases SET dehashstatus = -1 WHERE dehashstatus = 0 AND searchname REGEXP '[a-fA-F0-9]{32}'");
	if($res = $db->query("SELECT id, searchname FROM releases WHERE dehashstatus BETWEEN -6 AND -1 AND searchname REGEXP '[a-fA-F0-9]{32}'"))
	{
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
		}
	}
	echo "\n".$counter. " release(s) names changed.\n";
}
