<?php

require_once dirname(__FILE__) . '/../../../../www/config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'tmux.php';
require_once nZEDb_LIB . 'site.php';

passthru("clear");

$db = new DB();
$DIR = MISC_DIR;

$limited = false;
if (isset($argv['1']) && $argv['1'] == "limited")
	$limited = true;

$t = new Tmux();
$tmux = $t->get();
$tmux_session = $tmux->tmux_session;
$seq = $tmux->sequential;
$powerline = $tmux->powerline;
$colors = $tmux->colors;
$import = $tmux->import;
$s = new Sites();
$site = $s->get();
$patch = $site->sqlpatch;
$hashcheck = $site->hashcheck;
$tablepergroup = (!empty($site->tablepergroup)) ? $site->tablepergroup : 0;

//check if session exists
$session = exec("echo `tmux list-sessions | grep $tmux_session | wc -l`");
if ($session != 0)
	exit("\033[1;33mtmux session:".$tmux_session." is already running, aborting.\033[0m\n\n");
else
	echo "The above is just a TMUX notice, it is saying TMUX, that you do not have a TMUX session currently running. It is not an error. It is TMUX\n";

function writelog($pane)
{
	$path = dirname(__FILE__)."/logs";
	$getdate = gmDate("Ymd");
	$tmux = new Tmux();
	$logs = $tmux->get()->write_logs;
	if ($logs == "TRUE")
	{
		return "2>&1 | tee -a $path/$pane-$getdate.log";
	}
	else
	{
		return "";
	}
}

if ($hashcheck != '1')
{
	echo "\033[1;33mWe have updated the way collections are created, the collection table has to be updated to use the new changes.\n";
	echo "php ${DIR}testing/DB_scripts/reset_Collections.php true\033[0m\n";
	exit(1);
}

if ($patch < '145')
{
	echo "\033[1;33mYour database is not up to date. Please update.\n";
	echo "php ${DIR}testing/DB_scripts/patchDB.php\033[0m\n";
	exit(1);
}

passthru("clear");

//remove folders from tmpunrar
$tmpunrar = $site->tmpunrarpath;
if ((count(glob("$tmpunrar/*",GLOB_ONLYDIR))) > 0)
{
	echo "Removing dead folders from ".$tmpunrar."\n";
	exec("rm -r ".$tmpunrar."/*");
}

function command_exist($cmd) {
	$returnVal = exec("which $cmd 2>/dev/null");
	return (empty($returnVal) ? false : true);
}

//check for apps
$apps = array("time", "tmux", "nice", "python", "tee");
foreach ($apps as &$value)
{
	if (!command_exist($value)) {
		echo "I require ".$value." but it's not installed. Aborting.\n";
		exit(1);
	}
}

function python_module_exist($module) {
	exec("python -c \"import $module\"", $output, $returnCode);
	return ($returnCode == 0 ? true : false);
}

$nntpproxy = $site->nntpproxy;
if ($nntpproxy == '1')
{
	$modules = array("nntp", "socketpool");
	foreach ($modules as &$value)
	{
		if (!python_module_exist($value)) {
			echo "NNTP Proxy requires ".$value." python module but it's not installed. Aborting.\n";
			exit(1);
		}
	}
}

//reset collections dateadded to now
print("Resetting expired collections and nzbs dateadded to now. This could take a minute or two. Really.\n");
if ($tablepergroup == 1)
{
	$sql = "SHOW tables";
	$tables = $db->query($sql);
	$ran = 0;
	foreach($tables as $row)
	{
		$tbl = $row[0];
		if (preg_match('/\d+_collections/',$tbl))
		{
			$run = $db->prepare('UPDATE '.$tbl.' SET dateadded = now()');
			$run->execute();
			$ran += $run->rowCount();
		}
	}
	echo $ran." collections reset\n";
}
else
{
	$run = $db->prepare("update collections set dateadded = now()");
	$run->execute();
	echo $run->rowCount()." collections reset\n";
}

$run = $db->prepare("update nzbs set dateadded = now()");
$run->execute();
echo $run->rowCount()." nzbs reset\n";
sleep(2);

