<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

passthru("clear");
$c= new ColorCLI();

if (!isset($argv[1]) || (isset($argv[1]) && $argv[1] != "site" && $argv[1] != "tmux")) {
	exit($c->error("\nThis script will output the setting of your site-edit or tmux-edit page to share with others. This will ouptut directly to web using pastebinit. This does not post any private information.\nTo run:\nphp export_settings.php [site, tmux] [tabbed, html, csv]\n"));
}

if (!command_exist('pastebinit')) {
	exit($c->error("This script requires pastebinit, but it's not installed. Aborting.\n"));
}

switch (strtolower($argv[1]))
{
	case 'site':
		$sql = "SELECT * FROM site WHERE setting NOT LIKE '%key%' AND setting NOT LIKE '%google%' AND setting NOT LIKE '%seed%' AND setting NOT LIKE '%amazon%' AND setting != 'saburl' AND setting != 'adheader' AND setting != 'adbrowse' AND setting != 'addetail' AND setting != 'request_url'";
		break;
	case 'tmux':
		$sql = 'SELECT * FROM tmux';
		break;
	default:
		$sql = '';
}

if ($sql != '') {
	$output = '';
	$style = isset($argv[2]) ? strtolower($argv[2]) : '';
	switch ($style)
	{
		case 'html':
			$mask = "\t\t<tr><td>%s</td><td>%s</td></tr>\n";
			$output = "<table>\n\t<thead>\n\t\t<tr>\n\t\t\t<th>Setting</th>\n\t\t\t<th>Value</th>\n\t\t</tr>\n\t</thead>\n\t<tbody>\n";
			break;
		case 'csv':
			$mask = "'%s','%s'\n";
			break;
		default:
		case 'tabbed':
		case 'tabulated':
			$mask = "%-30s... %-125s\n";
	}

	$db = new DB();
	$res = $db->queryDirect($sql);
	@unlink("xdfrexgvtedvgb.uhdntef");

	foreach ($res as $setting)
	{
		$line = sprintf($mask, $setting['setting'], $setting['value']);
		if (nZEDb_DEBUG) {
			echo $line;
		}
		$output .= $line;
	}

	if ($style == 'html') {
		$line .= "\t</tbody>\n</table>\n";
		$output .= $line;
	}

	file_put_contents("xdfrexgvtedvgb.uhdntef", $output, FILE_APPEND);

	if (file_exists("xdfrexgvtedvgb.uhdntef")) {
		passthru("pastebinit xdfrexgvtedvgb.uhdntef");
	}
	@unlink("xdfrexgvtedvgb.uhdntef");
}

function command_exist($cmd)
{
	if (strtolower(PHP_OS) == 'windows') {
		echo "This script currently does not work on Windows ;-(";
		return false;
	}
	$returnVal = exec("which $cmd 2>/dev/null");
	return (empty($returnVal) ? false : true);
}
