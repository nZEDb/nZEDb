<?php

use nzedb\db\Settings;

/**
 * Tmux pane shell exec functions for pane respawning
 *
 * Class TmuxRun
 */
class TmuxRun extends Tmux
{
	protected $_dateFormat;

	/**
	 * @param Settings $pdo
	 */
	public function __construct(Settings $pdo = null)
	{
		parent::__construct($pdo);
		$dateFormat = $this->pdo->getSetting(
				   [
					   'section'    => 'shell',
					   'subsection' => 'date',
					   'name'       => 'format'
				   ]);
		$this->_dateFormat = empty($dateFormat) ? '%Y-%m-%d %T' : $dateFormat;

	}

	// main switch for running tmux panes
	public function runPane($cmdParam, &$runVar)
	{
		switch ((int) $runVar['constants']['sequential']) {
			case 0:
				switch ((string) $cmdParam) {
					case 'amazon':
						$this->_runAmazon($runVar);
						break;
					case 'dehash':
						$this->_runDehash($runVar);
						break;
					case 'fixnames':
						$this->_runFixReleaseNames($runVar);
						break;
					case 'import':
						$this->_runNZBImport($runVar);
						break;
					case 'main':
						$this->_runMainNon($runVar);
						break;
					case 'nonamazon':
						$this->_runNonAmazon($runVar);
						break;
					case 'notrunning':
						$this->_notRunningNon($runVar);
						break;
					case 'ppadditional':
						$this->_runPPAdditional($runVar);
						break;
					case 'removecrap':
						$this->_runRemoveCrap($runVar);
						break;
					case 'scraper':
						$this->_runIRCScraper(3, $runVar);
						break;
					case 'sharing':
						$this->_runSharing(($runVar['constants']['nntpproxy'] == 1 ? 5 : 4), $runVar);
						break;
					case 'updatetv':
						$this->_runUpdateTv($runVar);
						break;
				}
				break;
			case 1:
				switch ($cmdParam) {
					case 'amazon':
						$this->_runAmazon($runVar);
						break;
					case 'dehash':
						$this->_runDehash($runVar);
						break;
					case 'fixnames':
						$this->_runFixReleaseNames($runVar);
						break;
					case 'import':
						$this->_runNZBImport($runVar);
						break;
					case 'main':
						$this->_runMainBasic($runVar);
						break;
					case 'nonamazon':
						$this->_runNonAmazon($runVar);
						break;
					case 'notrunning':
						$this->_notRunningBasic($runVar);
						break;
					case 'ppadditional':
						$this->_runPPAdditional($runVar);
						break;
					case 'removecrap':
						$this->_runRemoveCrap($runVar);
						break;
					case 'scraper':
						$this->_runIRCScraper(3, $runVar);
						break;
					case 'sharing':
						$this->_runSharing(($runVar['constants']['nntpproxy'] == 1 ? 5 : 4), $runVar);
						break;
					case 'updatetv':
						$this->_runUpdateTv($runVar);
						break;
				}
				break;
			case 2:
				switch ($cmdParam) {
					case 'amazon':
						$this->_runAmazonFull($runVar);
						break;
					case 'main':
						$this->_runMainFull($runVar);
						break;
					case 'notrunning':
						$this->_notRunningFull($runVar);
						break;
					case 'scraper':
						$this->_runIRCScraper(2, $runVar);
						break;
					case 'sharing':
						$this->_runSharing(($runVar['constants']['nntpproxy'] == 1 ? 4 : 3), $runVar);
						break;
					case 'updatetv':
						$this->_runUpdateTvFull($runVar);
						break;
				}
				break;
		}
	}

	protected function _runDehash(&$runVar)
	{
		switch ($runVar['settings']['dehash']) {
			case 1:
				$log = $this->writelog($runVar['panes']['one'][3]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.3 ' \
					{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/decrypt_hashes.php 1000 $log; \
					date +\"{$this->_dateFormat}\"; {$runVar['commands']['_sleep']} {$runVar['settings']['dehash_timer']}' 2>&1 1> /dev/null"
				);
				break;
			case 2:
				$log = $this->writelog($runVar['panes']['one'][3]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.3 ' \
					{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/tmux/bin/postprocess_pre.php {$runVar['constants']['pre_lim']} $log; \
					date +\"{$this->_dateFormat}\"; {$runVar['commands']['_sleep']} {$runVar['settings']['dehash_timer']}' 2>&1 1> /dev/null"
				);
				break;
			case 3:
				$log = $this->writelog($runVar['panes']['one'][3]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.3 ' \
					{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/tmux/bin/postprocess_pre.php {$runVar['constants']['pre_lim']} $log; \
					{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/decrypt_hashes.php 1000 $log; \
					date +\"{$this->_dateFormat}\"; {$runVar['commands']['_sleep']} {$runVar['settings']['dehash_timer']}' 2>&1 1> /dev/null"
				);
				break;
			default:
				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.3 'echo \"\033[38;5;${color}m\n{$runVar['panes']['one'][3]} has been disabled/terminated by Decrypt Hashes\"'");
		}
	}

