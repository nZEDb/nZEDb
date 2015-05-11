<?php

use nzedb\NZBGet;
use nzedb\utility\Utility;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}


$nzbget = new NZBGet($page);

$output = "";
$data = $nzbget->getQueue();

if ($data !== false) {
	if (count($data > 0)) {

		$status = $nzbget->status();

		if ($status !== false) {
			$output .=
			"<div class='container text-center' style='display:block;'>
				<div style='width:16.666666667%;float:left;'><b>Avg Speed:</b><br /> " . Utility::bytesToSizeString($status['AverageDownloadRate'], 2) . "/s </div>
				<div style='width:16.666666667%;float:left;'><b>Speed:</b><br /> " . Utility::bytesToSizeString($status['DownloadRate'], 2) . "/s </div>
				<div style='width:16.666666667%;float:left;'><b>Limit:</b><br /> " . Utility::bytesToSizeString($status['DownloadLimit'], 2) . "/s </div>
				<div style='width:16.666666667%;float:left;'><b>Queue Left(no pars):</b><br /> " . Utility::bytesToSizeString($status['RemainingSizeLo'], 2) . " </div>
				<div style='width:16.666666667%;float:left;'><b>Free Space:</b><br /> " . Utility::bytesToSizeString($status['FreeDiskSpaceMB'] * 1024000, 2) . " </div>
				<div style='width:16.666666667%;float:left;'><b>Status:</b><br /> " . ($status['Download2Paused'] == 1 ? 'Paused' : 'Downloading') . " </div>
			</div>";
		}

		$count = 1;
		$output .=
			"<table class='table table-striped table-condensed table-highlight data'>
				<thead>
					<tr >
						<th style='width=10px;text-align:center;'>#</th>
						<th style='text-align:left;'>Name</th>
						<th style='width:80px;text-align:center;'>Size</th>
						<th style='width:80px;text-align:center;'>Left(+pars)</th>
						<th style='width:50px;text-align:center;'>Done</th>
						<th style='width:80px;text-align:center;'>Status</th>
						<th style='width:50px;text-align:center;'>Delete</th>
						<th style='width:80px;text-align:center;'><a href='?pall'>Pause all</a></th>
						<th style='width:80px;text-align:center;'><a href='?rall'>Resume all</a></th>
					</tr>
				</thead>
				<tbody>";

		foreach ($data as $item) {
			$output .=
				"<tr>" .
				"<td style='text-align:center;width:10px'>" . $count . "</td>" .
				"<td style='text-align:left;'>" . $item['NZBName'] . "</td>" .
				"<td style='text-align:center;'>" . $item['FileSizeMB'] . " MB</td>" .
				"<td style='text-align:center;'>" . $item['RemainingSizeMB'] . " MB</td>" .
				"<td style='text-align:center;'>" . ($item['FileSizeMB'] == 0 ? 0 : round(100 - ($item['RemainingSizeMB'] / $item['FileSizeMB']) * 100)) . "%</td>" .
				"<td style='text-align:center;'>" . ($item['ActiveDownloads'] > 0 ? 'Downloading' : 'Paused') . "</td>" .
				"<td style='text-align:center;'><a  onclick=\"return confirm('Are you sure?');\" href='?del=" . $item['LastID'] . "'>Delete</a></td>" .
				"<td style='text-align:center;'><a href='?pause=" . $item['LastID'] . "'>Pause</a></td>" .
				"<td style='text-align:center;'><a href='?resume=" . $item['LastID'] . "'>Resume</a></td>" .
				"</tr>";
			$count++;
		}
		$output .=
			"</tbody>
		</table>";
	} else {
		$output .= "<br /><br /><p style='text-align:center;'>The queue is currently empty.</p>";
	}
} else {
	$output .= "<p style='text-align:center;'>Error retreiving queue.</p>";
}

print $output;
