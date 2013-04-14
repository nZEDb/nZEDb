<?php
define('FS_ROOT', realpath(dirname(__FILE__)));
require_once(FS_ROOT."/../../www/config.php");
require_once(FS_ROOT."/../../www/lib/nzbcontents.php");

$nzbcontents = new NZBcontents();

$nzbcontents->getNFOfromNZB();