	protected function _runFixReleaseNames(&$runVar)
	{
		switch ($runVar['settings']['fix_names']) {
			case 1:
				$log = $this->writelog($runVar['panes']['one'][0]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.0 ' \
					{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/multiprocessing/fixrelnames.php md5 $log; \
					{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/multiprocessing/fixrelnames.php filename $log; \
					{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/multiprocessing/fixrelnames.php nfo $log; \
					{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/multiprocessing/fixrelnames.php par2 $log; \
					{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/multiprocessing/fixrelnames.php miscsorter $log; \
					{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/multiprocessing/fixrelnames.php predbft $log; date +\"{$this->_dateFormat}\"; \
					{$runVar['commands']['_sleep']} {$runVar['settings']['fix_timer']}' 2>&1 1> /dev/null"
				);
				break;
			default:
				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.0 'echo \"\033[38;5;${color}m\n{$runVar['panes']['one'][0]} has been disabled/terminated by Fix Release Names\"'");
		}
	}

	protected function _runAmazon(&$runVar)
	{
		switch (true) {
			case $runVar['settings']['post_amazon'] == 1 && ($runVar['counts']['now']['processmusic'] > 0
					|| $runVar['counts']['now']['processbooks'] > 0 || $runVar['counts']['now']['processconsole'] > 0
						|| $runVar['counts']['now']['processgames'] > 0 || $runVar['counts']['now']['processxxx'] > 0)
							&& ($runVar['settings']['processbooks'] == 1 || $runVar['settings']['processmusic'] == 1
								|| $runVar['settings']['processgames'] == 1  || $runVar['settings']['processxxx'] == 1):

				$log = $this->writelog($runVar['panes']['two'][2]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:2.2 ' \
						{$runVar['commands']['_phpn']} {$runVar['paths']['misc']}update/postprocess.php amazon true $log; date +\"{$this->_dateFormat}\"; {$runVar['commands']['_sleep']} {$runVar['settings']['post_timer_amazon']}' 2>&1 1> /dev/null"
				);
				break;
			case $runVar['settings']['post_amazon'] == 1 && $runVar['settings']['processbooks'] == 0
					&& $runVar['settings']['processmusic'] == 0 && $runVar['settings']['processgames'] == 0
						&& $runVar['settings']['processxxx'] == 0:

				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.2 \
					'echo \"\033[38;5;${color}m\n{$runVar['panes']['two'][2]} has been disabled/terminated in Admin Disable Music/Books/Console/XXX\"'");
				break;
			case $runVar['settings']['post_amazon'] == 1 && $runVar['counts']['now']['processmusic'] == 0 && $runVar['counts']['now']['processbooks'] == 0 && $runVar['counts']['now']['processconsole'] == 0 && $runVar['counts']['now']['processgames'] == 0 && $runVar['counts']['now']['processxxx'] == 0:
				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.2 \
					'echo \"\033[38;5;${color}m\n{$runVar['panes']['two'][2]} has been disabled/terminated by No Music/Books/Console/Games/XXX to process\"'");
				break;
			default:
				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.2 \
					'echo \"\033[38;5;${color}m\n{$runVar['panes']['two'][2]} has been disabled/terminated by Postprocess Amazon\"'");
		}
	}

