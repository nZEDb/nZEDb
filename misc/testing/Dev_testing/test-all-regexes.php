<?php
if (!isset($argv[1]))
	exit("This script will test a string(release name), single quoted, against all regexes in lib/namecleaning.php. To test a string run:\nphp test_all_regexes.php '[Samurai.Warriors.3.PROPER.USA.Wii-CLANDESTiNE-Scrubbed-xeroxmalf]-[#a.b.g.w@efnet]-[www.abgx.net]-[001/176] - \"Samurai.Warriors.3.PROPER.USA.Wii-CLANDESTiNE-Scrubbed-xeroxmalf.par2\" yEnc'\n");
require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'ColorCLI.php';
require_once nZEDb_LIB . 'groups.php';

passthru('clear');

$c = new ColorCLI;

$file = nZEDb_WWW.'lib/namecleaning.php';
$handle = fopen($file, "r");

$test_str = $argv[1];

$e0 = '([-_](proof|sample|thumbs?))*(\.part\d*(\.rar)?|\.rar)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|")';
$e1 = $e0.' yEnc$/';
$groupName = '';

if (isset($argv[2]) && is_numeric($argv[2]))
{
	$groups = new Groups();
	$group = $groups->getByNameByID($argv[2]);
}

if ($handle)
{
	echo $c->header($argv[1]);
	if (isset($groupName))
		echo $c->primary($group."\n");
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
					echo $c->header("Group regex => ".$groupName);
				else
					echo $c->header("Group regex => collectionCleaner");
				if ($match1)
					echo $c->primary($regex);
				if (isset($match1[1]) && $match1[1] != '' && $match1[1] != '"')
					echo $c->primary("match[1]->".$match1[1]);
				if (isset($match1[2]) && $match1[2] != '' && $match1[2] != '"')
					echo $c->primary("match[2]->".$match1[2]);
				if (isset($match1[3]) && $match1[3] != '' && $match1[3] != '"')
					echo $c->primary("match[3]->".$match1[3]);
				if (isset($match1['title']) && $match1['title'] != '' && $match1['title'] != '"')
					echo $c->primary("match['title']->".$match1['title']);
				echo "\n\n\n";
			}
		}
	}
}
else
{
	echo $c->error("bad file path?\n");
}

$file = nZEDb_MISC . 'testing/Dev_testing/renametopre.php';
$handle = fopen($file, "r");

$test_str = $argv[1];

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
					echo $c->header("Group regex => ".$groupName."\n");
				if ($match1)
					echo $c->primary($regex);
				if (isset($match1[1]) && $match1[1] != '' && $match1[1] != '"')
					echo $c->primary("match[1]->".$match1[1]);
				if (isset($match1[2]) && $match1[2] != '' && $match1[2] != '"')
					echo $c->primary("match[2]->".$match1[2]);
				if (isset($match1[3]) && $match1[3] != '' && $match1[3] != '"')
					echo $c->primary("match[3]->".$match1[3]);
				if (isset($match1['title']) && $match1['title'] != '' && $match1['title'] != '"')
					echo $c->primary("match['title']->".$match1['title']);
				echo "\n\n\n";
			}
		}
	}
}

