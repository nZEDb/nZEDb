<?php
require_once dirname(__FILE__) . '/config.php';

passthru("clear");

$db = new DB();
$t = new Tmux();
$tmux = $t->get();
$powerline = (isset($tmux->powerline)) ? $tmux->powerline : 0;
$s = new Sites();
$site = $s->get();
$c = new ColorCLI();

$tmux_session = 'NNTPProxy';

function python_module_exist($module)
{
	exec("python -c \"import $module\"", $output, $returnCode);
	return ($returnCode == 0 ? true : false);
}

$nntpproxy = $site->nntpproxy;
if ($nntpproxy === '0') {
	exit();
} else {
	$modules = array("nntp", "socketpool");
	foreach ($modules as &$value) {
		if (!python_module_exist($value)) {
			exit($c->error("NNTP Proxy requires " . $value . " python module but it's not installed. Aborting."));
		}
	}
}

function window_proxy($tmux_session, $powerline)
{
	$s = new Sites();
	$site = $s->get();
	$DIR = nZEDb_MISC;
	if ($powerline === '1') {
		$tmuxconfig = $DIR . "update/nix/tmux/powerline/tmux.conf";
	} else {
		$tmuxconfig = $DIR . "update/nix/tmux/tmux.conf";
	}

	$nntpproxy = $site->nntpproxy;
	if ($nntpproxy === '1') {
		$DIR = nZEDb_MISC;
		$nntpproxypy = $DIR . "update/python/nntpproxy.py";
		if (file_exists($DIR . "update/python/lib/nntpproxy.conf")) {
			$nntpproxyconf = $DIR . "update/python/lib/nntpproxy.conf";
			shell_exec("cd ${DIR}/update/nix/tmux; tmux -f $tmuxconfig attach-session -t $tmux_session || tmux -f $tmuxconfig new-session -d -s $tmux_session -n NNTPProxy 'printf \"\033]2;\"NNTPProxy\"\033\" && python $nntpproxypy $nntpproxyconf'");
		}
	}

	if ($nntpproxy === '1' && ($site->alternate_nntp === '1' || $site->grabnzbs === '2')) {
		$DIR = nZEDb_MISC;
		$nntpproxypy = $DIR . "update/python/nntpproxy.py";
		if (file_exists($DIR . "update/python/lib/nntpproxy_a.conf")) {
			$nntpproxyconf = $DIR . "update/python/lib/nntpproxy_a.conf";
			shell_exec("tmux selectp -t 0; tmux splitw -t $tmux_session:0 -h -p 50 'printf \"\033]2;NNTPProxy\033\" && python $nntpproxypy $nntpproxyconf'");
		}
	}
}

window_proxy($tmux_session, $powerline);
