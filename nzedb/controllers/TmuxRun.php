<?php

/**
 * Tmux pane shell exec functions for pane respawning
 *
 * Class TmuxRun
 */
class TmuxRun extends Tmux
{
	/**
	 * @param $pdo Class instances / Echo to cli?
	 */
	public function __construct($pdo)
	{
		$this->pdo = $pdo;
		parent::__construct($this->pdo);
	}

	// runs panes other than bins/backfill/import/releases
	public function runPaneExtra($cmdParam, $runVar)
	{
		$this->runVar = $runVar;
		switch ($this->runVar['constants']['sequential']) {
			case 0:
			case 1:
				switch ($cmdParam) {
					case 'amazon':
						$this->_runAmazon($this->runVar);
						break;
					case 'dehash':
						$this->_runDehash($this->runVar);
						break;
					case 'nonamazon':
						$this->_runNonAmazon($this->runVar);
						break;
					case 'updatetv':
						return $this->_runUpdateTv($this->runVar);
						break;
				}
		}
	}

	protected function _runDehash($runVar)
	{
		$this->runVar = $runVar;

		switch ($this->runVar['settings']['dehash']) {
			case 1:
				$log = $this->writelog($this->runVar['panes']['one'][3]);
				shell_exec("tmux respawnp -t{$this->runVar['constants']['tmux_session']}:1.3 ' \
					{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/decrypt_hashes.php 1000 $log; \
					date +\"%D %T\"; {$runVar['commands']['_sleep']} {$this->runVar['settings']['dehash_timer']}' 2>&1 1> /dev/null"
				);
				break;
			case 2:
				$log = $this->writelog($this->runVar['panes']['one'][3]);
				shell_exec("tmux respawnp -t{$this->runVar['constants']['tmux_session']}:1.3 ' \
					{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/tmux/bin/postprocess_pre.php {$this->runVar['constants']['pre_lim']} $log; \
					date +\"%D %T\"; {$runVar['commands']['_sleep']} {$this->runVar['settings']['dehash_timer']}' 2>&1 1> /dev/null"
				);
				break;
			case 3:
				$log = $this->writelog($this->runVar['panes']['one'][3]);
				shell_exec("tmux respawnp -t{$this->runVar['constants']['tmux_session']}:1.3 ' \
					{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/tmux/bin/postprocess_pre.php {$this->runVar['constants']['pre_lim']} $log; \
					{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/decrypt_hashes.php 1000 $log; \
					date +\"%D %T\"; {$runVar['commands']['_sleep']} {$this->runVar['settings']['dehash_timer']}' 2>&1 1> /dev/null"
				);
				break;
			default:
				$color = $this->get_color($this->runVar['settings']['colors_start'], $this->runVar['settings']['colors_end'], $this->runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$this->runVar['constants']['tmux_session']}:1.3 'echo \"\033[38;5;${color}m\n{$this->runVar['panes']['one'][3]} has been disabled/terminated by Decrypt Hashes\"'");
				break;
		}
	}

	protected function _runAmazon($runVar)
	{
		$this->runVar = $runVar;

		switch (true) {
			case $runVar['settings']['post_amazon'] == 1 && ($runVar['counts']['now']['processmusic'] > 0 || $runVar['counts']['now']['processbooks'] > 0 || $runVar['counts']['now']['processgames'] > 0 || $runVar['counts']['now']['apps'] > 0 || $runVar['counts']['now']['processxxx'] > 0)
					&& ($runVar['settings']['processbooks'] == 1 || $runVar['settings']['processmusic'] == 1 || $runVar['settings']['processgames'] == 1  || $runVar['settings']['processxxx'] == 1):
				$log = $this->writelog($runVar['panes']['two'][2]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:2.2 ' \
						{$runVar['commands']['_phpn']} {$runVar['paths']['misc']}update/postprocess.php amazon true $log; date +\"%D %T\"; {$runVar['commands']['_sleep']} {$runVar['settings']['post_timer_amazon']}' 2>&1 1> /dev/null"
				);
				break;
			case $runVar['settings']['post_amazon'] == 1 && $runVar['settings']['processbooks'] == 0 && $runVar['settings']['processmusic'] == 0 && $runVar['settings']['processgames'] == 0 && $runVar['settings']['processxxx'] == 0:
				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.2 'echo \"\033[38;5;${color}m\n{$runVar['panes']['two'][2]} has been disabled/terminated in Admin Disable Music/Books/Console/XXX\"'");
				break;
			case $runVar['settings']['post_amazon'] == 1 && $runVar['counts']['now']['processmusic'] == 0 && $runVar['counts']['now']['processbooks'] == 0 && $runVar['counts']['now']['processgames'] == 0 && $runVar['counts']['now']['apps'] == 0 && $runVar['counts']['now']['processxxx'] == 0:
				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.2 'echo \"\033[38;5;${color}m\n{$runVar['panes']['two'][2]} has been disabled/terminated by No Music/Books/Console/Games/XXX to process\"'");
				break;
			default:
				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.2 'echo \"\033[38;5;${color}m\n{$runVar['panes']['two'][2]} has been disabled/terminated by Postprocess Amazon\"'");
				break;
		}
	}

