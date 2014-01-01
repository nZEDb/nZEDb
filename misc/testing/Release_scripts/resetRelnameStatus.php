<?php

require_once dirname(__FILE__) . '/../../../www/config.php';
require_once nZEDb_LIB . 'framework/db.php';
require_once nZEDb_LIB . 'ColorCLI.php';

$c = new ColorCLI();


if (!isset($argv[1])) {
    passthru('clear');
    exit($c->error("\nThis script will set bitwise = 0 or all rename bits to unchecked or just specific bits.\nTo reset all releases to 0, run:\nphp $argv[0] true\n\nTo reset all rename bits run:\nphp $argv[0] rename\n\nTo run on specific bit, run:\nphp $argv[0] 512\n\n"));
}

$db = new DB();
if ($argv[1] === 'true') {
    $res = $db->queryExec('UPDATE releases SET bitwise = 0');
} else if ($argv[1] === 'rename') {
    $res = $db->queryExec('UPDATE releases SET bitwise = ((bitwise & ~252)|0)');
} else if (is_numeric($argv[1])) {
    $res = $db->queryExec('UPDATE releases SET bitwise = ((bitwise & ~' . $argv[1] . ')|0)');
}

if ($res->rowCount() > 0 && is_numeric($argv[1])) {
    echo $c->header('Succesfully reset the bitwise of ' . $res->rowCount() . ' releases to 0 for bit(s) ' . $argv[1] . '.');
} else if ($res->rowCount() > 0) {
    echo $c->header('Succesfully reset the bitwise of ' . $res->rowCount() . ' releases to unprocessed.');
} else {
    echo $c->header('No releases to be reset.');
}
