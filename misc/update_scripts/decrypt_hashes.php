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
	$counter = 0;
	$loops = 1;
	$reset = 0;
	//$db->queryDirect("update releases set dehashstatus = -1 where dehashstatus = 0 and searchname REGEXP '[a-fA-F0-9]{32}'");

	$db->query("update releases set dehashstatus = -1 where dehashstatus = 0 and searchname REGEXP '[a-fA-F0-9]{32}'");
	if($res = $db->queryDirect("select ID, searchname from releases where dehashstatus between -6 and -1 and searchname REGEXP '[a-fA-F0-9]{32}'"))
	{
		foreach ($res as $row)
		{
			$success = false;
			if (preg_match('/([0-9a-fA-F]{32})/', $row['searchname'], $match))
			{
				if($res1 = $db->queryOneRow(sprintf("select title from predb where md5 = %s", $db->escapeString($match[1]))))
				{
					$db->query(sprintf("update releases set dehashstatus = 1, relnamestatus = 6, searchname = %s where ID = %d", $db->escapeString($res1['title']), $row['ID']));
					if ($db->getAffectedRows() >= 1)
					{
						echo "Renamed hashed release: ".$res1['title']."\n";
						$success = true;
						$counter++;
					}
				}
			}
			if ($success == false)
				$db->query(sprintf("update releases set dehashstatus = dehashstatus - 1 where ID = %d", $row['ID']));
			$consoletools->overWrite("Renaming hashed releases:".$consoletools->percentString($loops++,mysqli_num_rows($res)));
		}
	}
	echo "\n".$counter. " release(s) names changed.\n";
}
