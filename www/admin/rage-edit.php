<?php
require_once './config.php';

$page   = new AdminPage();
$tvrage = new TvRage(['Settings' => $page->settings]);
$id     = 0;

// Set the current action.
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

switch ($action) {
	case 'submit':
		if ($_POST["id"] == "") {
			$imgbytes = "";
			if ($_FILES['imagedata']['size'] > 0) {
				$fileName = $_FILES['imagedata']['name'];
				$tmpName  = $_FILES['imagedata']['tmp_name'];
				$fileSize = $_FILES['imagedata']['size'];
				$fileType = $_FILES['imagedata']['type'];

				// Check the uploaded file is actually an image.
				$file_info = getimagesize($tmpName);
				if (!empty($file_info)) {
					$fp       = fopen($tmpName, 'r');
					$imgbytes = fread($fp, filesize($tmpName));
					fclose($fp);
				}
			}
			$tvrage->add($_POST["rageid"],
						 $_POST["releasetitle"],
						 $_POST["description"],
						 $_POST["genre"],
						 $_POST['country'],
						 $imgbytes);
		} else {
			$imgbytes = "";
			if ($_FILES['imagedata']['size'] > 0) {
				$fileName = $_FILES['imagedata']['name'];
				$tmpName  = $_FILES['imagedata']['tmp_name'];
				$fileSize = $_FILES['imagedata']['size'];
				$fileType = $_FILES['imagedata']['type'];

				// Check the uploaded file is actually an image.
				$file_info = getimagesize($tmpName);
				if (!empty($file_info)) {
					$fp       = fopen($tmpName, 'r');
					$imgbytes = fread($fp, filesize($tmpName));
					fclose($fp);
				}
			}
			$tvrage->update($_POST["id"],
							$_POST["rageid"],
							$_POST["releasetitle"],
							$_POST["description"],
							$_POST["genre"],
							$_POST['country'],
							$imgbytes);
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
			$id          = $_GET["id"];
			$rage        = $tvrage->getByID($id);
			$page->smarty->assign('rage', $rage);
		}
		break;
}

$page->title   = "Add/Edit TV Rage Show Data";
$page->content = $page->smarty->fetch('rage-edit.tpl');
$page->render();
