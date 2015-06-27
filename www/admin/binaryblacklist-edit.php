<?php
require_once './config.php';

use nzedb\Binaries;
use nzedb\Category;

$page = new AdminPage();
$bin  = new Binaries(['Settings' => $page->settings]);
$error = '';
$regex = ['id' => '', 'groupname' => '', 'regex' => '', 'description' => ''];

switch ((isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view')) {
	case 'submit':
		if ($_POST["groupname"] == '') {
			$error = "Group must be a valid usenet group";
			break;
		}

		if ($_POST["regex"] == '') {
			$error = "Regex cannot be empty";
			break;
		}

		if ($_POST["id"] == '') {
			$bin->addBlacklist($_POST);
		} else {
			$ret = $bin->updateBlacklist($_POST);
		}

		header("Location:" . WWW_TOP . "/binaryblacklist-list.php");
		break;

	case 'addtest':
		if (isset($_GET['regex']) && isset($_GET['groupname'])) {
			$regex += [
				'groupname' => $_GET['groupname'],
				'regex' => $_GET['regex'],
				'ordinal' => '1',
				'status'    => '1'
			];
		}
		break;

	case 'view':
	default:
		if (isset($_GET["id"])) {
			$page->title = "Binary Black/Whitelist Edit";
			$regex = $bin->getBlacklistByID($_GET["id"]);
		} else {
			$page->title = "Binary Black/Whitelist Add";
			$regex += [
				'status' => 1,
				'optype' => 1,
				'msgcol' => 1
			];
		}
		break;
}

$page->smarty->assign([
		'error'        => $error,
		'regex'        => $regex,
		'status_ids'   => [Category::STATUS_ACTIVE, Category::STATUS_INACTIVE],
		'status_names' => ['Yes', 'No'],
		'optype_ids'   => [1, 2],
		'optype_names' => ['Black', 'White'],
		'msgcol_ids'   => [
			Binaries::BLACKLIST_FIELD_SUBJECT,
			Binaries::BLACKLIST_FIELD_FROM,
			Binaries::BLACKLIST_FIELD_MESSAGEID
		],
		'msgcol_names' => ['Subject', 'Poster', 'MessageId']
	]
);

$page->content = $page->smarty->fetch('binaryblacklist-edit.tpl');
$page->render();
