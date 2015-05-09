<?php
require_once './config.php';

use nzedb\Binaries;
use nzedb\Category;

$page = new AdminPage();
$bin  = new Binaries(['Settings' => $page->settings]);
$id   = 0;

// Set the current action.
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

switch ($action) {
	case 'submit':
		if ($_POST["groupname"] == "") {
			$page->smarty->assign('error', "Group must be a valid usenet group");
			break;
		}

		if ($_POST["regex"] == "") {
			$page->smarty->assign('error', "Regex cannot be empty");
			break;
		}

		if ($_POST["id"] == "") {
			$bin->addBlacklist($_POST);
		} else {
			$ret = $bin->updateBlacklist($_POST);
		}

		header("Location:" . WWW_TOP . "/binaryblacklist-list.php");
		break;

	case 'addtest':
		if (isset($_GET['regex']) && isset($_GET['groupname'])) {
			$r = [
				'groupname' => $_GET['groupname'], 'regex' => $_GET['regex'], 'ordinal' => '1',
				'status'    => '1'
			];
			$page->smarty->assign('regex', $r);
		}
		break;

	case 'view':
	default:
		if (isset($_GET["id"])) {
			$page->title = "Binary Black/Whitelist Edit";
			$id          = $_GET["id"];
			$r           = $bin->getBlacklistByID($id);
		} else {
			$page->title = "Binary Black/Whitelist Add";
			$r           = [];
			$r["status"] = 1;
			$r["optype"] = 1;
			$r["msgcol"] = 1;
		}
		$page->smarty->assign('regex', $r);
		break;
}

$page->smarty->assign('status_ids', [Category::STATUS_ACTIVE, Category::STATUS_INACTIVE]);
$page->smarty->assign('status_names', ['Yes', 'No']);

$page->smarty->assign('optype_ids', [1, 2]);
$page->smarty->assign('optype_names', ['Black', 'White']);

$page->smarty->assign('msgcol_ids',
					  [
						  Binaries::BLACKLIST_FIELD_SUBJECT, Binaries::BLACKLIST_FIELD_FROM,
						  Binaries::BLACKLIST_FIELD_MESSAGEID
					  ]);
$page->smarty->assign('msgcol_names', ['Subject', 'Poster', 'MessageId']);

$page->content = $page->smarty->fetch('binaryblacklist-edit.tpl');
$page->render();
