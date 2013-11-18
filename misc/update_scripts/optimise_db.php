<?php
require_once dirname(__FILE__) . '/config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'ColorCLI.php';

$c = new ColorCLI;
$db = new DB();
$type = $db->dbSystem();
if (isset($argv[1]) && $argv[1] === "run")
{
	if ($type == 'mysql')
	{
		$a = 'MySQL';
		$b = 'Optimizing';
		$c = 'Optimized';
	}
	if ($type == 'pgsql')
	{
		$a = 'PostgreSQL';
		$b = 'Vacuuming';
		$c = 'Vacuumed';
	}
	echo $c->header("{$b} {$a} tables, this can take a while...");
	$tablecnt = $db->optimise();
	if ($tablecnt > 0)
		exit($c->primary("{$c} {$tablecnt} {$a} tables succesfuly."));
	else
		exit($c->notice("No {$a} tables to optimize."));
}
else
	exit($c->error("\nWrong set of arguments.\n"
		."php optimise_db.php true		...: Optimise the database.\n"));
?>
