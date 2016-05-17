<?php

use nzedb\CouchPotato;

if (!$page->users->isLoggedIn())
	$page->show403();

if (empty($_GET["id"]))
	$page->show404();

$cp = new CouchPotato($page);

if (empty($cp->cpurl))
	$page->show404();

if (empty($cp->cpapi))
	$page->show404();

$cp->sendToCouchPotato($_GET["id"]);

