<?php
require_once realpath(dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'bootstrap.php');

use nzedb\db\DB;

$pdo = new DB();


exit($pdo->log->error('Non-threaded update_releases.php is not supported anymore.You must use /misc/update/nix/multiprocessing/releases.php'));


