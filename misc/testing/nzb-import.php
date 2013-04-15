<?php

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(FS_ROOT."/../../www/lib/framework/db.php");
require_once(FS_ROOT."/../../www/lib/binaries.php");

$db = new DB();
$binaries = new Binaries();

if (empty($argc))
	$page = new AdminPage();

$filestoprocess = Array();
$browserpostednames = Array();
$viabrowser = false;

if (!empty($argc) || $page->isPostBack() )
{
	$retval = "";	

	//
	// Via browser, build an array of all the nzb files uploaded into php /tmp location
	//	
	if (isset($_FILES["uploadedfiles"]))
	{
    foreach ($_FILES["uploadedfiles"]["error"] as $key => $error)
    {
      if ($error == UPLOAD_ERR_OK)
      {
          $tmp_name = $_FILES["uploadedfiles"]["tmp_name"][$key];
          $name = $_FILES["uploadedfiles"]["name"][$key];
          $filestoprocess[] = $tmp_name;
          $browserpostednames[$tmp_name] = $name;
          $viabrowser = true;
      }
    }
	}

	if (!empty($argc))
	{
		$strTerminator = "\n";
		$path = $argv[1];
		$usenzbname = (isset($argv[2]) && $argv[2] == 'true') ? true : false;
	}
	else		
	{
		$strTerminator = "<br />";
		$path = (isset($_POST["folder"]) ? $_POST["folder"] : "");
		$usenzbname = (isset($_POST['usefilename']) && $_POST["usefilename"] == 'on') ? true : false;
	}
		
	if (substr($path, strlen($path) - 1) != '/')
		$path = $path."/";

	$groups = $db->query("SELECT ID, name FROM groups");
	foreach ($groups as $group)
		$siteGroups[$group["name"]] = $group["ID"];

	if (!isset($groups) || count($groups) == 0)
	{
		if (!empty($argc))
		{
			echo "no groups specified\n";
		}
		else
		{
			$retval.= "no groups specified"."<br />";
		}		
	}
	else
	{	
		$nzbCount = 0;
	
		//
		// read from the path, if no files submitted via the browser
		//		
		if (count($filestoprocess) == 0)
			$filestoprocess = glob($path."*.nzb"); 
		$start=date('Y-m-d H:i:s');
		
		foreach($filestoprocess as $nzbFile) 
		{

			$isBlackListed = FALSE;
			$importfailed = false;
			$nzb = file_get_contents($nzbFile);
			
			$xml = @simplexml_load_string($nzb);
			if (!$xml || strtolower($xml->getName()) != 'nzb') 
			{
				continue;
			}

			$skipCheck = false;
			
			$i=0;
			$firstname = [];
			$postername = [];
			$postdate = [];
			$totalFiles = 0;
			$totalsize = 0;
			
			foreach($xml->file as $file) 
			{
				//file info
				$groupID = -1;
				$name = (string)$file->attributes()->subject;
				$firstname[] = $name;
				$fromname = (string)$file->attributes()->poster;
				$postername[] = $fromname;
				$unixdate = (string)$file->attributes()->date;
				$totalFiles++;
				
				$date = date("Y-m-d H:i:s", (string)$file->attributes()->date);
				$postdate[] = $date;

				// make a fake message object to use to check the blacklist
				$msg = array("Subject" => $name, "From" => $fromname, "Message-ID" => "");

				// if the release is in our DB already then don't bother importing it
				if ($usenzbname and $skipCheck !== true)
				{
					$usename = str_replace('.nzb', '', ($viabrowser ? $browserpostednames[$nzbFile] : basename($nzbFile)));
					$dupeCheckSql = sprintf("SELECT * FROM releases WHERE name = %s AND postdate - interval 5 hour <= %s AND postdate + interval 5 hour > %s",
							$db->escapeString($usename), $db->escapeString($date), $db->escapeString($date));
					$res = $db->queryOneRow($dupeCheckSql);
					
					// only check one binary per nzb, they should all be in the same release anyway
					$skipCheck = true;
					
					// if the release is in the DB already then just skip this whole procedure
					if ($res !== false)
					{
						if (!empty($argc))
						{
							echo ("skipping ".$usename.", it already exists in your database\n");
							flush();
						}
						else
						{
							$retval.= "skipping ".$usename.", it already exists in your database<br />";
						}
						
						$importfailed = true;
						break;
					}
				}

				//groups
				$groupArr = array();
				foreach($file->groups->group as $group) 
				{
					$group = (string)$group;
					if (array_key_exists($group, $siteGroups)) 
					{
						$groupID = $siteGroups[$group];
					}
					$groupArr[] = $group;

					if ($binaries->isBlacklisted($msg, $group))
					{
						$isBlackListed = TRUE;
					}
				}

				if ($groupID != -1 && !$isBlackListed)
				{
					if ($usenzbname) 
					{
						$usename = str_replace('.nzb', '', ($viabrowser ? $browserpostednames[$nzbFile] : basename($nzbFile)));
					}
					//segments (i.e. parts)
					if (count($file->segments->segment) > 0)
					{
						foreach($file->segments->segment as $segment) 
						{
							$size = $segment->attributes()->bytes;
							$totalsize = $totalsize+$size;
						}
					}
				}
				else
				{
					if ($isBlackListed)
					{
						$errorMessage = "blacklisted binaries found in ".$name;
					}
					else
					{
						$errorMessage = "no group found for ".$name." (one of ".implode(', ', $groupArr)." are missing";
					}

					$importfailed = true;
					if (!empty($argc))
					{
						echo ($errorMessage."\n");
						flush();
					}
					else
					{
						$retval.= $errorMessage."<br />";
					}
					break;
				}
			}
			
			$relguid = md5(uniqid());
			$nzb = new NZB();
			
			if($relID = $db->queryInsert(sprintf("insert into releases (name, searchname, totalpart, groupID, adddate, guid, rageID, postdate, fromname, size, passwordstatus, categoryID) values (%s, %s, %d, %d, now(), %s, -1, %s, %s, %s, -1, 7010)", $db->escapeString($firstname['0']), $db->escapeString($firstname['0']), $totalFiles, $groupID, $db->escapeString($relguid), $db->escapeString($postdate['0']), $db->escapeString($postername['0']), $db->escapeString($totalsize))));
			{
				if($nzb->copyNZBforImport($relguid, $nzbFile))
				{
					/*echo "\nImported NZB successfully.\n";
					echo "\nSubject: ".$firstname['0']."\n";
					echo "Poster: ".$postername['0']."\n";
					echo "Added to usenet: ".$postdate['0']."\n";
					echo "Amount of files: ".$totalFiles."\n";
					echo "Release GUID: ".$relguid."\n";
					echo "GroupID: ".$groupID."\n";
					echo "Release size: ".number_format($totalsize / 1048576, 2)." MB"."\n\n";*/
				}
				else
				{
					$db->queryOneRow(sprintf("delete from releases where postdate = %s and size = %d", $db->escapeString($postdate['0']), $db->escapeString($totalsize)));
					echo "Failed copying NZB, deleting release from DB.\n";
					$importfailed = true;
				}
			}
			
			
			if (!$importfailed)
			{
				$nzbCount++;
				@unlink($nzbFile);

				if (!empty($argc))
				{
					echo ("imported ".$nzbFile."\n");
					flush();
				}
				else
				{
					$retval.= "imported ".$nzbFile."<br />";
				}
			}
		}
	}
	$seconds = strtotime(date('Y-m-d H:i:s')) - strtotime($start);
	$retval.= 'Processed '.$nzbCount.' nzbs in '.$seconds.' second(s)';

	if (!empty($argc))
	{
		echo $retval."\n";
		die();
	}
	$page->smarty->assign('output', $retval);	
}

$page->title = "Import Nzbs";
$page->content = $page->smarty->fetch('nzb-import.tpl');
$page->render();

?>
