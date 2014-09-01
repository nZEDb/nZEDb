<?php

require_once dirname(__FILE__) . '/../../../www/config.php';

if (!isset($argv[1])) {
	exit();
}

$group = $argv[1];
//$cleaner = new CollectionsCleaning();
$nntp = new \NNTP();
$cli = new \ColorCLI();
if ($nntp->doConnect() !== true) {
	exit($cli->error("Unable to connect to usenet."));
}

$number = 1000000;
//exec("tmux kill-session -t NNTPProxy");

$groupArr = $nntp->selectGroup($group);
if ($nntp->isError($groupArr) || !isset($groupArr['first']) || !isset($groupArr['last'])) {
	exit();
}

print_r($groupArr);
if (isset($argv[2]) && is_numeric($argv[2])) {
	$first = $argv[2];
} else
if ($groupArr['last'] - $number > $groupArr['first']) {
	$first = $groupArr['last'] - $number;
} else {
	$first = $groupArr['first'];
}
$last = $groupArr['last'];
@unlink(nZEDb_RES . 'logs' . DS . 'not_yenc' . DS . $group . '.txt');
@unlink(nZEDb_RES . 'logs' . DS . 'not_yenc' . DS . $group . '.failed.regex.txt');

$count = $last - $first;
echo "\nGrabbing " . $count . " headers from " . $argv[1] . "\n";

for ($x = $first; $x <= $last; $x += 5000) {
	$y = $x + 4999;

	echo "Grabbing " . $x . " -> " . $y . "\n";
	$msgs = $nntp->getXOVER($x . "-" . $y);

	foreach ($msgs as $msg) {
		//if (isset($msg[':bytes']))
		//	$bytes = $msg[':bytes'];
		//	else if (isset($msg['Bytes']))
		//		$bytes = $msg['Bytes'];
		//if (preg_match('/(.+yEnc)(\.\s*|\s*--\s*READ NFO!\s*|\s*)\((\d+)\/(\d+)\)$/', $msg['Subject'], $matches))
		//{
		//$clean = $cleaner->collectionsCleaner($matches[1], $group);
		///if (preg_match('/yEnc/', $clean))
		//{
		//$header = $msg['Number']."\t\t".$msg['Subject']."\t\t".$msg['From']."\t\t".$msg['Date']."\t\t".$msg['Message-ID']."\t\t".$bytes."\t\t".$msg['Xref']."\n";
		if (isset($msg['Subject'])) {
			$header = $msg['Subject'] . "\n";
			if (preg_match('/yEnc/', $header )) {
				echo $header;
			}
			file_put_contents(nZEDb_RES . 'logs' . DS . 'not_yenc' . DS . $group . '.txt', $header, FILE_APPEND);
			//var_dump($msg);
		} else {
			$fp = fopen(nZEDb_RES . 'logs' . DS . 'not_yenc' . DS . $group . '.txt', 'w');
			fwrite($fp, print_r($msg, TRUE));
			fclose($fp);
		}
		//}
		//}
	}
}

passthru("php /var/www/nZEDb/misc/testing/Regex/parseheaders.php ${group}");
//$msgs = $nntp->getOverview(29668562-29668572, true, false);
//var_dump($msgs);
/*
Array
(
    [group] => alt.binaries.tv
    [first] => 209648003
    [last] => 626029608
    [count] => 416381606
)

array(9) {
  ["Number"]=>
  string(9) "209648013"
  ["Subject"]=>
  string(121) "www.Bin-Req.net Presents: #54776 - Flashpoint.S01E06.720p.HDTV.x264-CTU - [33/41] - ctu-x264-flashpoint.106.rar (064/130)"
  ["From"]=>
  string(16) "Fake@address.com"
  ["Date"]=>
  string(24) "15 Aug 2008 04:00:08 GMT"
  ["Message-ID"]=>
  string(46) "<1218773073.20346.64@europe.news.astraweb.com>"
  ["References"]=>
  string(0) ""
  [":bytes"]=>
  string(6) "399164"
  [":lines"]=>
  string(4) "3066"
  ["Xref"]=>
  string(89) "number1.nntp.dca.giganews.com alt.binaries.multimedia:347528145 alt.binaries.tv:209648013"
}
*/
