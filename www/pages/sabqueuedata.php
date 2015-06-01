<?php

use nzedb\SABnzbd;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

$sab = new SABnzbd($page);

$output = "";

$json = $sab->getQueue();

if ($json !== false) {
	$obj = json_decode($json);
	$queue = $obj->{'jobs'};
	$count = 1;

	$output .=
		"<div class='container text-center' style='display:block;'>
			<div style='width:16.666666667%;float:left;'><b>Speed:</b><br /> " . $obj->{'speed'} . "B/s </div>
			<div style='width:16.666666667%;float:left;'><b>Queued:</b><br /> " . round($obj->{'mbleft'}, 2) . "MB / " . round($obj->{'mb'}, 2) . "MB" . " </div>
			<div style='width:16.666666667%;float:left;'><b>Status:</b><br /> " . ucwords(strtolower($obj->{'state'})) . " </div>
			<div style='width:16.666666667%;float:left;'><b>Free (temp):</b><br /> " . round($obj->{'diskspace1'}) . "GB </div>
			<div style='width:16.666666667%;float:left;'><b>Free Space:</b><br /> " . round($obj->{'diskspace2'}) . "GB</div>
			<div style='width:16.666666667%;float:left;'><b>Stats:</b><br /> " . preg_replace('/\s+\|\s+| /', ',', $obj->{'loadavg'}) . " </div>
		</div>";

	if (count($queue) > 0) {
		$output .=
			"<table class='table table-striped table-condensed table-highlight data'>
				<thead>
					<tr >
						<th style='width=10px;text-align:center;'>#</th>
						<th style='text-align:left;'>Name</th>
						<th style='width:80px;text-align:center;'>Size</th>
						<th style='width:80px;text-align:center;'>Left</th>
						<th style='width:50px;text-align:center;'>Done</th>
						<th style='width:80px;text-align:center;'>Time Left</th>
						<th style='width:50px;text-align:center;'>Delete</th>
						<th style='width:80px;text-align:center;'><a href='?pall'>Pause all</a></th>
						<th style='width:80px;text-align:center;'><a href='?rall'>Resume all</a></th>
					</tr>
				</thead>
				<tbody>";

		foreach ($queue as $item) {
			if (strpos($item->{'filename'}, "fetch NZB") === false) {
				$output .=
					"<tr>" .
						"<td style='text-align:center;width:10px'>" . $count . "</td>" .
						"<td style='text-align:left;'>" . $item->{'filename'} . "</td>" .
						"<td style='text-align:center;'>" . round($item->{'mb'}, 2) . " MB</td>" .
						"<td style='text-align:center;'>" . round($item->{'mbleft'}, 2) . " MB</td>" .
						"<td style='text-align:center;'>" . ($item->{'mb'} == 0 ? 0 : round(100 - ($item->{'mbleft'} / $item->{'mb'}) * 100)) . "%</td>" .
						"<td style='text-align:center;'>" . $item->{'timeleft'} . "</td>" .
						"<td style='text-align:center;'><a  onclick=\"return confirm('Are you sure?');\" href='?del=" . $item->{'id'} . "'>Delete</a></td>" .
						"<td style='text-align:center;'><a href='?pause=" . $item->{'id'} . "'>Pause</a></td>" .
						"<td style='text-align:center;'><a href='?resume=" . $item->{'id'} . "'>Resume</a></td>" .
					"</tr>";
				$count++;
			}
		}
		$output .=
				"</tbody>
			</table>";
	} else {
		$output .= "<br /><br /><p style='text-align:center;'>The queue is currently empty.</p>";
	}
} else {
	$output .= "<p style='text-align:center;'>Error retrieving queue.</p>";
}

print $output;
