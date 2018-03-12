<?php
require_once './config.php';

use nzedb\Games;
use nzedb\Genres;
use nzedb\Category;

$page   = new AdminPage();
$games  = new Games(['Settings' => $page->settings]);
$gen    = new Genres(['Settings' => $page->settings]);
$gameID = 0;

// Set the current action.
$action = $_REQUEST['action'] ?? 'view';

if (isset($_REQUEST['id'])) {
	$gameID   = $_REQUEST['id'];
	$game = $games->getGamesInfo($gameID);

	if (!$game) {
		$page->show404();
	}

	switch ($action) {
		case 'submit':
			$coverLoc = nZEDb_COVERS . 'games/' . $gameID . '.jpg';

			if ($_FILES['cover']['size'] > 0) {
				$tmpName   = $_FILES['cover']['tmp_name'];
				$fileInfo = getimagesize($tmpName);
				if (!empty($fileInfo)) {
					move_uploaded_file($_FILES['cover']['tmp_name'], $coverLoc);
				}
			}

			$_POST['cover']       = (file_exists($coverLoc)) ? 1 : 0;
			$_POST['releasedate'] = (empty($_POST['releasedate']) || !strtotime($_POST['releasedate'])) ? $game['releasedate'] : date('Y-m-d H:i:s', strtotime($_POST['releasedate']));

			$games->update($gameID,
						   $_POST['title'],
						   $_POST['asin'],
						   $_POST['url'],
						   $_POST['publisher'],
						   $_POST['releasedate'],
						   $_POST['esrb'],
						   $_POST['cover'],
						   $_POST['trailerurl'],
						   $_POST['genre']);

			header('Location:' . WWW_TOP . '/game-list.php');
			die();
			break;

		case 'view':
		default:
			$page->title = 'Game Edit';
			$page->smarty->assign('game', $game);
			$page->smarty->assign('genres', $gen->getGenres(Category::PC_ROOT));
			break;
	}
}

$page->content = $page->smarty->fetch('game-edit.tpl');
$page->render();
