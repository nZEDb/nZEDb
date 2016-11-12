<?php

require_once realpath(dirname(dirname(dirname(dirname(__DIR__)))) . DIRECTORY_SEPARATOR . 'bootstrap.php');

use nzedb\Tmux;

$restart = (new Tmux())->stopIfRunning();