function start_apps($tmux_session)
{
	$t = new tmux();
	$tmux = $t->get();
	$htop = $tmux->htop;
	$vnstat = $tmux->vnstat;
	$vnstat_args = $tmux->vnstat_args;
	$tcptrack = $tmux->tcptrack;
	$tcptrack_args = $tmux->tcptrack_args;
	$nmon = $tmux->nmon;
	$bwmng = $tmux->bwmng;
	$mytop = $tmux->mytop;
	$showprocesslist = $tmux->showprocesslist;
	$processupdate = $tmux->processupdate;
	$console_bash = $tmux->console;

	if ($htop == "TRUE" && command_exist("htop"))
		exec("tmux new-window -t $tmux_session -n htop 'printf \"\033]2;htop\033\" && htop'");

	if ($nmon == "TRUE" && command_exist("nmon"))
		exec("tmux new-window -t $tmux_session -n nmon 'printf \"\033]2;nmon\033\" && nmon -t'");

	if ($vnstat == "TRUE" && command_exist("vnstat"))
		exec("tmux new-window -t $tmux_session -n vnstat 'printf \"\033]2;vnstat\033\" && watch -n10 \"vnstat ${vnstat_args}\"'");

	if ($tcptrack == "TRUE" && command_exist("tcptrack"))
		exec("tmux new-window -t $tmux_session -n tcptrack 'printf \"\033]2;tcptrack\033\" && tcptrack ${tcptrack_args}'");

	if ($bwmng == "TRUE" && command_exist("bwm-ng"))
		exec("tmux new-window -t $tmux_session -n bwm-ng 'printf \"\033]2;bwm-ng\033\" && bwm-ng'");

	if ($mytop == "TRUE" && command_exist("mytop"))
		exec("tmux new-window -t $tmux_session -n mytop 'printf \"\033]2;mytop\033\" && mytop -u'");

	if ($showprocesslist == "TRUE")
		exec("tmux new-window -t $tmux_session -n showprocesslist 'printf \"\033]2;showprocesslist\033\" && watch -n .5 \"mysql -e \\\"SELECT time, state, info FROM information_schema.processlist WHERE command != \\\\\\\"Sleep\\\\\\\" AND time >= $processupdate ORDER BY time DESC \\\G\\\"\"'");
		//exec("tmux new-window -t $tmux_session -n showprocesslist 'printf \"\033]2;showprocesslist\033\" && watch -n .2 \"mysql -e \\\"SELECT time, state, rows_examined, info FROM information_schema.processlist WHERE command != \\\\\\\"Sleep\\\\\\\" AND time >= $processupdate ORDER BY time DESC \\\G\\\"\"'");

	if ($console_bash == "TRUE")
		exec("tmux new-window -t $tmux_session -n bash 'printf \"\033]2;Bash\033\" && bash -i'");
}

function window_proxy($tmux_session, $window)
{
	$s = new Sites();
	$site = $s->get();
	$nntpproxy = $site->nntpproxy;
	if ($nntpproxy == '1')
	{
		$DIR = MISC_DIR;
		$nntpproxypy = $DIR."update_scripts/python_scripts/nntpproxy.py";
		if(file_exists($DIR."update_scripts/python_scripts/lib/nntpproxy.conf"))
		{
			$nntpproxyconf = $DIR."update_scripts/python_scripts/lib/nntpproxy.conf";
			exec("tmux new-window -t $tmux_session -n nntpproxy 'printf \"\033]2;NNTPProxy\033\" && python $nntpproxypy $nntpproxyconf'");
		}
	}
	$alternate_nntp = $site->alternate_nntp;
	$grabnzbs = $site->grabnzbs;
	if ($nntpproxy == '1' && ($alternate_nntp == '1' || $grabnzbs == '2'))
	{
		$DIR = MISC_DIR;
		$nntpproxypy = $DIR."update_scripts/python_scripts/nntpproxy.py";
		if (file_exists($DIR."update_scripts/python_scripts/lib/nntpproxy_a.conf"))
		{
			$nntpproxyconf = $DIR."update_scripts/python_scripts/lib/nntpproxy_a.conf";
			exec("tmux selectp -t 0; tmux splitw -t $tmux_session:$window -h -p 50 'printf \"\033]2;NNTPProxy\033\" && python $nntpproxypy $nntpproxyconf'");
		}
	}

}

function window_utilities($tmux_session)
{
	exec("tmux new-window -t $tmux_session -n utils 'printf \"\033]2;fixReleaseNames\033\"'");
	exec("tmux splitw -t $tmux_session:1 -v -p 50 'printf \"\033]2;misc_sorter\033\"'");
	exec("tmux splitw -t $tmux_session:1 -h -p 33 'printf \"\033]2;updateTVandTheaters\033\"'");
	exec("tmux selectp -t 0; tmux splitw -t $tmux_session:1 -h -p 50 'printf \"\033]2;removeCrapReleases\033\"'");
	exec("tmux selectp -t 2; tmux splitw -t $tmux_session:1 -h -p 50 'printf \"\033]2;decryptHashes\033\"'");
}

function window_colors($tmux_session)
{
	echo "WTF";
	exec("tmux new-window -t $tmux_session -n colors 'printf \"\033]2;tmux_colors\033\"'");
}

function window_stripped_utilities($tmux_session)
{
	exec("tmux new-window -t $tmux_session -n utils 'printf \"\033]2;updateTVandTheaters\033\"'");
}

function window_post($tmux_session)
{
	exec("tmux new-window -t $tmux_session -n post 'printf \"\033]2;postprocessing_additional\033\"'");
	exec("tmux splitw -t $tmux_session:2 -v -p 67 'printf \"\033]2;postprocessing_non_amazon\033\"'");
	exec("tmux splitw -t $tmux_session:2 -v -p 50 'printf \"\033]2;postprocessing_amazon\033\"'");
}

