<?php
require_once './config.php';

use nzedb\TvRage;

$page   = new AdminPage();
$tvRage = new TvRage(['Settings' => $page->settings]);

$rage = [
	'id' => '', 'description' => '', 'releasetitle' => '', 'genre' => '',
	'rageid' => '', 'country' => '', 'imgdata' => ''
];

switch ((isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view')) {
	case 'submit':
		if ($_POST["id"] == '') {
			$tvRage->add($_POST["rageid"], $_POST["releasetitle"], $_POST["description"],
				$_POST["genre"], $_POST['country'], getImage()
			);
		} else {
			$tvRage->update($_POST["id"], $_POST["rageid"], $_POST["releasetitle"],
				$_POST["description"], $_POST["genre"], $_POST['country'], getImage()
			);
		}

		if (isset($_POST['from']) && !empty($_POST['from'])) {
			header("Location:" . $_POST['from']);
			exit;
		}

		header("Location:" . WWW_TOP . "/rage-list.php");
		break;

	case 'view':
	default:
		if (isset($_GET["id"])) {
			$page->title = "Tv Rage Edit";
			$rage = $tvRage->getByID($_GET["id"]);
		}
		break;
}

$page->smarty->assign('rage', $rage);

$page->title   = "Add/Edit TV Rage Show Data";
$page->content = $page->smarty->fetch('rage-edit.tpl');
$page->render();

function getImage() {
	$imgBytes = '';
	if ($_FILES['imagedata']['size'] > 0) {
		$tmpName = $_FILES['imagedata']['tmp_name'];
		// Check the uploaded file is actually an image.
		if (!empty(getimagesize($tmpName))) {
			$fp       = fopen($tmpName, 'r');
			$imgBytes = fread($fp, filesize($tmpName));
			fclose($fp);
		}
	}
	return $imgBytes;
}