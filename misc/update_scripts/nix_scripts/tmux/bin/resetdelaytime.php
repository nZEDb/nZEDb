<?php

require_once(dirname(__FILE__)."/../../../../../www/config.php");
require_once(WWW_DIR."lib/framework/db.php");

$db = new DB();

//reset collections dateadded to now
print("Resetting expired collections and nzbs dateadded to now. This could take a minute or two. Really.\n");
$run = $db->queryExec("update collections set dateadded = now()");
echo $run->rowCount()." collections reset\n";
$run = $db->queryExec("update nzbs set dateadded = now()");
echo $run->rowCount()." nzbs reset\n";
