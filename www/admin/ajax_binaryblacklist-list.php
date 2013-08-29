<?php
require_once("config.php");
require_once(WWW_DIR."/lib/adminpage.php");
require_once(WWW_DIR."/lib/binaries.php");

// Login Check
$admin = new AdminPage;
$bin  = new Binaries();

if (isset($_GET['action']) && $_GET['action'] == "2")
{
	$id = (int)$_GET['bin_id'];
	$bin->deleteBlacklist($id);
	print "Blacklist $id deleted.";
}
