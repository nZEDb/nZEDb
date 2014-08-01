<?php

use nzedb\db\Settings;

/**
 * Tmux output functions for printing monitor data
 *
 * Class TmuxOutput
 */
class TmuxOutput extends Tmux
{

	/**
	 * @param $pdo Class instances
	 */
	public function __construct(Settings $pdo = null)
	{
		parent::__construct($pdo);
	}

	public function displayOutput($section, $runVar)
	{
		switch ((int) $section) {

			case 1:
				$this->_displayHeader($runVar);
				break;
			case 2:
				$this->_displayMonitor($runVar);
				break;
			case 3:
				$this->_displayQueryBlock($runVar);
				break;
		}
	}

	protected function _getColorsMasks($compressed)
	{
		$masks[1] = $this->pdo->log->headerOver("%-18s") . " " . $this->pdo->log->tmuxOrange("%-48.48s");
		$masks[2] = ($compressed == 1
					? $this->pdo->log->headerOver("%-20s") . " " . $this->pdo->log->tmuxOrange("%-33.33s")
					: $this->pdo->log->alternateOver("%-20s") . " " . $this->pdo->log->tmuxOrange("%-33.33s")
		);
		$masks[3] = $this->pdo->log->header("%-16.16s %25.25s %25.25s");
		$masks[4] = $this->pdo->log->primaryOver("%-16.16s") . " " . $this->pdo->log->tmuxOrange("%25.25s %25.25s");
		$masks[5] = $this->pdo->log->tmuxOrange("%-16.16s %25.25s %25.25s");

		return $masks;
	}

	protected function _displayHeader($runVar)
	{
		$versions = \nzedb\utility\Utility::getValidVersionsFile();
		$git = new \nzedb\utility\Git();
		$version = $versions->versions->git->tag . 'r' . $git->commits();

		$masks = $this->_getColorsMasks($runVar['settings']['compressed']);

		if ($runVar['settings']['is_running'] == 1) {
			printf($masks[2], "Monitor Running v$version [" . $runVar['constants']['sqlpatch'] . "]: ", $this->relativeTime($runVar['timers']['timer1']));
		} else {
			printf($masks[2], "Monitor Off v$version [" . $runVar['constants']['sqlpatch'] . "]: ", $this->relativeTime($runVar['timers']['timer1']));
		}
		printf($masks[1],
				"USP Connections:",
				sprintf(
					"%d active (%d total) - %s:%d",
					$runVar['conncounts']['primary']['active'],
					$runVar['conncounts']['primary']['total'],
					$runVar['connections']['host'],
					$runVar['connections']['port']
				)
		);
		if ($runVar['constants']['alternate_nntp']) {
			printf($masks[1],
					"USP Alternate:",
					sprintf(
						"%d active (%d total) - %s:%d)",
						$runVar['conncounts']['alternate']['active'],
						$runVar['conncounts']['alternate']['total'],
						$runVar['connections']['host_a'],
						$runVar['connections']['port_a']
					)
			);
		}

		printf($masks[1],
				"Newest Release:",
				$runVar['timers']['newOld']['newestrelname']
		);
		printf($masks[1],
				"Release Added:",
				sprintf(
					"%s ago",
					$this->relativeTime($runVar['timers']['newOld']['newestrelease'])
				)
		);
		printf($masks[1],
				"Predb Updated:",
				sprintf(
					"%s ago",
					$this->relativeTime($runVar['timers']['newOld']['newestpre'])
				)
		);
		printf($masks[1],
				sprintf(
					"Collection Age[%d]:",
					$runVar['constants']['delaytime']
				),
				sprintf(
					"%s ago",
					$this->relativeTime($runVar['timers']['newOld']['oldestcollection'])
				)
		);
		printf($masks[1],
				"Parts in Repair:",
				number_format($runVar['counts']['now']['partrepair_table'])
		);
		if (($runVar['settings']['post'] == "1" || $runVar['settings']['post'] == "3") && $runVar['constants']['sequential'] != 2) {
			printf($masks[1],
					"Postprocess:",
					"stale for " . $this->relativeTime($runVar['timers']['timer3'])
			);
		}
		echo PHP_EOL;
	}

	protected function _displayTableCounts($runVar)
	{
		$masks = $this->_getColorsMasks($runVar['settings']['compressed']);

		printf($masks[3], "Collections", "Binaries", "Parts");
		$this->_displaySeparator($runVar['settings']['compressed']);
		printf($masks[5],
				number_format($runVar['counts']['now']['collections_table']),
				number_format($runVar['counts']['now']['binaries_table']),
				number_format($runVar['counts']['now']['parts_table'])
		);
	}

