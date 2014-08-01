<?php
require_once dirname(__FILE__) . '/../../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();
$tRun = new TmuxRun($pdo);
$tOut = new TmuxOutput($pdo);

$runVar['paths']['misc'] = nZEDb_MISC;
$db_name = DB_NAME;
$dbtype = DB_SYSTEM;
$tmux = $tRun->get('niceness');

$tmux_niceness = (isset($tmux->niceness) ? $tmux->niceness : 2);

$runVar['constants'] = $pdo->queryOneRow($tRun->getConstantSettings());

$PHP = ($tRun->command_exist("php5") ? 'php5' : 'php');
$PYTHON = ($tRun->command_exist("python3") ? 'python3 -OOu' : 'python -OOu');

//assign shell commands
$show_time = (nZEDb_DEBUG ? "/usr/bin/time" : "");
$runVar['commands']['_php'] = $show_time . " nice -n{$tmux_niceness} $PHP";
$runVar['commands']['_phpn'] = "nice -n{$tmux_niceness} $PHP";
$runVar['commands']['_python'] = $show_time . " nice -n{$tmux_niceness} $PYTHON";
$runVar['commands']['_sleep'] = "{$runVar['commands']['_phpn']} {$runVar['paths']['misc']}update/nix/tmux/bin/showsleep.php";

//spawn IRCScraper as soon as possible
$tRun->runPane('scraper', $runVar);

//get list of panes by name
$runVar['panes'] = $tRun->getListOfPanes($runVar);

//totals per category in db, results by parentID
$catcntqry = "SELECT c.parentid AS parentid, COUNT(r.id) AS count FROM category c, releases r WHERE r.categoryid = c.id GROUP BY c.parentid";

//create timers and set to now
$runVar['timers']['timer1'] = $runVar['timers']['timer2'] = $runVar['timers']['timer3'] =
$runVar['timers']['timer4'] = $runVar['timers']['timer5'] = time();

$runVar['timers']['query']['tmux_time'] = $runVar['timers']['query']['split_time'] = $runVar['timers']['query']['init_time'] = $runVar['timers']['query']['proc1_time'] =
$runVar['timers']['query']['proc2_time'] = $runVar['timers']['query']['proc3_time'] = $runVar['timers']['query']['split1_time'] = $runVar['timers']['query']['init1_time'] =
$runVar['timers']['query']['proc11_time'] = $runVar['timers']['query']['proc21_time'] = $runVar['timers']['query']['proc31_time'] = $runVar['timers']['query']['tpg_time'] =
$runVar['timers']['query']['tpg1_time'] = 0;

// Analyze tables
printf($pdo->log->info("\nAnalyzing your tables to refresh your indexes."));
$pdo->optimise(true, 'analyze');
passthru('clear');

$runVar['settings']['monitor'] = 0;
$runVar['counts']['iterations'] = 1;
$runVar['modsettings']['fc']['firstrun'] = true;
$runVar['modsettings']['fc']['num'] = 0;

