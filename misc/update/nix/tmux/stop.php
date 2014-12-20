<?php

require_once dirname(__FILE__) . '/../../../../www/config.php';

#use nzedb\db\Settings;

#$pdo = new Settings();
$restart = (new \Tmux())->isRunning();


