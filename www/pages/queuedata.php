<?php
require_once(WWW_DIR."lib/sabnzbd.php");

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
	$load = $obj->{'loadavg'};
	$space1 = $obj->{'diskspace1'};
	$space2 = $obj->{'diskspace2'};

	$output .= "<div class='container text-center'><div class='col-2'><b>Download speed:</b><br> ".$speed."B/s </div><div class='col-2'><b>Queued:</b><br> ".$queued." </div><div class='col-2'><b>Status:</b><br> ".$status." </div><div class='col-2'><b>Server stats:</b><br> ".$load." </div><div class='col-2'><b>Space left download dir:</b><br> ".round($space1)."GB </div><div class='col-2'><b>Complete dir:</b><br> ".round($space2)."GB</p></div></div>";
	if (count($queue) > 0)
	{
		$output.="<table class=\"table table-striped table-condensed table-highlight data\">";
		$output.="<tr><thead>
		<th></th>
		<th>Name</th>
		<th style='width:80px;text-align:center;'>size</th>
		<th style='width:80px;text-align:center;'>left</th>
		<th style='width:50px;text-align:center;'>%</th>
		<th style='text-align:center;'>time left</th>
		<th style='text-align:center;'>Delete</th>";
		$output.="<th style='text-align:center;'><a href='?pall'>Pause all</a></th>";
		$output.="<th style='text-align:center;'><a href='?rall'>Resume all</a></th></tr></thead><tbody>";
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
				$output.="<td style='text-align:center;'><a href='?pause=".$item->{'id'}."'>pause</a></td>";
				$output.="<td style='text-align:center;'><a href='?resume=".$item->{'id'}."'>resume</a></td>";
				$output.="</tr>";
				$count++;
			}
		}
		$output.="</tbody></table>";
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

