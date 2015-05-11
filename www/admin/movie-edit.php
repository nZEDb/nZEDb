<?php
require_once './config.php';

use nzedb\Movie;

$page  = new AdminPage();
$movie = new Movie(['Settings' => $page->settings]);
$id    = 0;

// Set the current action.
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'view';

if (isset($_REQUEST["id"])) {
	$id  = $_REQUEST["id"];
	$mov = $movie->getMovieInfo($id);

	if (!$mov) {
		$page->show404();
	}

	switch ($action) {
		case 'submit':
			$coverLoc    = nZEDb_COVERS . "movies/" . $id . '-cover.jpg';
			$backdropLoc = nZEDb_COVERS . "movies/" . $id . '-backdrop.jpg';

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

			$movie->update($id,
						   $_POST["title"],
						   $_POST['tagline'],
						   $_POST["plot"],
						   $_POST["year"],
						   $_POST["rating"],
						   $_POST["genre"],
						   $_POST["director"],
						   $_POST["actors"],
						   $_POST["language"],
						   $_POST["cover"],
						   $_POST['backdrop']);

			header("Location:" . WWW_TOP . "/movie-list.php");
			die();
			break;

		case 'view':
		default:
			$page->title = "Movie Edit";
			$page->smarty->assign('movie', $mov);
			break;
	}
}

$page->content = $page->smarty->fetch('movie-edit.tpl');
$page->render();
