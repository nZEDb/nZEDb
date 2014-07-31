<?php
require_once './config.php';

// Login Check
$admin = new AdminPage;

if (isset($_GET['action']) && $_GET['action'] == "2") {
	$id = (int) $_GET['bin_id'];
	(new Binaries(['Settings' => $admin->settings]))->deleteBlacklist($id);
	print "Blacklist $id deleted.";
}