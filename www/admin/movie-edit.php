<?php
require_once './config.php';

use nzedb\Movie;

$page  = new AdminPage();
$movie = new Movie(['Settings' => $page->settings]);
$movieID    = 0;

// Set the current action.
$action = $_REQUEST['action'] ?? 'view';

if (isset($_REQUEST['id'])) {
	$movieID  = $_REQUEST['id'];
	$mov = $movie->getMovieInfo($movieID);

	if (!$mov) {
		$page->show404();
	}

	switch ($action) {
		case 'submit':
			$coverLoc    = nZEDb_COVERS . 'movies/' . $movieID . '-cover.jpg';
			$backdropLoc = nZEDb_COVERS . 'movies/' . $movieID . '-backdrop.jpg';

			if ($_FILES['cover']['size'] > 0) {
				$tmpName   = $_FILES['cover']['tmp_name'];
				$fileInfo = getimagesize($tmpName);
				if (!empty($fileInfo)) {
					move_uploaded_file($_FILES['cover']['tmp_name'], $coverLoc);
				}
			}

			if ($_FILES['backdrop']['size'] > 0) {
				$tmpName   = $_FILES['backdrop']['tmp_name'];
				$fileInfo = getimagesize($tmpName);
				if (!empty($fileInfo)) {
					move_uploaded_file($_FILES['backdrop']['tmp_name'], $backdropLoc);
				}
			}

			$_POST['cover']    = (file_exists($coverLoc)) ? 1 : 0;
			$_POST['backdrop'] = (file_exists($backdropLoc)) ? 1 : 0;

			$movie->update([
				'actors'   => $_POST['actors'],
				'backdrop' => $_POST['backdrop'],
				'cover'    => $_POST['cover'],
				'director' => $_POST['director'],
				'genre'    => $_POST['genre'],
				'imdbid'   => $movieID,
				'language' => $_POST['language'],
				'plot'     => $_POST['plot'],
				'rating'   => $_POST['rating'],
				'tagline'  => $_POST['tagline'],
				'title'    => $_POST['title'],
				'year'     => $_POST['year'],
			]);

			header('Location:' . WWW_TOP . '/movie-list.php');
			die();
			break;

		case 'view':
		default:
			$page->title = 'Movie Edit';
			$page->smarty->assign('movie', $mov);
			break;
	}
}

$page->content = $page->smarty->fetch('movie-edit.tpl');
$page->render();
