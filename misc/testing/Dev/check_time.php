<?php
// Shitty script to check time in php mysql and system...
require_once dirname(__FILE__) . '/../../../www/config.php';
$n = PHP_EOL;
$db = new DB();

$x = $db->queryOneRow('SELECT NOW() AS time');
echo 'System time  : ' . exec('date') . $n;
echo 'PHP time     : ' . date('r') . $n;
echo 'MYSQL time   : ' . $x['time'] . $n . $n;

exit('If one of these is off, you might have a wrong setting somewhere.' . $n);