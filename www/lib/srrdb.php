<?php
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/category.php");
require_once(WWW_DIR."/lib/groups.php");
/*
 * Class for inserting names from srrdb.com into the DB, also for matching names on files / subjects.
 */

Class SRRDB
{
	function SRRDB($echooutput=false)
	{
		$this->echooutput = $echooutput;
	}
	
	// Retrieve titles from srrdb and store them in the DB.
	// Returns the quantity of new titles retrieved.
	public function retrieveTitles()
	{
		$db = new DB;
		$newnames = 0;
		$newestrel = $db->queryOneRow("SELECT adddate, ID FROM srrdb ORDER BY adddate DESC LIMIT 1");
		if (strtotime($newestrel["adddate"]) < time()-600)
		{
			$releases = @simplexml_load_file('http://www.srrdb.com/feed/srrs');
			if ($releases !== false)
			{
				foreach ($releases->channel->item as $release)
				{
					$namecheck = $db->queryOneRow(sprintf("SELECT title FROM srrdb WHERE title = %s", $db->escapeString($release->title)));
					if ($namecheck["title"] == $release->title)
						continue;
					else
					{
						$db->query(sprintf("INSERT INTO srrdb (title, pubDate, adddate) VALUES (%s, FROM_UNIXTIME(".strtotime($release->pubDate)."), now())", $db->escapeString($release->title)));
						$newnames++;
					}
				}
				if ($newnames == 0)
					$db->query(sprintf("UPDATE srrdb SET adddate = now() where ID = %d", $newestrel["ID"]));
			}
		}
		return $newnames;
	}
	
	// Matches the names within the srrdb table to release files and subjects (names).
	public function parseTitles($time, $echo, $cats, $namestatus)
	{
		$db = new DB();
		$updated = 0;
		
		$newestrel = $db->queryOneRow("SELECT adddate, ID FROM srrdb where title = 'FIRST_CHECK_DO_NOT_DELETE_THIS'");
		if (strtotime($newestrel["adddate"]) < time()-1)
		{
			if($this->echooutput)
			{
				$te = "";
				if ($time == 1)
					$te = " in the past 3 hours";
				echo "Fixing search names".$te." using srrdb.com.\n";
			}
		
			$tq = "";
			if ($time == 1)
				$tq = " and r.adddate > (now() - interval 3 hour)";
			$ct = "";
			if ($cats == 1)
				$ct = " and (r.categoryID like \"1090\" or r.categoryID like \"2020\" or r.categoryID like \"3050\" or r.categoryID like \"6050\" or r.categoryID like \"5050\" or r.categoryID like \"7010\" or r.categoryID like \"8050\")";
			
			if($res = $db->queryDirect("SELECT r.searchname, r.categoryID, r.groupID, s.title, r.ID from releases r left join releasefiles rf on rf.releaseID = r.ID, srrdb s where (r.name like concat('%', s.title, '%') or rf.name like concat('%', s.title, '%')) and relnamestatus < 2".$tq.$ct))
			{
				while ($row = mysqli_fetch_assoc($res))
				{
					if ($row["title"] !== $row["searchname"])
					{
						$category = new Category();
						$determinedcat = $category->determineCategory($row["title"], $row["groupID"]);
					
						if ($echo == 1)
						{
							if ($namestatus == 1)
								$db->query(sprintf("UPDATE releases SET searchname = %s, categoryID = %d, relnamestatus = 2 where ID = %d", $db->escapeString($row["title"]), $determinedcat, $row["ID"]));
							else
								$db->query(sprintf("UPDATE releases SET searchname = %s, categoryID = %d where ID = %d", $db->escapeString($row["title"]), $determinedcat, $row["ID"]));
						}
						if ($this->echooutput)
						{
							$groups = new Groups();
						
							echo"New name: ".$row["title"]."\n".
								"Old name: ".$row["searchname"]."\n".
								"New cat:  ".$category->getNameByID($determinedcat)."\n".
								"Old cat:  ".$category->getNameByID($row["categoryID"])."\n".
								"Group:    ".$groups->getByNameByID($row["groupID"])."\n".
								"Method:   "."srrdb"."\n"."\n";
						}
					}
					$updated++;
				}
			}
			$db->query("UPDATE srrdb SET adddate = now() WHERE title = 'FIRST_CHECK_DO_NOT_DELETE_THIS'");
		}
		return $updated;
	}
}
?>
