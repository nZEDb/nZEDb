<?php

require("config.php");
require_once(WWW_DIR."/lib/framework/db.php");

$db = new DB;
$db->optimise();

?>