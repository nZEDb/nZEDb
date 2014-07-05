<?php
require_once dirname(__FILE__) . '/../../../config.php';

use nzedb\db\Settings;

$c = new ColorCLI;
$pdo = new Settings();
$DIR = nZEDb_MISC;
$ROOTDIR = nZEDb_ROOT;
$smarty = SMARTY_DIR . 'templates_c/';
$dbname = DB_NAME;

function command_exist($cmd)
{
	$returnVal = shell_exec("which $cmd");
	return (empty($returnVal) ? false : true);
}

if (command_exist('php5')) {
	$PHP = 'php5';
} else {
	$PHP = 'php';
}

if (isset($argv[1])) {
	$tmux = new Tmux();
	$running = $tmux->get()->running;
	$delay = $tmux->get()->monitor_delay;
	$patch = $tmux->get()->patchdb;
	$restart = 'false';

	if ($running == '1' && $argv[1] == 'true') {
		$pdo->queryExec("UPDATE tmux SET value = '0' WHERE setting = 'RUNNING'");
		$sleep = $delay;
		echo $c->header("Stopping tmux scripts and waiting $sleep seconds for all panes to shutdown");
		$restart = 'true';
		sleep($sleep);
	}

	if ($patch == '1') {
		exec("cd $ROOTDIR && git pull");

		//remove folders from smarty
		if ((count(glob("${smarty}*"))) > 0) {
			echo $c->info('Removing old stuff from ' . $smarty);
			exec('rm -rf ' . $smarty . '*');
		} else {
			echo $c->info('Nothing to remove from ' . $smarty);
		}

		echo $c->primary('Patching database - ' . $dbname);
		exec("$PHP ${DIR}testing/DB/patchDB.php");
	}

	$tablecnt = 0;
	if ($pdo->dbSystem() === 'mysql') {
		$alltables = $pdo->query('SHOW TABLE STATUS WHERE Data_free / Data_length > 0.005');
		$tablecnt = count($alltables);
		foreach ($alltables as $table) {
			if ($table['name'] != 'predb') {
				echo $c->primary('Optimizing table: ' . $table['name']);
				if (strtolower($table['engine']) == 'myisam') {
					$pdo->queryDirect('REPAIR TABLE `' . $table['name'] . '`');
				}
				$pdo->queryDirect('OPTIMIZE TABLE `' . $table['name'] . '`');
			}
		}
		$pdo->queryDirect('FLUSH TABLES');
	} else if ($pdo->dbSystem() === 'pgsql') {
		$alltables = $pdo->query('SELECT table_name AS name FROM information_schema.tables WHERE table_schema = \'public\'');
		$tablecnt = count($alltables);
		foreach ($alltables as $table) {
			echo $c->primary('Vacuuming table: ' . $table['name']);
			$pdo->query('VACUUM (ANALYZE) ' . $table['name']);
		}
	}
	if ($restart == 'true' && $argv[1] == 'true') {
		echo $c->info("Starting tmux scripts");
		$pdo->queryExec('update tmux set value = \'1\' where setting = \'RUNNING\'');
	}
} else {
	exit($c->notice("\nIf you have set the settings in admin tmux, then this script will automatically do a git pull, patch the DB and delete the smarty folder contents and optimize the database.\nphp optimize.php true\n\nTo run without stopping tmux scripts run: \nphp optimize.php false\n"));
}
