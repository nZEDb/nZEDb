<?php

/**
 * Tmux output functions for printing monitor data
 *
 * Class TmuxOutput
 */
class TmuxOutput extends Tmux
{

	/**
	 * @param $pdo Class instances / Echo to cli?
	 */
	public function __construct($pdo)
	{
		$this->pdo = $pdo;
		parent::__construct($this->pdo);
	}

	public function displayOutput($section, $runVar)
	{
		$this->runVar = $runVar;

		switch ((int) $section) {

			case 1:
				$this->_displayHeader($this->runVar);
				$break;
			case 2:
				$this->_displayMonitor($this->runVar);
				$break;
			case 3:
				$this->_displayQueryBlock($this->runVar);
				break;
		}
	}

	protected function _getColorsMasks($compressed)
	{
		$masks[1] = $this->pdo->log->headerOver("%-18s") . " " . $this->pdo->log->tmuxOrange("%-48.48s");
		$masks[2] = ($compressed == '1' ? $this->pdo->log->headerOver("%-20s") . " " . $this->pdo->log->tmuxOrange("%-33.33s")
					: $this->pdo->log->alternateOver("%-20s") . " " . $this->pdo->log->tmuxOrange("%-33.33s"));
		$masks[3] = $this->pdo->log->header("%-16.16s %25.25s %25.25s");
		$masks[4] = $this->pdo->log->primaryOver("%-16.16s") . " " . $this->pdo->log->tmuxOrange("%25.25s %25.25s");
		$masks[5] = $this->pdo->log->tmuxOrange("%-16.16s %25.25s %25.25s");

		return $masks;
	}

	protected function _displayHeader($runVar)
	{
		$this->runVar = $runVar;
		$versions = \nzedb\utility\Utility::getValidVersionsFile();
		$git = new \nzedb\utility\Git();
		$version = $versions->versions->git->tag . 'r' . $git->commits();

		$masks = $this->_getColorsMasks($this->runVar['settings']['compressed']);

		if ($this->runVar['settings']['is_running'] == 1) {
			printf($masks[2], "Monitor Running v$version [" . $this->runVar['constants']['sqlpatch'] . "]: ", $this->relativeTime($this->runVar['timers']['timer1']));
		} else {
			printf($masks[2], "Monitor Off v$version [" . $this->runVar['constants']['sqlpatch'] . "]: ", $this->relativeTime($this->runVar['timers']['timer1']));
		}
		printf($masks[1],
				"USP Connections:",
				sprintf(
					"%d active (%d total) - %s:%d)",
					$this->runVar['connections']['primary']['active'],
					$this->runVar['connections']['primary']['total'],
					$this->runVar['connections']['host'],
					$this->runVar['connections']['port']
				)
		);
		if ($this->runVar['constants']['alternate_nntp']) {
			printf($masks[1],
					"USP Alternate:",
					sprintf(
						"%d active (%d total) - %s:%d)",
						$this->runVar['connections']['alternate']['active'],
						$this->runVar['connections']['alternate']['total'],
						$this->runVar['connections']['host_a'],
						$this->runVar['connections']['port_a']
					)
			);
		}

		printf($masks[1],
				"Newest Release:",
				$this->runVar['timers']['newOld']['newestrelname']
		);
		printf($masks[1],
				"Release Added:",
				sprintf(
					"%s ago",
					$this->relativeTime($this->runVar['timers']['newOld']['newestrelease'])
				)
		);
		printf($masks[1],
				"Predb Updated:",
				sprintf(
					"%s ago",
					$this->relativeTime($this->runVar['timers']['newOld']['newestpre'])
				)
		);
		printf($masks[1],
				sprintf(
					"Collection Age[%d]:",
					$this->runVar['constants']['delaytime']
				),
				sprintf(
					"%s ago",
					$this->relativeTime($this->runVar['timers']['newOld']['oldestcollection'])
				)
		);
		printf($masks[1],
				"Parts in Repair:",
				number_format($this->runVar['counts']['now']['partrepair_table'])
		);
		if (($this->runVar['settings']['post'] == "1" || $this->runVar['settings']['post'] == "3") && $this->runVar['constants']['sequential'] != 2) {
			printf($masks[1],
					"Postprocess:",
					"stale for " . $this->relativeTime($this->runVar['timers']['timer3'])
			);
		}
		echo PHP_EOL;
	}

