<?php
require_once './config.php';

use nzedb\Category;
use nzedb\Genres;
use nzedb\XXX;

$page     = new AdminPage();
$xxxmovie = new XXX(['Settings' => $page->settings]);
$gen      = new Genres(['Settings' => $page->settings]);
$requestID       = 0;

// Set the current action.
$action = $_REQUEST['action'] ?? 'view';

if (isset($_REQUEST['id'])) {
	$requestID  = $_REQUEST['id'];
	$xxx = $xxxmovie->getXXXInfo($requestID);

	if (!$xxx) {
		$page->show404();
	}

	switch ($action) {
		case 'submit':
			$coverLoc    = nZEDb_COVERS . 'xxx/' . $requestID . '-cover.jpg';
			$backdropLoc = nZEDb_COVERS . 'xxx/' . $requestID . '-backdrop.jpg';

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
			if (is_array($_POST['genre'])) {
				$genre = implode(',', $_POST['genre']);
			} else {
				$genre = $_POST['genre'];
			}
			$trailerurl['url'] = $_POST['trailerurl'];
			$trailerurl        = serialize($trailerurl);
			$xxxmovie->update($requestID,
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
			header('Location:' . WWW_TOP . '/xxx-list.php');
			die();
			break;

		case 'view':
		default:
			$page->title = 'XXX Movie Edit';
			if (strpos($xxx['genre'], ',') !== false) {
				$xxx['genre'] = explode(',', $xxx['genre']);
			}
			$xxx['trailers'] = (!empty($xxx['trailers'])) ? unserialize($xxx['trailers'], false) :
				'';
			$xxx['trailers'] = $xxx['trailers']['url'];
			$page->smarty->assign('genres', $gen->getGenres(Category::XXX_ROOT));
			$page->smarty->assign('xxxmovie', $xxx);
			break;
	}
}

$page->content = $page->smarty->fetch('xxx-edit.tpl');
$page->render();
