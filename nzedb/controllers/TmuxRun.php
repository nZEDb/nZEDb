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
					case 'fixnames':
						$this->_runFixReleaseNames($this->runVar);
						break;
					case 'nonamazon':
						$this->_runNonAmazon($this->runVar);
						break;
					case 'ppadditional':
						return $this->_runPPAdditional($this->runVar);
						break;
					case 'removecrap':
						return $this->_runRemoveCrap($this->runVar);
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
					{$this->runVar['commands']['_php']} {$this->runVar['paths']['misc']}update/decrypt_hashes.php 1000 $log; \
					date +\"%D %T\"; {$this->runVar['commands']['_sleep']} {$this->runVar['settings']['dehash_timer']}' 2>&1 1> /dev/null"
				);
				break;
			case 2:
				$log = $this->writelog($this->runVar['panes']['one'][3]);
				shell_exec("tmux respawnp -t{$this->runVar['constants']['tmux_session']}:1.3 ' \
					{$this->runVar['commands']['_php']} {$this->runVar['paths']['misc']}update/nix/tmux/bin/postprocess_pre.php {$this->runVar['constants']['pre_lim']} $log; \
					date +\"%D %T\"; {$this->runVar['commands']['_sleep']} {$this->runVar['settings']['dehash_timer']}' 2>&1 1> /dev/null"
				);
				break;
			case 3:
				$log = $this->writelog($this->runVar['panes']['one'][3]);
				shell_exec("tmux respawnp -t{$this->runVar['constants']['tmux_session']}:1.3 ' \
					{$this->runVar['commands']['_php']} {$this->runVar['paths']['misc']}update/nix/tmux/bin/postprocess_pre.php {$this->runVar['constants']['pre_lim']} $log; \
					{$this->runVar['commands']['_php']} {$this->runVar['paths']['misc']}update/decrypt_hashes.php 1000 $log; \
					date +\"%D %T\"; {$this->runVar['commands']['_sleep']} {$this->runVar['settings']['dehash_timer']}' 2>&1 1> /dev/null"
				);
				break;
			default:
				$color = $this->get_color($this->runVar['settings']['colors_start'], $this->runVar['settings']['colors_end'], $this->runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$this->runVar['constants']['tmux_session']}:1.3 'echo \"\033[38;5;${color}m\n{$this->runVar['panes']['one'][3]} has been disabled/terminated by Decrypt Hashes\"'");
				break;
		}
	}

	protected function _runFixReleaseNames($runVar)
	{
		switch ($runVar['settings']['fix_names']) {
			case 1:
				$log = $this->writelog($runVar['panes']['one'][0]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.0 ' \
					{$runVar['commands']['_python']} {$runVar['paths']['misc']}update/python/groupfixrelnames_threaded.py md5 $log; \
					{$runVar['commands']['_python']} {$runVar['paths']['misc']}update/python/groupfixrelnames_threaded.py filename $log; \
					{$runVar['commands']['_python']} {$runVar['paths']['misc']}update/python/groupfixrelnames_threaded.py nfo $log; \
					{$runVar['commands']['_python']} {$runVar['paths']['misc']}update/python/groupfixrelnames_threaded.py par2 $log; \
					{$runVar['commands']['_python']} {$runVar['paths']['misc']}update/python/groupfixrelnames_threaded.py miscsorter $log; \
					{$runVar['commands']['_python']} {$runVar['paths']['misc']}update/python/groupfixrelnames_threaded.py predbft $log; date +\"%D %T\"; \
					{$runVar['commands']['_sleep']} {$runVar['settings']['fix_timer']}' 2>&1 1> /dev/null"
				);
				break;
			default:
				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.0 'echo \"\033[38;5;${color}m\n{$runVar['panes']['one'][0]} has been disabled/terminated by Fix Release Names\"'");
				break;
		}
	}

	protected function _runAmazon($runVar)
	{
		$this->runVar = $runVar;

		switch (true) {
			case $this->runVar['settings']['post_amazon'] == 1 && ($this->runVar['counts']['now']['processmusic'] > 0 || $this->runVar['counts']['now']['processbooks'] > 0 || $this->runVar['counts']['now']['processgames'] > 0 || $this->runVar['counts']['now']['apps'] > 0 || $this->runVar['counts']['now']['processxxx'] > 0)
					&& ($this->runVar['settings']['processbooks'] == 1 || $this->runVar['settings']['processmusic'] == 1 || $this->runVar['settings']['processgames'] == 1  || $this->runVar['settings']['processxxx'] == 1):
				$log = $this->writelog($this->runVar['panes']['two'][2]);
				shell_exec("tmux respawnp -t{$this->runVar['constants']['tmux_session']}:2.2 ' \
						{$this->runVar['commands']['_phpn']} {$this->runVar['paths']['misc']}update/postprocess.php amazon true $log; date +\"%D %T\"; {$this->runVar['commands']['_sleep']} {$this->runVar['settings']['post_timer_amazon']}' 2>&1 1> /dev/null"
				);
				break;
			case $this->runVar['settings']['post_amazon'] == 1 && $this->runVar['settings']['processbooks'] == 0 && $this->runVar['settings']['processmusic'] == 0 && $this->runVar['settings']['processgames'] == 0 && $this->runVar['settings']['processxxx'] == 0:
				$color = $this->get_color($this->runVar['settings']['colors_start'], $this->runVar['settings']['colors_end'], $this->runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$this->runVar['constants']['tmux_session']}:2.2 'echo \"\033[38;5;${color}m\n{$this->runVar['panes']['two'][2]} has been disabled/terminated in Admin Disable Music/Books/Console/XXX\"'");
				break;
			case $this->runVar['settings']['post_amazon'] == 1 && $this->runVar['counts']['now']['processmusic'] == 0 && $this->runVar['counts']['now']['processbooks'] == 0 && $this->runVar['counts']['now']['processgames'] == 0 && $this->runVar['counts']['now']['apps'] == 0 && $this->runVar['counts']['now']['processxxx'] == 0:
				$color = $this->get_color($this->runVar['settings']['colors_start'], $this->runVar['settings']['colors_end'], $this->runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$this->runVar['constants']['tmux_session']}:2.2 'echo \"\033[38;5;${color}m\n{$this->runVar['panes']['two'][2]} has been disabled/terminated by No Music/Books/Console/Games/XXX to process\"'");
				break;
			default:
				$color = $this->get_color($this->runVar['settings']['colors_start'], $this->runVar['settings']['colors_end'], $this->runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$this->runVar['constants']['tmux_session']}:2.2 'echo \"\033[38;5;${color}m\n{$this->runVar['panes']['two'][2]} has been disabled/terminated by Postprocess Amazon\"'");
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
						{$this->runVar['commands']['_php']} {$this->runVar['paths']['misc']}update/nix/multiprocessing/postprocess.php tv {$this->runVar['modsettings']['clean']} $log; \
						{$this->runVar['commands']['_php']} {$this->runVar['paths']['misc']}update/nix/multiprocessing/postprocess.php mov {$this->runVar['modsettings']['clean']} $log; \
						date +\"%D %T\"; {$this->runVar['commands']['_sleep']} {$this->runVar['settings']['post_timer_non']}' 2>&1 1> /dev/null"
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

	protected function _runPPAdditional($runVar)
	{
		$this->runVar = $runVar;

		//run postprocess_releases additional
		switch (true) {
			case $this->runVar['settings']['post'] == 1 && ($this->runVar['counts']['now']['work'] + $this->runVar['counts']['now']['apps'] + $this->runVar['counts']['now']['processxxx']) > 0:
				$log = $this->writelog($this->runVar['panes']['two'][0]);
				shell_exec("tmux respawnp -t{$this->runVar['constants']['tmux_session']}:2.0 'echo \"\033[38;5;${color}m\"; \
						{$this->runVar['commands']['_php']} {$this->runVar['paths']['misc']}update/nix/multiprocessing/postprocess.php add $log; date +\"%D %T\"; {$this->runVar['commands']['_sleep']} {$this->runVar['settings']['post_timer']}' 2>&1 1> /dev/null"
				);
				break;
			case $this->runVar['settings']['post'] == 2 && $this->runVar['counts']['now']['processnfo'] > 0:
				$log = $this->writelog($this->runVar['panes']['two'][0]);
				shell_exec("tmux respawnp -t{$this->runVar['constants']['tmux_session']}:2.0 ' \
						{$this->runVar['commands']['_php']} {$this->runVar['paths']['misc']}update/nix/multiprocessing/postprocess.php nfo $log; date +\"%D %T\"; {$this->runVar['commands']['_sleep']} {$this->runVar['settings']['post_timer']}' 2>&1 1> /dev/null"
				);
				break;
			case $this->runVar['settings']['post'] == 3 && ($this->runVar['counts']['now']['processnfo'] > 0 || $this->runVar['counts']['now']['work'] + $this->runVar['counts']['now']['apps'] + $this->runVar['counts']['now']['processxxx'] > 0):
				//run postprocess_releases additional
				$log = $this->writelog($this->runVar['panes']['two'][0]);
				shell_exec("tmux respawnp -t{$this->runVar['constants']['tmux_session']}:2.0 ' \
						{$this->runVar['commands']['_php']} {$this->runVar['paths']['misc']}update/nix/multiprocessing/postprocess.php add $log; \
						{$this->runVar['commands']['_php']} {$this->runVar['paths']['misc']}update/nix/multiprocessing/postprocess.php nfo $log; date +\"%D %T\"; {$this->runVar['commands']['_sleep']} {$this->runVar['settings']['post_timer']}' 2>&1 1> /dev/null"
				);
				break;
			case $this->runVar['settings']['post'] != 0 && ($this->runVar['counts']['now']['processnfo'] == 0) && ($this->runVar['counts']['now']['work'] + $this->runVar['counts']['now']['apps'] + $this->runVar['counts']['now']['processxxx'] == 0):
				$color = $this->get_color($this->runVar['settings']['colors_start'], $this->runVar['settings']['colors_end'], $this->runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$this->runVar['constants']['tmux_session']}:2.0 'echo \"\033[38;5;${color}m\n{$this->runVar['panes']['two'][0]} has been disabled/terminated by No Misc/Nfo to process\"'");
				break;
			default:
				$color = $this->get_color($this->runVar['settings']['colors_start'], $this->runVar['settings']['colors_end'], $this->runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$this->runVar['constants']['tmux_session']}:2.0 'echo \"\033[38;5;${color}m\n{$this->runVar['panes']['two'][0]} has been disabled/terminated by Postprocess Additional\"'");
				break;
		}
	}

	protected function _runRemoveCrap($runVar)
	{
		$this->runVar = $runVar;

		switch ($this->runVar['settings']['fix_crap_opt']) {

			// Do all types up to 2 hours.
			case 'All':
				$log = $this->writelog($this->runVar['panes']['one'][1]);
				shell_exec("tmux respawnp -t{$this->runVar['constants']['tmux_session']}:1.1 ' \
						{$this->runVar['commands']['_php']} {$this->runVar['paths']['misc']}testing/Release/removeCrapReleases.php true 2 $log; \
						date +\"%D %T\"; {$this->runVar['commands']['_sleep']} {$this->runVar['settings']['crap_timer']}' 2>&1 1> /dev/null"
				);
				break;
			// The user has specified custom types.
			case 'Custom':
				$log = $this->writelog($this->runVar['panes']['one'][1]);

				// Check how many types the user picked.
				$this->runVar['modsettings']['fc']['max'] = count($this->runVar['modsettings']['fix_crap']);

				// Make sure the user actually selected some.
				if ($this->runVar['modsettings']['fc']['max'] > 0) {

					// If this is the first run, do a full run, else run on last 2 hours of releases.
					$this->runVar['modsettings']['fc']['time'] = '4';
					if ((($this->runVar['counts']['iterations'] == 1) || $this->runVar['modsettings']['fc']['firstrun'])) {
						$this->runVar['modsettings']['fc']['time'] = 'full';
					}

					//Check to see if the pane is dead, if so respawn it.
					if (shell_exec("tmux list-panes -t{$this->runVar['constants']['tmux_session']}:1 | grep ^1 | grep -c dead") == 1) {

						// Run remove crap releases.
						shell_exec("tmux respawnp -t{$this->runVar['constants']['tmux_session']}:1.1 ' \
							echo \"Running removeCrapReleases for {$this->runVar['modsettings']['fix_crap'][$this->runVar['modsettings']['fc']['num']]}\"; \
							{$this->runVar['commands']['_phpn']} {$this->runVar['paths']['misc']}testing/Release/removeCrapReleases.php true \
							{$this->runVar['modsettings']['fc']['time']} {$this->runVar['modsettings']['fix_crap'][$this->runVar['modsettings']['fc']['num']]} $log; \
							date +\"%D %T\"; {$this->runVar['commands']['_sleep']} {$this->runVar['settings']['crap_timer']}' 2>&1 1> /dev/null"
						);

						// Increment so we know which type to run next.
						$this->runVar['modsettings']['fc']['num']++;
					}

					// If we reached the end, reset the type.
					if ($this->runVar['modsettings']['fc']['num'] == $this->runVar['modsettings']['fc']['max']) {
						$this->runVar['modsettings']['fc']['num'] = 0;
						// And say we are not on the first run, so we run 2 hours the next times.
						$this->runVar['modsettings']['fc']['firstrun'] = false;
					}
				}
				break;
			case 'Disabled':
			default:
				$color = $this->get_color($this->runVar['settings']['colors_start'], $this->runVar['settings']['colors_end'], $this->runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$this->runVar['constants']['tmux_session']}:1.1 'echo \"\033[38;5;${color}m\n{$this->runVar['panes']['one'][1]} has been disabled/terminated by Remove Crap Releases\"'");
				break;
		}
		return $this->runVar['modsettings']['fc'];
	}

	protected function _runUpdateTv($runVar)
	{
		$this->runVar = $runVar;

		switch (true) {
			case $this->runVar['settings']['update_tv'] == 1 && (time() - $this->runVar['timers']['timer4'] >= $this->runVar['settings']['tv_timer'] || $this->runVar['counts']['iterations'] == 1):
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

	public function notRunningNonSeq($runVar)
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

	public function notRunningBasicSeq($runVar)
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

	public function notRunningCompSeq($runVar)
	{
		$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
		for ($g = 1; $g <= 2; $g++) {
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.$g 'echo \"\033[38;5;${color}m\n{$runVar['panes']['zero'][$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 1; $g++) {
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.$g 'echo \"\033[38;5;${color}m\n{$runVar['panes']['one'][$g]} has been disabled/terminated by Running\"'");
		}
	}

	public function run_ircscraper($tmux_session, $_php, $pane, $run_ircscraper, &$runVar)
	{
		$this->runVar = $runVar;
		if ($run_ircscraper == 1) {
			//Check to see if the pane is dead, if so respawn it.
			if (shell_exec("tmux list-panes -t${tmux_session}:${pane} | grep ^0 | grep -c dead") == 1) {
				$ircscraper = $this->runVar['paths']['misc'] . "testing/IRCScraper/scrape.php";
				shell_exec(
					"tmux respawnp -t${tmux_session}:${pane}.0 ' \
					$_php $ircscraper true'"
				);
			}
		} else {
			shell_exec("tmux respawnp -t${tmux_session}:${pane}.0 'echo \"\nIRCScraper has been disabled/terminated by IRCSCraper\"'");
		}
	}

	public function run_sharing($tmux_session, $_php, $pane, $_sleep, $sharing_timer, &$runVar)
	{
		$this->runVar = $runVar;
		$sharing = $this->pdo->queryOneRow('SELECT enabled, posting, fetching FROM sharing');
		$tmux = $this->get();
		$tmux_share = (isset($tmux->run_sharing)) ? $tmux->run_sharing : 0;

		if ($tmux_share && $sharing['enabled'] == 1 && ($sharing['posting'] == 1 || $sharing['fetching'] == 1)) {
			if (shell_exec("tmux list-panes -t${tmux_session}:${pane} | grep ^0 | grep -c dead") == 1) {
				$sharing2 = $this->runVar['paths']['misc'] . "/update/postprocess.php sharing true";
				shell_exec(
					"tmux respawnp -t${tmux_session}:${pane}.0 ' \
						$_php $sharing2; $_sleep $sharing_timer' 2>&1 1> /dev/null"
				);
			}
		}
	}

	public function runBasicSequential($runVar)
	{
			$log = $this->writelog($runVar['panes']['zero'][2]);
			if (($runVar['killswitch']['coll'] == false) && ($runVar['killswitch']['pp'] == false) && (time() - $runVar['timers']['timer5'] <= 4800)) {
				switch ($runVar['settings']['binaries_run']) {
					case 0:
						switch ($runVar['settings']['backfill']) {
							case 0:
								//runs rel less than 4800
								if ($runVar['settings']['releases_run'] != 0) {
									shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
										{$runVar['scripts']['releases']} $log; date +\"%D %T\"; echo \"\nbinaries and backfill has been disabled/terminated by Binaries and Backfill\"; \
										{$runVar['commands']['_sleep']} {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
									);
								//runs nothing as all are disabled
								} else if ($runVar['settings']['releases_run'] == 0) {
									shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
										echo \"\nbinaries, backfill and releases have been disabled/terminated by Binaries, Backfill and Releases\"; \
										{$runVar['commands']['_sleep']} {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
									);
								}
								break;
							case 4:
								//runs back/safe/rel less than 4800
								if ($runVar['settings']['releases_run'] != 0) {
									shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
										{$runVar['commands']['_python']} {$runVar['paths']['misc']}update/python/backfill_safe_threaded.py $log; \
										{$runVar['scripts']['releases']} $log; date +\"%D %T\"; echo \"\nbinaries has been disabled/terminated by Binaries\"; \
										{$runVar['commands']['_sleep']} {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
									);
								//runs back/safe less than 4800
								} else if ($runVar['settings']['releases_run'] == 0) {
								shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
									{$runVar['commands']['_python']} {$runVar['paths']['misc']}update/python/backfill_safe_threaded.py $log; date +\"%D %T\"; \
									echo \"\nbinaries and releases have been disabled/terminated by Binaries and Releases\"; \
									{$runVar['commands']['_sleep']} {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
								);
								}
								break;
							default:
								//runs back/rel less than 4800
								if ($runVar['settings']['releases_run'] != 0) {
									shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
										{$runVar['commands']['_python']} {$runVar['paths']['misc']}update/python/backfill_threaded.py $log; \
										{$runVar['scripts']['releases']} $log; date +\"%D %T\"; echo \"\nbinaries has been disabled/terminated by Binaries\"; \
										{$runVar['commands']['_sleep']} {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
									);
								//runs back less than 4800
								} else if ($runVar['settings']['releases_run'] == 0) {
									shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
										{$runVar['commands']['_python']} {$runVar['paths']['misc']}update/python/backfill_threaded.py $log; \
										date +\"%D %T\"; echo \"\nbinaries and releases have been disabled/terminated by Binaries and Releases\"; \
										{$runVar['commands']['_sleep']} {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
									);
								}
								break;
						}
						break;
					default:
						switch ($runVar['settings']['backfill']) {
							case 0:
								//runs bin/rel less than 4800
								if ($runVar['settings']['releases_run'] != 0) {
									shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
										{$runVar['scripts']['binaries']} $log; \
										{$runVar['scripts']['releases']} $log; date +\"%D %T\"; echo \"\nbackfill has been disabled/terminated by Backfill\"; \
										{$runVar['commands']['_sleep']} {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
									);
								//runs bin less than 4800
								} else if ($runVar['settings']['releases_run'] == 0) {
									shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
										{$runVar['scripts']['binaries']} $log; date +\"%D %T\"; echo \"\nbackfill and releases have been disabled/terminated by Backfill and Releases\"; \
										{$runVar['commands']['_sleep']} {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
									);
								}
								break;
							case 4:
								//runs all/safe less than 4800
								if ($runVar['settings']['releases_run'] != 0) {
									shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
										{$runVar['scripts']['binaries']} $log; \
										{$runVar['commands']['_python']} {$runVar['paths']['misc']}update/python/backfill_safe_threaded.py $log; \
										{$runVar['scripts']['releases']} $log; date +\"%D %T\"; \
										{$runVar['commands']['_sleep']} {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
									);
								//runs bin/back/safe less than 4800
								} else if ($runVar['settings']['releases_run'] == 0) {
									shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
										{$runVar['scripts']['binaries']} $log; \
										{$runVar['commands']['_python']} {$runVar['paths']['misc']}update/python/backfill_safe_threaded.py $log; date +\"%D %T\"; \
										echo \"\nreleases has been disabled/terminated by Releases\"; \
										{$runVar['commands']['_sleep']} {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
									);
								}
								break;
							default:
								//runs all less than 4800
								if ($runVar['settings']['releases_run'] != 0) {
									shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
										{$runVar['scripts']['binaries']} $log; \
										{$runVar['commands']['_python']} {$runVar['paths']['misc']}update/python/backfill_threaded.py $log; \
										{$runVar['scripts']['releases']} $log; date +\"%D %T\"; \
										{$runVar['commands']['_sleep']} {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
									);
								//runs bin/back less than 4800
								} else if ($runVar['settings']['releases_run'] == 0) {
									shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
										{$runVar['scripts']['binaries']} $log; \
										{$runVar['commands']['_python']} {$runVar['paths']['misc']}update/python/backfill_threaded.py $log; date +\"%D %T\"; \
										echo \"\nreleases have been disabled/terminated by Releases\"; \
										{$runVar['commands']['_sleep']} {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
									);
								}
								break;
						}
						break;
				}
			} else if (($runVar['killswitch']['coll'] == false) && ($runVar['killswitch']['pp'] == false) && (time() - $runVar['timers']['timer5'] >= 4800)) {
				//run backfill all once and resets the timer
				if ($runVar['settings']['backfill'] != 0) {
					shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.2 ' \
						{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/python/backfill_threaded.py all $log; date +\"%D %T\"; {$runVar['commands']['_sleep']} {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
					);
					$runVar['timers']['timer5'] = time();
				}
				$runVar['timers']['timer5'] = time();
			} else if ((($runVar['killswitch']['coll'] == true) || ($runVar['killswitch']['pp'] == true)) && ($runVar['settings']['releases_run'] != 0)) {
				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 'echo \"\033[38;5;${color}m\"; \
					echo \"\nbinaries and backfill has been disabled/terminated by Exceeding Limits\"; \
					{$runVar['scripts']['releases']} $log; date +\"%D %T\"; echo \"\nbinaries and backfill has been disabled/terminated by Exceeding Limits\"; {$runVar['commands']['_sleep']} {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
				);
			} else if (($runVar['killswitch']['coll'] == true) || ($runVar['killswitch']['pp'] == true)) {
				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 'echo \"\033[38;5;${color}m\n{$runVar['panes']['zero'][2]} has been disabled/terminated by Exceeding Limits\"'");
			}
	}

}