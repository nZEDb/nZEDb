<?php
require_once './config.php';

use nzedb\Category;
use nzedb\Genres;
use nzedb\XXX;

$page     = new AdminPage();
$xxxmovie = new XXX(['Settings' => $page->settings]);
$gen      = new Genres(['Settings' => $page->settings]);
$id       = 0;

// Set the current action.
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

if (isset($_REQUEST["id"])) {
	$id  = $_REQUEST["id"];
	$xxx = $xxxmovie->getXXXInfo($id);

	if (!$xxx) {
		$page->show404();
	}

	switch ($action) {
		case 'submit':
			$coverLoc    = nZEDb_COVERS . "xxx/" . $id . '-cover.jpg';
			$backdropLoc = nZEDb_COVERS . "xxx/" . $id . '-backdrop.jpg';

			if ($_FILES['cover']['size'] > 0) {
				$tmpName   = $_FILES['cover']['tmp_name'];
				$file_info = getimagesize($tmpName);
				if (!empty($file_info)) {
					move_uploaded_file($_FILES['cover']['tmp_name'], $coverLoc);
				}
			}

			if ($_FILES['backdrop']['size'] > 0) {
				$tmpName   = $_FILES['backdrop']['tmp_name'];
				$file_info = getimagesize($tmpName);
				if (!empty($file_info)) {
					move_uploaded_file($_FILES['backdrop']['tmp_name'], $backdropLoc);
				}
			}

			$_POST['cover']    = (file_exists($coverLoc)) ? 1 : 0;
			$_POST['backdrop'] = (file_exists($backdropLoc)) ? 1 : 0;
			if (is_array($_POST['genre'])) {
				$genre = join(",", $_POST['genre']);
			} else {
				$genre = $_POST['genre'];
			}
			$trailerurl['url'] = $_POST['trailerurl'];
			$trailerurl        = serialize($trailerurl);
			$xxxmovie->update($id,
							  $_POST['title'],
							  $_POST['tagline'],
							  $_POST['plot'],
							  $genre,
							  $_POST['director'],
							  $_POST['actors'],
							  $_POST['extras'],
							  $_POST['productinfo'],
							  $trailerurl,
							  $_POST['directurl'],
							  $_POST['classused'],
							  $_POST['cover'],
							  $_POST['backdrop']);
			header("Location:" . WWW_TOP . "/xxx-list.php");
			die();
			break;

		case 'view':
		default:
			$page->title = "XXX Movie Edit";
			if (stristr($xxx['genre'], ",")) {
				$xxx['genre'] = explode(",", $xxx['genre']);
			}
			$xxx['trailers'] = (!empty($xxx['trailers'])) ? unserialize($xxx['trailers']) : '';
			$xxx['trailers'] = $xxx['trailers']['url'];
			$page->smarty->assign('genres', $gen->getGenres(Category::CAT_PARENT_XXX));
			$page->smarty->assign('xxxmovie', $xxx);
			break;
	}
}

$page->content = $page->smarty->fetch('xxx-edit.tpl');
$page->render();
