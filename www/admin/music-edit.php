<?php
require_once './config.php';

$page  = new AdminPage();
$music = new Music(['Settings' => $page->settings]);
$gen   = new Genres(['Settings' => $page->settings]);
$id    = 0;

// Set the current action.
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

if (isset($_REQUEST["id"])) {
	$id  = $_REQUEST["id"];
	$mus = $music->getMusicInfo($id);

	if (!$mus) {
		$page->show404();
	}

	switch ($action) {
		case 'submit':
			$coverLoc = nZEDb_COVERS . "music/" . $id . '.jpg';

			if ($_FILES['cover']['size'] > 0) {
				$tmpName   = $_FILES['cover']['tmp_name'];
				$file_info = getimagesize($tmpName);
				if (!empty($file_info)) {
					move_uploaded_file($_FILES['cover']['tmp_name'], $coverLoc);
				}
			}

			$_POST['cover']       = (file_exists($coverLoc)) ? 1 : 0;
			$_POST['salesrank']   = (empty($_POST['salesrank']) || !ctype_digit($_POST['salesrank']) ? "null" : $_POST['salesrank']);
			$_POST['releasedate'] = (empty($_POST['releasedate']) || !strtotime($_POST['releasedate'])) ? $mus['releasedate'] : date("Y-m-d H:i:s", strtotime($_POST['releasedate']));

			$music->update($id,
						   $_POST["title"],
						   $_POST['asin'],
						   $_POST['url'],
						   $_POST["salesrank"],
						   $_POST["artist"],
						   $_POST["publisher"],
						   $_POST["releasedate"],
						   $_POST["year"],
						   $_POST["tracks"],
						   $_POST["cover"],
						   $_POST["genre"]);

			header("Location:" . WWW_TOP . "/music-list.php");
			die();
			break;

		case 'view':
		default:
			$page->title = "Music Edit";
			$page->smarty->assign('music', $mus);
			$page->smarty->assign('genres', $gen->getGenres(Genres::MUSIC_TYPE));
			break;
	}
}

$page->content = $page->smarty->fetch('music-edit.tpl');
$page->render();
