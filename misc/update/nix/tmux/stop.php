<?php

require_once realpath(__DIR__ . '/../../../../www/config.php');

$restart = (new \Tmux())->stopIfRunning();
