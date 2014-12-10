<?php
require_once dirname(__FILE__) . '/../../../../www/config.php';

use nzedb\db\Settings;
use nzedb\utility\Utility;

$pdo = new Settings();
$DIR = nZEDb_MISC;

// Check that Db patch level is current. Also checks nZEDb.xml is valid.
Utility::isPatched();

Utility::clearScreen();

$patch = $pdo->getSetting('sqlpatch');
$patch = ($patch != '') ? $patch : 0;
$delaytimet = $pdo->getSetting('delaytime');
$delaytimet = ($delaytimet) ? (int)$delaytimet : 2;
$nntpproxy = $pdo->getSetting('nntpproxy');

// Search for NNTPProxy session that might be running from a user threaded.php run. Setup a clean environment to run in.
$nntpkill = shell_exec("tmux list-session | grep NNTPProxy");
if (count($nntpkill) !== 0) {
	exec("tmux kill-session -t NNTPProxy");
	echo $pdo->log->notice("Found NNTPProxy tmux session and killing it.");
} else {
	$sessions = shell_exec("tmux list-session");
}

$t = new \Tmux();
$tmux = $t->get();
$tmux_session = (isset($tmux->tmux_session)) ? $tmux->tmux_session : 0;
$seq = (isset($tmux->sequential)) ? $tmux->sequential : 0;
$powerline = (isset($tmux->powerline)) ? $tmux->powerline : 0;
$import = (isset($tmux->import)) ? $tmux->import : 0;
$tablepergroup = $pdo->getSetting('tablepergroup');
$tablepergroup = ($tablepergroup != '') ? $tablepergroup : 0;

//check if session exists
$session = shell_exec("tmux list-session | grep $tmux_session");
if (count($session) !== 0) {
	exit($pdo->log->error("tmux session: '" . $tmux_session . "' is already running, aborting.\n"));
}

$nntpproxy = $pdo->getSetting('nntpproxy');
if ($nntpproxy == '1') {
	$modules = ["nntp", "socketpool"];
	foreach ($modules as &$value) {
		if (!python_module_exist($value)) {
			exit($pdo->log->error("\nNNTP Proxy requires " . $value .
								  " python module but it's not installed. Aborting.\n"));
		}
	}
}

//reset collections dateadded to now if dateadded > delay time check
echo $pdo->log->header("Resetting expired collections dateadded to now. This could take a minute or two. Really.");

if ($tablepergroup == 1) {
	$sql    = "SHOW table status";
	$tables = $pdo->queryDirect($sql);
	$ran    = 0;
	foreach ($tables as $row) {
		$tbl = $row['name'];
		if (preg_match('/collections_\d+/', $tbl)) {
			$run = $pdo->queryExec('UPDATE ' . $tbl .
								   ' SET dateadded = now() WHERE dateadded < now() - INTERVAL ' .
								   $delaytimet . ' HOUR');
			if ($run !== false) {
				$ran += $run->rowCount();
			}
		}
	}
	echo $pdo->log->primary(number_format($ran) . " collections reset.");
} else {
	$ran = 0;
	$run = $pdo->queryExec('update collections set dateadded = now() WHERE dateadded < now() - INTERVAL ' .
						   $delaytimet . ' HOUR');
	if ($run !== false) {
		$ran += $run->rowCount();
	}
	echo $pdo->log->primary(number_format($ran) . " collections reset.");
}
sleep(2);

//create tmux session
if ($powerline == 1) {
	$tmuxconfig = $DIR . "update/nix/tmux/powerline/tmux.conf";
} else {
	$tmuxconfig = $DIR . "update/nix/tmux/tmux.conf";
}

