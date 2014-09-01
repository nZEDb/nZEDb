<?php
require_once dirname(__FILE__) . '/../../../config.php';

(new \PreDb(['Echo' => true]))->checkPre((isset($argv[1]) && is_numeric($argv[1]) ? $argv[1] : false));
