<?php
/*
 * Deletes releases in categories you have disabled here : http://localhost/admin/category-list.php
 */
require(dirname(__FILE__)."/../../../www/config.php");
require_once(WWW_DIR."/lib/framework/db.php");
require_once(WWW_DIR."/lib/category.php");
require_once(WWW_DIR."/lib/releases.php");
require_once(WWW_DIR."/lib/site.php");

if(isset($argv[1]) && $argv[1] == "true")
{
	$timestart = TIME();
	$s = new Sites();
	$db = new DB();
	$releases = new Releases();
	$category = new Category();
	$site = $s->get();
	if ($catlist = $category->getDisabledIDs())
	{
		$relsdeleted = 0;
		while ($cat = mysqli_fetch_assoc($catlist))
		{
			if ($rels = $db->query(sprintf("select ID, guid from releases where categoryID = %d", $cat['ID'])))
			{
				foreach ($rels as $rel)
				{
					$relsdeleted++;
					$releases->fastDelete($rel['ID'], $rel['guid'], $site);
				}
			}
		}
		$time = TIME() - $timestart;
		if ($relsdeleted > 0)
			exit ($relsdeleted." releases deleted in ".$time." seconds.\n");
		else
			exit ("No releases to delete.\n");
	}
}
else
	exit("Deletes releases in categories you have disabled here : http://localhost/admin/category-list.php\nIf you are sure you want to run this script, type php delete_unwated_category_releases.php true\n");
?>
