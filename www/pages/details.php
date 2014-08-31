<?php
if (!$page->users->isLoggedIn()) {
	$page->show403();
}

if (isset($_GET['id'])) {
	$releases = new Releases(['Settings' => $page->settings]);
	$data = $releases->getByGuid($_GET['id']);

	if (!$data) {
		$page->show404();
	}

	$rc = new ReleaseComments($page->settings);
	if ($page->isPostBack()) {
		$rc->addComment($data['id'], $_POST['txtAddComment'], $page->users->currentUserId(), $_SERVER['REMOTE_ADDR']);
	}

	$nfo = $releases->getReleaseNfo($data['id'], false);
	$re = new ReleaseExtra($page->settings);
	$reVideo = $re->getVideo($data['id']);
	$reAudio = $re->getAudio($data['id']);
	$reSubs = $re->getSubs($data['id']);
	$comments = $rc->getComments($data['id']);
	$similars = $releases->searchSimilar($data['id'], $data['searchname'], 6, $page->userdata['categoryexclusions']);

	$rage = $ani = $mov = $mus = $con = $game = $xxx = $boo = '';
	if ($data['rageid'] != '') {
		$tvrage = new TvRage(['Settings' => $page->settings]);
		$rageinfo = $tvrage->getByRageID($data['rageid']);
		if (count($rageinfo) > 0) {
			$seriesnames = $seriesdescription = $seriescountry = $seriesgenre = $seriesimg = $seriesid = array();
			foreach ($rageinfo as $r) {
				$seriesnames[] = $r['releasetitle'];
				if (!empty($r['description'])) {
					$seriesdescription[] = $r['description'];
				}

				if (!empty($r['country'])) {
					$seriescountry[] = $r['country'];
				}

				if (!empty($r['genre'])) {
					$seriesgenre[] = $r['genre'];
				}

				if (!empty($r['imgdata'])) {
					$seriesimg[] = $r['imgdata'];
					$seriesid[] = $r['id'];
				}
			}
			$rage = array('releasetitle' => array_shift($seriesnames),
				'description' => array_shift($seriesdescription),
				'country' => array_shift($seriescountry),
				'genre' => array_shift($seriesgenre),
				'imgdata' => array_shift($seriesimg),
				'id' => array_shift($seriesid)
			);
		}
	}

	if ($data['anidbid'] > 0) {
		$AniDB = new AniDB(['Settings' => $releases->pdo]);
		$ani = $AniDB->getAnimeInfo($data['anidbid']);
	}

	if ($data['imdbid'] != '' && $data['imdbid'] != 0000000) {
		$movie = new Movie(['Settings' => $page->settings]);
		$mov = $movie->getMovieInfo($data['imdbid']);

		$trakt = new TraktTv(['Settings' => $page->settings]);
		$traktSummary = $trakt->traktMoviesummary('tt' . $data['imdbid'], true);
		if ($traktSummary !== false &&
			isset($traktSummary['trailer']) &&
			$traktSummary['trailer'] !== '' &&
			preg_match('/[\/?]v[\/\=](\w+)$/i', $traktSummary['trailer'], $youtubeM)) {
			$mov['trailer'] =
			'<embed width="480" height="345" src="' .
			'https://www.youtube.com/v/' . $youtubeM[1] .
			'" type="application/x-shockwave-flash"></embed>';
		} else {
			$mov['trailer'] = nzedb\utility\imdb_trailers($data['imdbid']);
		}

		if ($mov && isset($mov['title'])) {
			$mov['title'] = str_replace(array('/', '\\'), '', $mov['title']);
			$mov['actors'] = $movie->makeFieldLinks($mov, 'actors');
			$mov['genre'] = $movie->makeFieldLinks($mov, 'genre');
			$mov['director'] = $movie->makeFieldLinks($mov, 'director');
		} else if ($traktSummary !== false) {
			$mov['title'] = str_replace(array('/', '\\'), '', $traktSummary['title']);
		} else {
			$mov = false;
		}
	}

	if ($data['xxxinfo_id'] != '' && $data['xxxinfo_id'] != 0) {
		$x = new XXX(['Settings' => $page->settings]);
		$xxx = $x->getXXXInfo($data['xxxinfo_id']);

		if (isset($xxx['trailers'])) {
			$xxx['trailers'] = $x->insertSwf($xxx['classused'], $xxx['trailers']);
		}

		if ($xxx && isset($xxx['title'])) {
			$xxx['title'] = str_replace(array('/', '\\'), '', $xxx['title']);
			$xxx['actors'] = $x->makeFieldLinks($xxx, 'actors');
			$xxx['genre'] = $x->makeFieldLinks($xxx, 'genre');
			$xxx['director'] = $x->makeFieldLinks($xxx, 'director');
		}else{
			$xxx = false;
		}
	}

	if ($data['musicinfoid'] != '') {
		$music = new Music(['Settings' => $page->settings]);
		$mus = $music->getMusicInfo($data['musicinfoid']);
	}

	if ($data['consoleinfoid'] != '') {
		$c = new Console(['Settings' => $page->settings]);
		$con = $c->getConsoleInfo($data['consoleinfoid']);
	}

	if ($data['gamesinfo_id'] != '') {
		$g = new Games(['Settings' => $page->settings]);
		$game = $g->getgamesInfo($data['gamesinfo_id']);
	}

	if ($data['bookinfoid'] != '') {
		$b = new Books(['Settings' => $page->settings]);
		$boo = $b->getBookInfo($data['bookinfoid']);
	}

	$rf = new ReleaseFiles($page->settings);
	$releasefiles = $rf->get($data['id']);

	$predb = new PreDb(['Settings' => $page->settings]);
	$pre = $predb->getForRelease($data['preid']);

	$user = $page->users->getById($page->users->currentUserId());

	$page->smarty->assign('cpapi',  $user['cp_api']);
	$page->smarty->assign('cpurl', $user['cp_url']);
	$page->smarty->assign('releasefiles', $releasefiles);
	$page->smarty->assign('release', $data);
	$page->smarty->assign('reVideo', $reVideo);
	$page->smarty->assign('reAudio', $reAudio);
	$page->smarty->assign('reSubs', $reSubs);
	$page->smarty->assign('nfo', $nfo);
	$page->smarty->assign('rage', $rage);
	$page->smarty->assign('movie', $mov);
	$page->smarty->assign('xxx', $xxx);
	$page->smarty->assign('anidb', $ani);
	$page->smarty->assign('music', $mus);
	$page->smarty->assign('con', $con);
	$page->smarty->assign('game', $game);
	$page->smarty->assign('boo', $boo);
	$page->smarty->assign('pre', $pre);
	$page->smarty->assign('comments', $comments);
	$page->smarty->assign('similars', $similars);
	$page->smarty->assign('searchname', $releases->getSimilarName($data['searchname']));

	$page->meta_title = 'View NZB';
	$page->meta_keywords = 'view,nzb,description,details';
	$page->meta_description = 'View NZB for' . $data['searchname'];

	$page->content = $page->smarty->fetch('viewnzb.tpl');
	$page->render();
}
