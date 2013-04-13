<?php

define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(FS_ROOT."/../../www/lib/nzbcontents.php");

$nzbcontents = new NZBContents();

$nzbcontents->getNzbContents("8f43fae6f523f919ef8d9c08539a3887");
