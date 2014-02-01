<?php
if (!$users->isLoggedIn()) {
	$page->show403();
}

if (isset($_GET['id'])) {
	$releases = new Releases();
	$data = $releases->getByGuid($_GET['id']);

	if (!$data) {
		$page->show404();
	}

	$rc = new ReleaseComments();
	if ($page->isPostBack()) {
		$rc->addComment($data['id'], $_POST['txtAddComment'], $users->currentUserId(), $_SERVER['REMOTE_ADDR']);
	}

	$nfo = $releases->getReleaseNfo($data['id'], false);
	$re = new ReleaseExtra;
	$reVideo = $re->getVideo($data['id']);
	$reAudio = $re->getAudio($data['id']);
	$reSubs = $re->getSubs($data['id']);
	$comments = $rc->getComments($data['id']);
	$similars = $releases->searchSimilar($data['id'], $data['searchname'], 6, $page->userdata['categoryexclusions']);

	$rage = $ani = $mov = $mus = $con = $boo = '';
	if ($data['rageid'] != '') {
		$tvrage = new TvRage();
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
		$AniDB = new AniDB();
		$ani = $AniDB->getAnimeInfo($data['anidbid']);
	}

	if ($data['imdbid'] != '') {
		$movie = new Movie();
		$mov = $movie->getMovieInfo($data['imdbid']);

		if ($mov) {
			$mov['title'] = str_replace(array('/', '\\'), '', $mov['title']);
			$mov['actors'] = $movie->makeFieldLinks($mov, 'actors');
			$mov['genre'] = $movie->makeFieldLinks($mov, 'genre');
			$mov['director'] = $movie->makeFieldLinks($mov, 'director');
		}
	}

	if ($data['musicinfoid'] != '') {
		$music = new Music();
		$mus = $music->getMusicInfo($data['musicinfoid']);
	}

	if ($data['consoleinfoid'] != '') {
		$c = new Console();
		$con = $c->getConsoleInfo($data['consoleinfoid']);
	}

	if ($data['bookinfoid'] != '') {
		$b = new Books();
		$boo = $b->getBookInfo($data['bookinfoid']);
	}

	$rf = new ReleaseFiles();
	$releasefiles = $rf->get($data['id']);

	$predb = new PreDb();
	$pre = $predb->getForRelease($data['preid']);

	$page->smarty->assign('releasefiles', $releasefiles);
	$page->smarty->assign('release', $data);
	$page->smarty->assign('reVideo', $reVideo);
	$page->smarty->assign('reAudio', $reAudio);
	$page->smarty->assign('reSubs', $reSubs);
	$page->smarty->assign('nfo', $nfo);
	$page->smarty->assign('rage', $rage);
	$page->smarty->assign('movie', $mov);
	$page->smarty->assign('anidb', $ani);
	$page->smarty->assign('music', $mus);
	$page->smarty->assign('con', $con);
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
