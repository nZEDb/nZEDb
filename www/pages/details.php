<?php

use nzedb\AniDB;
use nzedb\Books;
use nzedb\Console;
use nzedb\Games;
use nzedb\Movie;
use nzedb\Music;
use nzedb\PreDb;
use nzedb\ReleaseComments;
use nzedb\ReleaseExtra;
use nzedb\ReleaseFiles;
use nzedb\Releases;
use nzedb\TraktTv;
use nzedb\TvRage;
use nzedb\XXX;

if (!$page->users->isLoggedIn()) {
	$page->show403();
}

if (isset($_GET['id'])) {
	$releases = new Releases(['Settings' => $page->settings]);
	$data     = $releases->getByGuid($_GET['id']);

	if (!$data) {
		$page->show404();
	}

	$rc = new ReleaseComments($page->settings);
	if ($page->isPostBack()) {
		$rc->addComment($data['id'], $_POST['txtAddComment'], $page->users->currentUserId(), $_SERVER['REMOTE_ADDR']);
	}

	$rage = $mov = $xxx = '';
	if ($data['rageid'] != '') {
		$rageInfo = (new TvRage(['Settings' => $page->settings]))->getByRageID($data['rageid']);
		if (count($rageInfo) > 0) {
			$rage = ['releasetitle' => '', 'description' => '', 'country' => '', 'genre' => '', 'imgdata' => '', 'id' => ''];
			$done = 1;
			$needed = count($rage);
			foreach ($rageInfo as $info) {
				foreach($rage as $key => $value) {
					if (empty($value) && !empty($info[$key])) {
						$rage[$key] = $info[$key];
						$done++;
					}
				}
				if ($done == $needed) {
					break;
				}
			}
		}
	}

	if ($data['imdbid'] != '' && $data['imdbid'] != 0000000) {
		$movie = new Movie(['Settings' => $page->settings]);
		$mov   = $movie->getMovieInfo($data['imdbid']);
		if ($mov && isset($mov['title'])) {
			$mov['title']    = str_replace(['/', '\\'], '', $mov['title']);
			$mov['actors']   = $movie->makeFieldLinks($mov, 'actors');
			$mov['genre']    = $movie->makeFieldLinks($mov, 'genre');
			$mov['director'] = $movie->makeFieldLinks($mov, 'director');
			if ($page->settings->getSetting('trailers_display')) {
				$trailer = (!isset($mov['trailer']) || empty($mov['trailer']) || $mov['trailer'] == '' ? $movie->getTrailer($data['imdbid']) : $mov['trailer']);
				if ($trailer) {
					$mov['trailer'] = sprintf(
						"<iframe width=\"%d\" height=\"%d\" src=\"%s\"></iframe>",
						$page->settings->getSetting('trailers_size_x'),
						$page->settings->getSetting('trailers_size_y'),
						$trailer
					);
				}
			}
		}
	}

	if ($data['xxxinfo_id'] != '' && $data['xxxinfo_id'] != 0) {
		$XXX   = new XXX(['Settings' => $page->settings]);
		$xxx = $XXX->getXXXInfo($data['xxxinfo_id']);
		if ($xxx && isset($xInfo['title'])) {
			$xxx['title']    = str_replace(['/', '\\'], '', $xxx['title']);
			$xxx['actors']   = $XXX->makeFieldLinks($xxx, 'actors');
			$xxx['genre']    = $XXX->makeFieldLinks($xxx, 'genre');
			$xxx['director'] = $XXX->makeFieldLinks($xxx, 'director');
			if (isset($xxx['trailers'])) {
				$xxx['trailers'] = $XXX->insertSwf($xxx['classused'], $xxx['trailers']);
			}
		} else {
			$xxx = false;
		}
	}

	$user = $page->users->getById($page->users->currentUserId());
	$re  = new ReleaseExtra($page->settings);

	$page->smarty->assign([
		'anidb'   => ($data['anidbid'] > 0 ? (new AniDB(['Settings' => $page->settings]))->getAnimeInfo($data['anidbid']) : ''),
		'boo'   => ($data['bookinfoid'] != '' ? (new Books(['Settings' => $page->settings]))->getBookInfo($data['bookinfoid']) : ''),
		'con'   => ($data['consoleinfoid'] != '' ? (new Console(['Settings' => $page->settings]))->getConsoleInfo($data['consoleinfoid']) : ''),
		'game'  => ($data['gamesinfo_id'] != '' ? (new Games(['Settings' => $page->settings]))->getgamesInfo($data['gamesinfo_id']) : ''),
		'movie'   => $mov,
		'music' => ($data['musicinfoid'] != '' ? (new Music(['Settings' => $page->settings]))->getMusicInfo($data['musicinfoid']) : ''),
		'pre'   => (new PreDb(['Settings' => $page->settings]))->getForRelease($data['preid']),
		'rage'  => $rage,
		'xxx'   => $xxx,
		'comments' => $rc->getComments($data['id']),
		'cpapi'    => $user['cp_api'],
		'cpurl'    => $user['cp_url'],
		'nfo'      => $releases->getReleaseNfo($data['id'], false),
		'release'  => $data,
		'reAudio'  => $re->getAudio($data['id']),
		'reSubs'   => $re->getSubs($data['id']),
		'reVideo'  => $re->getVideo($data['id']),
		'similars' => $releases->searchSimilar($data['id'], $data['searchname'], 6, $page->userdata['categoryexclusions']),
		'privateprofiles' => ($page->settings->getSetting('privateprofiles') == 1 ? true : false),
		'releasefiles'    => (new ReleaseFiles($page->settings))->get($data['id']),
		'searchname'      => $releases->getSimilarName($data['searchname']),
	]);

	$page->smarty->assign('rage', $rage);

	$page->meta_title       = 'View NZB';
	$page->meta_keywords    = 'view,nzb,description,details';
	$page->meta_description = 'View NZB for' . $data['searchname'];

	$page->content = $page->smarty->fetch('viewnzb.tpl');
	$page->render();
}
