<?php
require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'ColorCLI.php';

passthru("clear");
$c= new ColorCLI();

if (!isset($argv[1]) || ($argv[1] != "site" && $argv[1] != "tmux"))
	echo $c->error("\nThis script will output the setting of your site-edit or tmux-edit page to share with others. This will ouptut directly to web using pastebinit. This does not post any private information.\nTo run:\nphp export_settings.php [site, tmux]\n");

if (!command_exist('pastebinit'))
	exit($c->error("This script requires pastebinit, but it's not installed. Aborting.\n"));

function command_exist($cmd) {
	$returnVal = exec("which $cmd 2>/dev/null");
	return (empty($returnVal) ? false : true);
}

$db = new DB();
if ($argv[1] == 'tmux')
{
	$mask = "%-30s... %-125s\n";
	$res = $db->queryDirect('SELECT * FROM tmux');
	@unlink("xdfrexgvtedvgb.uhdntef");
	foreach ($res as $setting)
	{
		$line = sprintf($mask, $setting['setting'], $setting['value']);
		file_put_contents("xdfrexgvtedvgb.uhdntef", $line, FILE_APPEND);
	}
	if (file_exists("xdfrexgvtedvgb.uhdntef"))
		passthru("pastebinit xdfrexgvtedvgb.uhdntef");
	@unlink("xdfrexgvtedvgb.uhdntef");
}

if ($argv[1] == 'site')
{
	$mask = "%-30s... %-125s\n";
	$res = $db->queryDirect("SELECT * FROM site WHERE setting NOT LIKE '%key%' AND setting NOT LIKE '%google%' AND setting NOT LIKE '%seed%' AND setting NOT LIKE '%amazon%' AND setting != 'saburl' AND setting != 'adheader' AND setting != 'adbrowse' AND setting != 'addetail' AND setting != 'request_url'");
	@unlink("xdfrexgvtedvgb.uhdntef");
	foreach ($res as $setting)
	{
		$line = sprintf($mask, $setting['setting'], $setting['value']);
		file_put_contents("xdfrexgvtedvgb.uhdntef", $line, FILE_APPEND);
	}
	if (file_exists("xdfrexgvtedvgb.uhdntef"))
		passthru("pastebinit xdfrexgvtedvgb.uhdntef");
	@unlink("xdfrexgvtedvgb.uhdntef");
}