	protected function _displayTableCounts($runVar)
	{
		$this->runVar = $runVar;

		$masks = $this->_getColorsMasks($this->runVar['settings']['compressed']);

		printf($masks[3], "Collections", "Binaries", "Parts");
		$this->_displaySeparator($this->runVar['settings']['compressed']);
		printf($masks[5],
				number_format($this->runVar['counts']['now']['collections_table']),
				number_format($this->runVar['counts']['now']['binaries_table']),
				number_format($this->runVar['counts']['now']['parts_table'])
		);
	}

	protected function _displayPaths($runVar)
	{
		$this->runVar = $runVar;
		$monitor_path = $monitor_path_a = $monitor_path_b = "";
		$masks = $this->_getColorsMasks($this->runVar['settings']['compressed']);

		// assign timers from tmux table
		$monitor_path = $this->runVar['settings']['monitor_path'];
		$monitor_path_a = $this->runVar['settings']['monitor_path_a'];
		$monitor_path_b = $this->runVar['settings']['monitor_path_b'];

		if (((isset($monitor_path)) && (file_exists($monitor_path))) ||
			((isset($monitor_path_a)) && (file_exists($monitor_path_a))) ||
				((isset($monitor_path_b)) && (file_exists($monitor_path_b)))) {

			echo "\n";
			printf($masks[3], "File System", "Used", "Free");

			if (isset($monitor_path) && $monitor_path != "" && file_exists($monitor_path)) {
				$disk_use = $this->decodeSize(disk_total_space($monitor_path) - disk_free_space($monitor_path));
				$disk_free = $this->decodeSize(disk_free_space($monitor_path));
				if (basename($monitor_path) == "") {
					$show = "/";
				} else {
					$show = basename($monitor_path);
				}
				printf($masks[4], $show, $disk_use, $disk_free);
			}
			if (isset($monitor_path_a) && $monitor_path_a != "" && file_exists($monitor_path_a)) {
				$disk_use = $this->decodeSize(disk_total_space($monitor_path_a) - disk_free_space($monitor_path_a));
				$disk_free = $this->decodeSize(disk_free_space($monitor_path_a));
				if (basename($monitor_path_a) == "") {
					$show = "/";
				} else {
					$show = basename($monitor_path_a);
				}
				printf($masks[4], $show, $disk_use, $disk_free);
			}
			if (isset($monitor_path_b) && $monitor_path_b != "" && file_exists($monitor_path_b)) {
				$disk_use = $this->decodeSize(disk_total_space($monitor_path_b) - disk_free_space($monitor_path_b));
				$disk_free = $this->decodeSize(disk_free_space($monitor_path_b));
				if (basename($monitor_path_b) == "") {
					$show = "/";
				} else {
					$show = basename($monitor_path_b);
				}
				printf($masks[4], $show, $disk_use, $disk_free);
			}
		}
		echo PHP_EOL;
	}

