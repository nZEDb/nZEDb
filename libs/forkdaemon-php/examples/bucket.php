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

test_bucket();

function test_bucket()
{
	global $server;

	define("BUCKET1", 1);
	define("BUCKET2", 2);

	$server->add_bucket(BUCKET1);
	$server->add_bucket(BUCKET2);
	$server->max_children_set(2, BUCKET1);
	$server->max_children_set(5, BUCKET2);

	$data_set = array();
	for($i=0; $i<100; $i++) $data_set[] = $i;

	/* add work to bucket 1 */
	shuffle($data_set);
	$server->addwork($data_set, "", BUCKET1);

	/* add work to bucket 2 */
	shuffle($data_set);
	$server->addwork($data_set, "", BUCKET2);

	/* wait until all work allocated */
	while ($server->work_sets_count(BUCKET1) > 0 || $server->work_sets_count(BUCKET2) > 0)
	{
		echo "work set count(1): " . $server->work_sets_count(BUCKET1) . ", count(2): " . $server->work_sets_count(BUCKET2) . "\n";
		if ($server->work_sets_count(BUCKET1) > 0) $server->process_work(false, BUCKET1);
		if ($server->work_sets_count(BUCKET2) > 0) $server->process_work(false, BUCKET2);
		sleep(1);
	}

	/* wait until all children finish */
	while ($server->children_running() > 0)
	{
		echo "waiting for " . $server->children_running() . " children to finish\n";
		sleep(1);
	}
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
