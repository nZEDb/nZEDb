<?php
require_once dirname(__FILE__) . '/config.php';
require_once nZEDb_LIB . 'category.php';
require_once nZEDb_LIB . 'groups.php';
require_once nZEDb_LIB . 'nfo.php';
require_once nZEDb_LIB . 'namecleaning.php';
require_once nZEDb_LIB . 'ColorCLI.php';

$c = new ColorCLI;
if (!isset($argv[1]) || ( $argv[1] != "all" && $argv[1] != "full" && !is_numeric($argv[1])))
	exit($c->error("\nThis script tries to match an MD5 of the releases.name or releases.searchname to predb.md5.\n"
		."php decrypt_hashes.php 1000		...: to limit to 1000 sorted by newest postdate.\n"
		."php decrypt_hashes.php full 		...: to run on full database.\n"
		."php decrypt_hashes.php all 		...: to run on all hashed releases(including previously renamed).\n"));

echo $c->header("\nDecrypt Hashes (${argv[1]}) Started at ".date('g:i:s')."\nMatching predb MD5 to md5(releases.name or releases.searchname)");
preName($argv);

function preName($argv)
{
	$db = new DB();
	$timestart = TIME();
	if (isset($argv[1]) && $argv[1] === "all")
		$res = $db->queryDirect('SELECT id, name, searchname, groupid, categoryid FROM releases WHERE (bitwise & 512) = 512');
	else if (isset($argv[1]) && $argv[1] === "full")
		$res = $db->queryDirect('SELECT id, name, searchname, groupid, categoryid FROM releases WHERE (bitwise & 512) = 512 AND dehashstatus BETWEEN -6 AND 0');
	else if (isset($argv[1]) && is_numeric($argv[1]))
		$res = $db->queryDirect('SELECT id, name, searchname, groupid, categoryid FROM releases WHERE (bitwise & 512) = 512 AND dehashstatus BETWEEN -6 AND 0 ORDER BY postdate DESC LIMIT '.$argv[1]);
	$c = new ColorCLI;

	$total = $res->rowCount();
	$counter = 0;
	$show = '';
	if($total > 0)
	{
		$precount = $db->queryOneRow('SELECT COUNT(*) AS count FROM predb');
		echo $c->primary('Comparing '.number_format($total).' releases against '.number_format($precount['count'])." preDB's.");
		sleep(2);
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
					$result = $db->prepare(sprintf('UPDATE releases SET dehashstatus = 1, bitwise = ((bitwise & ~36)|36), searchname = %s, categoryid = %d WHERE id = %d', $db->escapeString($pre['title']), $determinedcat, $row['id']));
					$result->execute();
					if (count($result) > 0)
					{
						$groups = new Groups();
						$groupname = $groups->getByNameByID($row['groupid']);
						$oldcatname = $category->getNameByID($row['categoryid']);
						$newcatname = $category->getNameByID($determinedcat);

						echo	$n.$c->headerOver("New name:  ").$c->primary($pre['title']).
							$c->headerOver("Old name:  ").$c->primary($row['searchname']).
							$c->headerOver("New cat:   ").$c->primary($newcatname).
							$c->headerOver("Old cat:   ").$c->primary($oldcatname).
							$c->headerOver("Group:     ").$c->primary($groupname).
							$c->headerOver("Method:    ").$c->primary('predb md5 release name: '.$pre['source']).
							$c->headerOver("ReleaseID: ").$c->primary($row['id']);

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