	protected function _displayMonitor($runVar)
	{
		$this->runVar = $runVar;
		$masks = $this->_getColorsMasks($this->runVar['settings']['compressed']);

		$this->_displayTableCounts($this->runVar);
		$this->_displayPaths($this->runVar);

		printf($masks[3], "Collections", "Binaries", "Parts");
		$this->_displaySeparator($this->runVar['settings']['compressed']);
		printf($masks[5],
				number_format($this->runVar['counts']['now']['collections_table']),
				number_format($this->runVar['counts']['now']['binaries_table']),
				number_format($this->runVar['counts']['now']['parts_table'])
		);

		printf($masks[3], "Category", "In Process", "In Database");
		$this->_displaySeparator($this->runVar['settings']['compressed']);
		printf($masks[4], "predb",
			sprintf(
				"%s(%d)",
				number_format($this->runVar['counts']['now']['predb'] - $this->runVar['counts']['now']['distinct_predb_matched']),
				$this->runVar['counts']['diff']['distinct_predb_matched']
			),
			sprintf(
				"%s(%d%%)",
				number_format($this->runVar['counts']['now']['predb_matched']),
				$this->runVar['counts']['percent']['predb_matched']
			)
		);
		printf($masks[4], "requestID",
			sprintf(
				"%s(%d)",
				number_format($this->runVar['counts']['now']['requestid_inprogress']),
				$this->runVar['counts']['diff']['requestid_inprogress']
			),
			sprintf(
				"%s(%d%%)",
				number_format($this->runVar['counts']['now']['requestid_matched']),
				$this->runVar['counts']['percent']['requestid_matched']
			)
		);
		printf($masks[4], "NFO's",
			sprintf(
				"%s(%d)",
				number_format($this->runVar['counts']['now']['processnfo']),
				$this->runVar['counts']['diff']['processnfo']
			),
			sprintf(
				"%s(%d%%)",
				number_format($this->runVar['counts']['now']['nfo']),
				$this->runVar['counts']['percent']['nfo']
			)
		);
		printf($masks[4], "Console(1000)",
			sprintf(
				"%s(%d)",
				number_format($this->runVar['counts']['now']['processconsole']),
				$this->runVar['counts']['diff']['processgames']
			),
			sprintf(
				"%s(%d%%)",
				number_format($this->runVar['counts']['now']['console']),
				$this->runVar['counts']['percent']['console']
			)
		);
		printf($masks[4], "Movie(2000)",
			sprintf(
				"%s(%d)",
				number_format($this->runVar['counts']['now']['processmovies']),
				$this->runVar['counts']['diff']['processmovies']
			),
			sprintf(
				"%s(%d%%)",
				number_format($this->runVar['counts']['now']['movies']),
				$this->runVar['counts']['percent']['movies']
			)
		);
		printf($masks[4], "Audio(3000)",
			sprintf(
				"%s(%d)",
				number_format($this->runVar['counts']['now']['processmusic']),
				$this->runVar['counts']['diff']['processmusic']
			),
			sprintf(
				"%s(%d%%)",
				number_format($this->runVar['counts']['now']['audio']),
				$this->runVar['counts']['percent']['audio']
			)
		);
		printf($masks[4], "PC(4000)",
			sprintf(
				"%s(%d)",
				number_format($this->runVar['counts']['now']['processgames']),
				$this->runVar['counts']['diff']['processgames']
			),
			sprintf(
				"%s(%d%%)",
				number_format($this->runVar['counts']['now']['pc']),
				$this->runVar['counts']['percent']['pc']
			)
		);
		printf($masks[4], "TV(5000)",
			sprintf(
				"%s(%d)",
				number_format($this->runVar['counts']['now']['processtvrage']),
				$this->runVar['counts']['diff']['processtvrage']
			),
			sprintf(
				"%s(%d%%)",
				number_format($this->runVar['counts']['now']['tv']),
				$this->runVar['counts']['percent']['tv']
			)
		);
		printf($masks[4], "xXx(6000)",
			sprintf(
				"%s(%d)",
				number_format($this->runVar['counts']['now']['processxxx']),
				$this->runVar['counts']['diff']['processxxx']
			),
			sprintf(
				"%s(%d%%)",
				number_format($this->runVar['counts']['now']['xxx']),
				$this->runVar['counts']['percent']['xxx']
			)
		);
		printf($masks[4], "Misc(7000)",
			sprintf(
				"%s(%d)",
				number_format($this->runVar['counts']['now']['work']),
				$this->runVar['counts']['diff']['work']
			),
			sprintf(
				"%s(%d%%)",
				number_format($this->runVar['counts']['now']['misc']),
				$this->runVar['counts']['percent']['misc']
			)
		);
		printf($masks[4], "Books(8000)",
			sprintf(
				"%s(%d)",
				number_format($this->runVar['counts']['now']['processbooks']),
				$this->runVar['counts']['diff']['processbooks']
			),
			sprintf(
				"%s(%d%%)",
				number_format($this->runVar['counts']['now']['books']),
				$this->runVar['counts']['percent']['books']
			)
		);
		printf($masks[4], "Total",
			sprintf(
				"%s(%s)",
				number_format($this->runVar['counts']['now']['total_work']),
				number_format($this->runVar['counts']['diff']['total_work'])
			),
			sprintf(
				"%s(%s)",
				number_format($this->runVar['counts']['now']['releases']),
				number_format($this->runVar['counts']['diff']['releases'])
			)
		);
		echo PHP_EOL;

		$this->_displayBackfill($this->runVar);
	}

