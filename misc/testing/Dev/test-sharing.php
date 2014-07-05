<?php
exit();
require_once dirname(__FILE__) . '/../../../www/config.php';
use nzedb\db\Settings;
$nntp = new NNTP();
$nntp->doConnect();
$pdo = new Settings();
$sharing = new Sharing($pdo, $nntp);

while (true) {
	$sharing->start();
	sleep(60);
}
