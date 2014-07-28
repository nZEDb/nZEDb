<?php

declare(ticks=1);

require_once(__DIR__ . '/../fork_daemon.php');

/* setup forking daemon */
$server = new fork_daemon();
$server->max_children_set(5);
$server->max_work_per_child_set(3);
$server->register_child_run("process_child_run");
$server->register_parent_child_exit("process_child_exit");
$server->register_logging("logger", fork_daemon::LOG_LEVEL_ALL);

test_blocking();

function test_blocking()
{
	global $server;

	echo "Adding 10 units of work\n";

	$data_set = array();
	for($i=0; $i<10; $i++) $data_set[] = $i;
	shuffle($data_set);
	$server->addwork($data_set);

	/* process work blocking mode */
	$server->process_work(true);

	echo "Adding 15 more units of work\n";

	$data_set = array();
	for($i=10; $i<25; $i++) $data_set[] = $i;
	shuffle($data_set);
	$server->addwork($data_set);

	/* process work blocking mode */
	$server->process_work(true);
}

/*
 * CALLBACK FUNCTIONS
 */

/* registered call back function */
function process_child_run($data_set, $identifier = "")
{
	echo "I'm child working on: " . implode(",", $data_set) . ($identifier == "" ? "" : " (id:$identifier)") . "\n";
	sleep(rand(4,8));
}

/* registered call back function */
function process_child_exit($pid, $identifier = "")
{
	echo "Child $pid just finished" . ($identifier == "" ? "" : " (id:$identifier)") . "\n";
}

/* registered call back function */
function logger($message)
{
	echo "logger: " . $message . PHP_EOL;
}