	protected function _displayBackfill($runVar)
	{
		$this->runVar = $runVar;
		$masks = $this->_getColorsMasks($this->runVar['settings']['compressed']);

		printf($masks[3], "Groups", "Active", "Backfill");
		$this->_displaySeparator($this->runVar['settings']['compressed']);
		if ($this->runVar['settings']['backfilldays'] == "1") {
			printf($masks[4], "Activated",
				sprintf(
					"%d(%d)",
					$this->runVar['counts']['now']['active_groups'],
					$this->runVar['counts']['now']['all_groups']
				),
				sprintf(
					"%d(%d)",
					$this->runVar['counts']['now']['backfill_groups_days'],
					$this->runVar['counts']['now']['all_groups']
				)
			);
		} else {
			printf($masks[4], "Activated",
				sprintf(
					"%d(%d)",
					$this->runVar['counts']['now']['active_groups'],
					$this->runVar['counts']['now']['all_groups']
				),
				sprintf(
					"%d(%d)",
					$this->runVar['counts']['now']['backfill_groups_date'],
					$this->runVar['counts']['now']['all_groups']
				)
			);
		}
	}

	protected function _displayQueryBlock($runVar)
	{
		$this->runVar = $runVar;
		$masks = $this->_getColorsMasks($this->runVar['settings']['compressed']);

		echo PHP_EOL;
		printf($masks[3], "Query Block", "Time", "Cumulative");
		$this->_displaySeparator($this->runVar['settings']['compressed']);
		printf($masks[4], "Combined",
				sprintf(
					"%d %d %d %d %d %d %d",
					$this->runVar['timers']['query']['tmux_time'],
					$this->runVar['timers']['query']['split_time'],
					$this->runVar['timers']['query']['init_time'],
					$this->runVar['timers']['query']['proc1_time'],
					$this->runVar['timers']['query']['proc2_time'],
					$this->runVar['timers']['query']['proc3_time'],
					$this->runVar['timers']['query']['tpg_time']
				),
				sprintf(
					"%d %d %d %d %d %d %d",
					$this->runVar['timers']['query']['tmux_time'],
					$this->runVar['timers']['query']['split1_time'],
					$this->runVar['timers']['query']['init1_time'],
					$this->runVar['timers']['query']['proc11_time'],
					$this->runVar['timers']['query']['proc21_time'],
					$this->runVar['timers']['query']['proc31_time'],
					$this->runVar['timers']['query']['tpg1_time']
				)
		);

		$pieces = explode(" ", $this->pdo->getAttribute(PDO::ATTR_SERVER_INFO));
		echo $this->pdo->log->primaryOver("\nThreads = ") .
					$this->pdo->log->headerOver($pieces[4]) . $this->pdo->log->primaryOver(', Opens = ') .
					$this->pdo->log->headerOver($pieces[14]) . $this->pdo->log->primaryOver(', Tables = ') .
					$this->pdo->log->headerOver($pieces[22]) . $this->pdo->log->primaryOver(', Slow = ') .
					$this->pdo->log->headerOver($pieces[11]) . $this->pdo->log->primaryOver(', QPS = ') .
					$this->pdo->log->header($pieces[28]);
	}

	protected function _displaySeparator($compressed)
	{
		$masks = $this->_getColorsMasks($compressed);
		printf($masks[3], "======================================", "=========================", "======================================");
	}

	public function getConnectionsInfo($runVar)
	{
		$this->runVar = $runVar;

		if ($this->runVar['constants']['nntpproxy'] == 0) {
			$this->runVar['connections']['port'] = NNTP_PORT;
			$this->runVar['connections']['host'] = NNTP_SERVER;
			$this->runVar['connections']['ip'] = gethostbyname($this->runVar['connections']['host']);
			if ($this->runVar['constants']['alternate_nntp']) {
				$this->runVar['connections']['port_a'] = NNTP_PORT_A;
				$this->runVar['connections']['host_a'] = NNTP_SERVER_A;
				$this->runVar['connections']['ip_a'] = gethostbyname($this->runVar['connections']['host_a']);
			}
		} else {
			$filename = $this->runVar['paths']['misc'] . "update/python/lib/nntpproxy.conf";
			$fp = fopen($filename, "r") or die("Couldn't open $filename");
			while (!feof($fp)) {
				$line = fgets($fp);
				if (preg_match('/"host": "(.+)",$/', $line, $match)) {
					$this->runVar['connections']['host'] = $match[1];
				}
				if (preg_match('/"port": (.+),$/', $line, $match)) {
					$this->runVar['connections']['port'] = $match[1];
					break;
				}
			}
			if ($this->runVar['constants']['alternate_nntp']) {
				$filename = $this->runVar['paths']['misc'] . "update/python/lib/nntpproxy_a.conf";
				$fp = fopen($filename, "r") or die("Couldn't open $filename");
				while (!feof($fp)) {
					$line = fgets($fp);
					if (preg_match('/"host": "(.+)",$/', $line, $match)) {
						$this->runVar['connections']['host_a'] = $match[1];
					}
					if (preg_match('/"port": (.+),$/', $line, $match)) {
						$this->runVar['connections']['port_a'] = $match[1];
						break;
					}
				}
			}
			$this->runVar['connections']['ip'] = gethostbyname($this->runVar['connections']['host']);
			if ($this->runVar['constants']['alternate_nntp']) {
				$this->runVar['connections']['ip_a'] = gethostbyname($this->runVar['connections']['host_a']);
			}
		}
		return $this->runVar['connections'];
	}

