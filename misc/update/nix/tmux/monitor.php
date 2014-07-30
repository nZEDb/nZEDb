<?php
require_once dirname(__FILE__) . '/../../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();
$t = new Tmux();

$versions = \nzedb\utility\Utility::getValidVersionsFile();
$git = new \nzedb\utility\Git();
$version = $versions->versions->git->tag . 'r' . $git->commits();

$DIR = nZEDb_MISC;
$db_name = DB_NAME;
$dbtype = DB_SYSTEM;

$runVar['constants'] = $pdo->queryOneRow($t->getConstantSettings());
$runVar['constants']['pre_lim'] = '';

$PYTHON = ($t->command_exist("python3") ? 'python3 -OOu' : 'python -OOu');
$PHP = ($t->command_exist("php5") ? 'php5' : 'php');

if ($runVar['constants']['nntpproxy'] == 0) {
	$port = NNTP_PORT;
	$host = NNTP_SERVER;
	$ip = gethostbyname($host);
	if ($runVar['constants']['alternate_nntp']) {
		$port_a = NNTP_PORT_A;
		$host_a = NNTP_SERVER_A;
		$ip_a = gethostbyname($host_a);
	}
} else {
	$filename = $DIR . "update/python/lib/nntpproxy.conf";
	$fp = fopen($filename, "r") or die("Couldn't open $filename");
	while (!feof($fp)) {
		$line = fgets($fp);
		if (preg_match('/"host": "(.+)",$/', $line, $match)) {
			$host = $match[1];
		}
		if (preg_match('/"port": (.+),$/', $line, $match)) {
			$port = $match[1];
			break;
		}
	}

	if ($runVar['constants']['alternate_nntp']) {
		$filename = $DIR . "update/python/lib/nntpproxy_a.conf";
		$fp = fopen($filename, "r") or die("Couldn't open $filename");
		while (!feof($fp)) {
			$line = fgets($fp);
			if (preg_match('/"host": "(.+)",$/', $line, $match)) {
				$host_a = $match[1];
			}
			if (preg_match('/"port": (.+),$/', $line, $match)) {
				$port_a = $match[1];
				break;
			}
		}
	}
	$ip = gethostbyname($host);
	if ($runVar['constants']['alternate_nntp']) {
		$ip_a = gethostbyname($host_a);
	}
}

//totals per category in db, results by parentID
$catcntqry = "SELECT c.parentid AS parentid, COUNT(r.id) AS count FROM category c, releases r WHERE r.categoryid = c.id GROUP BY c.parentid";

//create timers and set to now
$timer1 = $timer2 = $timer3 = $timer4 = $timer5 = time();

$tmux_time = $split_time = $init_time = $proc1_time = $proc2_time = $proc3_time = $split1_time = 0;
$init1_time = $proc11_time = $proc21_time = $proc31_time = $tpg_count_time = $tpg_count_1_time = 0;

$last_history = "";

// Analyze tables
printf($pdo->log->info("\nAnalyzing your tables to refresh your indexes."));
$pdo->optimise(true, 'analyze');
passthru('clear');

$mask1 = $pdo->log->headerOver("%-18s") . " " . $pdo->log->tmuxOrange("%-48.48s");
$mask2 = $pdo->log->headerOver("%-20s") . " " . $pdo->log->tmuxOrange("%-33.33s");
$mask3 = $pdo->log->header("%-16.16s %25.25s %25.25s");
$mask4 = $pdo->log->primaryOver("%-16.16s") . " " . $pdo->log->tmuxOrange("%25.25s %25.25s");
$mask5 = $pdo->log->tmuxOrange("%-16.16s %25.25s %25.25s");

$runVar['settings']['monitor'] = 0;
$i = 1;
$fcfirstrun = true;
$fcnum = 0;