if ($seq == 1) {
	exec("cd ${DIR}/update/nix/tmux; tmux -f $tmuxconfig new-session -d -s $tmux_session -n Monitor 'printf \"\033]2;\"Monitor\"\033\"'");
	exec("tmux selectp -t $tmux_session:0.0; tmux splitw -t $tmux_session:0 -h -p 67 'printf \"\033]2;update_releases\033\"'");
	exec("tmux selectp -t $tmux_session:0.0; tmux splitw -t $tmux_session:0 -v -p 25 'printf \"\033]2;nzb-import\033\"'");

	window_utilities($tmux_session);
	window_post($tmux_session);
// todo simplify this IF removing redundancy (consider moving to a function)
	if ($nntpproxy == 1) {
		window_ircscraper($tmux_session);
		window_proxy($tmux_session, 4);
		window_sharing($tmux_session);
	} else {
		window_ircscraper($tmux_session);
		window_sharing($tmux_session);
	}
	start_apps($tmux_session);
	attach($DIR, $tmux_session);
} else {
	if ($seq == 2) {
		exec("cd ${DIR}/update/nix/tmux; tmux -f $tmuxconfig new-session -d -s $tmux_session -n Monitor 'printf \"\033]2;\"Monitor\"\033\"'");
		exec("tmux selectp -t $tmux_session:0.0; tmux splitw -t $tmux_session:0 -h -p 67 'printf \"\033]2;sequential\033\"'");
		exec("tmux selectp -t $tmux_session:0.0; tmux splitw -t $tmux_session:0 -v -p 25 'printf \"\033]2;nzb-import\033\"'");

		window_stripped_utilities($tmux_session);
// todo simplify this IF removing redundancy (consider moving to a function)
		if ($nntpproxy == 1) {
			window_ircscraper($tmux_session);
			window_proxy($tmux_session, 3);
			window_sharing($tmux_session);
		} else {
			window_ircscraper($tmux_session);
			window_sharing($tmux_session);
		}

		start_apps($tmux_session);
		attach($DIR, $tmux_session);
	} else {
		exec("cd ${DIR}/update/nix/tmux; tmux -f $tmuxconfig new-session -d -s $tmux_session -n Monitor 'printf \"\033]2;Monitor\033\"'");
		exec("tmux selectp -t $tmux_session:0.0; tmux splitw -t $tmux_session:0 -h -p 67 'printf \"\033]2;update_binaries\033\"'");
		exec("tmux selectp -t $tmux_session:0.0; tmux splitw -t $tmux_session:0 -v -p 25 'printf \"\033]2;nzb-import\033\"'");
		exec("tmux selectp -t $tmux_session:0.2; tmux splitw -t $tmux_session:0 -v -p 67 'printf \"\033]2;backfill\033\"'");
		exec("tmux splitw -t $tmux_session -v -p 50 'printf \"\033]2;update_releases\033\"'");

		window_utilities($tmux_session);
		window_post($tmux_session);
		if ($nntpproxy == 1) {
			window_ircscraper($tmux_session);
			window_proxy($tmux_session, 4);
			window_sharing($tmux_session);
		} else {
			window_ircscraper($tmux_session);
			window_sharing($tmux_session);
		}
		start_apps($tmux_session);
		attach($DIR, $tmux_session);
	}
}

####################################################################################################
######################################### F U N C T I O N S ########################################
####################################################################################################

function writelog($pane)
{
	$path = dirname(__FILE__) . "/logs";
	$getdate = gmDate("Ymd");
	$tmux = new \Tmux();
	$logs = $tmux->get()->write_logs;
	if ($logs == 1) {
		return "2>&1 | tee -a $path/$pane-$getdate.log";
	} else {
		return "";
	}
}

function command_exist($cmd)
{
	$returnVal = exec("which $cmd 2>/dev/null");

	return (empty($returnVal) ? false : true);
}

//check for apps
$apps = ["time", "tmux", "nice", "python", "tee"];
foreach ($apps as &$value) {
	if (!command_exist($value)) {
		exit($pdo->log->error("Tmux scripts require " . $value . " but it's not installed. Aborting.\n"));
	}
}

function python_module_exist($module)
{
	$output = $returnCode = '';
	exec("python -c \"import $module\"", $output, $returnCode);

	return ($returnCode == 0 ? true : false);
}

function start_apps($tmux_session)
{
	$t = new \Tmux();
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

	if ($htop == 1 && command_exist("htop")) {
		exec("tmux new-window -t $tmux_session -n htop 'printf \"\033]2;htop\033\" && htop'");
	}

	if ($nmon == 1 && command_exist("nmon")) {
		exec("tmux new-window -t $tmux_session -n nmon 'printf \"\033]2;nmon\033\" && nmon -t'");
	}

	if ($vnstat == 1 && command_exist("vnstat")) {
		exec("tmux new-window -t $tmux_session -n vnstat 'printf \"\033]2;vnstat\033\" && watch -n10 \"vnstat ${vnstat_args}\"'");
	}

	if ($tcptrack == 1 && command_exist("tcptrack")) {
		exec("tmux new-window -t $tmux_session -n tcptrack 'printf \"\033]2;tcptrack\033\" && tcptrack ${tcptrack_args}'");
	}

	if ($bwmng == 1 && command_exist("bwm-ng")) {
		exec("tmux new-window -t $tmux_session -n bwm-ng 'printf \"\033]2;bwm-ng\033\" && bwm-ng'");
	}

	if ($mytop == 1 && command_exist("mytop")) {
		exec("tmux new-window -t $tmux_session -n mytop 'printf \"\033]2;mytop\033\" && mytop -u'");
	}

	if ($showprocesslist == 1) {
		exec("tmux new-window -t $tmux_session -n showprocesslist 'printf \"\033]2;showprocesslist\033\" && watch -n .5 \"mysql -e \\\"SELECT time, state, info FROM information_schema.processlist WHERE command != \\\\\\\"Sleep\\\\\\\" AND time >= $processupdate ORDER BY time DESC \\\G\\\"\"'");
	}
	//exec("tmux new-window -t $tmux_session -n showprocesslist 'printf \"\033]2;showprocesslist\033\" && watch -n .2 \"mysql -e \\\"SELECT time, state, rows_examined, info FROM information_schema.processlist WHERE command != \\\\\\\"Sleep\\\\\\\" AND time >= $processupdate ORDER BY time DESC \\\G\\\"\"'");

	if ($console_bash == 1) {
		exec("tmux new-window -t $tmux_session -n bash 'printf \"\033]2;Bash\033\" && bash -i'");
	}
}