function window_optimize($tmux_session)
{
	exec("tmux new-window -t $tmux_session -n optimize 'printf \"\033]2;update_nZEDb\033\"'");
	exec("tmux splitw -t $tmux_session:3 -v -p 50 'printf \"\033]2;optimize\033\"'");
}

function attach($DIR, $tmux_session, $limited=false)
{
	if (command_exist("php5"))
		$PHP = "php5";
	else
		$PHP = "php";

	//get list of panes by name
	$panes_win_1 = exec("echo `tmux list-panes -t $tmux_session:0 -F '#{pane_title}'`");
	$panes0 = str_replace("\n", '', explode(" ", $panes_win_1));
	$log = writelog($panes0[0]);
	if (!$limited)
		exec("tmux respawnp -t $tmux_session:0.0 '$PHP ".$DIR."update_scripts/nix_scripts/tmux/monitor.php $log'");
	else
		exec("tmux respawnp -t $tmux_session:0.0 '$PHP ".$DIR."update_scripts/nix_scripts/tmux/monitor.php limited $log'");
	exec("tmux select-window -t $tmux_session:0; tmux attach-session -d -t $tmux_session");
}

//create tmux session
if ($powerline == "TRUE")
	$tmuxconfig = $DIR."update_scripts/nix_scripts/tmux/powerline/tmux.conf";
else
	$tmuxconfig = $DIR."update_scripts/nix_scripts/tmux/tmux.conf";

if ($seq == 1)
{
	exec("cd ${DIR}/update_scripts/nix_scripts/tmux; tmux -f $tmuxconfig new-session -d -s $tmux_session -n Monitor 'printf \"\033]2;\"Monitor\"\033\"'");
	exec("tmux selectp -t $tmux_session:0.0; tmux splitw -t $tmux_session:0 -h -p 67 'printf \"\033]2;update_releases\033\"'");
	if ($import != 0)
		exec("tmux selectp -t $tmux_session:0.0; tmux splitw -t $tmux_session:0 -v -p 33 'printf \"\033]2;nzb-import\033\"'");
	else
		exec("tmux selectp -t $tmux_session:0.0; tmux splitw -t $tmux_session:0 -v -p 5 'printf \"\033]2;nzb-import\033\"'");

	window_utilities($tmux_session);
	window_post($tmux_session);
	window_proxy($tmux_session, 3);
	if ($colors == "TRUE")
		window_colors($tmux_session);
	start_apps($tmux_session);
	attach($DIR, $tmux_session, $limited);
}
elseif ($seq == 2)
{
	exec("cd ${DIR}/update_scripts/nix_scripts/tmux; tmux -f $tmuxconfig new-session -d -s $tmux_session -n Monitor 'printf \"\033]2;\"Monitor\"\033\"'");
	exec("tmux selectp -t $tmux_session:0.0; tmux splitw -t $tmux_session:0 -h -p 67 'printf \"\033]2;sequential\033\"'");
	if ($import != 0)
		exec("tmux selectp -t $tmux_session:0.0; tmux splitw -t $tmux_session:0 -v -p 33 'printf \"\033]2;nzb-import\033\"'");
	else
		exec("tmux selectp -t $tmux_session:0.0; tmux splitw -t $tmux_session:0 -v -p 5 'printf \"\033]2;nzb-import\033\"'");

	window_stripped_utilities($tmux_session);
	window_proxy($tmux_session, 2);
	if ($colors == "TRUE")
		window_colors($tmux_session);
	start_apps($tmux_session);
	attach($DIR, $tmux_session, $limited);
}
else
{
	exec("cd ${DIR}/update_scripts/nix_scripts/tmux; tmux -f $tmuxconfig new-session -d -s $tmux_session -n Monitor 'printf \"\033]2;Monitor\033\"'");
	exec("tmux selectp -t $tmux_session:0.0; tmux splitw -t $tmux_session:0 -h -p 67 'printf \"\033]2;update_binaries\033\"'");
	if ($import != 0)
		exec("tmux selectp -t $tmux_session:0.0; tmux splitw -t $tmux_session:0 -v -p 33 'printf \"\033]2;nzb-import\033\"'");
	else
		exec("tmux selectp -t $tmux_session:0.0; tmux splitw -t $tmux_session:0 -v -p 5 'printf \"\033]2;nzb-import\033\"'");
	exec("tmux selectp -t $tmux_session:0.2; tmux splitw -t $tmux_session:0 -v -p 67 'printf \"\033]2;backfill\033\"'");
	exec("tmux splitw -t $tmux_session -v -p 50 'printf \"\033]2;update_releases\033\"'");

	window_utilities($tmux_session);
	window_post($tmux_session);
	window_proxy($tmux_session, 3);

	if ($colors == "TRUE")
		window_colors($tmux_session);
	start_apps($tmux_session);
	attach($DIR, $tmux_session, $limited);
}
?>