	protected function _displayPaths($runVar)
	{
		$monitor_path = $monitor_path_a = $monitor_path_b = "";
		$masks = $this->_getColorsMasks($runVar['settings']['compressed']);

		// assign timers from tmux table
		$monitor_path = $runVar['settings']['monitor_path'];
		$monitor_path_a = $runVar['settings']['monitor_path_a'];
		$monitor_path_b = $runVar['settings']['monitor_path_b'];

		if (((isset($monitor_path)) && (file_exists($monitor_path)))
			|| ((isset($monitor_path_a)) && (file_exists($monitor_path_a)))
				|| ((isset($monitor_path_b)) && (file_exists($monitor_path_b)))) {
			echo "\n";
			printf($masks[3], "File System", "Used", "Free");
			$this->_displaySeparator($runVar['settings']['compressed']);
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
		$masks = $this->_getColorsMasks($runVar['settings']['compressed']);

		$this->_displayTableCounts($runVar);
		$this->_displayPaths($runVar);

		printf($masks[3], "Category", "In Process", "In Database");
		$this->_displaySeparator($runVar['settings']['compressed']);
		printf($masks[4], "predb",
			sprintf(
				"%s(%s)",
				number_format($runVar['counts']['now']['predb'] - $runVar['counts']['now']['distinct_predb_matched']),
				$runVar['counts']['diff']['distinct_predb_matched']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['predb_matched']),
				$runVar['counts']['percent']['predb_matched']
			)
		);
		printf($masks[4], "requestID",
			sprintf(
				"%s(%s)",
				number_format($runVar['counts']['now']['requestid_inprogress']),
				$runVar['counts']['diff']['requestid_inprogress']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['requestid_matched']),
				$runVar['counts']['percent']['requestid_matched']
			)
		);
		printf($masks[4], "NFO's",
			sprintf(
				"%s(%s)",
				number_format($runVar['counts']['now']['processnfo']),
				$runVar['counts']['diff']['processnfo']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['nfo']),
				$runVar['counts']['percent']['nfo']
			)
		);
		printf($masks[4], "Console",
			sprintf(
				"%s(%s)",
				number_format($runVar['counts']['now']['processconsole']),
				$runVar['counts']['diff']['processgames']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['console']),
				$runVar['counts']['percent']['console']
			)
		);
		printf($masks[4], "Movie",
			sprintf(
				"%s(%s)",
				number_format($runVar['counts']['now']['processmovies']),
				$runVar['counts']['diff']['processmovies']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['movies']),
				$runVar['counts']['percent']['movies']
			)
		);
		printf($masks[4], "Audio",
			sprintf(
				"%s(%s)",
				number_format($runVar['counts']['now']['processmusic']),
				$runVar['counts']['diff']['processmusic']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['audio']),
				$runVar['counts']['percent']['audio']
			)
		);
		printf($masks[4], "PC",
			sprintf(
				"%s(%s)",
				number_format($runVar['counts']['now']['processgames']),
				$runVar['counts']['diff']['processgames']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['pc']),
				$runVar['counts']['percent']['pc']
			)
		);
		printf($masks[4], "TV",
			sprintf(
				"%s(%s)",
				number_format($runVar['counts']['now']['processtvrage']),
				$runVar['counts']['diff']['processtvrage']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['tv']),
				$runVar['counts']['percent']['tv']
			)
		);
		printf($masks[4], "XXX",
			sprintf(
				"%s(%s)",
				number_format($runVar['counts']['now']['processxxx']),
				$runVar['counts']['diff']['processxxx']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['xxx']),
				$runVar['counts']['percent']['xxx']
			)
		);
		printf($masks[4], "Misc",
			sprintf(
				"%s(%s)",
				number_format($runVar['counts']['now']['work']),
				$runVar['counts']['diff']['work']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['misc']),
				$runVar['counts']['percent']['misc']
			)
		);
		printf($masks[4], "Books",
			sprintf(
				"%s(%s)",
				number_format($runVar['counts']['now']['processbooks']),
				$runVar['counts']['diff']['processbooks']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['books']),
				$runVar['counts']['percent']['books']
			)
		);
		printf($masks[4], "Total",
			sprintf(
				"%s(%s)",
				number_format($runVar['counts']['now']['total_work']),
				$runVar['counts']['diff']['total_work']
			),
			sprintf(
				"%s(%s)",
				number_format($runVar['counts']['now']['releases']),
				$runVar['counts']['diff']['releases']
			)
		);
		echo PHP_EOL;

		$this->_displayBackfill($runVar);
	}

	protected function _displayBackfill($runVar)
	{
		$masks = $this->_getColorsMasks($runVar['settings']['compressed']);

		printf($masks[3], "Groups", "Active", "Backfill");
		$this->_displaySeparator($runVar['settings']['compressed']);
		if ($runVar['settings']['backfilldays'] == "1") {
			printf($masks[4], "Activated",
				sprintf(
					"%d(%d)",
					$runVar['counts']['now']['active_groups'],
					$runVar['counts']['now']['all_groups']
				),
				sprintf(
					"%d(%d)",
					$runVar['counts']['now']['backfill_groups_days'],
					$runVar['counts']['now']['all_groups']
				)
			);
		} else {
			printf($masks[4], "Activated",
				sprintf(
					"%d(%d)",
					$runVar['counts']['now']['active_groups'],
					$runVar['counts']['now']['all_groups']
				),
				sprintf(
					"%d(%d)",
					$runVar['counts']['now']['backfill_groups_date'],
					$runVar['counts']['now']['all_groups']
				)
			);
		}
	}

	protected function _displayQueryBlock($runVar)
	{
		$masks = $this->_getColorsMasks($runVar['settings']['compressed']);

		echo PHP_EOL;
		printf($masks[3], "Query Block", "Time", "Cumulative");
		$this->_displaySeparator($runVar['settings']['compressed']);
		printf($masks[4], "Combined",
				sprintf(
					"%d %d %d %d %d %d %d",
					$runVar['timers']['query']['tmux_time'],
					$runVar['timers']['query']['split_time'],
					$runVar['timers']['query']['init_time'],
					$runVar['timers']['query']['proc1_time'],
					$runVar['timers']['query']['proc2_time'],
					$runVar['timers']['query']['proc3_time'],
					$runVar['timers']['query']['tpg_time']
				),
				sprintf(
					"%d %d %d %d %d %d %d",
					$runVar['timers']['query']['tmux_time'],
					$runVar['timers']['query']['split1_time'],
					$runVar['timers']['query']['init1_time'],
					$runVar['timers']['query']['proc11_time'],
					$runVar['timers']['query']['proc21_time'],
					$runVar['timers']['query']['proc31_time'],
					$runVar['timers']['query']['tpg1_time']
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
}