while ($runVar['counts']['iterations'] > 0) {

	//check the db connection
	if ($pdo->ping(true) == false) {
		unset($pdo);
		$pdo = new Settings();
	}

	$timer01 = time();
	// These queries are very fast, run every loop -- tmux and site settings
	$runVar['settings'] = $pdo->queryOneRow($tRun->getMonitorSettings(), false);
	$runVar['timers']['query']['tmux_time'] = (time() - $timer01);

	$runVar['settings']['book_reqids'] = (!empty($runVar['settings']['book_reqids'])
		? $runVar['settings']['book_reqids'] : Category::CAT_PARENT_BOOKS);

	//get usenet connection info
	$runVar['connections'] = $tOut->getConnectionsInfo($runVar);

	$runVar['modsettings']['clean'] = ($runVar['settings']['post_non'] == 2 ? ' clean ' : ' ');
	$runVar['constants']['pre_lim'] = ($runVar['counts']['iterations'] > 1 ? '7' : '');

	//assign scripts
	$runVar['scripts']['releases'] = ($runVar['constants']['tablepergroup'] == 0
		? "{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/update_releases.php 1 false"
		: "{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/multiprocessing/releases.php"
	);
	$runVar['scripts']['binaries'] = ($runVar['settings']['binaries_run'] < 2
		? "{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/multiprocessing/binaries.php 0"
		: "{$runVar['commands']['_python']} {$runVar['paths']['misc']}update/python/binaries_safe_threaded.py"
	);

	switch ((int) $runVar['settings']['backfill']) {
		case 1:
			$runVar['scripts']['backfill'] = "{$runVar['commands']['_php']} {$runVar['paths']['misc']}update/nix/multiprocessing/backfill.php";
			break;
		case 2:
			$runVar['scripts']['backfill'] = "{$runVar['commands']['_python']} {$runVar['paths']['misc']}update/python/backfill_threaded.py group";
			break;
		case 4:
			$runVar['scripts']['backfill'] = "{$runVar['commands']['_python']} {$runVar['paths']['misc']}update/python/backfill_safe_threaded.py";
	}

	//get usenet connection counts
	$runVar['conncounts'] = $tOut->getConnectionsCounts($runVar);

	//run queries only after time exceeded, these queries can take awhile
	if ($runVar['counts']['iterations'] == 1 || (time() - $runVar['timers']['timer2'] >= $runVar['settings']['monitor'] && $runVar['settings']['is_running'] == 1)) {

		$runVar['counts']['proc1'] = $runVar['counts']['proc2'] = $runVar['counts']['proc3'] = $splitqry = $newOldqry = false;
		$runVar['counts']['now']['total_work'] = 0;
		$runVar['modsettings']['fix_crap'] = explode(', ', ($runVar['settings']['fix_crap']));

		echo $pdo->log->info("\nThe numbers(queries) above are currently being refreshed. \nNo pane(script) can be (re)started until these have completed.\n");
		$timer02 = time();

		if ($dbtype == 'mysql') {
			$splitqry = $tRun->proc_query(4, $runVar['settings']['book_reqids'], $runVar['settings']['request_hours'], $db_name);
			$newOldqry = $tRun->proc_query(6, $runVar['settings']['book_reqids'], $runVar['settings']['request_hours'], $db_name);
		} else {
			$splitqry = $tRun->proc_query(5, $runVar['settings']['book_reqids'], $runVar['settings']['request_hours'], $db_name);
			$newOldqry = $tRun->proc_query(7, $runVar['settings']['book_reqids'], $runVar['settings']['request_hours'], $db_name);
		}

		$splitres = $pdo->queryOneRow($splitqry, false);
		$runVar['timers']['newOld'] = $pdo->queryOneRow($newOldqry, false);

		//assign split query results to main var
		foreach ($splitres AS $splitkey => $split) {
			$runVar['counts']['now'][$splitkey] = $split;
		}

		$runVar['timers']['query']['split_time'] = (time() - $timer02);
		$runVar['timers']['query']['split1_time'] = (time() - $timer01);

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

		$runVar['timers']['query']['init_time'] = (time() - $timer03);
		$runVar['timers']['query']['init1_time'] = (time() - $timer01);

		$timer04 = time();
		$proc1res = $pdo->queryOneRow($tRun->proc_query(1, $runVar['settings']['book_reqids'], $runVar['settings']['request_hours'], $db_name), $tRun->rand_bool($runVar['counts']['iterations']));
		$runVar['timers']['query']['proc1_time'] = (time() - $timer04);
		$runVar['timers']['query']['proc11_time'] = (time() - $timer01);

		$timer05 = time();
		$proc2res = $pdo->queryOneRow($tRun->proc_query(2, $runVar['settings']['book_reqids'], $runVar['settings']['request_hours'], $db_name), $tRun->rand_bool($runVar['counts']['iterations']));
		$runVar['timers']['query']['proc2_time'] = (time() - $timer05);
		$runVar['timers']['query']['proc21_time'] = (time() - $timer01);

		$timer06 = time();
		$proc3res = $pdo->queryOneRow($tRun->proc_query(3, $runVar['settings']['book_reqids'], $runVar['settings']['request_hours'], $db_name), $tRun->rand_bool($runVar['counts']['iterations']));
		$runVar['timers']['query']['proc3_time'] = (time() - $timer06);
		$runVar['timers']['query']['proc31_time'] = (time() - $timer01);

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
						$run = $pdo->queryOneRow($cntsql, $tRun->rand_bool($runVar['counts']['iterations']));
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
							$tRun->rand_bool($runVar['counts']['iterations'])
						);
						if (isset($run1['dateadded']) && is_numeric($run1['dateadded']) && $run1['dateadded'] < $age) {
							$age = $run1['dateadded'];
						}
					} else if (strpos($tbl, 'binaries_') !== false) {
						$run = $pdo->queryOneRow($cntsql, $tRun->rand_bool($runVar['counts']['iterations']));
						if (isset($run['count']) && is_numeric($run['count'])) {
							$runVar['counts']['now']['binaries_table'] += $run['count'];
						}
					} else if (strpos($tbl, 'parts_') !== false) {
						$run = $pdo->queryOneRow($cntsql, $tRun->rand_bool($runVar['counts']['iterations']));
						if (isset($run['count']) && is_numeric($run['count'])) {
							$runVar['counts']['now']['parts_table'] += $run['count'];
						}
					} else if (strpos($tbl, 'partrepair_') !== false) {
						$run = $pdo->queryOneRow($cntsql, $tRun->rand_bool($runVar['counts']['iterations']));
						if (isset($run['count']) && is_numeric($run['count'])) {
							$runVar['counts']['now']['partrepair_table'] += $run['count'];
						}
					}
				}
				$runVar['timers']['newOld']['oldestcollection'] = $age;

				//free up memory used by now stale data
				unset($age, $run, $run1, $tables);

				$runVar['timers']['query']['tpg_time'] = (time() - $timer07);
				$runVar['timers']['query']['tpg1_time'] = (time() - $timer01);
			}
		}
		$runVar['timers']['timer2'] = time();

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
		if ($runVar['counts']['iterations'] == 1) {
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
			$runVar['counts']['percent'][$key] = ($runVar['counts']['now']['releases'] > 0
				? sprintf("%02s", floor(($proc / $runVar['counts']['now']['releases']) * 100)) : 0);
		}

		$runVar['counts']['now']['total_work'] += $runVar['counts']['now']['work'];

		// Set initial total work count for diff
		if ($runVar['counts']['iterations'] == 1) {
			$runVar['counts']['start']['total_work'] = $runVar['counts']['now']['total_work'];
		}

		// Set diff total work count
		$runVar['counts']['diff']['total_work'] = number_format($runVar['counts']['now']['total_work'] - $runVar['counts']['start']['total_work']);
	}

	//set kill switches
	$runVar['killswitch']['pp'] = (($runVar['settings']['postprocess_kill'] < $runVar['counts']['now']['total_work']) && ($runVar['settings']['postprocess_kill'] != 0)
		? true
		: false
	);
	$runVar['killswitch']['coll'] = (($runVar['settings']['collections_kill'] < $runVar['counts']['now']['collections_table']) && ($runVar['settings']['collections_kill'] != 0)
		? true
		: false
	);

	//begin update display with screen clear
	passthru('clear');

	//display monitor header
	$tOut->displayOutput(1, $runVar);

	//display monitor body
	($runVar['settings']['monitor'] > 0 ? $tOut->displayOutput(2, $runVar) : '');

	//display query block
	($runVar['settings']['show_query'] == 1 ? $tOut->displayOutput(3, $runVar) : '');

	//begin pane run execution
	if ($runVar['settings']['is_running'] === '1') {

		//run main updating function(s)
		$tRun->runPane('main', $runVar);

		//run nzb-import
		$tRun->runPane('import', $runVar);

		//run postprocess_releases amazon
		$tRun->runPane('amazon', $runVar);

		//respawn IRCScraper if it has been killed
		$tRun->runPane('scraper', $runVar);

		//run sharing regardless of sequential setting
		$tRun->runPane('sharing', $runVar);

		//update tv and theaters
		$runVar['timers']['timer4'] = $tRun->runPane('updatetv', $runVar);

		//run these if complete sequential not set
		if ($runVar['constants']['sequential'] != 2) {

			//fix names
			$tRun->runPane('fixnames', $runVar);

			//dehash releases
			$tRun->runPane('dehash', $runVar);

			// Remove crap releases.
			$runVar['modsettings']['fc'] = $tRun->runPane('removecrap', $runVar);

			//run postprocess_releases additional
			$runVar['timers']['timer3'] = $tRun->runPane('ppadditional', $runVar);

			//run postprocess_releases non amazon
			$tRun->runPane('nonamazon', $runVar);
		}

	} else  if ($runVar['settings']['is_running'] === '0') {
		$tRun->runPane('notrunning', $runVar);
	}

	$runVar['counts']['iterations']++;
	sleep(10);
}
