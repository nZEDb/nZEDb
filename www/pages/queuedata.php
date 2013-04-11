<?php
require_once(WWW_DIR."/lib/sabnzbd.php");

if (!$users->isLoggedIn())
	$page->show403();

$sab = new SABnzbd($page);

if (empty($sab->url))
	$page->show404();

if (empty($sab->apikey))
	$page->show404();
	
$output = "";

$json = $sab->getQueue();
if ($json !== false)
{
	$obj = json_decode($json);
	$queue = $obj->{'jobs'};
	$count = 1;
	
	$speed = $obj->{'speed'};
	$queued = round($obj->{'mbleft'}, 2)."MB / ".round($obj->{'mb'}, 2)."MB";
	$status = ucwords(strtolower($obj->{'state'}));
	
	$output .= "<p><b>Download speed:</b> ".$speed."B/s - <b>Queued:</b> ".$queued." - <b>Status:</b> ".$status."</p>";
	
	if (count($queue) > 0)
	{
		$output.="<table class=\"data highlight\">";
		$output.="<tr>
		<th></th>
		<th>Name</th>
		<th style='width:80px;'>size</th>
		<th style='width:80px;'>left</th>
		<th style='width:50px;'>%</th>
		<th>time left</th>
		<th></th>
		</tr>";
		foreach ($queue as $item)
		{
			if (strpos($item->{'filename'}, "fetch NZB") > 0)
			{
			}
			else
			{
				$output.="<tr>";
				$output.="<td style='text-align:right;'>".$count."</td>";
				$output.="<td>".$item->{'filename'}."</td>";
				$output.="<td style='text-align:right;'>".round($item->{'mb'}, 2)." MB</td>";
				$output.="<td class='right'>".round($item->{'mbleft'}, 2)." MB</td>";
				$output.="<td class='right'>".($item->{'mb'}==0?0:round($item->{'mbleft'}/$item->{'mb'}*100))."%</td>";
				$output.="<td style='text-align:right;'>".$item->{'timeleft'}."</td>";
				$output.="<td style='text-align:right;'><a  onclick=\"return confirm('Are you sure?');\" href='?del=".$item->{'id'}."'>delete</a></td>";
				$output.="</tr>";
				$count++;
			}
		}
		$output.="</table>";
	}
	else
	{
		$output.="<p>The queue is currently empty.</p>";
	}
}
else
{
	$output.="<p>Error retreiving queue.</p>";
}

print $output;
?>