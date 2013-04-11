<?php
require_once('config.php');

$type = "tv";
$reqid = array();

if (isset($_GET["t"]))
	$type = $_GET["t"];

if (isset($_GET["reqid"]))
	$reqid = explode(",",$_GET["reqid"]);

$result = mysql_query("select count(ID) as num from feed where code = '".mysql_real_escape_string($type)."'");
while ($row = mysql_fetch_assoc($result)) 
{
	if ($row["num"] == "0")
	{
		header("Content-type: text/xml");
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		echo "<error>no feed</error>";
		die();	
	}
}

//
// query can include multiple comma sep reqids
//
$reqstr = "(";
foreach ($reqid as $req)
	$reqstr.= " reqid = '".mysql_real_escape_string($req)."' or ";
$reqstr.= " 1=2) ";

$result = mysql_query("select * from item inner join feed on feed.ID = item.feedID where ".$reqstr." and feed.code = '".mysql_real_escape_string($type)."'");	

//
// build metadata about the item(s)
//
$ret = "<items>";
while ($row = mysql_fetch_assoc($result)) 
	$ret.="<item reqid=\"".$row["reqid"]."\" link=\"".cleanXML($row["link"])."\" date=\"".$row["pubdate"]."\" title=\"".cleanXML($row["title"])."\" />\n";
$ret.="</items>";

//
// output xml
//
header("Content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo $ret;
die();


function cleanXML($strin) 
{
	$strout = null;

	for ($i = 0; $i < strlen($strin); $i++) 
	{
		$ord = ord($strin[$i]);

		if (($ord > 0 && $ord < 32) || ($ord >= 127)) 
		{
			$strout .= "&amp;#{$ord};";
		}
		else 
		{
			switch ($strin[$i]) 
			{
				case '<':
					$strout .= '&lt;';
					break;
				case '>':
					$strout .= '&gt;';
					break;
				case '&':
					$strout .= '&amp;';
					break;
				case '"':
					$strout .= '&quot;';
					break;
				default:
					$strout .= $strin[$i];
			}
		}
	}

	return $strout;
}


?>