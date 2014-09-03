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
	 * @var Git
	 */
	protected $_git;

	/**
	 * @var Versions
	 */
	protected $_vers;

	private $runVar;
	/**
	 * @var array of current format masks to use.
	 */
	private $tmpMasks;


	/**
	 * @param Settings $pdo
	 */
	public function __construct(Settings $pdo = null)
	{
		parent::__construct($pdo);
		$this->_git = new \nzedb\utility\Git();
		$this->_vers = \nzedb\utility\Utility::getValidVersionsFile();

		$this->_setColourMasks();
	}

	public function updateMonitorPane(&$runVar)
	{
		$this->runVar = $runVar;
		$this->tmpMasks = $this->_getFormatMasks($runVar['settings']['compressed']);

		$buffer = $this->_getHeader();

		if ($runVar['settings']['monitor'] > 0) {
			$buffer .= $this->_getMonitor();
		}

		if ($runVar['settings']['show_query'] == 1) {
			$buffer .= $this->_getQueries();
		}

		//begin update display with screen clear
		passthru('clear');
		echo $buffer;
	}

	protected function _getBackfill()
	{
		$buffer = sprintf($this->tmpMasks[3], "Groups", "Active", "Backfill");
		$buffer .= $this->_getSeparator();

		if ($this->runVar['settings']['backfilldays'] == "1") {
			$buffer .= sprintf($this->tmpMasks[4],
				   "Activated",
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
			$buffer .= sprintf($this->tmpMasks[4],
				   "Activated",
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

		return $buffer;
	}

	protected function _getFormatMasks($compressed)
	{
		$index = $compressed == 1 ? 2.1 : 2.0;
		return array(
			1 => &$this->_colourMasks[1],
			2 => &$this->_colourMasks[$index],
			3 => &$this->_colourMasks[3],
			4 => &$this->_colourMasks[4],
			5 => &$this->_colourMasks[5],
		);
	}

	protected function _getHeader()
	{
		$buffer = '';
		$state = ($this->runVar['settings']['is_running'] == 1) ? 'Running' : 'Disabled';
		$version = $this->_vers->versions->git->tag . 'r' . $this->_git->commits();

		$buffer .= sprintf($this->tmpMasks[2],
					"Monitor $state v$version [" . $this->runVar['constants']['sqlpatch'] . "]: ",
					$this->relativeTime($this->runVar['timers']['timer1'])
		);

		$buffer .= sprintf($this->tmpMasks[1],
					"USP Connections:",
					sprintf(
						"%d active (%d total) - %s:%d",
						$this->runVar['conncounts']['primary']['active'],
						$this->runVar['conncounts']['primary']['total'],
						$this->runVar['connections']['host'],
						$this->runVar['connections']['port']
					)
		);

		if ($this->runVar['constants']['alternate_nntp']) {
			$buffer .= sprintf($this->tmpMasks[1],
					"USP Alternate:",
					sprintf(
						"%d active (%d total) - %s:%d)",
						$this->runVar['conncounts']['alternate']['active'],
						$this->runVar['conncounts']['alternate']['total'],
						$this->runVar['connections']['host_a'],
						$this->runVar['connections']['port_a']
					)
			);
		}

		$buffer .= sprintf($this->tmpMasks[1],
				"Newest Release:",
				$this->runVar['timers']['newOld']['newestrelname']
		);
		$buffer .= sprintf($this->tmpMasks[1],
				"Release Added:",
				sprintf(
					"%s ago",
					(isset($this->runVar['timers']['newOld']['newestrelease'])
						? $this->relativeTime($this->runVar['timers']['newOld']['newestrelease'])
						: 0)
				)
		);
		$buffer .= sprintf($this->tmpMasks[1],
				"Predb Updated:",
				sprintf(
					"%s ago",
					(isset($this->runVar['timers']['newOld']['newestpre'])
						? $this->relativeTime($this->runVar['timers']['newOld']['newestpre'])
						: 0)
				)
		);
		$buffer .= sprintf($this->tmpMasks[1],
				sprintf(
					"Collection Age[%d]:",
					$this->runVar['constants']['delaytime']
				),
				sprintf(
					"%s ago",
					(isset($this->runVar['timers']['newOld']['oldestcollection'])
						? $this->relativeTime($this->runVar['timers']['newOld']['oldestcollection'])
						: 0)
				)
		);
		$buffer .= sprintf($this->tmpMasks[1],
				"Parts in Repair:",
				number_format($this->runVar['counts']['now']['partrepair_table'])
		);

		if (($this->runVar['settings']['post'] == "1" || $this->runVar['settings']['post'] == "3") && $this->runVar['constants']['sequential'] != 2) {
			$buffer .= sprintf($this->tmpMasks[1],
					"Postprocess:",
					"stale for " . $this->relativeTime($this->runVar['timers']['timer3'])
			);
		}

		return $buffer . PHP_EOL;
	}

	protected function _getMonitor()
	{
		$buffer = $this->_getTableCounts();
		$buffer .= $this->_getPaths();

		$buffer .= sprintf($this->tmpMasks[3], "PPA Lists", "Unmatched", "Matched");
		$buffer .= $this->_getSeparator();

		$buffer .= sprintf($this->tmpMasks[4],
						   "NFO's",
						   sprintf(
							   "%s(%s)",
							   number_format($this->runVar['counts']['now']['processnfo']),
							   $this->runVar['counts']['diff']['processnfo']
						   ),
						   sprintf(
							   "%s(%d%%)",
							   number_format($this->runVar['counts']['now']['nfo']),
							   $this->runVar['counts']['percent']['nfo']
						   )
		);
		$buffer .= sprintf($this->tmpMasks[4],
						   "predb",
						   sprintf(
							   "%s(%s)",
							   number_format($this->runVar['counts']['now']['predb'] -
											 $this->runVar['counts']['now']['distinct_predb_matched']),
							   $this->runVar['counts']['diff']['distinct_predb_matched']
						   ),
						   sprintf(
							   "%s(%d%%)",
							   number_format($this->runVar['counts']['now']['predb_matched']),
							   $this->runVar['counts']['percent']['predb_matched']
						   )
		);
		$buffer .= sprintf($this->tmpMasks[4],
						   "requestID",
						   sprintf(
							   "%s(%s)",
							   number_format($this->runVar['counts']['now']['requestid_inprogress']),
							   $this->runVar['counts']['diff']['requestid_inprogress']
						   ),
						   sprintf(
							   "%s(%d%%)",
							   number_format($this->runVar['counts']['now']['requestid_matched']),
							   $this->runVar['counts']['percent']['requestid_matched']
						   )
		);

		$buffer .= PHP_EOL;
		$buffer .= sprintf($this->tmpMasks[3], "Category", "In Process", "In Database");
		$buffer .= $this->_getSeparator();

		$buffer .= sprintf($this->tmpMasks[4],
						   "Audio",
						   sprintf(
							   "%s(%s)",
							   number_format($this->runVar['counts']['now']['processmusic']),
							   $this->runVar['counts']['diff']['processmusic']
						   ),
						   sprintf(
							   "%s(%d%%)",
							   number_format($this->runVar['counts']['now']['audio']),
							   $this->runVar['counts']['percent']['audio']
						   )
		);
		$buffer .= sprintf($this->tmpMasks[4],
						   "Books",
						   sprintf(
							   "%s(%s)",
							   number_format($this->runVar['counts']['now']['processbooks']),
							   $this->runVar['counts']['diff']['processbooks']
						   ),
						   sprintf(
							   "%s(%d%%)",
							   number_format($this->runVar['counts']['now']['books']),
							   $this->runVar['counts']['percent']['books']
						   )
		);
		$buffer .= sprintf($this->tmpMasks[4],
						   "Console",
						   sprintf(
							   "%s(%s)",
							   number_format($this->runVar['counts']['now']['processconsole']),
							   $this->runVar['counts']['diff']['processconsole']
						   ),
						   sprintf(
							   "%s(%d%%)",
							   number_format($this->runVar['counts']['now']['console']),
							   $this->runVar['counts']['percent']['console']
						   )
		);
		$buffer .= sprintf($this->tmpMasks[4],
						   "Misc",
						   sprintf(
							   "%s(%s)",
							   number_format($this->runVar['counts']['now']['work']),
							   $this->runVar['counts']['diff']['work']
						   ),
						   sprintf(
							   "%s(%d%%)",
							   number_format($this->runVar['counts']['now']['misc']),
							   $this->runVar['counts']['percent']['misc']
						   )
		);
		$buffer .= sprintf($this->tmpMasks[4],
						   "Movie",
						   sprintf(
							   "%s(%s)",
							   number_format($this->runVar['counts']['now']['processmovies']),
							   $this->runVar['counts']['diff']['processmovies']
						   ),
						   sprintf(
							   "%s(%d%%)",
							   number_format($this->runVar['counts']['now']['movies']),
							   $this->runVar['counts']['percent']['movies']
						   )
		);
		$buffer .= sprintf($this->tmpMasks[4],
						   "PC",
						   sprintf(
							   "%s(%s)",
							   number_format($this->runVar['counts']['now']['processgames']),
							   $this->runVar['counts']['diff']['processgames']
						   ),
						   sprintf(
							   "%s(%d%%)",
							   number_format($this->runVar['counts']['now']['pc']),
							   $this->runVar['counts']['percent']['pc']
						   )
		);
		$buffer .= sprintf($this->tmpMasks[4],
						   "TV",
						   sprintf(
							   "%s(%s)",
							   number_format($this->runVar['counts']['now']['processtvrage']),
							   $this->runVar['counts']['diff']['processtvrage']
						   ),
						   sprintf(
							   "%s(%d%%)",
							   number_format($this->runVar['counts']['now']['tv']),
							   $this->runVar['counts']['percent']['tv']
						   )
		);
		$buffer .= sprintf($this->tmpMasks[4],
						   "XXX",
						   sprintf(
							   "%s(%s)",
							   number_format($this->runVar['counts']['now']['processxxx']),
							   $this->runVar['counts']['diff']['processxxx']
						   ),
						   sprintf(
							   "%s(%d%%)",
							   number_format($this->runVar['counts']['now']['xxx']),
							   $this->runVar['counts']['percent']['xxx']
						   )
		);

		$buffer .= $this->_getSeparator();

		$buffer .= sprintf($this->tmpMasks[4],
						   "Total",
						   sprintf(
							   "%s(%s)",
							   number_format($this->runVar['counts']['now']['total_work']),
							   $this->runVar['counts']['diff']['total_work']
						   ),
						   sprintf(
							   "%s(%s)",
							   number_format($this->runVar['counts']['now']['releases']),
							   $this->runVar['counts']['diff']['releases']
						   )
		);
		$buffer .= PHP_EOL;

		$buffer .= $this->_getBackfill();

		return $buffer;
	}

	protected function _getPaths()
	{
		$buffer = '';

		// assign timers from tmux table
		$monitor_path = $this->runVar['settings']['monitor_path'];
		$monitor_path_a = $this->runVar['settings']['monitor_path_a'];
		$monitor_path_b = $this->runVar['settings']['monitor_path_b'];

		if (((isset($monitor_path)) && (file_exists($monitor_path)))
			|| ((isset($monitor_path_a)) && (file_exists($monitor_path_a)))
				|| ((isset($monitor_path_b)) && (file_exists($monitor_path_b)))) {

			$buffer .= "\n";
			$buffer .= sprintf($this->tmpMasks[3], "File System", "Used", "Free");
			$buffer .= $this->_getSeparator();

			if (isset($monitor_path) && $monitor_path != "" && file_exists($monitor_path)) {
				$disk_use = $this->decodeSize(disk_total_space($monitor_path) - disk_free_space($monitor_path));
				$disk_free = $this->decodeSize(disk_free_space($monitor_path));
				if (basename($monitor_path) == "") {
					$show = "/";
				} else {
					$show = basename($monitor_path);
				}
				$buffer .= sprintf($this->tmpMasks[4], $show, $disk_use, $disk_free);
			}

			if (isset($monitor_path_a) && $monitor_path_a != "" && file_exists($monitor_path_a)) {
				$disk_use = $this->decodeSize(disk_total_space($monitor_path_a) - disk_free_space($monitor_path_a));
				$disk_free = $this->decodeSize(disk_free_space($monitor_path_a));
				if (basename($monitor_path_a) == "") {
					$show = "/";
				} else {
					$show = basename($monitor_path_a);
				}
				$buffer .= sprintf($this->tmpMasks[4], $show, $disk_use, $disk_free);
			}

			if (isset($monitor_path_b) && $monitor_path_b != "" && file_exists($monitor_path_b)) {
				$disk_use = $this->decodeSize(disk_total_space($monitor_path_b) - disk_free_space($monitor_path_b));
				$disk_free = $this->decodeSize(disk_free_space($monitor_path_b));
				if (basename($monitor_path_b) == "") {
					$show = "/";
				} else {
					$show = basename($monitor_path_b);
				}
				$buffer .= sprintf($this->tmpMasks[4], $show, $disk_use, $disk_free);
			}
		}
		return $buffer . PHP_EOL;
	}

	protected function _getQueries()
	{
		$buffer = PHP_EOL;
		$buffer .= sprintf($this->tmpMasks[3], "Query Block", "Time", "Cumulative");
		$buffer .= $this->_getSeparator();
		$buffer .= sprintf($this->tmpMasks[4],
						   "Combined",
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

		$pieces = explode(" ", $this->pdo->getAttribute(\PDO::ATTR_SERVER_INFO));
		$buffer .= $this->pdo->log->primaryOver("\nThreads = ") .
				   $this->pdo->log->headerOver($pieces[4]) .
				   $this->pdo->log->primaryOver(', Opens = ') .
				   $this->pdo->log->headerOver($pieces[14]) .
				   $this->pdo->log->primaryOver(', Tables = ') .
				   $this->pdo->log->headerOver($pieces[22]) .
				   $this->pdo->log->primaryOver(', Slow = ') .
				   $this->pdo->log->headerOver($pieces[11]) .
				   $this->pdo->log->primaryOver(', QPS = ') .
				   $this->pdo->log->header($pieces[28]);

		return $buffer;
	}

	protected function _getSeparator()
	{
		return sprintf($this->tmpMasks[3],
					   "======================================",
					   "=========================",
					   "======================================");
	}

	protected function _getTableCounts()
	{
		$buffer = sprintf($this->tmpMasks[3], "Collections", "Binaries", "Parts");
		$buffer .= $this->_getSeparator();
		$buffer .= sprintf($this->tmpMasks[5],
			   number_format($this->runVar['counts']['now']['collections_table']),
			   number_format($this->runVar['counts']['now']['binaries_table']),
			   number_format($this->runVar['counts']['now']['parts_table'])
		);

		return $buffer;
	}

	protected function _SetColourMasks()
	{
		$this->_colourMasks[1]   =
			$this->pdo->log->headerOver("%-18s") . " " . $this->pdo->log->tmuxOrange("%-48.48s");
		$this->_colourMasks[2.0] =
			$this->pdo->log->alternateOver("%-20s") . " " . $this->pdo->log->tmuxOrange("%-33.33s");
		$this->_colourMasks[2.1] =
			$this->pdo->log->headerOver("%-20s") . " " . $this->pdo->log->tmuxOrange("%-33.33s");
		$this->_colourMasks[3]   = $this->pdo->log->header("%-16.16s %25.25s %25.25s");;
		$this->_colourMasks[4] = $this->pdo->log->primaryOver("%-16.16s") .
								 " " . $this->pdo->log->tmuxOrange("%25.25s %25.25s");;
		$this->_colourMasks[5] = $this->pdo->log->tmuxOrange("%-16.16s %25.25s %25.25s");
	}
}
