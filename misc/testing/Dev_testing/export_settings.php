<?php
require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'ColorCLI.php';

passthru("clear");
$c= new ColorCLI();

if (!isset($argv[1]) || ($argv[1] != "site" && $argv[1] != "tmux")) {
	echo $c->error("\nThis script will output the setting of your site-edit or tmux-edit page to share with others. This will ouptut directly to web using pastebinit. This does not post any private information.\nTo run:\nphp export_settings.php [site, tmux]\n");
}

if (!command_exist('pastebinit')) {
	if (!nZEDb_DEBUG) {
		exit($c->error("This script requires pastebinit, but it's not installed. Aborting.\n"));
	}
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

if ($sql != '')
{
	$db = new DB();
	$mask = "\t\t<td>%s</td>\n\t\t<td>%s</td>\n";
	$res = $db->queryDirect($sql);
	@unlink("xdfrexgvtedvgb.uhdntef");

	$html = "<table>\n\t<thead>\n\t<tr>\n\t\t\t<th>Setting</th>\n\t\t\t<th>Value</th>\n\t\t</tr>\n	</thead>\n\t<tbody>\n";
	if (nZEDb_DEBUG) {
		echo $html;
	}

	foreach ($res as $setting)
	{
		$line .= sprintf($mask, $setting['setting'], $setting['value']);
		if (nZEDb_DEBUG) {
			echo $line;
		}
		$html .= $line;
	}
	$line .= "\t</tbody>\n</table>\n";
	if (nZEDb_DEBUG) {
		echo $line;
	}
	$html .= $line;

	if (!nZEDb_DEBUG) {
		file_put_contents("xdfrexgvtedvgb.uhdntef", $html, FILE_APPEND);
	}

	if (file_exists("xdfrexgvtedvgb.uhdntef") && !nZEDb_DEBUG) {
		passthru("pastebinit xdfrexgvtedvgb.uhdntef");
	}
	@unlink("xdfrexgvtedvgb.uhdntef");
}

function command_exist($cmd) {
	$returnVal = exec("which $cmd 2>/dev/null");
	return (empty($returnVal) ? false : true);
}
?>
