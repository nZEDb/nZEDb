<?php
require_once dirname(__FILE__) . '/config.php';
require_once nZEDb_LIB . 'category.php';
require_once nZEDb_LIB . 'groups.php';
require_once nZEDb_LIB . 'nfo.php';
require_once nZEDb_LIB . 'namecleaning.php';
require_once nZEDb_LIB . 'ColorCLI.php';

$c = new ColorCLI;
if (!isset($argv[1]))
	exit($c->error("\nThis script tries to match an MD5 of the releases.name or releases.searchname to predb.md5.\n"
		."php decrypt_hashes.php true		...: to limit 1000.\n"
		."php decrypt_hashes.php full 		...: to run on full database.\n"));

echo $c->header("\nDecrypt Hashes Started at ".date('g:i:s')."\nMatching predb MD5 to md5(releases.name or releases.searchname)");
preName($argv);

function preName($argv)
{
	$db = new DB();
	$timestart = TIME();
	$limit = ($argv[1] == 'full') ? '' : ' LIMIT 1000';
	$c = new ColorCLI;

	$res = $db->queryDirect('SELECT id, name, searchname, groupid, categoryid FROM releases WHERE hashed = true AND dehashstatus BETWEEN -6 AND 0'.$limit);
	$total = $res->rowCount();
	$counter = 0;
	$show = '';
	if($total > 0)
	{
		$precount = $db->queryOneRow('SELECT COUNT(*) AS count FROM predb');
		echo $c->primary('Comparing '.number_format($total).' releases against '.number_format($precount['count'])." preDB's.");
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
				$pre = $db->queryOneRow(sprintf('SELECT title, source FROM predb WHERE md5 = %s', $db->escapeString($match[1])));
				if ($pre !== false)
				{
					$determinedcat = $category->determineCategory($pre['title'], $row['groupid']);
					$result = $db->prepare(sprintf('UPDATE releases SET dehashstatus = 1, relnamestatus = 5, searchname = %s, categoryid = %d WHERE id = %d', $db->escapeString($pre['title']), $determinedcat, $row['id']));
					$result->execute();
					if (count($result) > 0)
					{
						$groups = new Groups();
						$groupname = $groups->getByNameByID($row['groupid']);
						$oldcatname = $category->getNameByID($row['categoryid']);
						$newcatname = $category->getNameByID($determinedcat);

						echo $c->primary($n.'New name:  '.$pre['title'].$n.
							'Old name:  '.$row['searchname'].$n.
							'New cat:   '.$newcatname.$n.
							'Old cat:   '.$oldcatname.$n.
							'Group:     '.$groupname.$n.
							'Method:    '.'predb md5 release name: '.$pre['source'].$n.
							'ReleaseID: '. $row['id']);

						$success = true;
						$counter++;
					}
				}
			}
			if ($success == false)
			{
				$fail = $db->prepare(sprintf('UPDATE releases SET dehashstatus = dehashstatus - 1 WHERE id = %d', $row['id']));
				$fail->execute();
			}
		}
	}
	if ($total > 0)
		echo $c->header("\nRenamed ".$counter." releases in ".$consoletools->convertTime(TIME() - $timestart).".");
	else
		echo $c->info("\nNothing to do.");
}
?>