	protected function _runNonAmazon($runVar)
	{
		$this->runVar = $runVar;

		switch (true) {
			case $this->runVar['settings']['post_non'] != 0 && ($this->runVar['counts']['now']['processmovies'] > 0 || $this->runVar['counts']['now']['processtvrage'] > 0):
				$log = $this->writelog($this->runVar['panes']['two'][1]);
				shell_exec("tmux respawnp -t{$this->runVar['constants']['tmux_session']}:2.1 ' \
						{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/multiprocessing/postprocess.php tv {$this->runVar['modsettings']['clean']} $log; \
						{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/multiprocessing/postprocess.php mov {$this->runVar['modsettings']['clean']} $log; \
						date +\"%D %T\"; {$runVar['commands']['_sleep']} {$this->runVar['settings']['post_timer_non']}' 2>&1 1> /dev/null"
				);
				break;
			case $this->runVar['settings']['post_non'] != 0 && $this->runVar['counts']['now']['processmovies'] == 0 && $this->runVar['counts']['now']['processtvrage'] == 0:
				$color = $this->get_color($this->runVar['settings']['colors_start'], $this->runVar['settings']['colors_end'], $this->runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$this->runVar['constants']['tmux_session']}:2.1 'echo \"\033[38;5;${color}m\n{$this->runVar['panes']['two'][1]} has been disabled/terminated by No Movies/TV to process\"'");
				break;
			default:
				$color = $this->get_color($this->runVar['settings']['colors_start'], $this->runVar['settings']['colors_end'], $this->runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$this->runVar['constants']['tmux_session']}:2.1 'echo \"\033[38;5;${color}m\n{$this->runVar['panes']['two'][1]} has been disabled/terminated by Postprocess Non-Amazon\"'");
				break;
		}
	}

	protected function _runRemoveCrap($runVar)
	{
	}

