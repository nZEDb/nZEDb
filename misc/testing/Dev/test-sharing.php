<?php
exit();
require_once dirname(__FILE__) . '/../../../www/config.php';
use nzedb\db\DB;
$nntp = new NNTP();
$nntp->doConnect();
$db = new DB();
$sharing = new Sharing($db, $nntp);

while (true) {
	$sharing->start();
	sleep(60);
}