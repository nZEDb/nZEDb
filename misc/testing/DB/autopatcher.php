<?php
require_once dirname(__FILE__) . '/../../../www/config.php';

use nzedb\db\Settings;

$pdo = new Settings();
$DIR = nZEDb_MISC;
$smarty = new Smarty;
$dbname = DB_NAME;
$cli = new \ColorCLI();

if (isset($argv[1]) && ($argv[1] == "true" || $argv[1] == "safe")) {
	$restart = (new \Tmux())->isRunning();

	system("cd $DIR && git pull");

	if (\nzedb\utility\Utility::hasCommand("php5")) {
		$PHP = "php5";
	} else {
		$PHP = "php";
	}

	echo $cli->header("Patching database - ${dbname}.");

	$safe = ($argv[1] === "safe") ? true : false;
	system("$PHP " . nZEDb_ROOT . 'cli' . DS . "update_db.php true $safe");

	// Remove folders from smarty.
	$cleared = $smarty->clearCompiledTemplate();
	if ($cleared) {
		echo $cli->header("The smarty template cache has been cleaned for you");
	} else {
		echo $cli->header("You should clear your smarty template cache at: " . SMARTY_DIR . "templates_c");
	}

	if ($restart) {
		echo $cli->header("Starting tmux scripts.");
		$pdo->queryExec("UPDATE tmux SET value = '1' WHERE setting = 'RUNNING'");
	}
} else {
	exit($cli->error("\nThis script will automatically do a git pull, patch the DB and delete the smarty folder contents.\n\n"
			. "php $argv[0] true   ...: To run.\n"
			. "php $argv[0] safe   ...: Tto run a backup of your database and then update.\n"));
}
?>
