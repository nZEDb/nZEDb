<?php
require_once dirname(__FILE__) . '/../../../config.php';

use nzedb\db\Settings;

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
	$tmux = new \Tmux();
	$restart = false;
	if ($argv[1] === 'true') {
		$restart = $tmux->isRunning();
	}

	if ($tmux->get()->patchdb == '1') {
		exec("cd $ROOTDIR && git pull");

		//remove folders from smarty
		if ((count(glob("${smarty}*"))) > 0) {
			echo $pdo->log->info('Removing old stuff from ' . $smarty);
			exec('rm -rf ' . $smarty . '*');
		} else {
			echo $pdo->log->info('Nothing to remove from ' . $smarty);
		}

		echo $pdo->log->primary('Patching database - ' . $dbname);
		exec("$PHP ${ROOTDIR}/cli/update_db.php true");
	}

	$tablecnt = 0;

	$alltables = $pdo->query('SHOW TABLE STATUS WHERE Data_free / Data_length > 0.005');
	$tablecnt = count($alltables);
	foreach ($alltables as $table) {
		if ($table['name'] != 'predb') {
			echo $pdo->log->primary('Optimizing table: ' . $table['name']);
			if (strtolower($table['engine']) == 'myisam') {
				$pdo->queryDirect('REPAIR TABLE `' . $table['name'] . '`');
			}
			$pdo->queryDirect('OPTIMIZE TABLE `' . $table['name'] . '`');
		}
	}
	$pdo->queryDirect('FLUSH TABLES');

	if ($restart == 'true' && $argv[1] == 'true') {
		echo $pdo->log->info("Starting tmux scripts");
		$pdo->queryExec('update tmux set value = \'1\' where setting = \'RUNNING\'');
	}
} else {
	exit($pdo->log->notice("\nIf you have set the settings in admin tmux, then this script will automatically do a git pull, patch the DB and delete the smarty folder contents and optimize the database.\nphp optimize.php true\n\nTo run without stopping tmux scripts run: \nphp optimize.php false\n"));
}
