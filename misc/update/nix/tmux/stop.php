<?php

require_once realpath(__DIR__ . '/../../../../www/config.php');

use nzedb\Tmux;

$restart = (new Tmux())->stopIfRunning();
