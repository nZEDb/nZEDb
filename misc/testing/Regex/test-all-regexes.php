<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

if ($argc == 1) {
	exit("This script will test a string(release name), single quoted, against all regexes in NameCleaning.php. To test a string run:\nphp test_all_regexes.php '[Samurai.Warriors.3.PROPER.USA.Wii-CLANDESTiNE-Scrubbed-xeroxmalf]-[#a.b.g.w@efnet]-[www.abgx.net]-[001/176] - \"Samurai.Warriors.3.PROPER.USA.Wii-CLANDESTiNE-Scrubbed-xeroxmalf.par2\" yEnc'\n");
}


passthru('clear');
if ($argv[1] == 'file' && isset($argv[2]) && file_exists($argv[2])) {
	$filename = $argv[2];
	$fp = fopen($filename, "r") or die("Couldn't open $filename");
	while (!feof($fp)) {
		$line = fgets($fp, 1024);
		$pieces = explode('                    ', $line);
		if (isset($pieces[0]) && isset($pieces[1])) {
			$groups = new \Groups();
			$group = $groups->getByNameByID($pieces[0]);
			test_regex($pieces[1], $group, $argv);
			echo "\n\n\n";
		}
	}
} else if ($argv[1] == 'file') {
	exit("The file $argv[1] does not exist or and invalid file was specified.\n");
}

if (isset($argv[2]) && is_numeric($argv[2]) && $argv[1] != 'file') {
	$groups = new \Groups();
	$group = $groups->getByNameByID($argv[2]);
	test_regex($argv[1], $group, $argv);
} else if ($argv[1] != 'file') {
	test_regex($argv[1], null, $argv);
}

function print_str($type, $str, $argv)
{
	if ($argv[1] != 'file') {
		$cli = new \ColorCLI();
		if ($type == "primary") {
			echo $cli->primary($str);
		} else if ($type == "alternate") {
			echo $cli->alternate($str);
		} else {
			echo $cli->header($str);
		}
	} else {
		echo $str . "\n";
	}
}

function test_regex($name, $group, $argv)
{
	$file = nZEDb_LIB . '/controllers/ReleaseCleaning.php';
	/* TODO: add CollectionCleaning */
	$handle = fopen($file, "r");
	$test_str = $name;
	$e0 = '([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|")';
	$e1 = $e0 . '[- ]{0,3}yEnc$/';
	$e2 = $e0 . '[- ]{0,3}\d+[.,]\d+ [kKmMgG][bB][- ]{0,3}yEnc$/';
	$groupName = '';
	if ($handle) {
		print_str('header', $name, $argv);
		if (!is_null($group)) {
			print_str('primary', $group . "\n", $argv);
		}
		while (($line1 = fgets($handle)) !== false) {
			$line3 = preg_replace('/\' \. \$this->e0 \. \'/', $e0, $line1);
			$line2 = preg_replace('/\' \. \$this->e1/', $e1 . '\'', $line3);
			$line = preg_replace('/\' \. \$this->e2/', $e2 . '\'', $line2);
			if (preg_match('/public function (.+)\(\)/', $line, $matchName)) {
				$groupName = $matchName[1];
			} else if (preg_match('/if \(preg_match\(\'(.+)\', \$this->subject\, \$match\)\)/', $line, $match)) {
				$regex = $match[1];
				$match1 = array();
				if (preg_match($regex, $test_str, $match1)) {
					if ($groupName != '') {
						print_str('header', "Group regex => " . $groupName, $argv);
					} else {
						print_str('header', "Group regex => ReleaseCleaner", $argv);
					}
					if ($match1) {
						print_str('alternate', $regex, $argv);
					}
					if (isset($match1[1]) && $match1[1] != '' && $match1[1] != '"') {
						print_str('primary', "match[1]->" . $match1[1], $argv);
					}
					if (isset($match1[2]) && $match1[2] != '' && $match1[2] != '"') {
						print_str('primary', "match[2]->" . $match1[2], $argv);
					}
					if (isset($match1[3]) && $match1[3] != '' && $match1[3] != '"') {
						print_str('primary', "match[3]->" . $match1[3], $argv);
					}
					if (isset($match1['title']) && $match1['title'] != '' && $match1['title'] != '"') {
						print_str('primary', "match['title']->" . $match1['title'], $argv);
					}
					echo "\n";
				}
			}
		}
	}
	$file = nZEDb_MISC . 'testing/Dev/renametopre.php';
	$handle1 = fopen($file, "r");
	$test_str1 = $name;
	$e01 = '([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|")';
	$e11 = $e01 . ' yEnc$/';
	$e12 = $e01 . '[- ]{0,3}\d+[.,]\d+ [kKmMgG][bB][- ]{0,3}yEnc$/';
	$groupName1 = 'renametopre';
	if ($handle1) {
		while (($line1 = fgets($handle1)) !== false) {
			$line3 = preg_replace('/\' \. \$this->e0 \. \'/', $e01, $line1);
			$line2 = preg_replace('/\' \. \$this->e1/', $e11 . '\'', $line3);
			$line = preg_replace('/\' \. \$this->e2/', $e12 . '\'', $line2);
			if (preg_match('/if \(preg_match\(\'(.+)\', \$this->subject\, \$match\)\)/', $line, $match) || preg_match('/if \(preg_match\(\'(.+)\', \$subject\, \$match\)\)/', $line, $match)) {
				$regex = $match[1];
				$match1 = array();
				if (preg_match($regex, $test_str1, $match1)) {
					if ($groupName != '') {
						print_str('header', "Group regex => " . $groupName1, $argv);
					}
					if ($match1) {
						print_str('alternate', $regex, $argv);
					}
					if (isset($match1[1]) && $match1[1] != '' && $match1[1] != '"') {
						print_str('primary', "match[1]->" . $match1[1], $argv);
					}
					if (isset($match1[2]) && $match1[2] != '' && $match1[2] != '"') {
						print_str('primary', "match[2]->" . $match1[2], $argv);
					}
					if (isset($match1[3]) && $match1[3] != '' && $match1[3] != '"') {
						print_str('primary', "match[3]->" . $match1[3], $argv);
					}
					if (isset($match1['title']) && $match1['title'] != '' && $match1['title'] != '"') {
						print_str('primary', "match['title']->" . $match1['title'], $argv);
					}
					echo "\n";
				}
			}
		}
	}
}
