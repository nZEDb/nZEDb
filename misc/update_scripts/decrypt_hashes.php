<?php
require_once(dirname(__FILE__)."/config.php");
require_once(WWW_DIR."lib/framework/db.php");
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
	$counter=0;
	if($res = $db->queryDirect("select ID, searchname from releases where searchname REGEXP '[a-fA-F0-9]{32}' and dehashstatus = 0"))
	{
		foreach ($res as $row)
		{
			if (preg_match('/^([0-9a-f]+?) .+?/', $row['searchname'], $match))
			{
				if($res1 = $db->queryOneRow(sprintf("select title from predb where md5 = %s", $db->escapeString($match[1]))))
				{
					$db->query(sprintf("update releases set dehashstatus = 1, searchname = %s where ID = %d", $db->escapeString($res1['title']), $row['ID']));
					if ($db->getAffectedRows() >= 1)
					{
						$consoletools->overWrite("Renaming hashed releases:".$consoletools->percentString($counter++,mysqli_num_rows($res)));
					}
				}
			}
		}
	}
	echo "\n";
}
