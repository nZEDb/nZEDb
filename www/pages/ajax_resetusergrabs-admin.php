<?php

use nzedb\Users;

$page = new AdminPage();
$u = new Users();

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$id = (isset($_REQUEST['id'])) ? $_REQUEST['id'] : '';

switch($action)
{
	case 'grabs':
		$u->delDownloadRequests($id);
	break;
	case 'api':
		$u->delApiRequests($id);
	break;
	default:
		$page->show404();
	break;
}

