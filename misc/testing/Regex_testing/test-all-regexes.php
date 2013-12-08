<?php
if (!isset($argv[1]))
	exit("This script will test a string(release name), single quoted, against all regexes in lib/namecleaning.php. To test a string run:\nphp test_all_regexes.php '[Samurai.Warriors.3.PROPER.USA.Wii-CLANDESTiNE-Scrubbed-xeroxmalf]-[#a.b.g.w@efnet]-[www.abgx.net]-[001/176] - \"Samurai.Warriors.3.PROPER.USA.Wii-CLANDESTiNE-Scrubbed-xeroxmalf.par2\" yEnc'\n");
require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'ColorCLI.php';
require_once nZEDb_LIB . 'groups.php';

passthru('clear');

if ($argv[1] == 'file' && isset($argv[2]) && file_exists($argv[2]))
{
	$filename = $argv[2];
	$fp = fopen( $filename, "r" ) or die("Couldn't open $filename");
	while ( ! feof( $fp ) ) {
		$line = fgets( $fp, 1024 );
		$pieces = explode('                    ', $line);
		if (isset($pieces[0]) && isset($pieces[1]))
		{
			$groups = new Groups();
			$group = $groups->getByNameByID($pieces[0]);
			test_regex($pieces[1], $group, $argv);
			echo "\n\n\n";
		}
	}
}
else if ($argv[1] == 'file')
	exit("The file $argv[1] does not exist or and invalid file was specified.\n");

if (isset($argv[2]) && is_numeric($argv[2]) && $argv[1] != 'file')
{
	$groups = new Groups();
	$group = $groups->getByNameByID($argv[2]);
	test_regex($argv[1], $group, $argv);
}
else if ($argv[1] != 'file')
	test_regex($argv[1], null, $argv);

function print_str($type, $str, $argv)
{
	if ($argv[1] != 'file')
	{
		$c = new ColorCLI;
		if ($type == "primary")
			echo $c->primary($str);
		else
			echo $c->header($str);
	}
	else
		echo $str."\n";
}

function test_regex($name, $group, $argv)
{
	$file = nZEDb_WWW.'lib/namecleaning.php';
	$handle = fopen($file, "r");

	$test_str = $name;

	$e0 = '([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|")';
	$e1 = $e0.' yEnc$/';
	$groupName = '';

	if ($handle)
	{
		print_str('header', $name, $argv);
		if (!is_null($group))
			print_str('primary', $group."\n", $argv);
		while (($line = fgets($handle)) !== false)
		{
			$line = preg_replace('/\$this->e0/', $e0, $line);
			$line = preg_replace('/\$this->e1/', $e1, $line);
			if (preg_match('/if \(\$groupName === "(.+)"\)/', $line, $matchName) || preg_match('/public function (.+)\(\)/', $line, $matchName))
				$groupName = $matchName[1];
			else if (preg_match('/if \(preg_match\(\'(.+)\', \$this->subject\, \$match\)\)/', $line, $match) || preg_match('/if \(preg_match\(\'(.+)\', \$subject\, \$match\)\)/', $line, $match))
			{
				$regex = $match[1];
				if (preg_match($regex, $test_str, $match1))
				{
					if ($groupName != '')
						print_str('header', "Group regex => ".$groupName, $argv);
					else
						print_str('header', "Group regex => collectionCleaner", $argv);
					if ($match1)
						print_str('primary', $regex, $argv);
					if (isset($match1[1]) && $match1[1] != '' && $match1[1] != '"')
						print_str('primary', "match[1]->".$match1[1], $argv);
					if (isset($match1[2]) && $match1[2] != '' && $match1[2] != '"')
						print_str('primary', "match[2]->".$match1[2], $argv);
					if (isset($match1[3]) && $match1[3] != '' && $match1[3] != '"')
						print_str('primary', "match[3]->".$match1[3], $argv);
					if (isset($match1['title']) && $match1['title'] != '' && $match1['title'] != '"')
						print_str('primary', "match['title']->".$match1['title'], $argv);
					echo "\n";
				}
			}
		}
	}

	$file = nZEDb_MISC . 'testing/Dev_testing/renametopre.php';
	$handle = fopen($file, "r");

	$test_str = $name;

	$e0 = '([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|")';
	$e1 = $e0.' yEnc$/';
	$groupName = 'renametopre';

	if ($handle)
	{
		while (($line = fgets($handle)) !== false)
		{
			$line = preg_replace('/\$this->e0/', $e0, $line);
			$line = preg_replace('/\$this->e1/', $e1, $line);
			if (preg_match('/if \(preg_match\(\'(.+)\', \$this->subject\, \$match\)\)/', $line, $match) || preg_match('/if \(preg_match\(\'(.+)\', \$subject\, \$match\)\)/', $line, $match))
			{
				$regex = $match[1];
				if (preg_match($regex, $test_str, $match1))
				{
					if ($groupName != '')
						print_str('header', "Group regex => ".$groupName, $argv);
					if ($match1)
						print_str('primary', $regex, $argv);
					if (isset($match1[1]) && $match1[1] != '' && $match1[1] != '"')
						print_str('primary', "match[1]->".$match1[1], $argv);
					if (isset($match1[2]) && $match1[2] != '' && $match1[2] != '"')
						print_str('primary', "match[2]->".$match1[2], $argv);
					if (isset($match1[3]) && $match1[3] != '' && $match1[3] != '"')
						print_str('primary', "match[3]->".$match1[3], $argv);
					if (isset($match1['title']) && $match1['title'] != '' && $match1['title'] != '"')
						print_str('primary', "match['title']->".$match1['title'], $argv);
					echo "\n";
				}
			}
		}
	}

	$file = nZEDb_MISC . '../apre.php';
	$handle = fopen($file, "r");

	$test_str = $name;

	$e0 = '([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|")';
	$e1 = $e0.' yEnc$/';
	$groupName = 'apre';
	if ($handle)
	{
		while (($line = fgets($handle)) !== false)
		{
			$line = preg_replace('/\$this->e0/', $e0, $line);
			$line = preg_replace('/\$this->e1/', $e1, $line);
			if (preg_match('/if \(preg_match\(\'(.+)\', \$this->subject\, \$match\)\)/', $line, $match) || preg_match('/if \(preg_match\(\'(.+)\', \$subject\, \$match\)\)/', $line, $match))
			{
				$regex = $match[1];
				if (preg_match($regex, $test_str, $match1))
				{
					if ($groupName != '')
						print_str('header', "Group regex => ".$groupName, $argv);
					if ($match1)
						print_str('primary', $regex, $argv);
					if (isset($match1[1]) && $match1[1] != '' && $match1[1] != '"')
						print_str('primary', "match[1]->".$match1[1], $argv);
					if (isset($match1[2]) && $match1[2] != '' && $match1[2] != '"')
						print_str('primary', "match[2]->".$match1[2], $argv);
					if (isset($match1[3]) && $match1[3] != '' && $match1[3] != '"')
						print_str('primary', "match[3]->".$match1[3], $argv);
					if (isset($match1['title']) && $match1['title'] != '' && $match1['title'] != '"')
						print_str('primary', "match['title']->".$match1['title'], $argv);
					echo "\n";
				}
			}
		}
	}
}