while ($i > 0) {

	//check the db connection
	if ($pdo->ping(true) == false) {
		unset($pdo);
		$pdo = new Settings();
	}

	$timer01 = time();
	// These queries are very fast, run every loop -- tmux and site settings
	$runVar['settings'] = $pdo->queryOneRow($t->getMonitorSettings(), false);
	$tmux_time = (time() - $timer01);

	//run queries only after time exceeded, these queries can take awhile
	if ($i == 1 || (time() - $timer2 >= $runVar['settings']['monitor'] && $runVar['settings']['is_running'] == 1)) {

		$runVar['counts']['proc1'] = $runVar['counts']['proc2'] = $runVar['counts']['proc3'] = $splitqry = $newOldqry = false;
		$runVar['counts']['now']['total_work'] = 0;
		$runVar['modsettings']['fix_crap'] = explode(', ', ($runVar['settings']['fix_crap']));

		echo $pdo->log->info("\nThe numbers(queries) above are currently being refreshed. \nNo pane(script) can be (re)started until these have completed.\n");
		$timer02 = time();

		if ($dbtype == 'mysql') {
			$splitqry = $t->proc_query(4, $runVar['constants']['book_reqids'], $runVar['constants']['request_hours'], $db_name);
			$newOldqry = $t->proc_query(6, $runVar['constants']['book_reqids'], $runVar['constants']['request_hours'], $db_name);
		} else {
			$splitqry = $t->proc_query(5, $runVar['constants']['book_reqids'], $runVar['constants']['request_hours'], $db_name);
			$newOldqry = $t->proc_query(7, $runVar['constants']['book_reqids'], $runVar['constants']['request_hours'], $db_name);
		}

		$splitres = $pdo->queryOneRow($splitqry, false);
		$runVar['timers']['newOld'] = $pdo->queryOneRow($newOldqry, false);

		//assign split query results to main var
		foreach ($splitres AS $splitkey => $split) {
			$runVar['counts']['now'][$splitkey] = $split;
		}

		$split_time = (time() - $timer02);
		$split1_time = (time() - $timer01);

		$timer03 = time();
		if ($pdo->dbSystem() === 'mysql') {
			//This is subpartition compatible -- loops through all partitions and adds their total row counts instead of doing a slow query count
			$partitions = $pdo->queryDirect(
							sprintf("
								SELECT SUM(TABLE_ROWS) AS count, PARTITION_NAME AS category
								FROM INFORMATION_SCHEMA.PARTITIONS
								WHERE TABLE_NAME = 'releases'
								AND TABLE_SCHEMA = %s
								GROUP BY PARTITION_NAME",
								$pdo->escapeString($db_name)
							)
			);
			foreach ($partitions as $partition) {
				$runVar['counts']['now'][$partition['category']] = $partition['count'];
			}
			unset($partitions);
		} else {
			$initquery = $pdo->queryDirect($catcntqry, false);
			$catParentPart = array(
						Category::CAT_PARENT_GAME  => 'console',
						Category::CAT_PARENT_MOVIE => 'movies',
						Category::CAT_PARENT_MUSIC => 'audio',
						Category::CAT_PARENT_PC    => 'pc',
						Category::CAT_PARENT_TV    => 'tv',
						Category::CAT_PARENT_XXX   => 'xxx',
						Category::CAT_PARENT_MISC  => 'misc',
						Category::CAT_PARENT_BOOKS => 'books'
			);
			foreach ($initquery as $parent => $count) {
				$runVar['counts']['now'][$catParentPart[$parent]] = $count;
			}
			unset($initquery);
		}
		$init_time = (time() - $timer03);
		$init1_time = (time() - $timer01);

		$timer04 = time();
		$proc1res = $pdo->queryOneRow($t->proc_query(1, $runVar['constants']['book_reqids'], $runVar['constants']['request_hours'], $db_name), $t->rand_bool($i));
		$proc1_time = (time() - $timer04);
		$proc11_time = (time() - $timer01);

		$timer05 = time();
		$proc2res = $pdo->queryOneRow($t->proc_query(2, $runVar['constants']['book_reqids'], $runVar['constants']['request_hours'], $db_name), $t->rand_bool($i));
		$proc2_time = (time() - $timer05);
		$proc21_time = (time() - $timer01);

		$timer06 = time();
		$proc3res = $pdo->queryOneRow($t->proc_query(3, $runVar['constants']['book_reqids'], $runVar['constants']['request_hours'], $db_name), $t->rand_bool($i));
		$proc3_time = (time() - $timer06);
		$proc31_time = (time() - $timer01);

		$timer07 = time();
		if ($runVar['constants']['tablepergroup'] == 1) {
			if ($pdo->dbSystem() === 'mysql') {
				$sql = 'SHOW TABLE STATUS';
			} else {
				$sql = "SELECT relname FROM pg_class WHERE relname !~ '^(pg_|sql_)' AND relkind = 'r'";
			}
			$tables = $pdo->queryDirect($sql);
			$age = time();

			$runVar['counts']['now']['collections_table'] = $runVar['counts']['now']['binaries_table'] = 0;
			$runVar['counts']['now']['parts_table'] = $runVar['counts']['now']['parterpair_table'] = 0;

			if (count($tables) > 0) {
				foreach ($tables as $row) {
					$cntsql = '';
					if ($pdo->dbSystem() === 'mysql') {
						$tbl = $row['name'];
						$stamp = 'UNIX_TIMESTAMP(MIN(dateadded))';
						$orderlim = '';
						$cntsql = sprintf('
								SELECT TABLE_ROWS AS count
								FROM INFORMATION_SCHEMA.TABLES
								WHERE TABLE_NAME = %s
								AND TABLE_SCHEMA = %s',
								$pdo->escapeString($tbl),
								$pdo->escapeString($db_name)
						);
					} else {
						$tbl = $row['relname'];
						$stamp = 'extract(epoch FROM dateadded)';
						$orderlim = 'ORDER BY dateadded ASC LIMIT 1';
						$cntsql = 'SELECT COUNT(*) AS count FROM ' . $tbl;
					}

					if (strpos($tbl, 'collections_') !== false) {
						$run = $pdo->queryOneRow($cntsql, $t->rand_bool($i));
						$runVar['counts']['now']['collections_table'] += $run['count'];
						$run1 =
							$pdo->queryOneRow(
									sprintf('
										SELECT %s AS dateadded
										FROM %s %s',
										$stamp,
										$tbl,
										$orderlim
									),
							$t->rand_bool($i)
						);
						if (isset($run1['dateadded']) && is_numeric($run1['dateadded']) && $run1['dateadded'] < $age) {
							$age = $run1['dateadded'];
						}
					} else if (strpos($tbl, 'binaries_') !== false) {
						$run = $pdo->queryOneRow($cntsql, $t->rand_bool($i));
						if (isset($run['count']) && is_numeric($run['count'])) {
							$runVar['counts']['now']['binaries_table'] += $run['count'];
						}
					} else if (strpos($tbl, 'parts_') !== false) {
						$run = $pdo->queryOneRow($cntsql, $t->rand_bool($i));
						if (isset($run['count']) && is_numeric($run['count'])) {
							$runVar['counts']['now']['parts_table'] += $run['count'];
						}
					} else if (strpos($tbl, 'partrepair_') !== false) {
						$run = $pdo->queryOneRow($cntsql, $t->rand_bool($i));
						if (isset($run['count']) && is_numeric($run['count'])) {
							$runVar['counts']['now']['parterpair_table'] += $run['count'];
						}
					}
				}
				$runVar['timers']['newOld']['oldestcollection'] = $age;

				//free up memory used by now stale data
				unset($age, $run, $run1, $tables);

				$tpg_count_time = (time() - $timer07);
				$tpg_count_1_time = (time() - $timer01);
			}
		}
		$timer2 = time();

		if (($proc1res == false) || ($proc2res == false) || ($proc3res == false) || ($splitres == false) || ($runVar['timers']['newOld'] == false)) {
			echo $pdo->log->error(PHP_EOL . "Monitor encountered severe errors retrieving process data from MySQL.  Please diagnose and try running again." . PHP_EOL);
			exit;
		}

		//assign postprocess values from $proc
		foreach ($proc1res AS $proc1key => $proc1) {
			$runVar['counts']['now'][$proc1key] = $proc1;
		}
		foreach ($proc2res AS $proc2key => $proc2) {
			$runVar['counts']['now'][$proc2key] = $proc2;
		}
		foreach ($proc3res AS $proc3key => $proc3) {
			$runVar['counts']['now'][$proc3key] = $proc3;
		}

		// now that we have merged our query data we can unset these to free up memory
		unset($proc1res, $proc2res, $proc3res, $splitres);

		// Zero out any post proc counts when that type of pp has been turned off
		foreach ($runVar['settings'] as $settingkey => $setting) {
			if (strpos($settingkey, 'process') === 0 && $setting === 0) {
				$runVar['counts']['now'][$settingkey] = $runVar['counts']['start'][$settingkey] = 0;
			}
		}

		//set initial start postproc values from work queries -- this is used to determine diff variables
		if ($i == 1) {
			$runVar['counts']['start'] = $runVar['counts']['now'];
		}

		foreach ($runVar['counts']['now'] as $key => $proc) {

			//if key is a process type, add it to total_work
			if (strpos($key, 'process') === 0) {
				$runVar['counts']['now']['total_work'] += $proc;
			}

			//calculate diffs
			$runVar['counts']['diff'][$key] = number_format($proc - $runVar['counts']['start'][$key]);

			//calculate percentages -- if user has no releases, set 0 for each key or this will fail on divide by zero
			if ($runVar['counts']['now']['releases'] != 0) {
				$runVar['counts']['percent'][$key] = sprintf("%02s", floor(($proc / $runVar['counts']['now']['releases']) * 100));
			} else {
				$runVar['counts']['percent'][$key] = 0;
			}

		}

		$runVar['counts']['now']['total_work'] += $runVar['counts']['now']['work'];

		// Set initial total work count for diff
		if ($i == 1) {
			$runVar['counts']['start']['total_work'] = $runVar['counts']['now']['total_work'];
		}

		// Set diff total work count
		$runVar['counts']['diff']['total_work'] = $runVar['counts']['now']['total_work'] - $runVar['counts']['start']['total_work'];
	}

	//reset monitor paths before assigning query values
	$monitor_path = $monitor_path_a = $monitor_path_b = "";

	// assign timers from tmux table
	$monitor_path = $runVar['settings']['monitor_path'];
	$monitor_path_a = $runVar['settings']['monitor_path_a'];
	$monitor_path_b = $runVar['settings']['monitor_path_b'];

	$runVar['connections']['primary']['active'] = $runVar['connections']['primary']['total'] =
	$runVar['connections']['alternate']['active'] = $runVar['connections']['alternate']['total'] = 0;

	//get usenet connections
	$runVar['connections']['primary']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $ip . ":" . $port . " | grep -c ESTAB"));
	$runVar['connections']['primary']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $ip . ":" . $port));
	if ($runVar['constants']['alternate_nntp']) {
		$runVar['connections']['alternate']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $ip_a . ":" . $port_a . " | grep -c ESTAB"));
		$runVar['connections']['alternate']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $ip_a . ":" . $port_a));
	}
	if ($runVar['connections']['primary']['active'] == 0 && $runVar['connections']['primary']['total'] == 0 && $runVar['connections']['alternate']['active'] == 0 && $runVar['connections']['alternate']['total'] == 0 && $port != $port_a) {
		$runVar['connections']['primary']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $ip . ":https | grep -c ESTAB"));
		$runVar['connections']['primary']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $ip . ":https"));
		if ($runVar['constants']['alternate_nntp']) {
			$runVar['connections']['alternate']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $ip_a . ":https | grep -c ESTAB"));
			$runVar['connections']['alternate']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $ip_a . ":https"));
		}
	}
	if ($runVar['connections']['primary']['active'] == 0 && $runVar['connections']['primary']['total'] == 0 && $runVar['connections']['alternate']['active'] == 0 && $runVar['connections']['alternate']['total'] == 0 && $port != $port_a) {
		$runVar['connections']['primary']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $port . " | grep -c ESTAB"));
		$runVar['connections']['primary']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $port));
		if ($runVar['constants']['alternate_nntp']) {
			$runVar['connections']['alternate']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $port_a . " | grep -c ESTAB"));
			$runVar['connections']['alternate']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $port_a));
		}
	}
	if ($runVar['connections']['primary']['active'] == 0 && $runVar['connections']['primary']['total'] == 0 && $runVar['connections']['alternate']['active'] == 0 && $runVar['connections']['alternate']['total'] == 0 && $port != $port_a) {
		$runVar['connections']['primary']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $ip . " | grep -c ESTAB"));
		$runVar['connections']['primary']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $ip));
		if ($runVar['constants']['alternate_nntp']) {
			$runVar['connections']['alternate']['active'] = str_replace("\n", '', shell_exec("ss -n | grep " . $ip . " | grep -c ESTAB"));
			$runVar['connections']['alternate']['total'] = str_replace("\n", '', shell_exec("ss -n | grep -c " . $ip));
		}
	}

	if ($runVar['settings']['compressed'] === '1') {
		$mask2 = $pdo->log->headerOver("%-20s") . " " . $pdo->log->tmuxOrange("%-33.33s");
	} else {
		$mask2 = $pdo->log->alternateOver("%-20s") . " " . $pdo->log->tmuxOrange("%-33.33s");
	}

	//update display
	passthru('clear');
	//printf("\033[1;31m First insert:\033[0m ".$t->relativeTime("$firstdate")."\n");
	if ($runVar['settings']['is_running'] == 1) {
		printf($mask2, "Monitor Running v$version [" . $runVar['constants']['sqlpatch'] . "]: ", $t->relativeTime("$timer1"));
	} else {
		printf($mask2, "Monitor Off v$version [" . $runVar['constants']['sqlpatch'] . "]: ", $t->relativeTime("$timer1"));
	}
	printf($mask1, "USP Connections:", $runVar['connections']['primary']['active'] . " active (" . $runVar['connections']['primary']['total'] . " total) - " . $host . ":" . $port);
	if ($runVar['constants']['alternate_nntp']) {
		printf($mask1, "USP Alternate:", $runVar['connections']['alternate']['active'] . " active (" . $runVar['connections']['alternate']['total'] . " total) - " . (($runVar['constants']['alternate_nntp']) ? $host_a . ":" . $port_a : "n/a"));
	}

	printf($mask1, "Newest Release:", $runVar['timers']['newOld']['newestrelname']);
	printf($mask1, "Release Added:", $t->relativeTime($runVar['timers']['newOld']['newestrelease']) . "ago");
	printf($mask1, "Predb Updated:", $t->relativeTime($runVar['timers']['newOld']['newestpre']) . "ago");
	printf($mask1, "Collection Age[{$runVar['constants']['delaytime']}]:", $t->relativeTime($runVar['timers']['newOld']['oldestcollection']) . "ago");
	printf($mask1, "Parts in Repair:", number_format($runVar['counts']['now']['partrepair_table']));
	if (($runVar['settings']['post'] == "1" || $runVar['settings']['post'] == "3") && $runVar['constants']['sequential'] != 2) {
		printf($mask1, "Postprocess:", "stale for " . $t->relativeTime($timer3));
	}
	echo "\n";

	if ($runVar['settings']['monitor'] > 0) {

		printf($mask3, "Collections", "Binaries", "Parts");
		printf($mask3, "======================================", "=========================", "======================================");
		printf($mask5,
				number_format($runVar['counts']['now']['collections_table']),
				number_format($runVar['counts']['now']['binaries_table']),
				number_format($runVar['counts']['now']['parts_table'])
		);

		if (((isset($monitor_path)) && (file_exists($monitor_path))) || ((isset($monitor_path_a)) && (file_exists($monitor_path_a))) || ((isset($monitor_path_b)) && (file_exists($monitor_path_b)))) {
			echo "\n";
			printf($mask3, "File System", "Used", "Free");
			printf($mask3, "======================================", "=========================", "======================================");
			if (isset($monitor_path) && $monitor_path != "" && file_exists($monitor_path)) {
				$disk_use = $t->decodeSize(disk_total_space($monitor_path) - disk_free_space($monitor_path));
				$disk_free = $t->decodeSize(disk_free_space($monitor_path));
				if (basename($monitor_path) == "") {
					$show = "/";
				} else {
					$show = basename($monitor_path);
				}
				printf($mask4, $show, $disk_use, $disk_free);
			}
			if (isset($monitor_path_a) && $monitor_path_a != "" && file_exists($monitor_path_a)) {
				$disk_use = $t->decodeSize(disk_total_space($monitor_path_a) - disk_free_space($monitor_path_a));
				$disk_free = $t->decodeSize(disk_free_space($monitor_path_a));
				if (basename($monitor_path_a) == "") {
					$show = "/";
				} else {
					$show = basename($monitor_path_a);
				}
				printf($mask4, $show, $disk_use, $disk_free);
			}
			if (isset($monitor_path_b) && $monitor_path_b != "" && file_exists($monitor_path_b)) {
				$disk_use = $t->decodeSize(disk_total_space($monitor_path_b) - disk_free_space($monitor_path_b));
				$disk_free = $t->decodeSize(disk_free_space($monitor_path_b));
				if (basename($monitor_path_b) == "") {
					$show = "/";
				} else {
					$show = basename($monitor_path_b);
				}
				printf($mask4, $show, $disk_use, $disk_free);
			}
		}
		echo "\n";
		printf($mask3, "Category", "In Process", "In Database");
		printf($mask3, "======================================", "=========================", "======================================");
		printf($mask4, "predb",
			sprintf(
				"%s(%d)",
				number_format($runVar['counts']['now']['predb'] - $runVar['counts']['now']['distinct_predb_matched']),
				$runVar['counts']['diff']['distinct_predb_matched']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['predb_matched']),
				$runVar['counts']['percent']['predb_matched']
			)
		);
		printf($mask4, "requestID",
			sprintf(
				"%s(%d)",
				number_format($runVar['counts']['now']['requestid_inprogress']),
				$runVar['counts']['diff']['requestid_inprogress']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['requestid_matched']),
				$runVar['counts']['percent']['requestid_matched']
			)
		);
		printf($mask4, "NFO's",
			sprintf(
				"%s(%d)",
				number_format($runVar['counts']['now']['processnfo']),
				$runVar['counts']['diff']['processnfo']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['nfo']),
				$runVar['counts']['percent']['nfo']
			)
		);
		printf($mask4, "Games(1000)",
			sprintf(
				"%s(%d)",
				number_format($runVar['counts']['now']['processgames']),
				$runVar['counts']['diff']['processgames']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['console']),
				$runVar['counts']['percent']['console']
			)
		);
		printf($mask4, "Movie(2000)",
			sprintf(
				"%s(%d)",
				number_format($runVar['counts']['now']['processmovies']),
				$runVar['counts']['diff']['processmovies']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['movies']),
				$runVar['counts']['percent']['movies']
			)
		);
		printf($mask4, "Audio(3000)",
			sprintf(
				"%s(%d)",
				number_format($runVar['counts']['now']['processmusic']),
				$runVar['counts']['diff']['processmusic']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['audio']),
				$runVar['counts']['percent']['audio']
			)
		);
		printf($mask4, "Apps(4000)",
			sprintf(
				"%s(%d)",
				number_format($runVar['counts']['now']['apps']),
				$runVar['counts']['diff']['apps']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['pc']),
				$runVar['counts']['percent']['pc']
			)
		);
		printf($mask4, "TV(5000)",
			sprintf(
				"%s(%d)",
				number_format($runVar['counts']['now']['processtvrage']),
				$runVar['counts']['diff']['processtvrage']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['tv']),
				$runVar['counts']['percent']['tv']
			)
		);
		printf($mask4, "xXx(6000)",
			sprintf(
				"%s(%d)",
				number_format($runVar['counts']['now']['processxxx']),
				$runVar['counts']['diff']['processxxx']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['xxx']),
				$runVar['counts']['percent']['xxx']
			)
		);
		printf($mask4, "Misc(7000)",
			sprintf(
				"%s(%d)",
				number_format($runVar['counts']['now']['work']),
				$runVar['counts']['diff']['work']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['misc']),
				$runVar['counts']['percent']['misc']
			)
		);
		printf($mask4, "Books(8000)",
			sprintf(
				"%s(%d)",
				number_format($runVar['counts']['now']['processbooks']),
				$runVar['counts']['diff']['processbooks']
			),
			sprintf(
				"%s(%d%%)",
				number_format($runVar['counts']['now']['books']),
				$runVar['counts']['percent']['books']
			)
		);
		printf($mask4, "Total",
			sprintf(
				"%s(%d)",
				number_format($runVar['counts']['now']['total_work']),
				$runVar['counts']['diff']['total_work']
			),
			sprintf(
				"%s(%d)",
				number_format($runVar['counts']['now']['releases']),
				$runVar['counts']['diff']['releases']
			)
		);
		echo "\n";
		printf($mask3, "Groups", "Active", "Backfill");
		printf($mask3, "======================================", "=========================", "======================================");
		if ($runVar['settings']['backfilldays'] == "1") {
			printf($mask4, "Activated",
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
			printf($mask4, "Activated",
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

	if ($runVar['settings']['show_query'] == 1) {
		echo PHP_EOL;
		printf($mask3, "Query Block", "Time", "Cumulative");
		printf($mask3, "======================================", "=========================", "======================================");
		printf($mask4, "Combined", $tmux_time . " " . $split_time . " " . $init_time . " " . $proc1_time . " " . $proc2_time . " " . $proc3_time . " " . $tpg_count_time, $tmux_time . " " . $split1_time . " " . $init1_time . " " . $proc11_time . " " . $proc21_time . " " . $proc31_time . " " . $tpg_count_1_time);

		$pieces = explode(" ", $pdo->getAttribute(PDO::ATTR_SERVER_INFO));
		echo $pdo->log->primaryOver("\nThreads = ") .
					$pdo->log->headerOver($pieces[4]) . $pdo->log->primaryOver(', Opens = ') .
					$pdo->log->headerOver($pieces[14]) . $pdo->log->primaryOver(', Tables = ') .
					$pdo->log->headerOver($pieces[22]) . $pdo->log->primaryOver(', Slow = ') .
					$pdo->log->headerOver($pieces[11]) . $pdo->log->primaryOver(', QPS = ') .
					$pdo->log->header($pieces[28])
		;
	}

	//get list of panes by name
	switch ($runVar['constants']['sequential']) {
		case 0:
			$panes_win_1 = shell_exec("echo `tmux list-panes -t {$runVar['constants']['tmux_session']}:0 -F '#{pane_title}'`");
			$panes0 = str_replace("\n", '', explode(" ", $panes_win_1));
			$panes_win_2 = shell_exec("echo `tmux list-panes -t {$runVar['constants']['tmux_session']}:1 -F '#{pane_title}'`");
			$panes1 = str_replace("\n", '', explode(" ", $panes_win_2));
			$panes_win_3 = shell_exec("echo `tmux list-panes -t {$runVar['constants']['tmux_session']}:2 -F '#{pane_title}'`");
			$panes2 = str_replace("\n", '', explode(" ", $panes_win_3));
			break;
		case 1:
			$panes_win_1 = shell_exec("echo `tmux list-panes -t {$runVar['constants']['tmux_session']}:0 -F '#{pane_title}'`");
			$panes0 = str_replace("\n", '', explode(" ", $panes_win_1));
			$panes_win_2 = shell_exec("echo `tmux list-panes -t {$runVar['constants']['tmux_session']}:1 -F '#{pane_title}'`");
			$panes1 = str_replace("\n", '', explode(" ", $panes_win_2));
			$panes_win_3 = shell_exec("echo `tmux list-panes -t {$runVar['constants']['tmux_session']}:2 -F '#{pane_title}'`");
			$panes2 = str_replace("\n", '', explode(" ", $panes_win_3));
			break;
		case 2:
			$panes_win_1 = shell_exec("echo `tmux list-panes -t {$runVar['constants']['tmux_session']}:0 -F '#{pane_title}'`");
			$panes0 = str_replace("\n", '', explode(" ", $panes_win_1));
			$panes_win_2 = shell_exec("echo `tmux list-panes -t {$runVar['constants']['tmux_session']}:1 -F '#{pane_title}'`");
			$panes1 = str_replace("\n", '', explode(" ", $panes_win_2));
			break;
	}

	(nZEDb_DEBUG ? $show_time = "/usr/bin/time" : $show_time = "");

	$_php = $show_time . " nice -n{$runVar['settings']['niceness']} $PHP";
	$_phpn = "nice -n{$runVar['settings']['niceness']} $PHP";

	$_python = $show_time . " nice -n{$runVar['settings']['niceness']} $PYTHON";
	$_pythonn = "nice -n{$runVar['settings']['niceness']} $PYTHON";

	if (($runVar['settings']['postprocess_kill'] < $runVar['counts']['now']['total_work']) && ($runVar['settings']['postprocess_kill'] != 0)) {
		$kill_pp = true;
	} else {
		$kill_pp = false;
	}
	if (($runVar['settings']['collections_kill'] < $runVar['counts']['now']['collections_table']) && ($runVar['settings']['collections_kill'] != 0)) {
		$kill_coll = true;
	} else {
		$kill_coll = false;
	}

	if ($runVar['settings']['binaries_run'] != 0) {
		$runVar['scripts']['binaries'] = "$_php ${DIR}update/nix/multiprocessing/binaries.php 0";
	} else if ($runVar['settings']['binaries_run'] == 2) {
		$runVar['scripts']['binaries'] = "$_python ${DIR}update/python/binaries_safe_threaded.py";
	}

	$_sleep = "$_phpn ${DIR}update/nix/tmux/bin/showsleep.php";

	if ($runVar['settings']['releases_run'] != 0) {
		if ($runVar['constants']['tablepergroup'] == 0) {
			$runVar['scripts']['releases'] = "$_php ${DIR}update/update_releases.php 1 false";
		} else {
			$runVar['scripts']['releases'] = "$_php ${DIR}update/nix/multiprocessing/releases.php";
		}
	}

	if ($runVar['settings']['post_non'] == 2) {
		$clean = ' clean ';
	} else {
		$clean = ' ';
	}

	if ($i === 2) {
		$runVar['constants']['pre_lim'] = '7';
	}

	if ($runVar['settings']['is_running'] == 1) {
		//run these if complete sequential not set
		if ($runVar['constants']['sequential'] != 2) {
			//fix names
			switch ($runVar['settings']['fix_names']) {
				case 1:
					$log = $t->writelog($panes1[0]);
					shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.0 ' \
							$_python ${DIR}update/python/groupfixrelnames_threaded.py md5 $log; \
							$_python ${DIR}update/python/groupfixrelnames_threaded.py filename $log; \
							$_python ${DIR}update/python/groupfixrelnames_threaded.py nfo $log; \
							$_python ${DIR}update/python/groupfixrelnames_threaded.py par2 $log; \
							$_python ${DIR}update/python/groupfixrelnames_threaded.py miscsorter $log; \
							$_python ${DIR}update/python/groupfixrelnames_threaded.py predbft $log; date +\"%D %T\"; $_sleep {$runVar['settings']['fix_timer']}' 2>&1 1> /dev/null"
					);
					break;
				default:
					$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
					shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.0 'echo \"\033[38;5;${color}m\n${panes1[0]} has been disabled/terminated by Fix Release Names\"'");
					break;
			}
			//dehash releases
			switch ($runVar['settings']['dehash']) {
				case 1:
					$log = $t->writelog($panes1[3]);
					shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.3 ' \
							$_php ${DIR}update/decrypt_hashes.php 1000 $log; date +\"%D %T\"; $_sleep {$runVar['settings']['dehash_timer']}' 2>&1 1> /dev/null"
					);
					break;
				case 2:
					$log = $t->writelog($panes1[3]);
					shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.3 ' \
							$_php ${DIR}update/nix/tmux/bin/postprocess_pre.php {$runVar['constants']['pre_lim']} $log; date +\"%D %T\"; $_sleep {$runVar['settings']['dehash_timer']}' 2>&1 1> /dev/null"
					);
					break;
				case 3:
					$log = $t->writelog($panes1[3]);
					shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.3 ' \
							$_php ${DIR}update/nix/tmux/bin/postprocess_pre.php {$runVar['constants']['pre_lim']} $log; \
							$_php ${DIR}update/decrypt_hashes.php 1000 $log; date +\"%D %T\"; $_sleep {$runVar['settings']['dehash_timer']}' 2>&1 1> /dev/null"
					);
					break;
				default:
					$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
					shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.3 'echo \"\033[38;5;${color}m\n${panes1[3]} has been disabled/terminated by Decrypt Hashes\"'");
					break;
			}
			// Remove crap releases.
			switch ($runVar['settings']['fix_crap_opt']) {
				// Do all types up to 2 hours.
				case 'All':
					$log = $t->writelog($panes1[1]);
					shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.1 ' \
							$_php ${DIR}testing/Release/removeCrapReleases.php true 2 $log; date +\"%D %T\"; $_sleep {$runVar['settings']['crap_timer']}' 2>&1 1> /dev/null"
					);
					break;
				// The user has specified custom types.
				case 'Custom':
					$log = $t->writelog($panes1[1]);

					// Check how many types the user picked.
					$fcmax = count($runVar['modsettings']['fix_crap']);

					// Make sure the user actually selected some.
					if ($fcmax > 0) {

						// If this is the first run, do a full run, else run on last 2 hours of releases.
						$fctime = '4';
						if ((($i == 1) || $fcfirstrun)) {
							$fctime = 'full';
						}

						//Check to see if the pane is dead, if so respawn it.
						if (shell_exec("tmux list-panes -t{$runVar['constants']['tmux_session']}:1 | grep ^1 | grep -c dead") == 1) {

							// Run remove crap releases.
							shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.1 ' \
								echo \"Running removeCrapReleases for {$runVar['modsettings']['fix_crap'][$fcnum]}\"; \
								php ${DIR}testing/Release/removeCrapReleases.php true $fctime {$runVar['modsettings']['fix_crap'][$fcnum]} $log; date +\"%D %T\"; $_sleep {$runVar['settings']['crap_timer']}' 2>&1 1> /dev/null"
							);

							// Increment so we know which type to run next.
							$fcnum++;
						}

						// If we reached the end, reset the type.
						if ($fcnum == $fcmax) {
							$fcnum = 0;

							// And say we are not on the first run, so we run 2 hours the next times.
							$fcfirstrun = false;
						}
					}
					break;
				case 'Disabled':
				default:
					$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
					shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.1 'echo \"\033[38;5;${color}m\n${panes1[1]} has been disabled/terminated by Remove Crap Releases\"'");
					break;
			}
			//run postprocess_releases additional
			switch (true) {
				case $runVar['settings']['post'] == 1 && ($runVar['counts']['now']['work'] + $runVar['counts']['now']['apps'] + $runVar['counts']['now']['processxxx']) > 0:
					$history = str_replace(" ", '', `tmux list-panes -t{$runVar['constants']['tmux_session']}:2 | grep 0: | awk '{print $4;}'`);
					if ($last_history != $history) {
						$last_history = $history;
						$timer3 = time();
					} else {
						if (time() - $timer3 >= $runVar['settings']['post_kill_timer']) {
							$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
							passthru("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.0 'echo \"\033[38;5;${color}m\n${panes2[0]} has been terminated by Possible Hung thread\"'");
							$wipe = `tmux clearhist -t{$runVar['constants']['tmux_session']}:2.0`;
							$timer3 = time();
						}
					}
					$dead1 = str_replace(" ", '', `tmux list-panes -t{$runVar['constants']['tmux_session']}:2 | grep dead | grep 0: | wc -l`);
					if ($dead1 == 1) {
						$timer3 = time();
					}
					$log = $t->writelog($panes2[0]);
					shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:2.0 'echo \"\033[38;5;${color}m\"; \
							$_php ${DIR}update/nix/multiprocessing/postprocess.php add $log; date +\"%D %T\"; $_sleep {$runVar['settings']['post_timer']}' 2>&1 1> /dev/null"
					);
					break;
				case $runVar['settings']['post'] == 2 && $runVar['counts']['now']['processnfo'] > 0:
					$log = $t->writelog($panes2[0]);
					shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:2.0 ' \
							$_php ${DIR}update/nix/multiprocessing/postprocess.php nfo $log; date +\"%D %T\"; $_sleep {$runVar['settings']['post_timer']}' 2>&1 1> /dev/null"
					);
					break;
				case $runVar['settings']['post'] == 3 && ($runVar['counts']['now']['processnfo'] > 0 || $runVar['counts']['now']['work'] + $runVar['counts']['now']['apps'] + $runVar['counts']['now']['processxxx'] > 0):
					//run postprocess_releases additional
					$history = str_replace(" ", '', `tmux list-panes -t{$runVar['constants']['tmux_session']}:2 | grep 0: | awk '{print $4;}'`);
					if ($last_history != $history) {
						$last_history = $history;
						$timer3 = time();
					} else {
						if (time() - $timer3 >= $runVar['settings']['post_kill_timer']) {
							$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
							shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.0 'echo \"\033[38;5;${color}m\n${panes2[0]} has been terminated by Possible Hung thread\"'");
							$wipe = `tmux clearhist -t{$runVar['constants']['tmux_session']}:2.0`;
							$timer3 = time();
						}
					}
					$dead1 = str_replace(" ", '', `tmux list-panes -t{$runVar['constants']['tmux_session']}:2 | grep dead | grep 0: | wc -l`);
					if ($dead1 == 1) {
						$timer3 = time();
					}
					$log = $t->writelog($panes2[0]);
					shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:2.0 ' \
							$_php ${DIR}update/nix/multiprocessing/postprocess.php add $log; \
							$_php ${DIR}update/nix/multiprocessing/postprocess.php nfo $log; date +\"%D %T\"; $_sleep {$runVar['settings']['post_timer']}' 2>&1 1> /dev/null"
					);
					break;
				case $runVar['settings']['post'] != 0 && ($runVar['counts']['now']['processnfo'] == 0) && ($runVar['counts']['now']['work'] + $runVar['counts']['now']['apps'] + $runVar['counts']['now']['processxxx'] == 0):
					$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
					shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.0 'echo \"\033[38;5;${color}m\n${panes2[0]} has been disabled/terminated by No Misc/Nfo to process\"'");
					break;
				default:
					$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
					shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.0 'echo \"\033[38;5;${color}m\n${panes2[0]} has been disabled/terminated by Postprocess Additional\"'");
					break;
			}
			//run postprocess_releases non amazon
			switch (true) {
				case $runVar['settings']['post_non'] != 0 && ($runVar['counts']['now']['processmovies'] > 0 || $runVar['counts']['now']['processtvrage'] > 0):
					$log = $t->writelog($panes2[1]);
					shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:2.1 ' \
							$_php ${DIR}update/nix/multiprocessing/postprocess.php tv $clean $log; \
							$_php ${DIR}update/nix/multiprocessing/postprocess.php mov $clean $log; date +\"%D %T\"; $_sleep {$runVar['settings']['post_timer_non']}' 2>&1 1> /dev/null"
					);
					break;
				case $runVar['settings']['post_non'] != 0 && $runVar['counts']['now']['processmovies'] == 0 && $runVar['counts']['now']['processtvrage'] == 0:
					$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
					shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.1 'echo \"\033[38;5;${color}m\n${panes2[1]} has been disabled/terminated by No Movies/TV to process\"'");
					break;
				default:
					$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
					shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.1 'echo \"\033[38;5;${color}m\n${panes2[1]} has been disabled/terminated by Postprocess Non-Amazon\"'");
					break;
			}
			//run postprocess_releases amazon
			switch (true) {
				case $runVar['settings']['post_amazon'] == 1 && ($runVar['counts']['now']['processmusic'] > 0 || $runVar['counts']['now']['processbooks'] > 0 || $runVar['counts']['now']['processgames'] > 0 || $runVar['counts']['now']['apps'] > 0 || $runVar['counts']['now']['processxxx'] > 0)
						&& ($runVar['settings']['processbooks'] == 1 || $runVar['settings']['processmusic'] == 1 || $runVar['settings']['processgames'] == 1  || $runVar['settings']['processxxx'] == 1):
					$log = $t->writelog($panes2[2]);
					shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:2.2 ' \
							$_phpn ${DIR}update/postprocess.php amazon true $log; date +\"%D %T\"; $_sleep {$runVar['settings']['post_timer_amazon']}' 2>&1 1> /dev/null"
					);
					break;
				case $runVar['settings']['post_amazon'] == 1 && $runVar['settings']['processbooks'] == 0 && $runVar['settings']['processmusic'] == 0 && $runVar['settings']['processgames'] == 0 && $runVar['settings']['processxxx'] == 0:
					$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
					shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.2 'echo \"\033[38;5;${color}m\n${panes2[2]} has been disabled/terminated in Admin Disable Music/Books/Console/XXX\"'");
					break;
				case $runVar['settings']['post_amazon'] == 1 && $runVar['counts']['now']['processmusic'] == 0 && $runVar['counts']['now']['processbooks'] == 0 && $runVar['counts']['now']['processgames'] == 0 && $runVar['counts']['now']['apps'] == 0 && $runVar['counts']['now']['processxxx'] == 0:
					$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
					shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.2 'echo \"\033[38;5;${color}m\n${panes2[2]} has been disabled/terminated by No Music/Books/Console/Games/XXX to process\"'");
					break;
				default:
					$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
					shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.2 'echo \"\033[38;5;${color}m\n${panes2[2]} has been disabled/terminated by Postprocess Amazon\"'");
					break;
				}
			//update tv and theaters
			switch (true) {
				case $runVar['settings']['update_tv'] == 1 && (time() - $timer4 >= $runVar['settings']['tv_timer'] || $i == 1):
					$log = $t->writelog($panes1[3]);
					shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.2 ' \
							$_phpn ${DIR}update/update_theaters.php $log; $_phpn ${DIR}testing/PostProc/populate_tvrage.php true $log; \
							$_phpn ${DIR}update/update_tvschedule.php $log; $_phpn ${DIR}testing/PostProc/updateTvRage.php $log; date +\"%D %T\"' 2>&1 1> /dev/null"
					);
					$timer4 = time();
					break;
				case $runVar['settings']['update_tv'] == 1:
					$run_time = $t->relativeTime($runVar['settings']['tv_timer'] + $timer4);
					$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
					shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.2 'echo \"\033[38;5;${color}m\n${panes1[2]} will run in T[ $run_time]\"' 2>&1 1> /dev/null");
					break;
				default:
					$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
					shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.2 'echo \"\033[38;5;${color}m\n${panes1[2]} has been disabled/terminated by Update TV/Theater\"'");
				}
			}

		if ($runVar['constants']['sequential'] == 1) {
			//run nzb-import
			if (($runVar['settings']['import'] != 0) && ($kill_pp == false)) {
				$log = $t->writelog($panes0[1]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.1 ' \
						$_python ${DIR}update/python/import_threaded.py $log; date +\"%D %T\"; $_sleep0 {$runVar['settings']['import_timer']}' 2>&1 1> /dev/null"
				);
			} else {
				$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.1 'echo \"\033[38;5;${color}m\n${panes0[1]} has been disabled/terminated by Import\"'");
			}

			//run update_binaries
			$log = $t->writelog($panes0[2]);
			if (($kill_coll == false) && ($kill_pp == false) && (time() - $timer5 <= 4800)) {
				switch (true) {
					//runs all/safe less than 4800
					case $runVar['settings']['binaries_run'] != 0 && $runVar['settings']['backfill'] == 4 && $runVar['settings']['releases_run'] != 0:
						shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
								{$runVar['scripts']['binaries']} $log; \
								$_python ${DIR}update/python/backfill_safe_threaded.py $log; \
								{$runVar['scripts']['releases']} $log; date +\"%D %T\"; $_sleep {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
						);
						break;
					//runs all less than 4800
					case $runVar['settings']['binaries_run'] != 0 && $runVar['settings']['backfill'] != 0 && $runVar['settings']['releases_run'] != 0:
						shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
								{$runVar['scripts']['binaries']} $log; \
								$_python ${DIR}update/python/backfill_threaded.py $log; \
								{$runVar['scripts']['releases']} $log; date +\"%D %T\"; $_sleep {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
						);
						break;
					//runs bin/back/safe less than 4800
					case $runVar['settings']['binaries_run'] != 0 && $runVar['settings']['backfill'] == 4 && $runVar['settings']['releases_run'] == 0:
						shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
								{$runVar['scripts']['binaries']} $log; \
								$_python ${DIR}update/python/backfill_safe_threaded.py $log; date +\"%D %T\"; \
								echo \"\nreleases has been disabled/terminated by Releases\"; $_sleep {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
						);
						break;
					//runs bin/back less than 4800
					case $runVar['settings']['binaries_run'] != 0 && $runVar['settings']['backfill'] != 0 && $runVar['settings']['releases_run'] == 0:
						shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
								{$runVar['scripts']['binaries']} $log; \
								$_python ${DIR}update/python/backfill_threaded.py $log; date +\"%D %T\"; echo \"\nreleases have been disabled/terminated by Releases\"; \
								$_sleep {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
						);
						break;
					//runs back/safe/rel less than 4800
					case $runVar['settings']['binaries_run'] == 0 && $runVar['settings']['backfill'] == 4 && $runVar['settings']['releases_run'] != 0:
						shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
								$_python ${DIR}update/python/backfill_safe_threaded.py $log; \
								{$runVar['scripts']['releases']} $log; date +\"%D %T\"; echo \"\nbinaries has been disabled/terminated by Binaries\"; \
								$_sleep {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
						);
						break;
					//runs back/rel less than 4800
					case $runVar['settings']['binaries_run'] == 0 && $runVar['settings']['backfill'] != 0 && $runVar['settings']['releases_run'] != 0:
						shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
								$_python ${DIR}update/python/backfill_threaded.py $log; \
								{$runVar['scripts']['releases']} $log; date +\"%D %T\"; echo \"\nbinaries has been disabled/terminated by Binaries\"; \
								$_sleep {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
						);
						break;
					//runs bin/rel less than 4800
					case $runVar['settings']['binaries_run'] != 0 && $runVar['settings']['backfill'] == 0 && $runVar['settings']['releases_run'] != 0:
						shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
								{$runVar['scripts']['binaries']} $log; \
								{$runVar['scripts']['releases']} $log; date +\"%D %T\"; echo \"\nbackfill has been disabled/terminated by Backfill\"; \
								$_sleep {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
						);
						break;
					//runs bin less than 4800
					case $runVar['settings']['binaries_run'] != 0 && $runVar['settings']['backfill'] == 0 && $runVar['settings']['releases_run'] == 0:
						shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
								{$runVar['scripts']['binaries']} $log; date +\"%D %T\"; echo \"\nbackfill and releases have been disabled/terminated by Backfill and Releases\"; \
								$_sleep {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
						);
						break;
					//runs back/safe less than 4800
					case $runVar['settings']['binaries_run'] == 0 && $runVar['settings']['backfill'] == 4 && $runVar['settings']['releases_run'] == 0:
						shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
								$_python ${DIR}update/python/backfill_safe_threaded.py $log; date +\"%D %T\"; echo \"\nbinaries and releases have been disabled/terminated by Binaries and Releases\"; \
								$_sleep {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
						);
						break;
					//runs back less than 4800
					case $runVar['settings']['binaries_run'] == 0 && $runVar['settings']['backfill'] == 4 && $runVar['settings']['releases_run'] == 0:
						shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
								$_python ${DIR}update/python/backfill_threaded.py $log; date +\"%D %T\"; echo \"\nbinaries and releases have been disabled/terminated by Binaries and Releases\"; \
								$_sleep {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
						);
						break;
					//runs rel less than 4800
					case $runVar['settings']['binaries_run'] == 0 && $runVar['settings']['backfill'] == 0 && $runVar['settings']['releases_run'] != 0:
						shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
								{$runVar['scripts']['releases']} $log; date +\"%D %T\"; echo \"\nbinaries and backfill has been disabled/terminated by Binaries and Backfill\"; \
								$_sleep {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
						);
						break;
					case $runVar['settings']['binaries_run'] == 0 && $runVar['settings']['backfill'] == 0 && $runVar['settings']['releases_run'] == 0:
						shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
								echo \"\nbinaries, backfill and releases have been disabled/terminated by Binaries, Backfill and Releases\"; \
								$_sleep {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
						);
						break;
				}
			} else if (($kill_coll == false) && ($kill_pp == false) && (time() - $timer5 >= 4800)) {
				//run backfill all once and resets the timer
				if ($runVar['settings']['backfill'] != 0) {
					shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.2 ' \
						$_php ${DIR}update/python/backfill_threaded.py all $log; date +\"%D %T\"; $_sleep {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
					);
					$timer5 = time();
				}
				$timer5 = time();
			} else if ((($kill_coll == true) || ($kill_pp == true)) && ($runVar['settings']['releases_run'] != 0)) {
				$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 'echo \"\033[38;5;${color}m\"; \
					echo \"\nbinaries and backfill has been disabled/terminated by Exceeding Limits\"; \
					{$runVar['scripts']['releases']} $log; date +\"%D %T\"; echo \"\nbinaries and backfill has been disabled/terminated by Exceeding Limits\"; $_sleep {$runVar['settings']['seq_timer']}' 2>&1 1> /dev/null"
				);
			} else if (($kill_coll == true) || ($kill_pp == true)) {
				$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 'echo \"\033[38;5;${color}m\n${panes0[2]} has been disabled/terminated by Exceeding Limits\"'");
			}

			//pane setup for IrcScraper / Sharing
			$ipane = 3;
			if ($runVar['constants']['nntpproxy'] == 1) {
				$spane = 5;
			} else {
				$spane = 4;
			}
			//run IRCScraper
			$t->run_ircscraper($runVar['constants']['tmux_session'], $_php, $ipane, $runVar['constants']['run_ircscraper']);
			//run Sharing
			$t->run_sharing($runVar['constants']['tmux_session'], $_php, $spane, $_sleep, $runVar['settings']['sharing_timer']);
		} else if ($runVar['constants']['sequential'] == 2) {
			//run nzb-import
			if (($runVar['settings']['import'] != 0) && ($kill_pp == false)) {
				$log = $t->writelog($panes0[1]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.1 ' \
						$_python ${DIR}update/python/import_threaded.py $log; date +\"%D %T\"; $_sleep {$runVar['settings']['import_timer']}' 2>&1 1> /dev/null"
				);
			} else {
				$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.1 'echo \"\033[38;5;${color}m\n${panes0[1]} has been disabled/terminated by Import\"'");
			}

			//update tv and theaters
			if (($runVar['settings']['update_tv'] == 1) && ((time() - $timer4 >= $runVar['settings']['tv_timer']) || ($i == 1))) {
				$log = $t->writelog($panes1[0]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.0 ' \
						$_phpn ${DIR}update/update_theaters.php $log; $_phpn ${DIR}testing/PostProc/populate_tvrage.php true $log; \
                                                $_phpn ${DIR}update/update_tvschedule.php $log; $_phpn ${DIR}testing/PostProc/updateTvRage.php $log; date +\"%D %T\"' 2>&1 1> /dev/null"
				);
				$timer4 = time();
			} else if ($runVar['settings']['update_tv'] == 1) {
				$run_time = $t->relativeTime($runVar['settings']['tv_timer'] + $timer4);
				$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.0 'echo \"\033[38;5;${color}m\n${panes1[0]} will run in T[ $run_time]\"' 2>&1 1> /dev/null");
			} else {
				$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.0 'echo \"\033[38;5;${color}m\n${panes1[0]} has been disabled/terminated by Update TV/Theater\"'");
			}

			if (($runVar['settings']['post_amazon'] == 1) && (($runVar['counts']['now']['processmusic'] > 0) || ($runVar['counts']['now']['processbooks'] > 0) ||
					($runVar['counts']['now']['processgames'] > 0) || ($runVar['counts']['now']['apps'] > 0) || ($runVar['counts']['now']['processxxx'] > 0)) && (($runVar['settings']['processbooks'] != 0) || ($runVar['settings']['processmusic'] != 0) || ($runVar['settings']['processgames'] != 0) || ($runVar['settings']['processxxx'] != 0))) {
				//run postprocess_releases amazon
				$log = $t->writelog($panes1[1]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:1.1 ' \
						$_phpn ${DIR}update/postprocess.php amazon true $log; date +\"%D %T\"; $_sleep {$runVar['settings']['post_timer_amazon']}' 2>&1 1> /dev/null"
				);
			} else if (($runVar['settings']['post_amazon'] == 1) && ($runVar['settings']['processbooks'] == 0) && ($runVar['settings']['processmusic'] == 0) && ($runVar['settings']['processgames'] == 0)) {
				$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.1 'echo \"\033[38;5;${color}m\n${panes1[1]} has been disabled/terminated in Admin Disable Music/Books/Console/XXX\"'");
			} else if (($runVar['settings']['post_amazon'] == 1) && ($runVar['counts']['now']['processmusic'] == 0) && ($runVar['counts']['now']['processbooks'] == 0) && ($runVar['counts']['now']['processgames'] == 0) && ($runVar['counts']['now']['apps'] == 0) && ($runVar['counts']['now']['processxxx'] == 0)) {
				$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.1 'echo \"\033[38;5;${color}m\n${panes1[1]} has been disabled/terminated by No Music/Books/Console/Games/XXX to process\"'");
			} else {
				$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.1 'echo \"\033[38;5;${color}m\n${panes1[1]} has been disabled/terminated by Postprocess Amazon\"'");
			}

			//run user_threaded.sh
			$log = $t->writelog($panes0[2]);
			shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
					${DIR}update/nix/screen/sequential/user_threaded.sh true $log; date +\"%D %T\"' 2>&1 1> /dev/null"
			);

			//pane setup for IrcScraper / Sharing
			$ipane = 2;
			if ($runVar['constants']['nntpproxy'] == 1) {
				$spane = 4;
			} else {
				$spane = 3;
			}

			//run IRCScraper
			$t->run_ircscraper($runVar['constants']['tmux_session'], $_php, $ipane, $runVar['constants']['run_ircscraper']);

			//run Sharing
			$t->run_sharing($runVar['constants']['tmux_session'], $_php, $spane, $_sleep, $runVar['settings']['sharing_timer']);
		} else {
			//run update_binaries
			$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
			if (($runVar['settings']['binaries_run'] != 0) && ($kill_coll == false) && ($kill_pp == false)) {
				$log = $t->writelog($panes0[2]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.2 ' \
						{$runVar['scripts']['binaries']} $log; date +\"%D %T\"; $_sleep {$runVar['settings']['bins_timer']}' 2>&1 1> /dev/null"
				);
			} else if (($kill_coll == true) || ($kill_pp == true)) {
				$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.2 'echo \"\033[38;5;${color}m\n${panes0[2]} has been disabled/terminated by Exceeding Limits\"'");
			} else {
				$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.2 'echo \"\033[38;5;${color}m\n${panes0[2]} has been disabled/terminated by Binaries\"'");
			}

			//run backfill
			if ($runVar['settings']['progressive'] == 1 && floor($runVar['counts']['collections']['rowCount'] / 500) > $runVar['settings']['back_timer']) {
				$backsleep = floor($runVar['counts']['collections']['rowCount'] / 500);
			} else {
				$backsleep = $runVar['settings']['back_timer'];
			}

			if (($runVar['settings']['backfill'] == 4) && ($kill_coll == false) && ($kill_pp == false) && (time() - $timer5 <= 4800)) {
				$log = $t->writelog($panes0[3]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.3 ' \
						$_python ${DIR}update/python/backfill_safe_threaded.py $log; date +\"%D %T\"; $_sleep $backsleep' 2>&1 1> /dev/null"
				);
			} else if (($runVar['settings']['backfill'] != 0) && ($kill_coll == false) && ($kill_pp == false) && (time() - $timer5 <= 4800)) {
				$log = $t->writelog($panes0[3]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.3 ' \
						$_python ${DIR}update/python/backfill_threaded.py group $log; date +\"%D %T\"; $_sleep $backsleep' 2>&1 1> /dev/null"
				);
			} else if (($runVar['settings']['backfill'] != 0) && ($kill_coll == false) && ($kill_pp == false) && (time() - $timer5 >= 4800)) {
				$log = $t->writelog($panes0[3]);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.3 ' \
						$_python ${DIR}update/python/backfill_threaded.py all $log; date +\"%D %T\"; $_sleep $backsleep' 2>&1 1> /dev/null"
				);
				$timer5 = time();
			} else if (($kill_coll == true) || ($kill_pp == true)) {
				$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.3 'echo \"\033[38;5;${color}m\n${panes0[3]} has been disabled/terminated by Exceeding Limits\"'");
			} else {
				$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.3 'echo \"\033[38;5;${color}m\n${panes0[3]} has been disabled/terminated by Backfill\"'");
			}

			//run nzb-import
			if (($runVar['settings']['import'] != 0) && ($kill_pp == false)) {
				$log = $t->writelog($panes0[1]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.1 ' \
						$_python ${DIR}update/python/import_threaded.py $log; date +\"%D %T\"; $_sleep {$runVar['settings']['import_timer']}' 2>&1 1> /dev/null"
				);
			} else if ($kill_pp == true) {
				$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.1 'echo \"\033[38;5;${color}m\n${panes0[1]} has been disabled/terminated by Exceeding Limits\"'");
			} else {
				$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.1 'echo \"\033[38;5;${color}m\n${panes0[1]} has been disabled/terminated by Import\"'");
			}

			//run update_releases
			if ($runVar['settings']['releases_run'] != 0) {
				$log = $t->writelog($panes0[4]);
				shell_exec("tmux respawnp -t{$runVar['constants']['tmux_session']}:0.4 ' \
						{$runVar['scripts']['releases']} $log; date +\"%D %T\"; $_sleep {$runVar['settings']['rel_timer']}' 2>&1 1> /dev/null"
				);
			} else {
				$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
				shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.4 'echo \"\033[38;5;${color}m\n${panes0[4]} has been disabled/terminated by Releases\"'");
			}

			//pane setup for IrcScraper / Sharing
			$ipane = 3;
			if ($runVar['constants']['nntpproxy'] == 1) {
				$spane = 5;
			} else {
				$spane = 4;
			}

			//run IRCScraper
			$t->run_ircscraper($runVar['constants']['tmux_session'], $_php, $ipane, $runVar['constants']['run_ircscraper']);

			//run Sharing
			$t->run_sharing($runVar['constants']['tmux_session'], $_php, $spane, $_sleep, $runVar['settings']['sharing_timer']);
		}
	} else if ($runVar['constants']['sequential'] == 0) {
		for ($g = 1; $g <= 4; $g++) {
			$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.$g 'echo \"\033[38;5;${color}m\n${panes0[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 3; $g++) {
			$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.$g 'echo \"\033[38;5;${color}m\n${panes1[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 2; $g++) {
			$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.$g 'echo \"\033[38;5;${color}m\n${panes2[$g]} has been disabled/terminated by Running\"'");
		}
	} else if ($runVar['constants']['sequential'] == 1) {
		for ($g = 1; $g <= 2; $g++) {
			$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.$g 'echo \"\033[38;5;${color}m\n${panes0[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 3; $g++) {
			$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.$g 'echo \"\033[38;5;${color}m\n${panes1[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 2; $g++) {
			$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:2.$g 'echo \"\033[38;5;${color}m\n${panes2[$g]} has been disabled/terminated by Running\"'");
		}
	} else if ($runVar['constants']['sequential'] == 2) {
		for ($g = 1; $g <= 2; $g++) {
			$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:0.$g 'echo \"\033[38;5;${color}m\n${panes0[$g]} has been disabled/terminated by Running\"'");
		}
		for ($g = 0; $g <= 1; $g++) {
			$color = $t->get_color($runVar['settings']['colors_start'], $runVar['settings']['colors_end'], $runVar['settings']['colors_exc']);
			shell_exec("tmux respawnp -k -t{$runVar['constants']['tmux_session']}:1.$g 'echo \"\033[38;5;${color}m\n${panes1[$g]} has been disabled/terminated by Running\"'");
		}
	}

	$i++;
	sleep(10);
}
