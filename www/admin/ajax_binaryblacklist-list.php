<?php
require_once './config.php';

// Login Check
$admin = new AdminPage;
$bin = new Binaries(['Settings' => $admin->settings]);

if (isset($_GET['action']) && $_GET['action'] == "2") {
	$id = (int) $_GET['bin_id'];
	$bin->deleteBlacklist($id);
	print "Blacklist $id deleted.";
}