function window_proxy($tmux_session, $window)
{
	global $pdo;
	$nntpproxy = $pdo->getSetting('nntpproxy');
	if ($nntpproxy === '1') {
		$DIR = nZEDb_MISC;
		$nntpproxypy = $DIR . "update/python/nntpproxy.py";
		if (file_exists($DIR . "update/python/lib/nntpproxy.conf")) {
			$nntpproxyconf = $DIR . "update/python/lib/nntpproxy.conf";
			exec("tmux new-window -t $tmux_session -n nntpproxy 'printf \"\033]2;NNTPProxy\033\" && python $nntpproxypy $nntpproxyconf'");
		}
	}

	if ($nntpproxy === '1' && ($pdo->getSetting('alternate_nntp') == '1')) {
		$DIR = nZEDb_MISC;
		$nntpproxypy = $DIR . "update/python/nntpproxy.py";
		if (file_exists($DIR . "update/python/lib/nntpproxy_a.conf")) {
			$nntpproxyconf = $DIR . "update/python/lib/nntpproxy_a.conf";
			exec("tmux selectp -t $tmux_session:$window.0; tmux splitw -t $tmux_session:$window -h -p 50 'printf \"\033]2;NNTPProxy\033\" && python $nntpproxypy $nntpproxyconf'");
		}
	}
}

function window_utilities($tmux_session)
{
	exec("tmux new-window -t $tmux_session -n utils 'printf \"\033]2;fixReleaseNames\033\"'");
	exec("tmux splitw -t $tmux_session:1 -v -p 50 'printf \"\033]2;updateTVandTheaters\033\"'");
	exec("tmux selectp -t $tmux_session:1.0; tmux splitw -t $tmux_session:1 -h -p 50 'printf \"\033]2;removeCrapReleases\033\"'");
	exec("tmux selectp -t $tmux_session:1.2; tmux splitw -t $tmux_session:1 -h -p 50 'printf \"\033]2;decryptHashes\033\"'");
}

function window_stripped_utilities($tmux_session)
{
	exec("tmux new-window -t $tmux_session -n utils 'printf \"\033]2;updateTVandTheaters\033\"'");
	exec("tmux selectp -t $tmux_session:1.0; tmux splitw -t $tmux_session:1 -h -p 50 'printf \"\033]2;postprocessing_amazon\033\"'");
}

function window_ircscraper($tmux_session)
{
	exec("tmux new-window -t $tmux_session -n IRCScraper 'printf \"\033]2;scrapeIRC\033\"'");
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

function window_sharing($tmux_session)
{
	$pdo = new Settings();
	$sharing = $pdo->queryOneRow('SELECT enabled, posting, fetching FROM sharing');
	$t = new \Tmux();
	$tmux = $t->get();
	$tmux_share = (isset($tmux->run_sharing)) ? $tmux->run_sharing : 0;

	if ($tmux_share && $sharing['enabled'] == 1 && ($sharing['posting'] == 1 || $sharing['fetching'] == 1)) {
		exec("tmux new-window -t $tmux_session -n Sharing 'printf \"\033]2;comment_sharing\033\"'");
	}
}

function attach($DIR, $tmux_session)
{
	if (command_exist("php5")) {
		$PHP = "php5";
	} else {
		$PHP = "php";
	}

	//get list of panes by name
	$panes_win_1 = exec("echo `tmux list-panes -t $tmux_session:0 -F '#{pane_title}'`");
	$panes0 = str_replace("\n", '', explode(" ", $panes_win_1));
	$log = writelog($panes0[0]);
	exec("tmux respawnp -t $tmux_session:0.0 '$PHP " . $DIR . "update/nix/tmux/monitor.php $log'");
	exec("tmux select-window -t $tmux_session:0; tmux attach-session -d -t $tmux_session");
}
