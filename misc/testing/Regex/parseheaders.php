<?php

require_once dirname(__FILE__) . '/../../../www/config.php';

$group = $argv[1];
$cleaner = new \ReleaseCleaning();

if (isset($argv[1]) && file_exists($argv[1])) {
	$filename = $argv[1];
	$fp = fopen($filename, "r") or die("Couldn't open $filename");
	$groups = new \Groups();
	$group = str_replace('.txt', '', basename($filename));
	@unlink(nZEDb_RES . 'logs' . DS . 'not_yenc' . DS . $group . ".failed.regex.txt");
	while (!feof($fp)) {
		$line = fgets($fp, 1024);
		if (preg_match('/^(.+yEnc).+\(\d+\/\d+\)/', $line, $matches)) {
			$clean = $cleaner->releaseCleaner($matches[1], '', 0, $group);
			if (is_array($clean)) {
				file_put_contents(
					nZEDb_RES . 'logs' . DS . 'not_yenc' . DS . $group .
					'.failed.regex.txt' , $line . "\n", FILE_APPEND
				);
			}
			/*$clean = preg_replace(array('/\d+\.mp3"/', '/\.jpg"/', '/\.nzb"/', '/\.sfv"/', '/\.srr"/', '/\.mp4"/', '/\.m4b"/', '/\.mov"/', '/\.mkv"/', '/\.avi"/', '/\.m4v"/', '/\.nfo"/', '/\.mkv"/', '/\.par2"/i', '/\.r\d+"/', '/\.rar"/i', '/\.vol\d+\+\d+"/i'), '"', $matches[1]);
			$clean = preg_replace(array('/\.mp3"/', '/\.sample"/', '/-sample"/', '/\.part\d+"/'), '"', $clean);
			$clean = preg_replace(array('/Bk \d+/', '/Book \d+/', '/\d+[.,]\d+ [MKG]B/i', '/sample-/i', '/\d+-\d+/'), '', $clean);
			$clean = preg_replace('/"\d\d/', '"', $clean);
			if (preg_match('/(.*\[)\d+(\/\d+\].*)/', $clean, $grouped)) {
				$clean = $grouped[1] . $grouped[2];
			}
			if (preg_match('/(.*\()\d+(\/\d+\).*)/', $clean, $grouped)) {
				$clean = $grouped[1] . $grouped[2];
			}
			if (preg_match('/(.+File )\d+( of \d+.+)/i', $clean, $grouped)) {
				$clean = $grouped[1] . $grouped[2];
			}
			$clean = preg_replace('/\s{2,}/', ' ', $clean);*/
			//if (is_array($clean) || (!is_array($clean) && preg_match('/yEnc/', $clean)))
			//{
			//echo $clean."\n";
			//usleep(25000);
			//file_put_contents(nZEDb_RES . 'logs' . DS .  "not_yenc" . DS . $group . ".failed.regex.txt", $line . "\n", FILE_APPEND);
			//}
		}
	}
}
