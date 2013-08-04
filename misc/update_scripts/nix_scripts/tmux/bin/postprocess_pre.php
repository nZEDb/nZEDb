<?php

require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/predb.php");

$postprocess = new PostProcess(true);
$postprocess->processPredb();
