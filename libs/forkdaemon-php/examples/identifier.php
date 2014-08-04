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

test_identifier();

function test_identifier()
{
	global $server;

	$server->child_single_work_item_set(true);
	$server->max_work_per_child_set(1);

	echo "Adding 100 units of work\n";

	/* add work */
	$data_set = array();
	for($i=0; $i<100; $i++) $data_set[] = $i;
	shuffle($data_set);
	$data_set = array_chunk($data_set, 3);

	$i = 0;
	foreach ($data_set as $item)
	{
		$server->addwork($item, "IDn$i");
		$i++;
	}

	echo "Processing work in non-blocking mode\n";

	/* process work non blocking mode */
	$server->process_work(false);

	/* wait until all work allocated */
	while ($server->work_sets_count() > 0)
	{
		echo "work set count: " . $server->work_sets_count() . "\n";
		$server->process_work(false);
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
