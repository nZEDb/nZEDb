<?php
require_once nZEDb_LIBS . 'TMDb.php';

if (!$users->isLoggedIn()) {
	$page->show403();
}

$um = new UserMovies();

if (isset($_REQUEST['del'])) {
	$usermovies = $um->delMovie($users->currentUserId(), $_REQUEST['del']);
} else if (isset($_REQUEST['add'])) {
	// Derive cats from user preferences.
	$cats = array();
	$cats[] = '2030';
	$cats[] = '2040';

	$m = new Movie(false);
	$mi = $m->getMovieInfo($_REQUEST['add']);
	if (!$mi) {
		$m->updateMovieInfo($_REQUEST['add']);
	}

	$usermovies = $um->addMovie($users->currentUserId(), $_REQUEST['add'], $cats);
} else {
	if (!isset($_REQUEST['id'])) {
		$page->show404();
	}

	$pdo = new \nzedb\db\Settings();
	$tmdb = new TMDb($pdo->getSetting('tmdbkey'), $pdo->getSetting('imdblanguage'));
	$m = new Movie(false);

	if (is_numeric($_REQUEST['id'])) {
		$movie = $m->fetchTMDBProperties($_REQUEST['id']);
		if ($movie !== false) {
			$obj = array($movie);
		}
	} else {
		$searchm = $tmdb->searchMovie($_REQUEST['id']);
		if ($searchm !== false) {
			if (isset($searchm['results'])) {
				$obj = array();
				$limit = 0;
				foreach ($searchm['results'] as $movie) {
					$limit++;
					$movieinfo = $m->fetchTMDBProperties($movie['id'], true);
					if ($movieinfo !== false) {
						$obj[] = $movieinfo;
					}
					if ($limit > 4) {
						break;
					}
				}
			}
		}
	}
	$imdbids = array();

	if (isset($obj) && count($obj) > 0) {
		foreach ($obj as $movie) {
			if (isset($movie['title']) && isset($movie['imdb_id'])) {
				$imdbids[] = str_replace('tt', '', $movie['imdb_id']);
			}
		}

		if (count($imdbids) == 0) {
			print "<h3 style='padding-top:30px;'>No results found</h3>";
		} else {
			$ourmovieimdbs = array();
			if (count($imdbids) > 0) {
				$m = new Movie();
				$allmovies = $m->getMovieInfoMultiImdb($imdbids);
				foreach ($allmovies as $ourmovie) {
					if ($ourmovie['relimdb'] != '') {
						$ourmovieimdbs[$ourmovie['imdbid']] = $ourmovie['imdbid'];
					}
				}
			}

			$userimdbs = array();
			$usermovies = $um->getMovies($users->currentUserId());
			foreach ($usermovies as $umovie) {
				$userimdbs[$umovie['imdbid']] = $umovie['imdbid'];
			}

			$page->smarty->assign('data', $obj);
			$page->smarty->assign('ourmovies', $ourmovieimdbs);
			$page->smarty->assign('userimdbs', $userimdbs);

			print $page->smarty->fetch('mymovielist.tpl');
		}
	}
}
