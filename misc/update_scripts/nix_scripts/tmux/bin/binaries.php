<?php
require_once(dirname(__FILE__)."/../../../config.php");
require_once(WWW_DIR."lib/binaries.php");

$pieces = explode(" ", $argv[1]);
$binaries = new Binaries(true);
$binaries->partRepair($nntp=null, $groupArr='', trim($pieces[0],"'"), trim($pieces[1],"'"));