	protected function _runUpdateTv($runVar)
	{
		$this->runVar = $runVar;

		switch (true) {
			case $this->runVar['settings']['update_tv'] == 1 && (time() - $runVar['timers']['timer4'] >= $this->runVar['settings']['tv_timer'] || $this->runVar['counts']['iterations'] == 1):
				$log = $this->writelog($this->runVar['panes']['one'][3]);
				shell_exec("tmux respawnp -t{$this->runVar['constants']['tmux_session']}:1.2 ' \
						{$this->runVar['commands']['_phpn']} {$this->runVar['paths']['misc']}update/update_theaters.php $log; {$this->runVar['commands']['_phpn']} {$this->runVar['paths']['misc']}testing/PostProc/populate_tvrage.php true $log; \
						{$this->runVar['commands']['_phpn']} {$this->runVar['paths']['misc']}update/update_tvschedule.php $log; {$this->runVar['commands']['_phpn']} {$this->runVar['paths']['misc']}testing/PostProc/updateTvRage.php $log; date +\"%D %T\"' 2>&1 1> /dev/null"
				);
				$this->runVar['timers']['timer4'] = time();
				break;
			case $this->runVar['settings']['update_tv'] == 1:
				$run_time = $this->relativeTime($this->runVar['settings']['tv_timer'] + $this->runVar['timers']['timer4']);
				$color = $this->get_color($this->runVar['settings']['colors_start'], $this->runVar['settings']['colors_end'], $this->runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -t{$this->runVar['constants']['tmux_session']}:1.2 'echo \"\033[38;5;${color}m\n{$this->runVar['panes']['one'][2]} will run in T[ $run_time]\"' 2>&1 1> /dev/null");
				break;
			default:
				$color = $this->get_color($this->runVar['settings']['colors_start'], $this->runVar['settings']['colors_end'], $this->runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$this->runVar['constants']['tmux_session']}:1.2 'echo \"\033[38;5;${color}m\n{$this->runVar['panes']['one'][2]} has been disabled/terminated by Update TV/Theater\"'");
		}
		return $this->runVar['timers']['timer4'];
	}

	public function getConnectionsCounts(&$runVar)
	{
		$this->runVar = $runVar;

		$this->runVar['connections']['primary']['active'] = $this->runVar['connections']['primary']['total'] =
		$this->runVar['connections']['alternate']['active'] = $this->runVar['connections']['alternate']['total'] = 0;

		$this->runVar['connections']['primary']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $this->runVar['connections']['ip'] . ":" . $this->runVar['connections']['port'] . " | grep -c ESTAB"));
		$this->runVar['connections']['primary']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $this->runVar['connections']['ip'] . ":" . $this->runVar['connections']['port']));
		if ($this->runVar['constants']['alternate_nntp']) {
			$this->runVar['connections']['alternate']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $this->runVar['connections']['ip_a'] . ":" . $this->runVar['connections']['port_a'] . " | grep -c ESTAB"));
			$this->runVar['connections']['alternate']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $this->runVar['connections']['ip_a'] . ":" . $this->runVar['connections']['port_a']));
		}
		if ($this->runVar['connections']['primary']['active'] == 0 && $this->runVar['connections']['primary']['total'] == 0 && $this->runVar['connections']['alternate']['active'] == 0 && $this->runVar['connections']['alternate']['total'] == 0 && $this->runVar['connections']['port'] != $this->runVar['connections']['port_a']) {
				$this->runVar['connections']['primary']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $this->runVar['connections']['ip'] . ":https | grep -c ESTAB"));
				$this->runVar['connections']['primary']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $this->runVar['connections']['ip'] . ":https"));
				if ($this->runVar['constants']['alternate_nntp']) {
					$this->runVar['connections']['alternate']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $this->runVar['connections']['ip_a'] . ":https | grep -c ESTAB"));
					$this->runVar['connections']['alternate']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $this->runVar['connections']['ip_a'] . ":https"));
				}
		}
		if ($this->runVar['connections']['primary']['active'] == 0 && $this->runVar['connections']['primary']['total'] == 0 && $this->runVar['connections']['alternate']['active'] == 0 && $this->runVar['connections']['alternate']['total'] == 0 && $this->runVar['connections']['port'] != $this->runVar['connections']['port_a']) {
			$this->runVar['connections']['primary']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $this->runVar['connections']['port'] . " | grep -c ESTAB"));
			$this->runVar['connections']['primary']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $this->runVar['connections']['port']));
			if ($this->runVar['constants']['alternate_nntp']) {
				$this->runVar['connections']['alternate']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $this->runVar['connections']['port_a'] . " | grep -c ESTAB"));
				$this->runVar['connections']['alternate']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $this->runVar['connections']['port_a']));
			}
		}
		if ($this->runVar['connections']['primary']['active'] == 0 && $this->runVar['connections']['primary']['total'] == 0 && $this->runVar['connections']['alternate']['active'] == 0 && $this->runVar['connections']['alternate']['total'] == 0 && $this->runVar['connections']['port'] != $this->runVar['connections']['port_a']) {
			$this->runVar['connections']['primary']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $this->runVar['connections']['ip'] . " | grep -c ESTAB"));
			$this->runVar['connections']['primary']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $this->runVar['connections']['ip']));
			if ($this->runVar['constants']['alternate_nntp']) {
				$this->runVar['connections']['alternate']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $this->runVar['connections']['ip'] . " | grep -c ESTAB"));
				$this->runVar['connections']['alternate']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $this->runVar['connections']['ip']));
			}
		}
		return ($this->runVar['connections']);
	}

	public function getListOfPanes(&$runVar)
	{
		$this->runVar = $runVar;
		switch ($this->runVar['constants']['sequential']) {
			case 0:
				$panes_win_1 = shell_exec("echo `tmux list-panes -t {$this->runVar['constants']['tmux_session']}:0 -F '#{pane_title}'`");
				$this->runVar['panes']['zero'] = str_replace("\n", '', explode(" ", $panes_win_1));
				$panes_win_2 = shell_exec("echo `tmux list-panes -t {$this->runVar['constants']['tmux_session']}:1 -F '#{pane_title}'`");
				$this->runVar['panes']['one'] = str_replace("\n", '', explode(" ", $panes_win_2));
				$panes_win_3 = shell_exec("echo `tmux list-panes -t {$this->runVar['constants']['tmux_session']}:2 -F '#{pane_title}'`");
				$this->runVar['panes']['two'] = str_replace("\n", '', explode(" ", $panes_win_3));
				break;
			case 1:
				$panes_win_1 = shell_exec("echo `tmux list-panes -t {$this->runVar['constants']['tmux_session']}:0 -F '#{pane_title}'`");
				$this->runVar['panes']['zero'] = str_replace("\n", '', explode(" ", $panes_win_1));
				$panes_win_2 = shell_exec("echo `tmux list-panes -t {$this->runVar['constants']['tmux_session']}:1 -F '#{pane_title}'`");
				$this->runVar['panes']['one'] = str_replace("\n", '', explode(" ", $panes_win_2));
				$panes_win_3 = shell_exec("echo `tmux list-panes -t {$this->runVar['constants']['tmux_session']}:2 -F '#{pane_title}'`");
				$this->runVar['panes']['two'] = str_replace("\n", '', explode(" ", $panes_win_3));
				break;
			case 2:
				$panes_win_1 = shell_exec("echo `tmux list-panes -t {$this->runVar['constants']['tmux_session']}:0 -F '#{pane_title}'`");
				$this->runVar['panes']['zero'] = str_replace("\n", '', explode(" ", $panes_win_1));
				$panes_win_2 = shell_exec("echo `tmux list-panes -t {$this->runVar['constants']['tmux_session']}:1 -F '#{pane_title}'`");
				$this->runVar['panes']['one'] = str_replace("\n", '', explode(" ", $panes_win_2));
				break;
		}
		return ($this->runVar['panes']);
	}

	public function notRunningNonSeq(&$runVar)
	{
		$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
		for ($g = 1; $g <= 4; $g++) {
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.$g 'echo \"\033[38;5;${color}m\n{$runVar['panes']['zero'][$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 3; $g++) {
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.$g 'echo \"\033[38;5;${color}m\n{$runVar['panes']['one'][$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 2; $g++) {
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.$g 'echo \"\033[38;5;${color}m\n{$runVar['panes']['two'][$g]} has been disabled/terminated by Running\"'");
		}
	}

	public function notRunningBasicSeq(&$runVar)
	{
		$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
		for ($g = 1; $g <= 2; $g++) {
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.$g 'echo \"\033[38;5;${color}m\n{$runVar['panes']['zero'][$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 3; $g++) {
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.$g 'echo \"\033[38;5;${color}m\n{$runVar['panes']['one'][$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 2; $g++) {
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.$g 'echo \"\033[38;5;${color}m\n{$runVar['panes']['two'][$g]} has been disabled/terminated by Running\"'");
		}
	}

	public function notRunningCompSeq(&$runVar)
	{
		$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
		for ($g = 1; $g <= 2; $g++) {
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.$g 'echo \"\033[38;5;${color}m\n{$runVar['panes']['zero'][$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 1; $g++) {
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.$g 'echo \"\033[38;5;${color}m\n{$runVar['panes']['one'][$g]} has been disabled/terminated by Running\"'");
		}
	}

	public function run_ircscraper($tmux_session, $_php, $pane, $run_ircscraper)
	{
		if ($run_ircscraper == 1) {
			//Check to see if the pane is dead, if so respawn it.
			if (shell_exec("tmux list-panes -t${tmux_session}:${pane} | grep ^0 | grep -c dead") == 1) {
				$DIR = nZEDb_MISC;
				$ircscraper = $DIR . "testing/IRCScraper/scrape.php";
				shell_exec(
					"tmux respawnp -t${tmux_session}:${pane}.0 ' \
					$_php $ircscraper true'"
				);
			}
		} else {
			shell_exec("tmux respawnp -t${tmux_session}:${pane}.0 'echo \"\nIRCScraper has been disabled/terminated by IRCSCraper\"'");
		}
	}

	public function run_sharing($tmux_session, $_php, $pane, $_sleep, $sharing_timer)
	{
		$sharing = $this->pdo->queryOneRow('SELECT enabled, posting, fetching FROM sharing');
		$tmux = $this->get();
		$tmux_share = (isset($tmux->run_sharing)) ? $tmux->run_sharing : 0;

		if ($tmux_share && $sharing['enabled'] == 1 && ($sharing['posting'] == 1 || $sharing['fetching'] == 1)) {
			if (shell_exec("tmux list-panes -t${tmux_session}:${pane} | grep ^0 | grep -c dead") == 1) {
				$DIR = nZEDb_MISC;
				$sharing2 = $DIR . "/update/postprocess.php sharing true";
				shell_exec(
					"tmux respawnp -t${tmux_session}:${pane}.0 ' \
						$_php $sharing2; $_sleep $sharing_timer' 2>&1 1> /dev/null"
				);
			}
		}
	}
}