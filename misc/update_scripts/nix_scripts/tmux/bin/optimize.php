<?php
require_once dirname(__FILE__) . '/../../../config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'tmux.php';
require_once nZEDb_LIB . 'ColorCLI.php';

$c = new ColorCLI;
$db = new DB();
$DIR = nZEDb_MISC;
$ROOTDIR = nZEDb_ROOT;
$smarty = SMARTY_DIR.'templates_c/';
$dbname = DB_NAME;

function command_exist($cmd) {
	$returnVal = shell_exec("which $cmd");
	return (empty($returnVal) ? false : true);
}

if (command_exist('php5'))
	$PHP = 'php5';
else
	$PHP = 'php';

if( isset($argv[1]) )
{
	$tmux = new Tmux();
	$running = $tmux->get()->running;
	$delay = $tmux->get()->monitor_delay;
	$patch = $tmux->get()->patchdb;
	$restart = 'false';

	if ( $running == 'TRUE' && $argv[1] == 'true' )
	{
		$db->queryExec('update tmux set value = \'FALSE\' where setting = \'RUNNING\'');
		$sleep = $delay;
		echo $c->header("Stopping tmux scripts and waiting $sleep seconds for all panes to shutdown");
		$restart = 'true';
		sleep($sleep);
	}

	if ( $patch == 'TRUE' )
	{
        exec("cd $ROOTDIR && git pull");

		//remove folders from smarty
		if ((count(glob("${smarty}*"))) > 0)
		{
			echo $c->info('Removing old stuff from '.$smarty);
			exec('rm -rf '.$smarty.'*');
		}
		else
			echo $c->info('Nothing to remove from '.$smarty);

		echo $c->primary('Patching database - '.$dbname);
		exec("$PHP ${DIR}testing/DB_scripts/patchDB.php");
	}

	$tablecnt = 0;
	if ($db->dbSystem() == 'mysql')
	{
		$alltables = $db->query('SHOW table status WHERE Data_free > 0');
		$tablecnt = count($alltables);
		foreach ($alltables as $table)
		{
			if ($table['name']!='predb')
			{
				echo $c->primary('Optimizing table: '.$table['name']);
				if (strtolower($table['engine']) == 'myisam')
					$db->queryDirect('REPAIR TABLE `'.$table['name'].'`');
				$db->queryDirect('OPTIMIZE TABLE `'.$table['name'].'`');
			}
		}
		$db->queryDirect('FLUSH TABLES');
	}
	else if ($db->dbSystem() == 'pgsql')
	{
		$alltables = $db->query('SELECT table_name as name FROM information_schema.tables WHERE table_schema = \'public\'');
		$tablecnt = count($alltables);
		foreach ($alltables as $table)
		{
			echo $c->primary('Vacuuming table: '.$table['name']);
			$db->query('VACUUM (ANALYZE) '.$table['name']);
		}
	}
	if ( $restart == 'true'  && $argv[1] == 'true' )
	{
		echo $c->info("Starting tmux scripts");
		$db->queryExec('update tmux set value = \'TRUE\' where setting = \'RUNNING\'');
	}
}
else
	exit($c->notice("\nIf you have set the settings in admin tmux, then this script will automatically do a git pull, patch the DB and delete the smarty folder contents and optimize the database.\nphp optimize.php true\n\nTo run without stopping tmux scripts run: \nphp optimize.php false\n"));
?>