	public function getConnectionsCounts(&$runVar)
	{
		$this->runVar = $runVar;

		$this->runVar['connections']['primary']['active'] = $this->runVar['connections']['primary']['total'] =
		$this->runVar['connections']['alternate']['active'] = $this->runVar['connections']['alternate']['total'] = 0;

		$this->runVar['connections']['primary']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $this->runVar['connections']['ip'] . ":" . $this->runVar['connections']['port'] . " | grep -c ESTAB"));
		$this->runVar['connections']['primary']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $this->runVar['connections']['ip'] . ":" . $this->runVar['connections']['port']));
		if ($this->runVar['constants']['alternate_nntp'] == 1) {
			$this->runVar['connections']['alternate']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $this->runVar['connections']['ip_a'] . ":" . $this->runVar['connections']['port_a'] . " | grep -c ESTAB"));
			$this->runVar['connections']['alternate']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $this->runVar['connections']['ip_a'] . ":" . $this->runVar['connections']['port_a']));
		}
		if ($this->runVar['connections']['primary']['active'] == 0 && $this->runVar['connections']['primary']['total'] == 0 && $this->runVar['connections']['alternate']['active'] == 0 && $this->runVar['connections']['alternate']['total'] == 0 && $this->runVar['connections']['port'] != $this->runVar['connections']['port_a']) {
				$this->runVar['connections']['primary']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $this->runVar['connections']['ip'] . ":https | grep -c ESTAB"));
				$this->runVar['connections']['primary']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $this->runVar['connections']['ip'] . ":https"));
				if ($this->runVar['constants']['alternate_nntp'] == 1) {
					$this->runVar['connections']['alternate']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $this->runVar['connections']['ip_a'] . ":https | grep -c ESTAB"));
					$this->runVar['connections']['alternate']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $this->runVar['connections']['ip_a'] . ":https"));
				}
		}
		if ($this->runVar['connections']['primary']['active'] == 0 && $this->runVar['connections']['primary']['total'] == 0 && $this->runVar['connections']['alternate']['active'] == 0 && $this->runVar['connections']['alternate']['total'] == 0 && $this->runVar['connections']['port'] != $this->runVar['connections']['port_a']) {
			$this->runVar['connections']['primary']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $this->runVar['connections']['port'] . " | grep -c ESTAB"));
			$this->runVar['connections']['primary']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $this->runVar['connections']['port']));
			if ($this->runVar['constants']['alternate_nntp'] == 1) {
				$this->runVar['connections']['alternate']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $this->runVar['connections']['port_a'] . " | grep -c ESTAB"));
				$this->runVar['connections']['alternate']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $this->runVar['connections']['port_a']));
			}
		}
		if ($this->runVar['connections']['primary']['active'] == 0 && $this->runVar['connections']['primary']['total'] == 0 && $this->runVar['connections']['alternate']['active'] == 0 && $this->runVar['connections']['alternate']['total'] == 0 && $this->runVar['connections']['port'] != $this->runVar['connections']['port_a']) {
			$this->runVar['connections']['primary']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $this->runVar['connections']['ip'] . " | grep -c ESTAB"));
			$this->runVar['connections']['primary']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $this->runVar['connections']['ip']));
			if ($this->runVar['constants']['alternate_nntp'] == 1) {
				$this->runVar['connections']['alternate']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $this->runVar['connections']['ip'] . " | grep -c ESTAB"));
				$this->runVar['connections']['alternate']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $this->runVar['connections']['ip']));
			}
		}
		return ($this->runVar['connections']);
	}
}