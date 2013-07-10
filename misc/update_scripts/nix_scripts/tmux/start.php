<?php

require_once(dirname(__FILE__)."/../../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");
require_once(WWW_DIR."lib/tmux.php");
require_once(WWW_DIR."lib/site.php");

$db = new DB();
$DIR = MISC_DIR;

$tmux = new Tmux();
$tmux_session = $tmux->get()->TMUX_SESSION;
$seq = $tmux->get()->SEQUENTIAL;
$powerline = $tmux->get()->POWERLINE;

$site = new Sites();
$patch = $site->get()->sqlpatch;
$hashcheck = $site->get()->hashcheck;

//check if session exists
$session = exec("echo `tmux list-sessions | grep $tmux_session | wc -l`");
if ( $session != 0 )
	exit("\033[1;33mtmux session:".$tmux_session." is already running, aborting.\033[0m\n\n");
else
	echo "The above is just a TMUX notice, it is saying TMUX, that you do not have a TMUX session currently running. It is not an error. It is TMUX\n";

function writelog( $pane )
{
	$path = dirname(__FILE__)."/logs";
	$getdate = gmDate("Ymd");
	$tmux = new Tmux();
	$logs = $tmux->get()->WRITE_LOGS;
	if ( $logs == "TRUE" )
	{
		return "2>&1 | tee -a $path/$pane-$getdate.log";
	}
	else
	{
		return "";
	}
}

if ( $hashcheck != '1' )
{
	echo "\033[1;33mWe have updated the way collections are created, the collection table has to be updated to use the new changes.\n";
	echo "php ${DIR}testing/DB_scripts/reset_Collections.php true\033[0m\n";
	exit(1);
}

if ( $patch < '94' )
{
	echo "\033[1;33mYour database is not up to date. Please update.\n";
	echo "php ${DIR}testing/DB_scripts/patchmysql.php\033[0m\n";
	exit(1);
}

passthru("clear");

//remove folders from tmpunrar
$tmpunrar = $site->get()->tmpunrarpath;
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

//reset collections dateadded to now
print("Resetting expired collections and nzbs dateadded to now. This could take a minute or two. Really.\n");
$db->query("update collections set dateadded = now()");
if ( $db->getAffectedRows() > 0 )
	echo $db->getAffectedRows()." collections reset\n";
$db->query("update nzbs set dateadded = now()");
if ( $db->getAffectedRows() > 0 )
    echo $db->getAffectedRows()." nzbs reset\n";

function start_apps($tmux_session)
{
	$tmux = new Tmux();
	$htop = $tmux->get()->HTOP;
	$vnstat = $tmux->get()->VNSTAT;
	$vnstat_args = $tmux->get()->VNSTAT_ARGS;
	$tcptrack = $tmux->get()->TCPTRACK;
	$tcptrack_args = $tmux->get()->TCPTRACK_ARGS;
	$nmon = $tmux->get()->NMON;
	$bwmng = $tmux->get()->BWMNG;
	$mytop = $tmux->get()->MYTOP;
	$console_bash = $tmux->get()->CONSOLE;

	if (( $htop == "TRUE" ) && (command_exist("htop")))
	{
		exec("tmux new-window -t $tmux_session -n htop 'printf \"\033]2;htop\033\" && htop'");
	}

	if (( $nmon == "TRUE" ) && (command_exist("nmon")))
	{
		exec("tmux new-window -t $tmux_session -n nmon 'printf \"\033]2;nmon\033\" && nmon -t'");
	}

	if (( $vnstat == "TRUE" ) && (command_exist("vnstat")))
	{
		exec("tmux new-window -t $tmux_session -n vnstat 'printf \"\033]2;vnstat\033\" && watch -n10 \"vnstat ${vnstat_args}\"'");
	}

	if (( $tcptrack == "TRUE" ) && (command_exist("tcptrack")))
	{
		exec("tmux new-window -t $tmux_session -n tcptrack 'printf \"\033]2;tcptrack\033\" && tcptrack ${tcptrack_args}'");
	}

	if (( $bwmng == "TRUE" ) && (command_exist("bwm-ng")))
	{
		exec("tmux new-window -t $tmux_session -n bwm-ng 'printf \"\033]2;bwm-ng\033\" && bwm-ng'");
	}

	if (( $mytop == "TRUE" ) && (command_exist("mytop")))
	{
		exec("tmux new-window -t $tmux_session -n mytop 'printf \"\033]2;mytop\033\" && mytop -u'");
	}

	if ( $console_bash == "TRUE" )
	{
		exec("tmux new-window -t $tmux_session -n bash 'printf \"\033]2;Bash\033\" && bash -i'");
	}
}

