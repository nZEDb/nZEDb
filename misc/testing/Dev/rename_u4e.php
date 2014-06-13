<?php
require_once dirname(__FILE__) . '/../../../www/config.php';
use nzed\db\DB;

$c = new ColorCLI();

if (!isset($argv[1]) && $argv[1] !== 'makeitso') {
	exit($c->error("\nThis script is not currently operational and should not be run. If you must play around with it, then use makeitso as the argument.\n"
					. "If you do not understand programming or have the IQ of a quanset hut, I urge you to reconsider trying to do so.\n"
					. "Run this script with the syntax below at your own risk.\n\n"
					. "php $argv[0] makeitso                                     ...: To run rename on u4e.\n"
			)
	     );
}

rename_u4e();

exit;

function __construct()
{
	$s = new Sites();
	$site = $s->get();
	$db = new DB();
	$nntp = new NNTP;
	$nzb = new NZB();
	$nzbInfo = new nzbInfo;
	$category = new Category();
	$c = new ColorCLI();
	$tmpPath = $site->tmpunrarpath;
}

function rename_u4e()
{
	$res = $db->queryDirect("SELECT rf.name as filename, r.categoryid, r.name, r.guid, r.id, r.group_id, r.postdate, r.searchname as oldname "
				 . "FROM releasefiles rf INNER JOIN releases r ON rf.releaseid = rf.id "
				 . "WHERE (r.isrenamed = 0 OR r.categoryid = 7020) "
				 . "AND r.passwordstatus = 0
				 . "AND rf.name like '%Linux_2rename.sh%' "
				 . "ORDER BY r.postdate DESC"
				 );
	$nntp->doConnect();

	foreach($res as $row) {

		// get name , full path to nzb and the segment id
		$nzbfile = $nzb->getNZBPath($row['guid'], $site->nzbpath, true);
		$nzbInfo->loadFromFile($nzbfile);

		foreach($nzbInfo->rarfiles as $rarFile)
			{
				$rarMsgids = array_slice($rarFile['segments'], 0, 1); //get first segment
				$msgid = $rarMsgids[0];
			}
			//get the group name from the db
			$groupName = $functions->getByNameByid($row['group_id']);
			$sampleBinary = $nntp->getMessage($groupName, $msgid);
			if ($sampleBinary === false) {
			echo "-Couldnt fetch binary \n";
			} else {
			file_put_contents("$tmpPath . '1.rar'", $sampleBinary);
			}

			// Extract the segment
			$execstring = 'unrar e -ai -ep -c- -id -r -kb -p- -y -inul $tmpPath . 1.rar $tmpPath . */Linux*.sh';
			$output2 = runCmd($execstring, false, true);
			$txt_file = file_get_contents('$tmpPath . Linux_2rename.sh');
			$arr = explode("\n", $txt_file);
			//delete the files
			@unlink("$tmpPath . '1.rar'");
			@unlink("$tmpPath . 'Linux_2rename.sh'");

			$newName = str_replace("mkdir ","", $arr[1]);
			$n = "\n";
			$determinedcat = $category->determineCategory($groupName, $newName);
			$oldcatname = $functions->getNameByid($row["categoryid"]);
			$newcatname = $functions->getNameByid($determinedcat);
			$type = "Files, ";
			$method = "u4e";

				if (isset($newName)) {
					echo $n . $c->headerOver("New name:  ") . $c->primary($newName) .
							$c->headerOver("Old name:  ") . $c->primary($row['oldname']) .
							$c->headerOver("Use name:  ") . $c->primary($row['name']) .
							$c->headerOver("New cat:   ") . $c->primary($newcatname) .
							$c->headerOver("Old cat:   ") . $c->primary($oldcatname) .
							$c->headerOver("Group:     ") . $c->primary($groupName) .
							$c->headerOver("Method:    ") . $c->primary($type . $method) .
							$c->headerOver("ReleaseID: ") . $c->primary($row['releaseid']);
				$db->exec(sprintf('update releases set isrenamed = 1, searchname = %s, categoryid = %d where id = %d', $db->escapeString(substr($newName, 0, 255)), $determinedcat, $row['id']));
				} else {

				echo $c->error('Cannot Determine name for ' . $row['id']);
				}
		}
	$nntp->doQuit();
}