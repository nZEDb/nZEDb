<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();
$DIR = nZEDb_MISC;
$smarty = new Smarty;
$dbname = DB_NAME;
$restart = "false";
$c = new ColorCLI();

if (isset($argv[1]) && ($argv[1] == "true" || $argv[1] == "safe")) {
	$tmux = new Tmux();
	$running = $tmux->get()->running;
	$delay = $tmux->get()->monitor_delay;

	if ($running == "1") {
		$pdo->queryExec("UPDATE tmux SET value = '0' WHERE setting = 'RUNNING'");
		$sleep = $delay;
		echo $c->header("Stopping tmux scripts and waiting $sleep seconds for all panes to shutdown.");
		sleep($sleep);
		$restart = "true";
	}

	system("cd $DIR && git pull");

	if (\nzedb\utility\Utility::hasCommand("php5")) {
		$PHP = "php5";
	} else {
		$PHP = "php";
	}

	echo $c->header("Patching database - ${dbname}.");

	$safe = ($argv[1] === "safe") ? true : false;
	system("$PHP " . nZEDb_LIB . 'db' . DS . "DbUpdate.php 1 $safe");

	// Remove folders from smarty.
	$cleared = $smarty->clearCompiledTemplate();
	if ($cleared) {
		echo $c->header("The smarty template cache has been cleaned for you");
	} else {
		echo $c->header("You should clear your smarty template cache at: " . SMARTY_DIR . "templates_c");
	}

	if ($restart == "true") {
		echo $c->header("Starting tmux scripts.");
		$pdo->queryExec("UPDATE tmux SET value = '1' WHERE setting = 'RUNNING'");
	}
} else {
	exit($c->error("\nThis script will automatically do a git pull, patch the DB and delete the smarty folder contents.\n\n"
			. "php $argv[0] true   ...: To run.\n"
			. "php $argv[0] safe   ...: Tto run a backup of your database and then update.\n"));
}
?>