function window_utilities($tmux_session)
{
	exec("tmux new-window -t $tmux_session -n utils 'printf \"\033]2;fixReleaseNames\033\"'");
	exec("tmux splitw -t $tmux_session:1 -v -p 50 'printf \"\033]2;misc_sorter\033\"'");
	exec("tmux splitw -t $tmux_session:1 -h -p 33 'printf \"\033]2;updateTVandTheaters\033\"'");
	exec("tmux selectp -t 0 && tmux splitw -t $tmux_session:1 -h -p 50 'printf \"\033]2;removeCrapReleases\033\"'");
	exec("tmux selectp -t 2 && tmux splitw -t $tmux_session:1 -h -p 50 'printf \"\033]2;decryptHashes\033\"'");
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

function attach($DIR, $tmux_session)
{
	if (command_exist("php5"))
		$PHP = "php5";
	else
		$PHP = "php";

	//get list of panes by name
	$panes_win_1 = exec("echo `tmux list-panes -t $tmux_session:0 -F '#{pane_title}'`");
	$panes0 = str_replace("\n", '', explode(" ", $panes_win_1));
	$log = writelog($panes0[0]);
	exec("tmux respawnp -t $tmux_session:0.0 '$PHP ".$DIR."update_scripts/nix_scripts/tmux/monitor.php $log'");
	exec("tmux select-window -t $tmux_session:0 && tmux attach-session -d -t $tmux_session");
}

//create tmux session
if ( $powerline == "TRUE" )
	$tmuxconfig = $DIR."update_scripts/nix_scripts/tmux/powerline/tmux.conf";
else
	$tmuxconfig = $DIR."update_scripts/nix_scripts/tmux/tmux.conf";

if ( $seq == "TRUE" )
{
	exec("cd ${DIR}/update_scripts/nix_scripts/tmux && tmux -f $tmuxconfig new-session -d -s $tmux_session -n Monitor 'printf \"\033]2;\"Monitor\"\033\"'");
	exec("tmux selectp -t $tmux_session:0.0 && tmux splitw -t $tmux_session:0 -h -p 67 'printf \"\033]2;update_releases\033\"'");
	exec("tmux selectp -t $tmux_session:0.0 && tmux splitw -t $tmux_session:0 -v -p 33 'printf \"\033]2;nzb-import-bulk\033\"'");

	window_utilities($tmux_session);
	window_post($tmux_session);
	start_apps($tmux_session);
	attach($DIR, $tmux_session);
}
else
{
	exec("cd ${DIR}/update_scripts/nix_scripts/tmux && tmux -f $tmuxconfig new-session -d -s $tmux_session -n Monitor 'printf \"\033]2;Monitor\033\"'");
	exec("tmux selectp -t $tmux_session:0.0 && tmux splitw -t $tmux_session:0 -h -p 67 'printf \"\033]2;update_binaries\033\"'");
	exec("tmux selectp -t $tmux_session:0.0 && tmux splitw -t $tmux_session:0 -v -p 33 'printf \"\033]2;nzb-import\033\"'");
	exec("tmux selectp -t $tmux_session:0.2 && tmux splitw -t $tmux_session:0 -v -p 67 'printf \"\033]2;backfill\033\"'");
	exec("tmux splitw -t $tmux_session -v -p 50 'printf \"\033]2;update_releases\033\"'");

	window_utilities($tmux_session);
	window_post($tmux_session);
	start_apps($tmux_session);
	attach($DIR, $tmux_session);
}
?>