	protected function _runAmazonFull(&$runVar)
	{
		switch (true) {
			case ($runVar['settings']['post_amazon'] == 1) && (($runVar['counts']['now']['processmusic'] > 0)
					|| ($runVar['counts']['now']['processbooks'] > 0) || ($runVar['counts']['now']['processconsole'] > 0)
						|| ($runVar['counts']['now']['processgames'] > 0) || ($runVar['counts']['now']['processxxx'] > 0))
							&& (($runVar['settings']['processbooks'] != 0) || ($runVar['settings']['processconsole'] != 0)
								|| ($runVar['settings']['processmusic'] != 0) || ($runVar['settings']['processgames'] != 0)
									|| ($runVar['settings']['processxxx'] != 0)):

				$log = $this->writelog($runVar['panes']['one'][1]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.1 ' \
						{$runVar['commands']['_phpn']} {$runVar['paths']['misc']}update/postprocess.php amazon true $log; \
						date +\"{$this->_dateFormat}\"; {$runVar['commands']['_sleep']} {$runVar['settings']['post_timer_amazon']}' 2>&1 1> /dev/null"
				);
				break;
			case ($runVar['settings']['post_amazon'] == 1) && ($runVar['settings']['processbooks'] == 0)
					&& ($runVar['counts']['now']['processconsole'] = 0) && ($runVar['settings']['processmusic'] == 0)
						&& ($runVar['settings']['processgames'] == 0):

				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.1 \
					'echo \"\033[38;5;${color}m\n{$runVar['panes']['one'][1]} has been disabled/terminated in Admin Disable Music/Books/Console/XXX\"'"
				);
				break;
			case ($runVar['settings']['post_amazon'] == 1) && ($runVar['counts']['now']['processmusic'] == 0)
					&& ($runVar['counts']['now']['processbooks'] == 0) && ($runVar['counts']['now']['processconsole'] == 0)
						&& ($runVar['counts']['now']['processgames'] == 0) && ($runVar['counts']['now']['processxxx'] == 0):

				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.1 \
					'echo \"\033[38;5;${color}m\n{$runVar['panes']['one'][1]} has been disabled/terminated by No Music/Books/Console/Games/XXX to process\"'"
				);
				break;
			default:
				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.1 \
					'echo \"\033[38;5;${color}m\n{$runVar['panes']['one'][1]} has been disabled/terminated by Postprocess Amazon\"'"
				);
		}
	}

	protected function _runNonAmazon(&$runVar)
	{
		switch (true) {
			case $runVar['settings']['post_non'] != 0 && ($runVar['counts']['now']['processmovies'] > 0 || $runVar['counts']['now']['processtvrage'] > 0 || $runVar['counts']['now']['processanime'] > 0):
				$log = $this->writelog($runVar['panes']['two'][1]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:2.1 ' \
						{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/multiprocessing/postprocess.php tv {$runVar['modsettings']['clean']} $log; \
						{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/multiprocessing/postprocess.php mov {$runVar['modsettings']['clean']} $log; \
						{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/postprocess.php anime true $log; \
						date +\"{$this->_dateFormat}\"; {$runVar['commands']['_sleep']} {$runVar['settings']['post_timer_non']}' 2>&1 1> /dev/null"
				);
				break;
			case $runVar['settings']['post_non'] != 0 && $runVar['counts']['now']['processmovies'] == 0 && $runVar['counts']['now']['processtvrage'] == 0 && $runVar['counts']['now']['processanime'] == 0:
				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.1 \
					'echo \"\033[38;5;${color}m\n{$runVar['panes']['two'][1]} has been disabled/terminated by No Movies/TV/Anime to process\"'");
				break;
			default:
				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.1 \
					'echo \"\033[38;5;${color}m\n{$runVar['panes']['two'][1]} has been disabled/terminated by Postprocess Non-Amazon\"'");
		}
	}

	protected function _runNonUpdateBinaries(&$runVar)
	{
		//run update_binaries
		//$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
		if (($runVar['settings']['binaries_run'] != 0) && ($runVar['killswitch']['coll'] == false) && ($runVar['killswitch']['pp'] == false)) {
			$log = $this->writelog($runVar['panes']['zero'][2]);
			shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
					{$runVar['scripts']['binaries']} $log; date +\"{$this->_dateFormat}\"; {$runVar['commands']['_sleep']} {$runVar['settings']['bins_timer']}' 2>&1 1> /dev/null"
			);
		} else if (($runVar['killswitch']['coll'] == true) || ($runVar['killswitch']['pp'] == true)) {
			$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.2 'echo \"\033[38;5;${color}m\n{$runVar['panes']['zero'][2]} has been disabled/terminated by Exceeding Limits\"'");
		} else {
			$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.2 'echo \"\033[38;5;${color}m\n{$runVar['panes']['zero'][2]} has been disabled/terminated by Binaries\"'");
		}
	}

	protected function _runNonBackfill(&$runVar)
	{
		//run backfill
		$backsleep = ($runVar['settings']['progressive'] == 1 && floor($runVar['counts']['now']['collections_table'] / 500) > $runVar['settings']['back_timer']
					? floor($runVar['counts']['now']['collections_table'] / 500)
					: $runVar['settings']['back_timer']
		);

		if (($runVar['settings']['backfill'] != 0) && ($runVar['killswitch']['coll'] == false) && ($runVar['killswitch']['pp'] == false)) {
			$log = $this->writelog($runVar['panes']['zero'][3]);
			shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.3 ' \
				{$runVar['scripts']['backfill']} $log; date +\"{$this->_dateFormat}\"; {$runVar['commands']['_sleep']} $backsleep' 2>&1 1> /dev/null"
			);
		} else if (($runVar['killswitch']['coll'] == true) || ($runVar['killswitch']['pp'] == true)) {
			$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.3 'echo \"\033[38;5;${color}m\n{$runVar['panes']['zero'][3]} has been disabled/terminated by Exceeding Limits\"'");
		} else {
			$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.3 'echo \"\033[38;5;${color}m\n{$runVar['panes']['zero'][3]} has been disabled/terminated by Backfill\"'");
		}
	}

	protected function _runNonUpdateReleases(&$runVar)
	{
		//run update_releases
		if ($runVar['settings']['releases_run'] != 0) {
			$log = $this->writelog($runVar['panes']['zero'][4]);
			shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.4 ' \
					{$runVar['scripts']['releases']} $log; date +\"{$this->_dateFormat}\"; {$runVar['commands']['_sleep']} {$runVar['settings']['rel_timer']}' 2>&1 1> /dev/null"
			);
		} else {
			$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.4 'echo \"\033[38;5;${color}m\n{$runVar['panes']['zero'][4]} has been disabled/terminated by Releases\"'");
		}
	}

	protected function _runNZBImport(&$runVar)
	{
		switch ($runVar['settings']['import']) {
			case 1:
				$useFilenames = 'false';
				break;
			case 2:
				$useFilenames = 'true';
				break;
			default:
				$useFilenames = 'false';
		}

		if (($runVar['settings']['import'] != 0) && ($runVar['killswitch']['pp'] == false)) {
			$log = $this->writelog($runVar['panes']['zero'][1]);
			shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.1 ' \
				{$runVar['commands']['_phpn']} {$runVar['paths']['misc']}update/nix/multiprocessing/import.php {$runVar['settings']['nzbs']} {$runVar['settings']['nzbthreads']} true {$useFilenames} false $log; \
				date +\"{$this->_dateFormat}\"; {$runVar['commands']['_sleep']} {$runVar['settings']['import_timer']}' 2>&1 1> /dev/null"
			);

		} else if ($runVar['killswitch']['pp'] == true) {
			$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.1 'echo \"\033[38;5;${color}m\n{$runVar['panes']['zero'][1]} has been disabled/terminated by Exceeding Limits\"'");

		} else {
			$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.1 'echo \"\033[38;5;${color}m\n{$runVar['panes']['zero'][1]} has been disabled/terminated by Import\"'");
		}
	}

	protected function _runPPAdditional(&$runVar)
	{
		//run postprocess_releases additional
		switch (true) {
			case ($runVar['settings']['post'] == 1) && ($runVar['counts']['now']['work'] > 0):
				$log = $this->writelog($runVar['panes']['two'][0]);
				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:2.0 'echo \"\033[38;5;${color}m\"; \
						{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/multiprocessing/postprocess.php add $log; date +\"{$this->_dateFormat}\"; {$runVar['commands']['_sleep']} {$runVar['settings']['post_timer']}' 2>&1 1> /dev/null"
				);
				$runVar['timers']['timer3'] = time();
				break;
			case ($runVar['settings']['post'] == 2) && ($runVar['counts']['now']['processnfo'] > 0):
				$log = $this->writelog($runVar['panes']['two'][0]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:2.0 ' \
						{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/multiprocessing/postprocess.php nfo $log; date +\"{$this->_dateFormat}\"; {$runVar['commands']['_sleep']} {$runVar['settings']['post_timer']}' 2>&1 1> /dev/null"
				);
				$runVar['timers']['timer3'] = time();
				break;
			case ($runVar['settings']['post'] == 3) && (($runVar['counts']['now']['processnfo'] > 0) || ($runVar['counts']['now']['work'] > 0)):
				//run postprocess_releases additional
				$log = $this->writelog($runVar['panes']['two'][0]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:2.0 ' \
						{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/multiprocessing/postprocess.php add $log; \
						{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/multiprocessing/postprocess.php nfo $log; \
						date +\"{$this->_dateFormat}\"; {$runVar['commands']['_sleep']} {$runVar['settings']['post_timer']}' 2>&1 1> /dev/null"
				);
				$runVar['timers']['timer3'] = time();
				break;
			case ($runVar['settings']['post'] != 0) && ($runVar['counts']['now']['processnfo'] == 0) && ($runVar['counts']['now']['work'] == 0):
				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.0 \
					'echo \"\033[38;5;${color}m\n{$runVar['panes']['two'][0]} has been disabled/terminated by No Misc/Nfo to process\"'");
				break;
			default:
				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.0 'echo \"\033[38;5;${color}m\n{$runVar['panes']['two'][0]} has been disabled/terminated by Postprocess Additional\"'");
		}
	}

	protected function _runRemoveCrap(&$runVar)
	{
		switch ($runVar['settings']['fix_crap_opt']) {

			// Do all types up to 2 hours.
			case 'All':
				$log = $this->writelog($runVar['panes']['one'][1]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.1 ' \
						{$runVar['commands']['_php']} {$runVar['paths']['misc']}testing/Release/removeCrapReleases.php true 2 $log; \
						date +\"{$this->_dateFormat}\"; {$runVar['commands']['_sleep']} {$runVar['settings']['crap_timer']}' 2>&1 1> /dev/null"
				);
				break;
			// The user has specified custom types.
			case 'Custom':
				$log = $this->writelog($runVar['panes']['one'][1]);

				// Check how many types the user picked.
				$runVar['modsettings']['fc']['max'] = count($runVar['modsettings']['fix_crap']);

				// Make sure the user actually selected some.
				if ($runVar['modsettings']['fc']['max'] > 0) {

					// If this is the first run, do a full run, else run on last 2 hours of releases.
					$runVar['modsettings']['fc']['time'] = '4';
					if ((($runVar['counts']['iterations'] == 1) || $runVar['modsettings']['fc']['firstrun'])) {
						$runVar['modsettings']['fc']['time'] = 'full';
					}

					//Check to see if the pane is dead, if so respawn it.
					if (shell_exec("tmux list-panes -t{$runVar['constants']['tmux_session']}:1 | grep ^1 | grep -c dead") == 1) {

						// Run remove crap releases.
						shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.1 ' \
							echo \"Running removeCrapReleases for {$runVar['modsettings']['fix_crap'][$runVar['modsettings']['fc']['num']]}\"; \
							{$runVar['commands']['_phpn']} {$runVar['paths']['misc']}testing/Release/removeCrapReleases.php true \
							{$runVar['modsettings']['fc']['time']} {$runVar['modsettings']['fix_crap'][$runVar['modsettings']['fc']['num']]} $log; \
							date +\"{$this->_dateFormat}\"; {$runVar['commands']['_sleep']} {$runVar['settings']['crap_timer']}' 2>&1 1> /dev/null"
						);

						// Increment so we know which type to run next.
						$runVar['modsettings']['fc']['num']++;
					}

					// If we reached the end, reset the type.
					if ($runVar['modsettings']['fc']['num'] == $runVar['modsettings']['fc']['max']) {
						$runVar['modsettings']['fc']['num'] = 0;
						// And say we are not on the first run, so we run 2 hours the next times.
						$runVar['modsettings']['fc']['firstrun'] = false;
					}
				}
				break;
			case 'Disabled':
			default:
				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.1 'echo \"\033[38;5;${color}m\n{$runVar['panes']['one'][1]} has been disabled/terminated by Remove Crap Releases\"'");
		}
	}

	protected function _runUpdateTv(&$runVar)
	{
		switch (true) {
			case ($runVar['settings']['update_tv'] == 1 && (time() - $runVar['timers']['timer4'] >= $runVar['settings']['tv_timer'])) || ($runVar['counts']['iterations'] == 1):
				$log = $this->writelog($runVar['panes']['one'][3]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.2 ' \
						{$runVar['commands']['_phpn']} {$runVar['paths']['misc']}update/update_theaters.php $log; \
						{$runVar['commands']['_phpn']} {$runVar['paths']['misc']}testing/PostProc/populate_tvrage.php true $log; \
						{$runVar['commands']['_phpn']} {$runVar['paths']['misc']}update/update_tvschedule.php $log; \
						{$runVar['commands']['_phpn']} {$runVar['paths']['misc']}testing/PostProc/updateTvRage.php $log; date +\"{$this->_dateFormat}\"' 2>&1 1> /dev/null"
				);
				$runVar['timers']['timer4'] = time();
				break;
			case $runVar['settings']['update_tv'] == 1:
				$run_time = $this->relativeTime($runVar['settings']['tv_timer'] + $runVar['timers']['timer4']);
				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.2 \
					'echo \"\033[38;5;${color}m\n{$runVar['panes']['one'][2]} will run in T[ $run_time]\"' 2>&1 1> /dev/null");
				break;
			default:
				$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.2 \
					'echo \"\033[38;5;${color}m\n{$runVar['panes']['one'][2]} has been disabled/terminated by Update TV/Theater\"'");
		}
	}

	protected function _runUpdateTvFull(&$runVar)
	{
		if (($runVar['settings']['update_tv'] == 1) && ((time() - $runVar['timers']['timer4'] >= $runVar['settings']['tv_timer']) || ($runVar['counts']['iterations'] == 1))) {
			$log = $this->writelog($runVar['panes']['one'][0]);
			shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.0 ' \
				{$runVar['commands']['_phpn']} {$runVar['paths']['misc']}update/update_theaters.php $log; \
				{$runVar['commands']['_phpn']} {$runVar['paths']['misc']}testing/PostProc/populate_tvrage.php true $log; \
				{$runVar['commands']['_phpn']} {$runVar['paths']['misc']}update/update_tvschedule.php $log; \
				{$runVar['commands']['_phpn']} {$runVar['paths']['misc']}testing/PostProc/updateTvRage.php $log; date +\"{$this->_dateFormat}\"' 2>&1 1> /dev/null"
			);
			$runVar['timers']['timer4'] = time();

		} else if ($runVar['settings']['update_tv'] == 1) {
			$run_time = $this->relativeTime($runVar['settings']['tv_timer'] + $runVar['timers']['timer4']);
			$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
			shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.0 \
				'echo \"\033[38;5;${color}m\n{$runVar['panes']['one'][0]} will run in T[ $run_time]\"' 2>&1 1> /dev/null");

		} else {
			$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.0 \
				'echo \"\033[38;5;${color}m\n{$runVar['panes']['one'][0]} has been disabled/terminated by Update TV/Theater\"'");
		}
	}

	protected function _runMainNon(&$runVar)
	{
		$this->_runNonUpdateBinaries($runVar);
		$this->_runNonUpdateReleases($runVar);
		$this->_runNonBackfill($runVar);
	}

	protected function _runMainBasic(&$runVar)
	{
		$log = $this->writelog($runVar['panes']['zero'][2]);
		if (($runVar['killswitch']['coll'] == false) && ($runVar['killswitch']['pp'] == false) && (time() - $runVar['timers']['timer5'] <= 4800)) {

			$date = 'date +"%Y-%m-%d %T";';
			$sleep = sprintf(
				'%s %s;',
				$runVar['commands']['_sleep'],
				$runVar['settings']['seq_timer']
			);

			switch ($runVar['settings']['binaries_run']) {
				case 0:
					$binaries = 'echo "\nbinaries has been disabled/terminated by Binaries"';
					break;
				case 1:
				case 2:
					$binaries = sprintf(
						'%s %s;',
						$runVar['scripts']['binaries'],
						$log
					);
					break;
				default:
					$binaries = '';
			}

			switch ($runVar['settings']['backfill']) {
				case 0:
					$backfill = 'echo "backfill is disabled in settings";';
					break;
				case 1:
					$backfill = sprintf(
						'%s %s %s;',
						$runVar['scripts']['backfill'],
						$runVar['settings']['backfill_qty'],
						$log
					);
					break;
				case 2:
					$backfill = sprintf(
						'%s %s %s;',
						$runVar['scripts']['backfill'],
						'group',
						$log
					);
					break;
				case 4:
					$backfill = sprintf(
						'%s %s;',
						$runVar['scripts']['backfill'],
						$log
					);
					break;
				default:
					$backfill = '';
			}

			switch ($runVar['settings']['releases_run']) {
				case 0:
					$releases = 'echo "\nreleases have been disabled/terminated by Releases"';
					break;
				case 1:
				case 2:
					$releases = sprintf(
						'%s %s;',
						$runVar['scripts']['releases'],
						$log
					);
					break;
				default:
					$releases = '';
			}

			shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 '$binaries $backfill $releases $date $sleep' 2>&1 1> /dev/null");

		} else if (($runVar['killswitch']['coll'] == false) && ($runVar['killswitch']['pp'] == false) && (time() - $runVar['timers']['timer5'] >= 4800)) {
			//run backfill all once and resets the timer
			if ($runVar['settings']['backfill'] != 0) {
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
					{$runVar['commands']['_python']} {$runVar['paths']['misc']}update/python/backfill_threaded.py all $log; \
					date +\"{$this->_dateFormat}\"; {$runVar['commands']['_sleep']} {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
				);
				$runVar['timers']['timer5'] = time();
			}
			$runVar['timers']['timer5'] = time();

		} else if ((($runVar['killswitch']['coll'] == true) || ($runVar['killswitch']['pp'] == true)) && ($runVar['settings']['releases_run'] != 0)) {
			$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
			shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 'echo \"\033[38;5;${color}m\"; \
				echo \"\nbinaries and backfill has been disabled/terminated by Exceeding Limits\"; \
				{$runVar['scripts']['releases']} $log; date +\"{$this->_dateFormat}\"; echo \"\nbinaries and backfill has been disabled/terminated by Exceeding Limits\"; \
				{$runVar['commands']['_sleep']} {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
			);

		} else if (($runVar['killswitch']['coll'] == true) || ($runVar['killswitch']['pp'] == true)) {
			$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
			shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 'echo \"\033[38;5;${color}m\n{$runVar['panes']['zero'][2]} has been disabled/terminated by Exceeding Limits\"'");
		}
	}

	protected function _runMainFull(&$runVar)
	{
		$log = $this->writelog($runVar['panes']['zero'][2]);
		shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
				{$runVar['paths']['misc']}update/nix/screen/sequential/user_threaded.sh true $log; date +\"{$this->_dateFormat}\"' 2>&1 1> /dev/null"
		);
	}

	protected function _notRunningNon(&$runVar)
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

	protected function _notRunningBasic(&$runVar)
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

	protected function _notRunningFull(&$runVar)
	{
		$color = $this->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
		for ($g = 1; $g <= 2; $g++) {
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.$g 'echo \"\033[38;5;${color}m\n{$runVar['panes']['zero'][$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 1; $g++) {
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.$g 'echo \"\033[38;5;${color}m\n{$runVar['panes']['one'][$g]} has been disabled/terminated by Running\"'");
		}
	}

	protected function _runIRCScraper($pane, &$runVar)
	{
		if ($runVar['constants']['run_ircscraper'] == 1) {
			//Check to see if the pane is dead, if so respawn it.
			if (shell_exec("tmux list-panes -t{$runVar['constants']['tmux_session']}:${pane} | grep ^0 | grep -c dead") == 1) {
				shell_exec(
					"tmux respawnp -t{$runVar['constants']['tmux_session']}:${pane}.0 ' \
					{$runVar['commands']['_phpn']} {$runVar['paths']['misc']}testing/IRCScraper/scrape.php true'"
				);
			}
		} else {
			shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:${pane}.0 'echo \"\nIRCScraper has been disabled/terminated by IRCSCraper\"'");
		}
	}

	protected function _runSharing($pane, &$runVar)
	{
		$sharing = $this->pdo->queryOneRow('SELECT enabled, posting, fetching FROM sharing');

		if ($runVar['settings']['run_sharing'] == 1 && $sharing['enabled'] == 1 && ($sharing['posting'] == 1 || $sharing['fetching'] == 1)) {
			if (shell_exec("tmux list-panes -t{$runVar['constants']['tmux_session']}:${pane} | grep ^0 | grep -c dead") == 1) {
				shell_exec(
					"tmux respawnp -t{$runVar['constants']['tmux_session']}:${pane}.0 ' \
						{$runVar['commands']['_php']} {$runVar['paths']['misc']}/update/postprocess.php sharing true; \
						{$runVar['commands']['_sleep']} {$runVar['settings']['sharing_timer']}' 2>&1 1> /dev/null"
				);
			}
		}
	}
}
