<?php

use nzedb\CouchPotato;

if (!$page->users->isLoggedIn()){
	$page->show403();
}

if (empty($_GET["id"])) {
	$page->show404();
} else {
	$cp = new CouchPotato($page);

	if (empty($cp->cpurl)) {
		$page->show404();
	}

	if (empty($cp->cpapi)) {
		$page->show404();
	}
	$id = $_GET["id"];
	$cp->sendToCouchPotato($id);
}
