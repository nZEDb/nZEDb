<?php
require_once './config.php';

// Login Check
$admin = new AdminPage;

if (isset($_GET['action']) && $_GET['action'] == "1") {
	$id = (int) $_GET['col_id'];
	(new CollectionsCleaning(['Settings' => $admin->settings]))->deleteRegex($id);
	print "Regex $id deleted.";
}